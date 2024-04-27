<?php

require_once('../inc/util.inc');
require_once('cmi_db.inc');
require_once('cmi.inc');
require_once('ser.inc');

define('PAGE_SIZE', 50);

/////////////// LOCATION //////////////////

function do_location() {
    page_head('Locations');
    $locs = get_locations();
    show_button(
        sprintf('edit.php?type=%d', LOCATION),
        'Add location'
    );
    echo '<p>';
    start_table();
    table_header(
        'name', 'adjective', 'type', 'parent'
    );
    foreach ($locs as $loc) {
        table_row(
            sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                LOCATION, $loc->id, $loc->name
            ),
            $loc->adjective,
            location_type_id_to_name($loc->type),
            $loc->parent?location_id_to_name($loc->parent):''
        );
    }
    end_table();
    page_tail();
}

/////////////// PERSON //////////////////

function person_form($params) {
    form_start('search.php');
    form_input_hidden('type', 'person');
    form_input_text('Name', 'name', $params->name);
    form_select('Sex', 'sex', sex_options(), $params->sex);
    form_select('Nationality', 'location', country_options(), $params->location);
    form_submit('Search');
    form_general('',
        button_text(
            sprintf('edit.php?type=%d', PERSON),
            'Add person'
        )
    );
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
    page_head('People');
    person_form($params);
    $x = [];
    if ($params->name) {
        $x[] = sprintf(
            "match (first_name, last_name) against ('%s' in boolean mode)",
            $params->name
        );
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
        sprintf('order by last_name,first_name limit %d,%d', $params->offset, PAGE_SIZE+1)
    );
    if ($params->offset) {
        $params2 = clone $params;
        $params2->offset = max($params->offset-PAGE_SIZE, 0);
        echo sprintf('<a href=search.php?type=person%s>Previous %d</a>',
            person_encode($params2), PAGE_SIZE 
        );
    }
    start_table();
    table_header(
        'Name', 'Sex', 'Born', 'Locations'
    );
    $i = 0;
    foreach ($pers as $p) {
        if (++$i == PAGE_SIZE+1) break;
        table_row(
            sprintf('<a href=item.php?type=%d&id=%d>%s %s</a>',
                PERSON,
                $p->id,
                $p->first_name, $p->last_name
            ),
            dash(sex_id_to_name($p->sex)),
            dash(DB::date_num_to_str($p->born)),
            dash(locations_str($p->locations))
        );
    }
    end_table();
    if (count($pers) > PAGE_SIZE) {
        $params->offset += PAGE_SIZE;
        echo sprintf('<a href=search.php?type=person%s>Next %d</a>',
            person_encode($params), PAGE_SIZE
        );
    }
    page_tail();
}

/////////////// COMPOSITION //////////////////

function comp_form($params) {
    form_start('search.php');
    form_input_hidden('type', 'composition');
    form_input_text('Title', 'title', $params->title);
    select2_multi(
        'Composed for',
        'insts', instrument_options(), $params->insts
    );
    form_checkboxes('Other instruments OK?', [['others_ok', '', $params->others_ok]]);
    echo "<hr>";
    form_input_text('Composer name', 'name', $params->name);
    form_select('Composer sex', 'sex', sex_options(), $params->sex);
    form_select('Composer nationality', 'location', country_options(), $params->location);
    echo "<hr>";
    form_checkboxes(
        'Show arrangements', [['arr', '', $params->arr]], 'id=arr_check'
    );
    select2_multi(
        'For',
        'arr_insts', instrument_options(), $params->arr_insts, 'id=arr_inst'
    );
    form_checkboxes(
        'Other instruments OK?', [['arr', '', $params->arr_others_ok]],
        'id=arr_others_ok'
    );
    form_submit('Search');
    form_general('',
        button_text(
            sprintf('edit.php?type=%d', COMPOSITION),
            'Add composition'
        )
    );
    form_end();
    echo "
<script>
var arr_check = document.getElementById('arr_check');
var arr_inst = document.getElementById('arr_inst');
var arr_others_ok = document.getElementById('arr_others_ok');
f = function() {
    arr_inst.disabled = !arr_check.checked;
    arr_others_ok.disabled = !arr_check.checked;
};
f();
arr_check.onchange = f;
</script>
    ";
}

