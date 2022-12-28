<?php

// create or edit a work

require_once("web.inc");
require_once("imslp_db.inc");
require_once("imslp_util.inc");

function form($work) {
    if ($work) {
        page_head("Edit work $work->title");
        [$title, $first, $last] = parse_title($work->title);
        $opus = $work->opus_catalogue;
    } else {
        page_head("Create work");
        $title = '';
        $first = '';
        $last = '';
        $opus = '';
    }

    form_start("edit_work.php");
    form_input_text('Title', 'title', $title);
    form_input_text('Opus/Catalogue', 'opus', $opus);
    if ($work) {
        form_submit('Update');
    } else {
        form_submit('Create');
    }
    form_end();
    page_tail();
}

function create() {
}

function update() {
}

$id = get_int('id', true);
if ($id) {
    $work = DB_work::lookup_id($id);
    if (!$work) {
        error_page('no such work');
    }
    form($work);
} else {
    form(null);
}

?>
