#! /usr/bin/env php

<?php

// Serialize tables that are populated
// during the population of person and composition,
// so you need to do this after each run of populate_comp.php

require_once('cmi_db.inc');
require_once('ser.inc');

function do_name_to_id($table_name) {
    $tname = "DB_$table_name";
    $insts = $tname::enum();
    $x = [];
    foreach ($insts as $inst) {
        $x[$inst->name] = $inst->id;
    }
    $f = fopen("data/".$table_name."_name_to_id.ser", 'w');
    fwrite($f, serialize($x));
    fclose($f);
}

function do_table($name, $clause=null) {
    $tname = "DB_$name";
    $x = $tname::enum('', $clause);
    $arr = [];
    foreach ($x as $y) {
        $arr[$y->id] = (object)(array) $y;
    }
    $f = fopen(sprintf("data/%s_by_id.ser", $name), 'w');
    fwrite($f, serialize($arr));
    fclose($f);
}

// fill in instrument.ncombos
// NOT CURRENTLY USED
//
function populate_ncombos() {
    $insts = [];
    $recs = DB_instrument::enum();
    foreach ($recs as $rec) {
        $insts[$rec->id] = $rec;
    }
    $combos = get_inst_combos();
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
function do_inst_combo_select() {
    $combos = get_inst_combos();
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
function inst_combo_digest() {
    $combos = get_inst_combos();
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

do_table('period');
do_name_to_id('period');
do_table('license');
do_table('instrument_combo');

//populate_ncombos();
//do_inst_combo_select();
inst_combo_digest();

?>
