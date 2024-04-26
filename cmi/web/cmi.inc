<?php

// utility functions for CMI web pages

display_errors();

require_once('cmi_db.inc');
require_once('ser.inc');

//DB::$show_queries = true;

define('SHOW_COMP_QUERY', 0);       // show comp search queries

function get_int_array($name) {
    return array_map('intval', get_array($name));
}

// if arg is blank, return em dash
//
function dash($s=null) {
    return $s?$s:'&mdash;';
}

// if arg is falsey, return '', else arg
//
function blank($s) {
    return $s?$s:'';
}


// is logged-in user an editor?
//
function editor() {
    return true;
}

// json decode, but return [] if null
function json_decode2($s) {
    return $s?json_decode($s):[];
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
    if (!$cts) return '';
    $cts = json_decode($cts);
    $x = [];
    foreach ($cts as $id) {
        $prole = DB_person_role::lookup_id($id);
        $p = DB_person::lookup_id($prole->person);
        $s = sprintf(
            "<a href=item.php?type=%d&id=%d>%s</a>",
            PERSON,
            $p->id,
            person_id_to_name($prole->person)
        );
        if ($show_role) {
            $s .= sprintf(' (%s)', role_str($prole));
        }
        $x[] = $s;
    }
    return implode('<br>', $x);
}

// return 'first last'
//
function person_id_to_name($id) {
    $person = DB_person::lookup_id($id);
    return "$person->first_name $person->last_name";
}

// if performer, return instrument name if present, else 'performer'
// else return role name
//
function role_str($prole) {
    if ($prole->instrument) {
        return instrument_id_to_name($prole->instrument);
    }
    return role_id_to_name($prole->role);
}

// return e.g. Joe Smith (piano) or Joe Smith (lyricist)
//
function person_role_str($prole, $show_role) {
    if (!$show_role) return $s;
    return "$s ($r)";
}

// same, but return name and role separately
//
function person_role_array($role) {
    if ($role->person) {
        $person = DB_person::lookup_id($role->person);
        $s = "$person->first_name $person->last_name";
    } else {
        $ensemble = DB_ensemble::lookup_id($role->ensemble);
        $s = "$ensemble->name";
    }
    $t = role_id_to_name($role->role);
    if ($role->instrument) {
        $t .= sprintf(' (%s)', instrument_id_to_name($role->instrument));
    }
    return [$s, $t];
}

function pluralize($x) {
    if (substr($x, -2) == 'ss') return $x.'es';
    if (substr($x, -1) == 's') return $x;
    return $x.'s';
}

function instrument_combo_str($ic) {
    $is = json_decode($ic->instruments);
    $x = [];
    $n= count($is->count);
    if (!$n) return '';
    for ($i=0; $i<$n; $i++) {
        if ($is->count[$i] == 1) {
            $x[] = instrument_id_to_name($is->id[$i]);
        } else {
            $x[] = sprintf('%d %s',
                $is->count[$i],
                pluralize(instrument_id_to_name($is->id[$i]))
            );
        }
    }
    $s = $x[0];
    for ($i=1; $i<$n-1; $i++) {
        $s .= ', '.$x[$i];
    }
    if ($n>1) {
        $s .= ' and '.$x[$n-1];
    }
    return $s;
}

function instrument_combos_str($ics) {
    if (!$ics) return '';
    $ics = json_decode($ics);
    $x = [];
    foreach ($ics as $id) {
        $ic = DB_instrument_combo::lookup_id($id);
        $x[] = instrument_combo_str($ic);
    }
    return implode('<br>', $x);
}

function sex_options() {
    $sexes = get_sexes();
    $x = [[0, dash(null)]];
    foreach ($sexes as $s) {
        $x[] = [$s->id, $s->name];
    }
    return $x;
}
function ethnicity_options() {
    $ethnicities = get_ethnicities();
    $x = [[0, dash(null)]];
    foreach ($ethnicities as $s) {
        $x[] = [$s->id, $s->name];
    }
    return $x;
}

function ethnicity_str($eths) {
    if (empty($eths)) return dash();
    $ethnicities = get_ethnicities();
    $x = [];
    foreach ($eths as $eth) {
        $x[] = $ethnicities[$eth]->name;
    }
    return implode(', ', $x);
}

