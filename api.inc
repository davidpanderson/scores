<?php

// utility functions for API handler

require_once('ser.inc');

// in: ["Scores_featuring_the_x", ...]
// out: list of instr ids
//
function parse_filter_inst($x) {
    $ids = [];
    $n = strlen("Scores_featuring_the_");
    foreach ($x as $str) {
        $iname = substr($str, $n);
        $ids[] = inst_name_to_id($iname);
    }
    return $ids;
}

// in: ["x_style", ...]
// out: list of period ids
//
function parse_filter_period($x) {
    $ids = [];
    foreach ($x as $str) {
        $n = strpos($str, '_');
        $pname = substr($str, 0, $n);
        $ids[] = period_name_to_id($pname);
    }
    return $ids;
}

?>
