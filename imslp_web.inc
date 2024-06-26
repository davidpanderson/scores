<?php

// IMSLP-specific web utility functions

require_once("imslp_db.inc");
require_once("imslp_util.inc");
require_once("ser.inc");
require_once("audio.inc");
require_once("template.inc");

//////////////// DISPLAY UTILITIES: strings /////////////////

// hierarchy terms (Complete etc.) as a/b/c
//
function hier_string($fs) {
    $x = [];
    if ($fs->hier1) $x[]=$fs->hier1;
    if ($fs->hier2) $x[]=$fs->hier2;
    if ($fs->hier3) $x[]=$fs->hier3;
    return implode(' &middot; ', $x);
}

// birth info using available info
//
function person_birth_string($person) {
    if ($person->born_year) {
        if ($person->born_month) {
            if ($person->born_day) {
                return sprintf('%d/%d/%d',
                    $person->born_day, $person->born_month, $person->born_year
                );
            } else {
                return sprintf('%d/%d',
                    $person->born_month, $person->born_year
                );
            }
        } else {
            return "$person->born_year";
        }
    }
    return $person->birth_date;
}

function person_death_string($person) {
    if ($person->died_year) {
        if ($person->died_month) {
            if ($person->died_day) {
                return sprintf('%d/%d/%d',
                    $person->died_day, $person->died_month, $person->died_year
                );
            } else {
                return sprintf('%d/%d',
                    $person->died_month, $person->died_year
                );
            }
        } else {
            return "$person->died_year";
        }
    }
    return $person->death_date;
}

// nationality(ies) of a person as string
//
function person_nationality_string($person) {
    if (!$person->nationality_ids) return '';
    $x = [];
    $nat_ids = json_decode($person->nationality_ids);
    foreach ($nat_ids as $id) {
        $x[] = nationality_name($id);
    }
    return implode(', ', $x);
}

// period(s) of a person as string
//
function person_period_string($person) {
    if (!$person->period_ids) return '';
    $x = [];
    $per_ids = json_decode($person->period_ids);
    foreach ($per_ids as $id) {
        $x[] = period_name($id);
    }
    return implode(', ', $x);
}

// $combo is a list of [count, code] or [count, id]
// return e.g. "piano, 2 violins"
//
function inst_combo_str($combo, $is_id) {
    $x = [];
    foreach ($combo as $inst) {
        $count = $inst[0];
        $inst = $is_id ? inst_by_id($inst[1]) : inst_by_code($inst[1]);
        $name = $inst->name;
        if ($count == 1) {
            $x[] = "$name";
        } else {
            $plu = substr($name, -1, 1) == 's'?'':'s';
            $x[] = "$count $name$plu";
        }
    }
    return implode(', ', $x);
}

// convert instr combo to string
// $combo is DB format, i.e. struct x->count, x->id
//
function inst_combo_str_struct($combo) {
    $x = [];
    for ($i=0; $i<count($combo->count); $i++) {
        $x[] = [$combo->count[$i], $combo->id[$i]];
    }
    return inst_combo_str($x, true);
}

// given a list of instr combo IDs,
// return a list of strings
//
function inst_combo_ids_to_strs($ids) {
    $strs = [];
    $inst_combos = get_inst_combos();
    foreach ($ids as $id) {
        $combo = $inst_combos[$id];
        $insts = json_decode($combo->instruments);
        $strs[] = inst_combo_str_struct($insts);
    }
    return $strs;
}

// similar, but return a single string with <br>
//
function inst_combo_ids_to_str($ids) {
    return implode('<br>', inst_combo_ids_to_strs($ids));
}

// URL of an IMSLP score or audio file, given its name
//
function imslp_file_url($fname) {
    if (0) {
        return "https://imslp.org/wiki/File:$fname";
    } else {
        $m = md5($fname);
        return sprintf("https://imslp.org/images/%s/%s%s/%s",
            $m[0], $m[0], $m[1], $fname
        );
    }
}

