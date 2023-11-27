<?php

require_once("imslp_db.inc");
require_once("web.inc");
require_once("imslp_web.inc");

function main($fs) {
    $work = DB_work::lookup_id($fs->work_id);
    page_head("Recording");
    audio_file_set_detail($fs, $work);
    page_tail();
}

$id = get_int('id');
$fs = DB_audio_file_set::lookup_id($id);
if (!$fs) error_page('no such recording');
main($fs);

?>