function comp_get() {
    $params = new stdClass;
    $params->offset = get_int('offset', true);
    $params->title = get_str('title', true);
    $params->insts = get_str('insts', true);
    $params->others_ok = get_str('others_ok', true);
    $params->name = get_str('name', true);
    $params->sex = get_int('sex', true);
    $params->location = get_int('location', true);
    $params->arr = get_str('arr', true);
    $params->arr_insts = get_str('arr_insts', true);
    $params->arr_others_ok = get_str('arr_others_ok', true);
    return $params;
}

function comp_encode($params) {
    $x = '';
    if ($params->offset) $x .= "&offset=$params->offset";
    if ($params->title) $x .= "&title=$params->title";
    if ($params->insts) $x .= sprintf(
        '&insts[]=%s', implode(',', $params->insts)
    );
    if ($params->others_ok) $x .= "&others_ok=$params->others_ok";
    if ($params->name) $x .= "&name=$params->name";
    if ($params->sex) $x .= "&sex=$params->sex";
    if ($params->location) $x .= "&location=$params->location";
    if ($params->arr) $x .= "&arr=$params->arr";
    if ($params->arr_insts) $x .= sprintf(
        '&arr_insts[]=%s', implode(',', $params->arr_insts)
    );
    if ($params->arr_others_ok) $x .= "&arr_others_ok=$params->arr_others_ok";
    return $x;
}

// return list of inst combos that contain the given instruments
// (and possibly others)
//
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

function imslp_url($c) {
    $t = str_replace(' ', '_', $c->long_title);
    $t = str_replace('No._', 'No.', $t);
    $t = str_replace('Op._', 'Op.', $t);
    return sprintf('https://imslp.org/wiki/%s', $t);
}

function show_arrangements($comps) {
    start_table();
    table_header(
        'Arrangement of', 'Section', 'IMSLP', 'Composed', 'Type', 'Arranger', 'Instrumentation'
    );
    foreach ($comps as $c) {
        $c2 = DB_composition::lookup_id($c->arrangement_of);
        table_row(
            sprintf(
                '<a href=item.php?type=%d&id=%d>%s</a>',
                COMPOSITION, $c2->id, $c2->long_title
            ),
            $c->title,
            sprintf('<a href=%s>View</a>', imslp_url($c2)),
            DB::date_num_to_str($c2->composed),
            comp_types_str($c2->comp_types),
            creators_str($c->creators, false),
            instrument_combos_str($c->instrument_combos)
        );
    }
    end_table();
}

function show_compositions($comps) {
    copy_to_clipboard_script();
    start_table();
    table_header(
        'Title<br><small>click for details</small>',
        'Creators',
        'IMSLP',
        'Key',
        'Opus',
        'Composed',
        'Instrumentation',
        'Code'
    );
    foreach ($comps as $c) {
        table_row(
            sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                COMPOSITION, $c->id, $c->title
            ),
            creators_str($c->creators, true),
            sprintf('<a href=%s>View</a>', imslp_url($c)),
            $c->_keys,
            $c->opus_catalogue,
            DB::date_num_to_str($c->composed),
            //comp_types_str($c->comp_types),
            instrument_combos_str($c->instrument_combos),
            copy_button(item_code($c->id, 'composition'))
        );
    }
    end_table();
}

