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
    if (!$person->nationality_ids) return '';
    $x = [];
    $nat_ids = json_decode($person->nationality_ids);
    foreach ($nat_ids as $id) {
        $x[] = nationality_name($id);
    }
    return implode(', ', $x);
}

// period(s) of a person as string
//
function person_period_string($person) {
    if (!$person->period_ids) return '';
    $x = [];
    $per_ids = json_decode($person->period_ids);
    foreach ($per_ids as $id) {
        $x[] = period_name($id);
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

function show_person_detail($p) {
    start_table();
    row2('First name', $p->first_name);
    row2('Last name', $p->last_name);
    if ($p->alternate_names) {
        row2('Alternate names', str_replace(';', '<br>', $p->alternate_names));
    }
    $x = person_birth_string($p);
    if ($x) {
        row2('Born', $x);
    }
    if ($p->birth_place) {
        row2('Birth place', $p->birth_place);
    }
    $x = person_death_string($p);
    if ($x) {
        row2('Died', $x);
    }
    if ($p->death_place) {
        row2('Death place', $p->death_place);
    }
    if ($p->flourished) {
        row2('Flourished', $p->flourished);
    }
    if ($p->nationality_ids) {
        row2('Nationality', person_nationality_string($p));
    }
    if ($p->period_ids) {
        row2('Period', person_period_string($p));
    }
    if ($p->picture) {
        row2('Picture',
            sprintf('<a href="https://imslp.org/wiki/File:%s">%s</a>',
                $p->picture, $p->picture
            )
        );
    }
    if ($p->picture_caption) {
        row2('Picture caption', $p->picture_caption);
    }
    if ($p->sex) {
        row2('Sex', $p->sex);
    }
    if ($p->signature) {
        row2('Signature',
            sprintf('<a href="https://imslp.org/wiki/File:%s">%s</a>',
                $p->signature, $p->signature
            )
        );
    }
    end_table();
}

function show_work_detail($c) {
    start_table('');
    $composer = DB_person::lookup_id($c->composer_id);
    $name = "$composer->first_name $composer->last_name";
    row2('Composer', "<a href=person.php?id=$composer->id>$name</a>");
    if ($c->opus_catalogue) {
        row2('Opus', $c->opus_catalogue);
    }
    if ($c->comments) {
        row2('Comments', $c->comments);
    }
    if ($c->dedication) {
        row2('Dedication', $c->dedication);
    }
    if ($c->external_links) {
        row2('External links', $c->external_links);
    }
    if ($c->first_performance) {
        row2('First performance', $c->first_performance);
    }
    if ($c->_key) {
        row2('Key', $c->_key);
    }
    if ($c->movements_header) {
        row2('Movements', $c->movements_header);
    }
    if ($c->tags) {
        row2('Tags', $c->tags);
    }
    if ($c->year_date_of_composition) {
        row2('Composition date', $c->year_date_of_composition);
    }
    if ($c->year_of_first_publication) {
        row2('Publication date', $c->year_of_first_publication);
    }
    if ($c->instrumentation) {
        row2('Instrumentation', $c->instrumentation);
    }
    end_table();
}

function show_ensemble_detail($e) {
    start_table();
    row2('Name', $e->name);
    if ($e->alternate_names) {
        row2('Alternate names', $e->alternate_names);
    }
    if ($e->born_year) {
        row2('Start year', $e->born_year);
    }
    if ($e->died_year) {
        row2('End year', $e->died_year);
    }
    if ($e->nationality_id) {
        row2('Nationality', nationality_name($e->nationality_id));
    }
    if ($e->period_id) {
        row2('Period', period_name($e->period_id));
    }
    if ($e->picture) {
        row2('Picture', $e->picture);
    }
    if ($e->type) {
        row2('Type', $e->type);
    }
    end_table();
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
    form_input_text('Last name', 'last_name', 'Any');
    form_select('Period', 'period_id', period_options());
    form_select('Nationality', 'nationality_id', nationality_options());
    form_radio_buttons('Type', 'person_type', person_type_options(), 'either');
    form_radio_buttons('Sex', 'sex', sex_options(), 'either');
    form_submit('Find people');
    form_end();
}

function ensemble_search_form() {
    form_start('search_ensemble.php');
    form_input_text('Name', 'name', 'Any');
    form_select('Type', 'type', ensemble_type_options(), 'any');
    form_select('Period', 'period_id', period_options());
    form_select('Nationality', 'nationality_id', nationality_options());
    form_submit('Find ensembles');
    form_end();
}
?>
