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

/////////////// PERSON //////////////////

function person_form($params) {
    form_start('query.php');
    form_input_hidden('type', 'person');
    form_input_text('Name', 'name', $params->name);
    form_select('Sex', 'sex', sex_options(), $params->sex);
    form_select('Nationality', 'location', country_options(), $params->location);
    form_submit('Update');
    form_end();
}

function person_get() {
    $params = new stdClass;
    $params->offset = get_int('offset', true);
    $params->sex = get_int('sex', true);
    $params->name = get_str('name', true);
    $params->location = get_int('location', true);
    return $params;
}

function person_encode($params) {
    $x = '';
    if ($params->offset) $x .= "&offset=$params->offset";
    if ($params->sex) $x .= "&sex=$params->sex";
    if ($params->name) $x .= "&name=$params->name";
    if ($params->location) $x .= "&location=$params->location";
    return $x;
}

function do_person($params) {
    $page_size = 50;
    
    person_form($params);
    $x = [];
    if ($params->name) {
        $x[] = sprintf("last_name like '%%%s%%'", $params->name);
    }
    if ($params->sex) {
        $x[] = sprintf('sex=%d', $params->sex);
    }
    if ($params->location) {
        $x[] = sprintf("%d member of (locations->'$')", $params->location);
    }
    $y = implode(' and ', $x);
    $pers = DB_person::enum(
        $y,
        sprintf('order by last_name limit %d,%d', $params->offset, $page_size+1)
    );
    if ($params->offset) {
        $params->offset = max($params->offset-$page_size, 0);
        echo sprintf('<a href=query.php?type=person%s>Previous %d</a>',
            person_encode($params), $page_size
        );
    }
    start_table();
    table_header(
        'name', 'sex', 'born', 'locations'
    );
    $i = 0;
    foreach ($pers as $p) {
        if (++$i == $page_size+1) break;
        table_row(
            sprintf('<a href=item.php?type=person&id=%d>%s</a>',
                $p->id,
                $p->last_name.', '.$p->first_name
            ),
            sex_id_to_name($p->sex),
            $p->born,
            locations_str($p->locations)
        );
    }
    end_table();
    if (count($pers) > $page_size) {
        $params->offset += $page_size;
        echo sprintf('<a href=query.php?type=person%s>Next %d</a>',
            person_encode($params), $page_size
        );
    }
}

/////////////// COMPOSITION //////////////////

function comp_form($params) {
    form_start('query.php');
    form_input_hidden('type', 'person');
    form_input_text('Title', 'title', $params->title);
    form_select('Instrument', 'inst1', instrument_options(), $params->inst1);
    form_select('Instrument', 'inst2', instrument_options(), $params->inst2);
    form_input_text('Composer name', 'name', $params->name);
    form_select('Composer sex', 'sex', sex_options(), $params->sex);
    form_select('Composer nationality', 'location', country_options(), $params->location);
    form_checkboxes('Arrangement?', [['arr', '', $params->arr]]);
    form_select('Instrument', 'arr_inst1', instrument_options(), $params->arr_inst1);
    form_select('Instrument', 'arr_inst2', instrument_options(), $params->arr_inst2);
    form_submit('Update');
    form_end();
}

function comp_get() {
    $params = new stdClass;
    $params->offset = get_int('offset', true);
    $params->title = get_str('title', true);
    $params->inst1 = get_int('inst1', true);
    $params->inst2 = get_int('inst2', true);
    $params->name = get_str('name', true);
    $params->sex = get_int('sex', true);
    $params->location = get_int('location', true);
    $params->arr = get_int('arr', true);
    $params->arr_inst1 = get_int('arr_inst1', true);
    $params->arr_inst2 = get_int('arr_inst2', true);
    return $params;
}

function comp_encode($params) {
    $x = '';
    if ($params->offset) $x .= "&offset=$params->offset";
    if ($params->title) $x .= "&title=$params->title";
    if ($params->inst1) $x .= "&inst1=$params->inst1";
    if ($params->inst2) $x .= "&inst1=$params->inst2";
    if ($params->name) $x .= "&inst1=$params->name";
    if ($params->sex) $x .= "&sex=$params->sex";
    if ($params->location) $x .= "&location=$params->location";
    if ($params->arr) $x .= "&arr=$params->arr";
    if ($params->arr_inst1) $x .= "&arr=$params->arr_inst1";
    if ($params->arr_inst2) $x .= "&arr=$params->arr_inst2";
    return $x;
}

function do_composition($params) {
    $page_size = 50;
    comp_form($params);
    $comps = DB_composition::enum('arrangement_of is null and parent is null', 'limit 50');
    start_table();
    table_header(
        'title', 'composed', 'type', 'creators', 'instrumentation'
    );
    foreach ($comps as $c) {
        if ($c->arrangement_of) {
            $c2 = DB_composition::lookup_id($c->arrangement_of);
            $title = sprintf(
                'Arrangement of <a href=item.php?type=composition&id=%d>%s</a>',
                $c2->id, $c2->long_title
            );
        } else {
            $title = sprintf('<a href=item.php?type=composition&id=%d>%s</a>',
                $c->id, $c->title
            );
        }
        table_row(
            $title,
            $c->composed,
            comp_types_str($c->comp_types),
            creators_str($c->creators),
            instrument_combos_str($c->instrument_combos)
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
        do_person(person_get()); break;
    case 'instrument':
        do_instrument(); break;
    case 'composition':
        do_composition(comp_get()); break;
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
