<?php

// read a JSON/mediawiki file and output in semi-readable form

function main($file, $nlines) {
    $f = fopen($file, 'r');
    for ($i=0; $i<$nlines; $i++) {
        echo "JSON record $i\n";
        $x = fgets($f);
        if (!$x) {
            echo "end of file\n";
            break;
        }
        if (!trim($x)) continue;
        $y = json_decode($x);
        if (!$y) {
            echo "bad JSON: $x\n";
            continue;
        }
        foreach ($y as $t => $z) {
            echo "======= $t =========\n$z\n";
        }
    }
}

main('david_page_dump.txt', 200);

?>
