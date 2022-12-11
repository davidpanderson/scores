#! /usr/bin/env php

<?php

// parse IMSLP data (in JSON/mediawiki format) and populate the SQL DB

require_once("mediawiki.inc");
require_once("imslp_db.inc");

// given "Piano_Sonata_No.13,_Op.27_No.1_(Beethoven,_Ludwig_van)" return
// - title/opus (with spaces)
// - first name
// - last name
//
function parse_title($str) {
    $x = strpos($str, '(');
    if ($x === false) {
        return [$str, '',''];
    }
    $y = strpos($str, ')');
    if ($y === false || $y < $x) {
        echo "malformed title $str\n";
        return [$str, '',''];
    }
    $t = substr2($str, 0, $x);
    $t = str_replace('_', ' ', $t);
    $t = trim($t);

    $c = substr2($str, $x+1, $y);
    $c = str_replace('_', ' ', $c);
    $x = strpos($c, ',');
    if ($x === false) {
        $last = trim($c);
        $first = '';
    } else {
        $last = trim(substr2($c, 0, $x));
        $first = trim(substr($c, $x+1));
    }
    return [$t, $first, $last];
}

// $name is of the form "foo 4" or "foo 4-6".
// Add $val to the corresponding elements of $arr
//
function add_elements($name, $val, &$arr) {
    $x = explode(' ', $name);
    $y = explode('-', $x[1]);
    if (count($y) == 1) {
        $n = (int)$y[0];
        $arr[$n] = $val;
    } else {
        $n = (int)$y[0];
        $m = (int)$y[1];
        for ($i=$n; $i<=$m; $i++) {
            $arr[$i] = $val;
        }
    }
}

// convert a #fte:imslpfile template call to an object
//
function file_args_to_object($args) {
    $f = new StdClass;
    $f->file_names = [];
    $f->file_descs = [];
    // per-file info
    $f->uploaders = [];
    $f->date_submitteds = [];
    $f->scanners = [];
    $f->page_counts = [];
    $f->thumb_filenames = [];
    $f->sample_filenames = [];
    foreach ($args as $name=>$val) {
        if (starts_with($name, 'File Name')) {
            $f->file_names[] = $val;
        } else if (starts_with($name, 'File Description')) {
            $f->file_descs[] = $val;
        } else if ($name == 'Editor') {
            $f->editor = $val;
        } else if ($name == 'Image Type') {
            $f->image_type = $val;
        } else if ($name == 'Scanner') {
            $f->scanner = $val;
        } else if (starts_with($name, 'Scanner ')) {
            add_elements($name, $val, $f->scanners);
        } else if ($name == 'Uploader') {
            $f->uploader = $val;
        } else if (starts_with($name, 'Uploader ')) {
            add_elements($name, $val, $f->uploaders);
        } else if ($name == 'Date Submitted') {
            $f->date_submitted = $val;
        } else if (starts_with($name, 'Date Submitted ')) {
            add_elements($name, $val, $f->date_submitteds);
        } else if (starts_with($name, 'Page Count ')) {
            add_elements($name, $val, $f->page_counts);
        } else if ($name == 'Publisher Information') {
            $f->publisher_information = $val;
        } else if ($name == 'Copyright') {
            $f->copyright = $val;
        } else if ($name == 'Misc. Notes') {
            $f->misc_notes = $val;
        } else if ($name == 'Amazon') {
            $f->amazon = $val;
        } else if ($name == 'Arranger') {
            $f->arranger = $val;
        } else if ($name == 'Translator') {
            $f->translator = $val;
        } else if ($name == 'Thumb Filename') {
            $f->thumb_filename = $val;
        } else if (starts_with($name, 'Thumb Filename ')) {
            add_elements($name, $val, $f->thumb_filenames);
        } else if ($name == 'Sample Filename') {
            $f->sample_filename = $val;
        } else if (starts_with($name, 'Sample Filename ')) {
            add_elements($name, $val, $f->sample_filenames);
        } else if ($name == 'SM+') {
            $f->sm_plus = $val;
        } else if ($name == 'Reprint') {
            $f->reprint = $val;
        } else if ($name == 'Engraver') {
            $f->engraver = $val;
        } else if ($name == 'File Tags') {
            $f->file_tags = $val;
        } else {
            echo "unrecognized file arg: $name ($val)\n";
        }
    }
    return $f;
}

