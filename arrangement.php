<?php

// show compositions that have been arranged for other instruments

require_once("web.inc");
require_once("imslp_db.inc");

// show a list of all arrangement targets
//
function target_list() {
    page_head("Arrangements");
    $ats = DB_arrangement_target::enum('');
    start_table('table-striped');
    row_heading_array(['Instruments']);
    foreach ($ats as $at) {
        row_array([
            "<a href=arrangement.php?id=$at->id>$at->instruments</a>"
        ]);
    }
    end_table();
    page_tail();
}

// show the compositions with arrangements for a particular target
//
function target_comps($at) {
    page_head("Arrangements for $at->instruments");
    $fss = DB_score_file_set::enum("arrangement_target_id=$at->id");
    start_table('table-striped');
    row_heading_array([
        "Composition", "Arrangement", "Selection"
    ]);
    foreach ($fss as $fs) {
        $comp = DB_composition::lookup_id($fs->composition_id);
        row_array([
            sprintf("<a href=composition.php?id=%d#sfs_%d>%s</a>",
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
    target_comps($at);
} else {
    target_list();
}

?>
