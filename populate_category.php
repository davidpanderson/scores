<?php

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
            //echo "$t\n";
            if (strpos($t, 'Category:') !== 0) continue;
            $name = substr($t, 9);
            $p = parse_person($z);
        }
    }
}

main('david_category_template_dump.txt', 10000);

?>
