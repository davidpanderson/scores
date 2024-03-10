#! /usr/bin/env php

<?php

// create database entries for compositions and related items:
//      composition
//      person
//      score
//      recording
//      license
//      publisher
//      ensemble
//
// input: a file in which each line is a JSON record
//      with roughly 100 wiki pages, each name=>contents
//      also .ser files from populate_comp_type.php
// For each page:
// - parse it using parse_work()
// - skip if it doesn't contain a #pfe:imslppage call
// - call make_work() to write DB entries

require_once("mediawiki.inc");
require_once("parse_work.inc");
require_once("parse_tags.inc");
require_once("parse_combo.inc");
require_once("cmi_db.inc");
require_once("cmi_util.inc");
require_once("populate_util.inc");

define('DEBUG_ARRANGEMENTS', 0);
define('DEBUG_WIKITEXT', 0);
define('DEBUG_PARSED_WORK', 0);

$test = false;
DB::$exit_on_db_error = true;

// given a string of the form ===FOO, return [3, 'FOO']
//
function hier_label($str) {
    if (strpos($str, '=====') === 0) {
        $n = strpos($str, '=', 5);
        return [5, substr2($str, 5, $n)];
    }
    if (strpos($str, '====') === 0) {
        $n = strpos($str, '=', 4);
        return [4, substr2($str, 4, $n)];
    }
    if (strpos($str, '===') === 0) {
        $n = strpos($str, '=', 3);
        return [3, substr2($str, 3, $n)];
    }
    return [0, ''];
}

///////////////// PERSON_ROLE /////////////////

function get_person_role($person_id, $role) {
    $role_id = role_name_to_id($role);
    $r = DB_person_role::lookup("person=$person_id and role=$role_id");
    if ($r) return $r->id;
    return DB_person_role::insert(
        "(person, role) values ($person_id, $role_id)"
    );
}

///////////////// SCORE FILES /////////////////

function make_score_file($f, $i, $file_set_id) {
    global $test;
    if (!array_key_exists($i, $f->file_names)) {
        echo "make_score_file(): missing file name\n";
        print_r($f->file_names);
        return;
    }
    if (!array_key_exists($i, $f->file_descs)) {
        echo "make_score_file(): missing file desc\n";
        print_r($f->file_descs);
        return;
    }
    $q = sprintf(
        "(score_file_set_id, file_name, file_description) values (%d, '%s', '%s')",
        $file_set_id,
        DB::escape($f->file_names[$i]),
        DB::escape($f->file_descs[$i])
    );
    if ($test) {
        echo "score_file insert: $q\n";
    } else {
        $id = DB_score_file::insert($q);
        if (!$id) {
            echo "score_file insert failed\n";
            return;
        }
    }

    $x = [];
    if (array_key_exists($i, $f->date_submitteds)) {
        $x[] = sprintf("date_submitted='%s'", DB::escape($f->date_submitteds[$i]));
    }
    if (array_key_exists($i, $f->page_counts)) {
        $x[] = sprintf("page_count='%s'", DB::escape($f->page_counts[$i]));
    }
    if (array_key_exists($i, $f->sample_filenames)) {
        $x[] = sprintf("sample_filename='%s'", DB::escape($f->sample_filenames[$i]));
    }
    if (array_key_exists($i, $f->scanners)) {
        $x[] = sprintf("scanner='%s'", DB::escape($f->scanners[$i]));
    }
    if (array_key_exists($i, $f->thumb_filenames)) {
        $x[] = sprintf("thumb_filename='%s'", DB::escape($f->thumb_filenames[$i]));
    }
    if (array_key_exists($i, $f->uploaders)) {
        $x[] = sprintf("uploader='%s'", DB::escape($f->uploaders[$i]));
    }
    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "score file update: $query\n";
        } else {
            $x = new DB_score_file;
            $x->id = $id;
            $x->update($query);
        }
    }
}

