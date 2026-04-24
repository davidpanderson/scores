<?php

// unsuccessful scraper

require_once('cmi_db.inc');

// 76000 sound files
// say 10MB each.  .76 TB
//
// 670K PDFs.
// Say 2MB each.  1.34 TB

// strategy:
// generate pages of 10K files (a0.html .. a76.html, s0.html .. s670)

define('NLINES', 10000);

function get_files($items, $prefix) {
    $n = 0;
    $ifile = 0;
    $f = null;
    foreach ($items as $s) {
        $files = json_decode($s->files);
        foreach ($files as $file) {
            if (!$n) {
                if ($f) {
                    fwrite($f, "</body>\n");
                    fclose($f);
                }
                $f = fopen(
                    sprintf('f/%s%d.html', $prefix, $ifile),
                    'w'
                );
                fwrite($f, "<head><meta charset=\"utf-8\"></head><body>\n");
                $ifile++;
            }
            fwrite($f,
                sprintf(
                    '<a href="https://imslp.org/wiki/Special:ImagefromIndex/%s">%s</a><br>
',
                    $file->name,
                    $file->name
                )
            );
            $n++;
            if ($n == NLINES) {
                $n = 0;
            }
        }
    }
    fwrite($f, "</body>\n");
    fclose($f);
}

get_files(DB_score::enum(), 's');
get_files(DB_performance::enum(), 'a');

?>
