<?php

// create database entries for works and relate items

require_once("mediawiki.inc");
require_once("parse_work.inc");
require_once("imslp_db.inc");
require_once("imslp_util.inc");
require_once("populate_util.inc");

$test = false;
//$exit_on_db_error = true;

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

if (0) {
    print_r(hier_label('===foo==='));
    print_r(hier_label('foo'));
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

    // if the score is an arrangement, link it to the arrangement target
    //
    $at = parse_arrangement_target($hier);
    if ($at) {
        $at_id = get_arrangement_target($at);
        $x[] = "arrangement_target_id=$at_id";
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
    if (!empty($f->publisher_info)) {
        $x[] = sprintf("publisher_info='%s'", DB::escape($f->publisher_info));
    }
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
    if (!empty($f->publisher_info)) {
        $x[] = sprintf("publisher_info='%s'", DB::escape($f->publisher_info));
    }
    if (!empty($f->thumb_filename)) {
        $x[] = sprintf("thumb_filename='%s'", DB::escape($f->thumb_filename));
    }
    if (!empty($f->uploader)) {
        $x[] = sprintf("uploader='%s'", DB::escape($f->uploader));
    }

    // create the performer records
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
        $x[] = sprintf("performer_role_ids='%s'", json_encode($perf_role_ids));
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

///////////////// WORK /////////////////

// create DB records for work and its files
//
function make_work($c) {
    global $test;
    [$title, $composer_first, $composer_last] = parse_title($c->json_title);
    $composer_id = get_person($composer_first, $composer_last, true, false);
    if (!empty($c->piece_style)) {
        $period_id = get_period(str_replace('_', ' ', $c->piece_style));
    } else {
        $period_id = 0;
    }

    $json_title = str_replace('_', ' ', $c->json_title);
    if (empty($c->opus_catalogue)) {
        $c->opus_catalogue = '';
    }
    // check for works with same title
    //
    $w = DB_work::lookup(
        sprintf("title='%s'", DB::escape($json_title))
    );
    if ($w) {
        for ($i=2; ; $i++) {
            $title = sprintf('%s (%d)', $json_title, $i);
            echo "DUP FOUND: trying $title\n";
            $w = DB_work::lookup(
                sprintf("title='%s'", DB::escape($title))
            );
            if (!$w) break;
        }
    } else {
        $title = $json_title;
    }
    $q = sprintf("(composer_id, title, opus_catalogue) values (%d, '%s', '%s')",
        $composer_id,
        DB::escape($title),
        DB::escape($c->opus_catalogue)
    );
    if ($test){
        echo "work insert: $q\n";
        $work_id = 1;
    } else {
        $work_id = DB_work::insert($q);
        if (!$work_id) {
            echo "work insert failed\n";
            return;
        }
    }

    $x = [];
    if (!empty($c->alternative_title)) {
        $x[] = sprintf("alternative_title='%s'", DB::escape($c->alternative_title));
    }
    if (!empty($c->attrib)) {
        $x[] = sprintf("attrib='%s'", DB::escape($c->attrib));
    }
    if (!empty($c->authorities)) {
        $x[] = sprintf("authorities='%s'", DB::escape($c->authorities));
    }
    if (!empty($c->average_duration)) {
        $x[] = sprintf("average_duration='%s'", DB::escape($c->average_duration));
    }
    if (!empty($c->comments)) {
        $x[] = sprintf("comments='%s'", DB::escape($c->comments));
    }
    if (!empty($c->dedication)) {
        $x[] = sprintf("dedication='%s'", DB::escape($c->dedication));
    }
    if (!empty($c->discography)) {
        $x[] = sprintf("discography='%s'", DB::escape($c->discography));
    }
    if (!empty($c->external_links)) {
        $x[] = sprintf("external_links='%s'", DB::escape($c->external_links));
    }
    if (!empty($c->extra_information)) {
        $x[] = sprintf("extra_information='%s'", DB::escape($c->extra_information));
    }
    if (!empty($c->first_performance)) {
        $x[] = sprintf("first_performance='%s'", DB::escape($c->first_performance));
    }
    if (!empty($c->incipit)) {
        $x[] = sprintf("incipit='%s'", DB::escape($c->incipit));
    }
    if (!empty($c->instrdetail)) {
        $x[] = sprintf("instrdetail='%s'", DB::escape($c->instrdetail));
    }
    if (!empty($c->instrumentation)) {
        $x[] = sprintf("instrumentation='%s'", DB::escape($c->instrumentation));
    }
    if (!empty($c->key)) {
        $x[] = sprintf("_key='%s'", DB::escape($c->key));
    }
    if (!empty($c->language)) {
        $x[] = sprintf("language='%s'", DB::escape($c->language));
    }
    if (!empty($c->librettist)) {
        $x[] = sprintf("librettist='%s'", DB::escape($c->librettist));
    }
    if (!empty($c->manuscript_sources)) {
        $x[] = sprintf("manuscript_sources='%s'", DB::escape($c->manuscript_sources));
    }
    if (!empty($c->movements_header)) {
        $x[] = sprintf("movements_header='%s'", DB::escape($c->movements_header));
    }
    if (!empty($c->ncrecordings)) {
        $x[] = sprintf("ncrecordings='%s'", DB::escape($c->ncrecordings));
    }
    if (!empty($c->nonpd_eu)) {
        $x[] = sprintf("nonpd_eu=1");
    }
    if (!empty($c->nonpd_us)) {
        $x[] = sprintf("nonpd_us=1");
    }
    if (!empty($c->number_of_movements_sections)) {
        $x[] = sprintf("number_of_movements_sections='%s'", DB::escape($c->number_of_movements_sections));
    }
    if ($period_id) {
        $x[] = sprintf("period_id=%d", $period_id);
    }
    if (!empty($c->related_works)) {
        $x[] = sprintf("related_works='%s'", DB::escape($c->related_works));
    }
    if (!empty($c->searchkey)) {
        $x[] = sprintf("searchkey='%s'", DB::escape($c->searchkey));
    }
    if (!empty($c->searchkey_amarec)) {
        $x[] = sprintf("searchkey_amarec='%s'", DB::escape($c->searchkey_amarec));
    }
    if (!empty($c->searchkey_scores)) {
        $x[] = sprintf("searchkey_scores='%s'", DB::escape($c->searchkey_scores));
    }
    if (!empty($c->tags)) {
        $x[] = sprintf("tags='%s'", DB::escape($c->tags));
    }
    if (!empty($c->year_date_of_composition)) {
        $x[] = sprintf("year_date_of_composition='%s'", DB::escape($c->year_date_of_composition));
        $year = (int)$c->year_date_of_composition;
        if ($year) {
            $x[] = sprintf("year_of_composition=%d", $year);
        }
    }
    if (!empty($c->year_of_first_publication)) {
        $x[] = sprintf("year_of_first_publication='%s'", DB::escape($c->year_of_first_publication));
    }
    if (!empty($c->work_title)) {
        $x[] = sprintf("work_title='%s'", DB::escape($c->work_title));
    }

    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "comp update: $query\n";
        } else {
            $comp = new DB_work;
            $comp->id = $work_id;
            $ret = $comp->update($query);
            if (!$ret) {
                echo "comp update failed\n";
            }
        }
    }

    if (!empty($c->files)) {
        make_score_file_sets($work_id, $c->files);
    }
    if (!empty($c->audios)) {
        make_audio_file_sets($work_id, $c->audios);
    }
}

function main($start_line, $end_line) {
    $f = fopen('david_page_dump.txt', 'r');
    for ($i=0; ; $i++) {
        $x = fgets($f);
        if (!$x) {
            echo "Reached end of file\n";
            break;
        }
        if ($i<$start_line) continue;
        if ($i>=$end_line) continue;
        echo "JSON record $i\n";
        if (!trim($x)) continue;    // skip blank lines
        $y = json_decode($x);
        DB::begin_transaction();
        foreach ($y as $title => $body) {
            $comp = parse_work($title, $body);
            if (empty($comp->imslppage)) {
                // redirect, pop_section, link) work
                continue;
            }
            make_work($comp);
            //break;
        }
        DB::commit_transaction();
    }
}

// there are 3079 lines

main(0, 1);

?>
