<?php
require_once("web.inc");
require_once("imslp_web.inc");

function main() {
    page_head("IMSLP/DB", true);
    echo "<h2>Works</h2>";
    work_search_form();
    form_start('');
    form_general(
        "Arrangements", button_text("arrangement.php", "View all combos")
    );
    form_end();

    echo "<h2>People</h2>";
    person_search_form();

    echo "<h2>Ensembles</h2>";
    ensemble_search_form();

    echo "<h2>Publishers</h2>";
    form_start('');
    form_general(
        "", button_text('publisher.php', 'View all')
    );
    form_end();
    page_tail();
}

main();

?>
