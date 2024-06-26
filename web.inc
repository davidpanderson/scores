<?php

// web utility functions, not IMSLP-specific

ini_set('display_errors', 1);

require_once("bootstrap.inc");
require_once("audio.inc");

function page_head($title, $is_front=false) {
    echo sprintf('
<!DOCTYPE html>
<html lang="en">
<head>
<title>%s</title>
<meta charset="utf-8">
<link type="text/css" rel="stylesheet" href="bootstrap_darkly.min.css" media="all">
<link rel=stylesheet type="text/css" href="custom_dark.css">
</head>
<body >
',
    "$title"
    );
    audio_enable();
    echo '<div class="container-fluid">';

    echo sprintf(
        '<p><table width=100%%><tr><td><font size=+3>%s</font></td><td align=right>%s</a></td></tr></table>',
        $is_front?"IMSLP/DB home page (prototype)":'<a href=index.php>Return to home page</a>',
        ''
    );
    echo "<hr>";
    if (!$is_front) {
        echo "<h1>$title</h1>";
    }
}

function page_tail() {
    echo "</div></body></html>";
}

function error_page($msg) {
    page_head("Unable to handle request");
    echo $msg;
    page_tail();
    exit();
}

// ---------------- form stuff ------------

function get_int($name, $optional=false) {
    $x=null;
    if (isset($_GET[$name])) $x = $_GET[$name];
    if (!is_numeric($x)) {
        if ($optional) {
            if ($x) {
                error_page("bad value for $name");
            }
            return null;
        } else {
            error_page("missing $name");
        }
    }
    return (int)$x;
}

function get_str($key, $optional=false) {
    if (array_key_exists($key, $_GET)) {
        return $_GET[$key];
    }
    if ($optional) return null;
    error_page("missing arg: $key");
}

function post_str($key, $optional=false) {
    if (array_key_exists($key, $_POST)) {
        return $_POST[$key];
    }
    if ($optional) return null;
    error_page("missing arg: $key");
}

// Display lots of checkboxes divided into columns.
// Returns text for a table with $ncols columns.
// for each item in $items:
// - if it's an array, it's a list of checkbox items; divide it into columns
// - otherwise show it in a row that spans all cols
// Use this as arg to form_general();
//
function checkbox_table($items, $ncols) {
    $x = '<table width=100%>';
    foreach ($items as $item) {
        if (is_array($item)) {
            if (count($item)) {
                $x .= checkbox_table_array($item, $ncols);
            }
        } else {
            $x .= sprintf('<tr colspan=%d><td>%s</td></tr>', $ncols, $item);
        }
    }
    $x .= "</table>\n";
    return $x;
}

// Helper function for the above.
// Return '<tr><td>x</td> ... <td>x</td></tr>'
// where each table cell contains 1/ncols of the given checkbox items
//
function checkbox_table_array($items, $ncols) {
    $x = '<tr>';
    $y = split_list($items, $ncols);
    for ($i=0; $i<$ncols; $i++) {
        $x .= sprintf("<td valign=top style=\"padding: 4px\" width=\"%d%%\">%s</td>\n",
            intdiv(100, $ncols),
            implode('<br>', checkbox_item_strings($y[$i]))
        );
    }
    $x .= '</tr>';
    return $x;
}

// ---------------- table stuff ------------

// divide a list into $ncols parts
//
function split_list($items, $ncols) {
    $n = count($items);
    $x = [];
    for ($i=0; $i<$ncols; $i++) {
        $a = ceil($n*$i/$ncols);
        $b = ceil($n*($i+1)/$ncols);
        $x[] = array_slice($items, $a, ($b-$a));
    }
    return $x;
}

function show_items_cols($items, $ncols) {
    echo "<table width=100%><tr>\n";
    $cols = split_list($items, $ncols);
    foreach ($cols as $col) {
        echo sprintf(
            "<td valign=top style=\"padding: 4px\" width=\"%d%%\">%s</td>\n",
            intdiv(100, $ncols),
            implode('<br>', $col)
        );
    }
    echo "</tr></table>\n";
}

function start_table_str($class="", $style="") {
    $s = $style?'style="'.$style.'"':'';
    return '<div class="table">
      <table '.$s.' width="100%" class="table table-condensed '.$class.'" >
    ';
}