function country_options() {
    $cid = location_type_name_to_id('country');
    $locs = get_locations();
    $x = [[0, dash(null)]];
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
        $x[] = [0, dash(null)];
    }
    foreach ($insts as $inst) {
        $x[] = [$inst->id, $inst->name];
    }
    return $x;
}

function venue_options() {
    $vs = DB_venue::enum();
    $x = [];
    $x[] = [0, dash(null)];
    foreach ($vs as $v) {
        $x[] = [$v->id, $v->name];
    }
    return $x;
}

function venue_str($id) {
    if (!$id) return dash(null);
    $v = DB_venue::lookup_id($id);
    return $v->name;
}

function language_options() {
    $langs = DB_language::enum();
    $x = [0, dash(null)];
    foreach ($langs as $lang) {
        $x[] = [$lang->id, $lang->name];
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
    $x = [[0, dash(null)]];
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
    $x = [[0, dash(null)]];
    foreach ($lts as $lt) {
        $x[] = [$lt->id, location_name($lt)];
    }
    return $x;
}

function role_options() {
    $rs = DB_role::enum();
    $x = [[0, dash(null)]];
    foreach ($rs as $r) {
        $x[] = [$r->id, $r->name];
    }
    return $x;
}

function organization_type_options() {
    $types = DB_organization_type::enum();
    $x = [[0, dash(null)]];
    foreach ($types as $type) {
        $x[] = [$type->id, $type->name];
    }
    return $x;
}

function organization_options() {
    $orgs = DB_organization::enum();
    $x = [[0, dash(null)]];
    foreach ($orgs as $org) {
        $x[] = [$org->id, $org->name];
    }
    return $x;
}

function license_options() {
    $lics = DB_license::enum();
    $x = [[0, dash(null)]];
    foreach ($lics as $lic) {
        $x[] = [$lic->id, $lic->name];
    }
    return $x;
}

function comp_type_options() {
    $cts = get_comp_types();
    $x = [[0, dash(null)]];
    foreach ($cts as $ct) {
        $x[] = [$ct->id, $ct->name];
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
    case 'inst_combo': return "ico$id";
    case 'comp_type': return "cpt$id";
    }
    return 'bad type';
}

function parse_code($code, $type) {
    $t = substr($code, 0, 3);
    $n = substr($code, 3);
    switch ($type) {
    case 'composition': if ($t != 'com') return 0; break;
    case 'person_role': if ($t != 'prl') return 0; break;
    case 'inst_combo': if ($t != 'ico') return 0; break;
    case 'comp_type': if ($t != 'ctp') return 0; break;
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
    return sprintf("<button onclick=\"copy_to_clipboard('%s')\" title=\"Copy code to clipboard\">Code</button>",
        $text
    );
}

// return a string describing the composition,
// which could be an arrangement or a sub-composition
//
function composition_str($c) {
    if ($c->arrangement_of) {
        $c2 = DB_composition::lookup_id($c->arrangement_of);
        $s = 'Arrangement of ';
        if ($c->title && $c->title != 'Complete') {
            $s .= "$c->title from ";
        }
        $s .= $c2->long_title;
        if ($c->instrument_combos) {
            $s .= ' for '.instrument_combos_str($c->instrument_combos);
        }
        return $s;
    } else if ($c->parent) {
        $c2 = DB_composition::lookup_id($c->parent);
        return sprintf('Section (%s) of %s', $c->title, $c2->long_title);
    } else {
        return $c->long_title;
    }
}

// the name of a score's section and composition
//
function score_str($score) {
    $score->compositions = json_decode($score->compositions);
    $comp = DB_composition::lookup_id($score->compositions[0]);
    $s = '';
    if ($score->section) {
        $s = "$score->section from ";
    }
    $s .= composition_str($comp);
    return $s;
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
    if (!$id) return dash(null);
    $org = DB_organization::lookup_id($id);
    return $org->name;
}

function image_type_options() {
    return [
        [0, dash('')],
        [1, 'Normal Scan'],
        [2, 'Manuscript Scan'],
        [3, 'Typeset']
    ];
}
?>
