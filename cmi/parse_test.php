#! /usr/bin/env php
<?php

require_once('parse_work.inc');
require_once('parse_combo.inc');
require_once('parse_tags.inc');
require_once('template.inc');

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

////////////////////// title /////////////////////

function test_title() {
    $ts = [
        'Improvisation on “Osanna” from the “{{NoComp|Missa a 3|Aulen, Johannes}}” by {{LinkComp|Johannes|Aulen}}',
        'In paradisum, from the {{NoComp|Requiem|St. George Tucker, Tui}}',
        'Piano Trio in B{{flat}} major',
        'Hebräische Melodien “Nach Eindrücken der {{LinkCompS|George Gordon|Byron|Byron’schen}} Gesänge” für Bratsche und Klavier, Op. 9',
        'Sonata for Piano Duet in {{K|C}}',
        'Piano sonata No. 1 in C{{sharp}} minor, Op. 6',
        'Méthode de 1er cornet en si{{flat}}',
        'Violin Sonata in {{Key|Bb}}'
    ];
    foreach($ts as $t) {
        echo "----------------\ninput: $t\noutput:\n";
        print_r(expand_title($t));
        echo "\n";
    }
}

////////////////////// opus /////////////////////

function test_opus() {
    $ts = [
        'K.428 ; {{K6|421b}} ; Op.10 No.4',
        '{{HaydnHob|n801|XVI:52}} ; Op.92',
        "Op.25 ({{Sb}})",
        "{{MWV|W|13}}",
        "Op.19 / 44 (but {{Sb}})",
        "RISM B/I: 1514{{Sup|1}}",
        "{{RISMs|000124897|Ricasoli Profana 255 (No.5)}}",
        "Op.13 [also assigned to {{LinkWorkN|Grande valse No.1|Op.13|Pessard|Émile|0}}]",
        "R.101-110  (Rubio's catalogue : R.423(101){{EN}}432(110) )",
        "{{K6|Anh.C 15.11}}",
        "Op.47 {{Version|A}}",
        "{{RISMc|00001000000456|RISM A/I: SS-7238a}}",
        "Published as Op.3 <br>  {{LinkWork|Symphony in C major|M.A6/I:11|Rosetti|Antonio|0}}",
        "{{plain|https://archive.org/stream/JosephHaydnThematisch-bibliographischesWerkverzeichnis/Hoboken1-3#page/n1168/mode/1up|Hob.XXVIa:a2}}",
        "{{LinkName|Felix|Mendelssohn}}'s Op.9, Nos.7, 10, 12",
        "{{Key|F}}",
        "R.1{{EN}}10  (Rubio's catalogue : R.336(1){{EN}}345(10) )",
        "{{sb}}\n#Solitude, VWV 1044\n#La petite chevrière, VWV 1045\n#L'absence, VWV 1046
        ",
    ];
    foreach($ts as $t) {
        echo "----------------\ninput: $t\n";
        $x = parse_opus($t);
        echo "output: $x\n";
    }
}

////////////////////// performer /////////////////////

function test_performer() {
    $ts = [
        ['','London Symphony Orchestra=orchestra;Krips, Josef=conductor'],
        ['Orchestre symphonique du Gürzenich de Cologne, Günter Wand (dir.)', 'Gürzenich-Orchester Köln=orchestra'],
        ['Utah Symphony; Maurice Abravanel (conductor)', 'Manookian, Jeff=piano'],
        ['Ezequiel Diz, Guillermo Copello, Mariano Asato, Lucas Querini, Julia Martínez, Salvador Trapani, Sebastian de la Vallina', ''],
    ];
    foreach ($ts as [$t, $u]) {
        echo "----------------\ninput: $t\noutput: ";
        print_r(parse_performers($t, $u));
        echo "\n";
    }
}

////////////////////// publisher /////////////////////

function test_publisher() {
    $ts = [
        '{{MssAu|17|79}}, dated July 9.',
        '{{BeethovenComplete|16:<br>Sonaten für das Pianoforte|134|1862|165-88}}'
    ];
    foreach ($ts as $t) {
        echo "----------------\ninput: $t\noutput: ";
        print_r(parse_publisher($t));
        echo "\n";
    }
}

////////////////////// template expansion /////////////////////

