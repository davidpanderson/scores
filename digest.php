#! /usr/bin/env php

<?php

// write flat-file digests of works, scores, recordings, and inst combos
// (digest_*.txt).
// These are then read by digest.cpp and converted to binary files

require_once('imslp_db.inc');
require_once('ser.inc');

// expand a work type list to include descendants
//
function expand_work_type_list($wt_ids) {
    $out = $wt_ids;
    static $work_types = false;
    if (!$work_types) {
        $work_types = work_types();
        foreach ($work_types as $wt) {
            $wt->descendant_ids = json_decode($wt->descendant_ids);
        }
    }

    foreach ($wt_ids as $wt_id) {
        $wt = $work_types[$wt_id];
        if ($wt->descendant_ids) {
            $out = array_unique(array_merge($out, $wt->descendant_ids));
        }
    }
    return $out;
}

function sex_str_to_int($str) {
    if ($str == 'male') return 1;
    if ($str == 'female') return 2;
    return 0;
}

function write_composer_info($f, $work) {
    fprintf($f, "$work->composer_id\n");
    $comp = DB_person::lookup_id($work->composer_id);
    if ($comp->nationality_ids) {
        $nats = json_decode($comp->nationality_ids);
        $nats = array_map('strval', $nats);
        $nats = implode(' ', $nats);
    } else {
        $nats = '';
    }
    fprintf($f, "$nats\n");

    $sex = sex_str_to_int($comp->sex);
    fprintf($f, "$sex\n");
}

function write_work_types($f, $work) {
    if ($work->work_type_ids) {
        $wt_ids = json_decode($work->work_type_ids);
        //$wt_ids = expand_work_type_list($wt_ids);
        $x = implode(' ', $wt_ids);
        fprintf($f, "$x\n");
    } else {
        fprintf($f, "\n");
    }
}

// write instrument IDs, then combo IDs
//
function write_instrument_info($f, $cids) {
    static $inst_combos=false;
    if (!$inst_combos) {
        $inst_combos = get_inst_combos();
    }
    $inst_ids = [];
    foreach ($cids as $id) {
        $ic = $inst_combos[$id];
        $x = json_decode($ic->instruments);
        $inst_ids = array_unique(array_merge($inst_ids, $x->id));
    }
    fprintf($f, implode(' ', array_map('strval', $inst_ids))."\n");

    $x = implode(' ', $cids);
    fprintf($f, "$x\n");
}

function do_works() {
    $f = fopen('data/digest_work.txt', 'w');
    $works = DB_work::enum('', '');
    foreach ($works as $work) {
        fprintf($f, strtolower($work->title)."\n");
        fprintf($f, "$work->id\n");
        write_work_types($f, $work);
        fprintf($f, "$work->period_id\n");
        if ($work->instrument_combo_ids) {
            $cids = json_decode($work->instrument_combo_ids);
            write_instrument_info($f, $cids);
        } else {
            fprintf($f, "\n\n");
        }
        write_composer_info($f, $work);
        fprintf($f, sprintf("%d\n", $work->year_pub));
    }
    fclose($f);
}

function do_score($f, $score) {
    $work = DB_work::lookup_id($score->work_id);
    fprintf($f, strtolower($work->title)."\n");
    fprintf($f, "$score->id\n");
    fprintf($f, "$work->id\n");
    write_work_types($f, $work);
    fprintf($f, "$work->period_id\n");
    if ($score->instrument_combo_ids) {
        $cids = json_decode($score->instrument_combo_ids);
        write_instrument_info($f, $cids);
    } else if ($work->instrument_combo_ids) {
        $cids = json_decode($work->instrument_combo_ids);
        write_instrument_info($f, $cids);
    } else {
        fprintf($f, "\n\n");
    }
    write_composer_info($f, $work);
    fprintf($f, "$score->copyright_id\n");
    if ($score->hier1 == 'Arrangements and Transcriptions') {
        fprintf($f, "1\n");
    } else {
        fprintf($f, "0\n");
    }
    fprintf($f, "$score->publisher_id\n");
}

// do scores 10000 at a time to avoid running out of memory
//
function do_scores() {
    $f = fopen('data/digest_score.txt', 'w');
    $min_id = 0;
    while (1) {
        echo "min_id $min_id\n";
        $scores = DB_score_file_set::enum("id>$min_id", 'limit 10000');
        if (!$scores) break;
        foreach ($scores as $score) {
            do_score($f, $score);
            if ($score->id > $min_id) $min_id = $score->id;
        }
    }
    fclose($f);
}

function do_recs() {
    $f = fopen('data/digest_rec.txt', 'w');
    $recs = DB_audio_file_set::enum('', '');
    foreach ($recs as $rec) {
        $work = DB_work::lookup_id($rec->work_id);
        fprintf($f, strtolower($work->title)."\n");
        fprintf($f, "$rec->id\n");
        fprintf($f, "$work->id\n");
        write_work_types($f, $work);
        fprintf($f, "$work->period_id\n");
        if ($rec->instrument_combo_id) {
            write_instrument_info($f, [$rec->instrument_combo_id]);
        } else if ($work->instrument_combo_ids) {
            $cids = json_decode($work->instrument_combo_ids);
            write_instrument_info($f, $cids);
        } else {
            fprintf($f, "\n\n");
        }
        write_composer_info($f, $work);
        fprintf($f, "$rec->copyright_id\n");
        if ($rec->hier1 == 'Arrangements and Transcriptions') {
            fprintf($f, "1\n");
        } else {
            fprintf($f, "0\n");
        }
    }
    fclose($f);
}

function do_inst_combos() {
    $f = fopen('data/digest_ic.txt', 'w');
    $ics = DB_instrument_combo::enum();
    $max_len = 0;
    foreach ($ics as $ic) {
        $insts = json_decode($ic->instruments);
        array_multisort($insts->id, $insts->count);
        fwrite($f, "$ic->id\n");
        $x = implode(' ', array_map('strval', $insts->id));
        fwrite($f, "$x\n");
        $x = implode(' ', array_map('strval', $insts->count));
        fwrite($f, "$x\n");

        $n = count($insts->id);
        if ($n > $max_len) {
            $max_len = $n;
            $max_id = $ic->id;
        }
    }
    printf("max IC length $max_len; ID $max_id\n");
    fclose($f);
}

echo "doing works\n";
do_works();
echo "doing scores\n";
do_scores();
echo "doing recordings\n";
do_recs();
echo "doing instrument combos\n";
do_inst_combos();

?>
