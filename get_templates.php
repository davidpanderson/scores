#! /usr/bin/env php

<?php

require_once("imslp_util.inc");

// read JSON/mediawiki file with templates,
// write serialized array of templates (name=>def)

function strip_noinclude($str) {
    $n = strpos($str, '<noinclude>');
    if ($n === false) return $str;
    $m = strpos($str, '</noinclude>');
    if ($m === false) {
        echo "no </noinclude> found: $str\n";
        return substr2($str, 0, $n);
    }
    if ($m < $n) {
        echo "end < start\n";
        exit;
    }
    $x = substr2($str, 0, $n) . substr2($str, $m+12, strlen($str));
    return $x;
}

function strip_includeonly($str) {
    $x = str_replace('<includeonly>', '', $str);
    $x = str_replace('</includeonly>', '', $x);
    return $x;
}

function main($file, $nlines) {
    $f = fopen($file, 'r');
    $templates = [];
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
            if (strpos($t, 'Template:') !== 0) continue;
            $name = strtolower(substr($t, 9));
            $z = strip_noinclude($z);
            $z = strip_includeonly($z);
            $templates[$name] = $z;
        }
    }
    $f = fopen('data/templates.ser', 'w');
    fwrite($f, serialize($templates));
    fclose($f);
}

main('data/david_category_template_dump.txt', 10000);

?>
