<?php

// read the JSON/mediawiki file and output in readable form

function main($nlines) {
    $f = fopen('david_page_dump.txt', 'r');
    for ($i=0; $i<$nlines; $i++) {
        echo "JSON record $i\n";
        $x = fgets($f);
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

main(100);

?>