function make_score_file_set($wid, $f, $hier) {
    global $test;
    $copyright_id = 0;
    if (!empty($f->copyright)) {
        $copyright_id = get_copyright($f->copyright);
    }

    $q = sprintf(
        "(work_id, hier1, hier2, hier3) values (%d, '%s', '%s', '%s')",
        $wid,
        DB::escape($hier[0]),
        DB::escape($hier[1]),
        DB::escape($hier[2])
    );
    if ($test) {
        echo "file set insert: $q\n";
        $file_set_id = 1;
    } else {
        $file_set_id = DB_score_file_set::insert($q);
        if (!$file_set_id) {
            echo "score_file_set insert failed\n";
            return;
        }
    }
    $x = [];
    if (!empty($f->amazon)) {
        $x[] = sprintf("amazon='%s'", DB::escape($f->amazon));
    }
    if (!empty($f->arranger)) {
        $x[] = sprintf("arranger='%s'", DB::escape($f->arranger));
    }
    if ($copyright_id) {
        $x[] = sprintf("copyright_id=%d", $copyright_id);
    }
    if (!empty($f->date_submitted)) {
        $x[] = sprintf("date_submitted='%s'", DB::escape($f->date_submitted));
    }
    if (!empty($f->editor)) {
        $x[] = sprintf("editor='%s'", DB::escape($f->editor));
    }
    if (!empty($f->engraver)) {
        $x[] = sprintf("engraver='%s'", DB::escape($f->engraver));
    }
    if (!empty($f->file_tags)) {
        $x[] = sprintf("file_tags='%s'", DB::escape($f->file_tags));
        process_tags_score($x, $f->file_tags);
    }
    if (!empty($f->image_type)) {
        $x[] = sprintf("image_type='%s'", DB::escape($f->image_type));
    }
    if (!empty($f->misc_notes)) {
        $x[] = sprintf("misc_notes='%s'", DB::escape($f->misc_notes));
    }
    if (!empty($f->publisher_information)) {
        $x[] = sprintf("publisher_information='%s'", DB::escape($f->publisher_information));
    }
    if (!empty($f->pub)) {
        $pub = $f->pub;
        // sometimes the name is missing
        //
        if ($pub->imprint && !$pub->name) {
            $pub->name = $pub->imprint;
        }
        $publisher_id = get_publisher($pub->name, $pub->imprint, $pub->location);
        $x[] = sprintf("publisher_id=%d", $publisher_id);
        if (!empty($pub->date)) {
            $x[] = sprintf("pub_date='%s'", DB::escape($pub->date));
        }
        if (!empty($pub->edition_number)) {
            $x[] = sprintf("pub_edition_number='%s'", DB::escape($pub->edition_number));
        }
        if (!empty($pub->extra)) {
            $x[] = sprintf("pub_extra='%s'", DB::escape($pub->extra));
        }
        if (!empty($pub->plate_number)) {
            $x[] = sprintf("pub_plate_number='%s'", DB::escape($pub->plate_number));
        }
        if (!empty($pub->year)) {
            $x[] = sprintf("pub_year=%d", $pub->year);
        }
    }
    if (!empty($f->reprint)) {
        $x[] = sprintf("reprint='%s'", DB::escape($f->reprint));
    }
    if (!empty($f->sample_filename)) {
        $x[] = sprintf("sample_filename='%s'", DB::escape($f->sample_filename));
    }
    if (!empty($f->scanner)) {
        $x[] = sprintf("scanner='%s'", DB::escape($f->scanner));
    }
    if (!empty($f->sm_plus)) {
        $x[] = sprintf("sm_plus='%s'", DB::escape($f->sm_plus));
    }
    if (!empty($f->thumb_filename)) {
        $x[] = sprintf("thumb_filename='%s'", DB::escape($f->thumb_filename));
    }
    if (!empty($f->translator)) {
        $x[] = sprintf("translator='%s'", DB::escape($f->translator));
    }
    if (!empty($f->uploader)) {
        $x[] = sprintf("uploader='%s'", DB::escape($f->uploader));
    }

    // if the score is an arrangement:
    // - link it to an "arrangement target" entry (deprecated)
    // - if no instrumentation specified in tags,
    //      try to infer it from hier3
    //
    if ($hier[0] == 'Arrangements and Transcriptions') {
        $at = parse_arrangement_target($hier);
        if ($at) {
            $at_id = get_arrangement_target($at);
            $x[] = "arrangement_target_id=$at_id";
        }
        if (empty($f->file_tags)) {
            $inst_combos = parse_arrangement_string($hier[2]);
            if ($inst_combos) {
                $x[] = inst_combos_clause($inst_combos, false);
            }
        }
    }

    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "file set update: $query\n";
        } else {
            $x = new DB_score_file_set;
            $x->id = $file_set_id;
            $x->update($query);
        }
    }

    // create the file records
    //
    $n = count($f->file_names);
    for ($i=0; $i<$n; $i++) {
        make_score_file($f, $i, $file_set_id);
    }
}

