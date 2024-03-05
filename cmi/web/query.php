<?php

require_once('../inc/util.inc');
require_once('cmi_db.inc');
require_once('cmi.inc');
require_once('ser.inc');

function do_location() {
    $locs = DB_location::enum();
    start_table();
    table_header(
        'ID', 'name', 'adjective', 'type', 'parent'
    );
    foreach ($locs as $loc) {
        table_row(
            $loc->id,
            $loc->name,
            $loc->adjective,
            location_type_id_to_name($loc->type),
            $loc->parent?location_id_to_name($loc->parent):''
        );
    }
    end_table();
}

function do_person() {
    $pers = DB_person::enum('', 'order by last_name limit 50');
    start_table();
    table_header(
        'ID', 'name', 'sex', 'born', 'locations'
    );
    foreach ($pers as $p) {
        table_row(
            sprintf('<a href=item.php?type=person&id=%d>%d</a>',
                $p->id, $p->id
            ),
            $p->last_name.', '.$p->first_name,
            sex_id_to_name($p->sex),
            $p->born,
            locations_str($p->locations)
        );
    }
    end_table();
}

function main($type) {
    global $tables;
    page_head($tables[$type]);
    switch ($type) {
    case 'location':
        do_location(); break;
    case 'person':
        do_person(); break;
    case 'instrument':
        do_instrument(); break;
    case 'composition':
        do_composition(); break;
    default:
        echo 'Unimplemented'; break;
    }
    page_tail();
}

$type = get_str('type');
if (array_key_exists($type, $tables)) {
    main($type);
} else {
    error_page("No type $type");
}

?>
