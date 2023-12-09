<?php

require_once('web.inc');
require_once('imslp_db.inc');
require_once('imslp_util.inc');
require_once('imslp_web.inc');

define('RESULTS_PER_PAGE', 50);

function limit_clause($offset) {
    return sprintf('limit %d, %d', $offset, RESULTS_PER_PAGE);
}

function work_search_action() {
    $keywords = get_str('keywords');
    $instrument_combo_id = get_int('instrument_combo_id');
    $is_arr = get_int('is_arr');
    $orig_instrument_combo_id = get_int('orig_instrument_combo_id');
    $period_id = get_int('period_id');
    $sex = get_str('sex');
    $nationality_id = get_int('nationality_id');

    $offset = get_int('offset', true);
    if (!$offset) $offset = 0;

    $joins = [];
    $where_clauses = [];

    if ($keywords) {
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
        $where_clauses[] = sprintf("match (title, instrumentation) against ('%s' in boolean mode)",
            DB::escape($k)
        );
    }
    if ($is_arr) {
        $joins[] = "join score_file_set as s";
        $where_clauses[] = "s.work_id = w.id";
        $where_clauses[] = "s.hier1 = 'Arrangements and Transcriptions' and s.hier3<>''";
        if ($instrument_combo_id) {
            $where_clauses[] = "$instrument_combo_id member of (s.instrument_combo_ids->'$')";
        }
        if ($orig_instrument_combo_id) {
            $where_clauses[] = "$orig_instrument_combo_id member of (w.instrument_combo_ids->'$')";
        }
    } else {
        if ($instrument_combo_id) {
            $where_clauses[] = "$instrument_combo_id member of (w.instrument_combo_ids->'$')";
        }
    }
    if ($period_id) {
        $where_clauses[] = "period_id=$period_id";
    }
    if ($sex!='either' || $nationality_id) {
        $joins[] = 'join person as p';
        $where_clauses[] = 'p.id = w.composer_id';
    }
    if ($sex == 'male') {
        $where_clauses[] = "p.sex='male'";
    } else if ($sex == 'female') {
        $where_clauses[] = "p.sex='female'";
    }
    if ($nationality_id) {
        $where_clauses[] = "$nationality_id member of (p.nationality_ids->'$')";
    }
    if (!$where_clauses) {
        page_head("No search parameters given");
        page_tail();
        return;
    }
    $join_clause = implode(' ', $joins);
    $where_clause = implode(' and ', $where_clauses);
    if ($is_arr) {
        $query = "select s.*, w.title, w.instrument_combo_ids as wici, w.composer_id from work as w $join_clause where $where_clause ";
    } else {
        $query = "select w.* from work as w $join_clause where $where_clause ";
    }
    $query .= limit_clause($offset);

    $db = DB::get();
    $results = $db->do_query($query);
    if (!mysqli_num_rows($results)) {
        page_head('Search results');
        echo "No works match those parameters.";
        page_tail();
        return;
    }
    if ($is_arr) {
        page_head('Arrangement search results');
    } else {
        page_head('Work search results');
    }
    start_table();
    $nresults = 0;
    if ($is_arr) {
        arr_table_header();
        while ($score = $results->fetch_object()) {
            // collect work info so we don't have to read it again
            $work = new StdClass;
            $work->id = $score->work_id;
            $work->instrument_combo_ids = $score->wici;
            $work->title = $score->title;
            $work->composer_id = $score->composer_id;
            arr_table_row($score, $work);
            $nresults++;
        }
    } else {
        work_table_header();
        while ($work = $results->fetch_object()) {
            work_table_row($work);
            $nresults++;
        }
    }
    end_table();
    if ($nresults==RESULTS_PER_PAGE) {
        echo sprintf(
            '<p><a href="search_work.php?keywords=%s&instrument_combo_id=%d&is_arr=%d&orig_instrument_combo_id=%d&period_id=%d&sex=%s&nationality_id=%d&offset=%d">Next %d</a>',
            $keywords, $instrument_combo_id, $is_arr, $orig_instrument_combo_id,
            $period_id, $sex, $nationality_id,
            $offset+RESULTS_PER_PAGE, RESULTS_PER_PAGE
        );
    }
    page_tail();
}


work_search_action();

?>