// create file sets for a work
// $wid is a work ID
// $files is a list of strings and file objects
//
function make_score_file_sets($wid, $files) {
    $hier = ['','',''];
    foreach ($files as $item) {
        if (is_string($item)) {
            if (starts_with($item, '===')) {
                [$level, $name] = hier_label($item);
                if ($level == 3) {
                    $hier[0] = $name;
                    $hier[1] = '';
                    $hier[2] = '';
                } else if ($level == 4) {
                    $hier[1] = $name;
                    $hier[2] = '';
                } else if ($level == 5) {
                    $hier[2] = $name;
                } else {
                    echo "unrecognized hierarchy level: $item\n";
                }
            } else {
                echo "unrecognized string in file list: $item\n";
            }
        } else {
            make_score_file_set($wid, $item, $hier);
        }
    }
}

///////////////// AUDIO FILES /////////////////

function make_audio_file($f, $i, $file_set_id) {
    global $test;
    if (!array_key_exists($i, $f->file_names)) return;
    if (!array_key_exists($i, $f->file_descs)) return;
    $q = sprintf(
        "(audio_file_set_id, file_name, file_description) values (%d, '%s', '%s')",
        $file_set_id,
        DB::escape($f->file_names[$i]),
        DB::escape($f->file_descs[$i])
    );
    if ($test) {
        echo "audio_file insert: $q\n";
    } else {
        $id = DB_audio_file::insert($q);
        if (!$id) {
            echo "audio_file insert failed\n";
            return;
        }
    }
    $x = [];
    if (array_key_exists($i, $f->date_submitteds)) {
        $x[] = sprintf("date_submitted='%s'", DB::escape($f->date_submitteds[$i]));
    }
    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "score file update: $query\n";
        } else {
            $x = new DB_score_file;
            $x->id = $id;
            $x->update($query);
        }
    }
}

