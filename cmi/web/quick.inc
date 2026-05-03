<?php

require_once('../inc/util.inc');
require_once('cmi.inc');

function compare($x, $y) {
    if ($x->name > $y->name) return 1;
    if ($x->name < $y->name) return -1;
    return 0;
}

function get_entries() {
    $x = json_decode(file_get_contents('top_comps.json'));
    uasort($x, 'compare');
    $entries = [];
    foreach ($x as $person) {
        $pid = $person->person_id;
        $p = DB_person::lookup_id($pid);
        $entry = "<h4>$p->first_name $p->last_name</h4>\n<ul>\n";
        $url = sprintf('https://classicalmusicindex.org/search.php?type=composition&composer_id=%d',
            $pid
        );
        $name = $p->last_name;
        if ($name == 'Bach') {
            $name = "$p->first_name $p->last_name";
            $name = str_replace(' ', '-', $name);
        }
        $url = sprintf('s/%s', $name);
        $entry .= "<li> <a href=$url>All</a>\n";
        foreach ($person->insts as $cid) {
            $url = sprintf('https://classicalmusicindex.org/search.php?type=composition&composer_id=%d&inst_combo_id=%d',
                $pid, $cid
            );
            $ic = DB_instrument_combo::lookup_id($cid);
            $url = sprintf('s/%s/%s',
                $name, instrument_combo_str($ic)
            );
            $entry .= sprintf("<li> <a href=\"%s\">%s</a>\n",
                $url,
                instrument_combo_str($ic)
            );
        }
        $entry .= "</ul>\n";
        $entries[] = $entry;
    }
    return $entries;
}

function quick_search() {
    $entries = get_entries();
    start_table();
    $n = count($entries);
    $m = intdiv($n, 2);
    for ($i=0; $i<$m; $i++) {
        echo sprintf("<tr><td>%s</td><td>%s</td></tr>\n",
            $entries[$i*2], $entries[$i*2+1]
        );
    }
    if ($n != $m*2) {
        echo sprintf("<tr><td>%s</td><td></td></tr>\n",
            $entries[$m*2]
        );
    }
    end_table();
}

?>
