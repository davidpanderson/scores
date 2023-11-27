<?php

// parse JSON/mediawiki data for categories/templates.
// Show the resulting PHP data structure, and error/unrecognized messages

require_once("parse_category.inc");

function main($nlines) {
    $f = fopen('data/david_category_template_dump.txt', 'r');
    $npeople = [];
    for ($i=0; $i<$nlines; $i++) {
        echo "JSON line $i\n";
        $x = fgets($f);
        if (!$x) break;
        if (!trim($x)) continue;
        $y = json_decode($x);
        $n = 0;
        foreach ($y as $title => $body) {
            if (!strstr($title, 'Category:')) continue;
            $p = parse_person($title, $body);
            if (!$p) continue;
            echo "main(): got ".$p->template_name."\n";
            //print_r($p);
            $n++;
        }
        $npeople[$i] = $n;
        echo "people: $n\n";
    }
    echo "totals:\n";
    print_r($npeople);
}

$verbose = false;
// 480 lines
main(500);
?>
