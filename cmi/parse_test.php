<?php

require_once('parse_work.inc');

function test_movement_lines() {
    $lines = file('movement_lines.txt');
    foreach ($lines as $line) {
        echo "--------------\nline: $line\n";
        print_r(parse_movement_line($line));
    }
}

function do_nmvts_sections($s) {
    echo "---------------\n";
    echo "input:\n$s\n";
    $x = parse_nmvts_sections($s);
    if (!$x) {
        echo "output: none\n";
        return;
    }
    echo "output:\n";
    echo "title: $x->title\n";
    $n = count($x->sections);
    echo "$n sections:\n";
    foreach ($x->sections as $y) {
        print_r($y);
    }
}

// file has DB output format
function test_nmvts_sections() {
    $x = '';
    $lines = file('nmvts_sections.txt');
    foreach ($lines as $line) {
        if (strpos($line, '******') !== false) {
            if ($x) {
                do_nmvts_sections($x);
                $x = '';
            }
        } else {
            $x .= $line;
        }
    }
    if ($x) {
        do_nmvts_sections($x);
    }
}

//test_movement_lines();

test_nmvts_sections();

?>
