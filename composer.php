<?php
require_once("imslp_web.inc");
require_once("imslp_util.inc");
require_once("imslp_db.inc");

function show_composer($id) {
    $c = DB_person::lookup_id($id);
    if (!$c) {
        error_page('no such person');
    }
    $name = "$c->first_name $c->last_name";
    page_head("$name");
    echo "<h2>Compositions</h2>";
    $compositions = DB_composition::enum("composer_id=$id", 'order by year_of_composition');
    start_table('table-striped');
    row_heading_array(['Title', 'Year', 'Instrumentation']);
    foreach ($compositions as $c) {
        [$t, $first, $last] = parse_title($c->title);
        row_array([
            sprintf("<p><a href=composition.php?id=%d>%s</a>",
                $c->id, $t
            ),
            $c->year_of_composition?$c->year_of_composition:'---',
            $c->instrumentation
        ]);
    }
    end_table();
    page_tail();
}

function composer_list() {
    page_head("Composers");
    $composers = DB_person::enum('is_composer=1', 'order by last_name');
    foreach ($composers as $c) {
        echo sprintf("<p><a href=composer.php?id=%d>%s, %s</a>\n",
            $c->id, $c->last_name, $c->first_name
        );
    }
    page_tail();
}

$id = get_int('id', true);
if ($id) {
    show_composer($id);
} else {
    composer_list();
}

?>
