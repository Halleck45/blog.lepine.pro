#!/usr/bin/env php
<?php
/**
 * Build related post suggestions for Jekyll posts using OpenAI.
 *
 * - Reads atom feed (build/atom.xml preferred, fallback to atom.xml)
 * - Scans _posts and _posts/en to map URLs to files
 * - For each post, asks OpenAI to pick 3-6 relevant suggestions from the full list
 * - Writes into front matter under key `suggestions` as a YAML array of maps: { title, link }
 * - Language is inferred from the URL (/en/ => en, else fr) and enforced in the prompt.
 *
 * Env vars:
 *   OPENAI_API_KEY   required
 *   OPENAI_BASE_URL  optional (default https://api.openai.com)
 *   OPENAI_MODEL     optional (default gpt-4.1-mini)
 */

function out($msg)
{
    fwrite(STDOUT, $msg . "\n");
}

function err($msg)
{
    fwrite(STDERR, "[ERR] " . $msg . "\n");
}

$root = dirname(__DIR__);
$postsDirs = array(
        $root . '/_posts',
        $root . '/_posts/en',
);

$apiKey = getenv('OPENAI_API_KEY');
if (!$apiKey) {
    err('OPENAI_API_KEY not set');
    exit(1);
}
$baseUrl = rtrim(getenv('OPENAI_BASE_URL') ? getenv('OPENAI_BASE_URL') : 'https://api.openai.com', '/');
$model = getenv('OPENAI_MODEL') ? getenv('OPENAI_MODEL') : 'gpt-4.1-nano';

$feedPath = $root . '/build/atom.xml';
if (!is_file($feedPath)) {
    $alt = $root . '/atom.xml';
    if (is_file($alt)) {
        $feedPath = $alt;
    }
}
if (!is_file($feedPath)) {
    err('atom.xml not found (looked for build/atom.xml and atom.xml)');
    exit(1);
}

// Simple YAML front matter parse/serialize
function parseFrontMatter($content)
{
    if (!preg_match('/^---\n(.*?)\n---\n/s', $content, $m)) {
        return array(null, null, null);
    }
    $yaml = $m[1];
    $body = substr($content, strlen($m[0]));
    $lines = preg_split('/\r?\n/', $yaml);
    $data = array();
    $currentKey = null;
    $indentLevel = null;
    $buffer = array();
    foreach ($lines as $line) {
        if (preg_match('/^([A-Za-z0-9_\-]+):\s*(.*)$/', $line, $mm)) {
            if ($currentKey !== null && $buffer) {
                $data[$currentKey] = rtrim(implode("\n", $buffer));
                $buffer = array();
            }
            $k = $mm[1];
            $v = $mm[2];
            if ($v === '|' || $v === '>' || $v === '') {
                $currentKey = $k;
                $buffer = array();
                $indentLevel = null;
                $data[$k] = '';
            } else {
                $currentKey = null;
                if ((strlen($v) >= 2) && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                    $v = substr($v, 1, -1);
                }
                $data[$k] = $v;
            }
        } else if ($currentKey !== null) {
            if ($indentLevel === null) {
                $indentLevel = strlen($line) - strlen(ltrim($line, ' '));
            }
            $buffer[] = $indentLevel > 0 ? substr($line, $indentLevel) : ltrim($line);
        }
    }
    if ($currentKey !== null && $buffer) {
        $data[$currentKey] = rtrim(implode("\n", $buffer));
    }
    return array($data, $yaml, $body);
}