function do_composition($params) {
    select2_head('Compositions');
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
        if (!$arr_inst_combos) {
            echo 'Nothing arranged for those instruments.';
            return;
        }
    }

    $composer_params = $params->name || $params->sex || $params->location;

    $query = 'select';
    if ($params->arr) {
        $query .= ' comp2.* from composition as comp2
            join composition as comp1
            on comp2.arrangement_of=comp1.id
        ';
    } else {
        $query .= ' comp1.* from composition as comp1
        ';
    }
    $query .= ' where true ';

    // clauses for main composition
    //
    if ($params->title) {
        $query .= sprintf(" and match(comp1.title) against ('%s' in boolean mode)",
            DB::escape($params->title)
        );
    }
    if (!$params->arr) {
        $query .= ' and comp1.arrangement_of = 0 and comp1.parent = 0
        ';
    }
    if ($params->insts) {
        $query .= sprintf(' and json_overlaps("%s", comp1.instrument_combos)',
            make_int_list($inst_combos)
        );
    }

    // composer
    //
    if ($composer_params) {
        $query .= 'and json_overlaps(
            (select json_arrayagg(person_role.id) from person_role
                join person
                on person.id = person_role.person
        ';
        $query .= sprintf('where person_role.role=%d',
            role_name_to_id('composer')
        );
        if ($params->sex) {
            $query .= sprintf(" and person.sex=%d", $params->sex);
        }
        if ($params->location) {
            $query .= sprintf(
                " and %d member of (person.locations->'$')",
                $params->location
            );
        }
        if ($params->name) {
            $query .= sprintf(
                " and match(person.first_name, person.last_name) against ('%s' in boolean mode)",
                DB::escape($params->name)
            );
        }
        $query .= '), comp1.creators->\'$\')
        ';
    }

    // arrangement
    //
    if ($params->arr_insts) {
        $query .= sprintf(' and json_overlaps("%s", comp2.instrument_combos)',
            make_int_list($arr_inst_combos)
        );
    }
    //$query .= 'order by comp1.long_title ';
    $query .= sprintf(' limit %d,%d', $params->offset, PAGE_SIZE+1);

    if (SHOW_COMP_QUERY) {
        echo "QUERY: $query\n";
    }
    $comps = DB::enum($query);

    if (!$comps) {
        echo "No compositions found.";
        return;
    }

    if ($params->offset) {
        $p2 = clone $params;
        $p2->offset = max($params->offset-PAGE_SIZE, 0);
        echo sprintf('<a href=search.php?type=composition%s>Previous %d</a>',
            comp_encode($p2), PAGE_SIZE
        );
    }
    if ($params->arr) {
        show_arrangements($comps);
    } else {
        show_compositions($comps);
    }
    if (count($comps) > PAGE_SIZE) {
        $params->offset += PAGE_SIZE;
        echo sprintf('<a href=search.php?type=composition%s>Next %d</a>',
            comp_encode($params), PAGE_SIZE
        );
    }
    page_tail();
}

//////////////////// PERSON ROLE ////////////////////

function do_person_role($id) {
    $pr = DB_person_role::lookup_id($id);
    if (!$pr) error_page("No person_role %d\n");
    $person = DB_person::lookup_id($pr->person);
    $role = DB_role::lookup_id($pr->role);
    switch ($role->name) {
    case 'composer':
        page_head("Compositions by $person->first_name $person->last_name");
        show_compositions(
            DB_composition::enum(sprintf("%d member of (creators->'$')", $id))
        );
        break;
    case 'performer':
        break;
    case 'arranger':
        page_head("Arrangements by $person->first_name $person->last_name");
        show_arrangements(
            DB_composition::enum(sprintf("%d member of (creators->'$')", $id))
        );
        break;
    case 'lyricist':
        page_head("Compositions with lyrics by $person->first_name $person->last_name");
        break;
    case 'librettist':
        page_head("Compositions with libretto by $person->first_name $person->last_name");
        show_compositions(
            DB_composition::enum(sprintf("%d member of (creators->'$')", $id))
        );
        break;
    }
    page_tail();
}

function do_concert() {
    page_head("Concerts");
    $cs = DB_concert::enum();
    start_table();
    table_header('Details', 'Venue', 'Location', 'Date');
    foreach ($cs as $c) {
        $v = DB_venue::lookup_id($c->venue);
        table_row(
            sprintf('<a href=item.php?type=%d&id=%d>View</a>',
                CONCERT, $c->id
            ),
            dash($v->name),
            $v?location_id_to_name($v->location):dash(null),
            DB::date_num_to_str($c->_when)
        );
    }
    end_table();
    show_button(
        sprintf('edit.php?type=%d', CONCERT),
        'Add concert'
    );
    page_tail();
}

