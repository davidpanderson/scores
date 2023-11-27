<?php

// functions to look up items in various tables,
// create them if not there, and return ID
//
// tables:
// person
// copyright
// period
// publisher
// ensemble
// performer_role
// nationality

require_once("imslp_db.inc");

// add space after No. and Op. in work title.
// that way a search for 'beethoven symphony 9' works.
//
function fix_title($title) {
    $x = str_replace('No.', 'No. ', $title);
    $x = str_replace('Op.', 'Op. ', $x);
    return str_replace('  ', ' ', $x);  // in case there was already a space
}

// lookup performer (from audio file set) or composer (from work).
// Create (minimal) person if not there.
// Set composer/performer flags if needed
//
function get_person($first, $last, $is_composer, $is_performer) {
    global $test;
    $p = DB_person::lookup(
        sprintf("first_name='%s' and last_name='%s'",
            DB::escape($first),
            DB::escape($last)
        )
    );
    if ($p) {
        if ($is_composer && !$p->is_composer) {
            $p->update("is_composer=1");
        }
        if ($is_performer && !$p->is_performer) {
            $p->update("is_performer=1");
        }
        return $p->id;
    }
    $q = sprintf("(first_name, last_name, is_composer, is_performer) values ('%s', '%s', %d, %d)",
        DB::escape($first),
        DB::escape($last),
        $is_composer?1:0,
        $is_performer?1:0
    );
    if ($test) {
        echo "person insert: $q\n";
        $id = 0;
    } else {
        $id = DB_person::insert($q);
        if (!$id) {
            echo "person insert failed\n";
            exit;
        }
    }
    return $id;
}

// create a person (from parsed category page)
//
function make_person($p) {
    global $test;
    $n = $p->name;
    $pers = DB_person::lookup(
        sprintf("first_name='%s' and last_name='%s'",
            DB::escape($n[0]),
            DB::escape($n[1])
        )
    );
    if ($pers) {
        $name = $n[0].' '.$n[1];
        echo "person already exists: $name\n";
        return;
    }
    $q = sprintf("(first_name, last_name) values ('%s', '%s')",
        DB::escape($n[0]),
        DB::escape($n[1])
    );
    if ($test) {
        echo "person insert: $q\n";
        $person_id = 0;
    } else {
        $person_id = DB_person::insert($q);
        if (!$person_id) {
            echo "person insert failed\n";
            exit;
        }
    }
    $x = [];
    if (!empty($p->alternate_names)) {
        $x[] = sprintf("alternate_names='%s'", DB::escape($p->alternate_names));
    }
    if (!empty($p->birth_date)) {
        $x[] = sprintf("birth_date='%s'", DB::escape($p->birth_date));
    }
    if (!empty($p->birth_place)) {
        $x[] = sprintf("birth_place='%s'", DB::escape($p->birth_place));
    }
    if (!empty($p->born_year)) $x[] = "born_year=$p->born_year";
    if (!empty($p->born_month)) $x[] = "born_month=$p->born_month";
    if (!empty($p->born_day)) $x[] = "born_day=$p->born_day";
    if (!empty($p->death_date)) {
        $x[] = sprintf("death_date='%s'", DB::escape($p->death_date));
    }
    if (!empty($p->death_place)) {
        $x[] = sprintf("death_place='%s'", DB::escape($p->death_place));
    }
    if (!empty($p->died_year)) $x[] = "died_year=$p->died_year";
    if (!empty($p->died_month)) $x[] = "died_month=$p->died_month";
    if (!empty($p->died_day)) $x[] = "died_day=$p->died_day";
    if (!empty($p->flourished)) {
        $x[] = sprintf("flourished='%s'", DB::escape($p->flourished));
    }
    if (!empty($p->picture)) {
        $x[] = sprintf("picture='%s'", DB::escape($p->picture));
    }
    if (!empty($p->picture_caption)) {
        $x[] = sprintf("picture_caption='%s'", DB::escape($p->picture_caption));
    }
    if (!empty($p->sex)) {
        $x[] = sprintf("sex='%s'", DB::escape($p->sex));
    }
    if (!empty($p->signature)) {
        $x[] = sprintf("signature='%s'", DB::escape($p->signature));
    }
    if (!empty($p->nationality)) {
        $nat_ids = [];
        foreach ($p->nationality as $nat) {
            $nat_id = get_nationality($nat);
            if (!$nat_id) {
                echo "ERROR: no nat $nat\n";
                exit;
            }
            $nat_ids[] = $nat_id;
        }
        $x[] = sprintf("nationality_ids='%s'", json_encode($nat_ids, JSON_NUMERIC_CHECK));
    }
    if (!empty($p->time_period)) {
        $per_ids = [];
        foreach ($p->time_period as $per) {
            $per_id = get_period($per);
            if (!$per_id) {
                echo "ERROR: no per $per\n";
                exit;
            }
            $per_ids[] = $per_id;
        }
        $x[] = sprintf("period_ids='%s'", json_encode($per_ids, JSON_NUMERIC_CHECK));
    }
    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "comp update: $query\n";
        } else {
            $person = new DB_person;
            $person->id = $person_id;
            $ret = $person->update($query);
            if (!$ret) {
                echo "person update failed\n";
            }
        }
    }
}

