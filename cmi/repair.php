#! /usr/bin/env php

<?php

require_once('cmi_db.inc');

// title is null.  Get it from the long title
function missing_title() {
    $comps = DB_composition::enum('title is null');
    foreach ($comps as $c) {
        $x = explode(' (', $c->long_title);
        $c->update(sprintf("title='%s'", DB::escape($x[0])));
    }
}

missing_title();
?>
