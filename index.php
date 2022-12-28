<?php
require_once("web.inc");
require_once("imslp_db.inc");

function style_options() {
    $styles = DB_style::enum('');
    $x  =[[0, 'Any']];
    foreach ($styles as $style) {
        $x[] = [$style->id, $style->name];
    }
    return $x;
}

function search_form() {
    echo "<h2>Composition search</h2>";
    form_start('search_work.php');
    form_input_text(
        'Search terms
            <br><small>Title, composer, and/or instrument</small>
        ',
        'keywords'
    );
    form_select('Style', 'style_id', style_options());
    form_submit('Search');
    form_end();
}

function main() {
    page_head("IMSLP/DB", true);
    search_form();

    echo "<h2>Composers</h2>";
    show_button('composer.php', 'View all');
    echo "<h2>Publishers</h2>";
    show_button('publisher.php', 'View all');
    echo "<h2>Performers</h2>";
    show_button('performer.php', 'View all');
    echo "<h2>Ensembles</h2>";
    show_button('ensemble.php', 'View all');
    echo "<h2>Arrangements</h2>";
    show_button('arrangement.php', 'View all');
    page_tail();
}

main();

?>
