<?php

require_once("mediawiki.inc");
require_once("imslp_util.inc");

// read JSON/mediawiki file with categories.
// Parse the fte:person template calls

// fields of #fte:person,performer,composer:
// Birth Date
// Born Year/Born Month/Born Day
// Death Date
// Died Year/Died Month/Died Day
// Time Period
// Time Period2
// Nationality ; sometimes "Armenian composers"
// Nationality2
// Nationality3
// Sex / female or Female
// Alternate Names
// Biography Link
// Picture
// Picture Caption
// Extra Information
// Flourished
//
// performer:
// Type (organization)
// Website
// Instrument

function parse_aux($item, $template_name) {
    $p = new StdClass;
    $p->template_name = $template_name;
    $p->nationality = [];
    $p->time_period = [];
    foreach ($item->args as $name=>$arg) {
        if (!is_string($name)) {
            echo "unrecognized person arg: $name: $arg\n";
            continue;
        }
        switch (strtolower($name)) {
        case 'alternate names':
        case 'alternate name':
        case 'alternative names':
            $p->alternate_names = $arg; break;
        case 'birth date':
            $p->birth_date = $arg; break;
        case 'birth place':
            $p->birth_place = $arg; break;
        case 'born year':
            $p->born_year = (int)$arg; break;
        case 'born month':
            $p->born_month = (int)$arg; break;
        case 'born day':
            $p->born_day = (int)$arg; break;
        case 'death date':
            $p->death_date = $arg; break;
        case 'death place':
            $p->death_place = $arg; break;
        case 'died year':
        case 'death year':
            $p->died_year = (int)$arg; break;
        case 'died month':
        case 'death month':
            $p->died_month = (int)$arg; break;
        case 'died day':
        case 'death day':
            $p->died_day = (int)$arg; break;
        case 'flourished':
            $p->flourished = $arg; break;
        case 'instrument':
            $p->instrument = strtolower($arg); break;
        case 'nationality':
        case 'nationality1':
        case 'nationality2':
        case 'nationality 2':
        case 'nationality3':
        case 'nationality4':
            if ($arg) {
                $p->nationality[] = $arg;
            }
            break;
        case 'picture':
            $p->picture = $arg; break;
        case 'picture caption':
            $p->picture_caption = $arg; break;
        case 'sex':
            $p->sex = strtolower($arg); break;
        case 'signature':
            $p->signature = $arg; break;
        case 'time period':
        case 'time period2':
        case 'era':
            if ($arg) {
                $p->time_period[] = $arg;
            }
            break;
        case 'type':
            $p->type = $arg; break;
        case 'biography link':
        case 'extra information':
        case 'list pages':
        case 'list page':
        case 'website':
        case 'website Link':
        case 'imslp contributor':
        case 'wima':
        case 'recordings':
        case 'display name':
        case 'islocked':
        case 'external work lists':
        case 'external links':
            break;
        default:
            echo "unrecognized person arg: $name: $arg\n";
        }
    }
    return $p;
}

// parse a Category: page that may describe a person,
// in which case it has a #fte:performer/person/composer template call.
// Outside of template: {{MoreInfo
//
// return a PHP structure, or null if no template
//
function parse_person($title, $str) {
    $pos = 0;
    $other_text = '';
    $p = null;
    while (true) {
        [$item, $new_pos] = parse_item($str, $pos);
        $pos = $new_pos;
        if ($item === false) break;
        if (is_string($item)) {
            $other_text .= " $item";
        } else {
            if ($item->name === '#fte:performer') {
                $p = parse_aux($item, 'performer');
            } else if ($item->name === '#fte:person') {
                $p = parse_aux($item, 'person');
            } else if ($item->name === '#fte:composer') {
                $p = parse_aux($item, 'composer');
            }
        }
    }
    if ($p) {
        $n = substr($title, strlen('Category:'));
        $n = str_replace('_', ' ', $n);
        $p->name = parse_name($n);
    }
    return $p;
}

?>
