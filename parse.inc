<?php

// parse IMSLP data (in JSON/mediawiki format) into PHP data structures

require_once("mediawiki.inc");

$verbose = false;

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

// $name is of the form "foo bar 4" or "foo bar 4-6".
// Add $val to the corresponding elements of the array $arr
// NOTE: our arrays are 0-offset
//
function add_elements($name, $val, &$arr) {
    $x = explode(' ', $name);
    $n = count($x);
    $y = explode('-', $x[$n-1]);
    if (count($y) == 1) {
        $n = (int)$y[0];
        $arr[$n-1] = $val;
    } else {
        $n = (int)$y[0];
        $m = (int)$y[1];
        for ($i=$n; $i<=$m; $i++) {
            $arr[$i-1] = $val;
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
        if (starts_with($name, 'File Name ')) {
            add_elements($name, $val, $f->file_names);
        } else if (starts_with($name, 'File Description ')) {
            add_elements($name, $val, $f->file_descs);
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

function audio_args_to_object($args) {
    $f = new StdClass;
    $f->file_names = [];
    $f->file_descs = [];
    $f->uploaders = [];
    $f->date_submitteds = [];
    foreach ($args as $name=>$val) {
        if (starts_with($name, 'File Name ')) {
            add_elements($name, $val, $f->file_names);
        } else if (starts_with($name, 'File Description ')) {
            add_elements($name, $val, $f->file_descs);
        } else if ($name == 'Uploader') {
            $f->uploader = $val;
        } else if (starts_with($name, 'Uploader ')) {
            add_elements($name, $val, $f->uploaders);
        } else if ($name == 'Date Submitted') {
            $f->date_submitted = $val;
        } else if (starts_with($name, 'Date Submitted ')) {
            add_elements($name, $val, $f->date_submitteds);
        } else if ($name == 'Performers') {
            $f->performers = $val;
        } else if ($name == 'Performer Categories') {
            $f->performer_categories = $val;
        } else if ($name == 'Publisher Information') {
            $f->publisher_info= $val;
        } else if ($name == 'Copyright') {
            $f->copyright = $val;
        } else if ($name == 'Thumb Filename') {
            $f->thumb_filename = $val;
        } else if ($name == 'Misc. Notes') {
            $f->misc_notes = $val;
        } else if ($name == 'Arranger') {
            $f->arranger = $val;
        } else {
            echo "unrecognized audio arg: $name ($val)\n";
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
    $pos = 0;
    $files = [];
    $nfiles = 0;
    $nstr = 0;
    while (true) {
        [$item, $new_pos] = parse_item($str, $pos);
        if ($item === false) break;
        $pos = $new_pos;
        if (is_string($item)) {
            $x = explode("\n", $item);
            foreach ($x as $line) {
                if ($line) {
                    $files[] = $line;
                    $nstr++;
                }
            }
        } else {
            if ($item->name == '#fte:imslpfile') {
                $files[] = file_args_to_object($item->args);
            } else {
                echo "unrecognized template in file list: $item->name\n";
            }
            $nfiles++;
        }
    }
    return $files;
}

function parse_audio($str) {
    $pos = 0;
    $files = [];
    $nfiles = 0;
    $nstr = 0;
    while (true) {
        [$item, $new_pos] = parse_item($str, $pos);
        if ($item === false) break;
        $pos = $new_pos;
        if (is_string($item)) {
            $x = explode("\n", $item);
            foreach ($x as $line) {
                if ($line) {
                    $files[] = $line;
                    $nstr++;
                }
            }
        } else {
            if ($item->name == '#fte:imslpaudio') {
                $files[] = audio_args_to_object($item->args);
            } else {
                echo "unrecognized template in audio list: $item->name\n";
            }
            $nfiles++;
        }
    }
    return $files;
}

// parse a composition page.
// Return an object containing most of the info.
//
function parse_composition($title, $body) {
    global $verbose;
    if ($verbose) {
        echo "\n------------------\nprocessing $title\n";
    }
    $comp = new StdClass;
    [$t, $first, $last] = parse_title($title);
    $comp->title = $t;
    $comp->composer_first = $first;
    $comp->composer_last = $last;
    $comp->extra_text = [];
    if (starts_with($body, '#REDIRECT')) {
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
            //echo "non-template text: $item\n";
            continue;
        }
        if ($verbose) {
            echo "got template $item->name\n";
        }
        if (strtolower($item->name) == 'attrib') {
            $comp->attrib = $item->args[0];
        } else if ($item->name == '#fte:imslppage') {
            foreach ($item->args as $name=>$arg) {
                if ($verbose) {
                    echo "arg $name:\n";
                }
                if ($name === '*****AUDIO*****') {
                    $comp->audios = parse_audio($arg);
                } else if ($name === '*****FILES*****') {
                    $comp->files = parse_files($arg);
                } else if ($name === 'NCRecordings') {
                    $comp->ncrecordings = $arg;
                } else if ($name === 'Work Title') {
                    $comp->title = $arg;
                } else if ($name === 'Alternative Title') {
                    $comp->alternative_title = $arg;
                } else if ($name === 'Opus/Catalogue Number') {
                    $comp->opus = $arg;
                } else if ($name === 'Key') {
                    $comp->key = $arg;
                } else if ($name === 'Movements Header') {
                    $comp->movements_header = $arg;
                } else if ($name === 'Number of Movements/Sections') {
                    $comp->movements = $arg;
                } else if ($name === 'Incipit') {
                    $comp->incipit = $arg;
                } else if ($name === 'Dedication') {
                    $comp->dedication = $arg;
                } else if ($name === 'First Performance') {
                    $comp->first_performance = $arg;
                } else if ($name === 'Year/Date of Composition') {
                    $comp->composition_date = $arg;
                } else if ($name === 'Year of First Publication') {
                    $comp->publication_date = $arg;
                } else if ($name === 'Librettist') {
                    $comp->librettist = $arg;
                } else if ($name === 'Language') {
                    $comp->language = $arg;
                } else if ($name === 'Related Works') {
                    $comp->related_works = $arg;
                } else if ($name === 'Extra Information') {
                    $comp->extra_info = $arg;
                } else if ($name === 'Piece Style') {
                    $comp->style = $arg;
                } else if ($name === 'Authorities') {
                    $comp->authorities = $arg;
                } else if ($name === 'Manuscript Sources') {
                    $comp->manuscript_sources = $arg;
                } else if ($name === 'Average Duration') {
                    $comp->average_duration = $arg;
                } else if ($name === 'SearchKey') {
                    $comp->search_key = $arg;
                } else if ($name === 'SearchKey-amarec') {
                    $comp->search_key_amarec = $arg;
                } else if ($name === 'SearchKey-scores') {
                    $comp->search_key_scores = $arg;
                } else if ($name === 'External Links') {
                    $comp->external_links = $arg;
                } else if ($name === 'Discography') {
                    $comp->discography = $arg;
                } else if ($name === 'Instrumentation') {
                    $comp->instrumentation = $arg;
                } else if ($name === 'InstrDetail') {
                    $comp->instr_detail = $arg;
                } else if ($name === 'Tags') {
                    $comp->tags = $arg;
                } else if ($name === '*****COMMENTS*****') {
                    $comp->comments = $arg;
                } else if ($arg === '*****WORK INFO*****') {
                } else if ($arg === '*****END OF TEMPLATE*****') {
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

?>
