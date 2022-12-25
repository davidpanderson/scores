<?php

require_once("imslp_db.inc");
require_once("imslp_web.inc");
require_once("web.inc");

function publisher_list() {
    $pubs = DB_publisher::enum('', 'order by name');
    page_head("Publishers");
    start_table('table-striped');
    row_heading_array([
        'Name<br><small>Click to view scores</small>',
        'Imprint', 'Location'
    ]);
    foreach ($pubs as $pub) {
        row_array([
            "<a href=publisher.php?id=$pub->id>$pub->name</a>",
            $pub->imprint, $pub->location
        ]);
    }
    end_table();
    page_tail();
}

// show list of scores by a publisher
//
function scores_by_publisher($id) {
    $pub = DB_publisher::lookup_id($id);
    page_head("Scores published by $pub->name");
    $sets = DB_score_file_set::enum("publisher_id=$id");
    start_table('table-striped');
    row_heading_array([
        'Composition', 'Categories', 'Publication date'
    ]);
    foreach ($sets as $set) {
        $c = DB_composition::lookup_id($set->composition_id);
        $date = '---';
        if ($set->pub_date) {
            $date = $set->pub_date;
        } else if ($set->pub_year) {
            $date = $set->pub_year;
        }
        row_array([
            "<a href=composition.php?id=$c->id#sfs_$set->id>$c->title</a>",
            hier_string($set),
            $date
        ]);
    }
    end_table();
    page_tail();
}

$id = get_int('id', true);
if ($id) {
    scores_by_publisher($id);
} else {
    publisher_list();
}

?>
