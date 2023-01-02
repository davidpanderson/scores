<?php

require_once("imslp_db.inc");
require_once("web.inc");
require_once("imslp_web.inc");

function person_search_action() {
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
    $where = implode(' and ', $wheres);
    if ($nationality_id || $period_id) {
        $persons = DB_person::enum_join($nationality_id, $period_id, $where);
    } else {
        $persons = DB_person::enum($where);
    }
    page_head("Person search results");
    start_table();
    person_table_heading();
    foreach ($persons as $p) {
        person_table_row($p);
    }
    end_table();
    page_tail();
}

person_search_action();

?>
