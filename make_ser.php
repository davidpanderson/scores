#! /usr/bin/env php

<?php

// Make arrays for various small tables, to allow lookups without DB access.
// Write them (serialized) to files.
//
// instrument
//      instrument.ser
//          id => object
//      instrument_by_name.ser
//          name => id
// period
//      period.ser
//          id => object
//      period_by_name.ser
//          name => id
// work_type
//      work_type.ser
//          id => object

require_once('imslp_db.inc');
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
    $f = fopen("data/$name.ser", 'w');
    fwrite($f, serialize($arr));
    fclose($f);
}

// make a version of inst combo that only has name/id,
// and is sorted by #scores + #arrs
// (for select)
//
require_once("imslp_web.inc");
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

do_table('nationality', 'order by name');
do_table('period');
do_table('copyright');
do_table('arrangement_target');
do_table('work_type');
do_table('instrument', 'order by name');
do_table('language', 'order by name');
do_table('instrument_combo');

do_name_to_id('instrument');
do_name_to_id('period');

populate_ncombos();
do_inst_combo_select();

?>
