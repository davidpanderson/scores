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

// if arg is false, return '', else arg
//
function blank($s) {
    return $s?$s:'';
}

function spaces($n) {
    $s = '';
    for ($i=0; $i<$n; $i++) {
        $s .= '&nbsp;';
    }
    return $s;
}

// can user edit this item?
//
function can_edit($item) {
    $user = get_logged_in_user(false);
    if (!$user) return false;
    if ($user->posts == EDIT_ALL) return true;
    return $item->maker == $user->id;
}

// json decode, but return [] if null
function json_decode2($s) {
    if ($s) {
        return json_decode($s);
    } else {
        return [];
    }
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

// return 'first last', linked to person item page
//
function person_id_to_name($id) {
    $person = DB_person::lookup_id($id);
    return sprintf(
        "<a href=item.php?type=%d&id=%d>%s %s</a>",
        PERSON,
        $id,
        $person->first_name, $person->last_name
    );
}

// if performer, return instrument name if present, else 'performer'
// else return role name
//
function role_str($prole) {
    if ($prole->instrument) {
        return instrument_id_to_name($prole->instrument);
    } else {
        return role_id_to_name($prole->role);
    }
}

// return e.g. Joe Smith (piano) or Joe Smith (lyricist)
//
function person_role_str($prole, $show_role=true) {
    $s = person_id_to_name($prole->person);
    if ($show_role) {
        $s .= sprintf(' (%s)', role_str($prole));
    }
    return $s;
}

// $cts is a list of person_role IDs
//
function creators_str($cts, $show_role) {
    $x = [];
    foreach ($cts as $id) {
        $prole = DB_person_role::lookup_id($id);
        $x[] = person_role_str($prole, $show_role);
    }
    return implode(' and ', $x);
}

function pluralize($x) {
    if (substr($x, -2) == 'ss') return $x.'es';
    if (substr($x, -1) == 's') return $x;
    return $x.'s';
}

function instrument_combo_str($ic) {
    $is = $ic->instruments;
    if (is_string($is)) {
        $is = json_decode($is);
    }
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

// continents can have subcontinents.
// But some countries are just in a continent, not a subcontinent
//
// make the location list (country and above) into a hierarchy
//
function country_hier($locs) {
    $cont_type = location_type_name_to_id('continent');
    $subcont_type = location_type_name_to_id('subcontinent');
    $country_type = location_type_name_to_id('country');

    // set up lists
    //
    foreach ($locs as $loc) {
        if ($loc->type == $cont_type) {
            $loc->subconts = [];
            $loc->countries = [];
        } else if ($loc->type == $subcont_type) {
            $loc->countries = [];
        }
    }

    // add to parent lists
    //
    $conts = [];
    foreach ($locs as $loc) {
        if ($loc->type == $country_type) {
            $par = $locs[$loc->parent];
            $par->countries[] = $loc->id;
        } else if ($loc->type == $subcont_type) {
            $par = $locs[$loc->parent];
            $par->subconts[] = $loc->id;
        } else if ($loc->type == $cont_type) {
            $conts[] = $loc;
        }
    }
    return $conts;
}

function country_options() {
    $locs = get_locations();
    $conts = country_hier($locs);
    $opts = [[0, dash()]];
    $sp = '&nbsp;&nbsp;&nbsp;&nbsp;';
    foreach ($conts as $cont) {
        $opts[] = [$cont->id, $cont->name];
        foreach ($cont->subconts as $subcont) {
            $x = $locs[$subcont];
            $opts[] = [$subcont, "$sp$x->name"];
            foreach ($x->countries as $country) {
                $x = $locs[$country];
                $opts[] = [$country, "$sp$sp$x->name"];
            }
        }
        foreach ($cont->countries as $country) {
            $x = $locs[$country];
            $opts[] = [$country, "$sp$x->name"];
        }
    }
    return $opts;
}

function country_options_old() {
    $cid = location_type_name_to_id('country');
    $locs = get_locations();
    $x = [[0, dash()]];
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

function inst_combo_options($n) {
    $ics = get_inst_combos();
    $x = [[0, dash(null)]];
    $i = 0;
    foreach ($ics as $ic) {
        $x[] = [$ic->id, instrument_combo_str($ic)];
        $i++;
        if ($i == $n) break;
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
    if ($loc->type == location_type_name_to_id('country')) {
        return $loc->name;
    }
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
    case 'composition': return "com_$id";
    case 'person_role': return "prl_$id";
    case 'inst_combo': return "ico_$id";
    case 'comp_type': return "cpt_$id";
    case 'organization': return "org_$id";
    case 'ensemble': return "ens_$id";
    }
    return 'bad type';
}

function parse_code($code, $type) {
    $t = substr($code, 0, 3);
    $n = substr($code, 4);
    switch ($type) {
    case 'composition': if ($t != 'com') return 0; break;
    case 'person_role': if ($t != 'prl') return 0; break;
    case 'inst_combo': if ($t != 'ico') return 0; break;
    case 'comp_type': if ($t != 'ctp') return 0; break;
    case 'organization': if ($t != 'org') return 0; break;
    case 'ensemble': if ($t != 'ens') return 0; break;
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
    return sprintf("<img width=20 src=clipboard-copy-512.png onclick=\"copy_to_clipboard('%s')\" title=\"Copy code to clipboard\">",
        $text
    );
}

// return a string describing the composition,
// which could be an arrangement or a sub-composition
//
function composition_str($c, $link=true) {
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
    } else if ($c->parent) {
        $c2 = DB_composition::lookup_id($c->parent);
        $s = sprintf('Section (%s) of %s', $c->title, $c2->long_title);
    } else {
        $s = sprintf('%s for %s',
            $c->long_title,
            instrument_combos_str($c->instrument_combos)
        );
    }
    if ($link) {
        return sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
            COMPOSITION, $c->id, $s
        );
    } else {
        return $s;
    }
}

// the name of a score's section and composition
//
function score_str($score, $link=true) {
    $score->compositions = json_decode($score->compositions);
    $comp = DB_composition::lookup_id($score->compositions[0]);
    $s = '';
    if ($score->section && $score->section != 'Complete') {
        $s = "$score->section from ";
    }
    $s .= composition_str($comp, false);
    if ($link) {
        return sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
            SCORE, $score->id, $s
        );
    } else {
        return $s;
    }
}

// score attributes
//
function score_attrs_str($score) {
    $x = [];
    if ($score->is_parts) $x[] = 'parts';
    if ($score->is_selections) $x[] = 'selections';
    if ($score->is_vocal) $x[] = 'vocal score';
    return implode(', ', $x);
}

// convert a program (list of performance IDs) to text
//
function program_str($prog) {
    $n = 1;
    $x = [];
    foreach ($prog as $pid) {
        $perf = DB_performance::lookup_id($pid);
        $x[] = program_perf_str($n++, $perf);
    }
    return implode('<p>', $x);
}

// for program_str() (concerts)
//
function program_perf_str($n, $perf) {
    $comp = DB_composition::lookup_id($perf->composition);
    $s = sprintf('%d) %s', $n, composition_str($comp));
    $roles = json_decode($perf->performers);
    foreach ($roles as $role_id) {
        $role = DB_person_role::lookup_id($role_id);
        $s .= sprintf("<br>%s%s\n",
            spaces(6),
            person_role_str($role)
        );
    }
    if ($perf->ensemble) {
        $s .= sprintf("<br>%s%s\n",
            spaces(6),
            ensemble_str($perf->ensemble)
        );
    }
    return $s;
}

function ensemble_str($id, $link=true) {
    if (!$id) return '';
    $ens = DB_ensemble::lookup_id($id);
    $type = ensemble_type_id_to_name($ens->type);
    if ($link) {
        return sprintf('<a href=item.php?type=%d&id=%d>%s</a> (%s)',
            ENSEMBLE, $id, $ens->name, $type
        );
    } else {
        return "$ens->name ($type)";
    }
}

function ensemble_type_options() {
    $types = get_ensemble_types();
    $x = [];
    foreach ($types as $s) {
        $x[] = [$s->id, $s->name];
    }
    return $x;
}

function concert_str($con) {
    $venue = DB_venue::lookup_id($con->venue);
    return sprintf('<a href=item.php?type=%d&id=%d>Concert on %s at %s</a>',
        CONCERT, $con->id,
        DB::date_num_to_str($con->_when),
        $venue->name
    );
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

function form_input_text2($label, $name, $value, $placeholder) {
    form_input_text($label, $name, $value, 'text',
        sprintf('placeholder="%s"', $placeholder)
    );
}

// Show a list of top-level compositions as a table.  Used for
// 1) composition search results
// 2) person_role results where role is composer
//
function show_compositions($comps) {
    copy_to_clipboard_script();
    start_table('table-striped');
    table_header(
        'Title<br><small>click for details</small>',
        'Creators',
        'View on IMSLP',
        'Key',
        'Opus',
        'Composed',
        'Instrumentation',
        'Code'
    );
    foreach ($comps as $c) {
        table_row(
            sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                COMPOSITION, $c->id, $c->title
            ),
            creators_str(json_decode2($c->creators), true),
            imslp_link($c),
            $c->_keys,
            $c->opus_catalogue,
            DB::date_num_to_str($c->composed),
            //comp_types_str($c->comp_types),
            instrument_combos_str($c->instrument_combos),
            copy_button(item_code($c->id, 'composition'))
        );
    }
    end_table();
}

// show a set of arrangements as a table.  Used for
// - composition search with arrangement checked
// - person_role results where role is arranger
//
function show_arrangements($comps) {
    start_table('table-striped');
    table_header(
        'Arrangement of', 'Section', 'View on IMSLP', 'Composed', 'Type', 'Arranger', 'Instrumentation'
    );
    foreach ($comps as $c) {
        $c->creators = json_decode2($c->creators);
        $c2 = DB_composition::lookup_id($c->arrangement_of);
        table_row(
            sprintf(
                '<a href=item.php?type=%d&id=%d>%s</a>',
                COMPOSITION, $c2->id, $c2->long_title
            ),
            $c->title,
            imslp_link($c2),
            DB::date_num_to_str($c2->composed),
            comp_types_str($c2->comp_types),
            creators_str($c->creators, false),
            instrument_combos_str($c->instrument_combos)
        );
    }
    end_table();
}

function imslp_link($c) {
    $t = str_replace(' ', '_', $c->long_title);
    $t = str_replace('No._', 'No.', $t);
    $t = str_replace('Op._', 'Op.', $t);
    $url = sprintf('https://imslp.org/wiki/%s', $t);
    return sprintf(
        '<a href=%s><img width=50 src=imslp_logo.png></a>',
        $url
    );
}

// show user name, linked to their user page
//
function user_link($user_id) {
    $user = BoincUser::lookup_id($user_id);
    return sprintf('<a href=user.php?userid=%d>%s</a>',
        $user_id, $user->name
    );
}

// show org name, linked to org page
//
function org_link($id) {
    if ($id) {
        $org = DB_organization::lookup_id($id);
        return sprintf(
            '<a href=item.php?type=%d&id=%d>%s</a>',
            ORGANIZATION, $org->id, $org->name
        );
    }
    return dash();
}

define('BUTTON_STYLE', "background-color:#ffe0b0;border:1px solid #c08070;padding: 4px 10px;border-radius: 4px;");

function form_submit2($text) {
    form_general(
        "",
        sprintf('<button type="submit" style="%s">%s</button>',
            BUTTON_STYLE, $text
        )
    );
}

function button_link($url, $text, $title=null) {
    if ($title) $title=$text;
    return sprintf(
        '<a href="%s" title="%s" class="button" style="%s;color:black; text-decoration:none;">%s</a>',
        $url, $title, BUTTON_STYLE, $text
    );
}
?>