function test_template() {
    $ts = [
        '{{HaydnHob|n801|XVI:52}}',
        '{{P|Breitkopf und Härtel|Breitkopf & Härtel|Leipzig||||Orch.B. 92}}',
        '{{knuth}}',
        '{{Liszt-Stiftung|IV|2-3 (pp.141–228)|1922|I-IX B}}',
        '{{RC||Deutsche Grammophon|||1958}}',
        '{{NeueLisztAusgabe|2|19|1993|13 356}}',
        '{{BeethovenComplete|1|9|1863}}',
        '{{CCARH|2008}}',
        '{{MozartComplete|VIII:<br>Symphonien, Bd.1, No.21|134|1880|1-18 (271-288)}}',
        '{{MozartNMA|IV|11|3|Sinfonien|4502|1956}}',
        '{{Mutopia||2008}}',
        '{{BulowLebertAlbumSch|II|24|12589|124-42}}',
        '{{GardnerMus}}',
        '{{BeethPfsonCasella|3|1920|144-59}}',
        "{{SMPlink|Samtliche-Sonaten-Band-II/2656603|''Klaviersonaten, Band II''}}<br>{{P||Ullstein|Berlin|n.d.(ca.1918)|||}}<br>Reissue - {{P|Breitkopf und Härtel|Breitkopf & Härtel|Leipzig||1923||28727}}",
        '{{BrahmsComplete|1|1}}',
        '{{AGarch}}',
        '{{ChopinJoseffyEd|5: Ballades (LMC 31)|1916|25646.}}',
        "{{ChopinMikuliSch|5|1894|11490}}<br>''Reissue'' — Vol.5, 1934. Plate 36391.",
        '{{ChopinKullakS|III: Ballades|1882|7288(4)}}',
        '{{ChopinComplete|I|n.d.[1878]|I. 4.}}',
        '{{ChopinKlindworth|1: Ballades|12274.}}',
        '{{ChopinScholtzEP|II|6216}}',
        '{{ChopinPaderewskiPWM|III: Ballades|232|1949|30-40}}',
        "{{LinkWork|4 Ballades||Chopin|Frédéric|0}}<br>{{P|Litolff|Henry Litolff's Verlag|Braunschweig|||1048 Collection Litolff}}",
        '{{EinzelAus|06662||1917|06662}}',
        '{{Sarro}}',
        '{{BestWTarMasters|Vol.|3||2229|772-780}}',
        '{{Mss}}',
        '{{MssUA|18|22}}',
        '{{KlassischerStucke|III (no.5)|6248}}',
        '{{CVartist|stefanoligoratti|Stefano Ligoratti}}',
        '{{MssAu|17|79}}, dated July 9.',
        '{{Mss}} (Copyist)',
        'London: {{MssD|19|16}} (March)',
        '{{BrahmsSauer|1|a|9487}}',
        "Op.25 ({{Sb}})",
        "{{MWV|W|13}}",
        "Op.19 / 44 (but {{Sb}})",
        "RISM B/I: 1514{{Sup|1}}",
        "{{RISMs|000124897|Ricasoli Profana 255 (No.5)}}",
        "Op.13 [also assigned to {{LinkWorkN|Grande valse No.1|Op.13|Pessard|Émile|0}}]",
        "R.101-110  (Rubio's catalogue : R.423(101){{EN}}432(110) )",
        "{{K6|Anh.C 15.11}}",
        "Op.47 {{Version|A}}",
        "{{RISMc|00001000000456|RISM A/I: SS-7238a}}",
        "Published as Op.3 <br>  {{LinkWork|Symphony in C major|M.A6/I:11|Rosetti|Antonio|0}}",
        "{{plain|https://archive.org/stream/JosephHaydnThematisch-bibliographischesWerkverzeichnis/Hoboken1-3#page/n1168/mode/1up|Hob.XXVIa:a2}}",
        "{{LinkName|Felix|Mendelssohn}}'s Op.9, Nos.7, 10, 12",
        "{{Key|F}}",
        "R.1{{EN}}10  (Rubio's catalogue : R.336(1){{EN}}345(10) )",
        "{{sb}}\n#Solitude, VWV 1044",
        "{{LinkName|Felix|Mendelssohn}}"
    ];
    foreach ($ts as $t) {
        echo "----------------\ninput: $t\n";
        $x = expand_mw_text($t);
        echo "output: $x\n";
    }
}

//test_movement_lines();
//test_nmvts_sections();
//test_keys();
//test_arranger();
//test_dedication();
//test_arrangement_target();
//test_tags();
//test_title();
test_opus();
//test_publisher();
//test_template();
//test_performer();
?>
