#! /usr/bin/env php

<?php

// inputs:
//  imslp_data/work_types.tags, e.g.
//      # adagiettos = Adagiettos
//  imslp_data/work_types_hier.tags
//
// outputs:
//  populate composition_type table
//  create serialized file data/comp_type_by_code.ser
//      maps code=>ct struct (with descendants)
//      used by populate_work.php

require_once('cmi_db.inc');

function make_ct($code, $name) {
    $ct = new DB_composition_type;
    $ct->imslp_code = $code;
    $ct->name = $name;
    return $ct;
}

// read MW data, return list of structs
//
function get_work_types() {
    $lines = file('imslp_data/work_types.tags');

    $cts = [];
    foreach ($lines as $line) {
        $x = explode('=', $line);
        $code = trim(substr($x[0], 2));
        if ($code == 'hymns') continue; // see below
        if ($code == 'stage works') continue; // see below
        $name = trim($x[1]);
        $cts[] = make_ct($code, $name);
    }
    // the following are referenced in hierarchy but are not in list
    $cts[] = make_ct('cantatas', 'Cantatas');
    $cts[] = make_ct('choruses', 'Choruses');
    $cts[] = make_ct('hymns', 'Hymns');
    $cts[] = make_ct('sacred hymns', 'Sacred hymns');
    $cts[] = make_ct('jazz', 'Jazz');
    $cts[] = make_ct('oratorios', 'Oratorios');
    $cts[] = make_ct('quartettinos', 'Quartettinos');
    $cts[] = make_ct('stage works', 'Stage works');
    return $cts;
}

// populate the composition_type table,
// and add an "id" field to each composition type struct
//
function populate($cts) {
    foreach ($cts as $ct) {
        $id = DB_composition_type::insert(
            sprintf("(imslp_code, name) values ('%s', '%s')",
                DB::escape($ct->imslp_code),
                DB::escape($ct->name)
            )
        );
        $ct->id = $id;
    }
}

// make the serialized files
//
function write_ser() {
    $cts = DB_composition_type::enum();
    $x = [];
    foreach ($cts as $ct) {
        $x[$ct->imslp_code] = $ct;
    }
    $f = fopen('data/comp_type_by_code.ser', 'w');
    fwrite($f, serialize($x));
    fclose($f);

    $x = [];
    foreach ($cts as $ct) {
        $x[$ct->id] = $ct;
    }
    $f = fopen('data/comp_type_by_id.ser', 'w');
    fwrite($f, serialize($x));
    fclose($f);
}

// parse work_types_hier.tags,
// and add a "desc" fields to the work type structs in $cts.
//
function parse_hier($cts) {
    // The file uses names, not codes, so make a lookup array
    //
    $cts_by_name = [];
    foreach ($cts as $n=>$ct) {
        $cts_by_name[$ct->name] = $n;
        $ct->desc = [];     // indices of descendants
    }
    $lines = file('imslp_data/work_types_hier.tags');
    $level = 0;
    $anc = [];      // indices of ancestors
    foreach ($lines as $line) {
        $n = strpos($line, ' ')-2;
        if ($n<0) continue;
        $name = trim(strstr($line, ' '));
        if ($name == 'Uncategorized pages') continue;
        $ind = $cts_by_name[$name];
        for ($i=0; $i<$n; $i++) {
            $ct = $cts[$anc[$i]];
            $ct->desc[] = $ind;
        }
        $anc[$n] = $ind;
    }
}

// update the work_type table to add descendants
//
function update_descendants($cts) {
    foreach ($cts as $ct) {
        $w = DB_composition_type::lookup("imslp_code='$ct->imslp_code'");
        if (!$w) {
            die("no code $ct->imslp_code\n");
        }
        $w->update(
            sprintf("descendant_ids='%s'",
                DB::escape(json_encode($ct->desc, JSON_NUMERIC_CHECK))
            )
        );
    }
}

function main() {
    $cts = get_work_types();
    echo count($cts)." work types\n";
    echo "populating work_type table\n";
    populate($cts);
    echo "computing descendants\n";
    parse_hier($cts);
    //print_r($cts);
    echo "updating table\n";
    update_descendants($cts);
}

main();
echo "writing .ser files\n";
write_ser();

?>
