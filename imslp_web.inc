<?php

// IMSLP-specific web utility functions

require_once("imslp_db.inc");

// hierarchy terms (Complete etc.) as a/b/c
//
function hier_string($fs) {
    $x = [];
    if ($fs->hier1) $x[]=$fs->hier1;
    if ($fs->hier2) $x[]=$fs->hier2;
    if ($fs->hier3) $x[]=$fs->hier3;
    return implode('/', $x);
}

// birth info using available info
//
function person_birth_string($person) {
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

function person_death_string($person) {
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

function get_periods() {
    static $p = null;
    if ($p === null) {
        $p = unserialize(file_get_contents('period.ser'));
    }
    return $p;
}

// map period id to name using serialized table
//
function period_name($id) {
    $p = get_periods();
    return $p[$id]->name;
}

function get_nationalities() {
    static $n = null;
    if ($n === null) {
        $n = unserialize(file_get_contents('nationality.ser'));
    }
    return $n;
}

function nationality_name($id) {
    $n = get_nationalities();
    return $n[$id]->name;
}

// nationality(ies) of a person as string
//
function person_nationality_string($person) {
    $pns = DB_person_nationality::enum("person_id = $person->id");
    $x = [];
    foreach ($pns as $pn) {
        $x[] = nationality_name($pn->nationality_id);
    }
    return implode(', ', $x);
}

// period(s) of a person as string
//
function person_period_string($person) {
    $pps = DB_person_period::enum("person_id = $person->id");
    $x = [];
    foreach ($pps as $pp) {
        $x[] = period_name($pp->period_id);
    }
    return implode(', ', $x);
}

function person_table_heading() {
    row_heading_array([
        'Name', 'Composer', 'Performer', 'Born', 'Died', 'Sex', 'Nationality', 'Period'
    ]);
}

function person_table_row($p) {
    row_array([
        sprintf("<p><a href=person.php?id=%d>%s, %s</a>\n",
            $p->id, $p->last_name, $p->first_name
        ),
        $p->is_composer?'yes':'',
        $p->is_performer?'yes':'',
        person_birth_string($p),
        person_death_string($p),
        $p->sex,
        person_nationality_string($p),
        person_period_string($p)
    ]);
}

////////////   Search-related stuff //////////////

function period_options() {
    $opts = [[0, 'Any']];
    $periods = get_periods();
    foreach ($periods as $p) {
        $opts[] = [$p->id, $p->name];
    }
    return $opts;
}

function nationality_options() {
    $opts = [[0, 'Any']];
    $nats = get_nationalities();
    foreach ($nats as $n) {
        $opts[] = [$n->id, $n->name];
    }
    return $opts;
}

function sex_options() {
    return [
        ["male", "Male"],
        ["female", "Female"],
        ["either", "Either"]
    ];
}

// all/composer/performer
//
function person_type_options() {
    return [
        ["composer", "Composer"],
        ["performer", "Performer"],
        ["either", "Either"]
    ];
}

function ensemble_type_options() {
    $types = DB_ensemble::types();
    $opts = [['any', 'Any']];
    foreach ($types as $type) {
        $opts[] = [$type, $type];
    }
    return $opts;
}

function work_search_form() {
    form_start('search_work.php');
    form_input_text(
        'Keywords
            <br><small>Title, composer, and/or instrument</small>
        ',
        'keywords'
    );
    form_select('Period', 'period_id', period_options());
    form_submit('Find works');
    form_end();
}

function person_search_form() {
    form_start('search_person.php');
    form_radio_buttons('Type', 'person_type', person_type_options(), 'either');
    form_select('Period', 'period_id', period_options());
    form_select('Nationality', 'nationality_id', nationality_options());
    form_radio_buttons('Sex', 'sex', sex_options(), 'either');
    form_submit('Find people');
    form_end();
}

function ensemble_search_form() {
    form_start('search_ensemble.php');
    form_select('Type', 'type', ensemble_type_options(), 'any');
    form_select('Period', 'period_id', period_options());
    form_select('Nationality', 'nationality_id', nationality_options());
    form_submit('Find ensembles');
    form_end();
}
?>
