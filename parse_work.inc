<?php

// parse IMSLP data (in JSON/mediawiki format) into PHP data structures.
//
// we parse high-level constructs:
// work (#fte:imslppage)
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
        if ($item === null) break;
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
                $pub->plate_number = empty($args[6])?'':$args[6];
            }
        }
    }
    return $pub;
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
        } else if ($name === 'Thumb Filename') {
            $f->thumb_filename = $val;
        } else if ($name === 'Uploader') {
            $f->uploader = $val;
        } else if (starts_with($name, 'Uploader ')) {
            add_elements($name, $val, $f->uploaders);
        //} else if ($name === 'Arranger') {
        //    $f->arranger = $val;
        } else {
            echo "unrecognized audio arg: $name ($val)\n";
        }
    }
    return $f;
}

// parse the *****FILES***** part of a work.
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
        if ($item === null) break;
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
            if ($item->name == '#fte:') {
                $x = array_shift($item->args);
                if ($x == 'imslpfile') {
                    $files[] = file_args_to_object($item->args);
                } else {
                    echo "unrecognized #fte: in file list: $x\n";
                }
            } else {
                echo "unrecognized template in file list: $item->name\n";
            }
            $nfiles++;
        }
    }
    return $files;
}

// parse the *****AUDIO***** arg of a work page
//
function parse_audio($str) {
    $pos = 0;
    $files = [];
    $nfiles = 0;
    $nstr = 0;
    while (true) {
        [$item, $new_pos] = parse_item($str, $pos);
        if ($item === false) break;
        if ($item === null) break;
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
            if ($item->name == '#fte:') {
                $x = array_shift($item->args);
                if ($x == 'imslpaudio') {
                    $files[] = audio_args_to_object($item->args);
                } else {
                    echo "unrecognized #fte: in audio list: $x\n";
                }
            } else {
                echo "unrecognized template in audio list: $item->name\n";
            }
            $nfiles++;
        }
    }
    return $files;
}

// parse a work page.
// Return an object containing most of the info.
// Return null if error
//
function parse_work($title, $body) {
    global $verbose;
    if ($verbose) {
        echo "\n------------------\nprocessing $title\n";
    }
    $work = new StdClass;
    $work->json_title = $title;
    $work->extra_text = [];
    if (starts_with($body, '#REDIRECT')) {
        $lines = explode("\n", $body);
        $work->redirect = $lines[0];
        for ($i=1; $i<count($lines); $i++) {
            $x = trim($lines[$i]);
            if ($x) {
                $work->extra_text[] = $x;
            }
        }
        return $work;
    }
    $pos = 0;
    while (true) {
        [$item, $new_pos] = parse_item($body, $pos);
        $pos = $new_pos;
        if ($item === false) break;
        if ($item === null) break;
        if (is_string($item)) {
            $work->extra_text[] = $item;
            //echo "non-template text: $item\n";
            continue;
        }
        if ($verbose) {
            echo "got template $item->name\n";
        }
        if (strtolower($item->name) == 'attrib') {
            $work->attrib = $item->args[0];
        } else if ($item->name == 'WorkNonPD-USandEU') {
            $work->nonpd_us = true;
            $work->nonpd_eu = true;
        } else if ($item->name == 'WorkNonPD-EU') {
            $work->nonpd_eu = true;
        } else if ($item->name == 'PopSection') {
            $work->pop_section = $item->args[0];
        } else if (!strcasecmp($item->name, 'Mbox')) {
            $work->mbox = $item->args[0];
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
        } else if ($item->name == '#fte:') {
            $x = array_shift($item->args);
            if ($x !== 'imslppage') {
                echo "unrecognized #fte: $x";
                continue;
            }
            $work->imslppage = true;
            foreach ($item->args as $name=>$arg) {
                // MUST USE === in name comparisons (0 == 'foo' thing)
                if ($verbose) {
                    echo "arg $name:\n";
                }
                if ($name === '*****AUDIO*****') {
                    $work->audios = parse_audio($arg);
                } else if ($name === '*****FILES*****') {
                    $work->files = parse_files($arg);
                } else if ($name === 'Alternative Title') {
                    $work->alternative_title = $arg;
                } else if ($name === 'Authorities') {
                    $work->authorities = $arg;
                } else if ($name === 'Average Duration') {
                    $work->average_duration = $arg;
                } else if ($name === '*****COMMENTS*****') {
                    $work->comments = $arg;
                } else if ($name === 'Dedication') {
                    $work->dedication = $arg;
                } else if ($name === 'Discography') {
                    $work->discography = $arg;
                } else if ($name === 'External Links') {
                    $work->external_links = $arg;
                } else if ($name === 'Extra Information') {
                    $work->extra_info = $arg;
                } else if ($name === 'First Performance') {
                    $work->first_performance = $arg;
                } else if ($name === 'Incipit') {
                    $work->incipit = $arg;
                } else if ($name === 'InstrDetail') {
                    $work->instr_detail = $arg;
                } else if ($name === 'Instrumentation') {
                    $work->instrumentation = $arg;
                } else if ($name === 'Key') {
                    $work->key = $arg;
                } else if ($name === 'Language') {
                    $work->language = $arg;
                } else if ($name === 'Librettist') {
                    $work->librettist = $arg;
                } else if ($name === 'Manuscript Sources') {
                    $work->manuscript_sources = $arg;
                } else if ($name === 'Movements Header') {
                    $work->movements_header = $arg;
                } else if ($name === 'NCRecordings') {
                    $work->ncrecordings = $arg;
                } else if ($name === 'Number of Movements/Sections') {
                    $work->number_of_movements_sections = $arg;
                } else if ($name === 'Opus/Catalogue Number') {
                    $work->opus_catalogue = $arg;
                } else if ($name === 'Piece Style') {
                    $work->piece_style = $arg;
                } else if ($name === 'Related Works') {
                    $work->related_works = $arg;
                } else if ($name === 'SearchKey') {
                    $work->searchkey = $arg;
                } else if ($name === 'SearchKey-amarec') {
                    $work->searchkey_amarec = $arg;
                } else if ($name === 'SearchKey-scores') {
                    $work->searchkey_scores = $arg;
                } else if ($name === 'Tags') {
                    $work->tags = $arg;
                } else if ($name === 'Year/Date of Composition') {
                    $work->year_date_of_composition = $arg;
                } else if ($name === 'Year of First Publication') {
                    $work->year_of_first_publication = $arg;
                } else if ($name === 'Work Title') {
                    $work->work_title = $arg;
                } else if ($arg === '*****WORK INFO*****') {
                } else if ($arg === '*****END OF TEMPLATE*****') {
                } else {
                    echo "unrecognized work arg name: $name: $arg\n";
                }
            }
        } else {
            echo "unrecognized template in parse_work(): $item->name\n";
        }
    }
    return $work;
}

