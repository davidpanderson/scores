<?php

require_once('imslp_db.inc');
require_once('imslp.inc');

function composition_search_action($keywords) {
    $clause = sprintf("match (title, instrumentation) against ('%s')",
        DB::escape($keywords)
    );
    $clause .= " limit 10";
    $comps = DB_composition::enum($clause);
    page_head('Search results');
    foreach ($comps as $comp) {
        echo sprintf('<p><a href=composition.php?id=%d>%s</a> for %s',
            $comp->id, $comp->title, $comp->instrumentation
        );
    }
    if (!$comps) {
        echo "No compositions match '$keywords'.";
    }
    page_tail();
}

$keywords = get_str('keywords');
composition_search_action($keywords);

?>
