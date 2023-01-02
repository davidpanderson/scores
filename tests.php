<?php

// various tests

require_once("imslp_db.inc");

if (0) {
    print_r(DB_person::enum_join(1, 2, "sex='Female'"));
}

/////////////

require_once("imslp_util.inc");

if (0) {
    print_r(parse_title(
        "Piano_Sonata_No.13,_Op.27_No.1_(suite)_(Beethoven,_Ludwig_van)"
    ));
}

if (0) {
    print_r(parse_name('John Paul Jones'));
    print_r(parse_name('Jones, John Paul'));
    print_r(parse_name('Charo'));
}

/////////////

require_once("mediawiki.inc");

if (0) {
    $x = "Copyright=Public Domain
|Thumb Filename=Contenu neutre.jpg|200px|thumb|left
|Misc. Notes=";
    print_r(scan_arg($x, 0));
}

if (0) {
    $str = <<<'EOT'
    {{#fte:imslppage
    |Incipit=
    <score>\relative c'{   \key c \major \tempo \"Allegro maestoso\" \time 4/4 c8 r g r c r e r f4.( \tuplet 3/2 {e16 d c)} b4 r8. g16 b8 r d r f r d r g4.( \tuplet 3/2 {a16 g f)} e4 r}</score>
    <score>\relative c'' {   \key f \major \tempo \"Andante\" \time 4/4 f4..(  \p c16) a'4..( f16) c'2. bes16( a g f) fis4( g) r2 c,2( e4 g) bes2~( bes8 d c bes) gis( a) a4 r2    }</score>
    <br><score>\relative c'' {   \key c\major \tempo \"Allegro vivace assai\" \time 2/4 e8-. f-. fis-. g-. a( g) f-. e-. d \appoggiatura {e16} d16( c d8) d-. d4( e8) c  | e8-. f-. fis-. g-. a( g) f-. e-. d \appoggiatura {e16} d16( c d8) e-. c4 r    }
     </Score>
     | *****COMMENTS***** =
     {{Piano Concertos (Mozart, Wolfgang Amadeus)}}
     | *****END OF TEMPLATE***** }}
EOT;
    $str = <<<'EOT'
    {{#fte:imslppage
|Incipit=Quartet No.1 in D major, 1st mvt. <span class='expandline'><br/><score raw="1">
\header {tagline = ##f}
\score{
\new GrandStaff <<
\new Staff {
\relative c'''{
\set Staff.instrumentName="Flauto o Violino"
\version "2.18.0"
\key d \major
\clef treble
\tempo "Allegro"
\time 4/4
a8_\markup{"dol."}[ fis] d4~ d8[ d' cis b]
a16[ fis a fis] d4. d'8[ cis b]
a[( fis) d-. d]-. g[( e) cis-. cis]-.
d16[( fis d fis)] a4. d8[ cis b]
}
}

\new Staff { \relative c' {
\set Staff.instrumentName="Violino 2o"
\version "2.18.0"
\key d \major
\time 4/4
\clef treble
fis8_\p([ d fis d]) fis([ d g d])
fis([ d fis d] fis[ d g d])
<a fis'>4 r <a g'> r
fis'8[ d fis d] fis[ d g d]
}
}

\new Staff { \relative c' {
\set Staff.instrumentName="Viola"
\version "2.18.0"
\key d \major
\time 4/4
\clef alto
a8_\p([ d a d]) a([ d b d])
a([ d a d]) a([ d b d])
a4 r a r
a8([ d a d] a)[ d b d]
}
}

\new Staff { \relative c, {
\set Staff.instrumentName="Cello"
\version "2.18.0"
\key d \major
\time 4/4
\clef bass
d4_\p d' d d
d, d' d d
d r a r
d, d' d d
}
}
>>
}
</score></span>
|Piece Style=Classical
     | *****COMMENTS***** =
     {{Piano Concertos (Mozart, Wolfgang Amadeus)}}
     | *****END OF TEMPLATE***** }}
EOT;
    $pos = 0;
    while (true) {
        [$item, $new_pos] = parse_item($str, $pos);
        $pos = $new_pos;
        if ($item === false) break;
        if (is_string($item)) {
            echo "got text: $item\n";
        } else {
            echo "got template call\n";
            echo "  name: $item->name\n";
            foreach ($item->args as $n=>$v) {
                echo "  arg $n\n     $v\n";
            }
        }
    }
}

if (0) {
    //$str = '{{#if:blah|foo}}';
    //$str = '{{P|Edition Peters|C.F. Peters|Leipzig|n.d.[{{{2|1902-07}}}]|{{{2|1902}}}|3100|{{{3|8800, 10037-38}}}}}';
    //$str = '{{#if:{{{3|}}}|{{{3}}}:<nowiki> </nowiki>|}}';
    //$str = '{{#if:{{{4|}}}|{{{4}}}|{{#if:{{{5|}}}|{{{5}}}|n.d}}}}';

    $str = "{{#fte:mclink
    |catname = {{ #if:{{{5|}}}|{{#if:{{{2|}}}|{{{2}}}, {{{1}}} @{{{5}}}^|{{{1}}} @{{{5}}}^}} | {{#if:{{{2|}}}|{{{2}}}, {{{1}}}|{{{1}}}}}}}
    |sortkey = {{#worksortkey:}}
    |mctype  = Arranger
    |show    = {{{1}}}{{#if:{{{2|}}}|&#32;{{{2|}}}}}
    }}{{ #ifeq: 0{{{3|}}} | 0 | {{ #ifeq: 0{{{4|}}} | 0 | {{#if: {{{5|}}} | &nbsp;({{{5}}}) | }} | &nbsp;({{#if: {{{5|}}} |{{{5}}},&nbsp;|}}d. {{{4}}}) }} | &nbsp;{{ #ifeq: 0{{{4|}}} | 0 | ({{#if: {{{5|}}} |{{{5}}},&nbsp;|}}b. {{{3}}}) | ({{#if: {{{5|}}} |{{{5}}},&nbsp;|}}{{{3}}}-{{{4}}})}}}}";

    print_r(parse_template_call($str, 0));
}

/////////////

require_once("parse_work.inc");

if (0) {
    $x = [];
    //add_elements('xxx 4,5,8', 'foo', $x);
    add_elements('xxx 1-3,5,8', 'foo', $x);
    print_r($x);
    exit;
}

if (0) {
    $x = "''[[Haydn - Piano Sonatas (Martienssen)|Sonaten f▒~C¼r Klavier zu zwei H▒~CC¤nden]]''<br>{{P|Edition Peters|C.F. Peters|Leipzig||1937||11261}}";
    print_r(parse_publisher($x));
}

if (0) {
    //$str = '{{GardnerPerf|Martina Filjak, Piano}}';
    //$str = '{{GardnerPerf|Yunjie Chen (piano)}}';
    //$str = '{{GardnerPerf|Mark Padmore (voice), Jonathan Biss (piano)}}';
    //$str = '{{GardnerPerf|Benjamin Beilman, violin}}; {{GardnerPerf|Yekwon Sunwoo, piano}}';
    $str = '{{GardnerPerf|Rebel Baroque Orchestra (ensemble)}}';

    print_r(parse_gardner_perfs($str));
}

if (0) {
    print_r(parse_perf('Joe Smith, piano'));
    print_r(parse_perf('Joe Smith (piano)'));
    print_r(parse_perf('Joe Smith piano'));
}

if (1) {
    //$str = '{{GardnerPerf|Benjamin Beilman, violin}}; {{GardnerPerf|Yekwon Sunwoo, piano}}';
    $str = '{{GardnerPerf|Rebel Baroque Orchestra (ensemble)}}';
    //$str = 'Galaxy Bosendorfer 290 (Tatiana Kolesova)';
    $str = 'Daniel Guilet (violin)<br>{{Plain|http://www.rene-gagnaux.ch/tichman_herbert/courte_biographie.html#Ruth_Budnevich|Ruth Budnevich}} (piano)';
    print_r(parse_performers($str ,''));
}

/////////////

require_once("template.inc");

if (0) {
    //$str = "[[foo|bar]]";
    //$str = "{{GriegKlavierwerke|foo|blah|x3|x4|x5}}";
    //$str = "{{P|a|b|c|d|e|f|g|h}}";
    //$str = "{{LinkArr|Charles-Valentin|Alkan|1813|1888}}";
    //$str = "{{FE}}";
    $str = "{{P|Carl Simon||Berlin||||C.S. 1823}}";
    $x = expand_mw_text($str);
    echo "output: $x\n";
}

?>
