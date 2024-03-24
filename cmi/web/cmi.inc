<?php

// utility functions for CMI web pages

display_errors();

require_once('cmi_db.inc');
require_once('ser.inc');

define('DEBUG_QUERY', 0);

// is logged-in user an editor?
//
function editor() {
    return true;
}

function select2_head($title) {
    $head_extra = '
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    ';
    page_head($title, null, false, '', $head_extra);
    echo "<script>
        $(document).ready(function() {
            $('.js-example-basic-multiple').select2();
        });
        </script>
    ";
}

function select2_multi($label, $name, $items, $selected=null, $extra='') {
    echo sprintf('
        <div class="form-group">
            <label align=right class="%s" for="%s">%s</label>
            <div class="%s">
                <select class="js-example-basic-multiple" name="%s[]" multiple="multiple" style="width: 100%%" %s>
        ',
        FORM_LEFT_CLASS, $name, $label, FORM_RIGHT_CLASS, $name, $extra
    );
    foreach ($items as $i) {
        echo sprintf(
            '<option %s value=%s>%s</option>',
            ($selected && in_array($i[0], $selected))?'selected':'',
            $i[0], $i[1]
        );
    }
    echo "</select></div></div>\n";
}

function locations_str($locs) {
    if (!$locs) return '';
    $locs = json_decode($locs);
    $x = [];
    foreach ($locs as $id) {
        $x[] = location_id_to_name($id);
    }
    return implode(', ', $x);
}

function comp_types_str($cts) {
    if (!$cts) return '';
    $cts = json_decode($cts);
    $x = [];
    foreach ($cts as $id) {
        $x[] = comp_type_id_to_name($id);
    }
    return implode(', ', $x);
}

function creators_str($cts, $show_role) {
    if (!$cts) return '---';
    $cts = json_decode($cts);
    $x = [];
    foreach ($cts as $id) {
        $prole = DB_person_role::lookup_id($id);
        $p = DB_person::lookup_id($prole->person);
        $x[] = sprintf(
            "<a href=item.php?type=person&id=%d>%s %s</a>%s",
            $p->id,
            $p->first_name, $p->last_name,
            $show_role?' ('.role_id_to_name($prole->role).')':''
        );
    }
    return implode(', ', $x);
}

function pluralize($x) {
    if (substr($x, -2) == 'ss') return $x.'es';
    if (substr($x, -1) == 's') return $x;
    return $x.'s';
}

function instrument_combo_str($id) {
    $ic = DB_instrument_combo::lookup_id($id);
    $is = json_decode($ic->instruments);
    $x = [];
    for ($i=0; $i<count($is->count); $i++) {
        if ($is->count[$i] == 1) {
            $x[] = instrument_id_to_name($is->id[$i]);
        } else {
            $x[] = sprintf('%d %s',
                $is->count[$i],
                pluralize(instrument_id_to_name($is->id[$i]))
            );
        }
    }
    return implode(' + ', $x);
}

function instrument_combos_str($ics) {
    if (!$ics) return '';
    $ics = json_decode($ics);
    $x = [];
    foreach ($ics as $id) {
        $x[] = instrument_combo_str($id);
    }
    return implode('; ', $x);
}

function sex_options() {
    $sexes = get_sexes();
    $x = [[0, '---']];
    foreach ($sexes as $s) {
        $x[] = [$s->id, $s->name];
    }
    return $x;
}
function ethnicity_options() {
    $ethnicities = get_ethnicities();
    $x = [[0, '---']];
    foreach ($ethnicities as $s) {
        $x[] = [$s->id, $s->name];
    }
    return $x;
}

function country_options() {
    $cid = location_type_name_to_id('country');
    $locs = get_locations();
    $x = [[0, '---']];
    foreach ($locs as $loc) {
        if ($loc->type != $cid) continue;
        $x[] = [$loc->id, $loc->name];
    }
    return $x;
}

function instrument_options() {
    $insts = get_instruments();
    $x = [];
    foreach ($insts as $inst) {
        $x[] = [$inst->id, $inst->name];
    }
    return $x;
}

function languages_str($langs) {
    $x = [];
    foreach ($langs as $lang) {
        $x[] = language_id_to_name($lang);
    }
    return implode(', ', $x);
}

function location_type_options() {
    $lts = DB_location_type::enum('', 'order by name');
    $x = [[0, '---']];
    foreach ($lts as $lt) {
        $x[] = [$lt->id, $lt->name];
    }
    return $x;
}

function location_name($loc) {
    if ($loc->parent) {
        return sprintf('%s (%s in %s)',
            $loc->name, location_type_id_to_name($loc->type),
            location_id_to_name($loc->parent)
        );
    } else {
        return sprintf('%s (%s)',
            $loc->name, location_type_id_to_name($loc->type)
        );
    }
}
function location_options() {
    $lts = DB_location::enum('', 'order by name');
    $x = [[0, '---']];
    foreach ($lts as $lt) {
        $x[] = [$lt->id, location_name($lt)];
    }
    return $x;
}

?>
