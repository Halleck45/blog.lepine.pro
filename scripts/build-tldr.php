#!/usr/bin/env php
<?php
/**
 * Build TL;DR summaries for Jekyll posts.
 *
 * - Scans _posts and _posts/en
 * - Detects language from path ("/en/" => English, otherwise French) or from front matter 'lang'
 * - Checks if front matter contains 'tldr'. If missing or empty, calls OpenAI to generate one
 * - Writes TLDR into the post's front matter as a folded block scalar
 *
 * Env vars:
 *   OPENAI_API_KEY   required
 *   OPENAI_BASE_URL  optional (defaults to https://api.openai.com)
 *   OPENAI_MODEL     optional (defaults to gpt-4o-mini)
 */

// Basic CLI output helpers (compatible with PHP 5.3+)
function out($msg) { fwrite(STDOUT, $msg."\n"); }
function err($msg) { fwrite(STDERR, "[ERR] ".$msg."\n"); }

$root = dirname(__DIR__);
$postsDirs = array(
    $root.'/_posts',
    $root.'/_posts/en',
);

$apiKey = getenv('OPENAI_API_KEY');
if (!$apiKey) {
    err('OPENAI_API_KEY not set in environment. Aborting.');
    exit(1);
}
$baseUrl = rtrim(getenv('OPENAI_BASE_URL') ? getenv('OPENAI_BASE_URL') : 'https://api.openai.com', '/');
$model   = getenv('OPENAI_MODEL') ? getenv('OPENAI_MODEL') : 'gpt-4.1-mini';

// Helper polyfills for older PHP
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        if ($needle === '') return true;
        $len = strlen($needle);
        return substr($haystack, -$len) === $needle;
    }
}

// Very small YAML front matter parser tailored for Jekyll posts.
function parseFrontMatter($content) {
    if (!preg_match('/^---\n(.*?)\n---\n/s', $content, $m)) {
        return array(null, null, null);
    }
    $yaml = $m[1];
    $body = substr($content, strlen($m[0]));

    // Minimal YAML parsing: key: value and simple block scalars.
    $lines = preg_split("/\r?\n/", $yaml);
    $data = array();
    $currentKey = null;
    $indentLevel = 0;
    $buffer = array();
    foreach ($lines as $line) {
        if (preg_match('/^([A-Za-z0-9_\-]+):\s*(.*)$/', $line, $mm)) {
            // flush previous block scalar if any
            if ($currentKey !== null && $buffer) {
                $data[$currentKey] = rtrim(implode("\n", $buffer));
                $buffer = array();
            }
            $k = $mm[1];
            $v = $mm[2];
            if ($v === '|' || $v === '>' || $v === '') {
                $currentKey = $k;
                $buffer = array();
                $indentLevel = null; // detect on first indented line
                $data[$k] = '';
            } else {
                $currentKey = null;
                // Remove surrounding quotes if present
                if ((strlen($v) >= 2) && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                    $v = substr($v, 1, -1);
                }
                $data[$k] = $v;
            }
        } else {
            // part of a block scalar or nested content
            if ($currentKey !== null) {
                if ($indentLevel === null) {
                    $indentLevel = strlen($line) - strlen(ltrim($line, ' '));
                }
                $buffer[] = $indentLevel > 0 ? substr($line, $indentLevel) : ltrim($line);
            }
        }
    }
    if ($currentKey !== null && $buffer) {
        $data[$currentKey] = rtrim(implode("\n", $buffer));
    }
    return array($data, $yaml, $body);
}

function serializeFrontMatter($data, $originalYaml, $tldr) {
    // If original YAML had structure, we try to minimally append or replace 'tldr'
    $lines = preg_split("/\r?\n/", $originalYaml);
    $hasTldr = false;
    $tldrStart = -1;
    foreach ($lines as $i => $line) {
        if (preg_match('/^tldr:\s*(\||\>)?/', $line)) {
            $hasTldr = true;
            $tldrStart = $i;
            break;
        }
    }

    if ($tldr !== null) {
        $tldrBlock = "tldr: |\n";
        foreach (explode("\n", trim($tldr)) as $l) {
            $tldrBlock .= '  '.rtrim($l)."\n";
        }
        $tldrBlock = rtrim($tldrBlock, "\n");

        if ($hasTldr) {
            // Replace existing tldr block (simple approach: from tldr line until next top-level key or EOF)
            $end = count($lines);
            for ($j = $tldrStart + 1; $j < count($lines); $j++) {
                if (preg_match('/^[A-Za-z0-9_\-]+:\s*/', $lines[$j])) {
                    $end = $j;
                    break;
                }
            }
            $before = array_slice($lines, 0, $tldrStart);
            $after  = array_slice($lines, $end);
            $newYaml = implode("\n", $before);
            if ($newYaml !== '') { $newYaml .= "\n"; }
            $newYaml .= $tldrBlock;
            if (!empty($after)) { $newYaml .= "\n".implode("\n", $after); }
        } else {
            // Append at end with a blank line separator
            $newYaml = rtrim($originalYaml);
            if ($newYaml !== '') { $newYaml .= "\n"; }
            $newYaml .= $tldrBlock;
        }
        return "---\n".$newYaml."\n---\n";
    }

    // No TLDR change
    return "---\n".$originalYaml."\n---\n";
}

