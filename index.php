<?php
require_once("imslp.inc");
require_once("imslp_db.inc");

function main() {
    page_head("IMSLP/DB", true);
    echo "<h2>Composers</h2>";
    $composers = DB_person::enum('');
    foreach ($composers as $c) {
        echo sprintf("<p><a href=composer.php?id=%d>%s %s</a>\n",
            $c->id, $c->first_name, $c->last_name
        );
    }
    page_tail();
}

main();

?>
