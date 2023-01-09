<?php

// input: list of instrument names, e.g.
// # acc = For accordion + Scores featuring the accordion
// # acc gtr = For accordion, guitar + Scores featuring the accordion + Scores featuring the guitar
//
// output: serialized array like
// 'acc' => 'according'
//
// TODO: populate instrument table

function main() {
    $lines = file('inst.txt');
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

        echo "$code : $name\n";
        $insts[$code] = $name;
    }
    $f = fopen('inst_names.ser', 'w');
    fwrite($f, serialize($insts));
    fclose($f);
}

main();

?>
