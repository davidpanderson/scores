<?php

require_once('web.inc');
require_once('imslp_db.inc');

// PHP wrapper for query_items() API function

// table codes

define('WORK',      1);
define('SCORE',     2);
define('RECORDING', 3);

// result type codes

define('WORK_ID',       1);
define('SCORE_ID',      2);
define('RECORDING_ID',  3);
define('INSTRUMENT_ID', 4);
define('COMPOSER_ID',   5);
define('NATIONALITY_ID',6);
define('PERIOD_ID',     7);
define('WORK_TYPE_ID',  8);
define('PUBLISHER_ID',  9);

function do_req($req) {
    $cmd = ['./api'];
    $cmd[] = "--table $req->table";
    $cmd[] = "--result_type $req->result_type";
    if (!empty($req->offset)) {
        $cmd[] = "--offset $req->offset";
    }
    if (!empty($req->limit)) {
        $cmd[] = "--limit $req->limit";
    }
    if (!empty($req->composer_nationality)) {
        foreach ($req->composer_nationality as $x) {
            $cmd[] = "--composer_nationality $x";
        }
    }
    if (!empty($req->inst_combo)) {
        foreach ($req->inst_combo as $x) {
            $cmd[] = "--inst_combo_id $x";
        }
    }
    if (!empty($req->instrument)) {
        foreach ($req->instrument as $x) {
            $cmd[] = "--instrument $x";
        }
    }
    if (!empty($req->inst_combo_spec)) {
        foreach ($req->inst_combo_spec->instruments as $x) {
            $cmd[] = sprintf("--inst_spec %d %d %d", $x[0], $x[1], $x[2]);
        }
        if (!empty($req->inst_combo_spec->other_insts_ok)) {
            $cmd[] = "--inst_spec_others_ok";
        }
    }
    if (!empty($req->keywords)) {
        $wds = explode(' ', $req->keywords);
        foreach ($wds as $wd) {
            $cmd[] = "--keyword ".strtolower($wd);
        }
    }
    if (!empty($req->period)) {
        foreach ($req->period as $x) {
            $cmd[] = "--period $x";
        }
    }
    if (!empty($req->pub_year)) {
        $cmd[] = sprintf("--pub_year %d %d",
            $req->pub_year[0], $req->pub_year[1]
        );
    }
    if (!empty($req->publisher)) {
        $cmd[] = "--publisher $req->publisher";
    }
    if (!empty($req->work_type)) {
        foreach ($req->work_type as $x) {
            $cmd[] = "--work_type $x";
        }
    }
    $cmd = implode(' ', $cmd);
    exec($cmd, $output, $exit_code);
    return [$cmd, $exit_code, $output[0]];
}

function form() {
    page_head("API test");
    form_start("api3.php");
    form_input_textarea(
        'Arguments (JSON)
        <br><a href=https://bitbucket.org/imslp/imslp-new-parser/src/master/api.md>API documentation</a>
        <br><a href=http://34.83.223.220/scores/api3.php?codes=1>Lists of IDs</a>
        <br><a href=http://34.83.223.220/scores/api_examples.txt>Examples</a>
        ',
        'req', '', 20
    );
    form_submit('OK');
    form_end();
    page_tail();
}

function action() {
    $x = get_str('req');
    $req = json_decode($x);
    if (!$req) {
        error_page("Can't parse arguments");
    }
    $start = microtime(true);
    [$cmd, $exit_code, $out] = do_req($req);
    $dt = microtime(true)-$start;
    $dt *= 1000;
    $dt = number_format($dt, 2);
    page_head("Results");
    echo "<p>Command: $cmd<p>\n";
    echo "time: $dt msec<p>\n";
    if ($exit_code) {
        echo "command failed<p>exit code: $exit_code<p>output: '$out'<p>\n";
    } else {
        if (!$out) {
            echo "No results";
        } else {
            show_results(explode(' ', $out), $req->result_type);
        }
    }
    page_tail();
}

