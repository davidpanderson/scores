<?php
require_once("imslp.inc");
require_once("bootstrap.inc");
require_once("imslp_db.inc");

function composer_list() {
    echo "<h2>Composers</h2>";
    $composers = DB_person::enum('');
    foreach ($composers as $c) {
        echo sprintf("<p><a href=composer.php?id=%d>%s %s</a>\n",
            $c->id, $c->first_name, $c->last_name
        );
    }
}

function search_form() {
    echo "<h2>Composition search</h2>";
    form_start('search.php');
    form_input_text(
        'Search terms
            <br><small>Title, composer, and/or instrument</small>
        ',
        'keywords'
    );
    form_submit('Search');
    form_end();
}

function main() {
    page_head("IMSLP/DB", true);
    composer_list();
    search_form();
    page_tail();
}

main();

?>
