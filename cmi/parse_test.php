<?php

require_once('parse_work.inc');

function test_movement_lines() {
    $lines = file('movement_lines.txt');
    foreach ($lines as $line) {
        echo "--------------\nline: $line\n";
        print_r(parse_movement_line($line));
    }
}

test_movement_lines();

?>
