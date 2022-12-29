<?php

// IMSLP-specific web utility functions

function hier_string($fs) {
    $x = [];
    if ($fs->hier1) $x[]=$fs->hier1;
    if ($fs->hier2) $x[]=$fs->hier2;
    if ($fs->hier3) $x[]=$fs->hier3;
    return implode('/', $x);
}

function birth_string($person) {
    if ($person->born_year) {
        if ($person->born_month) {
            if ($person->born_day) {
                return sprintf('%d/%d/%d',
                    $person->born_day, $person->born_month, $person->born_year
                );
            } else {
                return sprintf('%d/%d',
                    $person->born_month, $person->born_year
                );
            }
        } else {
            return "$person->born_year";
        }
    }
    return $person->birth_date;
}

function death_string($person) {
    if ($person->died_year) {
        if ($person->died_month) {
            if ($person->died_day) {
                return sprintf('%d/%d/%d',
                    $person->died_day, $person->died_month, $person->died_year
                );
            } else {
                return sprintf('%d/%d',
                    $person->died_month, $person->died_year
                );
            }
        } else {
            return "$person->died_year";
        }
    }
    return $person->death_date;
}

function nationality_string($person) {
    static $n = null;
    if ($n === null) {
        $n = unserialize(file_get_contents('nationality.ser'));
    }
    $pns = DB_person_nationality::enum("person_id = $person->id");
    $x = [];
    foreach ($pns as $pn) {
        $x[] = $n[$pn->nationality_id]->name;
    }
    return implode(', ', $x);
}

function period_string($person) {
    static $p = null;
    if ($p === null) {
        $p = unserialize(file_get_contents('period.ser'));
    }
    $pps = DB_person_period::enum("person_id = $person->id");
    $x = [];
    foreach ($pps as $pp) {
        $x[] = $p[$pp->period_id]->name;
    }
    return implode(', ', $x);
}

?>
