#! /usr/bin/env php

<?php

// Populate composition.audio_file
// For each composition, enumerate its performances.
// Pick a .mp3 file from one of them
// Prioritize non-synthesized performances with original instruments.
// (could use criteria like ratings if we had them)

require_once('cmi_db.inc');

function main() {
    // make an array mapping comp ID to list of performances
    //
    $perfs = DB_performance::enum();
    $comps = [];
    foreach ($perfs as $p) {
        $cid = $p->composition;
        if (!array_key_exists($cid, $comps)) {
            $comps[$cid] = [$p];
        } else {
            $comps[$cid][] = $p;
        }
    }
    foreach ($comps as $id=>$perfs) {
        do_comp($id, $perfs);
        echo "$id\n";
    }
}

function do_comp($id, $perfs) {
    // priority:
    //  1) non-synth, original inst
    //  2) synth, original inst
    //  3) non-synth, other inst
    //  4) synth, other inst
    $nsyn_orig = null;
    $syn_orig = null;
    $nsyn_other = null;
    $syn_other = null;
    foreach ($perfs as $perf) {
        $files = json_decode($perf->files);
        if (!$files) continue;
        $name = $files[0]->name;
        if (!str_ends_with($name, '.mp3')) continue;
        if ($perf->is_synthesized) {
            if ($perf->instrumentation) {
                $syn_other = $name;
            } else {
                $syn_orig = $name;
            }
        } else {
            if ($perf->instrumentation) {
                $nsyn_other = $name;
            } else {
                $nsyn_orig = $name;
            }
        }
    }
    $file = null;
    if ($nsyn_orig) $file = $nsyn_orig;
    // else if ($syn_orig) $file = $syn_orig;
    // else if ($nsyn_other) $file = $nsyn_other;
    // else if ($syn_other) $file = $syn_other;
    if (!$file) return;
    $c = new DB_composition;
    $c->id = $id;
    $c->update(sprintf("audio_file='%s'", DB::escape($file)));
}

main();
?>
