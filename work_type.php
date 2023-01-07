<?php

// parse work_types.tags, e.g.
// # adagiettos = Adagiettos
// compute array of code=>name,
// and write to .ser file
//
function get_work_types() {
    $lines = file('work_types.tags');

    $wts = [];
    foreach ($lines as $line) {
        $x = explode('=', $line);
        $code = trim(substr($x[0], 2));
        $name = trim($x[1]);
        $wts[$code] = $name;
        echo "$code: $name\n";
    }
    $f = fopen('work_type_names.ser', 'w');
    fwrite($f, serialize($wts));
    fclose($f);
}

// parse work_types_hier.tags
//
function parse_hier() {
    $lines = file('work_types_hier.tags');
    foreach ($lines as $line) {
        $n = strpos($line, ' ');
        $x = trim(strstr($line, ' '));
        echo "$n $x\n";
    }
}

get_work_types();
//parse_hier();

?>
