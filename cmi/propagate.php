<?php

// propagate info from parent compositions to sections

require_once('cmi_db.inc');

function main() {
    DB::begin_transaction();
    $comps = DB_composition::enum("children <> '' and children<>'[]'");
    foreach ($comps as $comp) {
        $child_ids = json_decode($comp->children);
        if (!$child_ids) continue;
        $ids = implode(',', $child_ids);
        echo "$comp->id\n";
        if (!$comp->instrument_combos) {
            $comp->instrument_combos = '[]';
        }
        if (!$comp->comp_types) {
            $comp->comp_types = '[]';
        }
        DB_composition::update_multi(
            sprintf(
                "creators='%s', composed=%d, published=%d, comp_types='%s', instrument_combos='%s', period=%d",
                $comp->creators, $comp->composed, $comp->published, $comp->comp_types, $comp->instrument_combos, $comp->period
            ),
            sprintf('id in (%s)', $ids)
        );
    }
    DB::commit_transaction();
}

main();
?>
