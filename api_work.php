<?php

require_once('imslp_db.inc');
require_once('imslp_web.inc');
require_once('web.inc');
require_once('ser.inc');
require_once('template.inc');

function error($str) {
    $x = new StdClass;
    $x->error_msg = $str;
    echo json_encode($x, JSON_PRETTY_PRINT);
}

function do_work($work_id) {
    $inst_combos = get_inst_combos();
    $w = DB_work::lookup_id($work_id);
    if (!$w) {
        error('no such work');
    }
    $x = new StdClass;
    $x->title = $w->title;
    if ($w->dedication) {
        $x->dedication = $w->dedication;
    }
    if ($w->instrument_combo_ids) {
        $y = [];
        $ics = json_decode($w->instrument_combo_ids);
        foreach ($ics as $ic_id) {
            $y[] = [$ic_id,
                inst_combo_str_struct(
                    json_decode($inst_combos[$ic_id]->instruments)
                )
            ];
        }
        $x->instrument_combos = $y;
    }
    if ($w->number_of_movements_sections) {
        $x->nmovements = $w->number_of_movements_sections;
    }
    if ($w->period_id) {
        $x->period = period_name($w->period_id);
    }
    if ($w->year_of_composition) {
        $x->year_of_composition = $w->year_of_composition;
    }
    if ($w->year_of_first_publication) {
        $x->year_of_first_publication = $w->year_of_first_publication;
    }

    $scores = DB_score_file_set::enum("work_id=$work_id");
    $y = [];
    foreach ($scores as $score) {
        $s = new StdClass;
        if ($score->arranger) {
            $s->arranger = expand_mw_text($score->arranger);
        }
        if ($score->copyright_id) {
            $s->license = copyright_id_to_name($score->copyright_id);
        }
        if ($score->date_submitted) {
            $s->date_submitted = $score->date_submitted;
        }
        if ($score->hier1) $s->hier1 = $score->hier1;
        if ($score->hier2) $s->hier2 = $score->hier2;
        if ($score->hier3) $s->hier3 = $score->hier3;
        if ($score->misc_notes) {
            $s->misc_notes = expand_mw_text($score->misc_notes);
        }
        if ($score->publisher_information) {
            $s->publisher_information = expand_mw_text($score->publisher_information);
        }
        if ($score->scanner) {
            $s->scanner = expand_mw_text($score->scanner);
        }
        if ($score->thumb_filename) {
            $s->thumb_filename = $score->thumb_filename;
        }
        if ($score->uploader) {
            $s->uploader = expand_mw_text($score->uploader);
        }

        $files = DB_score_file::enum("score_file_set_id=$score->id");
        $z = [];
        foreach ($files as $file) {
            $f = new StdClass;
            $f->file_name = $file->file_name;
            $f->file_description = $file->file_description;
            if ($file->thumb_filename) {
                $f->thumb_filename = $file->thumb_filename;
            }
            $z[] = $f;
        }
        $s->files = $z;
        $y[] = $s;
    }
    $x->scores = $y;

    $recordings = DB_audio_file_set::enum("work_id=$work_id");
    $y = [];
    foreach ($recordings as $rec) {
        $r = new StdClass;
        if ($rec->copyright_id) {
            $r->license = copyright_id_to_name($rec->copyright_id);
        }
        if ($rec->date_submitted) {
            $r->date_submitted = $rec->date_submitted;
        }
        if ($rec->ensemble_id) {
            $ens = DB_ensemble::lookup_id($rec->ensemble_id);
            $r->ensemble = $ens->name;
        }
        if ($rec->hier1) $r->hier1 = $rec->hier1;
        if ($rec->hier2) $r->hier2 = $rec->hier2;
        if ($rec->hier3) $r->hier3 = $rec->hier3;
        if ($rec->performer_role_ids) {
            $prs = [];
            $pr_ids = json_decode($rec->performer_role_ids);
            foreach ($pr_ids as $pr_id) {
                $pr = DB_performer_role::lookup_id($pr_id);
                $person = DB_person::lookup_id($pr->person_id);
                $prs[] = ["$person->first_name $person->last_name", $pr->role];
            }
            $r->performers = $prs;
        }
        if ($rec->publisher_information) {
            $r->publisher_information = expand_mw_text($rec->publisher_information);
        }
        if ($rec->thumb_filename) {
            $r->thumb_filename = $rec->thumb_filename;
        }
        if ($rec->uploader) {
            $r->uploader = expand_mw_text($rec->uploader);
        }

        $files = DB_audio_file::enum("audio_file_set_id=$rec->id");
        $z = [];
        foreach ($files as $file) {
            $f = new StdClass;
            $f->file_name = $file->file_name;
            $z[] = $f;
        }
        $r->files = $z;
        $y[] = $r;
    }
    $x->recordings = $y;
    header('Content-Type: application/json');
    echo json_encode($x, JSON_PRETTY_PRINT|JSON_NUMERIC_CHECK);
}

$id = get_int('id');
do_work($id);
?>
