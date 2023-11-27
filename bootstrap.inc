<?php

// An interface to bootstrap navbars, forms, and grids.

$fixed_navbar = false;

////////////// NAVBAR ////////////////

// call this to start the navbar.
// $brand: the text or image to show at left of navbar
// If text, put it in <a class="navbar-brand" ...
//
function navbar_start($brand, $fixed, $inverse) {
    global $fixed_navbar;
    $class = "navbar";
    if ($inverse) {
        $class .= " navbar-inverse";
    } else {
        $class .= " navbar-default";
    }
    if ($fixed) {
        $class .= " navbar-fixed-top";
        $fixed_navbar = true;
    }
    echo "<nav class=\"$class\">\n";
    echo '
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      '.$brand.'
    </div>
    <div class="collapse navbar-collapse" id="myNavbar">
      <ul class="nav navbar-nav">
    ';
}

// call this to end it
//
function navbar_end() {
    echo '
      </ul>
    </div>
  </div>
</nav>
    ';
}

// put the login/logout stuff at the right side of navbar
//
function navbar_right($user) {
    global $is_login_page;
    echo '
      </ul>
      <ul class="nav navbar-nav navbar-right">
    ';
    if (!$is_login_page) {
        if ($user) {
            echo sprintf('
                <li><a href=%s%s>%s</a></li>
                ', url_base(), USER_HOME, $user->name
            );
            $url_tokens = url_tokens($user->authenticator);
            echo sprintf('<li><a href="%slogout.php?%s">Log out</a></li>',
                url_base(), $url_tokens
            );
        } else {
            echo sprintf('
                <li><a href="%ssignup.php">%s</a></li>
                <li><a href="%slogin_form.php">%s</a></li>
                ', url_base(),
                tra("Join"),
                url_base(),
                tra("Login")
            );
        }
    }
}

// add a dropdown menu
//
function navbar_menu($name, $items) {
    echo '
      <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">'.$name.'
        <span class="caret"></span></a>
        <ul class="dropdown-menu">
    ';
    foreach ($items as $item) {
        if (is_array($item)) {
            echo '<li><a href="'.$item[1].'">'.$item[0].'</a></li>
            ';
        } else {
            echo '<li class="dropdown-header">'.$item.'</li>
            ';
        }
    }
    echo '
        </ul>
      </li>
    ';
}

// add a single item (not menu)
//
function navbar_item($name, $url) {
    echo '<li><a href="'.$url.'">'.$name.'</a></li>
    ';
}

// output a panel.
// $content_func is a function that generates the panel contents
//
function panel($title, $content_func, $class="panel-primary", $body_class="") {
    echo sprintf('<div class="panel %s">
        ', $class
    );
    if ($title) {
        echo '
            <div class="panel-heading">
                <h1 class="panel-title">'.$title.'</h1>
            </div>
        ';
    }
    echo sprintf('<div class="panel-body %s">
        ', $body_class
    );
    $content_func();
    echo '
        </div>
        </div>
    ';
}

// grid layout with a full-width row followed by two equal columns
// $top_func, $left_func, and $right_func
// are functions that generate the top, left, and right content
// $left_width is the width of left column in 1/12 units.
//
function grid($top_func, $left_func, $right_func, $left_width=6) {
    echo '
        <div class="container-fluid">
    ';
    if ($top_func) {
        echo '
            <div class="row">
            <div class="col-sm-12">
        ';
        $top_func();
        echo '
            </div>
            </div>
        ';
    }
    $right_width = 12-$left_width;
    echo '
        <div class="row">
        <div class="col-sm-'.$left_width.'">
    ';
    $left_func();
    echo '
        </div>
        <div class="col-sm-'.$right_width.'">
    ';
    $right_func();
    echo '
        </div>
        </div>
        </div>
    ';
}

// to upload files:
//  use method = POST and extra=ENCTYPE="multipart/form-data"
// to have initial focus on input field foo:
//      use extra = "name=x"
//      call forum_focus(x, foo) after defining the field
//
function form_start($action, $method='get', $extra='') {
    echo sprintf(
        '<div class="container-fluid">
        <form class="form-horizontal" method="%s" action="%s" %s>'
        ,
        $method, $action, $extra
    );
}

function form_input_hidden($name, $value) {
    echo '<input type="hidden" name="'.$name.'" value="'.$value.'">
    ';
}

function form_focus($form_name, $field_name) {
    echo "<script>document.$form_name.$field_name.focus()</script>\n";

}

function form_end() {
    echo '</form>
        </div>
    ';
}

define('FORM_LEFT_CLASS', 'col-sm-3');
define('FORM_LEFT_OFFSET', 'col-sm-offset-3');
define('FORM_RIGHT_CLASS', 'col-sm-9');

// just the input field
//
function form_input_text_field(
    $name, $value='', $type='text', $attrs='', $extra=''
) {
    return sprintf(
        '<input %s type="%s" class="form-control" name="%s" value="%s">%s',
        $attrs, $type, $name, $value, $extra
    );
}

