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

function do_composition($id) {
    $c = DB_composition::lookup_id($id);
    if ($c->arrangement_of) {
        $par = DB_composition::lookup_id($c->arrangement_of);
        $page_title = "Arrangment of $par->long_title";
    } else {
        $page_title = $c->long_title;
    }
    page_head($page_title);
    start_table();
    if ($c->arrangement_of) {
        row2('Arrangement of',
            sprintf('<a href=item.php?type=composition&id=%d>%s</a>',
                $par->id, $par->long_title
            )
        );
    } else {
        row2('Title', $c->title);
    }
    if ($c->parent) {
        $par = DB_composition::lookup_id($c->parent);
        row2('Sub-composition of', $par->long_title);
    }
    if ($c->alternative_title) {
        row2('Alternative title', $c->alternative_title);
    }
    row2('Opus', $c->opus_catalogue);
    row2('Composed', $c->composed);
    row2('Published', $c->published);
    row2('Performed', $c->performed);
    row2('Dedication', $c->dedication);
    row2('Types', comp_types_str($c->comp_types));
    row2('Creators', creators_str($c->creators, true));
    if ($c->languages) {
        row2('Languages', languages_str($c->languages));
    }
    row2('Instrumentation', instrument_combos_str($c->instrument_combos));
    if ($c->ensemble_type) {
        row2('Ensemble_type', ensemble_type_id_to_name($c->ensemble_type));
    }
    if ($c->period) {
        row2('Period', period_name($c->period));
    }
    row2('Average duration', $c->average_duration);
    row2('Number of movements', $c->n_movements);
    end_table();

    $arrs = DB_composition::enum(sprintf('arrangement_of=%d', $id));
    if ($arrs) {
        echo "<h3>Arrangements</h3>\n";
        start_table();
        table_header('Instrumentation', 'Arranger');
        foreach ($arrs as $arr) {
            table_row(
                sprintf('<a href=item.php?type=composition&id=%d>%s</a>',
                    $arr->id,
                    instrument_combos_str($arr->instrument_combos)
                ),
                creators_str($arr->creators, true)
            );
        }
        end_table();
    }
    $children = DB_composition::lookup(sprintf('parent=%d', $id));
    if ($children) {
        echo "<h3>Sub-compositions</h3>\n";
    }
    page_tail();
}

function main($type, $id) {
    switch ($type) {
    case 'person':
        do_person($id);
        break;
    case 'composition':
        do_composition($id);
        break;
    }
}

$type = get_str('type');
$id = get_int('id');

main($type, $id);

?>
