<?php

// create or edit a composition

require_once("web.inc");
require_once("imslp_db.inc");
require_once("imslp_util.inc");

function form($comp) {
    if ($comp) {
        page_head("Edit composition $comp->title");
        [$title, $first, $last] = parse_title($comp->title);
        $opus = $comp->opus_catalogue;
    } else {
        page_head("Create composition");
        $title = '';
        $first = '';
        $last = '';
        $opus = '';
    }

    form_start("edit_comp.php");
    form_input_text('Title', 'title', $title);
    form_input_text('Opus/Catalogue', 'opus', $opus);
    if ($comp) {
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
    $comp = DB_composition::lookup_id($id);
    if (!$comp) {
        error_page('no such composition');
    }
    form($comp);
} else {
    form(null);
}

?>
