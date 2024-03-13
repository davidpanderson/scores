#! /usr/bin/env php

<?php

// Make arrays for various small tables,
// to allow fast lookup by web code.
// some of these are populated during the population of person and composition,
// so you need to do this after populating.

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

do_table('period');
do_table('license');
do_table('instrument', 'order by name');
do_table('language', 'order by name');
do_table('instrument_combo');

do_name_to_id('instrument');
do_name_to_id('period');
?>
