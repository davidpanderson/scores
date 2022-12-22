<?php

// parse IMSLP data (in JSON/mediawiki format) into PHP data structures.
//
// we parse high-level constructs:
// composition (#fte:imslppage)
//      score files (#fte:imslpfile)
//          publisher ({{P}})
//      audio files (#fte:imslpaudio)
//
// Other template calls are not parsed, and go into text fields.

require_once("mediawiki.inc");

$verbose = false;

// $name is of the form "foo bar 4" or "foo bar 4-6" or "foo bar 1-3,4,7".
// Add $val to the corresponding elements of the array $arr
// NOTE: the array are 0-offset, but the numbers are 1-offset
//
// Used to parse file name, file description, date submitted args
// from file and audio template calls.
//
function add_elements($name, $val, &$arr) {
    $x = explode(' ', $name);
    $n = count($x);
    $last = $x[$n-1];

    $y = explode(',', $last);
    foreach ($y as $z) {
        $y = explode('-', $z);
        if (count($y) > 1) {
            $n = (int)$y[0];
            $m = (int)$y[1];
            for ($i=$n; $i<=$m; $i++) {
                $arr[$i-1] = $val;
            }
        } else {
            $n = (int)$y[0];
            $arr[$n-1] = $val;
        }
    }
}

if (0) {
    $x = [];
    //add_elements('xxx 4,5,8', 'foo', $x);
    add_elements('xxx 1-3,5,8', 'foo', $x);
    print_r($x);
    exit;
}

// $x is the publisher_information arg for a score file.
// If it contains a {{P template call,
// parse the args and return a PHP object.
// Else return null
//
function parse_publisher($str) {
    $pos = 0;
    $other_text = '';
    $pub = null;
    while (true) {
        [$item, $new_pos] = parse_item($str, $pos);
        $pos = $new_pos;
        if ($item === false) break;
        if (is_string($item)) {
            $other_text .= " $item";
        } else {
            if ($item->name === 'P') {
                $pub = new StdClass;
                $args = $item->args;
                $n = count($args);
                $pub->name = $n>0?$args[0]:'';
                $pub->imprint = $n>1?$args[1]:'';
                $pub->location = $n>2?$args[2]:'';
                $pub->date = $n>3?$args[3]:'';
                $pub->year = $n>4?(int)$args[4]:0;
                $pub->edition_number = $n>5?$args[5]:'';
                $pub->plate_number = $n>6?$args[6]:'';
            }
        }
    }
    return $pub;
}

