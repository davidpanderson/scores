<?php

///////////////////////////
//
// code for parsing (possibly nested) Mediawiki template calls
// These look like
//      {{name|arg1|arg}}
// or
//      {{templatename | name=val | name=val}}
// and the arguments can contain template calls.
//
///////////////////////////

function starts_with($text, $x) {
    return strpos($text, $x) === 0;
}

// scan a template argument.
// Ignore the contents of {{...}} (template calls)
// and [[...]] (wiki links, which can contain |)
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
        if (substr($str, $i, 2) == '{{') {
            $template_depth += 1;
            $i += 1;
            continue;
        }
        if (substr($str, $i, 2) == '}}') {
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

function substr2($str, $start, $end) {
    return substr($str, $start, $end-$start);
}

// append an argument (named or positional) to the array $args
//
function add_arg(&$args, $str, $start_pos, $eq_pos, $end_pos) {
    if ($eq_pos>0) {
        $name = trim(substr2($str, $start_pos, $eq_pos));
        $value = trim(substr2($str, $eq_pos+1, $end_pos));
        $args[$name] = $value;
    } else {
        $value = trim(substr2($str, $start_pos, $end_pos));
        $args[] = $value;
    }
}

// given a string of the form
// {{tname | arg=val | arg=val ... }}
// return the template name and an array of argument names and values.
// The values may contain template calls.
//
function parse_template($str) {
    $i = 2;
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
                echo "malformed template call: $str\n";
                exit;
            }
        }
    } else {
        echo "malformed template call: $str\n";
        exit;
    }
    return [$template_name, $args];
}

if (0) {
    $x = "{{blah |x=\n |y=\n{{foo: |a=b}}}}";
    print_r(parse_template($x));
}

?>
