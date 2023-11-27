<?php

require_once("imslp_db.inc");
require_once("web.inc");
require_once("imslp_web.inc");

function main($fs) {
    $work = DB_work::lookup_id($fs->work_id);
    page_head("Score: <a href=work.php?id=$work->id>$work->title</a>");
    score_file_set_detail($fs);
    page_tail();
}

$id = get_int('id');
$fs = DB_score_file_set::lookup_id($id);
if (!$fs) error_page('no such score');
main($fs);

?>
