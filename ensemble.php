<?php

require_once("web.inc");
require_once("imslp_db.inc");
require_once("imslp_web.inc");

function ensemble_page($e) {
    page_head("$e->name");
    show_ensemble_detail($e);
    echo "<h2>Performances</h2>\n";
    $afs = DB_audio_file_set::enum("ensemble_id=$e->id");
    start_table();
    row_heading_array(['Composition', 'Categories']);
    foreach ($afs as $af) {
        $work_id = $af->work_id;
        $work = DB_work::lookup_id($work_id);
        row_array([
            "<a href=work.php?id=$work_id#afs_$af->id>$work->title</a>",
            hier_string($af)
        ]);
    }
    end_table();
    page_tail();
}

$id = get_int('id', true);
$e = DB_ensemble::lookup_id($id);
if (!$e) error_page('no such ensemble');
ensemble_page($e);

?>
