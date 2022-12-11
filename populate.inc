<?php

// functions for creating database entries,
// given the structures passed from parse.php

// look up person, create if not there.
// return ID
//
function get_person($first, $last) {
    $p = IMSLP_person::lookup(
        sprintf("first_name='%s' and last_name='%s'", $first, $last)
    );
    if ($p) return $p->id;
    $id = IMSLP_person::insert(
        sprintf("(first_name, last_name) values ('%s', '%s')", $first, $last)
    );
    return $id;
}

function make_composition($c) {
    $composer_id = get_person($c->composer_first, $c->composer_last);
    $composition_id = IMSLP_composition::insert(
        sprintf("(composer_id, title, opus) values (%d, '%s', '%s')",
            $composer_id, $c->title, $c->opus
        )
    );
    if (!$composition_id) {
        echo "insert failed for $composer_id, $c->title, $c->opus\n";
        return;
    }
    $x = [];
    if (!empty($c->alternative_title)) {
        $x[] = sprintf("alternative_title='%s'", $c->alternative_title);
    }
    if (!empty($c->key)) {
        $x[] = sprintf("key='%s'", $c->key);
    }
    if (!empty($c->nmovements)) {
        $x[] = sprintf("nmovements=%d", $c->nmovements);
    }
    if (!empty($c->movement_names)) {
        $x[] = sprintf("movement_names='%s'", $c->movement_names);
    }
    if (!empty($c->incipit)) {
        $x[] = sprintf("incipit='%s'", $c->incipit);
    }
    if (!empty($c->dedication)) {
        $x[] = sprintf("dedication='%s'", $c->dedication);
    }
    if (!empty($c->composition_date)) {
        $x[] = sprintf("composition_date='%s'", $c->composition_date);
    }
    if (!empty($c->first_performance)) {
        $x[] = sprintf("first_performance='%s'", $c->first_performance);
    }
    if (!empty($c->publication_date)) {
        $x[] = sprintf("publication_date='%s'", $c->publication_date);
    }
    if (!empty($c->average_dur_min)) {
        $x[] = sprintf("average_dur_min=%d", $c->average_dur_min);
    }

    if ($x) {
        $query = collapse(',', $x);
        $comp = new IMSLP_composition;
        $comp->id = $composition_id;
        $comp->update($query);
    }
}

function make_files($cid, $files) {
}

?>