function serializeFrontMatterWithSuggestions($originalYaml, $suggestions)
{
    // Build YAML fragment for suggestions
    $s = "suggestions:\n";
    foreach ($suggestions as $it) {
        $t = isset($it['title']) ? $it['title'] : '';
        $l = isset($it['link']) ? $it['link'] : '';
        // escape quotes minimally
        $t = str_replace('"', '\"', $t);
        $s .= "  - title: \"" . str_replace("\n", ' ', trim($t)) . "\"\n";
        $s .= "    link: " . trim($l) . "\n";
    }

    $lines = preg_split('/\r?\n/', $originalYaml);
    $has = false;
    $start = -1;
    $end = count($lines);
    foreach ($lines as $i => $line) {
        if (preg_match('/^suggestions:\s*$/', $line)) {
            $has = true;
            $start = $i;
            break;
        }
    }
    if ($has) {
        for ($j = $start + 1; $j < count($lines); $j++) {
            if (preg_match('/^[A-Za-z0-9_\-]+:\s*/', $lines[$j])) {
                $end = $j;
                break;
            }
        }
        $before = array_slice($lines, 0, $start);
        $after = array_slice($lines, $end);
        $newYaml = implode("\n", $before);
        if ($newYaml !== '') $newYaml .= "\n";
        $newYaml .= rtrim($s, "\n");
        if (!empty($after)) $newYaml .= "\n" . implode("\n", $after);
        return "---\n" . $newYaml . "\n---\n";
    } else {
        $newYaml = rtrim($originalYaml);
        if ($newYaml !== '') $newYaml .= "\n";
        $newYaml .= rtrim($s, "\n");
        return "---\n" . $newYaml . "\n---\n";
    }
}

function detectLangFromUrl($url)
{
    return (strpos($url, '/en/') !== false || preg_match('#^https?://[^/]+/en/#', $url)) ? 'en' : 'fr';
}

function loadFeed($path)
{
    $xml = @file_get_contents($path);
    if ($xml === false) {
        throw new Exception('Cannot read feed: ' . $path);
    }
    // Try SimpleXML first
    if (function_exists('simplexml_load_string')) {
        $sx = @simplexml_load_string($xml);
        if ($sx !== false) {
            $ns = $sx->getName();
            $items = array();
            // Support Atom and RSS2
            if (isset($sx->entry)) {
                foreach ($sx->entry as $e) {
                    $title = (string)$e->title;
                    $link = '';
                    foreach ($e->link as $ln) {
                        $attrs = $ln->attributes();
                        if (isset($attrs['href'])) {
                            $link = (string)$attrs['href'];
                            break;
                        }
                    }
                    if ($link === '' && isset($e->link)) {
                        $link = (string)$e->link;
                    }
                    $link = normalizeUrl($link);
                    $items[] = array('title' => $title, 'link' => $link);
                }
            } else if (isset($sx->channel->item)) {
                foreach ($sx->channel->item as $it) {
                    $title = (string)$it->title;
                    $link = (string)$it->link;
                    $link = normalizeUrl($link);
                    $items[] = array('title' => $title, 'link' => $link);
                }
            }
            return $items;
        }
    }
    // Fallback: regex parse links and titles (best effort)
    $items = array();
    if (preg_match_all('#<entry[\s\S]*?<title>(.*?)</title>[\s\S]*?<link[^>]*href=\"(.*?)\"#i', $xml, $m, PREG_SET_ORDER)) {
        foreach ($m as $mm) {
            $items[] = array('title' => html_entity_decode($mm[1]), 'link' => $mm[2]);
        }
    } elseif (preg_match_all('#<item>[\s\S]*?<title>(.*?)</title>[\s\S]*?<link>(.*?)</link>#i', $xml, $m, PREG_SET_ORDER)) {
        foreach ($m as $mm) {
            $items[] = array('title' => html_entity_decode($mm[1]), 'link' => trim($mm[2]));
        }
    }
    return $items;
}

function buildPostsIndex($dirs)
{
    $index = array();
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($rii as $f) {
            if ($f->isDir()) continue;
            $path = $f->getPathname();
            if (!preg_match('/\.(md|markdown|mkdn|mdown|html)$/i', $path)) continue;
            $content = file_get_contents($path);
            if ($content === false) continue;
            $parsed = parseFrontMatter($content);
            $front = $parsed[0];
            $yaml = $parsed[1];
            $body = $parsed[2];
            if ($front === null) continue;
            $permalink = isset($front['permalink']) ? trim($front['permalink']) : '';
            // Detect EN by filesystem path, not URL prefix
            $isEn = (strpos($path, '/_posts/en/') !== false);
            if ($permalink === '') {
                $bn = basename($path);
                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})-(.+)\.(md|markdown|html)$/i', $bn, $m)) {
                    $permalink = ($isEn ? '/en' : '') . '/' . $m[1] . '/' . $m[2] . '/' . $m[3] . '/' . $m[4];
                }
            }
            // Normalize permalink (strip domain if any and trailing slash)
            $norm = normalizeUrl($permalink);
            // Index both normalized and with trailing slash
            $index[$norm] = array('path' => $path, 'front' => $front, 'yaml' => $yaml, 'body' => $body);
            $index[rtrim($norm, '/') . '/'] = array('path' => $path, 'front' => $front, 'yaml' => $yaml, 'body' => $body);
        }
    }
    return $index;
}

