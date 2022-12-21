<?php
require_once("imslp_web.inc");
require_once("imslp_db.inc");

function style_options() {
    $styles = DB_style::enum('');
    $x  =[[0, 'Any']];
    foreach ($styles as $style) {
        $x[] = [$style->id, $style->name];
    }
    return $x;
}

function composer_list() {
    echo "<h2>Composers</h2>";
    $composers = DB_person::enum('', 'order by last_name');
    foreach ($composers as $c) {
        echo sprintf("<p><a href=composer.php?id=%d>%s, %s</a>\n",
            $c->id, $c->last_name, $c->first_name
        );
    }
}

function search_form() {
    echo "<h2>Composition search</h2>";
    form_start('search_comp.php');
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
    composer_list();
    page_tail();
}

main();

?>