function start_table($class="table-striped", $style="") {
    echo start_table_str($class, $style);
}

function end_table_str() {
    return '</table>
        </div>
    ';
}

function end_table() {
    echo end_table_str();
}

function table_header() {
    echo "<tr>\n";
    $c = 'class="bg-primary"';
    for ($i = 0; $i < func_num_args(); $i++) {
        if (is_array(func_get_arg($i))) {
            $col = func_get_arg($i);
            echo "<th $c ".$col[1].">".$col[0]."</th>\n";
        } else {
            echo "<th $c>".func_get_arg($i)."</th>\n";
        }
    }
    echo "</tr>\n";
}

// Table row with unlimited number of columns

function table_row() {
    echo "<tr>\n";
    for ($i = 0; $i < func_num_args(); $i++) {
        if (is_array(func_get_arg($i))) {
            $col = func_get_arg($i);
            echo "<td ".$col[1].">".$col[0]."</td>\n";
        } else {
            echo "<td>".func_get_arg($i)."</td>\n";
        }
    }
    echo "</tr>\n";
}

function row1($x, $ncols=2, $class="heading") {
    if ($class == "heading") {
        echo "<tr><th class=\"bg-primary\" colspan=\"$ncols\">$x</th></tr>\n";
    } else {
        echo "<tr><td class=\"$class\" colspan=\"$ncols\">$x</td></tr>\n";
    }
}

define('NAME_ATTRS', 'class="text-right " style="padding-right:12px"');
define('VALUE_ATTRS', 'style="padding-left:12px"');
define('VALUE_ATTRS_ERR', 'class="danger" style="padding-left:12px"');

function row2($x, $y, $show_error=false, $lwidth='40%') {
    if ($x==="") $x="<br>";
    if ($y==="") $y="<br>";
    $attrs = $show_error?VALUE_ATTRS_ERR:VALUE_ATTRS;
    echo "<tr>
        <td width=\"$lwidth\" ".NAME_ATTRS.">$x</td>
        <td $attrs >$y</td>
        </tr>
    ";
}

function row2_init($x, $y, $lwidth='40%') {
    echo '<tr>
        <td class="text-right " width="'.$lwidth.'"  style="padding-right: 20px;">'.$x.'</td>
        <td '.VALUE_ATTRS.'>'.$y.'
    ';
}

function row2_plain($x, $y) {
    echo "<tr><td>$x</td><td>$y</td></tr>\n";
}

function rowify($string) {
    echo "<tr><td>$string</td></tr>";
}

function row_array($x) {
    echo "<tr>\n";
    foreach ($x as $h) {
        echo "<td>$h</td>\n";
    }
    echo "</tr>\n";
}

define ('ALIGN_RIGHT', 'style="text-align:right;"');

function row_heading_array($x, $attrs=null, $class='bg-primary') {
    echo "<tr>";
    $i = 0;
    foreach ($x as $h) {
        $a = $attrs?$attrs[$i]:"";
        echo "<th $a class=\"$class\">$h</th>";
        $i++;
    }
    echo "</tr>\n";
}

function row_heading($x, $class='bg-primary') {
    echo sprintf('<tr><th class="%s" colspan=99>%s</th></tr>
        ', $class, $x
    );
}

/////////////////// buttons ////////////////////

function button_text($url, $text, $desc=null, $class="btn-success btn-sm") {
    if (!$desc) {
        $desc = $text;
    }
    return sprintf(' <a href="%s" title="%s" class="btn %s">%s</a>',
        $url, $desc, $class, $text
    );
}

function show_button($url, $text, $desc=null, $class="btn-success btn-sm") {
    echo button_text($url, $text, $desc, $class);
}

// for places with a bunch of buttons, like forum posts
//
function show_button_small($url, $text, $desc=null) {
    echo button_text($url, $text, $desc, "btn-primary btn-xs");
}

//////////////// TEXT ////////////////////

// use the following around text with long lines,
// to limit the width and make it more readable.
//
function text_start($width=640) {
    echo sprintf("<div style=\"max-width: %dpx;\">\n", $width);
}
function text_end() {
    echo "</div>\n";
}

?>