// create ensemble as defined by a category
//
function make_ensemble($p) {
    global $test;
    $name = $p->name[0].' '.$p->name[1];
    $ens = DB_ensemble::lookup(
        sprintf("name='%s'", DB::escape($name))
    );
    if ($ens) {
        echo "ensemble already exists: $name\n";
        return;
    }
    $q = sprintf("(name) values ('%s')", DB::escape($name));
    if ($test) {
        echo "ensemble insert: $q\n";
        $ensemble_id = 0;
    } else {
        $ensemble_id = DB_ensemble::insert($q);
        if (!$ensemble_id) {
            echo "ensemble insert failed\n";
            exit;
        }
    }

    // get nationality and period IDs
    //
    if (count($p->nationality)) {
        $nationality_id = get_nationality($p->nationality[0]);
    } else {
        $nationality_id = 0;
    }
    if (count($p->time_period)) {
        $period_id = get_period($p->time_period[0]);
    } else {
        $period_id = 0;
    }

    $x = [];
    if (!empty($p->alternate_names)) {
        $x[] = sprintf("alternate_names='%s'", DB::escape($p->alternate_names));
    }
    if (!empty($p->born_year)) $x[] = "born_year=$p->born_year";
    if (!empty($p->died_year)) $x[] = "died_year=$p->died_year";
    if ($nationality_id) $x[] = "nationality_id=$nationality_id";
    if ($period_id) $x[] = "period_id=$period_id";
    if (!empty($p->picture)) {
        $x[] = sprintf("picture='%s'", DB::escape($p->picture));
    }
    if (!empty($p->instrument)) {
        $x[] = sprintf("type='%s'", DB::escape($p->instrument));
    }

    if ($x) {
        $query = implode(',', $x);
        if ($test) {
            echo "ensemble update: $query\n";
        } else {
            $ens = new DB_ensemble;
            $ens->id = $ensemble_id;
            $ret = $ens->update($query);
            if (!$ret) {
                echo "ensemble update failed\n";
            }
        }
    }
}

function get_copyright($name) {
    global $test;
    if (!$name) return 0;
    $p = DB_copyright::lookup(sprintf("name='%s'", DB::escape($name)));
    if ($p) return (int)$p->id;
    $q = sprintf("(name) values ('%s')", DB::escape($name));
    if ($test) {
        echo "copyright insert: $q\n";
        $id = 0;
    } else {
        $id = DB_copyright::insert($q);
        if (!$id) {
            echo "copyright insert failed\n";
            exit;
        }
    }
    return $id;
}

function get_period($name) {
    global $test;
    if (!$name) return 0;
    $p = DB_period::lookup(sprintf("name='%s'", DB::escape($name)));
    if ($p) return (int)$p->id;
    $q = sprintf("(name) values ('%s')", DB::escape($name));
    if ($test) {
        echo "period insert: $q\n";
        $id = 0;
    } else {
        $id = DB_period::insert($q);
        if (!$id) {
            echo "period insert failed\n";
            exit;
        }
    }
    return $id;
}

function get_nationality($name) {
    global $test;

    // $name can contain extra crap at the end
    //
    $name = str_replace(' composers', '', $name);
    $n = strpos($name, "\n");
    if ($n) $name = substr2($name, 0, $n);

    if (!$name) return 0;
    $p = DB_nationality::lookup(sprintf("name='%s'", DB::escape($name)));
    if ($p) return (int)$p->id;
    $q = sprintf("(name) values ('%s')", DB::escape($name));
    if ($test) {
        echo "nationality insert: $q\n";
        $id = 0;
    } else {
        $id = DB_nationality::insert($q);
        if (!$id) {
            echo "nationality insert failed\n";
            exit;
        }
    }
    return $id;
}

function get_publisher($name, $imprint, $location) {
    global $test;
    if (!$name) return 0;
    $p = DB_publisher::lookup(
        sprintf("name='%s' and imprint='%s' and location='%s'",
            DB::escape($name),
            DB::escape($imprint),
            DB::escape($location)
        )
    );
    if ($p) return (int)$p->id;
    $q = sprintf("(name, imprint, location) values ('%s', '%s', '%s')",
        DB::escape($name),
        DB::escape($imprint),
        DB::escape($location)
    );
    if ($test) {
        echo "publisher insert: $q\n";
        $id = 0;
    } else {
        $id = DB_publisher::insert($q);
        if (!$id) {
            echo "publisher insert failed\n";
            exit;
        }
    }
    return $id;
}

