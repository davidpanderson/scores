<?php

require_once('cmi_db.inc');

// write .ser files by ID and by name
//
function write_ser_id_name($items, $name) {
    $x = [];
    $y = [];
    foreach ($items as $item) {
        $x[$item->name] = $item;
        $y[$item->id] = $item;
    }
    $f = fopen(sprintf('data/%s_by_name.ser', $name), 'w');
    fwrite($f, serialize($x));
    fclose($f);
    $f = fopen(sprintf('data/%s_by_id.ser', $name), 'w');
    fwrite($f, serialize($y));
    fclose($f);
}

// same, but only ID
//
function write_ser_id($items, $name) {
    $arr = [];
    foreach ($items as $y) {
        $arr[$y->id] = (object)(array) $y;
    }
    $f = fopen(sprintf("data/%s_by_id.ser", $name), 'w');
    fwrite($f, serialize($arr));
    fclose($f);
}

function write_ser_location_type() {
    write_ser_id_name(DB_location_type::enum(), 'location_type');
}

function write_ser_sex() {
    write_ser_id_name(DB_sex::enum(), 'sex');
}

function write_ser_ethnicity() {
    write_ser_id_name(DB_ethnicity::enum(), 'ethnicity');
}

function write_ser_role() {
    write_ser_id_name(DB_role::enum(), 'role');
}

function write_ser_location() {
    write_ser_id_name(DB_location::enum('', 'order by name'), 'location');
}

function write_ser_language() {
    $langs = DB_language::enum();
    $by_code = [];
    $by_id = [];
    foreach($langs as $lang) {
        $by_code[$lang->code] = $lang;
        $by_id[$lang->id] = $lang;
    }
    $f = fopen('data/lang_by_code.ser', 'w');
    fwrite($f, serialize($by_code));
    fclose($f);
    $f = fopen('data/lang_by_id.ser', 'w');
    fwrite($f, serialize($by_id));
    fclose($f);
}

function write_ser_instrument() {
    $insts = DB_instrument::enum();
    $x = [];
    foreach ($insts as $i) {
        $x[$i->imslp_code] = $i;
    }
    $f = fopen('data/inst_by_code.ser', 'w');
    fwrite($f, serialize($x));
    fclose($f);
    $x = [];
    foreach ($insts as $i) {
        $x[$i->id] = $i;
    }
    $f = fopen('data/inst_by_id.ser', 'w');
    fwrite($f, serialize($x));
    fclose($f);
}

function write_ser_comp_type() {
    $cts = DB_composition_type::enum();
    $x = [];
    foreach ($cts as $ct) {
        $x[$ct->imslp_code] = $ct;
    }
    $f = fopen('data/comp_type_by_code.ser', 'w');
    fwrite($f, serialize($x));
    fclose($f);

    $x = [];
    foreach ($cts as $ct) {
        $x[$ct->id] = $ct;
    }
    $f = fopen('data/comp_type_by_id.ser', 'w');
    fwrite($f, serialize($x));
    fclose($f);
}

function write_ser_period() {
    write_ser_id(DB_period::enum(), 'period');
}
function write_ser_license() {
    write_ser_id(DB_license::enum(), 'license');
}
function write_ser_org_type() {
    write_ser_id(DB_organization_type::enum(), 'organization_type');
}

/////////////// instrument combos ///////////////

// fill in instrument.ncombos
// NOT CURRENTLY USED
//
function populate_ncombos($combos) {
    $insts = [];
    $recs = DB_instrument::enum();
    foreach ($recs as $rec) {
        $rec->ncombos = 0;
        $insts[$rec->id] = $rec;
    }
    foreach ($combos as $combo) {
        $x = json_decode($combo->instruments);
        foreach ($x->id as $id) {
            $insts[$id]->ncombos++;
        }
    }
    foreach ($insts as $inst) {
        $inst->update("ncombos=$inst->ncombos");
    }
}

// make a version of inst combo that only has name/id,
// and is sorted by #scores + #arrs
// (for select)
// NOT CURRENTLY USED
//
function write_inst_combo_select($combos) {
    foreach ($combos as $combo) {
        $combo->ntotal = $combo->nworks + $combo->nscores;
        $combo->name = inst_combo_str_struct(json_decode($combo->instruments));
    }
    usort($combos, 'compare');
    //print_r($combos);
    $x = [[0, 'Any']];
    foreach ($combos as $combo) {
        if ($combo->ntotal > 0) {
            $x[] = [$combo->id, $combo->name];
        }
    }
    $f = fopen("data/inst_combo_select.ser", 'w');
    fwrite($f, serialize($x));
    fclose($f);
}

function compare($c1, $c2) {
    return $c1->ntotal < $c2->ntotal;
}

// make a serialized version of instrument combos,
// with instrument IDs in increasing order
//
function write_inst_combo_digest($combos) {
    $x = [];
    foreach ($combos as $combo) {
        $y = json_decode($combo->instruments);
        array_multisort($y->id, $y->count);
        $z = new StdClass;
        $z->combo_id = $combo->id;
        $z->inst_ids = $y->id;
        $z->counts = $y->count;
        $x[] = $z;
    }
    $f = fopen("data/inst_combo_digest.ser", 'w');
    fwrite($f, serialize($x));
    fclose($f);
}

function write_ser_instrument_combo() {
    $combos = DB_instrument_combo::enum();
    write_ser_id($combos , 'instrument_combo');
    write_inst_combo_digest($combos);
    //populate_ncombos($combos);
    //write_inst_combo_select($combos);
}

?>
