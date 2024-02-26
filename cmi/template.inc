<?php

require_once('mediawiki.inc');

// Expand some MW text, either a top-level page ($args == null)
// or a template (with given args).
// The text may contain:
//      template calls: {{foo|arg|arg}}
//      links: [url text]
//      mediawiki markup
//          we handle only lists and tables
// and in the template case:
//     params: {{{...}}}
//     conditionals: {{#if: etc.
//
// expand these and return the resulting text

$verbose = false;

function expand_mw_text($str, $args=null, $level=0, $is_doc = false) {
    global $verbose;

    $pos = 0;
    $out = '';
    $len = strlen($str);
    $in_template = ($args !== null);
    if ($verbose) {
        indent(
            sprintf(
                "expand_mw_text(): '%s' (level %d, %sin template)\n",
                str_head($str, 0),
                $level,
                $in_template?'':'not '
            ),
            $level
        );
        if ($in_template) {
            indent("args:\n".print_r($args, true), $level+1);
        }
    }
    while ($pos < $len) {
        //echo "pos $pos len $len\n";
        if (substr($str, $pos, 8)=='<nowiki>') {
            $n = strpos($str, '</nowiki>', $pos);
            if (!$n) {
                echo "no closing </nowiki>\n";
                return $out;
            }
            $out .= substr2($str, $pos+8, $n);
            $pos = $n+9;
            continue;
        }
        if ($in_template) {
            if (substr($str, $pos, 3) === '{{{') {
                [$x, $new_pos] = get_template_arg($str, $pos, $args, $level+1);
                $pos = $new_pos;
                $out .= $x;
                continue;
            }
        }
        if (substr($str, $pos, 2) === '{{') {
            [$name, $inner_args, $new_pos] = parse_template_call($str, $pos);
            if ($verbose) {
                indent("got template '$name'\n", $level+1);
            }

            // for #if*: functions, args of the form 'a=b' should
            // be treated as that string, not a=>b
            //
            if ($name=='#if:' || $name=='#ifeq:') {
                $inner_args = process_if_args($inner_args);
            }

            if ($name == '#if:') {
                $nargs = count($inner_args);
                if (count($inner_args) < 2) {
                    echo "wrong # args for #if\n";
                    print_r($inner_args);
                    exit;
                }
                $x = expand_mw_text($inner_args[0], $args, $level+1);
                if ($x) {
                    if ($verbose) {
                        indent("#if: condition is true\n", $level+1);
                    }
                    $out .= expand_mw_text($inner_args[1], $args, $level+1);
                } else {
                    if ($verbose) {
                        indent("#if: condition is false\n", $level+1);
                    }
                    if ($nargs>2) {
                        $out .= expand_mw_text($inner_args[2], $args, $level+1);
                    }
                }
            } else if ($name == '#ifeq:') {
                if (count($inner_args) != 4) {
                    echo "wrong # args for ifeq\n";
                    print_r($inner_args);
                    exit;
                }
                $x = expand_mw_text($inner_args[0], $args, $level+1);
                $y = expand_mw_text($inner_args[1], $args, $level+1);
                if ($x == $y) {
                    if ($verbose) {
                        indent("#ifeq conditon is true\n", $level+1);
                    }
                    $out .= expand_mw_text($inner_args[2], $args, $level+1);
                } else {
                    if ($verbose) {
                        indent("#ifeq condition is false\n", $level+1);
                    }
                    $out .= expand_mw_text($inner_args[3], $args, $level+1);
                }
            } else if ($name == '#switch:') {
                $x = expand_mw_text($inner_args[0], $args, $level+1);
                $found = false;
                foreach ($inner_args as $key=>$arg) {
                    if ($key == 0) continue;
                    if (!is_string($key)) {
                        $out .= expand_mw_text($arg, $args, $level+1);
                        $found = true;
                        break;
                    }
                    if ($key==$x || $key=='#default') {
                        $out .= expand_mw_text($arg, $args, $level+1);
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    echo "ERROR: no default in switch\n";
                }
            } else if ($name == '#iflang:') {
                if (array_key_exists('en', $inner_args)) {
                    $out .= expand_mw_text($inner_args['en'], $args, $level+1);
                }
            } else if ($name == '#ifexist:') {
                $out .= '(#ifexist:) ';
                $out .= expand_mw_text($inner_args[1], $args, $level+1);
            } else if ($name == '#smwlink:') {
                $out .= '(#smwlink:) ';
                foreach ($inner_args as $arg) {
                    $out .= ' '.expand_mw_text($arg, $args, $level+1).' ';
                }
            } else if ($name == 'padleft:') {
                $n = (int)$inner_args[1];
                if (count($inner_args)>2) {
                    $c = $inner_args[2];
                } else {
                    $x = '0';
                }
                for ($i=0; $i<$n; $i++) {
                    $out .= $c;
                }
                $out .= expand_mw_text($inner_args[0], $args, $level+1);
            } else if ($name == '#fte:') {
                $out .= '#fte: (';
                foreach ($inner_args as $key=>$arg) {
                    if ($key === 0) {
                        $out .= ' '.expand_mw_text($arg, $args, $level+1).' ';
                    } else {
                        $out .= "$key: ".expand_mw_text($arg, $args, $level+1);
                        $out .= ' ';
                    }
                }
                $out .= ')';
            } else if ($name == '#worksortkey:') {
                $out .= "$name (";
                foreach ($inner_args as $key=>$arg) {
                    $out .= "$key: ".expand_mw_text($arg, $args, $level+1);
                }
                $out .= ')';
            } else if ($name == 'CURRENTYEAR') {
                $out .= date('Y');
            } else if ($name == '#expr:') {
                $x = expand_mw_text($inner_args[0], $args, $level+1);
                $y = explode('-', $x);
                if (count($y) == 2) {
                    $out .= ((int)$y[0])-((int)$y[1]);
                } else {
                    $out .= $x;
                }
            } else {
                $inner_args = expand_args($inner_args, $args, $level+1);
                $out .= expand_template($name, $inner_args, $level+1);
            }
            $pos = $new_pos;
            continue;
        } else if (substr($str, $pos, 2) === '[[') {
            // internal link - just show text
            $n = strpos_skip_nowiki($str, ']]', $pos);
            if ($n === false) {
                echo "Missing ]]\n";
                break;
            } else {
                $out .= ' [[';
                $x = substr2($str, $pos+2, $n);
                [$eq_pos, $arg_end_pos, $tmp_end_pos] = scan_arg($x, 0);
                if ($arg_end_pos<0) {
                    $out .= expand_mw_text($x, $args, $level+1);
                } else {
                    $y = substr($x, $arg_end_pos+1);
                    $out .= expand_mw_text($y, $args, $level+1);
                }
                $out .= ']] ';
                $pos = $n+2;
            }
            continue;
        } else if (substr($str, $pos, 1) === '[') {
            // the closing ] may not be the first ];
            // there might be '<nowiki>]</nowiki>' first.
            // Boo!!!!!
            //
            $n = strpos_skip_nowiki($str, ']', $pos);
            if (!$n) {
                echo "no ] in external link: ".str_head($str, $pos)."\n";
                exit;
            }
            $x = substr2($str, $pos+1, $n);
            $x = expand_mw_text($x, $args, $level+1);
            [$url, $text] = parse_external_link($x, $level+1);
            if ($text) {
                $out .= sprintf("<a href=%s>%s</a>",
                    $url,
                    expand_mw_text($text, $args, $level+1)
                );
            } else {
                $out .= $url;
            }
            $pos = $n+1;
            continue;
        } else if ($is_doc && (!$pos || $str[$pos-1]=="\n")) {
            if ($verbose) {
                indent("start_of_line(): '".str_head($str, $pos)."'\n", $level+1);
            }
            [$out2, $new_pos] = start_of_line($str, $pos, $args, $level+1);
            if ($verbose) {
                indent("start_of_line() returns '$out2'\n", $level+1);
            }
            if ($out2) $out .= $out2;
            if ($new_pos > $pos) {
                $pos = $new_pos;
                continue;
            }
        }
        $out .= $str[$pos++];
    }
    if ($verbose) {
        indent("expand_mw_text(): returning '$out'\n", $level);
    }
    return $out;
}

// Parse a MW external link.
// The string is 'url text' or 'url'
// Return url, text, new_pos
//
function parse_external_link($str, $level) {
    global $verbose;
    if ($verbose) {
        indent(sprintf("parse_external_link(): '%s'\n", str_head($str, 0)), $level);
    }
    $n = strpos($str, ' ');
    if ($n === false) {
        $ret = [$str, null];
    } else {
        $ret = [
            substr2($str, 0, $n),
            substr($str, $n+1)
        ];
    }
    if ($verbose) {
        indent("parse_external_link() returns\n", $level);
        indent("URL: ".$ret[0]."\n", $level+1);
        indent("text: ".$ret[1]."\n", $level+1);
    }
    return $ret;
}

// expand a template, given a set of arguments.
//
function expand_template($name, $args, $level) {
    global $verbose;
    if ($verbose) {
        indent("expand_template(): '$name'\n", $level);
    }

    static $templates = null;
    if (!$templates) {
        $templates = unserialize(file_get_contents('data/templates.ser'));
    }
    $name = strtolower($name);
    if (!array_key_exists($name, $templates)) {
        echo "no such template: $name\n";
        return '';
    }
    $str = $templates[$name];
    if ($verbose) {
        indent("template text:\n", $level+1);
        indent("$str\n\n", $level+2);
    }
    $ret = expand_mw_text($str, $args, $level+1, true);
    if ($verbose) {
        indent("expand_template() returns $ret\n", $level);
    }
    return $ret;
}

// $str/$pos starts with {{{
// parse until }}},
// and return
// [$val, $pos]
// where $val is the corresponding value from $args, or the default
// and $pos is the string position after }}}
//
function get_template_arg($str, $pos, $args, $level) {
    global $verbose;
    if ($verbose) {
        indent(
            sprintf("get_template_arg(): '%s'\n", str_head($str, $pos)),
            $level
        );
    }
    $n = close_bracket_pos($str, $pos+3);
    if ($n === false || substr($str, $n, 3) != '}}}') {
        echo "missing }}} $n\n";
        exit;
    }
    $x = substr2($str, $pos+3, $n);
    $pipe_pos = strpos($x, '|');
    if ($pipe_pos === false) {
        $key = $x;
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
    } else {
        $key = substr2($x, 0, $pipe_pos);
        if (is_numeric($key)) {
            $key = (int)$key - 1;
        }
        if (array_key_exists($key, $args)) {
            $ret = [$args[$key], $n+3];
        } else {
            $ret = [substr($x, $pipe_pos+1), $n+3];
        }
    }
    if ($verbose) {
        indent(
            sprintf("get_template_arg() returning '%s' (%s)\n",
                $ret[0], str_head($str, $ret[1])
            ),
            $level
        );
    }
    return $ret;
}

// expand inner_args in the context of outer_args
//
function expand_args($inner_args, $outer_args, $level) {
    global $verbose;
    $a = [];
    if ($verbose) {
        indent("arguments:\n", $level);
        indent(print_r($inner_args, true), $level+1);
    }
    foreach ($inner_args as $key=>$str) {
        if ($str) {
            $a[$key] = expand_mw_text($str, $outer_args, $level+1);
        } else {
            $a[$key] = '';
        }
    }
    if ($verbose){
        indent("expanded arguments:\n", $level);
        indent(print_r($a, true), $level+1);
    }
    return $a;
}

// show text with given level of indentation (for debugging)
//
function indent($text, $level) {
    $lines = explode("\n", $text);
    foreach ($lines as $line) {
        if (!$line) continue;
        for ($i=0; $i<$level; $i++) echo '    ';
        echo "$line\n";
    }
}

// process MW start-of-line features:
// currently tables and lists.
// return [out, new_pos]
//
function start_of_line($str, $pos, $args, $level) {
    global $verbose;

    // We need to keep track of nesting level (for lists)
    // and whether we already generated <tr> (for tables)
    // But this info is particular to a recursion level;
    // keep it in arrays indexed by recursion level
    //
    static $ulist_level = [];
    static $in_row = [];
    static $last_level=-1;

    if ($level > $last_level || !array_key_exists($level, $in_row)) {
        $ulist_level[$level] = 0;
        $in_row[$level] = 0;
    }
    $last_level = $level;

    $out = '';
    $eol_pos = strpos($str, "\n", $pos);

    // unordered lists
    //
    $new_ulist_level = 0;
    if (substr($str, $pos, 2) == '* ') {
        $new_ulist_level = 1;
    } else if (substr($str, $pos, 3) == '** ') {
        $new_ulist_level = 2;
    } else if (substr($str, $pos, 4) == '*** ') {
        $new_ulist_level = 3;
    }
    while ($new_ulist_level < $ulist_level[$level]) {
        $out .= "</ul>\n";
        $ulist_level[$level]--;
    }
    while ($new_ulist_level > $ulist_level[$level]) {
        $out .= "<ul>\n";
        $ulist_level[$level]++;
    }
    $ulist_level[$level] = $new_ulist_level;
    if ($ulist_level[$level] > 0) {
        $out .= "<li> ";
        $p2 = $pos+$new_ulist_level+1;
        $out .= expand_mw_text(
            substr2($str, $p2, $eol_pos+1),
            $args, $level+1
        );
        $line_start = true;
        return [$out, $eol_pos+1];
    }

    // tables
    //
    if (substr($str, $pos, 2) == '{|') {
        $x = substr2($str, $pos+2, $eol_pos);
        $out .= "<table $x>\n";
        $pos = $eol_pos+1;
        $in_row[$level] = false;
        return [$out, $eol_pos+1];
    } else if (substr($str, $pos, 2) == '|}') {
        if ($in_row[$level]) $out .= "</tr>\n";
        $out .= "</table>\n";
        return [$out, $eol_pos+1];
    } else if (substr($str, $pos, 2) == '|-') {
        if ($in_row[$level]) $out .= "</tr>\n";
        $x = substr2($str, $pos+2, $eol_pos);
        $out .= "<tr $x>\n";
        $in_row[$level] = true;
        return [$out, $eol_pos+1];
    } else if (substr($str, $pos, 1) == '|') {
        if (!$in_row[$level]) {
            $out .= "<tr>\n";
            $in_row[$level] = true;
        }
        $x = substr2($str, $pos+1, $eol_pos);
        $x = explode('||', $x);
        foreach ($x as $y) {
            $z = expand_mw_text($y, $args, $level+1);
            $out .= "<td>$z</td>\n";
        }
        return [$out, $eol_pos+1];
    }
    return [$out, $pos];
}

// an #if call can look like
// {{#if {{{1}}}|https://foo?id={{{2}}} }}
// The '=' in the argument causes it to be parsed as name=>val.
// Undo this.
//
function process_if_args($args) {
    $new_args = [];
    foreach ($args as $key=>$val) {
        if (is_string($key)) {
            $new_args[]= "$key=$val";
        } else {
            $new_args[] = $val;
        }
    }
    return $new_args;
}

// like strpos() but skips <nowiki>...</nowiki>
//
function strpos_skip_nowiki($str, $x, $pos) {
    $n = strlen($str);
    $xlen = strlen($x);
    while ($pos < $n) {
        if (substr($str, $pos, $xlen) == $x) return $pos;
        if (substr($str, $pos, 8) == '<nowiki>') {
            $m = strpos($str, '</nowiki>', $pos);
            if (!$m) {
                echo "no matching </nowiki>\n";
                exit;
            }
            $pos += 9;
        } else {
            $pos++;
        }
    }
    return false;
}

// return the position of the first },
// skipping all matched {}
//
function close_bracket_pos($str, $pos) {
    $i = $pos;
    $nest = 0;
    $len = strlen($str);
    while ($i<$len) {
        if ($str[$i] == '{') {
            $nest++;
        } else if ($str[$i] == '}') {
            if (!$nest) return $i;
            $nest--;
        }
        $i++;
    }
    return false;
}

if (0) {
    $str = "
foobar
* [[blah|fyfyf]]
** xxxx
**  fufuf
* foo
*** fjfjf
*** fjfjf
blah
";
    expand_mw_text($str);
}

if (0) {
    $str = "
{| class=\"foo\"
| foo || xxx
| bar || xxx
|-
| fff || xxx
| fjfj || xxx
|}
";
    expand_mw_text($str);
}

?>
