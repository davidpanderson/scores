<?php

// Functions for parsing Mediawiki pages.
// These are an alternation of text and template calls.
// templates look like
//      {{name|arg1|arg}}
// or
//      {{templatename | name=val | name=val}}
// and the arguments can contain template calls.

// case-insensitive
//
function starts_with($text, $x) {
    return stripos($text, $x) === 0;
}

function substr2($str, $start, $end) {
    return substr($str, $start, $end-$start);
}

// scan a template argument.
// Ignore the contents of:
//      {{...}} (template calls, which can contain |)
//      [[...]] (wiki links, which can contain |)
//      <score>...</score> (incipit strings, which can contain { {, }} etc.)
// Return
//      eq_pos: offset of top-level = or -1
//      arg_end_pos: offset of top-level | or -1
//      template_end_pos: offset of top-level }} or -1
//
function scan_arg($str, $start_pos) {
    $n = strlen($str);
    $template_depth = 0;
    $link_depth = 0;
    $incipit_depth = 0;
    $eq_pos = -1;
    $arg_end_pos = -1;
    $template_end_pos = -1;
    for ($i=$start_pos; $i < $n; $i++) {
        if (!$incipit_depth && substr($str, $i, 2) == '{{') {
            $template_depth += 1;
            $i += 1;
            continue;
        }
        if (!$incipit_depth && substr($str, $i, 2) == '}}') {
            if ($template_depth == 0) {
                $template_end_pos = $i;
                break;
            }
            $template_depth -= 1;
            $i += 1;
            continue;
        }
        if (substr($str, $i, 2) == '[[') {
            $link_depth += 1;
            $i += 1;
            continue;
        }
        if (substr($str, $i, 2) == ']]') {
            $link_depth -= 1;
            $i += 1;
            continue;
        }
        if (substr($str, $i, 7) == '<score>') {
            $incipit_depth += 1;
            $i += 6;
            continue;
        }
        if (substr($str, $i, 8) == '</score>') {
            $incipit_depth -= 1;
            $i += 7;
            continue;
        }
        // look for | and =, but only if we're at top level
        //
        if (!$template_depth && !$link_depth && !$incipit_depth) {
            if (substr($str, $i, 1) == '|') {
                $arg_end_pos = $i;
                break;
            }
            if ($eq_pos<0 && substr($str, $i, 1) == '=') {
                $eq_pos = $i;
            }
        }
    }
    return [$eq_pos, $arg_end_pos, $template_end_pos];
}

if (0) {
    $x = "Copyright=Public Domain
|Thumb Filename=Contenu neutre.jpg|200px|thumb|left
|Misc. Notes=";
    print_r(scan_arg($x, 0));
}


// append an argument (named or positional) to the array $args
//
function add_arg(&$args, $str, $start_pos, $eq_pos, $end_pos) {
    if ($eq_pos>0) {
        $name = trim(substr2($str, $start_pos, $eq_pos));
        $value = trim(substr2($str, $eq_pos+1, $end_pos));
        $args[$name] = $value;
    } else {
        // this avoids PHP's 0 == 'foobar' debacle
        //
        $value = trim(substr2($str, $start_pos, $end_pos));
        $args['positional_arg'] = $value;
    }
}

// given a string of the form
// {{tname | arg=val | arg=val ... }}
// return
//      - the template name
//      - an array of argument names and values.
//      - the offset of the position past the }}
// The values may contain template calls.
//
function parse_template($str, $start_pos) {
    $i = $start_pos + 2;
    $args = [];

    // get the template name
    //
    [$eq_pos, $arg_end_pos, $template_end_pos] = scan_arg($str, $i);

    //echo "i $i eq_pos $eq_pos arg_end_pos $arg_end_pos temp_end_pos $template_end_pos\n";

    if ($template_end_pos > 0) {
        $template_name = trim(substr2($str, $i, $template_end_pos));
    } else if ($arg_end_pos > 0) {
        // if there are arguments, parse them
        //
        $template_name = trim(substr2($str, $i, $arg_end_pos));
        $i = $arg_end_pos + 1;
        while (true) {
            [$eq_pos, $arg_end_pos, $template_end_pos] = scan_arg($str, $i);
            //echo "i $i eq_pos $eq_pos arg_end_pos $arg_end_pos temp_end_pos $template_end_pos\n";
            if ($template_end_pos > 0) {
                add_arg($args, $str, $i, $eq_pos, $template_end_pos);        
                break;
            } else if ($arg_end_pos > 0) {
                add_arg($args, $str, $i, $eq_pos, $arg_end_pos);
                $i = $arg_end_pos + 1;
            } else {
                echo sprintf('malformed template call: %s\n', substr($str, $start_pos));
                debug_print_backtrace();
                exit;
            }
        }
    } else {
        echo sprintf('malformed template call: %s\n', substr($str, $start_pos));
        debug_print_backtrace();
        exit;
    }
    return [$template_name, $args, $template_end_pos+2];
}

// parse an "item", which is either
// - a template call, in which case return [object, new_pos]
// - other text, in which case return [string, new_pos]
// - the end of the string is reached: return [false, 0]
//
function parse_item($str, $pos) {
    $tstart = strpos($str, '{{', $pos);
    if ($tstart === false) {
        // no more template calls
        //
        $y = trim(substr($str, $pos));
        if ($y) {
            return [$y, strlen($str)];
        }
        return [false, 0];
    }
    // check for text before the template call
    //
    $y = trim(substr2($str, $pos, $tstart));
    if ($y) {
        return [$y, $tstart];
    }
    // if none, parse the template call
    //
    [$template_name, $args, $end_pos] = parse_template($str, $tstart);
    $x = new StdClass;
    $x->name = $template_name;
    $x->args = $args;
    return [$x, $end_pos];
}

if (0) {
    $str = "foobar {{blah |x=\n |y=\n{{foo: |a=b}}}} blah";
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
                echo "  arg $n => $v\n";
            }
        }
    }
}

?>
