<?php

// parse mediawiki data, show the resulting PHP data structure
// (and error/unrecognized messages)

require_once("parse.inc");

function main($nlines) {
    $f = fopen('david_page_dump.txt', 'r');
    $ncomps = [];
    for ($i=0; $i<$nlines; $i++) {
        $x = fgets($f);
        if (!$x) break;
        $y = json_decode($x);
        $n = 0;
        foreach ($y as $title => $body) {
            //if ($title != 'Piano_Sonata_No.14,_Op.27_No.2_(Beethoven,_Ludwig_van)') continue;
            $comp = parse_composition($title, $body);
            print_r($comp);
            $n++;
        }
        $ncomps[$i] = $n;
        echo "comps: $n\n";
    }
    echo "totals:\n";
    print_r($ncomps);
}

$verbose = true;
main(10);
?>
