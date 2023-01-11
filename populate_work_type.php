#! /usr/bin/env php

<?php

// parse work_types.tags, e.g.
// # adagiettos = Adagiettos
// and work_types_hier.tags
//
// 1) populate work_type table
// 2) create serialized file work_type_by_code.ser
//      maps code=>wt struct (with descendants)
//      used by populate_work.php

require_once('imslp_db.inc');

function make_wt($code, $name) {
    $wt = new DB_work_type;
    $wt->code = $code;
    $wt->name = $name;
    $wt->nworks = 0;
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
    $wts_by_code = [];
    foreach ($wts as $wt) {
        $wts_by_code[$wt->code] = $wt;
    }
    $f = fopen('work_type_by_code.ser', 'w');
    fwrite($f, serialize($wts_by_code));
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
            sprintf("descendant_ids='%s'",
                DB::escape(json_encode($wt->desc, JSON_NUMERIC_CHECK))
            )
        );
    }
}

$wts = get_work_types();
echo count($wts)." work types\n";
echo "populating work_type table\n";
populate($wts);
echo "computing descendants\n";
parse_hier($wts);
//print_r($wts);
echo "updating table\n";
update_descendants($wts);
echo "writing .ser files\n";
write_ser($wts);

?>
