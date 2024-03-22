#! /usr/bin/env php

<?php
require_once('cmi_db.inc');
require_once('write_ser.inc');

// populate static tables and write .ser files for
// location
// sex
// ethnicity
// role

function do_loc_type() {
    DB_location_type::insert("(name) values ('city')");
    DB_location_type::insert("(name) values ('province/state')");
    DB_location_type::insert("(name) values ('country')");
    DB_location_type::insert("(name) values ('subcontinent')");
    DB_location_type::insert("(name) values ('continent')");
}

function do_sex() {
    DB_sex::insert("(name) values ('male')");
    DB_sex::insert("(name) values ('female')");
}

function do_ethnicity() {
    // see https://grants.nih.gov/grants/guide/notice-files/NOT-OD-15-089.html
    DB_ethnicity::insert("(name) values ('American Indian')");
    DB_ethnicity::insert("(name) values ('Asian')");
    DB_ethnicity::insert("(name) values ('Black')");
    DB_ethnicity::insert("(name) values ('Hispanic')");
    DB_ethnicity::insert("(name) values ('Pacific Islander')");
    DB_ethnicity::insert("(name) values ('White')");
}

function do_role() {
    DB_role::insert("(name) values ('composer')");
    DB_role::insert("(name) values ('performer')");
    DB_role::insert("(name) values ('arranger')");
    DB_role::insert("(name) values ('lyricist')");
    DB_role::insert("(name) values ('conductor')");
    DB_role::insert("(name) values ('librettist')");
}

function do_all() {
    do_loc_type();
    do_sex();
    do_ethnicity();
    do_role();
}

function do_ser() {
    write_ser_location_type();
    write_ser_sex();
    write_ser_ethnicity();
    write_ser_role();
}

do_all();
do_ser();

?>