///////////  parse performers, performer_categories in audio files

$ensemble_types = ['orchestra', 'piano trio', 'ensemble'];

// parse
// {{GardnerPerf|Martina Filjak, Piano}}
// {{GardnerPerf|Yunjie Chen (piano)}}
// {{GardnerPerf|Mark Padmore (voice), Jonathan Biss (piano)}}
// {{GardnerPerf|Benjamin Beilman, violin}}; {{GardnerPerf|Yekwon Sunwoo, piano}}
// {{GardnerPerf|Rebel Baroque Orchestra (ensemble)}}
//      (return ensemble)
//
// don't parse
// {{GardnerPerf|Jeremy Denk}} (piano)
// {{GardnerPerf|Chamber Music Society of Lincoln Center}}<br><small>{{GardnerPerf|Ida Kavafian, Ani Kavafian (violins), Paul Neubauer, Roberto Diaz (violas),<br>Ronald Thomas, Fred Sherry (cellos)}}
// (gotta draw the line somewhere)
//
// return [ensemble_name, list of [first, last, instr]]

function parse_gardner_perf($str) {
    global $ensemble_types;
    $ensemble = null;
    $performers = [];
    $i = strpos($str, '|');
    if ($i === false) return;
    $str = substr($str, $i+1);

    // if there's a paren, accept only "first last (inst), first last (inst)"
    //
    if (strpos($str, '(') !== false) {
        $parts = explode(',', $str);
        foreach ($parts as $part) {
            $p = parse_perf_paren($part);
            if (!$p) {
                return [null, []];
            }
            if (in_array($p[2], $ensemble_types)) {
                $name = $p[0]." ".$p[1];
                if (valid_ensemble_name($name)) {
                    $ensemble = [$name , $p[2]];
                }
            } else {
                $performers[] = $p;
            }
        }
    } else {
        $p = parse_perf_comma($str);
        if ($p) {
            if (in_array($p[2], $ensemble_types)) {
                $name = $p[0]." ".$p[1];
                if (valid_ensemble_name($name)) {
                    $ensemble = [$name , $p[2]];
                }
            } else {
                $performers[] = $p;
            }
        }
    }
    return [$ensemble, $performers];
}

function parse_gardner_perfs($str) {
    $pos = 0;
    $ensemble = null;
    $performers = [];
    while (1) {
        $i = strpos($str, '{{GardnerPerf', $pos);
        if ($i === false) break;
        $j = strpos($str, '}}', $i);
        if ($j === false) break;
        [$ens, $perf] = parse_gardner_perf(substr2($str, $i, $j));
        if ($ens) $ensemble = $ens;
        $performers = array_merge($performers, $perf);
        $pos = $j+2;
    }
    return [$ensemble, $performers];
}

// for instrument, ensemble person
//
function valid_name($str) {
    if (!$str) return true;
    if (strstr($str, '{')) return false;
    if (strstr($str, '[')) return false;
    if (strstr($str, '|')) return false;
    if (is_numeric($str[0])) return false;
    return true;
}

