<?php

require_once('parse_work.inc');

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
        '{{LinkArr|Gustave|Leo}} ({{fl}}1888)'
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

//test_movement_lines();
//test_nmvts_sections();
//test_keys();
//test_arranger();
//test_dedication();
?>
