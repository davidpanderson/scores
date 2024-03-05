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

?>
