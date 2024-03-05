<?php

// write .ser files by ID and by name

function write_ser($items, $name) {
    $x = [];
    $y = [];
    foreach ($items as $item) {
        $x[$item->name] = $item;
        $y[$item->id] = $item;
    }
    $f = fopen(
        sprintf('data/%s_by_name.ser', $name),
        'w'
    );
    fwrite($f, serialize($x));
    fclose($f);
    $f = fopen(
        sprintf('data/%s_by_id.ser', $name),
        'w'
    );
    fwrite($f, serialize($y));
    fclose($f);
}
?>
