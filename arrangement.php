<?php

// show works that have been arranged for other instruments

require_once("web.inc");
require_once("imslp_db.inc");

// show a list of all arrangement targets
//
function target_list() {
    page_head("Arrangements");
    $ats = DB_arrangement_target::enum();
    start_table();
    row_heading_array(['Instruments']);
    foreach ($ats as $at) {
        row_array([
            "<a href=arrangement.php?id=$at->id>$at->instruments</a>"
        ]);
    }
    end_table();
    page_tail();
}

// show the works with arrangements for a particular target
//
function target_works($at) {
    page_head("Arrangements for $at->instruments");
    $fss = DB_score_file_set::enum("arrangement_target_id=$at->id");
    start_table();
    row_heading_array([
        "Work", "Arrangement", "Selection"
    ]);
    foreach ($fss as $fs) {
        $comp = DB_work::lookup_id($fs->work_id);
        row_array([
            sprintf("<a href=work.php?id=%d#sfs_%d>%s</a>",
                $comp->id, $fs->id, $comp->title
            ),
            $fs->hier3,
            $fs->hier2
        ]);
    }
    page_tail();
}

$id = get_int('id', true);
if ($id) {
    $at = DB_arrangement_target::lookup_id($id);
    if (!$at) error_page('no such arrangement target');
    target_works($at);
} else {
    target_list();
}

?>
