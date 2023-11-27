<?php

// scan recordings (audio_file_set) where 'performers' is of the form
// {{GardnerPerf|xxx}}
//
// if ensemble_id not set
//      If xxx is the name of an ensemble
//          fill in ensemble_id
// is performer_role_ids not set
//      If xxx is the name of a person,
//          let yyy be the recording's instrument combo (text)
//          look up a performer role (xxx, yyy); create if needed
//          set performer_role_ids to [x]
//
// We do this as a separate pass (after populate_work)
// because a {{GardnerPerf|Joe Smith}}
// might precede a {{GardnerPerf|Joe Smith, piano}}

require_once('imslp_db.inc');

function main() {
    $fss = DB_audio_file_set::enum(
        "performers like '{{GardnerPerf%' and ensemble_id=0 and performer_role_ids is NULL"
        //"performers like '{{GardnerPerf%'"
    );
    foreach ($fss as $fs) {
        echo "$fs->id $fs->performers\n";
    }
}

main();

?>
