<?php

// display individual items

require_once('../inc/util.inc');
require_once('cmi_db.inc');
require_once('cmi.inc');
require_once('ser.inc');
require_once('rate.inc');

function person_left($p) {
    start_table();
    row2('First name', $p->first_name);
    row2('Last name', $p->last_name);
    row2('Born', DB::date_num_to_str($p->born));
    row2('Birth place', location_id_to_name($p->birth_place));
    row2('Died', DB::date_num_to_str($p->died));
    row2('Death place', location_id_to_name($p->death_place));
    row2('Locations', locations_str($p->locations));
    row2('Sex', sex_id_to_name($p->sex));
    if (editor()) {
        row2('',
            button_text(
                sprintf('edit.php?type=%d&id=%d', PERSON, $p->id),
                'Edit info'
            )
        );
    }
    $prs = DB_person_role::enum("person=$p->id");
    if ($prs) {
        $x = [];
        foreach ($prs as $pr) {
            $s = sprintf('%s %s as %s',
                $p->first_name, $p->last_name,
                role_id_to_name($pr->role)
            );
            if ($pr->instrument) {
                $s .= sprintf(' (%s)', instrument_id_to_name($pr->instrument));
            }
            if (editor()) {
                $s .= ' '.copy_button(item_code($pr->id, 'person_role'));
            }
            $x[] = $s;
        }
    }
    if (editor()) {
        $x[] = '<hr>';
        $x[] = button_text(
            sprintf('edit.php?type=%d&person_id=%d', PERSON_ROLE, $p->id),
            'Add role'
        );
    }
    if ($x) {
        row2('Roles', implode('<p>', $x));
    }
    end_table();
}

function do_person($id) {
    $p = DB_person::lookup_id($id);
    if (!$p) error_page("no person $id\n");
    page_head("$p->first_name $p->last_name");
    copy_to_clipboard_script();
    grid(null, 'person_left', 'person_right', 7, $p);
    page_tail();
}

function do_composition($id) {
    $c = DB_composition::lookup_id($id);
    if (!$c) error_page("no composition $id\n");
    if ($c->arrangement_of) {
        $par = DB_composition::lookup_id($c->arrangement_of);
        $page_title = "Arrangment of $par->long_title";
    } else if ($c->parent) {
        $par = DB_composition::lookup_id($c->parent);
        $page_title = "Section of $par->long_title";
    } else {
        $par = null;
        $page_title = $c->long_title;
    }
    page_head($page_title);
    copy_to_clipboard_script();
    $arg = [$c, $par];
    grid(null, 'comp_left', 'comp_right', 7, $arg);
    page_tail();
}

function comp_left($arg) {
    [$c, $par] = $arg;
    start_table();
    if ($c->arrangement_of) {
        row2('Arrangement of',
            sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                COMPOSITION, $par->id, composition_str($par)
            )
        );
        row2('Instrumentation', instrument_combos_str($c->instrument_combos));
        if ($c->ensemble_type) {
            row2('Ensemble_type', ensemble_type_id_to_name($c->ensemble_type));
        }
    } else if ($c->parent) {
        row2('Section of',
            sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                COMPOSITION, $par->id, composition_str($par)
            )
        );
        row2('Title', $c->title);
        row2('Approximate duration', $c->average_duration);
    } else {
        row2('Title', $c->title);
        if ($c->alternative_title) {
            row2('Alternative title', $c->alternative_title);
        }
        row2('Opus', $c->opus_catalogue);
        row2('Composed', DB::date_num_to_str($c->composed));
        //row2('Published', DB::date_num_to_str($c->published));
        //row2('First performed', DB::date_num_to_str($c->performed));
        row2('Dedication', $c->dedication);
        row2('Composition types', comp_types_str($c->comp_types));
        row2('Creators', creators_str($c->creators, true));
        if ($c->languages) {
            row2('Languages', languages_str(json_decode($c->languages)));
        }
        row2('Instrumentation', instrument_combos_str($c->instrument_combos));
        if ($c->ensemble_type) {
            row2('Ensemble_type', ensemble_type_id_to_name($c->ensemble_type));
        }
        if ($c->period) {
            row2('Period', period_name($c->period));
        }
        row2('Approximate duration', $c->average_duration);
        row2('Number of movements', $c->n_movements);
    }
    if (editor()) {
        row2('Code', copy_button(item_code($c->id, 'composition')));
        row2('',
            button_text(
                sprintf('edit.php?type=%d&id=%d', COMPOSITION, $c->id),
                'Edit composition'
            )
        );
    }
    end_table();

    $arrs = DB_composition::enum(sprintf('arrangement_of=%d', $c->id));
    if ($arrs) {
        echo "<h3>Arrangements</h3>\n";
        start_table();
        table_header('Section', 'Arranged for', 'Creator', 'Code');
        foreach ($arrs as $arr) {
            $ics = instrument_combos_str($arr->instrument_combos);
            table_row(
                $arr->title?$arr->title:'Complete',
                sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                    COMPOSITION,
                    $arr->id,
                    $ics?$ics:'---'
                ),
                creators_str($arr->creators, true),
                copy_button(item_code($arr->id, 'composition'))
            );
        }
        end_table();
    }
    $children = DB_composition::enum(sprintf('parent=%d', $c->id));
    if ($children) {
        echo "<h3>Sections</h3>\n";
        start_table();
        table_header('Title', 'Metronome', 'Key', 'Measures', 'Code');
        foreach ($children as $child) {
            table_row(
                sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                    COMPOSITION, $child->id, $child->title
                ),
                $child->metronome_markings,
                $child->_keys,
                $child->nbars?$child->nbars:'',
                copy_button(item_code($child->id, 'composition'))
            );
        }
        end_table();
    }

    $scores = DB_score::enum(
        sprintf('json_overlaps("[%s]", compositions)', $c->id)
    );
    if ($scores) {
        echo "<h3>Scores</h3>\n";
        start_table();
        table_header('Description', 'File');
        foreach ($scores as $score) {
            $descs = json_decode($score->file_descs);
            $names = json_decode($score->file_names);
            $s = [];
            for ($i=0; $i<count($descs); $i++) {
                $s[] = sprintf('%s: %s', $descs[$i], $names[$i]);
            }
            row2('', implode('<br>', $s));
        }
        end_table();
    }
}

