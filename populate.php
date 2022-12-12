<?php

// functions for creating database entries,
// given the data structures passed from parse.php

require_once("mediawiki.inc");
require_once("parse.inc");
require_once("imslp_db.inc");

$test = true;

// look up person, create if not there.
// return ID
//
function get_person($first, $last) {
    global $test;
    $p = IMSLP_person::lookup(
        sprintf("first_name='%s' and last_name='%s'", $first, $last)
    );
    if ($p) return $p->id;
    $q = sprintf("(first_name, last_name) values ('%s', '%s')", $first, $last);
    if ($test) {
        echo "person insert: $q\n";
        $id = 0;
    } else {
        $id = IMSLP_person::insert($q);
    }
    return $id;
}

function get_license($name) {
    global $test;
    $p = IMSLP_license::lookup(
        sprintf("name='%s'", $name)
    );
    if ($p) return $p->id;
    $q = sprintf("(name) values ('%s')", $name);
    if ($test) {
        echo "license insert: $q\n";
        $id = 0;
    } else {
        $id = IMSLP_license::insert($q);
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

function make_score_file($f, $i, $file_set_id) {
    global $test;
    if (array_key_exists($i+1, $f->uploaders)) {
        $uploader = $f->uploaders[$i+1];
    } else {
        if (empty($f->uploader)) {
            echo "no uploader\n";
            print_r($f);
        }
        $uploader = $f->uploader;
    }
    $q = sprintf(
        "(score_file_set_id, name, description) values (%d, '%s', '%s')",
        $file_set_id, $f->file_names[$i], $f->file_descs[$i]
    );
    if ($test) {
        echo "score_file insert: $q\n";
    } else {
        $id = IMSLP_score_file::insert($q);
    }
}

function make_score_file_set($cid, $f, $hier) {
    global $test;
    $q = sprintf(
        "(composition_id, hier1, hier2, hier3) values (%d, '%s' '%s', '%s')",
        $cid,
        $hier[0], $hier[1], $hier[2]
    );
    if ($test) {
        echo "file set insert: $q\n";
        $file_set_id = 1;
    } else {
        $file_set_id = IMSLP_score_file_set::insert($q);
    }
    $x = [];
    if (!empty($f->arrangement_name)) {
        $x[] = sprintf("arrangement_name='%s'", $f->arrangement_name);
    }
    if (!empty($f->editor_id)) {
        $x[] = sprintf("editor_id=%d", $editor_id);
    }
    if (!empty($f->image_type)) {
        $x[] = sprintf("image_type='%s'", $f->image_type);
    }
    if (!empty($f->publisher_info)) {
        $x[] = sprintf("publisher_info='%s'", $f->publisher_info);
    }
    if (!empty($f->license_id)) {
        $x[] = sprintf("license_id=%d", $license_id);
    }
    if (!empty($f->misc_notes)) {
        $x[] = sprintf("misc_notes='%s'", $f->misc_notes);
    }
    if (!empty($f->amazon_info)) {
        $x[] = sprintf("amazon_info='%s'", $f->amazon_info);
    }
    if (!empty($f->arranger)) {
        $x[] = sprintf("arranger='%s'", $f->arranger);
    }
    if (!empty($f->translator)) {
        $x[] = sprintf("translator='%s'", $f->translator);
    }
    if (!empty($f->sm_plus)) {
        $x[] = sprintf("sm_plus='%s'", $f->sm_plus);
    }
    if (!empty($f->reprint)) {
        $x[] = sprintf("reprint='%s'", $f->reprint);
    }
    if (!empty($f->engraver)) {
        $x[] = sprintf("engraver='%s'", $f->engraver);
    }
    if (!empty($f->file_tags)) {
        $x[] = sprintf("file_tags='%s'", $f->file_tags);
    }
    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "file set update: $query\n";
        } else {
            $x = new IMSLP_score_file_set;
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

function make_audio_file($f, $i, $file_set_id) {
    global $test;
    if (array_key_exists($i+1, $f->uploaders)) {
        $uploader = $f->uploaders[$i+1];
    } else {
        if (empty($f->uploader)) {
            echo "no uploader\n";
            print_r($f);
        }
        $uploader = $f->uploader;
    }
    $q = sprintf(
        "(audio_file_set_id, name, description) values (%d, '%s', '%s')",
        $file_set_id, $f->file_names[$i], $f->file_descs[$i]
    );
    if ($test) {
        echo "audio_file insert: $q\n";
    } else {
        $id = IMSLP_audio_file::insert($q);
    }
}

function make_audio_set($cid, $f, $hier) {
    global $test;
    $q = sprintf(
        "(composition_id, hier1, hier2, hier3) values (%d, '%s' '%s', '%s')",
        $cid,
        $hier[0], $hier[1], $hier[2]
    );
    if ($test) {
        echo "audio set insert: $q\n";
        $file_set_id = 1;
    } else {
        $file_set_id = IMSLP_audio_file_set::insert($q);
    }
    $x = [];
    if (!empty($f->publisher_info)) {
        $x[] = sprintf("publisher_info='%s'", $f->publisher_info);
    }
    if (!empty($f->copyright)) {
        $license_id = get_license($f->copyright);
        $x[] = sprintf("license_id=%d", $license_id);
    }
    if (!empty($f->misc_notes)) {
        $x[] = sprintf("misc_notes='%s'", $f->misc_notes);
    }
    if (!empty($f->arranger)) {
        $x[] = sprintf("arranger='%s'", $f->arranger);
    }
    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "file set update: $query\n";
        } else {
            $x = new IMSLP_audio_file_set;
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

// create DB records for composition and its files
//
function make_composition($c) {
    $composer_id = get_person($c->composer_first, $c->composer_last);
    global $test;

    $q = sprintf("(composer_id, title, opus) values (%d, '%s', '%s')",
        $composer_id, $c->title, $c->opus
    );
    if ($test){
        echo "composition insert: $q\n";
        $composition_id = 1;
    } else {
        $composition_id = IMSLP_composition::insert($q);
    }

    if (!$composition_id) {
        echo "insert failed for $composer_id, $c->title, $c->opus\n";
        return;
    }
    $x = [];
    if (!empty($c->alternative_title)) {
        $x[] = sprintf("alternative_title='%s'", $c->alternative_title);
    }
    if (!empty($c->key)) {
        $x[] = sprintf("key='%s'", $c->key);
    }
    if (!empty($c->nmovements)) {
        $x[] = sprintf("nmovements=%d", $c->nmovements);
    }
    if (!empty($c->movement_names)) {
        $x[] = sprintf("movement_names='%s'", $c->movement_names);
    }
    if (!empty($c->incipit)) {
        $x[] = sprintf("incipit='%s'", $c->incipit);
    }
    if (!empty($c->dedication)) {
        $x[] = sprintf("dedication='%s'", $c->dedication);
    }
    if (!empty($c->composition_date)) {
        $x[] = sprintf("composition_date='%s'", $c->composition_date);
    }
    if (!empty($c->first_performance)) {
        $x[] = sprintf("first_performance='%s'", $c->first_performance);
    }
    if (!empty($c->publication_date)) {
        $x[] = sprintf("publication_date='%s'", $c->publication_date);
    }
    if (!empty($c->average_dur_min)) {
        $x[] = sprintf("average_dur_min=%d", $c->average_dur_min);
    }

    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "comp update: $query\n";
        } else {
            $comp = new IMSLP_composition;
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
        make_audio_file_sets($composition_id, $c->files);
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
            make_composition($comp);
        }
    }
}

main(1);

?>
