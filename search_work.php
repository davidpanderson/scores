<?php

require_once('imslp_db.inc');
require_once('imslp_util.inc');
require_once('imslp_web.inc');
require_once('web.inc');

function work_search_action($keywords, $period_id) {
    // remove commas
    //
    $k = str_replace(',', ' ', $keywords);

    // add the plural of each word
    //
    $k2 = explode(' ', $k);
    $k3 = $k2;
    foreach ($k2 as $k) {
        $k3[] = $k.'s';
    }
    $k3 = implode(' ', $k3);
    $clause = sprintf("match (title, instrumentation) against ('%s')",
        DB::escape($k3)
    );
    if ($period_id) {
        $clause .= " and period_id=$period_id";
    }
    $clause .= " limit 50";
    $works = DB_work::enum($clause);
    page_head('Work search results');
    if ($works) {
        start_table('table-striped');
        row_heading_array(['Title', 'Composer', 'Year', 'Period', 'Instruments']);
        foreach ($works as $work) {
            [$title, $first, $last] = parse_title($work->title);
            row_array([
                "<a href=work.php?id=$work->id>$title</a>",
                "$first $last",
                $work->year_of_composition?$work->year_of_composition:'---',
                $work->period_id?"<nobr>".period_name($work->period_id)."</nobr>":'',
                $work->instrumentation,
            ]);
        }
        end_table();
    } else {
        echo "No works match '$keywords'.";
    }
    page_tail();
}

$keywords = get_str('keywords');
$period_id = get_int('period_id');
work_search_action($keywords, $period_id);

?>
