<?php

require_once("web.inc");
require_once("imslp_web.inc");
require_once("ser.inc");

function wt_form() {
    $wts = work_types();
    page_head("Work types");
    start_table();
    row_heading_array(['Type', 'Works']);
    foreach ($wts as $wt) {
        if ($wt->nworks == 0) continue;
        row_array([
            $wt->name,
            "<a href=tags.php?action=wt_action&id=$wt->id>$wt->nworks</a>"
        ]);
    }
    end_table();
    page_tail();
}

function wt_action($id) {
    $wts = work_types();
    page_head($wts[$id]->name);
    $works = DB_work::enum("$id member of (work_type_ids->'$')");
    start_table();
    work_table_header();
    foreach ($works as $work) {
        work_table_row($work);
    }
    page_tail();
}

function lang_form() {
    $langs = languages();
    page_head("Languages");
    start_table();
    row_heading_array(['Language', 'Works']);
    foreach ($langs as $lang) {
        if ($lang->nworks == 0) continue;
        row_array([
            $lang->name,
            "<a href=tags.php?action=lang_action&id=$lang->id>$lang->nworks</a>"
        ]);
    }
    end_table();
    page_tail();
}

function lang_action($id) {
    $langs = languages();
    page_head($langs[$id]->name);
    $works = DB_work::enum("$id member of (language_ids->'$')");
    start_table();
    work_table_header();
    foreach ($works as $work) {
        work_table_row($work);
    }
    page_tail();
}

function combo_form() {
    $ics = get_inst_combos();
    page_head("Instrument combinations");
    start_table();
    row_heading_array(['Instrument combination', 'Works', 'Arrangements']);
    foreach ($ics as $ic) {
        if ($ic->nworks==0 && $ic->nscores==0) continue;
        row_array([
            inst_combo_str(json_decode($ic->instruments), true),
            $ic->nworks?"<a href=tags.php?action=ic_works&id=$ic->id>$ic->nworks</a>":'---',
            $ic->nscores?"<a href=tags.php?action=ic_scores&id=$ic->id>$ic->nscores</a>":'---',
        ]);
    }
    end_table();
    page_tail();
}

function combo_works($id) {
    $ics = get_inst_combos();
    $ic = $ics[$id];
    $name = inst_combo_str(json_decode($ic->instruments), true);
    page_head("Works for $name");
    $works = DB_work::enum("$id member of (instrument_combo_ids->'$')");
    start_table();
    work_table_header();
    foreach ($works as $work) {
        work_table_row($work);
    }
    page_tail();
}

function combo_scores($id) {
    $ics = get_inst_combos();
    $ic = $ics[$id];
    $name = inst_combo_str(json_decode($ic->instruments), true);
    page_head("Scores for $name");
    $scores = DB_score_file_set::enum("$id member of (instrument_combo_ids->'$')");
    start_table();
    score_table_header();
    foreach ($scores as $score) {
        score_table_row($score);
    }
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
if ($action == 'ic_form') {
    combo_form();
} else if ($action == 'ic_works') {
    combo_works(get_int('id'));
} else if ($action == 'ic_scores') {
    combo_scores(get_int('id'));
} else if ($action == 'lang_form') {
    lang_form();
} else if ($action == 'lang_action') {
    lang_action(get_int('id'));
} else if ($action == 'wt_form') {
    wt_form();
} else if ($action == 'wt_action') {
    wt_action(get_int('id'));
} else {
    choose_type();
}

?>