function make_audio_set($wid, $f, $hier) {
    global $test;
    $copyright_id = 0;
    if (!empty($f->copyright)) {
        $copyright_id = get_copyright($f->copyright);
    }
    $q = sprintf(
        "(work_id, hier1, hier2, hier3) values (%d, '%s', '%s', '%s')",
        $wid,
        DB::escape($hier[0]),
        DB::escape($hier[1]),
        DB::escape($hier[2])
    );
    if ($test) {
        echo "audio set insert: $q\n";
        $file_set_id = 1;
    } else {
        $file_set_id = DB_audio_file_set::insert($q);
        if (!$file_set_id) {
            echo "audio_file_set insert failed\n";
            return;
        }
    }
    $x = [];
    if ($copyright_id) {
        $x[] = sprintf("copyright_id=%d", $copyright_id);
    }
    if (!empty($f->date_submitted)) {
        $x[] = sprintf("date_submitted='%s'", DB::escape($f->date_submitted));
    }
    if (!empty($f->misc_notes)) {
        $x[] = sprintf("misc_notes='%s'", DB::escape($f->misc_notes));
    }
    if (!empty($f->performer_categories)) {
        $x[] = sprintf("performer_categories='%s'", DB::escape($f->performer_categories));
    }
    if (!empty($f->performers)) {
        $x[] = sprintf("performers='%s'", DB::escape($f->performers));
    }
    if (!empty($f->publisher_information)) {
        $x[] = sprintf("publisher_information='%s'", DB::escape($f->publisher_information));
    }
    if (!empty($f->thumb_filename)) {
        $x[] = sprintf("thumb_filename='%s'", DB::escape($f->thumb_filename));
    }
    if (!empty($f->uploader)) {
        $x[] = sprintf("uploader='%s'", DB::escape($f->uploader));
    }

    // create ensemble and performer records if needed
    //
    [$ensemble, $performers] = parse_performers(
        empty($f->performers)?'':$f->performers,
        empty($f->performer_categories)?'':$f->performer_categories
    );
    if ($ensemble) {
        $ens_id = get_ensemble($ensemble[0], $ensemble[1]);
        $x[] = "ensemble_id=$ens_id";
    }

    $perf_role_ids = [];
    foreach ($performers as $perf) {
        $perf_role_ids[] = get_performer_role($perf[0], $perf[1], $perf[2]);
    }
    if ($perf_role_ids) {
        $x[] = sprintf("performer_role_ids='%s'",
            json_encode($perf_role_ids, JSON_NUMERIC_CHECK)
        );
    }

    // try to get an instrument combo from hier3
    // (e.g. 'For 2 Flutes (Foo)')
    //
    if (str_starts_with($hier[2], 'For ')) {
        $combos = parse_arrangement_string($hier[2]);
        if ($combos) {
            $ic_rec = get_inst_combo($combos[0]);
            $x[] = "instrument_combo_id=$ic_rec->id";
        }
    }

    $afs = new DB_audio_file_set;
    $afs->id = $file_set_id;

    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "file set update: $query\n";
        } else {
            $afs->update($query);
        }
    }

    // create the audio records
    //
    $n = count($f->file_names);
    for ($i=0; $i<$n; $i++) {
        make_audio_file($f, $i, $file_set_id);
    }
}

// $wid is a work ID
// $audios is a list of strings and audio objects
//
function make_audio_file_sets($wid, $audios) {
    $hier = ['','',''];
    foreach ($audios as $item) {
        if (is_string($item)) {
            if (starts_with($item, '===')) {
                [$level, $name] = hier_label($item);
                if ($level == 3) {
                    $hier[0] = $name;
                    $hier[1] = '';
                    $hier[2] = '';
                } else if ($level == 4) {
                    $hier[1] = $name;
                    $hier[2] = '';
                } else if ($level == 5) {
                    $hier[2] = $name;
                } else {
                    echo "unrecognized hierarchy level: $item\n";
                }
            } else {
                echo "unrecognized string in file list: $item\n";
            }
        } else {
            make_audio_set($wid, $item, $hier);
        }
    }
}

