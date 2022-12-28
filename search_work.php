<?php

require_once('imslp_db.inc');
require_once('imslp_util.inc');
require_once('web.inc');

function style_name($id) {
    if (!$id) return '---';
    $style = DB_style::lookup_id($id);
    return "<nobr>$style->name</nobr>";
}

function work_search_action($keywords, $style_id) {
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
    if ($style_id) {
        $clause .= " and piece_style_id=$style_id";
    }
    $clause .= " limit 50";
    $works = DB_work::enum($clause);
    page_head('Search results');
    if ($works) {
        start_table('table-striped');
        row_heading_array(['Title', 'Composer', 'Year', 'Style', 'Instruments']);
        foreach ($works as $work) {
            [$title, $first, $last] = parse_title($work->title);
            row_array([
                "<a href=work.php?id=$work->id>$title</a>",
                "$first $last",
                $work->year_of_composition?$work->year_of_composition:'---',
                style_name($work->piece_style_id),
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
$style_id = get_int('style_id');
work_search_action($keywords, $style_id);

?>