if (0) {
    $x = "''[[Haydn - Piano Sonatas (Martienssen)|Sonaten f▒~C¼r Klavier zu zwei H▒~CC¤nden]]''<br>{{P|Edition Peters|C.F. Peters|Leipzig||1937||11261}}";
    print_r(parse_publisher($x));
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
        // MUST USE === in name comparisons (0 == 'foo' thing)
        if ($name === 'Amazon') {
            $f->amazon = $val;
        } else if ($name === 'Arranger') {
            $f->arranger = $val;
        } else if ($name === 'Copyright') {
            $f->copyright = $val;
        } else if ($name === 'Date Submitted') {
            $f->date_submitted = $val;
        } else if (starts_with($name, 'Date Submitted ')) {
            add_elements($name, $val, $f->date_submitteds);
        } else if ($name === 'Editor') {
            $f->editor = $val;
        } else if ($name === 'Engraver') {
            $f->engraver = $val;
        } else if (starts_with($name, 'File Name ')) {
            add_elements($name, $val, $f->file_names);
        } else if (starts_with($name, 'File Description ')) {
            add_elements($name, $val, $f->file_descs);
        } else if ($name === 'File Tags') {
            $f->file_tags = $val;
        } else if ($name === 'Image Type') {
            $f->image_type = $val;
        } else if ($name === 'Misc. Notes') {
            $f->misc_notes = $val;
        } else if (starts_with($name, 'Page Count ')) {
            add_elements($name, $val, $f->page_counts);
        } else if ($name === 'Publisher Information') {
            $f->publisher_information = $val;
            $f->pub = parse_publisher($val);
        } else if ($name === 'Reprint') {
            $f->reprint = $val;
        } else if ($name === 'Sample Filename') {
            $f->sample_filename = $val;
        } else if (starts_with($name, 'Sample Filename ')) {
            add_elements($name, $val, $f->sample_filenames);
        } else if ($name === 'Scanner') {
            $f->scanner = $val;
        } else if (starts_with($name, 'Scanner ')) {
            add_elements($name, $val, $f->scanners);
        } else if ($name === 'SM+') {
            $f->sm_plus = $val;
        } else if ($name === 'Thumb Filename') {
            $f->thumb_filename = $val;
        } else if (starts_with($name, 'Thumb Filename ')) {
            add_elements($name, $val, $f->thumb_filenames);
        } else if ($name === 'Translator') {
            $f->translator = $val;
        } else if ($name === 'Uploader') {
            $f->uploader = $val;
        } else if (starts_with($name, 'Uploader ')) {
            add_elements($name, $val, $f->uploaders);
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
        // MUST USE === in name comparisons (0 == 'foo' thing)
        if ($name === 'Copyright') {
            $f->copyright = $val;
        } else if ($name === 'Date Submitted') {
            $f->date_submitted = $val;
        } else if (starts_with($name, 'Date Submitted ')) {
            add_elements($name, $val, $f->date_submitteds);
        } else if (starts_with($name, 'File Name ')) {
            add_elements($name, $val, $f->file_names);
        } else if (starts_with($name, 'File Description ')) {
            add_elements($name, $val, $f->file_descs);
        } else if ($name === 'Misc. Notes') {
            $f->misc_notes = $val;
        } else if ($name === 'Performer Categories') {
            $f->performer_categories = $val;
        } else if ($name === 'Performers') {
            $f->performers = $val;
        } else if ($name === 'Publisher Information') {
            $f->publisher_information= $val;
        } else if ($name === 'Uploader') {
            $f->uploader = $val;
        } else if (starts_with($name, 'Uploader ')) {
            add_elements($name, $val, $f->uploaders);
        } else if ($name === 'Thumb Filename') {
            $f->thumb_filename = $val;
        //} else if ($name === 'Arranger') {
        //    $f->arranger = $val;
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

// parse the *****AUDIO***** arg of a composition page
//
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
    $comp->json_title = $title;
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
        } else if ($item->name == 'WorkNonPD-USandEU') {
            $comp->nonpd_us = true;
            $comp->nonpd_eu = true;
        } else if ($item->name == 'WorkNonPD-EU') {
            $comp->nonpd_eu = true;
        } else if ($item->name == 'PopSection') {
            $comp->pop_section = $item->args[0];
        } else if (!strcasecmp($item->name, 'Mbox')) {
            $comp->mbox = $item->args[0];
        } else if ($item->name == 'CollPage') {
            // meaning ??
        } else if ($item->name == 'CollabCat') {
            // meaning ??
        } else if ($item->name == 'Collab') {
        } else if ($item->name == 'Symphonies (Beethoven, Ludwig van)') {
        } else if ($item->name == 'Operas (Wagner, Richard)') {
        } else if ($item->name == 'Concertos_(Mendelssohn,_Felix)') {
        } else if ($item->name == 'LinkWorkN') {
        } else if ($item->name == 'LinkName') {
        } else if ($item->name == 'LinkComp') {
        } else if ($item->name == 'fax') {
        } else if ($item->name == 'PMBSrp') {
        } else if ($item->name == '#fte:imslppage') {
            $comp->imslppage = true;
            foreach ($item->args as $name=>$arg) {
                // MUST USE === in name comparisons (0 == 'foo' thing)
                if ($verbose) {
                    echo "arg $name:\n";
                }
                if ($name === '*****AUDIO*****') {
                    $comp->audios = parse_audio($arg);
                } else if ($name === '*****FILES*****') {
                    $comp->files = parse_files($arg);
                } else if ($name === 'Alternative Title') {
                    $comp->alternative_title = $arg;
                } else if ($name === 'Authorities') {
                    $comp->authorities = $arg;
                } else if ($name === 'Average Duration') {
                    $comp->average_duration = $arg;
                } else if ($name === '*****COMMENTS*****') {
                    $comp->comments = $arg;
                } else if ($name === 'Dedication') {
                    $comp->dedication = $arg;
                } else if ($name === 'Discography') {
                    $comp->discography = $arg;
                } else if ($name === 'External Links') {
                    $comp->external_links = $arg;
                } else if ($name === 'Extra Information') {
                    $comp->extra_info = $arg;
                } else if ($name === 'First Performance') {
                    $comp->first_performance = $arg;
                } else if ($name === 'Incipit') {
                    $comp->incipit = $arg;
                } else if ($name === 'InstrDetail') {
                    $comp->instr_detail = $arg;
                } else if ($name === 'Instrumentation') {
                    $comp->instrumentation = $arg;
                } else if ($name === 'Key') {
                    $comp->key = $arg;
                } else if ($name === 'Language') {
                    $comp->language = $arg;
                } else if ($name === 'Librettist') {
                    $comp->librettist = $arg;
                } else if ($name === 'Manuscript Sources') {
                    $comp->manuscript_sources = $arg;
                } else if ($name === 'Movements Header') {
                    $comp->movements_header = $arg;
                } else if ($name === 'NCRecordings') {
                    $comp->ncrecordings = $arg;
                } else if ($name === 'Number of Movements/Sections') {
                    $comp->number_of_movements_sections = $arg;
                } else if ($name === 'Opus/Catalogue Number') {
                    $comp->opus_catalogue = $arg;
                } else if ($name === 'Piece Style') {
                    $comp->piece_style = $arg;
                } else if ($name === 'Related Works') {
                    $comp->related_works = $arg;
                } else if ($name === 'SearchKey') {
                    $comp->searchkey = $arg;
                } else if ($name === 'SearchKey-amarec') {
                    $comp->searchkey_amarec = $arg;
                } else if ($name === 'SearchKey-scores') {
                    $comp->searchkey_scores = $arg;
                } else if ($name === 'Tags') {
                    $comp->tags = $arg;
                } else if ($name === 'Year/Date of Composition') {
                    $comp->year_date_of_composition = $arg;
                } else if ($name === 'Year of First Publication') {
                    $comp->year_of_first_publication = $arg;
                } else if ($name === 'Work Title') {
                    $comp->work_title = $arg;
                } else if ($arg === '*****WORK INFO*****') {
                } else if ($arg === '*****END OF TEMPLATE*****') {
                } else {
                    echo "unrecognized composition arg name: $name: $arg\n";
                }
            }
        } else {
            echo "unrecognized template name: $item->name\n";
            echo "exiting\n";
            exit;
        }
    }
    return $comp;
}

?>
