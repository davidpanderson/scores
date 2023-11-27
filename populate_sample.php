#! /usr/bin/env php

<?php

require_once("imslp_db.inc");
require_once("imslp_util.inc");
require_once("ser.inc");

// for each work, find a sample recording.
// Use only MP3 files (no MIDI)
// Give preferences to recordings that are not synthesized or transcriptions

function work_samples() {
    $works = DB_work::enum();
    foreach ($works as $work) {
        $sets = DB_audio_file_set::enum("work_id=$work->id");
        if (!$sets) continue;

        // look for one that's not synthesized or an arrangement
        // synth means hier1 = 'Synthesized/MIDI'
        // arr means hier3 = 'For ...'
        //
        $best_file = null;
        $first_file = null;
        foreach ($sets as $s) {
            if ($s->hier1 == '' and $s->hier3 == '') {
                $files = DB_audio_file::enum("audio_file_set_id=$s->id", "limit 1");
                if (!$files) continue;
                $f = $files[0];
                if (str_ends_with($f->file_name, '.mp3')) {
                    $best_file = $f;
                    break;
                }
            } else if (!$first_file) {
                $files = DB_audio_file::enum("audio_file_set_id=$s->id", "limit 1");
                if (!$files) continue;
                $f = $files[0];
                if (str_ends_with($f->file_name, '.mp3')) {
                    $first_file = $f;
                }
            }
        }
        if (!$best_file) $best_file = $first_file;
        if (!$best_file) continue;

        $work->update(sprintf("sample_audio_file_name='%s', sample_audio_file_id=%d",
            DB::escape($best_file->file_name), $best_file->id
        ));
        //echo "$work->id\n"; break;
    }
}

// for arrangement scores, see if there's a recording with a
// similar instrument combo.
// Link to the best of these.
//
// If the score has the same instrumentation as the work
// (at least one match), skip it.
//
function score_samples() {
    $inst_combos = get_inst_combos();
    $scores = DB_score_file_set::enum("instrument_combo_ids<>''");
    foreach ($scores as $score) {
        $score_combos = json_decode($score->instrument_combo_ids);
        $sets = DB_audio_file_set::enum(
            "work_id=$score->work_id and instrument_combo_id<>0"
        );
        if (!$sets) continue;

        // if score has same instrumentation as work, skip
        //
        $work = DB_work::lookup_id($score->work_id);
        $work_combos = json_decode($work->instrument_combo_ids);
        if ($work_combos && array_intersect($score_combos, $work_combos)) {
            continue;
        }

        // look for a recording with the most similar instrumentation;
        // prefer not synthesized (hier1 = 'Synthesized/MIDI')
        //
        $best_sim = 0;
        $best_rec = null;

        // loop over recordings
        //
        foreach ($sets as $s) {
            if ($s->hier1 == 'Accompaniments') continue;
            $rec_combo = json_decode($inst_combos[$s->instrument_combo_id]->instruments);
            $is_synth = ($s->hier1=='Synthesized/MIDI');
            foreach ($score_combos as $sc_id) {
                $score_combo = json_decode($inst_combos[$sc_id]->instruments);
                $sim = inst_combo_similarity($rec_combo, $score_combo);
                if ($sim > $best_sim || ($sim==$best_sim && !$is_synth)) {
                    $best_sim = $sim;
                    $best_rec = $s;
                }
            }
        }
        if ($best_sim <= 0) continue;
        $files = DB_audio_file::enum("audio_file_set_id=$best_rec->id", "limit 1");
        if (!$files) continue;
        $f = $files[0];
        if (!str_ends_with($f->file_name, '.mp3')) continue;
        if (0) {
            echo "work $score->work_id score $score->id rec $best_rec->id file $f->id sim $best_sim\n";
        } else {
            $score->update(sprintf(
                "sample_audio_file_name='%s', sample_audio_file_id=%d",
                DB::escape($f->file_name), $f->id
            ));
        }
    }
}

//work_samples();
score_samples();

?>