//% make a composition record for an arrangment
//
// $comp: the DB_composition
// $item: the parsed MW file record
// $hier: 3-element array
//
function make_arrangement($comp, $item, $hier, $n) {
    $x = [];

    // who did the arrangement?
    //
    $pers_role_ids = [];
    if (!empty($item->arranger)) {
        $arrangers = parse_arranger($item->arranger);
        foreach ($arrangers as [$first, $last, $born, $died]) {
            $person_id = get_person($first, $last, $born, $died);
            if ($person_id) {
                $pers_role_ids[] = get_person_role($person_id, 'arranger');
            }
        }
    }
    if ($pers_role_ids) {
        $x[] = sprintf("creators='%s'",
            json_encode($pers_role_ids, JSON_NUMERIC_CHECK)
        );
    }

    // what instrument combo is arrangement for?
    //
    if (!empty($item->file_tags)) {
        process_tags_score($x, $item->file_tags);
    } else {
        $combos = parse_arrangement_string($hier[2]);
        if ($combos) {
            $x[] = inst_combos_clause($combos);
        }
    }
    $long_title = "arr $comp->id $n";
    $id = DB_composition::insert(
        "(long_title, arrangement_of) values ('$long_title', $comp->id)"
    );

    if (DEBUG_ARRANGEMENTS) {
        echo "DEBUG_ARRANGMENTS start\n";
        echo "item:\n";
        print_r($item);
        echo "hier:\n";
        print_r($hier);
        echo "results:\n";
        print_r($x);
        echo "DEBUG_ARRANGMENTS end\n";
    }

    if ($x) {
        $query = implode(',', $x);
        $new_comp = new DB_composition;
        $new_comp->id = $id;
        $new_comp->update($query);
    }
}

// make records for a composition's arrangements
//
// $comp: the DB_composition
// $c: parsed mediawiki
//
function make_arrangements($comp, $c) {
    $hier = ['','',''];
    $n = 0;
    foreach ($c->files as $item) {
        if (is_string($item)) {
            if (starts_with($item, '===')) {
                [$level, $name] = hier_label($item);
                if ($level == 3) {
                    $hier[0] = $name;
                    $hier[1] = '';
                    $hier[2] = '';
                } else if ($level == 4) {
                    $hier[1] = $name;
                    $hier[2] = '';
                } else if ($level == 5) {
                    $hier[2] = $name;
                } else {
                    echo "unrecognized hierarchy level: $item\n";
                }
            } else {
                echo "unrecognized string in file list: $item\n";
            }
        } else {
            if ($hier[0] == 'Arrangements and Transcriptions') {
                make_arrangement($comp, $item, $hier, $n++);
            }
        }
    }
}

// make sub-compositions; return vector of IDs
//
function make_movements($c, $comp_id) {
    $mvts = parse_nmvts_sections($c->number_of_movements_sections);
    if (!$mvts) return [];
    $ids = [];
    $i = 0;
    foreach ($mvts->sections as $section) {
        $ids[] = DB_composition::insert(
            sprintf("(long_title, title, alternative_title, parent, _keys, nbars, metronome_markings) values ('%s', '%s', '%s', %d, '%s', %d, '%s')",
                "sub-comp $i of $comp_id",
                DB::escape($section->title),
                DB::escape($section->alt_title),
                $comp_id,
                DB::escape($section->keys),
                $section->nbars,
                DB::escape($section->metro)
            )
        );
        $i++;
    }
    return $ids;
}

///////////////// WORK /////////////////

