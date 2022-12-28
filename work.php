<?php
require_once("imslp_db.inc");
require_once("web.inc");

// $fss is a list of file sets (score or audio);
// each one has fields hier1, hier2, and hier3.
// Organize file sets into a hierarchical data structure -
// a list of lists of lists of lists - based on these values.
//
function sort_file_sets($fss) {
    $x1 = [];
    foreach ($fss as $fs) {
        if (array_key_exists($fs->hier1, $x1)) {
            $x2 = $x1[$fs->hier1];
            if (array_key_exists($fs->hier2, $x2)) {
                $x3 = $x2[$fs->hier2];
                if (array_key_exists($fs->hier3, $x3)) {
                    array_push($x3[$fs->hier3], $fs);
                } else {
                    $x3[$fs->hier3] = [$fs];
                }
            } else {
                $x3 = [$fs->hier3=>[$fs]];
            }
            // NOTE: x3 is a copy; need to assign to x2
            //
            $x2[$fs->hier2] = $x3;
        } else {
            $x3 = [$fs->hier3=>[$fs]];
            $x2 = [$fs->hier2=>$x3];
        }
        // same
        //
        $x1[$fs->hier1] = $x2;
    }
    return $x1;
}

function copyright($id) {
    $c = DB_copyright::lookup_id($id);
    return $c->name;
}

function show_score_file_set($fs) {
    echo "<hr><a name=sfs_$fs->id></a>";
    start_table('table-striped');
    if ($fs->amazon) row2("Amazon", $fs->amazon);
    if ($fs->arranger) row2("Arranger", $fs->arranger);
    if ($fs->copyright_id) row2("Copyright", copyright($fs->copyright_id));
    if ($fs->date_submitted) row2("Date Submitted", $fs->date_submitted);
    if ($fs->editor) row2("Editor", $fs->editor);
    if ($fs->engraver) row2("Engraver", $fs->engraver);
    if ($fs->file_tags) row2("File Tags", $fs->file_tags);
    if ($fs->image_type) row2("Image Type", $fs->image_type);
    if ($fs->misc_notes) row2("Misc. Notes", $fs->misc_notes);
    if ($fs->publisher_information) row2("Publisher Information", $fs->publisher_information);
    if ($fs->reprint) row2("Reprint", $fs->reprint);
    if ($fs->sample_filename) row2("Sample Filename", $fs->sample_filename);
    if ($fs->scanner) row2("Scanner", $fs->scanner);
    if ($fs->sm_plus) row2("SM+", $fs->sm_plus);
    if ($fs->thumb_filename) row2("Thumb Filename", $fs->thumb_filename);
    if ($fs->translator) row2("Translator", $fs->translator);
    if ($fs->uploader) row2("Uploader", $fs->uploader);

    $files = DB_score_file::enum("score_file_set_id=$fs->id");
    $x = [];
    foreach ($files as $f) {
        $x[] = sprintf('<a href="https://imslp.org/wiki/File:%s">%s</a>',
            $f->file_name,
            $f->file_description
        );
        // TODO: show per-file info
    }
    row2('Files', implode('<br>', $x));
    end_table();
}

function show_audio_file_set($fs) {
    echo "<hr><a name=afs_$fs->id></a>";
    start_table('table-striped');
    if ($fs->copyright_id) row2("Copyright", copyright($fs->copyright_id));
    if ($fs->date_submitted) row2("Date Submitted", $fs->date_submitted);
    if ($fs->misc_notes) row2("Misc. Notes", $fs->misc_notes);
    if ($fs->performer_categories) row2("Performer Categories", $fs->performer_categories);
    if ($fs->performers) row2("Performers", $fs->performers);
    if ($fs->publisher_information) row2("Publisher Information", $fs->publisher_information);
    if ($fs->uploader) row2("Uploader", $fs->uploader);

    $files = DB_audio_file::enum("audio_file_set_id=$fs->id");
    $x = [];
    foreach ($files as $f) {
        $x[] = sprintf('<a href="https://imslp.org/wiki/File:%s">%s</a>',
            $f->file_name,
            $f->file_description
        );
        // TODO: show per-file info
    }
    row2('Files', implode('<br>', $x));
    end_table();
}

function show_file_sets($fss, $is_score) {
    foreach ($fss as $fs) {
        if ($is_score) {
            show_score_file_set($fs);
        } else {
            show_audio_file_set($fs);
        }
    }
}

// show values in this order, followed by others
//
$hier_vals = [
    ['', 'Parts', 'Arrangements and Transcriptions', 'Other'],
    ['', 'Complete'],
    ['']
];

function show_files_hier($x, $level, $is_score) {
    global $hier_vals;
    $vals = $hier_vals[$level];

    $sp = '';
    for ($j=0; $j<$level; $j++) {
        $sp .= "&nbsp;&nbsp;&nbsp;";
    }

    // show values in list (see above)
    //
    foreach ($vals as $v) {
        if (!array_key_exists($v, $x)) {
            continue;
        }
        if ($v) {
            echo "<h3>$sp $v</h3>\n";
        }
        if ($level == 2) {
            show_file_sets($x[$v], $is_score);
        } else {
            show_files_hier($x[$v], $level+1, $is_score);
        }
    }

    // show values not in the list
    //
    foreach ($x as $v=>$list) {
        if (in_array($v, $vals)) continue;
        if ($v) {
            echo "<h3>$sp $v</h3>\n";
        }
        if ($level == 2) {
            show_file_sets($list, $is_score);
        } else {
            show_files_hier($list, $level+1, $is_score);
        }
    }
}

function show_score_files($cid) {
    $fss = DB_score_file_set::enum("work_id=$cid");
    if (!$fss) return;
    echo "<h2>Score files</h2>\n";
    $x1 = sort_file_sets($fss);
    show_files_hier($x1, 0, true);
}

function show_audio_files($cid) {
    $fss = DB_audio_file_set::enum("work_id=$cid");
    if (!$fss) return;
    echo "<h2>Audio files</h2>\n";
    $x1 = sort_file_sets($fss);
    show_files_hier($x1, 0, false);
}

function main($id) {
    $c = DB_work::lookup_id($id);
    if (!$c) {
        error_page('no such work');
    }
    page_head("$c->title");
    echo "<p>";
    start_table('table-striped');
    $composer = DB_person::lookup_id($c->composer_id);
    $name = "$composer->first_name $composer->last_name";
    row2('Composer', "<a href=composer.php?id=$composer->id>$name</a>");
    if ($c->opus_catalogue) {
        row2('Opus', $c->opus_catalogue);
    }
    if ($c->_key) {
        row2('Key', $c->_key);
    }
    if ($c->movements_header) {
        row2('Movements', $c->movements_header);
    }
    if ($c->year_date_of_composition) {
        row2('Composition date', $c->year_date_of_composition);
    }
    if ($c->year_of_first_publication) {
        row2('Publication date', $c->year_of_first_publication);
    }
    if ($c->instrumentation) {
        row2('Instrumentation', $c->instrumentation);
    }
    end_table();
    show_button("edit_work.php?id=$id", 'Edit work');
    show_button("edit_score.php?work_id=$id", 'Add score file');
    show_button("edit_audio.php?work_id=$id", 'Add audio file');
    echo "<hr>";
    show_score_files($c->id);
    show_audio_files($c->id);
    page_tail();
}

$id = get_int('id');
main($id);

?>
