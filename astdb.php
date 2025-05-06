#!/usr/bin/php -q
<?php

$dir = "/var/www/html/supermon2/";
$db = $dir . "astdb.txt";
$privatefile = $dir . "privatenodes.txt";

$retries = 0;
$Pcontents = '';
$private = getenv('PRIVATE_NODE');
$contents = '';
$contents2 = '';

$url = "http://allmondb.allstarlink.org/";

// Load private nodes first
if (file_exists($privatefile)) {
    $Pcontents .= file_get_contents($privatefile);
}

// Skip public DB fetch if only private node is configured
if (is_null($private) || !$private) {

    // Optional delay if run by cron
    if (isset($argv[1]) && $argv[1] === 'cron') {
        $seconds = mt_rand(0, 1800);
        echo "Waiting for $seconds seconds...\n";

        while ($seconds > 0) {
            $sleep = ($seconds > 60) ? 60 : $seconds;
            echo "Sleeping for $sleep seconds...\n";
            sleep($sleep);
            $seconds -= $sleep;
        }
    }

    // Retry fetching AllStar DB up to 5 times if too small
    while (true) {
        $contents2 = @file_get_contents($url);
        $size = strlen($contents2);

        if ($size < 300000) {
            if ($retries >= 5) {
                die("astdb.txt: Retries exceeded!! $size bytes - Invalid: file too small, bailing out.\n");
            }
            $retries++;
            echo "Retry $retries of 5. Will retry $url\n";
            sleep(5);
        } else {
            break;
        }
    }
}

$contents = $Pcontents . $contents2;

// Remove non-printing characters
$contents = preg_replace('/[\x00-\x09\x0B-\x0C\x0E-\x1F\x7F-\xFF]/', '', $contents);

// Write to output file with locking
if (!($fh = fopen($db, 'w'))) {
    die("Cannot open $db.\n");
}

if (!flock($fh, LOCK_EX)) {
    echo "Unable to obtain lock.\n";
    exit(-1);
}

if (fwrite($fh, $contents) === false) {
    die("Cannot write to $db.\n");
}

fclose($fh);

$size = strlen($contents);
echo "Success: astdb.txt $size bytes\n";
?>
