<?php

// generate an .htaccess file for top searches

require_once('cmi_db.inc');
require_once('web/cmi.inc');

function display_errors(){}

function main() {
    $x = json_decode(file_get_contents('top_comps.json'));
    $htf = fopen('.htaccess', 'w');
    $smf = fopen('sitemap.xml', 'w');
    fwrite($smf,
'<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
');
    foreach ($x as $person) {
        $pid = $person->person_id;
        $p = DB_person::lookup_id($pid);
        $name = $p->last_name;
        if ($name == 'Bach') {
            $name = "$p->first_name $p->last_name";
            $name = str_replace(' ', '-', $name);
        }
        foreach ($person->insts as $cid) {
            $url = sprintf('https://classicalmusicindex.org/search.php?type=composition&composer_id=%d&inst_combo_id=%d',
                $pid, $cid
            );
            $ic = DB_instrument_combo::lookup_id($cid);
            $x = sprintf(
                'Redirect "/s/%s/%s" %s',
                $name,
                instrument_combo_str($ic),
                $url
            );
            $x .= "\n";
            fwrite($htf, $x);

            $x = sprintf(
'<url>
   <loc>https://classicalmusicindex.org/s/%s/%s</loc>
</url>
',
                urlencode($name), urlencode(instrument_combo_str($ic)),
            );
            fwrite($smf, $x);
        }

        // 'all' entry must go last

        $url = sprintf('https://classicalmusicindex.org/search.php?type=composition&composer_id=%d',
            $pid
        );
        $x = sprintf(
            'Redirect "/s/%s" %s',
            $name,
            $url
        );
        $x .= "\n";
        fwrite($htf, $x);

        $x = sprintf(
 '<url>
   <loc>https://classicalmusicindex.org/s/%s</loc>
</url>
',
            urlencode($name)
        );
        fwrite($smf, $x);
    }
    $us = [
        '',
        'music_discover.php',
        'db_info.php',
        'about.php',
        'editing.php',
        'search.php?type=composition',
        'search.php?type=person',
        'search.php?type=inst_combo'
    ];
    foreach ($us as $u) {
        fwrite($smf, sprintf(
 '<url>
   <loc>https://classicalmusicindex.org/%s</loc>
</url>
',
            urlencode($u)
        ));
    }
    fwrite($smf, "</urlset>\n");
}

main();

?>
