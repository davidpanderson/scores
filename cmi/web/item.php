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
    row2('Birth place', dash(location_id_to_name($p->birth_place)));
    row2('Died', DB::date_num_to_str($p->died));
    row2('Death place', dash(location_id_to_name($p->death_place)));
    row2('Locations', locations_str($p->locations));
    row2('Sex', sex_id_to_name($p->sex));
    if ($p->maker) {
        row2('Added by', user_link($p->maker));
    }
    row2('Race/Ethnicity', ethnicity_str(json_decode2($p->ethnicity)));
    if (can_edit($p)) {
        row2('',
            button_text(
                sprintf('edit.php?type=%d&id=%d', PERSON, $p->id),
                'Edit info'
            )
        );
    }
    $prs = DB_person_role::enum("person=$p->id");
    $x = [];
    if ($prs) {
        foreach ($prs as $pr) {
            $s = sprintf('%s %s as %s',
                $p->first_name, $p->last_name,
                role_id_to_name($pr->role)
            );
            if ($pr->instrument) {
                $s .= sprintf(' (%s)', instrument_id_to_name($pr->instrument));
            }
            $s .= sprintf(' &middot; <a href=item.php?type=%d&id=%d>View works</a>',
                PERSON_ROLE, $pr->id
            );
            $s .= ' &middot; '.copy_button(item_code($pr->id, 'person_role'));
            $x[] = "$s<p>";
        }
    }
    if (!$x) $x[] = dash();
    if (can_edit($p)) {
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

function person_item($id) {
    $p = DB_person::lookup_id($id);
    if (!$p) error_page("no person $id\n");
    page_head("$p->first_name $p->last_name");
    copy_to_clipboard_script();
    grid(null, 'person_left', 'person_right', 7, $p);
    page_tail();
}

function composition_item($id) {
    $c = DB_composition::lookup_id($id);
    $c->creators = json_decode2($c->creators);
    if (!$c) error_page("no composition $id\n");
    if ($c->arrangement_of) {
        $par = DB_composition::lookup_id($c->arrangement_of);
        $page_title = "Composition: Arrangement of $par->long_title";
    } else if ($c->parent) {
        $par = DB_composition::lookup_id($c->parent);
        $page_title = "Composition: $c->title from $par->title";
    } else {
        $par = null;
        $page_title = "Composition: $c->long_title";
    }
    page_head($page_title);
    copy_to_clipboard_script();
    $arg = [$c, $par];
    grid(null, 'comp_left', 'comp_right', 7, $arg);
    page_tail();
}

// the left panel in a composition page
//
function comp_left($arg) {
    [$c, $par] = $arg;
    start_table();
    $is_arrangement = $c->arrangement_of;
    $is_section = $c->parent;

    if ($is_arrangement) {
        row2('Arrangement of', composition_str($par));
        row2('Creators', dash(creators_str($c->creators, true)));
        row2('Instrumentation', instrument_combos_str($c->instrument_combos));
    } else if ($is_section) {
        row2('Section of', composition_str($par));
        row2('Title', $c->title);
        row2('Tempo markings', dash($c->tempo_markings));
        row2('Metronome markings', dash($c->metronome_markings));
        row2('Keys', dash($c->_keys));
        row2('Time signatures', dash($c->time_signatures));
        row2('Average duration (sec)', dash($c->avg_duration_sec));
        row2('Number of measures', dash($c->n_bars));
    } else {
        row2('Title', $c->title);
        if ($c->alternative_title) {
            row2('Alternative title', $c->alternative_title);
        }
        row2('Creators', dash(creators_str($c->creators, true)));
        row2('Opus', $c->opus_catalogue);
        row2('Instrumentation', dash(instrument_combos_str($c->instrument_combos)));
        row2('Number of movements', dash($c->n_movements));
        row2('Keys', dash($c->_keys));
        row2('Composed', dash(DB::date_num_to_str($c->composed)));
        row2('First published', dash(DB::date_num_to_str($c->published)));
        //row2('First performed', DB::date_num_to_str($c->performed));
        row2('Dedication', dash($c->dedication));
        row2('Tempo markings', dash($c->tempo_markings));
        row2('Metronome markings', dash($c->metronome_markings));
        row2('Time signatures', dash($c->time_signatures));
        row2('Average duration (sec)', dash($c->avg_duration_sec));
        row2('Number of measures', dash($c->n_bars));
        row2('Composition types', dash(comp_types_str($c->comp_types)));
        if ($c->language) {
            row2('Language', languages_str([$c->language]));
        }
        if ($c->period) {
            row2('Period', period_name($c->period));
        }
    }
    row2('Code', copy_button(item_code($c->id, 'composition')));
    if (can_edit($c)) {
        if ($is_section) $x = 'section';
        else if ($is_arrangement) $x = 'arrangement';
        else $x = 'composition';
        row2('',
            button_text(
                sprintf('edit.php?type=%d&id=%d', COMPOSITION, $c->id),
                "Edit $x"
            )
        );
    }
    end_table();

    if (!$is_section && !$is_arrangement) {
        echo "<h3>Sections</h3>\n";
        $children = DB_composition::enum(sprintf('parent=%d', $c->id));
        if ($children) {
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
                    dash($child->n_bars)
                );
            }
            end_table();
        } else {
            echo '<p>(No sections)<p>';
        }
        if (can_edit($c)) {
            show_button(
                sprintf(
                    'edit.php?type=%d&parent=%d',
                    COMPOSITION, $c->id
                ),
                'Add section'
            );
        }
    }

    if ($is_arrangement) {
        $arrs = [];
    } else {
        echo "<h3>Arrangements</h3>\n";
        $arrs = DB_composition::enum(sprintf('arrangement_of=%d', $c->id));
        if ($arrs) {
            start_table();
            table_header('Details', 'Section', 'Arranged for', 'Arranger');
            foreach ($arrs as $arr) {
                $arr->creators = json_decode2($arr->creators);
                $ics = instrument_combos_str($arr->instrument_combos);
                $arr->ics = $ics;
                $arr->arranger = creators_str($arr->creators, false);
                table_row(
                    sprintf('<a href=item.php?type=%d&id=%d>view</a>',
                        COMPOSITION, $arr->id
                    ),
                    $arr->title?$arr->title:'Complete',
                    dash($ics),
                    $arr->arranger
                );
            }
            end_table();
        } else {
            echo '<p>(No arrangements)<p>';
        }
        if (can_edit($c)) {
            show_button(
                sprintf(
                    'edit.php?type=%d&arrangement_of=%d',
                    COMPOSITION, $c->id
                ),
                'Add arrangement'
            );
        }
    }

    echo "<h3>Scores</h3>\n";
    $scores = DB_score::enum(
        sprintf('json_overlaps("[%s]", compositions)', $c->id)
    );
    if ($scores || $arrs) {
        start_table();
        table_header('Details', 'Section', 'Type', 'Publisher', 'Date', 'File');
        foreach ($scores as $score) {
            score_row($score);
        }
        foreach ($arrs as $arr) {
            $scores = DB_score::enum(
                sprintf('json_overlaps("[%s]", compositions)', $arr->id)
            );
            foreach ($scores as $score) {
                if ($arr->ics) {
                    $s = "Arrangement for $arr->ics";
                } else {
                    $s = "Arrangement";
                }
                if ($arr->arranger) {
                    $s .= "<br><nobr>by $arr->arranger</nobr></br>";
                }
                score_row($score, $s);
            }
        }
        end_table();
    } else {
        echo '<p>(No scores)<p>';
    }
    if (can_edit($c)) {
        show_button(
            sprintf('edit.php?type=%d&comp_id=%d', SCORE, $c->id),
            'Add score'
        );
    }

    echo "<h3>Recordings/Performances</h3>\n";
    $perfs = DB_performance::enum("composition=$c->id");
    if ($perfs) {
        // performances may be recordings (with files)
        // or concert performances, or both
        // See which of these we have to decide what columns to show
        //
        $have_type = false;
        $have_section = false;
        $have_ensemble = false;
        $have_performers = false;
        $have_instrumentation = false;
        $have_concert = false;
        $have_files = false;
        foreach ($perfs as $perf) {
            if ($perf->is_synthesized) $have_type = true;
            if ($perf->section) $have_section = true;
            if ($perf->ensemble) $have_ensemble = true;
            $perf->performers = json_decode2($perf->performers);
            if ($perf->performers) $have_performers = true;
            if ($perf->instrumentation) $have_instrumentation = true;
            if ($perf->concert) $have_concert = true;
            $perf->files = json_decode2($perf->files);
            if ($perf->files) $have_files = true;
        }
        $x = ['Details'];
        if ($have_type) $x[] = 'Type';
        if ($have_section) $x[] = 'Section';
        if ($have_ensemble) $x[] = 'Ensemble';
        if ($have_performers) $x[] = 'Performers';
        if ($have_instrumentation) $x[] = 'Arranged for';
        if ($have_concert) $x[] = 'Concert';
        if ($have_files) $x[] = 'Files';
        start_table();
        row_heading_array($x);
        foreach ($perfs as $perf) {
            $x = [
                sprintf('<a href=item.php?type=%d&id=%d>view</a>',
                    PERFORMANCE, $perf->id
                ),
            ];
            if ($have_section) {
                $x[] = $perf->is_synthesized?'Synthesized':'';
            }
            if ($have_section) {
                $x[] = dash($perf->section);
            }
            if ($have_ensemble) {
                $x[] = dash(ensemble_str($perf->ensemble, true));
            }
            if ($have_performers) {
                $x[] = creators_str($perf->performers, true);
            }
            if ($have_instrumentation) {
                $x[] = $perf->instrumentation;
            }
            if ($have_concert) {
                $y = '';
                if ($perf->concert) {
                    $con = DB_concert::lookup_id($perf->concert);
                    $y = concert_str($con);
                }
                $x[] = $y;
            }
            if ($have_files) {
                $f = [];
                foreach ($perf->files as $file) {
                    $f[] = sprintf('%s &middot; <a href=%s>file</a>',
                        $file->desc, $file->name
                    );
                }
                $x[] = implode('<br>', $f);
            }
            row_array($x);
        }
        end_table();
    } else {
        echo '<p>(No recordings)<p>';
    }
    if (can_edit($c)) {
        show_button(
            sprintf('edit.php?type=%d&composition=%d', PERFORMANCE, $c->id),
            'Add recording'
        );
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
    if ($prefix && $type) $prefix .= '; ';
    $files = json_decode($score->files);
    $s = [];
    foreach ($files as $file) {
        $s[] = sprintf('%s &middot; <a href=%s>view</a>',
            $file->desc, $file->name
        );
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
        $score->section?$score->section:'Complete',
        dash($prefix.implode(',', $type)),
        dash($pub_str),
        dash($pub_year),
        implode('<br>', $s)
    );
}

