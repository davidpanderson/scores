#! /bin/env php
<?php

// stuff that needs to be done after populating compositions
// (e.g. because it involves instrument combos,
// which don't exist before that.

// Run this after each run of populate_comp.php

require_once('cmi_db.inc');
require_once('ser.inc');

// fill in instrument.ncombos
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
//
//require_once("imslp_web.inc");
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

populate_ncombos();
do_inst_combo_select();
inst_combo_digest();

?>
