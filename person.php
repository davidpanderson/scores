<?php

require_once('web.inc');
require_once('imslp_util.inc');
require_once('imslp_db.inc');
require_once('imslp_web.inc');

function show_works($person) {
    $works = DB_work::enum("composer_id=$person->id", 'order by year_of_composition');
    if (!$works) return;
    echo "<h2>Works</h2>\n";
    start_table('table-striped');
    work_table_header();
    foreach ($works as $w) {
        work_table_row($w);
    }
    end_table();
}

function show_recordings($person) {
    $prs = DB_performer_role::enum("person_id=$person->id");
    if (!$prs) return;
    echo "<h2>Performances</h2>\n";
    foreach ($prs as $pr) {
        echo "<h2>$pr->role</h2>\n";
        start_table();
        recording_table_header();
        // TODO: use a join to get work?
        $afss = DB_audio_file_set::enum(
            "$pr->id member of (performer_role_ids->'$')"
        );
        foreach ($afss as $afs) {
            recording_table_row($afs);
        }
        end_table();
    }
}

function show_person($person) {
    page_head("$person->first_name $person->last_name");
    show_person_detail($person);
    show_works($person);
    show_recordings($person);
    page_tail();
}

$id = get_int('id');
$person = DB_person::lookup_id($id);
if (!$person) {
    error_page('no such person');
}
show_person($person);

?>
