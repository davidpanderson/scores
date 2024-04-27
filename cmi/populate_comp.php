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
// input: imslp_data/david_page_dump.txt
//      a file in which each line is a JSON record
//      with roughly 100 wiki pages, each name=>contents
//      also .ser files for comp_type and instrument
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
define('DEBUG_WIKITEXT', 1);
    // log the original MW text
define('DEBUG_PARSED_WORK', 1);
    // log the parsed version of it
define('DEBUG_SCORES', 0);
    // log stuff related to scores
define('DEBUG_PERFS', 0);
    // log stuff related to recordings

//$test = false;
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

function get_person_role($person_id, $role, $inst_id=0) {
    $role_id = role_name_to_id($role);
    $q = "person=$person_id and role=$role_id";
    if ($inst_id) {
        $q .= " and instrument=$inst_id";
    }
    $r = DB_person_role::lookup($q);
    if ($r) return $r->id;
    return DB_person_role::insert(
        "(person, role, instrument) values ($person_id, $role_id, $inst_id)"
    );
}

///////////////// SCORE FILES /////////////////

// deprecated
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

// deprecated
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
// deprecated
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

// deprecated
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

// deprecated
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
// deprecated
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

// make a composition record for an arrangment
//
// $comp: the DB_composition
// $item: the parsed MW file record
// $hier: 3-element array
//
// To avoid duplicates,
// $other_arrs is a list of arrangements we've already made for this comp.
// Each is a pair [comp_id, struct] where the struct is
//      person_role_ids: clause for arrangers
//      inst_combos: clause for inst combos
//      title: string (e.g. sections)
//
// Return: a composition ID (new or existing)

function make_arrangement($comp, $item, $hier, &$other_arrs) {
    $clauses = [];

    if (DEBUG_ARRANGEMENTS) {
        echo "DEBUG_ARRANGEMENTS make_arrangement() start\n";
        echo "item:\n";
        print_r($item);
        echo "hier:\n";
        print_r($hier);
    }
    // get list of arrangers
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
    $pri_clause = '';
    if ($pers_role_ids) {
        $pri_clause = sprintf("creators='%s'",
            json_encode($pers_role_ids, JSON_NUMERIC_CHECK)
        );
        $clauses[] = $pri_clause;
    }

    // what instrument combo is arrangement for?
    //
    if (!empty($item->file_tags)) {
        [$work_types, $inst_combos, $arr_inst_combos, $langs] = parse_tags(
            $item->file_tags
        );
        $combos = $arr_inst_combos;
    } else {
        $combos = parse_arrangement_string($hier[2]);
    }
    if (DEBUG_ARRANGEMENTS) {
        echo "DEBUG_ARRANGEMENTS: combos\n";
        print_r($combos);
    }
    $combos_clause = '';
    if ($combos) {
        $combos_clause = inst_combos_clause($combos);
        $clauses[] = $combos_clause;
    }

    $title = $hier[1];

    // check for duplicate

    $new_desc = new StdClass;
    $new_desc->person_role_ids = $pri_clause;
    $new_desc->combos = $combos_clause;
    $new_desc->title = $title;

    foreach ($other_arrs as [$id, $desc]) {
        if ($desc == $new_desc) {
            return $id;
        }
    }

    $title = mb_convert_encoding($title, 'UTF-8');
    // fix Incorrect string value: '\xC3'
    $id = DB_composition::insert(
        sprintf(
            "(long_title, title, arrangement_of) values ('', '%s', %d)",
            DB::escape($title),
            $comp->id
        )
    );

    if (DEBUG_ARRANGEMENTS) {
        echo "clauses:\n";
        print_r($clauses);
        echo "DEBUG_ARRANGEMENTS make_arrangement() end\n";
    }

    if ($clauses) {
        $query = implode(',', $clauses);
        $new_comp = new DB_composition;
        $new_comp->id = $id;
        $new_comp->update($query);
    }

    $other_arrs[] = [$id, $new_desc];
    return $id;
}

// make records for a composition's
// - arrangements
// - scores
// based on $c->files (i.e. scores).
//
// $main_comp: the DB_composition
// $c: parsed mediawiki
//
// see notes_parse.txt
//
function handle_files($main_comp, $c) {
    $hier = ['','',''];
    $other_arrs = [];   // list of descriptors of arrangements we've already created
    $main_comp_id = $main_comp->id;
    $nsubcomps = 100;
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
            echo "score object:\n"; print_r($hier); print_r($item);

            $flags = 0;
            $section = '';

            // in the arrangement case we make a separate composition record;
            // handle this separately.
            //
            if (strtolower($hier[0]) == 'arrangements and transcriptions') {
                $arr_id = make_arrangement($main_comp, $item, $hier, $other_arrs);
                switch (strtolower($hier[1])) {
                case 'selections':
                    $flags = SELECTIONS;
                    break;
                default:
                    $section = expand_key($hier[1]);
                }
                make_score($item, $arr_id, $flags, $section);
                continue;
            }

            switch (strtolower($hier[0])) {
            case 'sketches and drafts':
                $flags |= SKETCHES;
                break;
            case 'parts':
                $flags |= PARTS;
                break;
            case 'vocal scores':
                $flags |= VOCAL;
                break;
            case '':
                break;
            default:
                echo sprintf("unrecognized hier[0]: %s\n", $hier[0]);
                break;
            }
            switch (strtolower($hier[1])) {
            case 'selections':
                $flags |= SELECTIONS;
                break;
            case '':
                break;
            default:
                $section = expand_key($hier[1]);
            }

            make_score($item, $main_comp_id, $flags, $section);
        }
    }
}

