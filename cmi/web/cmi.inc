<?php

display_errors();

$tables = [
    'location'=>'Location',
    'person'=>'Person',
    'instrument'=>'Instrument',
    'composition'=>'Composition',
];

function locations_str($locs) {
    if (!$locs) return '';
    $locs = json_decode($locs);
    $x = [];
    foreach ($locs as $id) {
        $x[] = location_id_to_name($id);
    }
    return implode(', ', $x);
}

function comp_types_str($cts) {
    if (!$cts) return '';
    $cts = json_decode($cts);
    $x = [];
    foreach ($cts as $id) {
        $x[] = comp_type_id_to_name($id);
    }
    return implode(', ', $x);
}

function creators_str($cts) {
    if (!$cts) return '';
    $cts = json_decode($cts);
    $x = [];
    foreach ($cts as $id) {
        $prole = DB_person_role::lookup_id($id);
        $p = DB_person::lookup_id($prole->person);
        $x[] = sprintf(
            "<a href=item.php?type=person&id=%d>%s %s</a> (%s)",
            $p->id,
            $p->first_name, $p->last_name,
            role_id_to_name($prole->role)
        );
    }
    return implode(', ', $x);
}

function instrument_combo_str($id) {
    $ic = DB_instrument_combo::lookup_id($id);
    $is = json_decode($ic->instruments);
    $x = [];
    for ($i=0; $i<count($is->count); $i++) {
        if ($is->count[$i] == 1) {
            $x[] = instrument_id_to_name($is->id[$i]);
        } else {
            $x[] = sprintf('%d %s',
                $is->count[$i],
                instrument_id_to_name($is->id[$i])
            );
        }
    }
    return implode(' + ', $x);
}

function instrument_combos_str($ics) {
    if (!$ics) return '';
    $ics = json_decode($ics);
    $x = [];
    foreach ($ics as $id) {
        $x[] = instrument_combo_str($id);
    }
    return implode(', ', $x);
}

function sex_options() {
    $sexes = get_sexes();
    $x = [[0, '---']];
    foreach ($sexes as $s) {
        $x[] = [$s->id, $s->name];
    }
    return $x;
}

function country_options() {
    $cid = location_type_name_to_id('country');
    $locs = get_locations();
    $x = [[0, '---']];
    foreach ($locs as $loc) {
        if ($loc->type != $cid) continue;
        $x[] = [$loc->id, $loc->name];
    }
    return $x;
}

?>
