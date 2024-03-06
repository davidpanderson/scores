<?php

// access to static tables via .ser files

require_once('cmi_db.inc');

/////////////// period /////////////////

function get_periods() {
    static $p = null;
    if ($p === null) {
        $p = unserialize(file_get_contents('data/period_by_id.ser'));
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

/////////////// composition type /////////////////

function comp_type_by_code($code) {
    static $cts;
    if (!$cts) {
        $cts= unserialize(file_get_contents('data/comp_type_by_code.ser'));
    }
    if (array_key_exists($code, $cts)) {
        return $cts[$code];
    }
    return null;
}

function comp_type_id_to_name($id) {
    static $cts = null;
    if (!$cts) {
        $cts = unserialize(file_get_contents('data/comp_type_by_id.ser'));
    }
    return $cts[$id]->name;
}

/////////////// instrument /////////////////

function inst_by_code($code) {
    static $insts = null;
    if (!$insts) {
        $insts = unserialize(file_get_contents('data/inst_by_code.ser'));
    }
    if (array_key_exists($code, $insts)) {
        return $insts[$code];
    }
    return null;
}

function instrument_id_to_name($id) {
    static $insts = null;
    if (!$insts) {
        $insts = unserialize(file_get_contents('data/inst_by_id.ser'));
    }
    return $insts[$id]->name;
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

/////////////// license /////////////////

function get_licenses() {
    static $licenses = null;
    if (!$licenses) {
        $licenses = unserialize(file_get_contents('data/license.ser'));
    }
    return $licenses;
}

function license_id_to_name($id) {
    $licenses = get_licenses();
    $c = $licenses[$id];
    return $c->name;
}

/////////////// location_type /////////////////

function location_type_name_to_id($name) {
    static $location_types = null;
    if (!$location_types) {
        $location_types = unserialize(
            file_get_contents('data/location_type_by_name.ser')
        );
    }
    $lt = $location_types[$name];
    return $lt->id;
}

function location_type_id_to_name($id) {
    static $location_types = null;
    if (!$location_types) {
        $location_types = unserialize(
            file_get_contents('data/location_type_by_id.ser')
        );
    }
    $lt = $location_types[$id];
    return $lt->name;
}

/////////////// location /////////////////

function location_name_to_id($name) {
    static $locations = null;
    if (!$locations) {
        $locations = unserialize(
            file_get_contents('data/location.ser')
        );
    }
    foreach ($locations as $loc) {
        if ($loc->adjective == $name) return $loc->id;
        if ($loc->name == $name) return $loc->id;
        if ($loc->name_native == $name) return $loc->id;
        if ($loc->adjective_native == $name) return $loc->id;
    }
    return 0;
}

function get_locations() {
    static $n = null;
    if ($n === null) {
        $n = unserialize(file_get_contents('data/location_by_id.ser'));
    }
    return $n;
}

function location_id_to_name($id) {
    if (!$id) return '';
    $n = get_locations();
    return $n[$id]->name;
}

/////////////// sex /////////////////

function sex_name_to_id($name) {
    static $sexes = null;
    if (!$sexes) {
        $sexes = unserialize(file_get_contents('data/sex_by_name.ser'));
    }
    if (array_key_exists($name, $sexes)) {
        return $sexes[$name]->id;
    }
    echo "Unknown sex $name\n";
    return 0;
}

function sex_id_to_name($id) {
    static $sexes = null;
    if (!$id) return '';
    if (!$sexes) {
        $sexes = unserialize(file_get_contents('data/sex_by_id.ser'));
    }
    if (array_key_exists($id, $sexes)) {
        return $sexes[$id]->name;
    }
    echo "Unknown $id\n";
    return 0;
}

function get_sexes() {
    static $sexes = null;
    if (!$sexes) {
        $sexes = unserialize(file_get_contents('data/sex_by_id.ser'));
    }
    return $sexes;
}

/////////////// role /////////////////

function role_name_to_id($name) {
    static $roles = null;
    if (!$roles) {
        $roles = unserialize(file_get_contents('data/role_by_name.ser'));
    }
    if (array_key_exists($name, $roles)) {
        return $roles[$name]->id;
    }
    echo "Unknown role $name\n";
    return 0;
}

function role_id_to_name($id) {
    static $roles = null;
    if (!$roles) {
        $roles = unserialize(file_get_contents('data/role_by_id.ser'));
    }
    return $roles[$id]->name;
}

?>
