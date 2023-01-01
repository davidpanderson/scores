<?php

require_once("web.inc");
require_once("imslp_db.inc");
require_once("imslp_web.inc");

function ensemble_search_action() {
    $type = get_str('type');
    $period_id = get_str('period_id');
    $nationality_id = get_str('nationality_id');

    $wheres = [];
    if ($type != 'any') {
        $wheres[] = sprintf("type='%s'", DB::escape($type));
    }
    if ($period_id) {
        $wheres[] = "period_id=$period_id";
    }
    if ($nationality_id) {
        $wheres[] = "nationality_id=$nationality_id";
    }
    $where = implode(' and ', $wheres);
    $es = DB_ensemble::enum($where);

    page_head("Ensembles");
    start_table('table-striped');
    row_heading_array(['Name', 'Type', 'Nationality', 'Period', 'Founded']);
    foreach ($es as $e) {
        row_array([
            "<a href=ensemble.php?id=$e->id>$e->name</a>",
            $e->type,
            $e->nationality_id?nationality_name($e->nationality_id):'',
            $e->period_id?period_name($e->period_id):'',
            $e->born_year?$e->born_year:''
        ]);
    }
    end_table();
    page_tail();
}

ensemble_search_action();

?>
