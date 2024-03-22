#! /usr/bin/env php

<?php

// input: lang.tags, e.g.
// # af = Afrikaans language
//
// output:
//      populate language table
//      data/lang_by_code.ser, serialized array like 'af' => 'Afrikaans'
//      data/lang_by_id.ser

require_once('cmi_db.inc');
require_once('cmi_util.inc');
require_once('write_ser.inc');

function main() {
    $lines = file('imslp_data/lang.tags');
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
    foreach ($langs as $code=>$name) {
        $id = DB_language::insert(
            sprintf("(code, name) values('%s', '%s')",
                DB::escape($code),
                DB::escape($name)
            )
        );
    }
}

main();
write_ser_language();

?>
