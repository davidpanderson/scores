<?php

require_once("parse_category.inc");
require_once("populate_util.inc");

function main($file, $nlines) {
    $f = fopen($file, 'r');
    for ($i=0; $i<$nlines; $i++) {
        echo "JSON record $i\n";
        $x = fgets($f);
        if (!$x) {
            echo "end of file\n";
            break;
        }
        if (!trim($x)) continue;
        $y = json_decode($x);
        if (!$y) {
            echo "bad JSON: $x\n";
            continue;
        }
        DB::begin_transaction();
        foreach ($y as $title => $body) {
            //echo "$title\n";
            if (strpos($title, 'Category:') !== 0) continue;
            $p = parse_person($title, $body);
            if (!$p) continue;
            $type = empty($p->type)?'':strtolower($p->type);
            if ($type === 'other') {
                // usually a category, e.g. German Folk Songs
                // or random other stuff, like RISM
                continue;
            }
            // ensembles can be either #fte:performer or #fte:person
            //
            if ($type == 'organization') {
                if (empty($p->instrument)) {
                    // TODO: in some cases instrument is missing
                    // but could be inferred from name
                    continue;
                }
                $p->instrument = strtolower($p->instrument);
                if (starts_with($p->instrument, 'opera company')) {
                    $p->instrument = 'opera company';
                }
                echo "got organization $p->instrument\n";
                switch ($p->instrument) {
                case 'band':
                case 'chamber ensemble':
                case 'choir':
                case 'chorus':
                case 'early music ensemble':
                case 'ensemble':
                case 'mixed chorus':
                case 'opera company':
                case 'orchestra':
                case 'period-instrument ensemble':
                case 'string quartet':
                case 'vocal ensemble':
                    make_ensemble($p);
                    break;
                }
            } else {
                make_person($p);
            }
        }
        DB::commit_transaction();
    }
}

// there are 480 lines
main('david_category_template_dump.txt', 100000);

?>
