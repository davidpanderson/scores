<?php

// read the JSON/mediawiki file and output in readable form

function main($nlines) {
    $f = fopen('david_page_dump.txt', 'r');
    for ($i=0; $i<$nlines; $i++) {
        $x = fgets($f);
        $y = json_decode($x);
        foreach ($y as $t => $z) {
            echo "======= $t =========\n$z\n";
        }
    }
}

main(10);

?>