// create DB records for work and its files
//
function make_work($c) {
    global $test;
    [$title, $composer_first, $composer_last] = parse_title($c->json_title);
    $composer_id = get_person($composer_first, $composer_last);

    $json_title = str_replace('_', ' ', $c->json_title);
    $json_title = fix_title($json_title);

    // check for works with same title
    //
    $w = DB_composition::lookup(
        sprintf("title='%s'", DB::escape($json_title))
    );
    if ($w) {
        for ($i=2; ; $i++) {
            $long_title = sprintf('%s (%d)', $json_title, $i);
            echo "DUP FOUND: trying $title\n";
            $w = DB_composition::lookup(
                sprintf("title='%s'", DB::escape($long_title))
            );
            if (!$w) break;
        }
    } else {
        $long_title = $json_title;
    }
    $q = sprintf("(long_title) values ('%s')", DB::escape($long_title));
    if ($test){
        echo "work insert: $q\n";
        $comp_id = 1;
    } else {
        $comp_id = DB_composition::insert($q);
        if (!$comp_id) {
            echo "composition insert failed\n";
            return;
        }
    }

    $x = [];

    $role_ids = [];
    if ($composer_id) {
        $role_ids[] = get_person_role($composer_id, 'composer');
    }
    if (!empty($c->librettist)) {
        $libs = parse_arranger($c->librettist);
        foreach ($libs as [$first, $last, $born, $died]) {
            $person_id = get_person($first, $last, $born, $died);
            if ($person_id) {
                $role_ids[] = get_person_role($person_id, 'librettist');
            }
        }
    }
    if ($role_ids) {
        $x[] = sprintf("creators='%s'",
            json_encode($role_ids, JSON_NUMERIC_CHECK)
        );
    }

    if (!empty($c->opus_catalogue)) {
        $x[] = sprintf("opus_catalogue='%s'", DB::escape($c->opus_catalogue));
    }
    if (!empty($c->alternative_title)) {
        $x[] = sprintf("alternative_title='%s'", DB::escape($c->alternative_title));
    }
    // TODO: deal with stuff like '10-12 minutes'
    if (!empty($c->average_duration)) {
        $x[] = sprintf("average_duration='%s'", DB::escape($c->average_duration));
    }
    // TODO: fix things like {{LinkDed|Ferdinand|Hiller}}
    if (!empty($c->dedication)) {
        $d = parse_dedication($c->dedication);
        if ($d) {
            $x[] = sprintf("dedication='%s'", DB::escape($d));
        }
    }
    if (0) {
    // TODO: parse '1881-01-04 in Breslau, Saal des Konzerthauses.  :Breslauer Orchesterverein, Johannes Brahms (conductor)'
    if (!empty($c->first_performance)) {
        $x[] = sprintf("first_performance='%s'", DB::escape($c->first_performance));
    }
    }

    if (!empty($c->key)) {
        $s = parse_keys($c->key);
        if ($s) {
            $x[] = sprintf("_keys='%s'", DB::escape($s));
        }
    }

    // e.g. '3 movements'
    if (!empty($c->movements_header)) {
        $n = (int)$c->movements_header;
        if ($n) {
            $x[] = sprintf("n_movements=%d", $n);
        }
    }

    // TODO: get rid of this?
    if (!empty($c->piece_style)) {
        $period_id = get_period_id(str_replace('_', ' ', $c->piece_style));
        if ($period_id) {
            $x[] = sprintf("period=%d", $period_id);
        }
    }
    // tags has composition type and instruments
    if (!empty($c->tags)) {
        process_tags_work($x, $c->tags);
    }

    $comp_year = 0;
    if (!empty($c->year_date_of_composition)) {
        $comp_year = (int)$c->year_date_of_composition;
        if ($comp_year) {
            $x[] = sprintf("composed='%s'", "$comp_year:01:01");
        }
    }
    $pub_year = 0;
    if (!empty($c->year_of_first_publication)) {
        $pub_year = (int)$c->year_of_first_publication;
    }
    if ($pub_year) {
        $x[] = sprintf("published='%s'", "$pub_year:01:01");
    }
    if (!empty($c->work_title)) {
        $x[] = sprintf("title='%s'",
            DB::escape(fix_title($c->work_title))
        );
    }

    // Make sub-compositions if needed
    //
    if (!empty($c->number_of_movements_sections)) {
        $ids = make_movements($c, $comp_id);
        $x[] = sprintf("children='%s'",
            json_encode($ids, JSON_NUMERIC_CHECK)
        );
    }

    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "comp update: $query\n";
        } else {
            $comp = new DB_composition;
            $comp->id = $comp_id;
            $ret = $comp->update($query);
            if (!$ret) {
                echo "comp update failed\n";
            }
        }
    }

    // Make arrangments if needed
    //
    make_arrangements($comp, $c);