// make a score record

function make_score($item, $comp_id, $flags, $section) {
    echo "make_score()\n";
    if (!$comp_id) throw new Exception('foo');
    $nfiles = min(count($item->file_names), count($item->file_descs));
    if ($nfiles == 0) return;
    $file_names = array_slice($item->file_names, 0, $nfiles);
    $file_descs = array_slice($item->file_descs, 0, $nfiles);
    $files = [];
    for ($i=0; $i<$nfiles; $i++) {
        $f = new StdClass;
        $f->desc = expand_key($file_descs[$i]);
        $f->name = $file_names[$i];
        if (!empty($item->page_counts[$i])) {
            $f->pages = $item->page_counts[$i];
        } else {
            $f->pages = 0;
        }
        $files[] = $f;
    }

    $license = 0;
    $publisher = 0;
    $publish_year = 0;
    $edition_number = '';
    $image_type = '';
    
    if (!empty($item->copyright)) {
        $license = get_license_id($item->copyright);
    }
    if (!empty($item->image_type)) {
        $image_type = $item->image_type;
    }
    if (!empty($item->pub)) {
        $pub = $item->pub;
        // sometimes the name is missing
        //
        if (!empty($pub->imprint) && empty($pub->name)) {
            $pub->name = $pub->imprint;
        }
        $publisher = get_publisher($pub, 'Music publisher');
        if (!empty($pub->year)) {
            if (is_numeric($pub->year)) {
                $publish_year = (int)$pub->year;
            }
        }
        if (!empty($pub->edition_number)) {
            $edition_numer = $pub->edition_number;
        }
    }
    $creators = [];
    if (!empty($item->editor)) {
        $pr_ids = get_editors($item->editor);
        $creators = array_merge($creators, $pr_ids);
    }
    if (!empty($item->translator)) {
        $pr_ids = get_translators($item->translator);
        $creators = array_merge($creators, $pr_ids);
    }
    $id = DB_score::insert(
        sprintf("(compositions, creators, files, section, publisher, license, publish_date, edition_number, image_type, is_parts, is_selections, is_vocal) values ('%s', '%s', '%s', '%s', %d, %d, %d, '%s', '%s', %d, %d, %d)",
            json_encode([$comp_id], JSON_NUMERIC_CHECK),
            json_encode($creators, JSON_NUMERIC_CHECK),
            DB::escape(json_encode($files, JSON_NUMERIC_CHECK)),
            DB::escape($section),
            $publisher,
            $license,
            DB::date_num($publish_year),
            $edition_number,
            $image_type,
            $flags&PARTS?1:0,
            $flags&SELECTIONS?1:0,
            $flags&VOCAL?1:0
        )
    );
    // TODO: populate languages, page_count
    return $id;
}

// make Performance records for recordings,
// based on c->audios
//
function handle_audios($comp, $c) {
    $hier = ['','',''];
    foreach ($c->audios as $item) {
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
            $is_synthesized = (strtolower($hier[0]) == 'synthesized/midi');
            $section = expand_key($hier[1]);
            $instrumentation = $hier[2];
            make_performance(
                $item, $comp, $is_synthesized, $section, $instrumentation
            );
        }
    }
}

