<?php

// parse JSON/mediawiki data for categories/templates.
// Show the resulting PHP data structure, and error/unrecognized messages

require_once("parse_category.inc");

function main($nlines) {
    $f = fopen('david_category_template_dump.txt', 'r');
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
            $person = parse_person($title, $body);
            print_r($person);
            $n++;
        }
        $npeople[$i] = $n;
        echo "people: $n\n";
    }
    echo "totals:\n";
    print_r($npeople);
}

$verbose = true;
main(100000);
?>
