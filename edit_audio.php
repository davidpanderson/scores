<?php

require_once("imslp_db.inc");
require_once("web.inc");

function form() {
    $comp_id = get_int('comp_id');
    $comp = DB_composition::lookup_id($comp_id);
    if (!$comp) error_page('No such composition');
    $file_id = get_int('file_id', true);
    if ($file_id) {
        $file = DB_score_file::lookup_id($file_id);
        if (!$file) error_page('No such audio file set');
        page_head('Edit file set');
    } else {
        $file = null;
        page_head('Create audio set');
    }
    page_tail();
}

form();

?>
