<?php

// Functions for parsing Mediawiki pages.
// These are an alternation of text and template calls.
// templates look like
//      {{name|arg1|arg}}
// or
//      {{templatename | name=val | name=val}}
// and the arguments can contain template calls.

$mw_verbose = false;

require_once("cmi_util.inc");

// get first part of line (for debugging)
//
function str_head($str, $pos) {
    $str = str_replace("\n", ' ', $str);
    if ($pos + 800 > strlen($str)) {
        return substr($str, $pos);
    }
    return substr($str, $pos, 80).' ...';
}

// scan a template argument.
// Ignore the contents of:
//      {{...}} (template calls, which can contain |)
//      [[...]] (wiki links, which can contain |)
//      <score>...</score> (incipit strings, which can contain { {, }} etc.)
//          can also be <score raw=1> (ugh).
// Return
//      eq_pos: offset of top-level = or -1
//      arg_end_pos: offset of top-level | or -1
//      template_end_pos: offset of top-level }} or -1
//
function scan_arg($str, $start_pos) {
    global $mw_verbose;

    if ($mw_verbose) {
        echo sprintf("scan_arg(): %s\n", str_head($str, $start_pos));
    }
    $len = strlen($str);
    $template_depth = 0;
    $arg_depth = 0;
    $link_depth = 0;
    $incipit_depth = 0;
    $eq_pos = -1;
    $arg_end_pos = -1;
    $template_end_pos = -1;
    for ($i=$start_pos; $i < $len; $i++) {
        if (!$incipit_depth && substr($str, $i, 3) == '{{{') {
            $arg_depth += 1;
            $i += 2;
            continue;
        }
        if (!$incipit_depth && $arg_depth && substr($str, $i, 3) == '}}}') {
            $arg_depth -= 1;
            $i += 2;
            continue;
        }
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
        // Aaargh!!  sometimes they spell it <Score>
        // And it can also be <score raw=1>
        $x = strtolower(substr($str, $i, 7));
        if ($x == '<score>' || $x == '<score ') {
            $incipit_depth += 1;
            $i += 6;
            continue;
        }
        if (strtolower(substr($str, $i, 8)) == '</score>') {
            $incipit_depth -= 1;
            $i += 7;
            continue;
        }
        // look for | and =, but only if we're at top level
        //
        if (!$template_depth && !$link_depth && !$incipit_depth && !$arg_depth) {
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

// append an argument (named or positional) to the array $args
//
function add_arg(&$args, $str, $start_pos, $eq_pos, $end_pos) {
    if ($eq_pos>0) {
        $name = trim(substr2($str, $start_pos, $eq_pos));
        $value = trim(substr2($str, $eq_pos+1, $end_pos));
        $args[$name] = $value;
        //echo "adding arg $name: $value\n";
    } else {
        $value = trim(substr2($str, $start_pos, $end_pos));
        $args[] = $value;
        //echo "adding arg $value\n";
    }
}

// return true if $str starts with a MW function name
// (#alpha: or alpha:, where alpha is alphabetic).
//
function is_mw_function($str, $pos) {
    $n = strlen($str);
    while ($pos < $n) {
        $c = $str[$pos];
        if ($c == ':') return true;
        if ($c!='#' && !ctype_alpha($c)) return false;
        $pos++;
    }
    return false;
}

// given a string of the form
// {{tname | arg=val | arg=val ... }}
// or
// {{#if:arg|arg|arg}}
// or
// {{padleft:foo|bar}}
//
// return
//      - the template or function name
//      - an array of argument names and values.
//      - the offset of the position past the }}
// The values may contain template calls.
//
function parse_template_call($str, $start_pos) {
    global $mw_verbose;

    if ($mw_verbose) {
        echo sprintf("parse_template_call(): %s\n", str_head($str, $start_pos));
    }
    $i = $start_pos + 2;

    // skip blanks before template name
    while ($str[$i]==' ') {
        $i++;
    }
    // get the template name.  Check for Mediawiki functions first
    //
    if (is_mw_function($str, $i)) {
        $n = strpos($str, ':', $i);
        if ($n === false) {
            echo "bad mediawiki function: $str\n";
            exit;
        }
        $template_name = substr2($str, $i, $n+1);
        $i = $n+1;
    } else {
        $n = strpos($str, '|', $i);
        $m = strpos($str, '}}', $i);
        if (!$m) {
            echo "unterminated template call: $str\n";
            return null;
        }
        if ($n && $n<$m) {
            // ... | ... }}
            $template_name = trim(substr2($str, $i, $n));
            $i = $n+1;
        } else {
            // no args
            $template_name = trim(substr2($str, $i, $m));
            return [$template_name, [], $m+2];
        }
    }
    //echo "template name $template_name\n";

    // parse arguments
    //
    $args = [];
    while (true) {
        [$eq_pos, $arg_end_pos, $template_end_pos] = scan_arg( $str, $i);
        //echo "after scan template arg: i $i eq_pos $eq_pos arg_end_pos $arg_end_pos temp_end_pos $template_end_pos\n";
        if ($template_end_pos > 0) {
            add_arg($args, $str, $i, $eq_pos, $template_end_pos);
            break;
        } else if ($arg_end_pos > 0) {
            add_arg($args, $str, $i, $eq_pos, $arg_end_pos);
            $i = $arg_end_pos + 1;
        } else {
            echo sprintf("ERROR: malformed template call (no arg end): %s\n", substr($str, $start_pos));
            return null;
        }
    }
    return [$template_name, $args, $template_end_pos+2];
}

// parse an "item", which is either
// - a template call, in which case return [object, new_pos]
//      where object has name and args
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
    [$template_name, $args, $end_pos] = parse_template_call($str, $tstart);
    if ($template_name === null) {
        return null;
    }
    $x = new StdClass;
    $x->name = $template_name;
    $x->args = $args;
    return [$x, $end_pos];
}

?>
