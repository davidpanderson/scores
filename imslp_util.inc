<?php

// utility functions used by both DB processing and web

// case-insensitive
//
function starts_with($text, $x) {
    return stripos($text, $x) === 0;
}

function substr2($str, $start, $end) {
    return substr($str, $start, $end-$start);
}

// see https://stackoverflow.com/questions/3635511/remove-diacritics-from-a-string
//
function remove_diacritics($x) {
    return preg_replace('/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);/i','$1',htmlentities($x));
}

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

// $str is of the form "first last" or "last, first".
// Return [first, last]
//
function parse_name($str) {
    $str = trim($str);
    $n = strpos($str, ',');
    if ($n) {
        $last = substr2($str, 0, $n);
        $first = substr2($str, $n+1, strlen($str));
    } else {
        $n = strrpos($str, ' ');
        if ($n) {
            $last = substr2($str, $n+1, strlen($str));
            $first = substr2($str, 0, $n);
        } else {
            $last = $str;
            $first = '';
        }
    }
    return [trim($first), trim($last)];
}

?>