function location_item($id) {
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

function venue_item($id) {
    $v = DB_venue::lookup_id($id);
    if (!$v) error_page("No venue $id");
    page_head("Venue");
    start_table();
    row2('Name', $v->name);
    row2('Location', location_id_to_name($v->location));
    row2('Capacity', $v->capacity);
    $concerts = DB_concert::enum("venue=$id");
    if ($concerts) {
        $x = [];
        foreach ($concerts as $c) {
            $x[] = sprintf(
                '<a href=item.php?type=%d&id=%d>%s</a>',
                CONCERT, $c->id, DB::date_num_to_str($c->_when)
            );
        }
        row2('Concerts', implode('<br>', $x));
    }
    if (can_edit($v)) {
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

function concert_item($id) {
    $c = DB_concert::lookup_id($id);
    if (!$c) error_page("No concert $id");
    page_head("Concert");
    start_table();
    row2('When', DB::date_num_to_str($c->_when));
    row2('Venue', venue_str($c->venue));
    row2('Audience size', dash($c->audience_size));
    row2('Organizer', organization_id_to_name($c->organization));
    row2('Program', program_str(json_decode($c->program)));
    if (can_edit($c)) {
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

function organization_item($id) {
    $org = DB_organization::lookup_id($id);
    page_head("Organization");
    copy_to_clipboard_script();
    start_table();
    row2('Name', $org->name);
    $type_str = organization_type_str($org->type);
    row2('Type', $type_str);
    //row2('Location', location_id_to_name($org->location));
    row2('Location', $org->location);
    row2('URL', sprintf('<a href=%s>%s</a>', $org->url, $org->url));
    if (can_edit($org)) {
        row2('Code', copy_button(item_code($id, 'organization')));
        row2('',
            button_text(
                sprintf('edit.php?type=%d&id=%d', ORGANIZATION, $id),
                'Edit'
            )
        );
    }
    end_table();
    if ($type_str == 'Music publisher') {
        $scores = DB_score::enum("publisher=$id");
        if ($scores) {
            echo '<h3>Scores</h3>';
            start_table();
            table_header('Composition');
            foreach ($scores as $score) {
                table_row(
                    score_str($score, true)
                );
            }
            end_table();
        }
    }
    page_tail();
}

function performance_item($id) {
    $perf = DB_performance::lookup_id($id);
    $perf->performers = json_decode2($perf->performers);
    page_head("Performance");
    copy_to_clipboard_script();
    grid(null, 'perf_left', 'perf_right', 7, $perf);
    page_tail();
}

function perf_left($perf) {
    start_table();
    $comp = DB_composition::lookup_id($perf->composition);
    row2('Composition', composition_str($comp));
    if ($perf->ensemble) {
        row2('Ensemble', ensemble_str($perf->ensemble, true));
    }
    row2('Performers', creators_str($perf->performers, true));
    row2('Synthesized?', $perf->is_synthesized?'Yes':'No');
    row2('Section', dash($perf->section));
    row2('Instrumentation', dash($perf->instrumentation));
    if ($perf->concert) {
        $con = DB_concert::lookup_id($perf->concert);
        row2('Concert', concert_str($con));
    }
    $lic_str = '';
    if ($perf->license) {
        $lic = DB_license::lookup_id($perf->license);
        $lic_str = $lic->name;
    }
    row2('License', dash($lic_str));
    end_table();

    echo '<h3>Files</h3>';
    $files = json_decode2($perf->files);
    start_table();
    table_header('Description', 'Name');
    foreach ($files as $file) {
        table_row(
            sprintf('<nobr>%s</nobr>', $file->desc),
            $file->name
        );
    }
    end_table();

    if (can_edit($perf)) {
        show_button(
            sprintf('edit.php?type=%d&id=%d', PERFORMANCE, $perf->id),
            'Edit recording'
        );
    }
}

function score_item($id) {
    $score = DB_score::lookup_id($id);
    $score->creators = json_decode2($score->creators);
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
        $comp_str[] = composition_str($comp);
    }
    row2('Composition', implode('<br>', $comp_str));
    $pub_str = '';
    if ($score->publisher) {
        $pub = DB_organization::lookup_id($score->publisher);
        $pub_str = $pub->name;
    }
    row2('Contributors', dash(creators_str($score->creators, true)));
    row2('Publisher', dash($pub_str));
    $x = '';
    if ($score->languages) {
        $x = languages_str(json_decode($score->languages));
    }
    row2('Languages', dash($x));

    $lic_str = '';
    if ($score->license) {
        $lic = DB_license::lookup_id($score->license);
        $lic_str = $lic->name;
    }
    row2('License', dash($lic_str));
    row2('Published', dash(DB::date_num_to_str($score->publish_date)));
    row2('Edition', dash($score->edition_number));
    row2('Parts?', $score->is_parts?'Yes':'No');
    row2('Selections?', $score->is_selections?'Yes':'No');
    row2('Vocal score?', $score->is_vocal?'Yes':'No');
    end_table();

    echo '<h3>Files</h3>';
    $files = json_decode($score->files);
    start_table();
    table_header('Description', 'Name', 'Pages');
    foreach ($files as $file) {
        table_row(
            $file->desc,
            $file->name,
            $file->pages?$file->pages:dash('')
        );
    }
    end_table();

    if (can_edit($score)) {
        show_button(
            sprintf('edit.php?type=%d&id=%d', SCORE, $score->id),
            'Edit score'
        );
    }
}

function person_role_item($id) {
    $pr = DB_person_role::lookup_id($id);
    $person = DB_person::lookup_id($pr->person);
    $role = role_id_to_name($pr->role);
    $inst = '';
    if ($pr->instrument) {
        $inst = DB_instrument::lookup_id($pr->instrument);
        $inst = " ($inst->name)";
    }
    page_head("Works by $person->first_name $person->last_name as $role $inst");
    switch ($role) {
    case 'performer':
        echo '<h3>Performances</h3>';
        start_table();
        table_header('Details', 'Composition');
        $q = sprintf("json_contains(performers, '%d', '$')", $id);
        $perfs = DB_performance::enum($q);
        foreach ($perfs as $perf) {
            $comp = DB_composition::lookup_id($perf->composition);
            table_row(
                sprintf('<a href=item.php?type=%d&id=%d>View</a>',
                    PERFORMANCE, $perf->id
                ),
                composition_str($comp)
            );
        }
        end_table();
        break;
    case 'conductor':
        echo '<h3>Performances</h3>';
        start_table();
        table_header('Composition', 'Ensemble');
        $q = sprintf("json_contains(performers, '%d', '$')", $id);
        $perfs = DB_performance::enum($q);
        foreach ($perfs as $perf) {
            $comp = DB_composition::lookup_id($perf->composition);
            table_row(
                composition_str($comp),
                ensemble_str($perf->ensemble, true)
            );
        }
        end_table();
        break;
    case 'arranger':
        $q = sprintf("json_contains(creators, '%d', '$')", $id);
        $comps = DB_composition::enum($q);
        show_arrangements($comps);
        break;
    case 'composer':
    case 'librettist':
    case 'lyricist':
        $q = sprintf("json_contains(creators, '%d', '$')", $id);
        $comps = DB_composition::enum($q);
        show_compositions($comps);
        break;
    case 'editor':
    case 'translator':
        echo '<h3>Scores</h3>';
        start_table();
        table_header('Composition', 'Attributes');
        $q = sprintf("json_contains(creators, '%d', '$')", $id);
        $scores = DB_score::enum($q);
        foreach ($scores as $score) {
            table_row(
                score_str($score),
                score_attrs_str($score)
            );
        }
        end_table();
        break;
    }
    page_tail();
}

function ensemble_item($id) {
    $ens = DB_ensemble::lookup_id($id);
    page_head("Ensemble: $ens->name");
    copy_to_clipboard_script();
    start_table();
    row2('Alternate names', $ens->alternate_names);
    row2('Type', ensemble_type_id_to_name($ens->type));
    row2('Location', location_id_to_name($ens->location));
    row2('Started', DB::date_num_to_str($ens->started));
    row2('Ended', DB::date_num_to_str($ens->ended));
    row2('Code', copy_button($ens->id, 'ensemble'));
    end_table();

    if (can_edit($ens)) {
        show_button(
            sprintf('edit.php?type=%d&id=%d', ENSEMBLE, $ens->id),
            'Edit ensemble'
        );
    }

    echo '<h3>Recordings</h3>';
    $perfs = DB_performance::enum("ensemble=$id");
    start_table();
    table_header('Composition', 'Performers');
    foreach ($perfs as $perf) {
        $perf->performers = json_decode2($perf->performers);
        $comp = DB_composition::lookup_id($perf->composition);
        table_row(
            composition_str($comp),
            creators_str($perf->performers, true)
        );
    }
    end_table();
    page_tail();
}

function main($type, $id) {
    switch ($type) {
    case PERSON:
        person_item($id);
        break;
    case COMPOSITION:
        composition_item($id);
        break;
    case LOCATION:
        location_item($id);
        break;
    case VENUE:
        venue_item($id);
        break;
    case CONCERT:
        concert_item($id);
        break;
    case ORGANIZATION:
        organization_item($id);
        break;
    case PERFORMANCE:
        performance_item($id);
        break;
    case SCORE:
        score_item($id);
        break;
    case PERSON_ROLE:
        person_role_item($id);
        break;
    case ENSEMBLE:
        ensemble_item($id);
        break;
    default: error_page("No type $type");
    }
}

$type = get_str('type');
$id = get_int('id');

main($type, $id);

?>
