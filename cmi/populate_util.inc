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

require_once("cmi_db.inc");
require_once("ser.inc");

// add space after No. and Op. in work title.
// that way a search for 'beethoven symphony 9' works.
//
function fix_title($title) {
    $x = str_replace('No.', 'No. ', $title);
    $x = str_replace('Op.', 'Op. ', $x);
    return str_replace('  ', ' ', $x);  // in case there was already a space
}

function person_born_num($p) {
    if (!empty($p->born_year)) {
        $y = (int)$p->born_year;
        $m = empty($p->born_month)?0:(int)$p->born_month;
        $d = empty($p->born_day)?0:(int)$p->born_day;
        if ($y) return DB::date_num($y, $m, $d);
    }
    if (!empty($p->birth_date)) {
        $y = (int)strpbrk($p->birth_date, '0123456789');
            // from first numeric char on
        if ($y) return DB::date_num($y);
    }
    return null;
}

function person_died_num($p) {
    if (!empty($p->died_year)) {
        $y = (int)$p->died_year;
        $m = empty($p->died_month)?0:(int)$p->died_month;
        $d = empty($p->died_day)?0:(int)$p->died_day;
        if ($y) return DB::date_num($y, $m, $d);
    }
    if (!empty($p->death_date)) {
        $y = (int)strpbrk($p->death_date, '0123456789');
        if ($y) return DB::date_num($y);
    }
    return null;
}

// lookup:
// performer (from audio file set)
// or composer (from work).
// or arranger (from score_file_set)
//
// Create (minimal) person if not there.
//
function get_person($first, $last, $born=0, $died=0) {
    global $test;
    $p = DB_person::lookup(
        sprintf("first_name='%s' and last_name='%s'",
            DB::escape($first),
            DB::escape($last)
        )
    );
    if ($p) {
        return $p->id;
    }
    $born_num = 0;
    $died_num = 0;
    if ($born) {
        $born_num = DB::date_num((int)$born);
    }
    if ($died) {
        $died_num = DB::date_num((int)$died);
    }
    $q = sprintf("(first_name, last_name%s%s) values ('%s', '%s'%s%s)",
        $born_num?', born':'', $died_num?', died':'',
        DB::escape($first),
        DB::escape($last),
        $born_num?", $born_num":'',
        $died_num?", $died_num":''
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
    $first = $n[0];
    $last = $n[1];
    $c = $last[0];
    if ($c=='.' || $c=='@' || ctype_digit($c)) {
        echo "bad last name: $last\n";
        return;
    }
    $pers = DB_person::lookup(
        sprintf("first_name='%s' and last_name='%s'",
            DB::escape($first),
            DB::escape($last)
        )
    );
    if ($pers) {
        $name = $first.' '.$last;
        echo "person already exists: $name\n";
        return;
    }
    $q = sprintf("(first_name, last_name) values ('%s', '%s')",
        DB::escape($first),
        DB::escape($last)
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
    $b = person_born_num($p);
    if ($b) {
        $x[] = sprintf("born=%d", $b);
    }
    $d = person_died_num($p);
    if ($d) {
        $x[] = sprintf("died=%d", $d);
    }
    if (!empty($p->sex)) {
        $s = sex_name_to_id($p->sex);
        if ($s) {
            $x[] = sprintf("sex=%d", $s);
        }
    }
    if (!empty($p->nationality)) {
        $loc_ids = [];
        foreach ($p->nationality as $nat) {
            $loc_id = get_location_id($nat);
            if ($loc_id) {
                $loc_ids[] = $loc_id;
            }
        }
        if ($loc_ids) {
            $x[] = sprintf("locations='%s'", json_encode($loc_ids, JSON_NUMERIC_CHECK));
        }
    }
    if (!empty($p->time_period)) {
        $per_ids = [];
        foreach ($p->time_period as $per) {
            $per_id = get_period_id($per);
            if (!$per_id) {
                echo "ERROR: no period $per\n";
                exit;
            }
            $per_ids[] = $per_id;
        }
        $x[] = sprintf("periods='%s'", json_encode($per_ids, JSON_NUMERIC_CHECK));
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

    $x = [];

    // get nationality and period IDs
    //
    if (count($p->nationality)) {
        $loc_id = get_location_id($p->nationality[0]);
        if ($loc_id) $x[] = "location=$loc_id";
    }
    if (count($p->time_period)) {
        $period_id = get_period_id($p->time_period[0]);
        if ($period_id) $x[] = "period=$period_id";
    }

    if (!empty($p->alternate_names)) {
        $x[] = sprintf("alternate_names='%s'", DB::escape($p->alternate_names));
    }
    if (!empty($p->born_year)) {
        $d = DB::date_num((int)$p->born_year);
        if ($d) {
            $x[] = "started=$d";
        }
    }
    if (!empty($p->died_year)) {
        $d = DB::date_num((int)$p->died_year);
        if ($d) {
            $x[] = "ended=$d";
        }
    }
    if (!empty($p->instrument)) {
        $id = get_ensemble_type_id($p->instrument);
        $x[] = sprintf("type=%d", $id);
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

function get_license_id($name) {
    global $test;
    if (!$name) return 0;
    $p = DB_license::lookup(sprintf("name='%s'", DB::escape($name)));
    if ($p) return (int)$p->id;
    $q = sprintf("(name) values ('%s')", DB::escape($name));
    if ($test) {
        echo "license insert: $q\n";
        $id = 0;
    } else {
        $id = DB_license::insert($q);
        if (!$id) {
            echo "license insert failed\n";
            exit;
        }
    }
    return $id;
}

function get_period_id($name) {
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

function get_ensemble_type_id($name) {
    $p = DB_ensemble_type::lookup(sprintf("name='%s'", DB::escape($name)));
    if ($p) return (int)$p->id;
    $q = sprintf("(name) values ('%s')", DB::escape($name));
    return DB_ensemble_type::insert($q);
}

function get_location_id($name) {
    global $test;

    // $name can contain extra crap at the end
    //
    $name = str_replace(' composers', '', $name);
    $n = strpos($name, "\n");
    if ($n) $name = substr2($name, 0, $n);
    $n = strpos($name, " <");
    if ($n) $name = substr2($name, 0, $n);
    $n = strpos($name, " (");
    if ($n) $name = substr2($name, 0, $n);

    if (!$name) return 0;
    if ($name=='Unaffiliated') return 0;
    if ($name=='Other nationality') return 0;
    if ($name=='Englsh') $name='English';
    if ($name=='german') $name='German';
    if ($name=='japanese') $name='Japanese';
    if ($name=='Argentine') $name='Argentinian';
    if ($name=='<Serbian>') $name='Serbian';
    if (strstr($name, 'Neapolitan')) $name = 'Neapolitan';
        // non-ascii char at end
    if (strstr($name, 'Italian')) $name = 'Italian';
    $id = location_name_to_id($name);
    if (!$id) {
        die ("nationality $name not found\n");
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
    $person_id = get_person($first, $last);
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

// Given an inst combo (as count/code list),
// get DB ID of an inst combo DB record,
// creating one if needed.
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

// at the end of populate_comp.php,
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
