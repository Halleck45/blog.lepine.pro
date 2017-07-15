<?php
$files = glob(__DIR__.'/../_attachments/*.html');
//$files = glob(__DIR__.'/../_attachments/*.html');
$posts = glob(__DIR__.'/_posts/*.html');
foreach($files as $filename) {

    $content = file_get_contents($filename);
    preg_match('!_wp_attached_file: (.*)\s!im', $content, $matches);
    list(, $imageuri) = $matches;

    $destination = $imageuri;
    $olduri = 'http://blog.lepine.pro/wp-content/uploads/' . $imageuri;
    $newuri = '{{site.baseurl}}/'.ltrim($destination, '/');

    $destination = __DIR__.'/files/'.str_replace('/', '-', $destination);

    echo PHP_EOL.sprintf('GET %s ', $olduri);
    if(!file_exists($destination)) {
        shell_exec(sprintf('wget %s -O %s', $olduri, $destination));
    }
    echo 'OK';




    // replacing in posts
    echo PHP_EOL. 'REPLACING:';
    foreach($posts as $postfile) {
        echo '.';
        $postcontent = file_get_contents($postfile);
        $count = substr_count($postcontent, $olduri);
        if($count < 1) {
            continue;
        }
        echo $count;
//        echo PHP_EOL.sprintf('- %d occurrences', $count);
        $postcontent = str_replace($olduri, $newuri, $postcontent);
        file_put_contents($postfile, $postcontent);

        // il faut gérer les cas où l'image est redimmensionnée en GET: php-5-3-performance-heritage1-150x150.gif

//        $info = new SplFileInfo($olduri);
//        $extension = $info->getExtension();

//        die('lkjdsf');
//        file_put_contents($postfile, $postcontent);

    }
}