function make_performance(
    $item, $comp, $is_synthesized, $section, $instrumentation
) {
    $nfiles = min(count($item->file_names), count($item->file_descs));
    if ($nfiles == 0) return;
    $file_names = array_slice($item->file_names, 0, $nfiles);
    $file_descs = array_slice($item->file_descs, 0, $nfiles);
    $files = [];
    for ($i=0; $i<$nfiles; $i++) {
        $f = new StdClass;
        $f->desc = expand_key($file_descs[$i]);
        $f->name = $file_names[$i];
        $files[] = $f;
    }
    $p = empty($item->performers)?'':$item->performers;
    $pc = empty($item->performer_categories)?'':$item->performer_categories;
    [$ensemble, $performers] = parse_performers($p, $pc);
    if ($ensemble) {
        $ens_id = get_ensemble_new($ensemble[0], $ensemble[1]);
    } else {
        $ens_id = 0;
    }
    $perf_role_ids = [];
    foreach ($performers as $perf) {
        $x = strtolower($perf[2]);
        if ($x == 'conductor' || $x == 'dir.') {
            $person = get_person($perf[0], $perf[1]);
            $pr_id = get_person_role($person, 'conductor');
            $perf_role_ids[] = $pr_id;
        } else {
            $inst = DB_instrument::lookup(
                sprintf("name='%s'", DB::escape($perf[2]))
            );
            $inst_id = $inst?$inst->id:0;
            $person = get_person($perf[0], $perf[1]);
            $pr_id = get_person_role($person, 'performer', $inst_id);
            $perf_role_ids[] = $pr_id;
        }
    }

    $license = 0;
    if (!empty($item->copyright)) {
        $license = get_license_id($item->copyright);
    }

    DB_performance::insert(
        sprintf(
            "(composition, performers, ensemble, is_recording, files, is_synthesized, section, instrumentation, license) values (%d, '%s', %d, 1, '%s', %d, '%s', '%s', %d)",
            $comp->id,
            DB::escape(json_encode($perf_role_ids, JSON_NUMERIC_CHECK)),
            $ens_id,
            DB::escape(json_encode($files, JSON_NUMERIC_CHECK)),
            $is_synthesized?1:0,
            DB::escape($section),
            DB::escape($instrumentation),
            $license
        )
    );
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
            sprintf("(long_title, title, alternative_title, parent, _keys, n_bars, metronome_markings) values ('', '%s', '%s', %d, '%s', %d, '%s')",
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

// convert e.g. '4 minutes' to seconds
function parse_average_duration($s) {
    if (strstr($s, 'minutes') === false) return 0;
    $n = (int)$s;
    return $n*60;
}

// create DB records for work and its scores and recordings
//
function make_work($c) {
    global $test;
    [$title, $composer_first, $composer_last] = parse_long_title($c->json_title);
    $composer_id = get_person($composer_first, $composer_last);

    $long_title = str_replace('_', ' ', $c->json_title);
    $long_title = fix_title($long_title);

    // check for works with same title
    //
    $w = DB_composition::lookup(
        sprintf("long_title='%s'", DB::escape($long_title))
    );
    if ($w) {
        echo "duplicate composition found\n";
        return;
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
        $oc = parse_opus($c->opus_catalogue);
        if ($oc) {
            $x[] = sprintf("opus_catalogue='%s'", DB::escape($oc));
        }
    }
    if (!empty($c->alternative_title)) {
        $x[] = sprintf("alternative_title='%s'", DB::escape($c->alternative_title));
    }
    // TODO: deal with stuff like '10-12 minutes'
    if (!empty($c->average_duration)) {
        $secs = parse_average_duration($c->average_duration);
        if ($secs) {
            $x[] = sprintf('avg_duration_sec=%d', $secs);
        }
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
            $d = DB::date_num($comp_year);
            if ($d) {
                $x[] = "composed=$d";
            }
        }
    }
    $pub_year = 0;
    if (!empty($c->year_of_first_publication)) {
        $pub_year = (int)$c->year_of_first_publication;
    }
    if ($pub_year) {
        $d = DB::date_num($pub_year);
        if ($d) {
            $x[] = "published=$d";
        }
    }
    if (!empty($c->work_title)) {
        $x[] = sprintf("title='%s'",
            DB::escape(expand_title(fix_title($c->work_title)))
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
        $comp = new DB_composition;
        $comp->id = $comp_id;
        $ret = $comp->update($query);
        if (!$ret) {
            echo "comp update failed\n";
        }
    }

    // process c->files; make arrangements, scores, sub-compositions
    //
    if (!empty($c->files)) {
        if (DEBUG_SCORES) {
            echo "------------ handling scores -------------\n";
            print_r($c->files);
        }
        handle_files($comp, $c);
    }

    if (!empty($c->audios)) {
        if (DEBUG_PERFS) {
            echo "------------ handling recordings -------------\n";
            print_r($c->audios);
        }
        handle_audios($comp, $c);
    }
}

// given a list of inst combos (as count/code list),
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
        // Keep only the first one
        //$x[] = sprintf("languages='%s'",
        //    json_encode($lang_ids, JSON_NUMERIC_CHECK)
        //);
        $x[] = sprintf('language=%d', $lang_ids[0]);
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
    $f = fopen('imslp_data/david_page_dump.txt', 'r');
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
            //if ($title != 'Symphony_No.20_in_D_major,_K.133_(Mozart,_Wolfgang_Amadeus)') continue;
            //if ($title != 'Fantasia_in_C_minor,_Op.80_(Beethoven,_Ludwig_van)') continue;
            //if ($title != 'Etudes,_Op.60_(Carcassi,_Matteo)') continue;
            echo "================ $title ==========\n";
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
            //break;
        }
        DB::commit_transaction();
    }
    //flush_inst_combo_cache();
    //flush_work_type_cache();


    fwrite(STDERR, "==> Now run make_ser.php\n");
}

DB::$show_queries = true;

// there are 3079 lines

main(0, 10);

?>
