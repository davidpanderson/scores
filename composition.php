<?php
require_once("imslp.inc");
require_once("imslp_db.inc");

// sort file sets into a hierarchical data structure
//
function sort_file_sets($fss) {
    $x1 = [];
    foreach ($fss as $fs) {
        if (array_key_exists($fs->hier1, $x1)) {
            $x2 = $x1[$fs->hier1];
            if (array_key_exists($fs->hier2, $x2)) {
                $x3 = $x2[$fs->hier2];
                if (array_key_exists($fs->hier3, $x3)) {
                    $x3[$fs->hier3][] = $fs;
                } else {
                    $x3[$fs->hier3] = [$fs];
                }
            } else {
                $x3 = [$fs->hier3=>$fs];
                $x2[$fs->hier2] = $x3;
            }
        } else {
            $x3 = [$fs->hier3=>[$fs]];
            $x2 = [$fs->hier2=>$x3];
            $x1[$fs->hier1] = $x2;
        }
    }
    return $x1;
}

function show_file_set($fs) {
    echo "$fs->id";
    $files = DB_score_file::enum("score_file_set_id=$fs->id");
    foreach ($files as $f) {
        start_table('table-striped');
        row2('Name', $f->name);
        if ($f->description) {
            row2('Description', $f->description);
        }
        end_table();
    }
}

function show_file_sets($fss) {
    foreach ($fss as $fs) {
        show_file_set($fs);
    }
}

// show values in this order, followed by others
//
$hier_vals = [
    ['', 'Parts', 'Arrangements and Transcriptions', 'Other'],
    ['', 'Complete'],
    ['']
];

function show_files_hier($x, $level) {
    global $hier_vals;
    $vals = $hier_vals[$level];
    foreach ($vals as $v) {
        if (!array_key_exists($v, $x)) {
            continue;
        }
        if ($v) {
            echo "<h2>$v</h2>\n";
        }
        if ($level == 2) {
            show_file_sets($x[$v]);
        } else {
            show_files_hier($x[$v], $level+1);
        }
    }
    foreach ($x as $val=>$list) {
        if (in_array($val, $vals)) continue;
        if ($v) {
            echo "<h2>$v</h2>\n";
        }
        if ($level == 2) {
            show_files($x);
        } else {
            show_files_hier($x, $level+1);
        }
    }
}

function show_score_files($cid) {
    $fss = DB_score_file_set::enum("composition_id=$cid");
    if (!$fss) return;
    echo "<h2>Score files</h2>\n";
    $x1 = sort_file_sets($fss);
    show_files_hier($x1, 0);
}

function show_audio_files($cid) {
}

function main($id) {
    $c = DB_composition::lookup_id($id);
    if (!$c) {
        error_page('no such composition');
    }
    page_head("$c->title");
    start_table('table-striped');
    $composer = DB_person::lookup_id($c->composer_id);
    $name = "$composer->first_name $composer->last_name";
    row2('Composer', "<a href=composer.php?id=$composer->id>$name</a>");
    if ($c->opus) {
        row2('Opus', $c->opus);
    }
    if ($c->_key) {
        row2('Key', $c->_key);
    }
    if ($c->movement_names) {
        row2('Movements', $c->movement_names);
    }
    if ($c->composition_date) {
        row2('Composition date', $c->composition_date);
    }
    if ($c->publication_date) {
        row2('Publication date', $c->publication_date);
    }
    if ($c->instrumentation) {
        row2('Instrumentation', $c->instrumentation);
    }
    end_table();
    show_score_files($c->id);
    page_tail();
}

$id = get_int('id');
main($id);

?>