function callOpenAI($baseUrl, $apiKey, $model, $prompt)
{
    $url = $baseUrl . '/v1/chat/completions';
    $payload = json_encode(array(
            'model' => $model,
            'messages' => array(
                    array('role' => 'system', 'content' => 'You are a helpful assistant for a bilingual blog (French/English).'),
                    array('role' => 'user', 'content' => $prompt),
            ),
            'temperature' => 0,
    ));
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Authorization: Bearer ' . $apiKey),
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
    ));
    $resp = curl_exec($ch);
    if ($resp === false) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($code < 200 || $code >= 300) {
        throw new Exception('OpenAI API error: HTTP ' . $code . ' -> ' . $resp);
    }
    $data = json_decode($resp, true);
    if (!is_array($data) || empty($data['choices'][0]['message']['content'])) {
        throw new Exception('Unexpected OpenAI response');
    }
    return trim($data['choices'][0]['message']['content']);
}


$items = loadFeed($feedPath);
if (empty($items)) {
    err('No entries found in feed');
    exit(1);
}
out('Loaded ' . count($items) . ' entries from feed');

// Build quick lookup by URL path (without domain) as keys
function normalizeUrl($url)
{

    $parts = parse_url($url);
    if (!isset($parts['path'])) return '';

    return rtrim($parts['path'], '/');
}

$postsIndex = buildPostsIndex($postsDirs);

// Map feed items to local files by permalink path
$map = array();
foreach ($items as $it) {
    $path = normalizeUrl($it['link']);
    // try exact match
    if (isset($postsIndex[$path])) {
        $map[$path] = array('file' => $postsIndex[$path]['path'], 'item' => $it);
        continue;
    }
    // try without trailing slash
    $alt = rtrim($path, '/');
    if (isset($postsIndex[$alt])) {
        $map[$path] = array('file' => $postsIndex[$alt]['path'], 'item' => $it);
    }
}

// Build batched suggestions via 1–2 API calls (FR and EN)
// Partition items by language
$frItems = array();
$enItems = array();
foreach ($items as $it) {
    if (detectLangFromUrl($it['link']) === 'en') {
        $enItems[] = $it;
    } else {
        $frItems[] = $it;
    }
}

function buildBatchedPrompt($lang, $items)
{
    $isFr = ($lang === 'fr');
    $header = $isFr
            ? "Tu es un assistant éditorial. Pour CHAQUE article ci-dessous, propose 3 à 6 autres billets PERTINENTS du même blog, en te basant UNIQUEMENT sur la liste de candidats. Réponds STRICTEMENT en JSON valide, format: {\"<url>\": [{\"title\":\"...\",\"link\":\"...\"}, ...], ...}. Utilise EXACTEMENT les titres et liens fournis (pas d'invention)."
            : "You are an editorial assistant. For EACH article below, propose 3 to 6 OTHER relevant posts from the same blog, using ONLY the candidates list. Reply STRICTLY in valid JSON, format: {\"<url>\": [{\"title\":\"...\",\"link\":\"...\"}, ...], ...}. Use EXACTLY the provided titles and links (no fabrication).";

    // Build list of candidates (all items of that language)
    $lines = array();
    foreach ($items as $it) {
        $lines[] = '- ' . ($it['title']) . ' | ' . ($it['link']);
    }
    $candidates = implode("\n", $lines);

    // List the query URLs (keys we expect)
    $urls = array();
    foreach ($items as $it) {
        $urls[] = $it['link'];
    }
    $expectKeys = ($isFr ? "Articles cibles (clés de l'objet JSON):\n" : "Target articles (keys in the JSON object):\n") . implode("\n", $urls);

    $prompt = $header . "\n\n" . ($isFr ? "Candidats (titre | URL):\n" : "Candidates (title | URL):\n") . $candidates . "\n\n" . $expectKeys . "\n\n" . ($isFr ? "Rappels: pas d'auto-référence; ne propose pas l'article lui-même; 3 à 6 items; JSON uniquement." : "Notes: no self-reference; do not suggest the same article; 3 to 6 items; JSON only.");
    return $prompt;
}