if (0) {
    if (!empty($c->files)) {
        make_score_file_sets($work_id, $c->files);
    }
    if (!empty($c->audios)) {
        make_audio_file_sets($work_id, $c->audios);
    }
}
}

// given a list of inst combos,
// return an update clause
//
function inst_combos_clause($inst_combos) {
    $ic_ids = [];
    foreach ($inst_combos as $ic) {
        // $ic is a list of [count, code]
        $ic_rec = get_inst_combo($ic);
        $ic_ids[] = $ic_rec->id;
    }
    return sprintf("instrument_combos='%s'",
        json_encode($ic_ids, JSON_NUMERIC_CHECK)
    );
}

// process a work's tags.
// append update clauses to array $x
//
function process_tags_work(&$x, $tags) {
    [$comp_types, $inst_combos, $arr_inst_combos, $langs] = parse_tags($tags);

    // work types

    $wt_ids = [];
    foreach ($comp_types as $code) {
        $wt = comp_type_by_code($code);
        $wt_ids[] = $wt->id;
    }
    if ($wt_ids) {
        // without the JSON_NUMERIC_CHECK,
        // ids sometimes get represented as strings - WTF???
        //
        $x[] = sprintf("comp_types='%s'",
            json_encode($wt_ids, JSON_NUMERIC_CHECK)
        );
    }

    // instrument combos
    //
    if ($inst_combos) {
        $x[] = inst_combos_clause($inst_combos);
    }

    // languages
    //
    $lang_ids = [];
    foreach ($langs as $lang) {
        $rec = lang_by_code($lang);
        $lang_ids[] = $rec->id;
    }
    if ($lang_ids) {
        $x[] = sprintf("languages='%s'",
            json_encode($lang_ids, JSON_NUMERIC_CHECK)
        );
    }
}

// process a score file's tags
//
function process_tags_score(&$x, $tags) {
    [$work_types, $inst_combos, $arr_inst_combos, $langs] = parse_tags($tags);
    $ic_ids = [];
    foreach ($arr_inst_combos as $ic) {
        // $ic is a list of [count, code]
        $ic_rec = get_inst_combo($ic);
        $ic_ids[] = $ic_rec->id;
        $ic_rec->nscores++;
    }
    if ($ic_ids) {
        $x[] = sprintf("instrument_combos='%s'",
            json_encode($ic_ids, JSON_NUMERIC_CHECK)
        );
    }
}

function main($start_line, $end_line) {
    $f = fopen('data/david_page_dump.txt', 'r');
    for ($i=0; ; $i++) {
        $x = fgets($f);
        if (!$x) {
            echo "Reached end of file\n";
            break;
        }
        if ($i<$start_line) continue;
        if ($i>=$end_line) break;
        echo "JSON record $i\n";
        if (!trim($x)) continue;    // skip blank lines
        $y = json_decode($x);
        DB::begin_transaction();
        foreach ($y as $title => $body) {
            //if ($title != 'Symphony_No.12_in_G_major,_K.110/75b_(Mozart,_Wolfgang_Amadeus)') continue;
            if ($title != 'Piano_Sonata_in_A_minor,_D.845_(Schubert,_Franz)') continue;
            echo "==================\ntitle: $title\n";
            if (DEBUG_WIKITEXT) {
                echo "DEBUG_WIKITEXT start\n";
                echo "$body\n";
                echo "DEBUG_WIKITEXT end\n";
            }
            $comp = parse_work($title, $body);
            if (DEBUG_PARSED_WORK) {
                echo "DEBUG_PARSED_WORK start\n";
                print_r($comp);
                echo "DEBUG_PARSED_WORK end\n";
            }
            if (empty($comp->imslppage)) {
                // redirect, pop_section, link) work
                continue;
            }
            make_work($comp);
        }
        DB::commit_transaction();
    }
    return;
    flush_inst_combo_cache();
    flush_work_type_cache();
}

// there are 3079 lines

DB::$show_queries = true;
main(0, 10);

?>
