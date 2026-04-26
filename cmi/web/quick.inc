<?php

require_once('../inc/util.inc');
require_once('cmi.inc');

function compare($x, $y) {
    if ($x->name > $y->name) return 1;
    if ($x->name < $y->name) return -1;
    return 0;
}

function quick_search() {
    $x = json_decode(file_get_contents('top_comps.json'));
    uasort($x, 'compare');
    foreach ($x as $person) {
        $pid = $person->person_id;
        $p = DB_person::lookup_id($pid);
        echo "<h4>$p->first_name $p->last_name</h4>\n<ul>\n";
        $url = sprintf('https://classicalmusicindex.org/search.php?type=composition&composer_id=%d',
            $pid
        );
        $url = sprintf('s/%s', $p->last_name);
        echo "<li> <a href=$url>All</a>\n";
        foreach ($person->insts as $cid) {
            $url = sprintf('https://classicalmusicindex.org/search.php?type=composition&composer_id=%d&inst_combo_id=%d',
                $pid, $cid
            );
            $ic = DB_instrument_combo::lookup_id($cid);
            $url = sprintf('s/%s/%s',
                $p->last_name, instrument_combo_str($ic)
            );
            echo sprintf("<li> <a href=\"%s\">%s</a>\n",
                $url,
                instrument_combo_str($ic)
            );
        }
        echo "</ul>\n";
    }
}

?>
