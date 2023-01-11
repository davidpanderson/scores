<?php

require_once('imslp_db.inc');
require_once('imslp_util.inc');
require_once('imslp_web.inc');
require_once('web.inc');

function work_search_action($keywords, $period_id) {
    // remove commas
    //
    $k = str_replace(',', ' ', $keywords);

    // MySQL's fulltext search system is deficient:
    // 1) With two search terms,
    //      it ranks some entries that match one of them higher than
    //      entries that match both!
    //      "natural language mode" doesn't seem to do anything useful.
    // 2) MariaDB doesn't deal with diacritical marks
    //      (MySQL fixes this)
    // 3) It doesn't deal with plurals
    //
    // We may need to use a search system outside of MySQL

    // add the plural of each word
    // (this didn't really work)
    //
    if (0) {
        $k2 = explode(' ', $k);
        $k3 = $k2;
        foreach ($k2 as $w) {
            $k3[] = $w.'s';
        }
        $k = implode(' ', $k3);
    }

    // make each word mandatory
    // (non-ideal but fixes the above wonkiness)
    if (1) {
        $k2 = explode(' ', $k);
        $k = [];
        foreach ($k2 as $w) {
            if ($w) {
                $k[] = "+$w";
            }
        }
        $k = implode(' ', $k);
    }
    $clause = sprintf("match (title, instrumentation) against ('%s' in boolean mode)",
        DB::escape($k)
    );
    if ($period_id) {
        $clause .= " and period_id=$period_id";
    }
    $clause .= " limit 50";
    $works = DB_work::enum($clause);
    page_head('Work search results');
    if ($works) {
        start_table();
        work_table_header();
        foreach ($works as $work) {
            work_table_row();
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
