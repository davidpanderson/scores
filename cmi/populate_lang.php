#! /usr/bin/env php

<?php

// input: lang.tags, e.g.
// # af = Afrikaans language
//
// output:
//      populate language table
//      data/lang_by_code.ser, serialized array like 'af' => 'Afrikaans'

require_once('cmi_db.inc');
require_once('cmi_util.inc');

function main() {
    $lines = file('data/lang.tags');
    $langs = [];
    foreach ($lines as $line) {
        $parts = explode(' ', $line);
        $code = $parts[1];
        $i = strpos($line, '=');
        $j = strpos($line, 'language');
        $name = substr2($line, $i+2, $j-1);
        //echo "$code: $name\n";
        $langs[$code] = $name;
    }
    echo count($langs)." languages\n";

    echo "populating language table\n";
    $lang_by_code = [];
    foreach ($langs as $code=>$name) {
        $id = LANGUAGE::insert(
            sprintf("(code, name) values('%s', '%s')",
                DB::escape($code),
                DB::escape($name)
            )
        );
        $rec = LANGUAGE::lookup_id($id);
        $lang_by_code[$code] = $rec;
    }

    echo "writing lang_by_code.ser\n";
    $f = fopen('data/lang_by_code.ser', 'w');
    fwrite($f, serialize($lang_by_code));
    fclose($f);
}

main();

?>