function parseJsonMap($text)
{
    // Extract outermost JSON object
    if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
        $json = $m[0];
    } else {
        $json = $text;
    }
    $data = json_decode($json, true);
    if (!is_array($data)) return array();
    $out = array();
    foreach ($data as $k => $arr) {
        if (!is_array($arr)) continue;
        $clean = array();
        foreach ($arr as $row) {
            if (!is_array($row)) continue;
            $t = isset($row['title']) ? trim($row['title']) : '';
            $l = isset($row['link']) ? trim($row['link']) : '';
            // Keep title as-is; only ensure link is present
            if ($t !== '' && $l !== '') {
                $clean[] = array('title' => $t, 'link' => $l);
            }
        }
        if (!empty($clean)) {
            $out[$k] = $clean;
        }
    }
    return $out;
}

// Call the API once per language
$batched = array();
if (!empty($frItems)) {
    out('→ Calling OpenAI once for FR (' . count($frItems) . ' posts)...');
    try {
        $ansFr = callOpenAI($baseUrl, $apiKey, $model, buildBatchedPrompt('fr', $frItems));
        $mapFr = parseJsonMap($ansFr);
        $batched = $batched + $mapFr;
    } catch (Exception $e) {
        err('FR batch failed: ' . $e->getMessage());
    }
}
if (!empty($enItems)) {
    out('→ Calling OpenAI once for EN (' . count($enItems) . ' posts)...');
    try {
        $ansEn = callOpenAI($baseUrl, $apiKey, $model, buildBatchedPrompt('en', $enItems));
        $mapEn = parseJsonMap($ansEn);
        $batched = $batched + $mapEn;
    } catch (Exception $e) {
        err('EN batch failed: ' . $e->getMessage());
    }
}

// Build a normalized key map so we can match by full URL or by path (domain-stripped)
$batched_norm = array();
foreach ($batched as $k => $v) {
    // keep original
    $batched_norm[$k] = $v;
    // trimmed slash variant
    $batched_norm[rtrim($k, '/')] = $v;
    // domain-stripped path
    $batched_norm[normalizeUrl($k)] = $v;
}

// Helper to lookup suggestions for a given URL (tries exact, trimmed, and normalized path)
function lookupSugg($batchedMap, $url)
{
    if (isset($batchedMap[$url])) return $batchedMap[$url];
    $alt = rtrim($url, '/');
    if (isset($batchedMap[$alt])) return $batchedMap[$alt];
    $path = normalizeUrl($url);
    if (isset($batchedMap[$path])) return $batchedMap[$path];
    return array();
}

$updated = 0;
$total = 0;
foreach ($map as $path => $pair) {
    $file = $pair['file'];
    $item = $pair['item'];
    $total++;
    $content = file_get_contents($file);
    if ($content === false) {
        err('Cannot read ' . $file);
        continue;
    }
    list($front, $yaml, $body) = parseFrontMatter($content);
    if ($front === null) {
        continue;
    }

//    // skip if suggestions already present
//    if (preg_match('/^suggestions:\s*$/m', $yaml) && preg_match('/^suggestions:\s*\n\s*-\s+title:/m', $yaml)) {
//        out('✓ Suggestions already present: '.$file);
//        continue;
//    }

    $sug = lookupSugg($batched_norm, $item['link']);
    if (empty($sug)) {
        err('No batched suggestions for ' . $item['link'] . '; skipping');
        var_dump($batched_norm);
        var_dump($item);
        exit;
        continue;
    }
    // Cap to 6
    if (count($sug) > 6) $sug = array_slice($sug, 0, 6);

    $newFront = serializeFrontMatterWithSuggestions($yaml, $sug);
    $newContent = $newFront . $body;
    if (file_put_contents($file, $newContent) === false) {
        err('Failed to write ' . $file);
        continue;
    }
    $updated++;
    out('✔ Updated: ' . $file);
}

out("Done. Updated $updated / $total mapped posts with batched suggestions.");
