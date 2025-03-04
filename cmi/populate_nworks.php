#! /usr/bin/env php
<?php

require_once('cmi_db.inc');
function main() {
    $ret = DB::enum('select instrument_combos from composition');
    $c = [];
    foreach ($ret as $x) {
        if (!$x->instrument_combos) continue;
        $y = json_decode($x->instrument_combos);
        foreach ($y as $id) {
            if (array_key_exists($id, $c)) {
                $c[$id] += 1;
            } else {
                $c[$id] = 1;
            }
        }
    }
    foreach ($c as $id=>$count) {
        echo "$id $count\n";
        DB::do_query("update instrument_combo set nworks=$count where id=$id");
    }
}

main();
?>