//////////////// DISPLAY UTILITIES: table /////////////////

function person_table_heading() {
    row_heading_array([
        'Name', 'Composer', 'Performer', 'Born', 'Died', 'Sex', 'Nationality', 'Period'
    ]);
}

function person_table_row($p) {
    row_array([
        sprintf("<p><a href=person.php?id=%d>%s, %s</a>\n",
            $p->id, $p->last_name, $p->first_name
        ),
        $p->is_composer?'yes':'',
        $p->is_performer?'yes':'',
        person_birth_string($p),
        person_death_string($p),
        $p->sex,
        person_nationality_string($p),
        person_period_string($p)
    ]);
}

function show_person_detail($p) {
    start_table();
    row2('First name', $p->first_name);
    row2('Last name', $p->last_name);
    if ($p->alternate_names) {
        row2('Alternate names', str_replace(';', '<br>', $p->alternate_names));
    }
    $x = person_birth_string($p);
    if ($x) {
        row2('Born', $x);
    }
    if ($p->birth_place) {
        row2('Birth place', $p->birth_place);
    }
    $x = person_death_string($p);
    if ($x) {
        row2('Died', $x);
    }
    if ($p->death_place) {
        row2('Death place', $p->death_place);
    }
    if ($p->flourished) {
        row2('Flourished', $p->flourished);
    }
    if ($p->nationality_ids) {
        row2('Nationality', person_nationality_string($p));
    }
    if ($p->period_ids) {
        row2('Period', person_period_string($p));
    }
    if ($p->picture) {
        row2('Picture',
            sprintf('<a href="https://imslp.org/wiki/File:%s">%s</a>',
                $p->picture, $p->picture
            )
        );
    }
    if ($p->picture_caption) {
        row2('Picture caption', mw_string($p->picture_caption));
    }
    if ($p->sex) {
        row2('Sex', $p->sex);
    }
    if ($p->signature) {
        row2('Signature',
            sprintf('<a href="https://imslp.org/wiki/File:%s">%s</a>',
                $p->signature, $p->signature
            )
        );
    }
    end_table();
}

function show_work_detail($c) {
    start_table('');
    $composer = DB_person::lookup_id($c->composer_id);
    $name = "$composer->first_name $composer->last_name";
    row2('Composer', "<a href=person.php?id=$composer->id>$name</a>");
    if ($c->opus_catalogue) {
        row2('Opus', mw_string($c->opus_catalogue));
    }
    if ($c->comments) {
        row2('Comments', mw_string($c->comments));
    }
    if ($c->dedication) {
        row2('Dedication', mw_string($c->dedication));
    }
    if ($c->external_links) {
        row2('External links', mw_string($c->external_links));
    }
    if ($c->first_performance) {
        row2('First performance', $c->first_performance);
    }
    if ($c->_key) {
        row2('Key', mw_string($c->_key));
    }
    if ($c->movements_header) {
        row2('Movements', $c->movements_header);
    }
    if ($c->tags) {
        row2('Tags', $c->tags);
    }
    if ($c->year_date_of_composition) {
        row2('Composition date', mw_string($c->year_date_of_composition));
    }
    if ($c->year_of_first_publication) {
        row2('Publication date', mw_string($c->year_of_first_publication));
    }
    if ($c->instrumentation) {
        row2('Instrumentation', mw_string($c->instrumentation));
    }
    if ($c->instrument_combo_ids) {
        $ids = json_decode($c->instrument_combo_ids);
        row2(
            'Instrumentation from tags',
            inst_combo_ids_to_str($ids)
        );
    }
    if ($c->work_type_ids) {
        $wts = json_decode($c->work_type_ids);
        $x = [];
        foreach ($wts as $id) {
            $wt = DB_work_type::lookup_id($id);
            $x[] = $wt->name;
        }
        row2('Work type', implode('<br>', $x));
    }
    end_table();
}

