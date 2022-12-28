<?php

require_once("web.inc");
require_once("imslp_db.inc");

function form() {
    $work_id = get_int('work_id');
    $work = DB_work::lookup_id($work_id);
    if (!$work) error_page('No such work');
    $file_id = get_int('file_id', true);
    if ($file_id) {
        $file = DB_score_file::lookup_id($file_id);
        if (!$file) error_page('No such score file');
        page_head('Edit file set');
    } else {
        $file = null;
        page_head('Create file set');
    }
    page_tail();
}

form();

?>
