<?php
require_once("imslp.inc");
require_once("imslp_db.inc");

function main($id) {
    $c = DB_person::lookup_id($id);
    if (!$c) {
        error_page('no such person');
    }
    $name = "$c->first_name $c->last_name";
    page_head("$name");
    echo "<h2>Compositions</h2>";
    $compositions = DB_composition::enum("composer_id=$id");
    foreach ($compositions as $c) {
        echo sprintf("<p><a href=composition.php?id=%d>%s</a>",
            $c->id, $c->title
        );
    }
    page_tail();
}

$id = get_int('id');
main($id);

?>