function show_ensemble_detail($e) {
    start_table();
    row2('Name', $e->name);
    if ($e->alternate_names) {
        row2('Alternate names', $e->alternate_names);
    }
    if ($e->born_year) {
        row2('Start year', $e->born_year);
    }
    if ($e->died_year) {
        row2('End year', $e->died_year);
    }
    if ($e->nationality_id) {
        row2('Nationality', nationality_name($e->nationality_id));
    }
    if ($e->period_id) {
        row2('Period', period_name($e->period_id));
    }
    if ($e->picture) {
        row2('Picture', $e->picture);
    }
    if ($e->type) {
        row2('Type', $e->type);
    }
    end_table();
}

// show both original and expanded
function mw_string($str) {
    $x = expand_mw_text($str, null, 0, true);
    if ($x != $str) {
        return "$str<p>expanded:<p>$x";
    }
    return $str;
}

function score_file_set_detail($fs) {
    start_table('');
    if ($fs->amazon) row2("Amazon", $fs->amazon);
    if ($fs->arranger) row2("Arranger", mw_string($fs->arranger));
    if ($fs->copyright_id) row2("Copyright", copyright_id_to_name($fs->copyright_id));
    if ($fs->date_submitted) row2("Date Submitted", $fs->date_submitted);
    if ($fs->editor) row2("Editor", mw_string($fs->editor));
    if ($fs->engraver) row2("Engraver", $fs->engraver);
    if ($fs->file_tags) row2("File Tags", $fs->file_tags);
    $hs = hier_string($fs);
    if ($hs) row2("Categories", $hs);
    if ($fs->image_type) row2("Image Type", $fs->image_type);
    if ($fs->instrument_combo_ids) {
        $ids = json_decode($fs->instrument_combo_ids);
        row2("Instrumentation", inst_combo_ids_to_str($ids));
    }
    if ($fs->misc_notes) row2("Misc. Notes", mw_string($fs->misc_notes));
    if ($fs->publisher_information) row2(
        "Publisher Information", mw_string($fs->publisher_information)
    );
    if ($fs->reprint) row2("Reprint", $fs->reprint);
    if ($fs->sample_filename) row2("Sample Filename", $fs->sample_filename);
    if ($fs->scanner) row2("Scanner", mw_string($fs->scanner));
    if ($fs->sm_plus) row2("SM+", $fs->sm_plus);
    if ($fs->thumb_filename) row2("Thumb Filename", $fs->thumb_filename);
    if ($fs->translator) row2("Translator", $fs->translator);
    if ($fs->uploader) row2("Uploader", $fs->uploader);

    $files = DB_score_file::enum("score_file_set_id=$fs->id");
    $x = [];
    foreach ($files as $f) {
        $x[] = sprintf('<a href="%s">%s</a>',
            imslp_file_url($f->file_name),
            $f->file_description
        );
        // TODO: show per-file info
    }
    row2('Files', implode('<br>', $x));
    end_table();
}

