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
        $page_title = "$c->title from $par->title";
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
        row2('Approximate duration', dash($c->average_duration));
    } else {
        row2('Title', $c->title);
        if ($c->alternative_title) {
            row2('Alternative title', $c->alternative_title);
        }
        row2('Opus', $c->opus_catalogue);
        row2('Composed', DB::date_num_to_str($c->composed));
        //row2('Published', DB::date_num_to_str($c->published));
        //row2('First performed', DB::date_num_to_str($c->performed));
        row2('Dedication', dash($c->dedication));
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
        table_header('Details', 'Section', 'Arranged for', 'Arranger');
        foreach ($arrs as $arr) {
            $ics = instrument_combos_str($arr->instrument_combos);
            $arr->ics = $ics;
            $arr->arranger = creators_str($arr->creators, false);
            table_row(
                sprintf('<a href=item.php?type=%d&id=%d>view</a>',
                    COMPOSITION, $arr->id
                ),
                $arr->title?$arr->title:'Complete',
                sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                    COMPOSITION,
                    $arr->id,
                    $ics?$ics:'---'
                ),
                $arr->arranger
            );
        }
        end_table();
    }
    $children = DB_composition::enum(sprintf('parent=%d', $c->id));
    if ($children) {
        echo "<h3>Sections</h3>\n";
        start_table();
        table_header('Title<br><small>click for details</small>',
            'Metronome', 'Key', 'Measures'
        );
        foreach ($children as $child) {
            table_row(
                sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                    COMPOSITION, $child->id, $child->title
                ),
                dash($child->metronome_markings),
                dash($child->_keys),
                dash($child->nbars)
            );
        }
        end_table();
    }

    $scores = DB_score::enum(
        sprintf('json_overlaps("[%s]", compositions)', $c->id)
    );
    if ($scores || $arrs) {
        echo "<h3>Scores</h3>\n";
        start_table();
        table_header('Details', 'Type', 'Publisher', 'Date', 'File');
        foreach ($scores as $score) {
            score_row($score);
        }
        foreach ($arrs as $arr) {
            $scores = DB_score::enum(
                sprintf('json_overlaps("[%s]", compositions)', $arr->id)
            );
            foreach ($scores as $score) {
                if ($arr->ics) {
                    $s = "<nobr>Arrangement for $arr->ics</nobr>";
                } else {
                    $s = "Arrangement";
                }
                $s .= "<br><nobr>by $arr->arranger</nobr></br>";
                score_row($score, $s);
            }
        }
        end_table();
    }

    $perfs = DB_performance::enum("composition=$c->id");
    if ($perfs) {
        echo "<h3>Recordings</h3>\n";
        start_table();
        table_header('Details', 'Type', 'Section', 'Instruments', 'File');
        foreach ($perfs as $perf) {
            $descs = json_decode($perf->file_descs);
            $names = json_decode($perf->file_names);
            $f = [];
            for ($i=0; $i<count($descs); $i++) {
                $f[] = sprintf('%s <a href=%s>file</a>', $descs[$i], $names[$i]);
            }
            table_row(
                sprintf('<a href=item.php?type=%d&id=%d>view</a>',
                    PERFORMANCE, $perf->id
                ),
                $perf->is_synthesized?'Synthesized':'',
                dash($perf->section),
                $perf->instrumentation,
                implode('<br>', $f)
            );
        }
        end_table();
    }
}

