<?php

// generate an .htaccess file for top searches

require_once('cmi_db.inc');
require_once('web/cmi.inc');

function display_errors(){}

function main() {
    $x = json_decode(file_get_contents('top_comps.json'));
    foreach ($x as $person) {
        $pid = $person->person_id;
        $p = DB_person::lookup_id($pid);
        foreach ($person->insts as $cid) {
            $url = sprintf('https://classicalmusicindex.org/search.php?type=composition&composer_id=%d&inst_combo_id=%d',
                $pid, $cid
            );
            $ic = DB_instrument_combo::lookup_id($cid);
            echo sprintf(
                'Redirect "/s/%s/%s" %s',
                $p->last_name,
                instrument_combo_str($ic),
                $url
            );
            echo "\n";
        }

        // 'all' entry must go last

        $url = sprintf('https://classicalmusicindex.org/search.php?type=composition&composer_id=%d',
            $pid
        );
        echo sprintf(
            'Redirect "/s/%s" %s',
            $p->last_name,
            $url
        );
        echo "\n";
    }
}

main();

?>