function show_results($result, $result_type) {
    $list = implode(',', $result);
    switch ($result_type) {
    case WORK_ID:
        $results = DB_work::enum("id in ($list)");
        break;
    case SCORE_ID:
        $results = DB_score_file_set::enum("id in ($list)");
        break;
    case RECORDING_ID:
        $results = DB_audio_file_set::enum("id in ($list)");
        break;
    case INSTRUMENT_ID:
        $results = DB_instrument::enum("id in ($list)");
        break;
    case COMPOSER_ID:
        $results = DB_person::enum("id in ($list)");
        break;
    case NATIONALITY_ID:
        $results = DB_nationality::enum("id in ($list)");
        break;
    case PERIOD_ID:
        $results = DB_period::enum("id in ($list)");
        break;
    case WORK_TYPE_ID:
        $results = DB_work_type::enum("id in ($list)");
        break;
    case PUBLISHER_ID:
        $results = DB_publisher::enum("id in ($list)");
        break;
    }

    $x = [];
    foreach ($results as $r) {
        switch($result_type) {
        case WORK_ID:
            $x[] = "$r->id) <a href=work.php?id=$r->id>$r->title</a>";
            break;
        case SCORE_ID:
            $w = DB_work::lookup_id($r->work_id);
            $x[] = "$r->id) <a href=score.php?id=$r->id>$w->title</a>";
            break;
        case RECORDING_ID:
            $w = DB_work::lookup_id($r->work_id);
            $x[] = "$r->id) <a href=recording.php?id=$r->id>$w->title</a>";
            break;
        case INSTRUMENT_ID:
            $x[] = "$r->id) $r->name";
            break;
        case COMPOSER_ID:
            $x[] = "$r->id) <a href=person.php?id=$r->id>$r->first_name $r->last_name</a>";
            break;
        case NATIONALITY_ID:
            $x[] = "$r->id) $r->name";
            break;
        case PERIOD_ID:
            $x[] = "$r->id) $r->name";
            break;
        case WORK_TYPE_ID:
            $x[] = "$r->id) $r->name";
            break;
        case PUBLISHER_ID:
            $x[] = "$r->id) $r->name";
            break;
        }
    }
    echo implode('<br>', $x);
}

function test() {
    $spec = new StdClass;
    $spec->instruments = [
        [115, 1, 2],
        [166, 1, 1]
    ];
    $spec->other_insts_ok = true;

    $x = new StdClass;
    $x->table_code = 1;
    $x->result_type = 1;
    $x->inst_combo_spec = $spec;
    $x->keywords = ['Mozart'];

    do_req($x);
}

function show_table($items) {
    foreach ($items as $item) {
        echo "$item->id: $item->name<br>\n";
    }
}

function show_codes() {
    page_head("Codes");
    echo "
        <h3>Tables</h3>
        <p>
        1: Works <br>
        2: Scores <br>
        3: Recordings

        <h3>Result types</h3>
        <p>
        1: work <br>
        2: score <br>
        3: recording <br>
        4: instrument <br>
        5: composer <br>
        6: nationality <br>
        7: period <br>
        8: work type <br>
        9: publisher <br>
        <h3>Publishers</h3>
        <p>
        <a href=http://34.83.223.220/scores/publisher.php>View</a>
        <p>
        <h3>Other</h3>
    ";
    start_table();
    row_heading_array([
        "Instruments",
        "Nationalities",
        "Periods",
        "Work types"
    ]);
    echo "<tr><td>";
    show_table(DB_instrument::enum());
    echo "</td><td>";
    show_table(DB_nationality::enum());
    echo "</td><td>";
    show_table(DB_period::enum());
    echo "</td><td>";
    show_table(DB_work_type::enum());
    echo "</td></tr>";
    end_table();
    page_tail();
}

if (get_str('req', true)) {
    action();
} else if (get_str('codes', true)) {
    show_codes();
} else {
    form();
}
?>
