<?php

// functions for creating database entries,
// given the PHP data structures passed from parse.php

require_once("mediawiki.inc");
require_once("parse.inc");
require_once("imslp_db.inc");

$test = false;

// look up person (e.g. composer), create if not there.
// return ID
//
function get_person($first, $last) {
    global $test;
    $p = DB_person::lookup(
        sprintf("first_name='%s' and last_name='%s'",
            DB::escape($first),
            DB::escape($last)
        )
    );
    if ($p) return $p->id;
    $q = sprintf("(first_name, last_name) values ('%s', '%s')",
        DB::escape($first),
        DB::escape($last)
    );
    if ($test) {
        echo "person insert: $q\n";
        $id = 0;
    } else {
        $id = DB_person::insert($q);
        if (!$id) {
            echo "person insert failed\n";
            exit;
        }
    }
    return $id;
}

function get_license($name) {
    global $test;
    $p = DB_license::lookup(
        sprintf("name='%s'",
            DB::escape($name)
        )
    );
    if ($p) return $p->id;
    $q = sprintf("(name) values ('%s')",
        DB::escape($name)
    );
    if ($test) {
        echo "license insert: $q\n";
        $id = 0;
    } else {
        $id = DB_license::insert($q);
        if (!$id) {
            echo "license insert failed\n";
            exit;
        }
    }
    return $id;
}

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
    if (array_key_exists($i, $f->uploaders)) {
        $uploader = $f->uploaders[$i];
    } else {
        if (empty($f->uploader)) {
            echo "no uploader\n";
            print_r($f);
        }
        $uploader = $f->uploader;
    }
    $q = sprintf(
        "(score_file_set_id, name, description) values (%d, '%s', '%s')",
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
            exit;
        }
    }
}

function make_score_file_set($cid, $f, $hier) {
    global $test;
    $q = sprintf(
        "(composition_id, hier1, hier2, hier3) values (%d, '%s', '%s', '%s')",
        $cid,
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
            echo "score_file insert failed\n";
            exit;
        }
    }
    $x = [];
    if (!empty($f->arrangement_name)) {
        $x[] = sprintf("arrangement_name='%s'", DB::escape($f->arrangement_name));
    }
    if (!empty($f->editor_id)) {
        $x[] = sprintf("editor_id=%d", $editor_id);
    }
    if (!empty($f->image_type)) {
        $x[] = sprintf("image_type='%s'", DB::escape($f->image_type));
    }
    if (!empty($f->publisher_info)) {
        $x[] = sprintf("publisher_info='%s'", DB::escape($f->publisher_info));
    }
    if (!empty($f->license_id)) {
        $x[] = sprintf("license_id=%d", $license_id);
    }
    if (!empty($f->misc_notes)) {
        $x[] = sprintf("misc_notes='%s'", DB::escape($f->misc_notes));
    }
    if (!empty($f->amazon_info)) {
        $x[] = sprintf("amazon_info='%s'", DB::escape($f->amazon_info));
    }
    if (!empty($f->arranger)) {
        $x[] = sprintf("arranger='%s'", DB::escape($f->arranger));
    }
    if (!empty($f->translator)) {
        $x[] = sprintf("translator='%s'", DB::escape($f->translator));
    }
    if (!empty($f->sm_plus)) {
        $x[] = sprintf("sm_plus='%s'", DB::escape($f->sm_plus));
    }
    if (!empty($f->reprint)) {
        $x[] = sprintf("reprint='%s'", DB::escape($f->reprint));
    }
    if (!empty($f->engraver)) {
        $x[] = sprintf("engraver='%s'", DB::escape($f->engraver));
    }
    if (!empty($f->file_tags)) {
        $x[] = sprintf("file_tags='%s'", DB::escape($f->file_tags));
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

// create file sets for a composition
// $cid is a composition ID
// $files is a list of strings and file objects
//
function make_score_file_sets($cid, $files) {
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
            make_score_file_set($cid, $item, $hier);
        }
    }
}

///////////////// AUDIO FILES /////////////////

function make_audio_file($f, $i, $file_set_id) {
    global $test;
    if (array_key_exists($i, $f->uploaders)) {
        $uploader = $f->uploaders[$i];
    } else {
        if (empty($f->uploader)) {
            echo "no uploader\n";
            print_r($f);
        }
        $uploader = $f->uploader;
    }
    $q = sprintf(
        "(audio_file_set_id, name, description) values (%d, '%s', '%s')",
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
            exit;
        }
    }
}

