<?php

function display_errors(){}

require_once('cmi_db.inc');
require_once('web/cmi.inc');

// find the N people with the most compositions.
// For each of them, find the instrument combos for which they wrote
// at least M% of their compositions

function main() {
    $composers = [];
        // person_role_id => struct
        // where struct has int count and ics: combo_id => count
    $comps = DB::enum('select instrument_combos, creators from composition where arrangement_of=0 and parent=0 limit 1000000');
    foreach ($comps as $comp) {
        $ics = json_decode($comp->instrument_combos);
        if (!$ics) continue;
        $prs = json_decode($comp->creators);
        foreach ($prs as $pr) {
            if (!array_key_exists($pr, $composers)) {
                $x = new StdClass;
                $x->count = 0;
                $x->ics = [];
                $composers[$pr] = $x;
            }
            update($composers[$pr], $ics);
        }
    }
    //show($composers);
    output($composers);
}

function compare($x, $y) {
    return $x->count < $y->count;
}

function show($composers) {
    $combos = get_inst_combos();
    uasort($composers, 'compare');
    foreach ($composers as $prid=>$x) {
        $pr = DB_person_role::lookup_id($prid);
        if ($pr->role != 1) continue;
        $person = DB_person::lookup_id($pr->person);
        echo "$pr->person $person->first_name $person->last_name $x->count\n";
        //continue;
        foreach ($x->ics as $icid=>$count) {
            echo sprintf("    %s %d\n",
                instrument_combo_str($combos[$icid]), $count
            );
        }
    }
}

function output($composers) {
    $f = fopen('composers', 'r');
    $top = [];
    while ($s = fgets($f)) {
        $top[] = intval($s);
    }
    $out = [];
    foreach ($composers as $prid=>$x) {
        $pr = DB_person_role::lookup_id($prid);
        if ($pr->role != 1) continue;
        if (!in_array($pr->person, $top)) continue;
        $y = new StdClass;
        $person = DB_person::lookup_id($pr->person);
        $y->person_id = $person->id;
        $y->name = "$person->last_name $person->first_name";
        $min = $x->count/10;
        if ($min > 20) $min = 20;
        $insts = [];
        if ($person->id == 15586) {
            print_r($x->ics);
        }
        foreach ($x->ics as $icid=>$count) {
            if ($icid == 1 || $icid==104) {  // orchestra, piano concerto
                if ($count >= 3) {
                    $insts[] = $icid;
                }
            } else {
                if ($count>$min) {
                    $insts[] = $icid;
                }
            }
        }
        $y->insts = $insts;
        $out[] = $y;
    }
    $x = json_encode($out, JSON_PRETTY_PRINT);
    file_put_contents('top_comps.json', $x);
}

function update (&$x, $ics) {
    $x->count++;
    foreach ($ics as $ic) {
        if (array_key_exists($ic, $x->ics)) {
            $x->ics[$ic]++;
        } else {
            $x->ics[$ic] = 1;
        }
    }
}

main();

?>
