<?php

// functions to look up items in secondary tables,
// create them if not there, and return ID
//
// person
// copyright
// style
// publisher
// ensemble
// performer_role

require_once("imslp_db.inc");

// set composer/performer flags if needed
// return ID
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

function get_copyright($name) {
    global $test;
    if (!$name) return 0;
    $p = DB_copyright::lookup(sprintf("name='%s'", DB::escape($name)));
    if ($p) return $p->id;
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

function get_style($name) {
    global $test;
    if (!$name) return 0;
    $name = str_replace('_', ' ', $name);
        // e.g. Early_20th_century
    $p = DB_style::lookup(sprintf("name='%s'", DB::escape($name)));
    if ($p) return $p->id;
    $q = sprintf("(name) values ('%s')", DB::escape($name));
    if ($test) {
        echo "style insert: $q\n";
        $id = 0;
    } else {
        $id = DB_style::insert($q);
        if (!$id) {
            echo "style insert failed\n";
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
    if ($p) return $p->id;
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

function get_ensemble($name, $type) {
    global $test;
    if (!$name) return 0;
    $e = DB_ensemble::lookup(
        sprintf("name='%s'", DB::escape($name))
    );
    if ($e) return $e->id;
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
    if ($e) return $e->id;
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
    if ($p) return $p->id;
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

?>