function audio_file_set_detail($fs, $work) {
    [$title, $first, $last] = parse_title($work->title);
    start_table('');
    row2("Work", "<a href=work.php?id=$work->id>$title</a>");
    row2("Composer", "<a href=person.php?id=$work->composer_id>$first $last</a>");
    $hs = hier_string($fs);
    if ($hs) row2("Categories", $hs);
    if ($fs->copyright_id) row2("Copyright", copyright_id_to_name($fs->copyright_id));
    if ($fs->date_submitted) row2("Date Submitted", $fs->date_submitted);
    if ($fs->ensemble_id) {
        $ensemble = DB_ensemble::lookup_id($fs->ensemble_id);
        row2("Ensemble",
            "<a href=ensemble.php?id=$fs->ensemble_id>$ensemble->name</a>"
        );
    }
    if ($fs->instrument_combo_id) {
    }
    if ($fs->misc_notes) row2("Misc. Notes", $fs->misc_notes);
    if ($fs->performer_categories) row2("Performer Categories", $fs->performer_categories);
    if ($fs->performers) row2("Performers", $fs->performers);
    if ($fs->performer_role_ids) {
        $x = [];
        $prs = json_decode($fs->performer_role_ids);
        foreach ($prs as $prid) {
            // could use a .ser file to avoid DB access
            $pr = DB_performer_role::lookup_id($prid);
            $person = DB_person::lookup_id($pr->person_id);
            $x[] = sprintf("<a href=person.php?id=%d>%s %s</a> (%s)",
                $person->id, $person->first_name, $person->last_name, $pr->role
            );
        }
        row2("Performer roles", implode('<br>', $x));
    }
    if ($fs->publisher_information) row2("Publisher Information", mw_string($fs->publisher_information));
    if ($fs->uploader) row2("Uploader", $fs->uploader);

    $files = DB_audio_file::enum("audio_file_set_id=$fs->id");
    $x = [];
    foreach ($files as $f) {
        $x[] = sprintf('<a href="%s">%s</a>',
            imslp_file_url($f->file_name),
            $f->file_description
        );
        // TODO: show per-file info
    }
    row2('Files', implode('<br>', $x));
    end_table();
}

function work_table_header() {
    row_heading_array(
        ['Title', 'Listen<br><small>click and hover</small>', 'Composer', 'Year', 'Period', 'Instruments']
    );
}

function work_table_row($work) {
    [$title, $first, $last] = parse_title($work->title);
    $note = '';
    if ($work->sample_audio_file_name) {
        audio_element($work->id, imslp_file_url($work->sample_audio_file_name));
        $note = audio_listen_link($work->id);
    }
    row_array([
        "<a href=work.php?id=$work->id>$title</a>",
        $note,
        "<nobr><a href=person.php?id=$work->composer_id>$first $last</a></nobr>",
        $work->year_of_composition?$work->year_of_composition:'---',
        $work->period_id?"<nobr>".period_name($work->period_id)."</nobr>":'',
        mw_string($work->instrumentation),
    ]);
}

// scores are shown in three contexts:
// 1) list of scores by a publisher (publisher.php)
// 2) list of arrangements for an instr combo (tags.php)
// 3) results of arrangement search (search_work.php)
//
// handle 1) separately from 2) and 3).

// for publisher list
//
function score_table_header() {
    row_heading_array([
        'Work',
        'Listen<br><small>click and hover</small>',
        'Publication date',
        'Categories'
    ]);
}

function score_table_row($fs) {
    $work = DB_work::lookup_id($fs->work_id);
    $date = '---';
    if ($fs->pub_date) {
        $date = mw_string($fs->pub_date);
    } else if ($fs->pub_year) {
        $date = $fs->pub_year;
    }
    $note = '';
    if ($work->sample_audio_file_name) {
        audio_element($fs->work_id, imslp_file_url($work->sample_audio_file_name));
        $note = audio_listen_link($fs->work_id);
    }
    row_array([
        "<a href=work.php?id=$fs->work_id#sfs_$fs->id>$work->title</a>",
        $note,
        $date,
        hier_string($fs)
    ]);
}

// for arrangement lists
//
function arr_table_header() {
    row_heading_array([
        'Work',
        'Composer',
        'Original instrumentation',
        'Listen<br><small>click and hover</small>',
        'Arrangement instrumentation'
    ]);
}

