<?php

// web API functions implemented as DB queries
// Sort of deprecated - this takes too long (~1 sec) in some cases.
// Use array-scanning approach instead (api2.php, api3.php)

require_once('web.inc');
require_once('imslp_db.inc');
require_once('ser.inc');
require_once('api.inc');

// show work types for which there are works
// for the given instruments and other constraints
//
function web_list_works_filter_worktype($pub_years, $inst_ids, $periods) {
    $clauses = [];

    // get instrument combos that include the given instruments
    //
    if ($inst_ids) {
        $ics = DB_instrument_combo::enum_fields("id",
            sprintf("json_contains( (instruments->'$.id'), cast('[%s]' as JSON))",
                implode(',', $inst_ids)
            )
        );
        $ids = [];
        foreach ($ics as $ic) {
            $ids[] = $ic->id;
        }
        $clauses[] = sprintf(
            " json_overlaps(CAST('[%s]' AS JSON), (instrument_combo_ids->'$')) ",
            implode(',', $ids)
        );
    }
    if ($pub_years) {
        $clauses[] = sprintf(
            " year_of_composition>=%d and year_of_composition<=%d ",
            $pub_years[0], $pub_years[1]
        );
    }
    if ($periods) {
        $clauses[] = sprintf(
            "period_id in (%s)",
            implode(',', $periods)
        );
    }

    $query = "select distinct work_type_ids from work";
    if ($clauses) {
        $query .= " where ".implode(' and ', $clauses);
    }
    //echo $query;
    $db = DB::get();
    $results = $db->do_query($query);

    // the query returns (possibly overlapping) lists of work types.
    // merge them into a single list

    $ids = [];
    while ($x = $results->fetch_object()) {
        $y = $x->work_type_ids;
        $y = json_decode($y);
        $ids = array_unique(array_merge($ids, $y));
    }
    //print_r($ids);

    // look up work type names and output JSON
    //
    $names = [];
    $work_types = work_types();
    foreach ($ids as $id) {
        $names[] = $work_types[$id]->name;
    }
    $x = new StdClass;
    $x->categories = $names;
    echo json_encode($x);
}


if (0) {
    $command = 'web.list.works.filter';
    $type = 'workType';
    $first_published_years = '[1700,1800]';
    $filter_categories = '[["Scores_featuring_the_violin","Scores_featuring_the_piano"],["Romantic_style"]]';
} else {
    // example: http://34.83.223.220/scores/api.php?command=web.list.works.filter&type=workType&firstPublishedYears=[1700,1800]&filterCategories=[[%22Scores_featuring_the_violin%22,%22Scores_featuring_the_piano%22],[%22Romantic_style%22]]
    $command = get_str('command');
    $type = get_str('type');
    $first_published_years = get_str('firstPublishedYears');
    $filter_categories = get_str('filterCategories');
}

switch ($command) {
case 'web.list.works.filter':
    switch($type) {
    case 'workType':
        $x = json_decode($filter_categories);
        web_list_works_filter_worktype(
            json_decode($first_published_years),
            parse_filter_inst($x[0]),
            parse_filter_period($x[1])
        );
        break;
    default:
        die("bad type $type\n");
    }
    break;
default:
    die("bad command $command");
}

?>
