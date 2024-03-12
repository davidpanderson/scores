<?php

require_once('cmi_db.inc');

// two ways of parsing the instrumentation string (hier3) for a score,
// e.g. 'For Flute, 2 Oboes, and Harp (Yokoyama)'
//
// The first just returns 'Flute, 2 Oboes, and Harp';
// this is used to populate the arrangement_target table.
// Possibly deprecated.
//
// The second creates a data structure describing
// one or more "instrument combos": lists of (count, instrument)

////////////////// 1st way //////////////////

// return the arrangement target
// e.g. if the score has
// hier1 = 'Arrangements and Transcriptions'
// hier3 = 'For Flute, 2 Oboes, and Harp (Yokoyama)'
//
// Note: this is semi deprecated;
// see parse_arrangement_string()

function parse_arrangement_target($hier) {
    $x = $hier[2];
    if (substr2($x, 0, 4) == 'For ') {
        $start = 4;
    } else if (substr2($x, 0, 5) == '*For ') {
        $start = 5;
    } else {
        return null;
    }
    $n = strpos($x, '(');
    if ($n) {
        $end = $n-1;
    } else {
        $end = strlen($x);
    }
    return substr2($x, $start, $end);
}

////////////////// 2nd way //////////////////

// parse a string like
// 'For 2 Clarinets, 2 Bassoons and 2 Horns (Patterson)'
// return a list of inst combos or null
// Each inst combo is a list of [count, code]
//
// for 'For Violin or Cello and Piano', return 2 combos:
// (violin, piano) and (cello, piano)
//
// TODO: make it work for
// For 4 Horns (or 3 Horns and Bassoon) (Miller)
// *For Violin, Cello or 2 Violins and Piano (Hoffmann)
//   I guess this means (vio+cel+piano) or (2vio + piano)
// *For Piano Trio or 2 Violins and Piano (Hofmann)
// For 2 Flutes (2nd also Piccolo), 2 Oboes, 2 Clarinets, 2 Bassoons and 2 Horns (Clements)
//
// deal with missing stuff:
// Theater Orchestra
// Theatre Orchestra
// Wind Band
// Wind Octet
// String Orchestra
// Bandurria
// String Quartet
// Treble Recorder
// No.1 For Small Orchestra (Mouton)
// *For Instrument and Piano (Larocque)
//
function parse_arrangement_string($x) {
    $x = strtolower($x);

    // some special cases
    $x = str_replace('pianos 8 hands', 'piano 8 hands', $x);
    $x = str_replace('piano 4-hands', 'piano 4 hands', $x);
    $x = str_replace('piano solo', 'piano', $x);
    $x = str_replace('piano trio', 'violin, cello and piano', $x);

    $x = str_replace('/', ' or ', $x);

    // most but not all start with 'for '
    $i = strpos($x, 'for ');
    if ($i !== false) {
        $x = substr($x, $i+4);
    }

    $i = strpos($x, '(');
    if ($i !== false) {
        $x = substr($x, 0, $i);
    }
    $x = str_replace(' and ', ' , ', $x);
    $parts = explode(',', $x);
    $pp = [];
    $got_or = false;
    foreach ($parts as $part) {
        if (!$part) continue;
        $p = parse_arr_part($part);
        if (!$p) return null;
        $pp[] = $p;
        if (is_array($p[0])) {
            $got_or = true;
        }
    }
    if ($got_or) {
        return [make_ic($pp, 0), make_ic($pp, 1)];
    } else {
        return [make_ic($pp)];
    }
}

// $pp is a list of [count, code]
// or (for "or") [[count, code], [count, code]]
// Make a list, using one or the other of the "or" parts
//
function make_ic($pp, $i=0) {
    $ic = [];
    foreach ($pp as $p) {
        if (is_array($p[0])) {
            $ic[] = $p[$i];
        } else {
            $ic[] = $p;
        }
    }
    return $ic;
}

// $x is like
// Piano 4 hands
// 2 Clarinets
// Chromatic Harmonica or Flute
//
// return [count, code] or [[count, code], [count, code]]
//
function parse_arr_part($x) {
    $y = explode(' or ', $x);
    if (count($y) > 1) {
        $a = parse_arr_part($y[0]);
        $b = parse_arr_part($y[1]);
        if ($a && $b) return [$a, $b];
        return null;
    }
    $x = trim($x);
    $y = explode(' ', $x);
    if (count($y)>1 && is_numeric($y[0])) {
        $count = (int)$y[0];
        $i = strpos($x, ' ');
        $x = substr($x, $i+1);
    } else {
        $count = 1;
    }
    $code = inst_name_to_code($x);
    if (!$code && $count>1 && substr($x, -1, 1)=='s') {
        $x = substr($x, 0, -1);
        $code = inst_name_to_code($x);
    }
    if ($code) {
        return [$count, $code];
    } else {
        //echo "not found: $x\n";
        return null;
    }
}

// map instrument name to code
//
function inst_name_to_code($name) {
    static $arr = [];
    if (!$arr) {
        $insts = DB_instrument::enum();
        foreach ($insts as $inst) {
            $arr[strtolower($inst->name)] = $inst->imslp_code;
        }
    }
    if (array_key_exists($name, $arr)) {
        return $arr[$name];
    }
    return null;
}

if (0) {
    echo inst_name_to_code('voices');
}

if (0) {
    //$str = '*For Flute, 2 Oboes, Clarinet, Bassoon and Horn or Harp (Yokoyama)';
    $str = 'For Piano (Novegno)';
    print_r(parse_arrangement_string($str));
}

if (0) {
    // uncomment "not found" above
    $fs = DB_score_file_set::enum("hier3<>''", 'limit 1000');
    foreach ($fs as $f) {
        $s = $f->hier3;
        $x = parse_arrangement_string($s);
        if ($x) {
            echo "success: $s\n";
        } else {
            echo "fail: $s\n";
        }
    }
}

if (0) {
    require_once("imslp_db.inc");
    $fs = DB_audio_file_set::enum();
    foreach ($fs as $f) {
        $s = $f->hier3;
        if (!str_starts_with($s, 'For ')) continue;
        $x = parse_arrangement_string($s);
        if ($x) {
            echo "$s\n";
            print_r($x);
        } else {
            echo "cant parse $s\n";
        }
    }
}

?>
