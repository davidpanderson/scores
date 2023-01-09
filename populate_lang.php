<?php

require_once('imslp_util.inc');

// input: lang.tags, e.g.
// # af = Afrikaans language
//
// output: lang_names.ser, serialized array like
// 'af' => 'Afrikaans'
//
// TODO: populate language table

function main() {
    $lines = file('lang.tags');
    $langs = [];
    foreach ($lines as $line) {
        $parts = explode(' ', $line);
        $code = $parts[1];
        $i = strpos($line, '=');
        $j = strpos($line, 'language');
        $name = substr2($line, $i+2, $j-1);
        echo "$code: $name\n";
        $langs[$code] = $name;
    }
    $f = fopen('lang_names.ser', 'w');
    fwrite($f, serialize($langs));
    fclose($f);
}

main();

?>
