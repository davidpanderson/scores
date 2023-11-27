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
// must handle:
//  titles w/ parens:
//      Turandot (suite), Op. 41, BV 248 (Busoni, Ferruccio)
//  duplicate titles
//      Ti Rapirei! (Tosti, Francesco Paolo) (2)
//
function parse_title($str) {
    $str = str_replace('_', ' ', $str);
    $lparen = strrpos($str, '(');
    if ($lparen === false) {
        return [trim($str), '',''];
    }
    $rparen = strrpos($str, ')');
    if ($rparen === false || $rparen < $lparen) {
        echo "malformed title $str\n";
        return [$str, '',''];
    }
    $t = trim(substr2($str, 0, $lparen));

    $c = substr2($str, $lparen+1, $rparen);
    if (is_numeric($c)) {
        $str2 = substr2($str, 0, $lparen);
        [$t, $first, $last] = parse_title($str2);
        return ["$t ($c)", $first, $last];
    }
    $comma = strpos($c, ',');
    if ($comma === false) {
        $last = trim($c);
        $first = '';
    } else {
        $last = trim(substr2($c, 0, $comma));
        $first = trim(substr($c, $comma+1));
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

// compute the similarity (defined in a somewhat arbitrary way)
// between two instrument combos
// $icx is struct with lists icx->count and icx->id
//
function inst_combo_similarity($ic1, $ic2) {
    $n = 0;
    foreach ($ic1->id as $i=>$id) {
        $p2 = array_search($id, $ic2->id);
        if ($p2 === false) {
            $n -= 1;
        } else {
            $n += min($ic1->count[$i], $ic2->count[$p2]);
        }
    }
    foreach ($ic2->id as $i=>$id) {
        if (!in_array($id, $ic1->id)) {
            $n -= 1;
        }
    }
    return $n;
}

if (0) {
    $ic1 = new StdClass;
    $ic2 = new StdClass;
    $ic1->id = [146, 141];
    $ic1->count = [1, 1];
    $ic2->id = [115];
    $ic2->count = [2];
    echo inst_combo_similarity($ic1, $ic2);
}

?>
