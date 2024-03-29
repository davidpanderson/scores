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
    return implode('<br>', $x);
}

function person_role_str($role) {
    if ($role->person) {
        $person = DB_person::lookup_id($role->person);
        $s = "$person->first_name $person->last_name";
    } else {
        $ensemble = DB_ensemble::lookup_id($role->ensemble);
        $s = "$ensemble->name";
    }
    $s .= ': '.role_id_to_name($role->role);
    if ($role->instrument) {
        $s .= sprintf(' (%s)', instrument_id_to_name($role->instrument));
    }
    return $s;
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

function instrument_options($include_none=false) {
    $insts = get_instruments();
    $x = [];
    if ($include_none) {
        $x[] = [0, '---'];
    }
    foreach ($insts as $inst) {
        $x[] = [$inst->id, $inst->name];
    }
    return $x;
}

function venue_options() {
    $vs = DB_venue::enum();
    $x = [];
    $x[] = [0, '---'];
    foreach ($vs as $v) {
        $x[] = [$v->id, $v->name];
    }
    return $x;
}

function venue_str($id) {
    if (!$id) return '---';
    $v = DB_venue::lookup_id($id);
    return $v->name;
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

function role_options() {
    $rs = DB_role::enum();
    $x = [[0, '---']];
    foreach ($rs as $r) {
        $x[] = [$r->id, $r->name];
    }
    return $x;
}

function organization_type_options() {
    $types = DB_organization_type::enum();
    $x = [[0, '---']];
    foreach ($types as $type) {
        $x[] = [$type->id, $type->name];
    }
    return $x;
}

function organization_options() {
    $orgs = DB_organization::enum();
    $x = [[0, '---']];
    foreach ($orgs as $org) {
        $x[] = [$org->id, $org->name];
    }
    return $x;
}

function organization_type_str($id) {
    $ot = DB_organization_type::lookup_id($id);
    return $ot->name;
}

function item_code($id, $type) {
    switch ($type) {
    case 'composition': return "com$id";
    case 'person_role': return "prl$id";
    }
    return 'bad type';
}

function parse_code($code, $type) {
    $t = substr($code, 0, 3);
    $n = substr($code, 3);
    switch ($type) {
    case 'composition': if ($t != 'com') return 0; break;
    case 'person_role': if ($t != 'prl') return 0; break;
    }
    if (!is_numeric($n)) return 0;
    return (int)$n;
}

function copy_to_clipboard_script() {
echo "
<script>
function unsecuredCopyToClipboard(text) {
  const textArea = document.createElement('textarea');
  textArea.value = text;
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();
  try {
    document.execCommand('copy');
  } catch (err) {
    console.error('Unable to copy to clipboard', err);
  }
  document.body.removeChild(textArea);
}

function copy_to_clipboard(x) {
  //navigator.clipboard.writeText(x);
  // doesn't work for http://
  unsecuredCopyToClipboard(x);
  
  alert('Copied code ' + x);
}
</script>
";
}

function copy_button($text) {
    return sprintf("<button onclick=\"copy_to_clipboard('%s')\">Copy</button>",
        $text
    );
}

// return a string describing the composition,
// which could be an arrangement or a sub-composition
//
function composition_str($c) {
    if ($c->arrangement_of) {
        $c2 = DB_composition::lookup_id($c->arrangement_of);
        return "Arrangement of $c2->long_title";
    } else if ($c->parent) {
        $c2 = DB_composition::lookup_id($c->parent);
        return sprintf('Section (%s) of %s', $c->title, $c2->long_title);
    } else {
        return $c->long_title;
    }
}

// convert a program (list of performance IDs) to text
//
function program_str($prog) {
    $n = 1;
    $x = [];
    foreach ($prog as $pid) {
        $perf = DB_performance::lookup_id($pid);
        $x[] = performance_str($n++, $perf);
    }
    return implode('<p>', $x);
}

function performance_str($n, $perf) {
    $comp = DB_composition::lookup_id($perf->composition);
    $s = sprintf('<h4>%d) %s</h4>', $n, composition_str($comp));
    $roles = json_decode($perf->performers);
    foreach ($roles as $role_id) {
        $role = DB_person_role::lookup_id($role_id);
        $s .= sprintf("<li> %s\n", person_role_str($role));
    }
    return $s;
}

function organization_id_to_name($id) {
    if (!$id) return '---';
    $org = DB_organization::lookup_id($id);
    return $org->name;
}

?>
