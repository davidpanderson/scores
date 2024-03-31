<?php

// access to small tables via .ser files
// used by web code

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

function get_comp_types() {
    static $cts = null;
    if (!$cts) {
        $cts = unserialize(file_get_contents('data/comp_type_by_id.ser'));
    }
    return $cts;
}

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
    $cts = get_comp_types();
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

function get_instruments() {
    static $insts = null;
    if (!$insts) {
        $insts = unserialize(file_get_contents('data/inst_by_id.ser'));
    }
    return $insts;
}

/////////////// instrument combo /////////////////

function get_inst_combos() {
    static $inst_combos;
    if (!$inst_combos) {
        $inst_combos = unserialize(file_get_contents('data/instrument_combo_by_id.ser'));
    }
    return $inst_combos;
}

function get_inst_combos_digest() {
    static $icd;
    if (!$icd) {
        $icd = unserialize(file_get_contents('data/inst_combo_digest.ser'));
    }
    return $icd;
}

// does the combo match the spec?
// See scores/api.h
//
function inst_combo_match(
    $ic_id, $ic_count, $spec_ids, $spec_min, $spec_max, $others_ok
) {
    $ic_ind = 0;
    $spec_ind = 0;
    $ic_len = count($ic_id);
    $spec_len = count($spec_ids);
    while (1) {
        if ($ic_ind == $ic_len) {
            // reached end of combo
            // if spec has more insts with nonzero min, fail
            while ($spec_ind < $spec_len) {
                if ($spec_min[$spec_ind]) {
                    return false;
                }
                $spec_ind++;
            }
            return true;
        }
        if ($spec_ind == $spec_len) {
            return $others_ok;
        }
        if ($ic_id[$ic_ind] == $spec_ids[$spec_ind]) {
            // inst ids match
            if ($ic_count[$ic_ind] < $spec_min[$spec_ind]) return false;
            if ($ic_count[$ic_ind] > $spec_max[$spec_ind]) return false;
            $ic_ind++;
            $spec_ind++;
        } else if ($ic_id[$ic_ind] < $spec_ids[$spec_ind]) {
            // combo has an instrument that spec doesn't
            if (!$others_ok) {
                return false;
            }
            $ic_ind++;
        } else {
            // spec has an instrument that combo doesn't
            if ($spec_min[$spec_ind]) {
                return false;
            }
            $spec_ind++;
        }
    }
    die("inst_combo_match() error\n");
}

if (0) {
    $ic_id = [10, 11, 12];
    $ic_count = [1, 1, 1];
    $spec_ids = [10];
    $spec_min = [1];
    $spec_max = [999];
    $ret = inst_combo_match(
        $ic_id, $ic_count, $spec_ids, $spec_min, $spec_max, true
    );
    if ($ret) {
        echo "match\n";
    } else {
        echo "no match\n";
    }
    exit;
}

// get list of inst combos with given instruments
//
function inst_combo_ids($spec_ids, $spec_min, $spec_max, $others_ok) {
    $ics = get_inst_combos_digest();
    $out = [];
    foreach ($ics as $ic) {
        if (inst_combo_match(
            $ic->inst_ids, $ic->counts, $spec_ids, $spec_min, $spec_max, $others_ok
        )) {
            $out[] = $ic->combo_id;
        }
    }
    return $out;
}

if (0) {
    $spec_ids = [115];
    $spec_min = [1];
    $spec_max = [999];
    print_r(inst_combo_ids($spec_ids, $spec_min, $spec_max, false));
    exit;
}

/////////////// language /////////////////

function lang_by_code($code) {
    static $langs_by_code;
    if (!$langs_by_code) {
        $langs_by_code = unserialize(file_get_contents('data/lang_by_code.ser'));
    }
    if (array_key_exists($code, $langs_by_code)) {
        return $langs_by_code[$code];
    }
    return null;
}

function language_id_to_name($id) {
    static $languages = null;
    if (!$languages) {
        $languages = unserialize(file_get_contents('data/lang_by_id.ser'));
    }
    return $languages[$id]->name;
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
            file_get_contents('data/location_by_id.ser')
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

/////////////// ethnicity /////////////////

function get_ethnicities() {
    static $ethnicities = null;
    if (!$ethnicities) {
        $ethnicities = unserialize(file_get_contents('data/ethnicity_by_id.ser'));
    }
    return $ethnicities;
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
    die("Unknown role $name\n");
    return 0;
}

function role_id_to_name($id) {
    static $roles = null;
    if (!$roles) {
        $roles = unserialize(file_get_contents('data/role_by_id.ser'));
    }
    return ucfirst($roles[$id]->name);
}

?>