function score_row($score, $prefix='') {
    $pub = null;
    if ($score->publisher) {
        $pub = DB_organization::lookup_id($score->publisher);
    }
    $type = [];
    if ($score->is_parts) $type[] = 'parts';
    if ($score->is_selections) $type[] = 'selections';
    if ($score->is_vocal) $type[] = 'vocal score';
    $descs = json_decode($score->file_descs);
    $names = json_decode($score->file_names);
    $s = [];
    for ($i=0; $i<count($descs); $i++) {
        //if ($score->is_parts) {
        if (1) {
            $s[] = sprintf('%s &middot; <a href=%s>view</a>', $descs[$i], $names[$i]);
        } else {
            $s[] = sprintf('<a href=%s>view</a>', $names[$i]);
        }
    }
    $pub_str = '';
    $pub_year = '';
    if ($pub) {
        $pub_str = $pub->name;
        if ($score->publish_date) {
            $pub_year = DB::date_num_to_str($score->publish_date);
        }
    }
    table_row(
        sprintf('<a href=item.php?type=%d&id=%d>view</a>', SCORE, $score->id),
        dash($prefix.implode(',', $type)),
        dash($pub_str),
        dash($pub_year),
        implode('<br>', $s)
    );
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

function do_performance($id) {
    $perf = DB_performance::lookup_id($id);
    page_head("Performance");
    copy_to_clipboard_script();
    grid(null, 'perf_left', 'perf_right', 7, $perf);
    page_tail();
}

function perf_left($perf) {
    start_table();
    $comp = DB_composition::lookup_id($perf->composition);
    row2('Composition', $comp->long_title);
    row2('Performers', creators_str($perf->performers, true));
    row2('Recording?', $perf->is_recording?'Yes':'No');
    row2('Synthesized?', $perf->is_synthesized?'Yes':'No');
    row2('Section', dash($perf->section));
    row2('Instrumentation', dash($perf->instrumentation));
    end_table();

    echo '<h3>Files</h3>';
    $names = json_decode($perf->file_names);
    $descs = json_decode($perf->file_descs);
    start_table();
    table_header('Description', 'Name');
    for ($i=0; $i<count($names); $i++) {
        table_row(
            sprintf('<nobr>%s</nobr>', $descs[$i]),
            $names[$i]
        );
    }
    end_table();
}

function do_score($id) {
    $score = DB_score::lookup_id($id);
    page_head("Score");
    grid(null, 'score_left', 'score_right', 7, $score);
    page_tail();
}

function score_left($score) {
    start_table();
    $comp_str = [];
    $comp_ids = json_decode($score->compositions);
    foreach ($comp_ids as $id) {
        $comp = DB_composition::lookup_id($id);
        $comp_str[] = $comp->long_title;
    }
    row2('Composition', implode('<br>', $comp_str));
    $pub_str = '';
    if ($score->publisher) {
        $pub = DB_organization::lookup_id($score->publisher);
        $pub_str = $pub->name;
    }
    row2('Publisher', dash($pub_str));
    $x = '';
    if ($score->languages) {
        languages_str(json_decode($score->languages));
    }
    row2('Languages', dash($x));

    $lic_str = '';
    if ($score->license) {
        $lic = DB_license::lookup_id($score->license);
        $lic_str = $lic->name;
    }
    row2('License', dash($lic_str));
    row2('Published', DB::date_num_to_str($score->publish_date));
    row2('Edition', dash($score->edition_number));
    row2('Parts?', $score->is_parts?'Yes':'No');
    row2('Selections?', $score->is_selections?'Yes':'No');
    row2('Vocal score?', $score->is_vocal?'Yes':'No');
    end_table();

    echo '<h3>Files</h3>';
    $names = json_decode($score->file_names);
    $descs = json_decode($score->file_descs);
    $page_counts = null;
    if ($score->page_counts) {
        $page_counts = json_decode($score->page_counts);
    }
    start_table();
    table_header('Description', 'Names', 'Pages');
    for ($i=0; $i<count($names); $i++) {
        table_row(
            $descs[$i],
            $names[$i],
            $page_counts?$page_counts[$i]:dash('')
        );
    }
    end_table();
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
    case PERFORMANCE:
        do_performance($id);
        break;
    case SCORE:
        do_score($id);
        break;
    default: error_page("No type $type");
    }
}

$type = get_str('type');
$id = get_int('id');

main($type, $id);

?>
