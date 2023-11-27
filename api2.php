<?php

require_once('web.inc');
require_once('api.inc');
require_once('ser.inc');

// API handler that uses a C program to do the work
// This wrapper converts args and results between text and numeric

DEPRECATED - see api3.php

require_once('api.inc');

function web_list_works_filter_worktype($pub_years, $insts, $periods) {
    $cmd = ['./api'];
    $cmd[] = '--pub_years '.$pub_years[0].' '.$pub_years[1];
    foreach ($insts as $id) {
        $cmd[] = "--inst $id";
    }
    foreach ($periods as $id) {
        $cmd[] = "--period $id";
    }
    $cmd = implode(' ', $cmd);
    echo $cmd;
    exec($cmd, $output, $exit_code);
    if ($exit_code) {
        die("exit $exit_code");
    }
    $line = $output[0];
    $wt_ids = explode(' ', $line);
    $work_types = work_types();
    $names = [];
    foreach ($wt_ids as $id) {
        $names[] = $work_types[$id]->name;
    }
    echo json_encode($names);
}

// example: http://34.83.223.220/scores/api2.php?command=web.list.works.filter&type=workType&firstPublishedYears=[1700,1800]&filterCategories=[[%22Scores_featuring_the_violin%22,%22Scores_featuring_the_piano%22],[%22Romantic_style%22]]

if (1) {
    $command = get_str('command');
    $type = get_str('type');
    $first_published_years = get_str('firstPublishedYears');
    $filter_categories = get_str('filterCategories');
} else {
    // debug
    $command = 'web.list.works.filter';
    $type = 'workType';
    $first_published_years = '[1700,1800]';
    $filter_categories = '[["Scores_featuring_the_violin","Scores_featuring_the_piano"],["Romantic_style"]]';
}

$first_published_years = json_decode($first_published_years);
$filter_categories = json_decode($filter_categories);
$insts = parse_filter_inst($filter_categories[0]);
$periods = parse_filter_period($filter_categories[1]);

switch ($command) {
case 'web.list.works.filter':
    switch($type) {
    case 'workType':
        web_list_works_filter_worktype(
            $first_published_years,
            $insts,
            $periods
        );
    }
}

?>

