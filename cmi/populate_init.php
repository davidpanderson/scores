#! /usr/bin/env php

<?php
require_once('cmi_db.inc');

// populate static tables

function write_ser($items, $fname) {
    $x = [];
    foreach ($items as $item) {
        $x[$item->name] = $item;
    }
    $f = fopen("data/$fname", 'w');
    fwrite($f, serialize($x));
    fclose($f);
}

function main() {
    DB_location_type::insert("(name) values ('city')");
    DB_location_type::insert("(name) values ('province/state')");
    DB_location_type::insert("(name) values ('country')");
    DB_location_type::insert("(name) values ('subcontinent')");
    DB_location_type::insert("(name) values ('continent')");
    write_ser(DB_location_type::enum(), 'location_type_by_name.ser');

    DB_sex::insert("(name) values ('male')");
    DB_sex::insert("(name) values ('female')");
    write_ser(DB_sex::enum(), 'sex_by_name.ser');

    // see https://grants.nih.gov/grants/guide/notice-files/NOT-OD-15-089.html
    DB_ethnicity::insert("(name) values ('American Indian')");
    DB_ethnicity::insert("(name) values ('Asian')");
    DB_ethnicity::insert("(name) values ('Black')");
    DB_ethnicity::insert("(name) values ('Hispanic')");
    DB_ethnicity::insert("(name) values ('Pacific Islander')");
    DB_ethnicity::insert("(name) values ('White')");
    write_ser(DB_ethnicity::enum(), 'ethnicity_by_name.ser');
}

main();
?>
