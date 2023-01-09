<?php

// parse work_types.tags, e.g.
// # adagiettos = Adagiettos
// and work_types_hier.tags
//
// 1) populate work_type table
// 2) create serialized files
//      - work_type_by_code.ser
//          maps code=>wt struct (with descendants)
//          used by populate_work.php
//      - work_type_name_by_id.ser
//          maps id=>name
//          used by web code

require_once('imslp_db.inc');

function make_wt($code, $name) {
    $wt = new StdClass;
    $wt->code = $code;
    $wt->name = $name;
    return $wt;
}

// read MW data, return list of structs
//
function get_work_types() {
    $lines = file('work_types.tags');

    $wts = [];
    foreach ($lines as $line) {
        $x = explode('=', $line);
        $code = trim(substr($x[0], 2));
        if ($code == 'hymns') continue; // see below
        if ($code == 'stage works') continue; // see below
        $name = trim($x[1]);
        $wts[] = make_wt($code, $name);
    }
    // the following are referenced in hierarchy but are not in list
    $wts[] = make_wt('cantatas', 'Cantatas');
    $wts[] = make_wt('choruses', 'Choruses');
    $wts[] = make_wt('hymns', 'Hymns');
    $wts[] = make_wt('sacred hymns', 'Sacred hymns');
    $wts[] = make_wt('jazz', 'Jazz');
    $wts[] = make_wt('oratorios', 'Oratorios');
    $wts[] = make_wt('quartettinos', 'Quartettinos');
    $wts[] = make_wt('stage works', 'Stage works');
    return $wts;
}

// populate the work_type table,
// and add an "id" field to each work type struct
//
function populate($wts) {
    foreach ($wts as $wt) {
        $id = DB_work_type::insert(
            sprintf("(code, name) values ('%s', '%s')",
                DB::escape($wt->code),
                DB::escape($wt->name)
            )
        );
        $wt->id = $id;
    }
}

// make the serialized files
//
function write_ser($wts) {
    $x = [];
    foreach ($wts as $wt) {
        $x[$wt->code] = $wt;
    }
    $f = fopen('work_type_by_code.ser', 'w');
    fwrite($f, serialize($x));
    fclose($f);

    $x = [];
    foreach ($wts as $wt) {
        $x[$wt->id] = $wt->name;
    }
    $f = fopen('work_type_name_by_id.ser', 'w');
    fwrite($f, serialize($x));
    fclose($f);
}

// parse work_types_hier.tags,
// and add a "desc" fields to the work type structs in $wts.
//
function parse_hier($wts) {
    // The file uses names, not codes, so make a lookup array
    //
    $wts_by_name = [];
    foreach ($wts as $n=>$wt) {
        $wts_by_name[$wt->name] = $n;
        $wt->desc = [];     // indices of descendants
    }
    $lines = file('work_types_hier.tags');
    $level = 0;
    $anc = [];      // indices of ancestors
    foreach ($lines as $line) {
        $n = strpos($line, ' ')-2;
        if ($n<0) continue;
        $name = trim(strstr($line, ' '));
        if ($name == 'Uncategorized pages') continue;
        $ind = $wts_by_name[$name];
        for ($i=0; $i<$n; $i++) {
            $wt = $wts[$anc[$i]];
            $wt->desc[] = $ind;
        }
        $anc[$n] = $ind;
    }
}

// update the work_type table to add descendants
//
function update_descendants($wts) {
    foreach ($wts as $wt) {
        $w = DB_work_type::lookup("code='$wt->code'");
        if (!$w) echo "no $wt->code\n";
        $w->update(
            sprintf("descendant_ids='%s'", DB::escape(json_encode($wt->desc)))
        );
    }
}

$wts = get_work_types();
populate($wts);
//print_r($wts);
parse_hier($wts);
update_descendants($wts);
write_ser($wts);

?>