// parse the *****FILES***** part of a composition.
// this is a sequence of #fte:imslpfile template calls,
// possibly interspersed with
// ===Parts===
// ====Complete====
// ====Selections====
// ===Arrangements and Transcriptions===
//    =====For Piano (novegno)=====
//    =====For Piano 4 hands (Ulrich)=====
//    ...
// Return a list which is an alternation of strings and file objects

function parse_files($str) {
    echo "start parse_files\n";
    $pos = 0;
    $files = [];
    $nfiles = 0;
    $nstr = 0;
    while (true) {
        [$item, $new_pos] = parse_item($str, $pos);
        if ($item === false) break;
        $pos = $new_pos;
        if (is_string($item)) {
            $files[] = $item;
            $nstr++;
        } else {
            if ($item->name == '#fte:imslpfile') {
                $files[] = file_args_to_object($item->args);
            } else {
                echo "unrecognized template in file list: $item->name\n";
            }
            $nfiles++;
        }
    }
    echo "end parse_files; $nfiles files, $nstr strings\n";
    return $files;
}

// parse a composition page.
// Return an object containing most of the info.
//
function parse_composition($title, $body) {
    echo "\n------------------\nprocessing $title\n";
    $comp = new StdClass;
    [$t, $first, $last] = parse_title($title);
    echo "  title: $t\n";
    echo "  auth_first: $first\n";
    echo "  auth_last: $last\n";
    $comp->title = $t;
    $comp->auth_first = $first;
    $comp->auth_last = $last;
    $comp->extra_text = [];
    if (starts_with($body, '#REDIRECT')) {
        echo "Redirect; skipping\n";
        $lines = explode("\n", $body);
        $comp->redirect = $lines[0];
        for ($i=1; $i<count($lines); $i++) {
            $x = trim($lines[$i]);
            if ($x) {
                $comp->extra_text[] = $x;
            }
        }
        return $comp;
    }
    $pos = 0;
    while (true) {
        [$item, $new_pos] = parse_item($body, $pos);
        $pos = $new_pos;
        if ($item === false) break;
        if (is_string($item)) {
            $comp->extra_text[] = $item;
            echo "non-template text: $item\n";
            continue;
        }
        echo "got template $item->name\n";
        if (strtolower($item->name) == 'attrib') {
            echo "Got attrib\n";
        } else if ($item->name == '#fte:imslppage') {
            foreach ($item->args as $name=>$arg) {
                echo "arg $name:\n";
                if ($name === '*****AUDIO*****') {
                } else if ($name === '*****FILES*****') {
                    parse_files($arg);
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
                    echo "comments: $arg\n";
                //} else if ($name === '*****END OF TEMPLATE*****') {
                } else if ($arg === '*****WORK INFO*****') {
                } else if ($arg === '*****END OF TEMPLATE*****') {
                    echo "got end of template\n";
                } else {
                    echo "unrecognized arg name: $name: $arg\n";
                }
            }
        } else {
            echo "unrecognized template name: $item->name\n";
            exit;
        }
    }
    return $comp;
}

function main($nlines) {
    $f = fopen('david_page_dump.txt', 'r');
    for ($i=0; $i<$nlines; $i++) {
        $x = fgets($f);
        if (!$x) break;
        $y = json_decode($x);
        foreach ($y as $title => $body) {
            parse_composition($title, $body);
        }
    }
}

main(1);
?>
