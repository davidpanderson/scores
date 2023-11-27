<?php
require_once("web.inc");
require_once("imslp_web.inc");

function tags() {
    echo "
        <p>
        <a href=tags.php?action=inst_form>Instruments</a>
        <p>
        <a href=tags.php?action=wt_form>Work types</a>
        <p>
        <a href=tags.php?action=lang_form>Languages</a>
    ";
}

function main() {
    page_head("IMSLP/DB", true);

    tags();
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
