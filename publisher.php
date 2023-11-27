<?php

require_once("imslp_db.inc");
require_once("imslp_web.inc");
require_once("web.inc");

define('RESULTS_PER_PAGE', 50);

function limit_clause($offset) {
    return sprintf('limit %d, %d', $offset, RESULTS_PER_PAGE);
}

function next_link($offset) {
    echo sprintf("<p><a href=publisher.php?offset=%d>Next %d</a>",
        $offset+RESULTS_PER_PAGE, RESULTS_PER_PAGE
    );
}

function publisher_list($offset) {
    $pubs = DB_publisher::enum('', 'order by name '.limit_clause($offset));
    page_head("Publishers");
    start_table();
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
    if (count($pubs)==RESULTS_PER_PAGE) {
        next_link($offset);
    }
    page_tail();
}

// show list of scores by a publisher
//
function scores_by_publisher($id) {
    $pub = DB_publisher::lookup_id($id);
    page_head("Scores published by $pub->name");
    $sets = DB_score_file_set::enum("publisher_id=$id");
    start_table();
    score_table_header();
    foreach ($sets as $set) {
        score_table_row($set);
    }
    end_table();
    page_tail();
}

$offset = get_int('offset', true);
if (!$offset) $offset = 0;

$id = get_int('id', true);
if ($id) {
    scores_by_publisher($id);
} else {
    publisher_list($offset);
}

?>
