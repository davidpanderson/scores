#! /usr/bin/env php
<?php

require_once('parse_work.inc');
require_once('parse_combo.inc');
require_once('parse_tags.inc');

////////// work.number_of_movements_sections ///////////////

// parse movement lines
//
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
                $x = str_replace('number_of_movements_sections: ', '', $x);
                do_nmvts_sections($x);
                $x = '';
            }
        } else {
            $x .= $line;
        }
    }
    if ($x) {
        $x = str_replace('number_of_movements_sections: ', '', $x);
        do_nmvts_sections($x);
    }
}

//////////////////// keys /////////////////

function test_keys() {
    $keys = [
        '{{Key|Db}} (1st version)<br>{{Key|Eb}} (2nd version)',
        '{{Key|C}}-{{Key|c}}',
        "''see below''",
        'C ionian'
    ];
    foreach ($keys as $k) {
        echo sprintf("----------------\ninput: %s\noutput: %s\n",
            $k, parse_keys($k)
        );
    }
}

////////////////////// arrangers /////////////////////

function test_arranger() {
    $arrs = [
        '{{LinkArr|Roberto|Novegno|1981|}}',
        '{{LinkArr|Roberto|Novegno|1981|1999}}',
        '{{LinkArr|Renaud de|Vilbac}} (1829–1884)<br>{{LinkArr|August|Schulz}} (1837-1909)<br>{{LinkArr|Heinrich|Plock}} (1829-1891)',
        '{{LinkArr|Varun Ryan|Soontornniyomkij|1997|}}<br>after composer in {{LinkWork|Piano Concerto in D major, Op.61a||Beethoven|Ludwig van|0}}',
        '{{LinkArr|Gustave|Leo}} ({{fl}}1888)',
        'Jacques Drillon<br>after {{LinkArr|Franz|Liszt|1811|1886}}'
    ];
    foreach ($arrs as $arr) {
        echo "----------------\ninput: $arr\noutput:\n";
        print_r(parse_arranger($arr));
        echo "\n";
    }
}

////////////////////// dedication /////////////////////

function test_dedication() {
    $deds = [
        '{{LinkDed|Joseph|Haydn|1732|1809}}',
        'Madame {{LinkDed|Caroline|Montigny-Rémaury}}',
        '[[Wikipedia:Count Ferdinand Ernst Gabriel von Waldstein|Count Ferdinand Ernst Gabriel von Waldstein]] (1762-1823)',
        'Moritz Reichsgraf von Fries (original); [[wikipedia:Elizabeth Alexeievna (Louise of Baden)|Ihrer Majestät der Kaiserinn Elisabeth Alexiewna]] (Diabelli piano arrangements)',
        '{{LinkName|t=ded|Erzherzog|Rudolph|Archduke Rudolph of Austria}} (1788-1831)',
        'F. J. von Lobkowitz<br>Graf A. von Rasumovsky',
        'None',
        '1. To the memory of Lieutenant {{LinkDed|Jacques|Charlot}} and 2. To the memory of Lieutenant Jean Cruppi and 3. To the memory of Lieutenant Gabriel Deluc and 4. To the memory of Piere and Pascal Gaudin and 5. To the memory of Jean Dreyfus and 6. To the memory of Captain {{LinkDed|Joseph de|Marliave}}',
    ];
    foreach ($deds as $ded) {
        echo "----------------\ninput: $ded\noutput:\n";
        print_r(parse_dedication($ded));
        echo "\n";
    }
}

////////////////////// arrangement target /////////////////////

function test_arrangement_target() {
    $tgs = [
        'For 2 Clarinets, 2 Bassoons and 2 Horns (Patterson)',
        'For Violin or Cello and Piano',
        'For 4 Horns (or 3 Horns and Bassoon) (Miller)',
        '*For Violin, Cello or 2 Violins and Piano (Hoffmann)',
        '*For Piano Trio or 2 Violins and Piano (Hofmann)',
        'For 2 Flutes (2nd also Piccolo), 2 Oboes, 2 Clarinets, 2 Bassoons and 2 Horns (Clements)',
        '*For Cello or Violin and Piano (Jansa)'
    ];
    foreach($tgs as $tg) {
        echo "----------------\ninput: $tg\noutput:\n";
        print_r(parse_arrangement_string($tg));
        echo "\n";
    }
}

////////////////////// tags /////////////////////

function test_tags() {
    $tgs = [
        'vc pf (arr) ; vn pf (arr)'
    ];
    foreach($tgs as $tg) {
        echo "----------------\ninput: $tg\noutput:\n";
        print_r(parse_tags($tg));
        echo "\n";
    }
}

//test_movement_lines();
//test_nmvts_sections();
//test_keys();
//test_arranger();
//test_dedication();
//test_arrangement_target();
test_tags();
?>