function make_audio_set($cid, $f, $hier) {
    global $test;
    $q = sprintf(
        "(composition_id, hier1, hier2, hier3) values (%d, '%s', '%s', '%s')",
        $cid,
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
            exit;
        }
    }
    $x = [];
    if (!empty($f->publisher_info)) {
        $x[] = sprintf("publisher_info='%s'", DB::escape($f->publisher_info));
    }
    if (!empty($f->copyright)) {
        $license_id = get_license($f->copyright);
        $x[] = sprintf("license_id=%d", $license_id);
    }
    if (!empty($f->misc_notes)) {
        $x[] = sprintf("misc_notes='%s'", DB::escape($f->misc_notes));
    }
    if (!empty($f->arranger)) {
        $x[] = sprintf("arranger='%s'", DB::escape($f->arranger));
    }
    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "file set update: $query\n";
        } else {
            $x = new DB_audio_file_set;
            $x->id = $file_set_id;
            $x->update($query);
        }
    }

    // create the audio records
    //
    $n = count($f->file_names);
    for ($i=0; $i<$n; $i++) {
        make_audio_file($f, $i, $file_set_id);
    }
}

// $cid is a composition ID
// $audios is a list of strings and audio objects
//
function make_audio_file_sets($cid, $audios) {
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
            make_audio_set($cid, $item, $hier);
        }
    }
}

///////////////// COMPOSITIONS /////////////////

// create DB records for composition and its files
//
function make_composition($c) {
    $composer_id = get_person($c->composer_first, $c->composer_last);
    global $test;

    $q = sprintf("(composer_id, title, opus) values (%d, '%s', '%s')",
        $composer_id,
        DB::escape($c->title),
        DB::escape($c->opus)
    );
    if ($test){
        echo "composition insert: $q\n";
        $composition_id = 1;
    } else {
        $composition_id = DB_composition::insert($q);
        if (!$composition_id) {
            echo "composition insert failed\n";
            exit;
        }
    }

    $x = [];
    if (!empty($c->alternative_title)) {
        $x[] = sprintf("alternative_title='%s'", DB::escape($c->alternative_title));
    }
    if (!empty($c->key)) {
        $x[] = sprintf("_key='%s'", DB::escape($c->key));
    }
    if (!empty($c->nmovements)) {
        $x[] = sprintf("nmovements=%d", $c->nmovements);
    }
    if (!empty($c->movement_names)) {
        $x[] = sprintf("movement_names='%s'", DB::escape($c->movement_names));
    }
    if (!empty($c->incipit)) {
        $x[] = sprintf("incipit='%s'", DB::escape($c->incipit));
    }
    if (!empty($c->dedication)) {
        $x[] = sprintf("dedication='%s'", DB::escape($c->dedication));
    }
    if (!empty($c->composition_date)) {
        $x[] = sprintf("composition_date='%s'", DB::escape($c->composition_date));
    }
    if (!empty($c->first_performance)) {
        $x[] = sprintf("first_performance='%s'", DB::escape($c->first_performance));
    }
    if (!empty($c->publication_date)) {
        $x[] = sprintf("publication_date='%s'", DB::escape($c->publication_date));
    }
    if (!empty($c->average_dur_min)) {
        $x[] = sprintf("average_dur_min=%d", $c->average_dur_min);
    }

    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "comp update: $query\n";
        } else {
            $comp = new DB_composition;
            $comp->id = $composition_id;
            $ret = $comp->update($query);
            if (!$ret) {
                echo "comp update failed\n";
            }
        }
    }

    if ($c->files) {
        make_score_file_sets($composition_id, $c->files);
    }
    if ($c->audios) {
        make_audio_file_sets($composition_id, $c->audios);
    }
}

function main($nlines) {
    $f = fopen('david_page_dump.txt', 'r');
    for ($i=0; $i<$nlines; $i++) {
        $x = fgets($f);
        if (!$x) break;
        $y = json_decode($x);
        foreach ($y as $title => $body) {
            $comp = parse_composition($title, $body);
            if (!empty($comp->redirect)) {
                // TODO - link to other composition
                continue;
            }
            make_composition($comp);
            break;
        }
    }
}

main(1);

?>
