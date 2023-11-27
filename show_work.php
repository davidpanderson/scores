<?php

// parse JSON/mediawiki data for works.
// Show the resulting PHP data structure, and error/unrecognized messages

require_once("parse_work.inc");

function main($nlines) {
    $f = fopen('data/david_page_dump.txt', 'r');
    $nworks = [];
    for ($i=0; $i<$nlines; $i++) {
        echo "JSON line $i\n";
        $x = fgets($f);
        if (!$x) break;
        if (!trim($x)) continue;
        $y = json_decode($x);
        $n = 0;
        foreach ($y as $title => $body) {
            //if ($title != 'Piano_Concerto_No.21_in_C_major,_K.467_(Mozart,_Wolfgang_Amadeus)') continue;
            $work = parse_work($title, $body);
            if (!$work) {
                echo "failed to parse $title\n";
            } else {
                echo "parsed $title\n";
                //print_r($work);
            }
            $n++;
        }
        $nworks[$i] = $n;
        echo "#works: $n\n";
    }
    echo "totals:\n";
    print_r($nworks);
}

$verbose = false;
// 3079 lines
main(4000);
?>