function valid_ensemble_name($str) {
    return valid_name($str);
}

function valid_person_name($str) {
    if (strlen($str)>89) return false;
    return valid_name($str);
}

// if str is of form "Joe Smith, piano" return [first, last, instr]
//
function parse_perf_comma($str) {
    if (strpos($str, '[[') !== false) return null;
    if (strpos($str, '(') !== false) return null;
    if (strpos($str, ';') !== false) return null;

    $n = strpos($str, ',');
    if (!$n) return null;

    if (strpos($str, ',', $n+1)) return null;

    $left = trim(substr2($str, 0, $n));
    if (!$left) return null;

    // get the name part
    //
    if (count(explode(' ', $left))>5) return null;
    [$first, $last] = parse_name($left);
    if (count(explode(' ', $first))>4) return null;
    if (!valid_person_name($first)) return null;
    if (!valid_person_name($last)) return null;

    // get the instrument
    $right = trim(substr2($str, $n+1, strlen($str)));
    if (!$right) return null;
    if (!valid_name($right)) return null;

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
    if (trim(substr2($str, $m+1, strlen($str)))) return null;
        // nothing after )

    // get the name part
    $left = trim(substr2($str, 0, $n));
    if (!$left) return null;
    [$first, $last] = parse_name($left);
    if (count(explode(' ', $first))>4) return null;
    if (count(explode(' ', $last))>1) return null;
    if (!valid_person_name($first)) return null;
    if (!valid_person_name($last)) return null;

    // get the instrument
    $inst = trim(substr2($str, $n+1, $m));
    if (!$inst) return null;
    if (!valid_name($inst)) return null;

    return [$first, $last, $inst];
}

function parse_perf($str) {
    $x = parse_perf_comma($str);
    if ($x) return $x;
    return parse_perf_paren($str);
}

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
//
// given the above fields, return
// - an ensemble [name, type]
// - a list of performers [first, last, instrument]
//
function parse_performers($perf, $perf_cat) {
    global $ensemble_types;

    $ensemble = null;
    $performers = [];
    $got_conductor = false;

    $perf = str_replace('<br>', ';', $perf);
    $perf = strip_tags($perf);
    $perf_cat = strip_tags($perf_cat);

    // start with performer_categories
    //
    $fields = explode(';', $perf_cat);
    // some operas list all the roles; skip these
    if (count($fields) < 5) {
        foreach ($fields as $field) {
            $parts = explode('=', $field);
            if (count($parts) != 2) continue;
            $inst = trim(strtolower($parts[1]));
            if (in_array($inst, $ensemble_types)) {
                $name = trim($parts[0]);
                if (valid_ensemble_name($name)) {
                    $ensemble = [$name , $inst];
                }
            } else {
                if ($inst == 'conductor') $got_conductor = true;
                [$first, $last] = parse_name($parts[0]);
                if (valid_person_name($last) && valid_person_name($last)) {
                    $performers[] = [$first, $last, $inst];
                }
            }
        }
    }

    // now performer
    //
    if (!$perf) {
        return [$ensemble, $performers];
    }

    // parse {{GardnerPerf
    //
    [$ens, $perfs] = parse_gardner_perfs($perf);
    if ($ens || $perfs) {
        if ($ens) $ensemble = $ens;
        $performers = array_merge($performers, $perfs);
        return [$ensemble, $performers];
    }

    // parse single performer
    //
    $p = parse_perf($perf);
    if ($p) {
        if ($p[2] == 'orchestra') {
            // parse "NY Philharmonic (orchestra)"
            //
            $name = $p[0]." ".$p[1];
            if (valid_ensemble_name($name)) {
                $ensemble = [$name , $p[2]];
            }
        } else {
            $performers[] = $p;
        }
        return [$ensemble, $performers];
    }
    // parse multiple performers separated by ;
    // "Joe smith, piano ; john doe, violin"
    //
    $parts = explode(';', $perf);
    if (count($parts) > 1) {
        foreach ($parts as $part) {
            $p = parse_perf($part);
            if ($p) {
                if (in_array($p[2], $ensemble_types)) {
                    $name = $p[0]." ".$p[1];
                    if (valid_ensemble_name($name)) {
                        $ensemble = [$name , $p[2]];
                    }
                } else {
                    $performers[] = $p;
                }
            }
        }
        return [$ensemble, $performers];
    }
    // parse multiple performers separated by ,
    // parse "joe smith (piano), john doe (violin)"
    //
    $parts = explode(',', $perf);
    if (count($parts) > 1) {
        foreach ($parts as $part) {
            $p = parse_perf_paren($part);
            if ($p) {
                if (in_array($p[2], $ensemble_types)) {
                    $name = $p[0]." ".$p[1];
                    if (valid_ensemble_name($name)) {
                        $ensemble = [$name , $p[2]];
                    }
                } else {
                    $performers[] = $p;
                }
            }
        }
    }
    return [$ensemble, $performers];
}

?>