function do_venue() {
    page_head("Venues");
    $vs = DB_venue::enum();
    start_table();
    table_header('Name', 'Location');
    foreach ($vs as $v) {
        table_row(
            sprintf('<a href=edit.php?type=%d&id=%d>%s</a>',
                VENUE, $v->id, $v->name
            ),
            location_id_to_name($v->location)
        );
    }
    end_table();
    show_button(
        sprintf('edit.php?type=%d', VENUE),
        'Add venue'
    );
    page_tail();
}

function do_organization() {
    page_head('Organizations');
    $orgs = DB_organization::enum('', 'order by name');
    start_table();
    table_header('Name', 'Type', 'Location');
    foreach ($orgs as $org) {
        table_row(
            sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                ORGANIZATION, $org->id, $org->name
            ),
            organization_type_str($org->type),
            $org->location
        );
    }
    end_table();
    show_button(
        sprintf('edit.php?type=%d', ORGANIZATION),
        'Add organization'
    );
    page_tail();
}

function do_ensemble() {
    page_head('Ensembles');
    $enss = DB_ensemble::enum();
    start_table();
    table_header('Name', 'Type', 'Location');
    foreach($enss as $ens) {
        $loc = null;
        if ($ens->location) {
            $loc = DB_location::lookup_id($ens->location);
        }
        table_row(
            sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                ENSEMBLE, $ens->id, $ens->name
            ),
            ensemble_type_id_to_name($ens->type),
            $loc?location_name($loc):dash()
        );
    }
    end_table();
    show_button(
        sprintf('edit.php?type=%d', ENSEMBLE),
        'Add ensemble'
    );
    page_tail();
}

///////////////////// INSTRUMENT COMBO //////////////////////

function inst_combo_form($params) {
    form_start('search.php');
    form_input_hidden('type', 'inst_combo');
    select2_multi(
        'Instruments',
        'insts', instrument_options(), $params->insts
    );
    form_submit('Search');
    form_general('',
        button_text(
            sprintf('edit.php?type=%d', INST_COMBO),
            'Add new instrument combination'
        )
    );
    form_end();
}

function inst_combo_get() {
    $params = new StdClass;
    $params->insts = get_str('insts', true);
    if (!$params->insts) $params->insts=[];
    return $params;
}

function do_inst_combo($params) {
    select2_head('Instrument combinations');
    inst_combo_form($params);
    $combo_ids = get_combos($params->insts, true);
    if (!$combo_ids) {
        echo 'There are currently no instrumentations with those instruments.';
        page_tail();
        exit();
    }
    start_table();
    copy_to_clipboard_script();
    table_header('Instruments', 'Code');
    foreach ($combo_ids as $id) {
        $ic = DB_instrument_combo::lookup_id($id);
        table_row(
            instrument_combo_str($ic),
            copy_button(item_code($id, 'inst_combo'))
        );
    }
    end_table();
    page_tail();
}

///////////////////////////////////////

function main($type) {
    switch ($type) {
    case 'composition':
        do_composition(comp_get());
        break;
    case 'concert':
        do_concert();
        break;
    case 'ensemble':
        do_ensemble();
        break;
    case 'instrument':
        do_instrument();
        break;
    case 'location':
        do_location();
        break;
    case 'organization':
        do_organization();
        break;
    case 'person':
        do_person(person_get());
        break;
    case 'person_role':
        do_person_role(get_int('id'));
        break;
    case 'venue':
        do_venue();
        break;
    case 'inst_combo':
        do_inst_combo(inst_combo_get());
        break;
    default:
        error_page("$type not implemented");
    }
}

if (!editor()) error_page("Not authorized to edit");
main(get_str('type'));

?>
