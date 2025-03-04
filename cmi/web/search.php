<?php

// show lists of items of a given type.
// For large tables, provide a form that lets you filter results

require_once('../inc/util.inc');
require_once('cmi_db.inc');
require_once('cmi.inc');
require_once('ser.inc');

define('PAGE_SIZE', 50);

/////////////// LOCATION //////////////////

function location_search() {
    page_head('Locations');
    $locs = get_locations();
    echo button_link(
        sprintf('edit.php?type=%d', LOCATION),
        'Add location'
    );
    echo '<p>';
    start_table('table-striped');
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
    form_submit2('Search');
    form_general('',
        button_link(
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

function person_params_url($params) {
    $x = '';
    if ($params->offset) $x .= "&offset=$params->offset";
    if ($params->sex) $x .= "&sex=$params->sex";
    if ($params->name) $x .= "&name=$params->name";
    if ($params->location) $x .= "&location=$params->location";
    return $x;
}

function person_search($params) {
    page_head('People');
    person_form($params);
    $x = [];
    if ($params->name) {
        $x[] = sprintf(
            "match (first_name, last_name) against ('%s')",
            $params->name
        );
    }
    $x[] = " last_name<>'' ";
    if ($params->sex) {
        $x[] = sprintf('sex=%d', $params->sex);
    }
    if ($params->location) {
        $x[] = sprintf("%d member of (locations->'$')", $params->location);
    }
    $y = implode(' and ', $x);
    $pers = DB_person::enum(
        $y,
        sprintf(
            '%s limit %d,%d',
            $params->name?'':'order by last_name,first_name',
            $params->offset, PAGE_SIZE+1
        )
    );
    if (!$pers) {
        echo "<h2>No people found</h2>
        Please modify your search and try again.";
        page_tail();
        return;
    }
    if ($params->offset) {
        $params2 = clone $params;
        $params2->offset = max($params->offset-PAGE_SIZE, 0);
        echo sprintf('<a href=search.php?type=person%s>Previous %d</a>',
            person_params_url($params2), PAGE_SIZE 
        );
    }
    start_table('table-striped');
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
            person_params_url($params), PAGE_SIZE
        );
    }
    page_tail();
}

/////////////// COMPOSITION //////////////////

function comp_form($params) {
    form_start('search.php');
    form_input_hidden('type', 'composition');
    form_input_text('Title contains', 'title', $params->title);
    form_general('', '<b><font size=+1>Composed for:</font></b>');
    select2_multi(
        'Select one or more instruments:',
        'insts', instrument_options(), $params->insts
    );
    form_select('... or select an instrumentation',
        'inst_combo_id', inst_combo_options(1000), $params->inst_combo_id
    );
    form_input_text(
        '... or enter an <a href=search.php?type=inst_combo>instrumentation code</a>',
        'inst_combo_code', $params->inst_combo_code
    );
    form_checkboxes('Additional instruments OK?', [['others_ok', '', $params->others_ok]]);
    echo "<hr>";
    form_input_text('Composer name contains', 'name', $params->name);
    form_select('Composer sex', 'sex', sex_options(), $params->sex);
    form_select('Composer nationality', 'location', country_options(), $params->location);
    echo "<hr>";
    form_checkboxes(
        'Show arrangements', [['arr', '', $params->arr]], 'id=arr_check'
    );
    form_general('', '<b><font size=+1>Arranged for:</font></b>');
    select2_multi(
        'Select one or more instruments:',
        'arr_insts', instrument_options(), $params->arr_insts, 'id=arr_inst'
    );
    form_select('... or select an instrumentation',
        'arr_inst_combo_id', inst_combo_options(1000), $params->arr_inst_combo_id
    );
    form_input_text(
        '... or enter an <a href=search.php?type=inst_combo>instrumentation code</a>',
        'arr_inst_combo_code', $params->arr_inst_combo_code, '', 'id=arr_inst_combo_code'
    );
    form_checkboxes(
        'Additional instruments OK?', [['arr_others_ok', '', $params->arr_others_ok]],
        'id=arr_others_ok'
    );
    form_submit2('Search');
    form_general('',
        button_link(
            sprintf('edit.php?type=%d', COMPOSITION),
            'Add composition'
        )
    );
    form_end();
    echo "
<script>
var arr_check = document.getElementById('arr_check');
var arr_inst = document.getElementById('arr_inst');
var arr_inst_combo_id = document.getElementById('arr_inst_combo_id');
var arr_inst_combo_code = document.getElementById('arr_inst_combo_code');
var arr_others_ok = document.getElementById('arr_others_ok');
f = function() {
    arr_inst.disabled = !arr_check.checked;
    arr_inst_combo_id.disabled = !arr_check.checked;
    arr_inst_combo_code.disabled = !arr_check.checked;
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
    $params->inst_combo_id = get_int('inst_combo_id', true);
    $params->inst_combo_code = get_str('inst_combo_code', true);
    $params->others_ok = get_str('others_ok', true);
    $params->name = get_str('name', true);
    $params->sex = get_int('sex', true);
    $params->location = get_int('location', true);
    $params->arr = get_str('arr', true);
    $params->arr_insts = get_str('arr_insts', true);
    $params->arr_inst_combo_id = get_int('arr_inst_combo_id', true);
    $params->arr_inst_combo_code = get_str('arr_inst_combo_code', true);
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
    if ($params->inst_combo_id) $x .= "&inst_combo_id=$params->inst_combo_id";
    if ($params->inst_combo_code) $x .= "&inst_combo_code=$params->inst_combo_code";
    if ($params->others_ok) $x .= "&others_ok=$params->others_ok";
    if ($params->name) $x .= "&name=$params->name";
    if ($params->sex) $x .= "&sex=$params->sex";
    if ($params->location) $x .= "&location=$params->location";
    if ($params->arr) $x .= "&arr=$params->arr";
    if ($params->arr_insts) $x .= sprintf(
        '&arr_insts[]=%s', implode(',', $params->arr_insts)
    );
    if ($params->arr_inst_combo_id) $x .= "&arr_inst_combo_id=$params->arr_inst_combo_id";
    if ($params->arr_inst_combo_code) $x .= "&arr_inst_combo_code=$params->arr_inst_combo_code";
    if ($params->arr_others_ok) $x .= "&arr_others_ok=$params->arr_others_ok";
    return $x;
}

// return list of inst combos that contain the given instruments
// (and possibly others)
//
function get_combos_with_instruments($insts, $others_ok) {
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

// Return list of inst combos that extend the given one
//
function inst_combos_extend($ic_id) {
    $ics = get_inst_combos();
    $ic = $ics[$ic_id];
    $spec_ids = [];
    $spec_min = [];
    $spec_max = [];
    $ids = $ic->instruments_sorted->id;
    $counts = $ic->instruments_sorted->count;
    $n = count($ids);
    for ($i=0; $i<$n; $i++) {
        $spec_ids[] = $ids[$i];
        $spec_min[] = $counts[$i];
        $spec_max[] = 999;
    }
    return inst_combo_ids($spec_ids, $spec_min, $spec_max, true);
}


// convert list of ints to string like '[1, 5, 10]'
//
function make_int_list($list) {
    return sprintf('[%s]', implode(',', $list));
}

// is there an instrumentation constraint?
//
function have_combo_spec($insts, $inst_combo_id, $inst_combo_code) {
    return $insts || $inst_combo_id || $inst_combo_code;
}

// There is an instrumentation constraint.
// Get list of inst combo IDs based on form args
// Return [list, error_msg]
// Used for both composition and arrangement instrumentation
//
function form_get_combos($insts, $inst_combo_id, $inst_combo_code, $others_ok) {
    if ($insts) {
        if ($inst_combo_id || $inst_combo_code) {
            return[null,
                "Specify either instruments or instrumentation, but not both"
            ];
        }
        $inst_combos = get_combos_with_instruments($insts, $others_ok);
        if ($inst_combos) {
            return [$inst_combos, ''];
        }
        return [null, 'No instrumentations with those instruments'];
    }
    if ($inst_combo_id) {
        if ($inst_combo_code) {
            return[null,
                "Specify instrumentation name or code, but not both"
            ];
        }
    }
    if ($inst_combo_code) {
        $inst_combo_id = parse_code($inst_combo_code, 'inst_combo');
        if (!$inst_combo_id) {
            return [null, 'Invalid instrumentation code'];
        }
    }
    if ($others_ok) {
        $inst_combos = inst_combos_extend($inst_combo_id);
    } else {
        $inst_combos = [$inst_combo_id];
    }
    return [$inst_combos, ''];
}

function composition_search($params) {
    select2_head('Compositions');
    comp_form($params);

    // make a SQL query based on search params

    // get lists of inst combos for composition and/or arrangement

    $inst_combos = null;
    if (have_combo_spec(
        $params->insts, $params->inst_combo_id, $params->inst_combo_code)
    ) {
        [$inst_combos, $error_msg] = form_get_combos(
            $params->insts, $params->inst_combo_id,
            $params->inst_combo_code, $params->others_ok
        );
        if ($error_msg) {
            echo sprintf(
                '<div class="alert alert-danger"><strong>Composed for: %s.</strong></div>',
                $error_msg
            );
            return;
        } else if (!$inst_combos) {
            echo '<div class="alert alert-danger"><strong>No compositions for those instruments.</strong></div>';
            return;
        }
    }

    $arr_inst_combos = null;
    if (have_combo_spec(
        $params->arr_insts, $params->arr_inst_combo_id, $params->arr_inst_combo_code)
    ) {
        [$arr_inst_combos, $error_msg] = form_get_combos(
            $params->arr_insts, $params->arr_inst_combo_id,
            $params->arr_inst_combo_code, $params->arr_others_ok
        );
        if ($error_msg) {
            echo sprintf(
                '<div class="alert alert-danger"><strong>Arranged for: %s.</strong></div>',
                $error_msg
            );
            return;
        } else if (!$arr_inst_combos) {
            echo '<div class="alert alert-danger"><strong>No arrangements for those instruments.</strong></div>';
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
    if ($inst_combos) {
        $query .= sprintf(' and json_overlaps("%s", comp1.instrument_combos->\'$\')',
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

    // arrangements
    //
    if ($arr_inst_combos) {
        $query .= sprintf(' and json_overlaps("%s", comp2.instrument_combos->\'$\')',
            make_int_list($arr_inst_combos)
        );
    }
    $query .= 'order by comp1.long_title ';
    $query .= sprintf(' limit %d,%d', $params->offset, PAGE_SIZE+1);

    if (SHOW_COMP_QUERY) {
        echo "QUERY: $query\n";
    }
    $comps = DB::enum($query);

    if (!$comps) {
        echo "<h2>No compositions found</h2>
        Please modify your search and try again.";
        page_tail();
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

//////////////////// CONCERT ////////////////////

function concert_search() {
    page_head("Concerts");
    $cs = DB_concert::enum();
    start_table('table-striped');
    table_header('Details', 'Venue', 'Location', 'Date');
    foreach ($cs as $c) {
        $v = null;
        if ($c->venue) {
            $v = DB_venue::lookup_id($c->venue);
        }
        table_row(
            sprintf('<a href=item.php?type=%d&id=%d>View</a>',
                CONCERT, $c->id
            ),
            dash($v?$v->name:''),
            $v?location_id_to_name($v->location):dash(null),
            DB::date_num_to_str($c->_when)
        );
    }
    end_table();
    echo button_link(
        sprintf('edit.php?type=%d', CONCERT),
        'Add concert'
    );
    page_tail();
}

//////////////////// VENUE  ////////////////////

function venue_search() {
    page_head("Venues");
    $vs = DB_venue::enum();
    start_table('table-striped');
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
    echo button_link(
        sprintf('edit.php?type=%d', VENUE),
        'Add venue'
    );
    page_tail();
}

function organization_search() {
    page_head('Organizations');
    $orgs = DB_organization::enum('', 'order by name');
    start_table('table-striped');
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
    echo button_link(
        sprintf('edit.php?type=%d', ORGANIZATION),
        'Add organization'
    );
    page_tail();
}

function ensemble_search() {
    page_head('Ensembles');
    copy_to_clipboard_script();
    $enss = DB_ensemble::enum();
    start_table('table-striped');
    table_header('Name', 'Type', 'Location', 'Code');
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
            $loc?location_name($loc):dash(),
            copy_button(item_code($ens->id, 'ensemble'))
        );
    }
    end_table();
    echo button_link(
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
    form_submit2('Search');
    form_general('',
        button_link(
            sprintf('edit.php?type=%d', INST_COMBO),
            'Add instrumentation'
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

function inst_combo_search($params) {
    select2_head('Instrumentations');
    inst_combo_form($params);
    $combo_ids = get_combos_with_instruments($params->insts, true);
    if (!$combo_ids) {
        echo "<h2>No instrumentations found</h2>
        Please modify your search and try again.";
        page_tail();
        exit();
    }
    start_table('table-striped');
    copy_to_clipboard_script();
    table_header('Instrumentation', '# of compositions and arrangements', 'Code');
    foreach ($combo_ids as $id) {
        $ic = DB_instrument_combo::lookup_id($id);
        table_row(
            instrument_combo_str($ic),
            $ic->nworks,
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
        composition_search(comp_get());
        break;
    case 'concert':
        concert_search();
        break;
    case 'ensemble':
        ensemble_search();
        break;
    case 'instrument':
        instrument_search();
        break;
    case 'location':
        location_search();
        break;
    case 'organization':
        organization_search();
        break;
    case 'person':
        person_search(person_get());
        break;
    case 'venue':
        venue_search();
        break;
    case 'inst_combo':
        inst_combo_search(inst_combo_get());
        break;
    default:
        error_page("$type not implemented");
    }
}

main(get_str('type'));

?>
