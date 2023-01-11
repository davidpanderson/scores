<?php

// access to static tables via .ser files

/////////////// period /////////////////

function get_periods() {
    static $p = null;
    if ($p === null) {
        $p = unserialize(file_get_contents('period.ser'));
    }
    return $p;
}

// map period id to name using serialized table
//
function period_name($id) {
    $p = get_periods();
    return $p[$id]->name;
}

/////////////// nationality /////////////////

function get_nationalities() {
    static $n = null;
    if ($n === null) {
        $n = unserialize(file_get_contents('nationality.ser'));
    }
    return $n;
}

function nationality_name($id) {
    $n = get_nationalities();
    return $n[$id]->name;
}

/////////////// work type /////////////////

$wts_by_code = null;
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

function work_types() {
    static $work_types = null;
    if (!$work_types) {
        $work_types = unserialize(file_get_contents('work_type.ser'));
    }
    return $work_types;
}

/////////////// instrument /////////////////

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

function inst_by_id($id) {
    static $inst_by_id = null;
    if (!$inst_by_id) {
        $inst_by_id = unserialize(file_get_contents('instrument.ser'));
    }
    if (array_key_exists($id, $inst_by_id)) {
        return $inst_by_id[$id];
    }
    return null;
}

/////////////// instrument combo /////////////////

$inst_combos=null;
function get_inst_combos() {
    global $inst_combos;
    if (!$inst_combos) {
        $inst_combos = unserialize(file_get_contents('instrument_combo.ser'));
    }
    return $inst_combos;
}

/////////////// language /////////////////

$langs_by_code = null;
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

function languages() {
    static $languages = null;
    if (!$languages) {
        $languages = unserialize(file_get_contents('language.ser'));
    }
    return $languages;
}

?>
