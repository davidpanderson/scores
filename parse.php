<?php

// parse the IMSLP data (in JSON/mediawiki format) and populate the SQL DB

require_once("template.inc");

// given Piano_Sonata_No.13,_Op.27_No.1_(Beethoven,_Ludwig_van)
// returnjj
function parse_title() {
}

function do_composition($title, $body) {
    echo "processing $title\n";
    if (starts_with($body, '#REDIRECT')) {
        echo "Redirect; skipping\n";
        return;
    }
    [$template_name, $args] = parse_template($body);
    echo "got $template_name\n";
    if (strtolower($template_name) == 'attrib') {
        echo "Got attrib:\n";
    } else if ($template_name == '#fte:imslppage') {
        foreach ($args as $name=>$arg) {
            echo "arg $name:\n";
            if ($name === '*****AUDIO*****') {
            } else if ($name === '*****FILES*****') {
            } else if ($name === 'NCRecordings') {
            } else if ($name === 'Work Title') {
                echo "title: $arg\n";
            } else if ($name === 'Alternative Title') {
            } else if ($name === 'Opus/Catalogue Number') {
                echo "opus: $arg\n";
            } else if ($name === 'Key') {
            } else if ($name === 'Movements Header') {
            } else if ($name === 'Number of Movements/Sections') {
            } else if ($name === 'Incipit') {
            } else if ($name === 'Dedication') {
            } else if ($name === 'First Performance') {
            } else if ($name === 'Year/Date of Composition') {
            } else if ($name === 'Year of First Publication') {
            } else if ($name === 'Librettist') {
            } else if ($name === 'Language') {
            } else if ($name === 'Related Works') {
            } else if ($name === 'Extra Information') {
            } else if ($name === 'Piece Style') {
            } else if ($name === 'Authorities') {
            } else if ($name === 'Manuscript Sources') {
            } else if ($name === 'Average Duration') {
            } else if ($name === 'SearchKey') {
            } else if ($name === 'SearchKey-amarec') {
            } else if ($name === 'SearchKey-scores') {
            } else if ($name === 'External Links') {
            } else if ($name === 'Discography') {
            } else if ($name === 'Instrumentation') {
            } else if ($name === 'InstrDetail') {
            } else if ($name === 'Tags') {
            } else if ($name === '*****COMMENTS*****') {
            } else if ($name === '*****END OF TEMPLATE*****') {
            } else if ($arg === '*****WORK INFO*****') {
            } else if ($arg === '*****END OF TEMPLATE*****') {
            } else {
                echo "unrecognized arg name: $name: $arg\n";
            }
        }
    } else {
        echo "unrecognized: ($template_name) $body\n";
        exit;
    }
}

function main($nlines) {
    $f = fopen('david_page_dump.txt', 'r');
    for ($i=0; $i<$nlines; $i++) {
        $x = fgets($f);
        if (!$x) break;
        $y = json_decode($x);
        foreach ($y as $title => $body) {
            do_composition($title, $body);
        }
    }
}

main(1);
?>
