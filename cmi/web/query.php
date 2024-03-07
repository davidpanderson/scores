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
    form_input_hidden('type', 'composition');
    form_input_text('Title', 'title', $params->title);
    form_select_multiple(
        'Composed for<br><small>Use Ctrl to select multiple</small>',
        'insts', instrument_options(), $params->insts
    );
    form_checkboxes('Other instruments OK?', [['arr', '', $params->others_ok]]);
    form_input_text('Composer name', 'name', $params->name);
    form_select('Composer sex', 'sex', sex_options(), $params->sex);
    form_select('Composer nationality', 'location', country_options(), $params->location);
    form_checkboxes('Arrangement?', [['arr', '', $params->arr]]);
    form_select_multiple(
        'Arranged for<br><small>Use Ctrl to select multiple</small>',
        'arr_insts', instrument_options(), $params->arr_insts
    );
    form_checkboxes('Other instruments OK?', [['arr', '', $params->arr_others_ok]]);
    form_submit('Update');
    form_end();
}

function comp_get() {
    $params = new stdClass;
    $params->offset = get_int('offset', true);
    $params->title = get_str('title', true);
    $params->insts = get_str('insts', true);
    $params->others_ok = get_int('others_ok', true);
    $params->name = get_str('name', true);
    $params->sex = get_int('sex', true);
    $params->location = get_int('location', true);
    $params->arr = get_str('arr', true);
    $params->arr_insts = get_str('arr_insts', true);
    $params->arr_others_ok = get_int('arr_others_ok', true);
    return $params;
}

function comp_encode($params) {
    $x = '';
    if ($params->offset) $x .= "&offset=$params->offset";
    if ($params->title) $x .= "&title=$params->title";
    if ($params->insts) $x .= sprintf(
        '&insts=%s',
        http_build_query($params->insts)
    );
    if ($params->others_ok) $x .= "&others_ok=$params->others_ok";
    if ($params->name) $x .= "&name=$params->name";
    if ($params->sex) $x .= "&sex=$params->sex";
    if ($params->location) $x .= "&location=$params->location";
    if ($params->arr) $x .= "&arr=$params->arr";
    if ($params->arr_insts) $x .= sprintf(
        '&arr_insts=%s',
        http_build_query($params->arr_insts)
    );
    if ($params->arr_others_ok) $x .= "&arr_others_ok=$params->arr_others_ok";
    return $x;
}

function get_combos($insts, $others_ok) {
    $spec_ids = [];
    $spec_min = [];
    $spec_max = [];
    foreach ($insts as $i) {
        $j = (int)$i;
        if (!$j) continue;
        $spec_ids[] = $j;
        $spec_min[] = 1;
        $spec_max[] = 999;
    }
    return inst_combo_ids($spec_ids, $spec_min, $spec_max, $others_ok);
}

// convert list of ints to string like '[1, 5, 10]'
//
function make_int_list($list) {
    return sprintf('[%s]', implode(',', $list));
}

function do_composition($params) {
    $page_size = 50;
    comp_form($params);

    // make a SQL query based on search params

    if ($params->insts && $params->insts<>[0]) {
        $inst_combos = get_combos($params->insts, $params->others_ok);
        if (!$inst_combos) {
            echo 'Nothing composed for those instruments.';
            return;
        }
    }
    if ($params->arr_insts && $params->arr_insts<>[0]) {
        $arr_inst_combos = get_combos($params->arr_insts, $params->arr_others_ok);
        echo "<p>final\n";
        print_r($arr_inst_combos);
        if (!$arr_inst_combos) {
            echo 'Nothing arranged for those instruments.';
            return;
        }
    }

    $composer_params = $params->name || $params->sex || $params->location;

    $query = 'select';
    if ($params->arr) {
        $query .= ' comp2.*';
    } else {
        $query .= ' comp1.*';
    }
    $query .= ' from composition as comp1';
    if ($composer_params) {
        $query .= ' , person_role, person';
    }
    if ($params->arr) {
        $query .= ' , composition as comp2';
    }
    $query .= ' where true';

    // main composition
    //
    if ($params->title) {
        $query .= sprintf(" and match(comp1.long_title) against ('%s' in boolean mode)",
            DB::escape($params->title)
        );
    }
    if (!$params->arr) {
        $query .= ' and comp1.arrangement_of is null';
    }
    if ($params->insts) {
        $query .= sprintf(' and json_overlaps("%s", comp1.instrument_combos)',
            make_int_list($inst_combos)
        );
    }

    // composer
    //
    if ($composer_params) {
        $query .= " and person_role.id member of (comp1.creators->'$')";
        $query .= sprintf(' and person_role.role=%d',
            role_name_to_id('composer')
        );
        $query .= ' and person_role.person = person.id ';
    }
    if ($params->name) {
        $query .= sprintf(
            " and match(person.first_name, person.last_name) against ('%s' in boolean mode)",
            DB::escape($params->name)
        );
    }
    if ($params->sex) {
        $query .= sprintf(" and person.sex=%d", $params->sex);
    }
    if ($params->location) {
        $query .= sprintf(
            " and %d member of (person.locations->'$')",
            $params->location
        );
    }

    // arrangement
    //
    if ($params->arr) {
        $query .= ' and comp2.arrangement_of=comp1.id';
    }
    if ($params->arr_insts) {
        $query .= sprintf(' and json_overlaps("%s", comp2.instrument_combos)',
            make_int_list($arr_inst_combos)
        );
    }
    $query .= sprintf(' limit %d,%d', $params->offset, $page_size+1);

    echo "QUERY: $query\n";
    $comps = DB::enum($query);
    //$comps = DB_composition::enum('arrangement_of is null and parent is null', 'limit 50');

    if (!$comps) {
        echo "No compositions found.";
        return;
    }

    if ($params->offset) {
        $params->offset = max($params->offset-$page_size, 0);
        echo sprintf('<a href=query.php?type=composition%s>Previous %d</a>',
            comp_encode($params), $page_size
        );
    }
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
    if (count($comps) > $page_size) {
        $params->offset += $page_size;
        echo sprintf('<a href=query.php?type=composition%s>Next %d</a>',
            comp_encode($params), $page_size
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
