<?php
require_once("imslp_db.inc");
require_once("imslp_web.inc");
require_once("web.inc");

DEPRECATED

function performer_list() {
    page_head("Performers");
    $persons = DB_person::enum('is_performer=1', 'order by last_name');
    start_table('table-striped');
    row_heading_array(['Name', 'Born', 'Role']);
    foreach ($persons as $person) {
        $name = "$person->last_name, $person->first_name";
        $prs = DB_performer_role::enum("person_id=$person->id");
        $x = [];
        foreach ($prs as $pr) {
            $x[] = $pr->role;
        }
        row_array([
            "<a href=performer.php?id=$person->id>$name</a>",
            person_birth_string($person),
            implode(', ', $x)
        ]);
    }
    end_table();
    page_tail();
}

function show_performer($person) {
    page_head("Recordings by $person->first_name $person->last_name");
    $prs = DB_performer_role::enum("person_id=$person->id");
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
    page_tail();
}

$id = get_int('id', true);
if ($id) {
    $person = DB_person::lookup_id($id);
    if (!$person) error_page('no such performer');
    if (!$person->is_performer) error_page('not a performer');
    show_performer($person);
} else {
    performer_list();
}

?>