function arr_table_row($fs, $work=null) {
    if (!$work) {
        $work = DB_work::lookup_id($fs->work_id);
    }
    // show only audio for this arrangement
    //
    $note = '';
    if ($fs->sample_audio_file_name) {
        audio_element($fs->id, imslp_file_url($fs->sample_audio_file_name));
        $note = audio_listen_link($fs->id);
    }
    [$title, $first, $last] = parse_title($work->title);
    row_array([
        "<a href=work.php?id=$fs->work_id#sfs_$fs->id>$title</a>",
        "<a href=person.php?id=$work->composer_id>$first $last</a>",
        inst_combo_ids_to_str(json_decode($work->instrument_combo_ids)),
        $note,
        //inst_combo_ids_to_str(json_decode($fs->instrument_combo_ids))
        $fs->hier3
    ]);
}

function recording_table_header() {
    row_heading_array([
        'Work',
        'Composer',
        'Listen<br><small>click and hover</small>',
        'Details'
    ]);
}

function recording_table_row($fs) {
    $work = DB_work::lookup_id($fs->work_id);
    [$title, $first, $last] = parse_title($work->title);
    $note = '';
    $afs = DB_audio_file::enum("audio_file_set_id=$fs->id", "limit 1");
    $af = $afs[0];
    audio_element($fs->id, imslp_file_url($af->file_name));
    row_array([
        "<a href=recording.php?id=$fs->id>$title</a>",
        "<a href=person.php?id=$work->composer_id>$first $last</a>",
        audio_listen_link($fs->id),
        hier_string($fs)
    ]);
}

////////////   Search-related stuff //////////////

function period_options() {
    $opts = [[0, 'Any']];
    $periods = get_periods();
    foreach ($periods as $p) {
        $opts[] = [$p->id, $p->name];
    }
    return $opts;
}

function nationality_options() {
    $opts = [[0, 'Any']];
    $nats = get_nationalities();
    foreach ($nats as $n) {
        $opts[] = [$n->id, $n->name];
    }
    return $opts;
}

function sex_options() {
    return [
        ["male", "Male"],
        ["female", "Female"],
        ["either", "Either"]
    ];
}

// all/composer/performer
//
function person_type_options() {
    return [
        ["composer", "Composer"],
        ["performer", "Performer"],
        ["either", "Either"]
    ];
}

function ensemble_type_options() {
    $types = DB_ensemble::types();
    $opts = [['any', 'Any']];
    foreach ($types as $type) {
        $opts[] = [$type, $type];
    }
    return $opts;
}

function inst_combo_options() {
    return unserialize(file_get_contents('data/inst_combo_select.ser'));
}

function score_type_options() {
    return [
        [0, 'Original'],
        [1, 'Arrangement'],
    ];
}

function work_search_form() {
    form_start('search_work.php');
    form_input_text(
        'Keywords
            <br><small>Title, composer, and/or instrument<br>Optional</small>
        ',
        'keywords'
    );
    form_select('Instrumentation', 'instrument_combo_id', inst_combo_options());
    form_radio_buttons('Type', 'is_arr', score_type_options(), 0);
    form_select('If arrangement, original instrumentation',
        'orig_instrument_combo_id', inst_combo_options()
    );
    form_select('Period', 'period_id', period_options());
    form_radio_buttons('Sex of composer', 'sex', sex_options(), 'either');
    form_select('Nationality of composer', 'nationality_id', nationality_options());
    form_submit('Find works');
    form_end();
}

function person_search_form() {
    form_start('search_person.php');
    form_input_text('Last name', 'last_name', 'Any');
    form_select('Period', 'period_id', period_options());
    form_select('Nationality', 'nationality_id', nationality_options());
    form_radio_buttons('Type', 'person_type', person_type_options(), 'either');
    form_radio_buttons('Sex', 'sex', sex_options(), 'either');
    form_submit('Find people');
    form_end();
}

function ensemble_search_form() {
    form_start('search_ensemble.php');
    form_input_text('Name', 'name', 'Any');
    form_select('Type', 'type', ensemble_type_options(), 'any');
    form_select('Period', 'period_id', period_options());
    form_select('Nationality', 'nationality_id', nationality_options());
    form_submit('Find ensembles');
    form_end();
}
?>