// called from audio file processing (minimal info)
//
function get_ensemble($name, $type) {
    global $test;
    if (!$name) return 0;
    $e = DB_ensemble::lookup(
        sprintf("name='%s'", DB::escape($name))
    );
    if ($e) return (int)$e->id;
    $q = sprintf("(name, type) values ('%s', '%s')",
        DB::escape($name),
        DB::escape($type)
    );
    if ($test) {
        echo "ensemble insert: $q\n";
        $id = 0;
    } else {
        $id = DB_ensemble::insert($q);
        if (!$id) {
            echo "ensemble insert failed\n";
            exit;
        }
    }
    return $id;
}

function get_arrangement_target($instruments) {
    global $test;
    if (!$instruments) return 0;
    $e = DB_arrangement_target::lookup(
        sprintf("instruments='%s'", DB::escape($instruments))
    );
    if ($e) return (int)$e->id;
    $q = sprintf("(instruments) values ('%s')",
        DB::escape($instruments)
    );
    if ($test) {
        echo "arrangement_target insert: $q\n";
        $id = 0;
    } else {
        $id = DB_arrangement_target::insert($q);
        if (!$id) {
            echo "arrangement_target insert failed\n";
            exit;
        }
    }
    return $id;
}

function get_performer_role($first, $last, $role) {
    global $test;
    $person_id = get_person($first, $last, false, true);
    $p = DB_performer_role::lookup(
        sprintf("person_id=%d and role='%s'",
            $person_id, DB::escape($role)
        )
    );
    if ($p) return (int)$p->id;
    $q = sprintf("(person_id, role) values (%d, '%s')",
        $person_id,
        DB::escape($role)
    );
    if ($test) {
        echo "performer_role insert: $q\n";
        $id = 0;
    } else {
        $id = DB_performer_role::insert($q);
        if (!$id) {
            echo "performer_role insert failed\n";
            exit;
        }
    }
    return $id;
}

// Get DB ID of an inst combo.
// Create new record if needed.
// For efficiency, cache the table in memory
//
$inst_combo_cache = [];
function get_inst_combo($ic) {
    static $first = true;
    static $insts_by_code;
    global $inst_combo_cache;
    if ($first) {
        // make an array keyed by md5
        $ics = DB_instrument_combo::enum();
        foreach ($ics as $i) {
            $inst_combo_cache[$i->md5] = $i;
        }
        $insts_by_code = unserialize(file_get_contents('data/inst_by_code.ser'));
        $first = false;
    }

    // $ic is a list of [count, code]
    // change to a struct x
    // x.count: list of counts
    // x.ids: list of IDs
    // The JSON version of this is stored in the DB
    //
    $x = new StdClass;
    $x->count=[];
    $x->id=[];
    foreach ($ic as $i=>$pair) {
        $x->count[] = $pair[0];
        $x->id[] = $insts_by_code[$pair[1]]->id;
    }

    // convert to JSON and get MD5
    //
    $ic_json = json_encode($x, JSON_NUMERIC_CHECK);
    $ic_md5 = md5($ic_json);

    // see if in cache
    //
    if (array_key_exists($ic_md5, $inst_combo_cache)) {
        $ic_rec = $inst_combo_cache[$ic_md5];
        return $ic_rec;
    }

    // no - create new entry
    //
    $id = DB_instrument_combo::insert(
        sprintf("(instruments, md5) values ('%s', '%s')",
            DB::escape($ic_json),
            DB::escape($ic_md5)
        )
    );

    // add new entry to cache
    //
    $ic_rec = DB_instrument_combo::lookup_id($id);
    $inst_combo_cache[$ic_md5] = $ic_rec;
    return $ic_rec;
}

// at the end of populate_works.php,
// update the count fields of instrument_combo, work_type, language
//
function flush_inst_combo_cache() {
    global $inst_combo_cache;

    foreach ($inst_combo_cache as $ic) {
        if ($ic->nworks==0 && $ic->nscores==0) continue;
        $ic->update(
            sprintf("nworks=%d, nscores=%d", $ic->nworks, $ic->nscores)
        );
    }
}

function flush_work_type_cache() {
    $wts_by_code = get_wts_by_code();
    foreach ($wts_by_code as $wt) {
        if ($wt->nworks==0) continue;
        $wt->update(sprintf("nworks=%d", $wt->nworks));
    }
}

function flush_lang_cache() {
    $langs_by_code = get_langs_by_code();
    foreach ($langs_by_code as $lang) {
        if ($lang->nworks==0) continue;
        $lang->update(sprintf("nworks=%d", $lang->nworks));
    }
}

?>
