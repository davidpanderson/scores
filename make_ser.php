<?php

// make serialized arrays for various small tables,
// to allow lookups without DB access

require_once('imslp_db.inc');

function do_table($name) {
    $tname = "DB_$name";
    $x = $tname::enum('');
    $arr = [];
    foreach ($x as $y) {
        $arr[$y->id] = (object)(array) $y;
    }
    $f = fopen("$name.ser", 'w');
    fwrite($f, serialize($arr));
    fclose($f);
}

do_table('nationality');
do_table('period');
do_table('copyright');
do_table('arrangement_target');

?>
