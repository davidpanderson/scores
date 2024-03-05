<?php

require_once('../inc/util.inc');
require_once('cmi_db.inc');
require_once('cmi.inc');
require_once('ser.inc');

function do_person($id) {
    $p = DB_person::lookup_id($id);
    page_head("$p->last_name, $p->first_name");
    start_table();
    row2('Last name', $p->last_name);
    row2('First name', $p->first_name);
    row2('Born', $p->born);
    row2('Birth place', location_id_to_name($p->birth_place));
    row2('Died', $p->died);
    row2('Death place', location_id_to_name($p->death_place));
    row2('Locations', locations_str($p->locations));
    row2('Sex', sex_id_to_name($p->sex));
    end_table();
    page_tail();
}

function main($type, $id) {
    switch ($type) {
    case 'person':
        do_person($id);
        break;
    }
}

$type = get_str('type');
$id = get_int('id');

main($type, $id);

?>
