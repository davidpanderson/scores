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

if (0) {
    print_r(parse_name('John Paul Jones'));
    print_r(parse_name('Jones, John Paul'));
    print_r(parse_name('Charo'));
}

//print_r(parse_title("Piano_Sonata_No.13,_Op.27_No.1_(suite)_(Beethoven,_Ludwig_van)"));

function valid_instrument($str) {
    if (is_numeric(substr2($str, 0, 1))) return false;
    return true;
}

// if str is of form "Joe Smith, piano" return [first, last, instr]
//
function parse_perf_comma($str) {
    if (strpos($str, '[[') !== false) return null;
    if (strpos($str, '(') !== false) return null;
    if (strpos($str, ';') !== false) return null;
    $n = strrpos($str, ',');
    if (!$n) return null;
    $left = trim(substr2($str, 0, $n));
    if (!$left) return null;
    if (count(explode(' ', $left))>3) return null;
    [$first, $last] = parse_name($left);
    $right = trim(substr2($str, $n+1, strlen($str)));
    if (!$right) return null;
    if (!valid_instrument($right)) return null;
    if (count(explode(' ', $first))>2) return null;
    return [$first, $last, $right];
}

// if str is of form "Joe Smith (piano)" return [first, last, instr]
//
function parse_perf_paren($str) {
    if (strpos($str, '[[') !== false) return null;
    $n = strpos($str, '(');
    if (!$n) return null;
    $m = strpos($str, ')');
    if (!$m) return null;
    if ($n>$m) return null;
    if (trim(substr2($str, $m+1, strlen($str)))) return null;  // nothing after )
    $left = trim(substr2($str, 0, $n));
    if (!$left) return null;
    [$first, $last] = parse_name($left);
    if (count(explode(' ', $first))>2) return null;
    if (count(explode(' ', $last))>1) return null;
    $inst = trim(substr2($str, $n+1, $m));
    if (!$inst) return null;
    if (!valid_instrument($inst)) return null;
    return [$first, $last, $inst];
}

function parse_perf($str) {
    $x = parse_perf_comma($str);
    if ($x) return $x;
    return parse_perf_paren($str);
}

if (0) {
    print_r(parse_perf('Joe Smith, piano'));
    print_r(parse_perf('Joe Smith (piano)'));
    print_r(parse_perf('Joe Smith piano'));
}

$ensemble_names = ['orchestra', 'piano trio'];

// parse the performer_categories and performer args of audio file
//
// performer_categories has
//     foo=orchestra;smith, joe=conductor
//     joe smith=piano, conductor
// performer (less frequently) has
//     Joe Smith, piano
//     Joe Smith (piano)
//     Joe Smith (piano)*
//     Joe smith, piano ; john doe, violin
//     joe smith (piano), john doe (violin)
//     orchestra name (if performer_categories has =conductor)
//
// given the above fields, return
// - an ensemble [name, type]
// - a list of performers [first, last, instrument]
//
function parse_performers($perf, $perf_cat) {
    global $ensemble_names;

    $ensemble = null;
    $performers = [];
    $got_conductor = false;

    $perf = str_replace('<br>', ';', $perf);
    $perf = strip_tags($perf);
    $perf_cat = strip_tags($perf_cat);

    // start with performer_categories
    //
    $fields = explode(';', $perf_cat);
    foreach ($fields as $field) {
        $parts = explode('=', $field);
        if (count($parts) != 2) continue;
        $inst = trim(strtolower($parts[1]));
        if (in_array($inst, $ensemble_names)) {
            $ensemble = [trim($parts[0]), $inst];
        } else {
            if ($inst == 'conductor') $got_conductor = true;
            [$first, $last] = parse_name($parts[0]);
            $performers[] = [$first, $last, $inst];
        }
    }
    // now performer
    //
    if ($perf) {
        if ($got_conductor && !$ensemble) {
            $ensemble = [trim($perf), 'orchestra'];
        } else {
            $p = parse_perf($perf);
            if ($p) {
                if ($p[2] == 'orchestra') {
                    $ensemble = [$p[0]." ".$p[1], $p[2]];
                } else {
                    $performers[] = $p;
                }
            } else {
                $parts = explode(';', $perf);
                if (count($parts) > 1) {
                    foreach ($parts as $part) {
                        $p = parse_perf($part);
                        if ($p) {
                            if (in_array($p[2], $ensemble_names)) {
                                $ensemble = [$p[0]." ".$p[1], $p[2]];
                            } else {
                                $performers[] = $p;
                            }
                        }
                    }
                } else {
                    $parts = explode(',', $perf);
                    if (count($parts) > 1) {
                        foreach ($parts as $part) {
                            $p = parse_perf_paren($part);
                            if ($p) {
                                if (in_array($p[2], $ensemble_names)) {
                                    $ensemble = [$p[0]." ".$p[1], $p[2]];
                                } else {
                                    $performers[] = $p;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    // skip bad ensemble names
    if ($ensemble) {
        $x = $ensemble[0];
        if (strstr($x, '{{') || strstr($x, '[[')) {
            $ensemble = null;
        }
    }
    return [$ensemble, $performers];
}

if (0) {
    print_r(parse_performers('Orchestre Symphonique Munich (orchestra)',''));
}
?>
