<?php

require_once("web.inc");
require_once("imslp_web.inc");
require_once("ser.inc");

define('RESULTS_PER_PAGE', 50);

function limit_clause($offset) {
    return sprintf('limit %d, %d', $offset, RESULTS_PER_PAGE);
}

function next_link($action, $id, $offset) {
    echo sprintf("<p><a href=tags.php?action=%s&id=%d&offset=%d>Next %d</a>",
        $action, $id, $offset+RESULTS_PER_PAGE, RESULTS_PER_PAGE
    );
}

function wt_form() {
    $wts = work_types();
    page_head("Work types");
    $items = [];
    foreach ($wts as $wt) {
        if ($wt->nworks == 0) continue;
        $items[] = sprintf(
            "<a href=tags.php?action=wt_action&id=%d>%s</a> (%d)",
            $wt->id,
            $wt->name,
            $wt->nworks
        );
    }
    show_items_cols($items, 5);
    page_tail();
}

function wt_action($id, $offset) {
    $wts = work_types();
    page_head("Work type: ".$wts[$id]->name);
    $works = DB_work::enum(
        "$id member of (work_type_ids->'$')",
        limit_clause($offset)
    );
    start_table();
    work_table_header();
    foreach ($works as $work) {
        work_table_row($work);
    }
    end_table();
    if (count($works) == RESULTS_PER_PAGE) {
        next_link('wt_action', $id, $offset);
    }
    page_tail();
}

function lang_form() {
    $langs = get_languages();
    page_head("Languages");
    $items = [];
    foreach ($langs as $lang) {
        if ($lang->nworks == 0) continue;
        $items[] = sprintf(
            "<a href=tags.php?action=lang_action&id=%d>%s</a> (%d)",
            $lang->id,
            $lang->name,
            $lang->nworks
        );
    }
    show_items_cols($items, 5);
    page_tail();
}

function lang_action($id, $offset) {
    $langs = get_languages();
    page_head("Language: ".$langs[$id]->name);
    $works = DB_work::enum(
        "$id member of (language_ids->'$')",
        limit_clause($offset)
    );
    start_table();
    work_table_header();
    foreach ($works as $work) {
        work_table_row($work);
    }
    end_table();
    if (count($works) == RESULTS_PER_PAGE) {
        next_link('lang_action', $id, $offset);
    }
    page_tail();
}

function combo_heading() {
    row_heading_array(['Instrument combination', 'Works', 'Arrangements']);
}

function combo_row($ic) {
    row_array([
        inst_combo_str_struct(json_decode($ic->instruments)),
        $ic->nworks?"<a href=tags.php?action=ic_works&id=$ic->id>$ic->nworks</a>":'---',
        $ic->nscores?"<a href=tags.php?action=ic_scores&id=$ic->id>$ic->nscores</a>":'---',
    ]);
}

// show all combos.
// probably not a good idea - there are ~11K of them
//
function combo_form() {
    $ics = get_inst_combos();
    page_head("Instrument combinations");
    start_table();
    foreach ($ics as $ic) {
        if ($ic->nworks==0 && $ic->nscores==0) continue;
        combo_row($ic);
    }
    end_table();
    page_tail();
}

function combo_works($id, $offset) {
    $ics = get_inst_combos();
    $ic = $ics[$id];
    $name = inst_combo_str_struct(json_decode($ic->instruments));
    page_head("Works for $name");
    $works = DB_work::enum(
        "$id member of (instrument_combo_ids->'$')",
        limit_clause($offset)
    );
    start_table();
    work_table_header();
    foreach ($works as $work) {
        work_table_row($work);
    }
    end_table();
    if (count($works) == RESULTS_PER_PAGE) {
        next_link('ic_works', $id, $offset);
    }
    page_tail();
}

function combo_scores($id, $offset) {
    $ics = get_inst_combos();
    $ic = $ics[$id];
    $name = inst_combo_str_struct(json_decode($ic->instruments), true);
    page_head("Arrangements for $name");
    $scores = DB_score_file_set::enum(
        "$id member of (instrument_combo_ids->'$')",
        limit_clause($offset)
    );
    start_table();
    arr_table_header();
    foreach ($scores as $score) {
        arr_table_row($score);
    }
    end_table();
    if (count($scores) == RESULTS_PER_PAGE) {
        next_link('ic_scores', $id, $offset);
    }
    page_tail();
}

// form with checkboxes for instruments
//
function inst_form() {
    $insts = get_instruments();
    $items = [];
    foreach ($insts as $inst) {
        $items[] = ["x$inst->id", $inst->name, false];
    }
    page_head("Find instrument combos");
    form_start('tags.php');
    form_input_hidden('action', 'inst_action');
    form_general("Instruments",
        checkbox_table([$items], 4)
    );
    form_submit('OK');
    form_end();
    page_tail();
}

function inst_action() {
    $insts = get_instruments();
    $ids = [];
    $names = [];

    // get list of checked instruments
    //
    foreach ($insts as $inst) {
        $x = "x$inst->id";
        if (get_str($x, true)) {
            $ids[] = $inst->id;
            $names[] = $inst->name;
        }
    }
    if (!$ids) error_page('No instruments selected');

    $clause = sprintf(
        "JSON_CONTAINS(instruments->'$.id', CAST('%s' AS JSON))",
        json_encode($ids)
    );
    $ics = DB_instrument_combo::enum($clause);

    page_head("Instrument combos involving ".implode(' and ', $names));
    start_table();
    combo_heading();
    foreach ($ics as $ic) {
        combo_row($ic);
    }
    end_table();
    page_tail();
}

function choose_type() {
    page_head("Categories");
    echo "
        <p>
        <a href=tags.php?action=ic_form>Instrument combinations</a>
        <p>
        <a href=tags.php?action=wt_form>Work types</a>
        <p>
        <a href=tags.php?action=lang_form>Languages</a>
    ";
    page_tail();
}

$action = get_str('action', true);
$offset = get_int('offset', true);
if (!$offset) $offset = 0;
if ($action == 'ic_form') {
    combo_form();
} else if ($action == 'ic_works') {
    combo_works(get_int('id'), $offset);
} else if ($action == 'ic_scores') {
    combo_scores(get_int('id'), $offset);
} else if ($action == 'lang_form') {
    lang_form();
} else if ($action == 'lang_action') {
    lang_action(get_int('id'), $offset);
} else if ($action == 'wt_form') {
    wt_form();
} else if ($action == 'wt_action') {
    wt_action(get_int('id'), $offset);
} else if ($action == 'inst_form') {
    inst_form();
} else if ($action == 'inst_action') {
    inst_action();
} else {
    choose_type();
}

?>
