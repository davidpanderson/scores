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
    row_heading_array(['Title', 'Year', 'Instrumentation']);
    foreach ($works as $w) {
        [$t, $first, $last] = parse_title($w->title);
        row_array([
            sprintf("<p><a href=work.php?id=%d>%s</a>",
                $w->id, $t
            ),
            $w->year_of_composition?$w->year_of_composition:'---',
            $w->instrumentation
        ]);
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
        row_heading_array(['Work']);
        $aps = DB_audio_performer::enum("performer_role_id=$pr->id");
        foreach ($aps as $ap) {
            $afs = DB_audio_file_set::lookup_id($ap->audio_file_set_id);
            $comp = DB_work::lookup_id($afs->work_id);
            row_array([
                "<a href=work.php?id=$comp->id#afs_$afs->id>$comp->title</a>"
            ]);
        }
        end_table();
    }
}

function show_person($person) {
    page_head("$person->first_name $person->last_name");
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
