<?php

// access to static tables via .ser files

/////////////// period /////////////////

function get_periods() {
    static $p = null;
    if ($p === null) {
        $p = unserialize(file_get_contents('data/period.ser'));
    }
    return $p;
}

// map period id to name using serialized table
//
function period_name($id) {
    $p = get_periods();
    return $p[$id]->name;
}

function period_name_to_id($name) {
    static $period_by_name = null;
    if (!$period_by_name) {
        $period_by_name = unserialize(file_get_contents('data/period_name_to_id.ser'));
    }
    return $period_by_name[$name];
}


/////////////// nationality /////////////////

function get_nationalities() {
    static $n = null;
    if ($n === null) {
        $n = unserialize(file_get_contents('data/nationality.ser'));
    }
    return $n;
}

function nationality_name($id) {
    $n = get_nationalities();
    return $n[$id]->name;
}

/////////////// work type /////////////////

function get_wts_by_code() {
    static $wts_by_code;
    if (!$wts_by_code) {
        $wts_by_code = unserialize(file_get_contents('data/work_type_by_code.ser'));
    }
    return $wts_by_code;
}

function work_type_by_code($code) {
    $wts_by_code = get_wts_by_code();
    if (array_key_exists($code, $wts_by_code)) {
        return $wts_by_code[$code];
    }
    return null;
}

function work_types() {
    static $work_types = null;
    if (!$work_types) {
        $work_types = unserialize(file_get_contents('data/work_type.ser'));
    }
    return $work_types;
}

/////////////// instrument /////////////////

function inst_by_code($code) {
    static $insts_by_code = null;
    if (!$insts_by_code) {
        $insts_by_code = unserialize(file_get_contents('data/inst_by_code.ser'));
    }
    if (array_key_exists($code, $insts_by_code)) {
        return $insts_by_code[$code];
    }
    return null;
}

function get_instruments() {
    static $instruments = null;
    if (!$instruments) {
        $instruments = unserialize(file_get_contents('data/instrument.ser'));
    }
    return $instruments;
}

function inst_by_id($id) {
    $instruments = get_instruments();
    if (array_key_exists($id, $instruments)) {
        return $instruments[$id];
    }
    return null;
}

function inst_name_to_id($name) {
    static $inst_by_name = null;
    if (!$inst_by_name) {
        $inst_by_name = unserialize(file_get_contents('data/instrument_name_to_id.ser'));
    }
    return $inst_by_name[$name];
}

/////////////// instrument combo /////////////////

function get_inst_combos() {
    static $inst_combos;
    if (!$inst_combos) {
        $inst_combos = unserialize(file_get_contents('data/instrument_combo.ser'));
    }
    return $inst_combos;
}

/////////////// language /////////////////

function get_langs_by_code() {
    static $langs_by_code;
    if (!$langs_by_code) {
        $langs_by_code = unserialize(file_get_contents('data/lang_by_code.ser'));
    }
    return $langs_by_code;
}

function lang_by_code($code) {
    $langs_by_code = get_langs_by_code();
    if (array_key_exists($code, $langs_by_code)) {
        return $langs_by_code[$code];
    }
    return null;
}

function get_languages() {
    static $languages = null;
    if (!$languages) {
        $languages = unserialize(file_get_contents('data/language.ser'));
    }
    return $languages;
}

/////////////// copyright /////////////////

function get_copyrights() {
    static $copyrights = null;
    if (!$copyrights) {
        $copyrights = unserialize(file_get_contents('data/copyright.ser'));
    }
    return $copyrights;
}

function copyright_id_to_name($id) {
    $copyrights = get_copyrights();
    $c = $copyrights[$id];
    return $c->name;
}

?>
