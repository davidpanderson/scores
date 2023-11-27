<?php

require_once("imslp_db.inc");
require_once("web.inc");
require_once("imslp_web.inc");

define('RESULTS_PER_PAGE', 50);

function limit_clause($offset) {
    return sprintf('limit %d, %d', $offset, RESULTS_PER_PAGE);
}

function next_link(
    $last_name, $person_type, $period_id, $nationality_id, $sex, $offset
) {
    echo sprintf('<p><a href="search_person.php?last_name=%s&person_type=%s&period_id=%d&nationality_id=%d&sex=%s&offset=%d">Next %d</a>',
        $last_name, $person_type, $period_id, $nationality_id, $sex, $offset+RESULTS_PER_PAGE, RESULTS_PER_PAGE
    );
}
function person_search_action($offset) {
    $last_name = strtolower(trim(get_str('last_name')));
    $person_type = get_str('person_type');
    $period_id = get_int('period_id');
    $nationality_id = get_int('nationality_id');
    $sex = get_str('sex');

    $wheres = [];
    if ($last_name && $last_name!='any') {
        $wheres[] = sprintf("last_name='%s'", DB::escape($last_name));
    }
    if ($person_type == 'composer') {
        $wheres[] = 'is_composer<>0';
    } else if ($person_type == 'performer') {
        $wheres[] = 'is_performer<>0';
    }
    if ($sex == 'male') {
        $wheres[] = "sex='male'";
    } else if ($sex == 'female') {
        $wheres[] = "sex='female'";
    }
    if ($nationality_id) {
        $wheres[] = "$nationality_id member of (nationality_ids->'$')";
    }
    if ($period_id) {
        $wheres[] = "$period_id member of (period_ids->'$')";
    }

    $where = implode(' and ', $wheres);
    $persons = DB_person::enum($where, limit_clause($offset));
    page_head("Person search results");
    start_table();
    person_table_heading();
    foreach ($persons as $p) {
        person_table_row($p);
    }
    end_table();
    if (count($persons)==RESULTS_PER_PAGE) {
        next_link(
            $last_name, $person_type, $period_id, $nationality_id, $sex, $offset
        );
    }
    page_tail();
}

$offset = get_int('offset', true);
if (!$offset) $offset = 0;

person_search_action($offset);

?>
