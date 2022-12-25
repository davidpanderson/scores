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

///////////  parse performers, performer_categories in audio files

// $str is of the form "first last" or "last, first".
// Return [first, last]
//
function parse_name($str) {
    $str = trim($str);
    $n = strpos($str, ',');
    if ($n) {
        $last = substr2($str, 0, $n);
        $first = substr2($str, $n+1, strlen($str));
    } else {
        $n = strrpos($str, ' ');
        if ($n) {
            $last = substr2($str, $n+1, strlen($str));
            $first = substr2($str, 0, $n);
        } else {
            $last = $str;
            $first = '';
        }
    }
    return [trim($first), trim($last)];
}

if (0) {
    print_r(parse_name('John Paul Jones'));
    print_r(parse_name('Jones, John Paul'));
    print_r(parse_name('Charo'));
}

//print_r(parse_title("Piano_Sonata_No.13,_Op.27_No.1_(suite)_(Beethoven,_Ludwig_van)"));

function valid_instrument($str) {
    if (is_numeric(substr2($str, 0, 1))) return false;
    return true;
}

// if str is of form "Joe Smith, piano" return [first, last, instr]
//
function parse_perf_comma($str) {
    if (strpos($str, '[[') !== false) return null;
    if (strpos($str, '(') !== false) return null;
    if (strpos($str, ';') !== false) return null;
    $n = strrpos($str, ',');
    if (!$n) return null;
    $left = trim(substr2($str, 0, $n));
    if (!$left) return null;
    if (count(explode(' ', $left))>3) return null;
    [$first, $last] = parse_name($left);
    $right = trim(substr2($str, $n+1, strlen($str)));
    if (!$right) return null;
    if (!valid_instrument($right)) return null;
    if (count(explode(' ', $first))>2) return null;
    return [$first, $last, $right];
}

// if str is of form "Joe Smith (piano)" return [first, last, instr]
//
function parse_perf_paren($str) {
    if (strpos($str, '[[') !== false) return null;
    $n = strpos($str, '(');
    if (!$n) return null;
    $m = strpos($str, ')');
    if (!$m) return null;
    if ($n>$m) return null;
    if (trim(substr2($str, $m+1, strlen($str)))) return null;  // nothing after )
    $left = trim(substr2($str, 0, $n));
    if (!$left) return null;
    [$first, $last] = parse_name($left);
    if (count(explode(' ', $first))>2) return null;
    if (count(explode(' ', $last))>1) return null;
    $inst = trim(substr2($str, $n+1, $m));
    if (!$inst) return null;
    if (!valid_instrument($inst)) return null;
    return [$first, $last, $inst];
}

function parse_perf($str) {
    $x = parse_perf_comma($str);
    if ($x) return $x;
    return parse_perf_paren($str);
}

if (0) {
    print_r(parse_perf('Joe Smith, piano'));
    print_r(parse_perf('Joe Smith (piano)'));
    print_r(parse_perf('Joe Smith piano'));
}

$ensemble_names = ['orchestra', 'piano trio'];

// parse the performer_categories and performer args of audio file
//
// performer_categories has
//     foo=orchestra;smith, joe=conductor
//     joe smith=piano, conductor
// performer (less frequently) has
//     Joe Smith, piano
//     Joe Smith (piano)
//     Joe Smith (piano)*
//     Joe smith, piano ; john doe, violin
//     joe smith (piano), john doe (violin)
//     orchestra name (if performer_categories has =conductor)
//
// given the above fields, return
// - an ensemble [name, type]
// - a list of performers [first, last, instrument]
//
function parse_performers($perf, $perf_cat) {
    global $ensemble_names;

    $ensemble = null;
    $performers = [];
    $got_conductor = false;

    $perf = str_replace('<br>', ';', $perf);
    $perf = strip_tags($perf);
    $perf_cat = strip_tags($perf_cat);

    // start with performer_categories
    //
    $fields = explode(';', $perf_cat);
    foreach ($fields as $field) {
        $parts = explode('=', $field);
        if (count($parts) != 2) continue;
        $inst = trim(strtolower($parts[1]));
        if (in_array($inst, $ensemble_names)) {
            $ensemble = [trim($parts[0]), $inst];
        } else {
            if ($inst == 'conductor') $got_conductor = true;
            [$first, $last] = parse_name($parts[0]);
            $performers[] = [$first, $last, $inst];
        }
    }
    // now performer
    //
    if ($perf) {
        if ($got_conductor && !$ensemble) {
            $ensemble = [trim($perf), 'orchestra'];
        } else {
            $p = parse_perf($perf);
            if ($p) {
                if ($p[2] == 'orchestra') {
                    $ensemble = [$p[0]." ".$p[1], $p[2]];
                } else {
                    $performers[] = $p;
                }
            } else {
                $parts = explode(';', $perf);
                if (count($parts) > 1) {
                    foreach ($parts as $part) {
                        $p = parse_perf($part);
                        if ($p) {
                            if (in_array($p[2], $ensemble_names)) {
                                $ensemble = [$p[0]." ".$p[1], $p[2]];
                            } else {
                                $performers[] = $p;
                            }
                        }
                    }
                } else {
                    $parts = explode(',', $perf);
                    if (count($parts) > 1) {
                        foreach ($parts as $part) {
                            $p = parse_perf_paren($part);
                            if ($p) {
                                if (in_array($p[2], $ensemble_names)) {
                                    $ensemble = [$p[0]." ".$p[1], $p[2]];
                                } else {
                                    $performers[] = $p;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    // skip bad ensemble names
    if ($ensemble) {
        $x = $ensemble[0];
        if (strstr($x, '{{') || strstr($x, '[[')) {
            $ensemble = null;
        }
    }
    return [$ensemble, $performers];
}

if (0) {
    print_r(parse_performers('Orchestre Symphonique Munich (orchestra)',''));
}

////////////////  arrangement target (from score hiers)

function parse_arrangement_target($hier) {
    if ($hier[0] != 'Arrangements and Transcriptions') return null;
    $x = $hier[2];
    if (substr2($x, 0, 4) == 'For ') {
        $start = 4;
    } else if (substr2($x, 0, 5) == '*For ') {
        $start = 5;
    } else {
        return null;
    }
    $n = strpos($x, '(');
    if ($n) {
        $end = $n-1;
    } else {
        $end = strlen($x);
    }
    return substr2($x, $start, $end);
}

?>
