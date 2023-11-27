#! /usr/bin/env php

<?php

// input: list of instrument names, e.g.
// # acc = For accordion + Scores featuring the accordion
// # acc gtr = For accordion, guitar + Scores featuring the accordion + Scores featuring the guitar
//
// output:
//      inst_by_code.ser
//          serialized array like 'acc' => struct
//      populate instrument table

require_once('imslp_db.inc');

function main() {
    $lines = file('data/inst.txt');
    $insts = [];
    foreach ($lines as $line) {
        $x = explode('=', $line);
        if (count($x) != 2) {
            continue;
        }
        $y = explode(' ', trim($x[0]));
        if (count($y)!=2) continue;
        $z = $y[1];
        if (is_numeric($z[0])) continue;
        $code = $z;

        if ($code == 'alt') $name = 'alto';
        else if ($code == 'bar') $name = 'baritone';
        else if ($code == 'bass') $name = 'bass';
        else if ($code == 'bbar') $name = 'bass/baritone';
        else if ($code == 'mez') $name = 'mezzosoprano';
        else if ($code == 'sop') $name = 'soprano';
        else if ($code == 'ten') $name = 'tenor';
        else if ($code == 'v') $name = 'voice';
        else if ($code == 'open') $name = 'Unspecified instrument';
        else {
            $y = trim($x[1]);
            $z = explode('+', $y);
            if (count($z)==1) {
                $name = $y;
            } else {
                $name = $z[0];
            }
            if (substr($name, 0, 4) != 'For ') continue;
            $name = substr($name, 4);
            $name = trim($name);
        }

        //echo "$code : $name\n";
        $insts[$code] = $name;
    }
    echo count($insts)." instruments\n";

    echo "populating instrument table\n";
    $inst_by_code = [];
    foreach ($insts as $code=>$name) {
        $id = DB_instrument::insert(
            sprintf("(code, name) values('%s', '%s')",
                DB::escape($code),
                DB::escape($name)
            )
        );
        $rec = DB_instrument::lookup_id($id);
        $inst_by_code[$code] = $rec;
    }

    echo "writing inst_by_code.ser\n";
    $f = fopen('data/inst_by_code.ser', 'w');
    fwrite($f, serialize($inst_by_code));
    fclose($f);
}

main();

?>