function do_location($id) {
    $loc = DB_location::lookup_id($id);
    if (!$loc) error_page("no location $id\n");
    page_head($loc->name);
    start_table();
    row2('Name', $loc->name);
    row2('Adjective', $loc->adjective);
    if ($loc->name_native) {
        row2('Name, native', $loc->name_native);
    }
    if ($loc->adjective_native) {
        row2('Adjective, native', $loc->adjective_native);
    }
    row2('Type', location_type_id_to_name($loc->type));
    if ($loc->parent) {
        row2('Parent',
            sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                LOCATION,
                $loc->parent,
                location_id_to_name($loc->parent)
            )
        );
    } else {
        row2('Parent', '---');
    }
    row2('',
        button_text(
            sprintf('edit.php?type=%d&id=%d', LOCATION, $loc->id),
            'Edit'
        )
    );
    end_table();
    page_tail();
}

function do_venue($id) {
    $v = DB_venue::lookup_id($id);
    if (!$v) error_page("No venue $id");
    page_head("Venue");
    start_table();
    row2('Name', $v->name);
    row2('Location', location_id_to_name($v->location));
    row2('Capacity', $v->capacity);
    if (editor()) {
        row2('',
            button_text(
                sprintf('edit.php?type=%d&id=%d', VENUE, $id),
                'Edit'
            )
        );
    }
    end_table();
    page_tail();
}

function do_concert($id) {
    $c = DB_concert::lookup_id($id);
    if (!$c) error_page("No concert $id");
    page_head("Concert");
    start_table();
    row2('When', DB::date_num_to_str($c->_when));
    row2('Venue', venue_str($c->venue));
    row2('Audience size', $c->audience_size?$c->audience_size:'---');
    row2('Organizer', organization_id_to_name($c->organization));
    row2('Program', program_str(json_decode($c->program)));
    if (editor()) {
        row2('',
            button_text(
                sprintf('edit.php?type=%d&id=%d', CONCERT, $id),
                'Edit'
            )
        );
    }
    end_table();
    page_tail();
}

function do_organization($id) {
    $org = DB_organization::lookup_id($id);
    page_head("Organization");
    start_table();
    row2('Name', $org->name);
    row2('Type', organization_type_str($org->type));
    row2('Location', location_id_to_name($org->location));
    row2('URL', sprintf('<a href=%s>%s</a>', $org->url, $org->url));
    if (editor()) {
        row2('',
            button_text(
                sprintf('edit.php?type=%d&id=%d', ORGANIZATION, $id),
                'Edit'
            )
        );
    }
    end_table();
    page_tail();
}

function main($type, $id) {
    switch ($type) {
    case PERSON:
        do_person($id);
        break;
    case COMPOSITION:
        do_composition($id);
        break;
    case LOCATION:
        do_location($id);
        break;
    case VENUE:
        do_venue($id);
        break;
    case CONCERT:
        do_concert($id);
        break;
    case ORGANIZATION:
        do_organization($id);
        break;
    default: error_page("No type $type");
    }
}

$type = get_str('type');
$id = get_int('id');

main($type, $id);

?>
