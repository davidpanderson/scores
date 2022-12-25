<?php
require_once("imslp_db.inc");
require_once("web.inc");

function performer_list() {
    page_head("Performers");
    $ps = DB_performer_role::enum('');
    start_table('table-striped');
    row_heading_array(['Name', 'Role']);
    foreach ($ps as $p) {
        $person = DB_person::lookup_id($p->person_id);
        $name = "$person->first_name $person->last_name";
        row_array([
            "<a href=performer.php?id=$p->id>$name</a>",
            $p->role
        ]);
    }
    end_table();
    page_tail();
}

function show_performer($p) {
    $person = DB_person::lookup_id($p->person_id);
    $aps = DB_audio_performer::enum("performer_role_id=$p->id");
    page_head("$person->first_name $person->last_name");
    start_table();
    row_heading_array(['Composition']);
    foreach ($aps as $ap) {
        $afs = DB_audio_file_set::lookup_id($ap->audio_file_set_id);
        $comp = DB_composition::lookup_id($afs->composition_id);
        row_array([
            "<a href=composition.php?id=$comp->id#afs_$afs->id>$comp->title</a>"
        ]);
    }
    end_table();
    page_tail();
}

$id = get_int('id', true);
if ($id) {
    $p = DB_performer_role::lookup_id($id);
    if (!$p) error_page('no such performer');
    show_performer($p);
} else {
    performer_list();
}

?>