function detectLanguage($path, $front) {
    if (!empty($front['lang'])) {
        $l = strtolower((string)$front['lang']);
        if (in_array($l, array('fr','en','en-us','en-gb'), true)) {
            return str_starts_with($l, 'en') ? 'en' : 'fr';
        }
    }
    return (strpos($path, '/_posts/en/') !== false) ? 'en' : 'fr';
}

function buildPrompt($title, $content, $lang) {
    $instructionMap = array(
        'fr' => "Écris un TL;DR court pour ce billet de blog. Exigences: quelques lignes ou puces, donne envie de lire, même langue que l'article, clair, concret, simple et engageant. Pas de bullshit, pas de jargon inutile.",
        'en' => "Write a concise TL;DR for this blog post. Requirements: a few lines or bullet points, make readers want to read, same language as the article, clear, concrete, simple and engaging. No fluff, no buzzwords."
    );
    $instruction = isset($instructionMap[$lang]) ? $instructionMap[$lang] : 'Write a concise TL;DR.';

    $sample = $lang === 'fr'
        ? "Format suggéré:\n- Point clé 1\n- Point clé 2\n- Ce que le lecteur gagnera\n\nGarde ça bref (3-6 lignes/puces)."
        : "Suggested format:\n- Key point 1\n- Key point 2\n- What the reader will gain\n\nKeep it brief (3-6 lines/bullets).";

    // Clip content to avoid sending huge files
    $maxChars = 8000;
    $snippet = function_exists('mb_substr') ? mb_substr($content, 0, $maxChars) : substr($content, 0, $maxChars);

    return $instruction."\n\nTitre: ".$title."\n\nContenu (extrait):\n".$snippet."\n\n".$sample;
}

function callOpenAI($baseUrl, $apiKey, $model, $prompt) {
    $url = $baseUrl.'/v1/chat/completions';
    $payloadArr = array(
        'model' => $model,
        'messages' => array(
            array('role' => 'system', 'content' => 'You are a helpful writing assistant that produces concise, concrete TL;DR summaries. Keep the language of the user prompt.'),
            array('role' => 'user', 'content' => $prompt),
        ),
        'temperature' => 0.4,
        'max_tokens' => 220,
    );
    $payload = json_encode($payloadArr);

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$apiKey,
        ),
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ));
    $resp = curl_exec($ch);
    if ($resp === false) {
        throw new Exception('cURL error: '.curl_error($ch));
    }
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($code < 200 || $code >= 300) {
        throw new Exception('OpenAI API error: HTTP '.$code.' -> '.$resp);
    }

    $data = json_decode($resp, true);
    if (!is_array($data) || empty($data['choices'][0]['message']['content'])) {
        throw new Exception('Unexpected OpenAI API response');
    }
    return trim((string)$data['choices'][0]['message']['content']);
}

function extractTitleAndBody($front, $body) {
    $title = isset($front['title']) ? $front['title'] : '';
    // Remove Liquid tags and code blocks to help the model
    $clean = preg_replace('/\{\%.*?\%\}|\{\{.*?\}\}/s', '', $body);
    $clean = preg_replace('/```[\s\S]*?```|\{\% highlight [\s\S]*?\{% endhighlight %\}/', '', (string)$clean);
    $clean = trim((string)$clean);
    return array($title, $clean);
}

$updated = 0;
$total = 0;

foreach ($postsDirs as $dir) {
    if (!is_dir($dir)) { continue; }
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        $path = $file->getPathname();
        if (!preg_match('/\.(md|markdown|mkdn|mdown|html)$/i', $path)) continue;

        $total++;
        $content = file_get_contents($path);
        if ($content === false) { err('Cannot read '.$path); continue; }

        $parsed = parseFrontMatter($content);
        $front = $parsed[0];
        $yaml  = $parsed[1];
        $body  = $parsed[2];
        if ($front === null) { continue; }

        $existing = isset($front['tldr']) ? trim((string)$front['tldr']) : '';
        if ($existing !== '') {
            out('✓ TLDR already present: '.$path);
            continue;
        }

        $lang = detectLanguage($path, $front);
        $tb = extractTitleAndBody($front, $body);
        $title = $tb[0];
        $cleanBody = $tb[1];
        $prompt = buildPrompt((string)$title, $cleanBody, $lang);
        out('→ Generating TLDR for '.$path.' (lang='.$lang.') ...');

        try {
            $tldr = callOpenAI($baseUrl, $apiKey, $model, $prompt);
            sleep(5); // 429
        } catch (Exception $e) {
            err('Failed to generate TLDR for '.$path.': '.$e->getMessage());
            continue;
        }

        $newFront = serializeFrontMatter($front, $yaml, $tldr);
        $newContent = $newFront.$body;

        if (file_put_contents($path, $newContent) === false) {
            err('Failed to write TLDR to '.$path);
            continue;
        }
        $updated++;
        out('✔ Updated: '.$path);
    }
}

out("Done. Updated $updated / $total files.");
