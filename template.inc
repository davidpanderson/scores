<?php

require_once('mediawiki.inc');

// Parse a MW link.
// The string is [[url|text]] or [[url]]
// where url is either a URL or a page name
// Return url, text, new_pos
//
function parse_link($str, $pos) {
    echo sprintf("parse_link(): %s...\n", str_head($str, $pos));
    $m = strpos($str, ']]', $pos);
    if ($m === false) {
        echo "no ]] in link\n";
        exit;
    }
    $n = strpos($str, '|', $pos);
    if ($n === false || $n>$m) {
        $ret = [
            substr2($str, $pos+2, $m),
            null,
            $m+2
        ];
    } else {
        $ret = [
            substr2($str, $n+1, $m),
            substr2($str, $pos+2, $n),
            $m+2
        ];
    }
    echo "parse_link() returns\n"; print_r($ret);
    return $ret;
}

// Expand some MW text, either a top-level page ($args == null)
// or a template (with given args).
// The text may contain:
//     template calls: {{foo|arg|arg}}
//     links: [[text|url]]
// and in the template case:
//     params: {{{...}}}
//     conditionals: {{#if: etc.
//
// expand these and return the resulting text
//
function expand_mw_text($str, $args=null) {
    $pos = 0;
    $out = '';
    $n = strlen($str);
    $in_template = ($args !== null);
    echo sprintf("expand_mw_text(): %s...\n", str_head($str, 0));
    if ($in_template) {
        echo "  in template\n";
    } else {
        echo "  not in template\n";
    }
    while ($pos < $n) {
        if ($in_template) {
            if (substr($str, $pos, 3) === '{{{') {
                [$x, $new_pos] = get_template_arg($str, $pos, $args);
                $pos = $new_pos;
                $out .= $x;
                continue;
            }
        }
        if (substr($str, $pos, 2) === '{{') {
            [$name, $inner_args, $new_pos] = parse_template_call($str, $pos);
            echo "expand_mw_text(): got template $name\n";
            if ($name == '#if') {
                $nargs = count($inner_args);
                if ($nargs < 2) {
                    echo "wrong # args for if\n";
                    print_r($inner_args);
                    exit;
                }
                $x = expand_mw_text($inner_args[0], $args);
                if ($x) {
                    echo "if is true\n";
                    $out .= expand_mw_text($inner_args[1], $args);
                } else {
                    echo "if is false\n";
                    if ($nargs>2) {
                        $out .= expand_mw_text($inner_args[2], $args);
                    }
                }
            } else if ($name == '#ifeq') {
                if (count($inner_args) != 4) {
                    echo "wrong # args for ifeq\n";
                    print_r($inner_args);
                    exit;
                }
                $x = expand_mw_text($inner_args[0], $args);
                $y = expand_mw_text($inner_args[1], $args);
                if ($x == $y) {
                    echo "if is true\n";
                    $out .= expand_mw_text($inner_args[2], $args);
                } else {
                    echo "if is false\n";
                    $out .= expand_mw_text($inner_args[3], $args);
                }
            } else if ($name == '#iflang') {
                if (array_key_exists('en', $args)) {
                    $out .= $args['en'];
                }
            } else {
                $inner_args = expand_args($inner_args, $args);
                $out .= expand_template($name, $inner_args);
            }
            $pos = $new_pos;
            continue;
        }
        if (substr($str, $pos, 2) === '[[') {
            [$url, $text, $new_pos] = parse_link($str, $pos);
            $url2 = expand_mw_text($url, $args);
            $text2 = expand_mw_text($text, $args);
            if ($text2) {
                $out .= "<a href=$url2>$text2</a>";
            } else {
                $out .= $url2;
            }
            $pos = $new_pos;
            continue;
        }
        $out .= substr($str, $pos, 1);
        $pos += 1;
    }
    echo "expand_mw_text(): returning $out\n";
    return $out;
}

// expand a template, given a set of arguments.
//
function expand_template($name, $args) {
    echo "expand_template(): $name\n";
    echo "args:\n"; print_r($args);
    static $templates = null;
    if (!$templates) {
        $templates = unserialize(file_get_contents('templates.ser'));
    }
    if (!array_key_exists($name, $templates)) {
        echo "no such template: $name\n";
        return '';
    }
    $str = $templates[$name];
    echo "template text:\n******$str\n******\n";
    return expand_mw_text($str, $args);
}

// $str/$pos starts with {{{
// parse until }}},
// and return
// [$val, $pos]
// where val the corresponding value from $args, or the default
//
function get_template_arg($str, $pos, $args) {
    echo sprintf("get_template_args(): %s...\n", str_head($str, $pos));
    $n = strpos($str, '}}}', $pos);
    if ($n === false) {
        echo "missing }}}\n";
        exit;
    }
    $x = substr2($str, $pos+3, $n);
    $parts = explode('|', $x);
    switch (count($parts)) {
    case 1:
        $key = $parts[0];
        // mediawiki numbers arguments starting from 1 (boo!!)
        if (is_numeric($key)) {
            $key = (int)$key - 1;
        }
        if (!array_key_exists($key, $args)) {
            // are missing args OK?
            echo "missing arg: $parts[0]\n";
            print_r($args);
            return ['', $n+3];
            //exit;
        }
        $ret = [$args[$key], $n+3];
        break;
    case 2:
        $key = $parts[0];
        if (is_numeric($key)) {
            $key = (int)$key - 1;
        }
        if (array_key_exists($key, $args)) {
            $ret = [$args[$key], $n+3];
        } else {
            $ret = [$parts[1], $n+3];
        }
        break;
    default:
        echo "bad arg: $x\n";
        print_r($parts);
        exit;
    }
    echo "get_template_args() returning\n"; print_r($ret);
    return $ret;
}

// expand inner_args in the context of outer_args
//
function expand_args($inner_args, $outer_args) {
    $a = [];
    echo "expand_args() input:\n"; print_r($inner_args);
    foreach ($inner_args as $key=>$str) {
        $a[$key] = expand_mw_text($str, $outer_args);
    }
    echo "expand_args() output:\n"; print_r($a);
    return $a;
}

if (1) {
    //$str = "[[foo|bar]]";
    //$str = "{{GriegKlavierwerke|foo|blah|x3|x4|x5}}";
    //$str = "{{P|a|b|c|d|e|f|g|h}}";
    $str = "{{LinkArr|Charles-Valentin|Alkan|1813|1888}}";
    $x = expand_mw_text($str);
    echo "output: $x\n";
}

?>
