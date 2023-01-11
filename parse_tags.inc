<?php

// parse a string of the form
// sonatas ; 2vn 2va viol vlne bc ; 2vn 2va viol vlne org
// requiems ; masses ; sop mez ten bass ch orch ; la ; grc
// and return
// 1) a list of work type codes
// 2) a list of inst combos
//      (An inst combo is a list of [count, inst code])
// 3) a list of arrangement inst combos
// 4) a list of language codes

$wts_by_code = null;
$langs_by_code = null;
function work_type_by_code($code) {
    global $wts_by_code;
    if (!$wts_by_code) {
        $wts_by_code = unserialize(file_get_contents('work_type_by_code.ser'));
    }
    if (array_key_exists($code, $wts_by_code)) {
        return $wts_by_code[$code];
    }
    return null;
}

function inst_by_code($code) {
    static $insts_by_code = null;
    if (!$insts_by_code) {
        $insts_by_code = unserialize(file_get_contents('inst_by_code.ser'));
    }
    if (array_key_exists($code, $insts_by_code)) {
        return $insts_by_code[$code];
    }
    return null;
}

function lang_by_code($code) {
    global $langs_by_code;
    if (!$langs_by_code) {
        $langs_by_code = unserialize(file_get_contents('lang_by_code.ser'));
    }
    if (array_key_exists($code, $langs_by_code)) {
        return $langs_by_code[$code];
    }
    return null;
}

function parse_tags($tags) {
    $work_types = [];
    $inst_combos = [];
    $arr_inst_combos = [];
    $languages = [];
    $parts = explode(';', $tags);
    foreach ($parts as $part) {
        $part = trim($part);
        if (work_type_by_code($part)) {
            $work_types[] = $part;
            continue;
        }
        if (lang_by_code($part)) {
            $languages[] = $part;
            continue;
        }
        // must be an instrument combo
        $combo = [];
        $arr = false;
        $insts = explode(' ', $part);
        foreach ($insts as $inst) {
            [$count, $code] = parse_count_str($inst);
            if ($inst == '(arr)') {
                $arr = true;
                continue;
            }
            if ($inst == 'open') continue;
            if (inst_by_code($code)) {
                $combo[] = [$count, $code];
            } else {
                echo "unknown inst code $code in $tags\n";
            }
        }
        if ($combo) {
            if ($arr) {
                $arr_inst_combos[] = $combo;
            } else {
                $inst_combos[] = $combo;
            }
        }
    }
    return [$work_types, $inst_combos, $arr_inst_combos, $languages];
}

function combo_str($combo) {
    $x = [];
    foreach ($combo as $inst) {
        $count = $inst[0];
        $inst = inst_by_code($inst[1]);
        $name = $inst->name;
        if ($count == 1) {
            $x[] = "$name";
        } else {
            $plu = substr($name, -1, 1) == 's'?'es':'s';
            $x[] = "$count $name$plu";
        }
    }
    return implode(' + ', $x);
}

// $combo is a list of [count, code]
//
function combo_hash($combo) {
    return md5(json_encode($combo));
}

function test() {
    $lines = file('tags.out');
    $unique_inst_combos = [];
    foreach ($lines as $line) {
        [$work_types, $inst_combos, $arr_inst_combos, $langs] = parse_tags($line);
        foreach ($inst_combos as $combo) {
            $h = combo_hash($combo);
            if (!array_key_exists($h, $unique_inst_combos)) {
                $unique_inst_combos[$h] = $combo;
            }
        }
    }
    foreach ($unique_inst_combos as $h=>$combo) {
        echo combo_str($combo)."\n";
    }
}

//test();

if (0) {
    //$str = 'sonatas ; 2vn 2va viol vlne bc ; 2vn 2va viol vlne org';
    $str = 'requiems ; masses ; sop mez ten bass ch orch ; la ; grc';
    print_r(parse_tags($str));
}

// for a string like '2vn' return [2, 'vn']
// for 'vn' return [1, 'vn']
//
function parse_count_str($str) {
    if (!is_numeric($str[0])) return [1, $str];
    $n = (int)$str;
    $i = 1;
    while (is_numeric($str[$i])) $i++;
    return [$n, substr($str, $i)];
}

if (0) {
    //$str = '2vn';
    $str = 'vn';
    print_r(parse_count_str($str));
}

?>
