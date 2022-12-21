<?php

// utility functions used by other DB processing and web

// given "Piano_Sonata_No.13,_Op.27_No.1_(Beethoven,_Ludwig_van)" return
// - title/opus (with spaces)
// - first name
// - last name
//
function parse_title($str) {
    $str = str_replace('_', ' ', $str);
    $x = strrpos($str, '(');
    if ($x === false) {
        return [trim($str), '',''];
    }
    $y = strrpos($str, ')');
    if ($y === false || $y < $x) {
        echo "malformed title $str\n";
        return [$str, '',''];
    }
    $t = trim(substr2($str, 0, $x));

    $c = substr2($str, $x+1, $y);
    $x = strpos($c, ',');
    if ($x === false) {
        $last = trim($c);
        $first = '';
    } else {
        $last = trim(substr2($c, 0, $x));
        $first = trim(substr($c, $x+1));
    }
    return [$t, $first, $last];
}

//print_r(parse_title("Piano_Sonata_No.13,_Op.27_No.1_(suite)_(Beethoven,_Ludwig_van)"));

// case-insensitive
//
function starts_with($text, $x) {
    return stripos($text, $x) === 0;
}

function substr2($str, $start, $end) {
    return substr($str, $start, $end-$start);
}

?>
