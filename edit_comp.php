<?php

// create or edit a composition

require_once("imslp.inc");

function form($comp) {
    if ($comp) {
        page_head("Edit composition");
    } else {
        page_head("Create composition");
    }
    form_start("edit_comp.php");
    form_input_text('Title<br><small>Name, Opus (Last, First)</small>', 'title', $comp?$comp->title:'');
    form_end();
    page_tail();
}

function create() {
}

function update() {
}

form(null);

?>
