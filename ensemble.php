<?php

require_once("imslp_db.inc");
require_once("imslp_web.inc");

function ensemble_list() {
    page_head("Ensembles");
    $es = DB_ensemble::enum('');
    start_table('table-striped');
    row_heading_array(['Name', 'Type']);
    foreach ($es as $e) {
        row_array([
            "<a href=ensemble.php?id=$e->id>$e->name</a>",
            $e->type
        ]);
    }
    end_table();
    page_tail();
}

function hier_string($h1, $h2, $h3) {
    $x = [];
    if ($h1) $x[]=$h1;
    if ($h2) $x[]=$h2;
    if ($h3) $x[]=$h3;
    return implode('/', $x);
}

function ensemble_page($e) {
    page_head("$e->name");
    $afs = DB_audio_file_set::enum("ensemble_id=$e->id");
    start_table('table-striped');
    row_heading_array(['Composition', 'Categories']);
    foreach ($afs as $af) {
        $compid = $af->composition_id;
        $comp = DB_composition::lookup_id($compid);
        row_array([
            "<a href=composition.php?id=$compid#afs_$af->id>$comp->title</a>",
            hier_string($af->hier1, $af->hier2, $af->hier3)
        ]);
    }
    end_table();
    page_tail();
}

$id = get_int('id', true);
if ($id) {
    $e = DB_ensemble::lookup_id($id);
    if (!$e) error_page('no such ensemble');
    ensemble_page($e);
} else {
    ensemble_list();
}

?>
