<?php

// parse tag strings

require_once('ser.inc');

// parse a string of the form
// sonatas ; 2vn 2va viol vlne bc ; 2vn 2va viol vlne org
// requiems ; masses ; sop mez ten bass ch orch ; la ; grc
// and return
// 1) a list of work type codes
// 2) a list of inst combos
//      (An inst combo is a list of [count, inst code])
// 3) a list of arrangement inst combos
// 4) a list of language codes

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
            if ($inst == '(arr)') {
                $arr = true;
                continue;
            }
            if ($inst == 'open') continue;
            if (!$inst) continue;
            if (is_numeric($inst)) continue;
            [$count, $code] = parse_count_str($inst);
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

// $combo is a list of [count, code]
//
function combo_hash($combo) {
    return md5(json_encode($combo));
}

if (0) {
    require_once('imslp_web.inc');
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
            echo inst_combo_str($combo, false)."\n";
        }
    }
    test();
}

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
