<?php

// parse mediawiki data, show the resulting PHP data structure
// (and error/unrecognized messages)

require_once("parse.inc");

function main($nlines) {
    $f = fopen('david_page_dump.txt', 'r');
    for ($i=0; $i<$nlines; $i++) {
        $x = fgets($f);
        if (!$x) break;
        $y = json_decode($x);
        foreach ($y as $title => $body) {
            $comp = parse_composition($title, $body);
            print_r($comp);
        }
    }
}

main(1);
?>
