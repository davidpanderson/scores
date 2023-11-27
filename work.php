<?php
require_once("imslp_db.inc");
require_once("web.inc");
require_once("imslp_web.inc");

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

function show_file_sets($v, $fss, $is_score) {
    if (count($fss) == 1) {
        $fs = $fss[0];
        if (!$v) $v = 'View';
        echo sprintf(
            "<br>%s<font size=+1><a href=%s?id=%d>%s</a></font>\n",
            indent_spaces(2),
            $is_score?'score.php':'recording.php',
            $fs->id, $v
        );
    } else {
        $sp = indent_spaces(2);
        if ($v) {
            echo "<br>$sp<font size=+1>$v</font>\n";
        }
        $sp = indent_spaces(3);
        foreach ($fss as $i=>$fs) {
            $j = $i+1;
            echo sprintf(
                "<br>%s<a href=%s?id=%d>Version %d</a>\n",
                $sp,
                $is_score?'score.php':'recording.php',
                $fs->id, $i+1
            );
        }
    }
}

function indent_spaces($level) {
    $sp = '';
    for ($j=0; $j<$level+1; $j++) {
        $sp .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    }
    return $sp;
}

function font_size($level) {
    switch ($level) {
    case -1: return '+4';
    case 0: return '+3';
    case 1: return '+2';
    case 2: return '+1';
    }
}

// show values in this order, followed by others
//
$hier_vals = [
    ['', 'Parts', 'Scores and Parts', 'Arrangements and Transcriptions', 'Other'],
    ['', 'Complete'],
    ['']
];

function show_files_hier($x, $level, $is_score) {
    global $hier_vals;
    $vals = $hier_vals[$level];

    $sp = indent_spaces($level);

    // show values in list (see above)
    //
    foreach ($vals as $v) {
        if (!array_key_exists($v, $x)) {
            continue;
        }
        if ($level == 2) {
            $w = $v;
            if (!$is_score && !$v) $w = 'Original instrumentation';
            show_file_sets($w, $x[$v], $is_score);
        } else {
            $sp = indent_spaces($level);
            $w = $v;
            if ($level==1 && !$v) $w = 'Complete';
            if ($w) {
                echo sprintf("<br>%s<font size=%s>%s</font>\n",
                    $sp, font_size($level), $w
                );
            }
            show_files_hier($x[$v], $level+1, $is_score);
        }
    }

    // show values not in the list
    //
    foreach ($x as $v=>$list) {
        if (in_array($v, $vals)) continue;
        if ($level == 2) {
            show_file_sets($v, $list, $is_score);
        } else {
            $sp = indent_spaces($level);
            if ($v) {
                echo sprintf("<br>%s<font size=%s>%s</font>\n",
                    $sp, font_size($level), $v
                );
            }
            show_files_hier($list, $level+1, $is_score);
        }
    }
}

function show_score_files($cid) {
    $fss = DB_score_file_set::enum("work_id=$cid");
    if (!$fss) return;
    echo sprintf("<p><font size=%s>Scores</font>\n", font_size(-1));
    $x1 = sort_file_sets($fss);
    show_files_hier($x1, 0, true);
}

function show_audio_files($cid) {
    $fss = DB_audio_file_set::enum("work_id=$cid");
    if (!$fss) return;
    echo sprintf("<p><font size=%s>Recordings</font>\n", font_size(-1));
    $x1 = sort_file_sets($fss);
    show_files_hier($x1, 0, false);
}

function main($id) {
    $c = DB_work::lookup_id($id);
    if (!$c) {
        error_page('no such work');
    }
    page_head("Work: $c->title");
    echo "<p>";
    show_work_detail($c);
    //show_button("edit_work.php?id=$id", 'Edit work');
    //show_button("edit_score.php?work_id=$id", 'Add score file');
    //show_button("edit_audio.php?work_id=$id", 'Add audio file');
    echo "<hr>";
    show_score_files($c->id);
    show_audio_files($c->id);
    page_tail();
}

$id = get_int('id');
main($id);

?>