// the whole row
//
function form_input_text(
    $label, $name, $value='', $type='text', $attrs='', $extra=''
) {
    echo sprintf('
        <div class="form-group">
            <label align=right class="%s">%s</label>
            <div class="%s">
                %s
            </div>
        </div>
        ',
        FORM_LEFT_CLASS, $label, FORM_RIGHT_CLASS,
        form_input_text_field($name, $value, $type, $attrs, $extra)
    );
}

// display name/value with same formatting as form
//
function form_attr($name, $value) {
    echo sprintf('
        <div class="form-group">
            <div class="%s text-right">%s</div>
            <div class="%s">%s</div>
        </div>
        ',
        FORM_LEFT_CLASS, $name, FORM_RIGHT_CLASS, $value
    );
}

function form_input_textarea($label, $name, $value='', $nrows=4) {
    echo sprintf('
        <div class="form-group">
            <label align=right class="%s" for="%s">%s</label>
            <div class="%s">
                <textarea rows="%d" class="form-control" id="%s" name="%s">%s</textarea>
            </div>
        </div>
        ',
        FORM_LEFT_CLASS, $name, $label, FORM_RIGHT_CLASS,
        $nrows, $name, $name, $value
    );
}

// $items is either a string of <option> elements, or an array
//
function form_select($label, $name, $items) {
    echo sprintf('
        <div class="form-group">
            <label align=right class="%s" for="%s">%s</label>
            <div class="%s">
                <select class="form-control" id="%s" name="%s">
        ',
        FORM_LEFT_CLASS, $name, $label, FORM_RIGHT_CLASS, $name, $name
    );
    if (is_array($items)) {
        foreach ($items as $i) {
            echo '<option value="'.$i[0].'">'.$i[1].'</option>
            ';
        }
    } else {
        echo $items;
    }
    echo "</select></div></div>\n";
}

// same, for multiple select.
// flags, if non-null, says which ones are selected
//
function form_select_multiple($label, $name, $items, $flags) {
    echo sprintf('
        <div class="form-group">
            <label align=right class="%s" for="%s">%s</label>
            <div class="%s">
                <select multiple class="form-control" id="%s" name="%s[]">
        ',
        FORM_LEFT_CLASS, $name, $label, FORM_RIGHT_CLASS, $name, $name
    );
    $n = 0;
    foreach ($items as $i) {
        $s = ($flags && $flags[$n])?'selected':'';
        echo '<option '.$s.' value="'.$i[0].'">'.$i[1].'</option>
        ';
        $n++;
    }
    echo "</select></div></div>\n";
}

// return a list of string for checkbox items
//
function checkbox_item_strings($items, $attrs='') {
    $x = [];
    foreach ($items as $i) {
        $x[] = sprintf('<input %s type="checkbox" name="%s" %s> %s
            ',
            $attrs, $i[0], $i[2]?"checked":"", $i[1]
        );
    }
    return $x;
}

// $items is list of (name, label, checked)
//
function form_checkboxes($label, $items, $attrs='') {
    echo sprintf('
        <div class="form-group">
            <label align=right class="%s">%s</label>
            <div class="%s">
        ',
        FORM_LEFT_CLASS, $label, FORM_RIGHT_CLASS
    );
    $x = checkbox_item_strings($items, $attrs);
    echo implode('<br>', $x);
    echo '</div>
        </div>
    ';
}

// $items is list of (value, label)
//
function form_radio_buttons($label, $name, $items, $selected) {
    echo sprintf('
        <div class="form-group">
            <label align=right class="%s">%s</label>
            <div class="%s">
        ',
        FORM_LEFT_CLASS, $label, FORM_RIGHT_CLASS
    );
    foreach ($items as $i) {
        $checked = ($selected == $i[0])?"checked":"";
        echo sprintf('<input type="radio" name="%s" value="%s" %s> %s <br>
            ',
            $name, $i[0], $checked, $i[1]
        );
    }
    echo '</div>
        </div>
    ';
}

function form_general($label, $item) {
    echo '
        <div class="form-group">
    ';
    if (strlen($label)) {
        echo sprintf(
'           <label align=right class="%s">%s</label>
            <div class="%s">%s</div>
        ',
            FORM_LEFT_CLASS, $label, FORM_RIGHT_CLASS, $item
        );
    } else {
        echo sprintf(
'           <div class="%s %s">%s</div>
        ',
            FORM_LEFT_OFFSET, FORM_RIGHT_CLASS, $item
        );
    }
    echo '</div>
';
}

function form_submit($text, $attrs='') {
    form_general(
        "",
        sprintf('<button %s type="submit" class="btn btn-success">%s</button>',
            $attrs, $text
        )
    );
}

function form_checkbox($label, $name, $checked=false) {
    echo sprintf('
        <div class="form-group">
            <input type="checkbox" name="%s" %s> &nbsp; <span class="lead">%s</span>
        </div>
        ', $name, $checked?"checked":"", $label
    );
}

?>
