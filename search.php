<?php

require_once('imslp_db.inc');
require_once('imslp.inc');

function composition_search_action($keywords) {
    // remove commas
    //
    $k = str_replace(',', ' ', $keywords);

    // add the plural of each word
    //
    $k2 = explode(' ', $k);
    $k3 = $k2;
    foreach ($k2 as $k) {
        $k3[] = $k.'s';
    }
    $k3 = implode(' ', $k3);
    $clause = sprintf("match (title, instrumentation) against ('%s')",
        DB::escape($k3)
    );
    $clause .= " limit 50";
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
