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

function show_period() {
    foreach (DB_period::enum() as $p) {
        $n = DB_composition::count("period = $p->id");
        echo "$p->id $p->name $n\n";
    }
}

function repair_period() {
    insert_period('New Age');
    delete_period("（必要）");
    delete_period("All'inizio del XX secolo");
    delete_period('Early 20th cenutry');
    delete_period('Impressionist-Expressionist');
    move_period('Classica', 'Classical');
    rename_period('early Baroque', 'Early Baroque');
    move_period('Galant', 'Baroque');
    delete_period('Impresionismo andino');
    move_period('jazzy-andino', 'New Age');
    rename_period('Modern/Classical', 'Neo-Classical');
    move_period('Neobarock', 'Neo-Baroque');
    move_period('Pseudo-Classical', 'Neo-Classical');
    delete_period('tradicional colombiano');
    move_period('Traditional folk', 'Traditional (folk)');
    move_period('Traditional folk song', 'Traditional (folk)');
    move_period('Western/Chinese/Japanese/Classic/Pop/Mixed', 'Non-western classical');
}

function delete_period($name) {
    $p = DB_period::lookup(sprintf("name='%s'", DB::escape($name)));
    DB_composition::update_multi('period=0', "period=$p->id");
    $p->delete();
}

function move_period($old, $new) {
    $p1 = DB_period::lookup(sprintf("name='%s'", DB::escape($old)));
    $p2 = DB_period::lookup(sprintf("name='%s'", DB::escape($new)));
    DB_composition::update_multi("period=$p2->id", "period=$p1->id");
    $p1->delete();
}

function rename_period($old, $new) {
    $p = DB_period::lookup(sprintf("name='%s'", DB::escape($old)));
    $p->update(sprintf("name='%s'", DB::escape($new)));
}
    
function insert_period($name) {
    DB_period::insert(sprintf("(name) values ('%s')", DB::escape($name)));
}

missing_title();
repair_period();
//show_period();
?>
