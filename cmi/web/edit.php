<?php

// create and edit items
//
// Some types (score, concert, etc.) have fields that are lists of things.
// To handle this we use an approach where we pass a JSON-encoded
// version of the item in the form URL.
// You can add/remove list items.
// Only when you click Add/Update at the bottom does it get written to the DB.
//
// For fields that are links to other tables:
// if the other table is small (instrument, language)
// we use select or select-multi.
// For large tables we use 'item codes',
// which are a DB ID plus a type symbol (e.g. comp2345).
// You look up the item you want, then copy/paste the code.

require_once('../inc/util.inc');
require_once('cmi.inc');
require_once('write_ser.inc');

define('BUTTON_CLASS_ADD', 'btn btn-xs btn-success py-0');
define('BUTTON_CLASS_REMOVE', 'btn btn-xs btn-warning');

function edit_error_page($name, $value) {
    page_head('Input error');
    echo "The input field <b>$name</b> had an illegal value '$value'.
        Please go back and correct it.
    ";
    page_tail();
    exit;
}

// get a date, convert to int, complain if bad
//
function get_date($field_name, $get_name) {
    $str = get_str($get_name, true);
    if ($str) {
        $val = DB::date_num_parse($str);
        if (!$val) edit_error_page($field_name, $str);
        return $val;
    }
    return 0;
}

// start a form row with the given title
//
function form_row_start($title) {
    echo sprintf('
        <div class="form-group">
            <label align=right class="%s">%s</label>
            <div class="%s">
        ',
        FORM_LEFT_CLASS, $title, FORM_RIGHT_CLASS
    );
}

function form_row_end() {
    echo '</div></div><p>&nbsp;</p>';
}

function comp_code_msg() {
    return "
        <p class=\"text-danger h4\">
        Invalid or missing composition code.
        </p>
        <p>
        To get a composition code:
        <ul>
        <li> In a different browser tab,
            locate the composition.
        <li> Click on the Copy button;
            this copies the role code to the clipboard.
        <li> Paste the code (e.g. <code>com1234</code>) into the form.
        </ul>
    ";
}

function person_role_code_msg() {
    return "
        <p class=\"text-danger h4\">
        Invalid or missing role code.
        </p>
        <p>
        To get a role code:
        <ul>
        <li> In a different browser tab,
            locate the performer (person or ensemble).
        <li> Find the appropriate role, e.g. 'performer (piano)'.
        <li> Click on the Copy button next to the role;
            this copies the role code to the clipboard.
        <li> Paste the role code (e.g. <code>rol1234</code>) into the form.
        </ul>
    ";
}

function concert_form($id) {
    // get concert params, from DB or from URL args
    //
    $con = null;
    if ($id) {
        $con = DB_concert::lookup_id($id);
        if (!$con) {
            error_page("No concert: $id\n");
        }
        $con->program = json_decode($con->program);
        $con_json = json_encode($con);
    } else {
        // if arg is absent, or present and id is zero,
        // we're creating a new concert
        // else we're editing an existing concert
        //
        $con_json = get_str('con', true);
        if ($con_json) {
            $con_json = urldecode($con_json);
            $con = json_decode($con_json);
        } else {
            $con = new StdClass;
            $con->id = 0;
            $con->_when = '';
            $con->venue = 0;
            $con->organizer = 0;
            $con->program = [];
            $con_json = json_encode($con);
        }
    }

    // do edits as needed
    //
    $message = '';
    $error_msg = '';
    $op = get_str('op', true);
    switch ($op) {
    case 'add_comp':
        $comp_code = get_str('comp_code', true);
        $comp_id = parse_code($comp_code, 'composition');
        $comp = DB_composition::lookup_id($comp_id);
        if (!$comp) {
            $error_msg = comp_code_msg();
            break;
        }

        // see if comp is already in program
        foreach ($con->program as $perf_id) {
            $perf = DB_performance::lookup_id($perf_id);
            if ($perf->composition == $comp_id) {
                $error_msg = "That composition is already in the program.";
                break;
            }
        }
        if ($error_msg) break;

        $perf_id = DB_performance::insert(
            sprintf("(composition, performers, tentative) values (%d, '%s',1)",
                (int)$comp_id,
                json_encode([])
            )
        );
        $con->program[] = $perf_id;
        $con_json = json_encode($con);
        $message = 'Added composition.';
        break;
    case 'remove_comp':
        $perf_id = get_int('perf_id');
        $con->program = array_diff($con->program, [$perf_id]);
        $con_json = json_encode($con);
        $message = 'Removed composition.';
        break;
    case 'add_perf':
        $prole_code = get_str('prole_code', true);
        $prole_id = parse_code($prole_code, 'person_role');
        if (!$prole_id) {
            $error_msg = person_role_code_msg();
            break;
        }
        $perf_id = get_int('perf_id');
        foreach ($con->program as $pid) {
            if ($pid == $perf_id) {
                $perf = DB_performance::lookup_id($perf_id);
                $x = json_decode($perf->performers);
                if (in_array($prole_id, $x)) {
                    $error_msg = "Duplicate performer.";
                    break;
                }
                $x[] = $prole_id;
                $perf->update(sprintf("performers='%s'", json_encode($x)));
                $message = 'Added performer.';
                break;
            }
        }
        if (!$message && !$error_msg) {
            $error_msg = 'Performer not found.';
        }
        break;
    case 'remove_perf':
        $prole_id = get_int('prole_id');
        $perf_id = get_int('perf_id');
        foreach ($con->program as $pid) {
            if ($pid == $perf_id) {
                $perf = DB_performance::lookup_id($perf_id);
                $x = json_decode($perf->performers);
                $x = array_diff($x, [$prole_id]);
                $perf->update(sprintf("performers='%s'", json_encode($x)));
                $message = 'Removed performer.';
                break;
            }
        }
        if (!$message) {
            $error_msg = 'Performer not found.';
        }
    }

    // show page
    //
    page_head($id?'Edit concert':'Add concert');

    if ($message) echo "$message<p>\n";
    if ($error_msg) echo "$error_msg<p>\n";

    form_row_start('Program');
    $n = 1;
    foreach ($con->program as $perf_id) {
        $perf = DB_performance::lookup_id($perf_id);
        $comp = DB_composition::lookup_id($perf->composition);
        echo sprintf('<b>%d) %s</b>', $n++, composition_str($comp));
        $roles = json_decode($perf->performers);

        echo "<ul>";
        foreach ($roles as $role_id) {
            $role = DB_person_role::lookup_id($role_id);
            echo sprintf("<li> %s\n", person_role_str($role));
        }
        echo "</ul>";

        // form for adding performer to this comp
        //
        echo sprintf('
            <p>
            <form action=edit.php>
            <input type=hidden name=type value=%d>
            <input type=hidden name=con value="%s">
            <input type=hidden name=op value=add_perf>
            <input type=hidden name=perf_id value=%d>
            <input type=submit class="%s" value="Add performer:">
            <input title="paste a role code here" name=prole_code placeholder="role code">
            </form>
            ',
            CONCERT,
            urlencode($con_json), $perf_id,
            BUTTON_CLASS_ADD
        );
        echo '<br>';

        // button for removing comp from program
        //
        show_button(
            sprintf(
                'edit.php?type=%d&id=%d&con=%s&perf_id=%d&op=remove_comp',
                CONCERT,
                $id,
                urlencode($con_json),
                $perf_id
            ),
            'Remove this composition',
            '', BUTTON_CLASS_REMOVE
        );
        echo '<hr>';
    }

    // form for adding another composition to program
    //
    echo sprintf('
        <form action=edit.php>
        <input type=hidden name=type value=%d>
        <input type=hidden name=con value="%s">
        <input type=hidden name=op value=add_comp>
        <input type=submit class="%s" value="Add composition">
        <input name=comp_code placeholder="composition code">
        </form>
        ',
        CONCERT,
        urlencode($con_json),
        BUTTON_CLASS_ADD
    );
    form_row_end();

    // main form with other items

    form_start('edit.php');
    form_input_hidden('con', urlencode($con_json));
    form_input_hidden('id', $id);
    form_input_hidden('type', CONCERT);
    form_input_hidden('submit', true);
    form_select('Venue', 'venue', venue_options(), $con->venue);
    if ($con->_when) {
        form_input_text('Date', 'when', DB::date_num_to_str($con->_when));
    } else {
        form_input_text('Date', 'when', '', 'text', 'placeholder="YYYY-MM-DD"');
    }
    form_select('Sponsor', 'organization', organization_options(), $con->organization);
    form_submit($id?'Update concert':'Add concert');
    form_end();

    page_tail();
}

function concert_action($id) {
    $con_json = urldecode(get_str('con'));
    $con = json_decode($con_json);
    $venue = get_int('venue');
    $when = get_str('when', true);
    if ($when) {
        $when = DB::date_num_parse($when);
    } else {
        $when = 0;
    }
    $organization = get_int('organization');

    // clear tentative from performances
    foreach ($con->program as $perf_id) {
        $p = DB_performance::lookup_id($perf_id);
        $p->update('tentative=0');
    }

    if ($id) {
        $c = DB_concert::lookup_id($id);
        if (!$c) error_page("No concert $id");
        $q = sprintf(
            "_when=%d, venue=%d, organization=%d, program='%s'",
            $when, $venue, $organization,
            json_encode($con->program)
        );
        $c->update($q);
    } else {
        $q = sprintf(
            "(_when, venue, organization, program) values (%d, %d, %d, '%s')",
            $when, $venue, $organization,
            json_encode($con->program)
        );
        $id = DB_concert::insert($q);
    }
    header(
        sprintf('Location: item.php?type=%d&id=%d', CONCERT, $id)
    );
}

function location_form($id) {
    $loc = null;
    if ($id) {
        $loc = DB_location::lookup_id($id);
        if (!$loc) {
            error_page("No location: $id\n");
        }
    }
    page_head($loc?'Edit location':'Add location');
    form_start('edit.php', 'get');
    form_input_hidden('type', LOCATION);
    form_input_hidden('submit', 1);
    if ($id) {
        form_input_hidden('id', $id);
    }
    form_input_text('Name', 'name', $loc?$loc->name:'');
    form_select('Type', 'loc_type', location_type_options(),
        $loc?$loc->type:null
    );
    form_select('Parent', 'parent', location_options(),
        $loc?$loc->parent:null
    );
    form_submit($loc?'Update':'Add');
    form_end();
    page_tail();
}

function location_action($id) {
    if ($id) {
        $loc = DB_location::lookup_id($id);
        if (!$loc) {
            error_page("No location: $id\n");
        }
        $name = get_str('name');
        $q = sprintf("name='%s', type=%d, parent=%d",
            DB::escape($name),
            get_int('loc_type'),
            get_int('parent')
        );
        $ret = $loc->update($q);
        if ($ret) {
            page_head("Location $name updated");
            page_tail();
        } else {
            error_page('Update failed');
        }
    } else {
        $name = get_str('name');
        $q = sprintf("(name, type, parent) values ('%s', %d, %d)",
            DB::escape($name),
            get_int('loc_type'),
            get_int('parent')
        );
        $ret = DB_location::insert($q);
        if ($ret) {
            page_head("Location $name added");
            page_tail();
        } else {
            error_page('Insert failed');
        }
    }
    write_ser_location();
}

function person_form($id) {
    if ($id) {
        $p = DB_person::lookup_id($id);
        if (!$p) die("No person $id");
        $p->locations = $p->locations?json_decode($p->locations):[];
        $p->ethnicity = $p->ethnicity?json_decode($p->ethnicity):[];
        select2_head("Edit person");
    } else {
        $p = new StdClass;
        $p->first_name = '';
        $p->last_name = '';
        $p->born = 0;
        $p->birth_place = 0;
        $p->died = 0;
        $p->death_place = 0;
        $p->locations = [];
        $p->sex = 0;
        $p->ethnicity = [];
        select2_head("Add person");
    }
    form_start('edit.php');
    form_input_hidden('type', PERSON);
    form_input_hidden('submit', true);
    if ($id) {
        form_input_hidden('id', $id);
    }
    form_input_text('First name', 'first_name', $p->first_name);
    form_input_text('Last name', 'last_name', $p->last_name);
    form_input_text('Born', 'born',
        $p->born?DB::date_num_to_str($p->born):'YYYY-MM-DD'
    );
    form_select('Birth place', 'birth_place', location_options(), $p->birth_place);
    form_input_text('Died', 'died',
        $p->died?DB::date_num_to_str($p->died):'YYYY-MM-DD'
    );
    form_select('Death place', 'death_place', location_options(), $p->death_place);
    select2_multi('Locations', 'locations', location_options(), $p->locations);
    form_select('Sex', 'sex', sex_options(), $p->sex);
    select2_multi('Ethnicity', 'ethnicity', ethnicity_options(), $p->ethnicity);
    form_submit('Update');
    form_end();
    page_tail();
}

function person_action($id) {
    $first_name = get_str('first_name');    
    $last_name = get_str('last_name');
    $born = DB::date_num_parse(get_str('born'));
    $birth_place = get_int('birth_place');
    $died = DB::date_num_parse(get_str('died'));
    $death_place = get_int('death_place');
    $locations = get_str('locations', true);
    if (!$locations) $locations = [];
    $sex = get_int('sex');
    $ethnicity = get_str('ethnicity', true);
    if (!$ethnicity) $ethnicity = [];
    if ($id) {
        $p = DB_person::lookup_id($id);
        if (!$p) error_page("No person $id");
        $q = sprintf(
            "first_name='%s', last_name='%s', born=%d, birth_place=%d, died=%d, death_place=%d, locations='%s', sex=%d, ethnicity='%s'",
            DB::escape($first_name),
            DB::escape($last_name),
            $born, $birth_place, $died, $death_place,
            json_encode($locations), $sex, json_encode($ethnicity)
        );
        $p->update($q);
    } else {
        $id = DB_person::insert(
            sprintf("(first_name, last_name, born, birth_place, died, death_place, locations, sex, ethnicity) values ('%s', '%s', %d, %d, %d, %d, '%s', %d, '%s')",
                DB::escape($first_name),
                DB::escape($last_name),
                $born, $birth_place, $died, $death_place,
                json_encode($locations), $sex, json_encode($ethnicity)
            )
        );
    }
    header(
        sprintf('Location: item.php?type=%d&id=%d', PERSON, $id)
    );
}
function ensemble_form($id) {
    $ens = null;
    if ($id) {
        $ens = DB_ensemble::lookup_id($id);
    }
    page_head($ens?'Edit ensemble':'Add ensemble');
    form_start('edit.php');
    form_input_hidden('type', ENSEMBLE);
    if ($id) {
        form_input_hidden('id', $id);
    }
    form_input_text('Name', 'name', $ens?$ens->name:'');
    form_select(
        'Type', 'type', ensemble_type_options(), $ens?$ens->type:null
    );
    form_end();
    page_tail();
}

function ensemble_action($id) {
    $name = get_str('name');
    $type = get_int('type');
    if ($id) {
        $ens = DB_ensemble::lookup_id($id);
        $q = sprintf("name='%s', type=%d", DB::escape($name), $type);
        $ens->update($q);
    } else {
        $id = DB_ensemble::insert(
            sprintf("(name, type) values ('%s', %d)",
                DB::escape($name), $type
            )
        );
    }
    header(
        sprintf('Location: item.php?type=%d&id=%d', ENSEMBLE, $id)
    );
}

function person_role_form() {
    $pid = get_int('person_id');
    $person = DB_person::lookup_id($pid);
    if (!$person) error_page("No person $pid");

    page_head("Add role for $person->first_name $person->last_name");
    form_start('edit.php');
    form_input_hidden('type', PERSON_ROLE);
    form_input_hidden('submit', true);
    form_input_hidden('person_id', $pid);
    form_select('Role', 'role', role_options());
    form_select(
        'Instrument<br><small>If performer</small>',
        'instrument', instrument_options(true), 0
    );
    form_submit('OK');
    form_end();
    page_tail();
}

function person_role_action() {
    $person_id = get_int('person_id');
    $role = get_int('role');
    $instrument = get_int('instrument');
    DB_person_role::insert(
        sprintf("(person, instrument, role) values (%d, %d, %d)",
            $person_id, $instrument, $role
        )
    );
    header(
        sprintf('Location: item.php?type=%d&id=%d', PERSON, $person_id)
    );
}

function venue_form($id) {
    $ven = null;
    if ($id) {
        $ven = DB_venue::lookup($id);
    }
    page_head($ven?'Edit venue':'Add venue');
    form_start('edit.php', 'get');
    form_input_hidden('type', VENUE);
    if ($id) {
        form_input_hidden('id', $id);
    }
    form_input_hidden('submit', true);
    form_input_text('Name', 'name', $ven?$ven->name:'');
    form_select(
        'Location', 'location', location_options(), $ven?$ven->location:null
    );
    form_input_text('Capacity', 'capacity', $ven?$ven->capacity:null);
    form_submit('OK');
    form_end();
    page_tail();
}

function venue_action($id) {
    $name = get_str('name');
    $location = get_int('location');
    $capacity = get_int('capacity');
    if ($id) {
        $ven = DB_venue::lookup_id($id);
        $q = sprintf("name='%s', location=%d, capacity=%d",
            DB::escape($name), $location, $capacity
        );
        $ven->update($q);
    } else {
        $id = DB_venue::insert(
            sprintf("(name, location, capacity) values ('%s', %d, %d)",
                DB::escape($name), $location, $capacity
            )
        );
    }
    header(
        sprintf('Location: item.php?type=%d&id=%d', VENUE, $id)
    );
}

function organization_form($id) {
    $org = null;
    if ($id) {
        $org = DB_organization::lookup_id($id);
    }
    page_head($org?'Edit organization':'Add organization');
    form_start('edit.php', 'get');
    form_input_hidden('type', ORGANIZATION);
    form_input_hidden('submit', true);
    if ($id) {
        form_input_hidden('id', $id);
    }
    form_input_text('Name', 'name', $org?$org->name:'');
    form_select(
        'Type', 'org_type', organization_type_options(), $org?$org->type:null
    );
    form_select('Location', 'location', location_options(),
        $org?$org->location:null
    );
    form_input_text('URL', 'url', $org?$org->url:'');
    form_submit('OK');
    form_end();
    page_tail();
}

function organization_action($id) {
    $name = get_str('name');
    $location = get_int('location');
    $type = get_int('org_type');
    $url = get_str('url', true);
    if ($id) {
        $org = DB_organization::lookup_id($id);
        $q = sprintf("name='%s', location=%d, type=%d, url='%s'",
            DB::escape($name), $location, $type, DB::escape($url)
        );
        $org->update($q);
    } else {
        $id = DB_organization::insert(
            sprintf("(name, type, location, url) values ('%s', %d, %d, '%s')",
                DB::escape($name), $type, $location, DB::escape($url)
            )
        );
    }
    header(
        sprintf('Location: item.php?type=%d&id=%d', ORGANIZATION, $id)
    );
}

function empty_composition() {
    $comp = new StdClass;
    $comp->title = '';
    $comp->long_title = '';
    $comp->alternative_title = '';
    $comp->opus_catalogue = '';
    $comp->composed = 0;
    $comp->published = 0;
    $comp->performed = 0;
    $comp->dedication = '';
    $comp->tempo_markings = '';
    $comp->metronome_markings = '';
    $comp->_keys = '';
    $comp->time_signatures = '';
    $comp->comp_types = [];
    $comp->creators = [];
    $comp->parent = 0;
    $comp->children = [];
    $comp->arrangement_of = 0;
    $comp->language = 0;
    $comp->instrument_combos = [];
    $comp->ensemble_type = 0;
    $comp->period = 0;
    $comp->avg_duration_sec = '';
    $comp->n_movements = 0;
    $comp->n_bars = 0;
    return $comp;
}

// we use the same code for
// - top-level compositions
// - sections
// - arrangements
// Some fields are relevant only to one of these
//
function composition_form($id) {
    $comp = null;
    $is_section = false;
    $is_arrangement = false;
    if ($id) {
        $comp = DB_composition::lookup_id($id);
        if ($comp->parent) {
            $parent_comp = DB_composition::lookup_id($comp->parent);
            $is_section = true;
        }
        if ($comp->arrangement_of) {
            $parent_comp = DB_composition::lookup_id($comp->arrangement_of);
            $is_arrangement = true;
        }
        if (!$is_section) {
            $comp->creators = json_decode($comp->creators);
            if ($comp->instrument_combos) {
                $comp->instrument_combos = json_decode($comp->instrument_combos);
            } else {
                $comp->instrument_combos = [];
            }
        }
        if (!$is_section && !$is_arrangement) {
            if ($comp->comp_types) {
                $comp->comp_types = json_decode($comp->comp_types);
            } else {
                $comp->comp_types = [];
            }
        }
        $comp_json = json_encode($comp);
    } else {
        $comp_json = get_str('comp', true);
        if ($comp_json) {
            $comp_json = urldecode($comp_json);
            $comp = json_decode($comp_json);
        } else {
            $comp = empty_composition();
            $parent = get_int('parent', true);
            if ($parent) {
                $parent_comp = DB_composition::lookup_id($parent);
                $comp->parent = $parent;
            }
            $arrangement_of = get_int('arrangement_of', true);
            if ($arrangement_of) {
                $parent_comp = DB_composition::lookup_id($arrangement_of);
                $comp->arrangement_of = $arrangement_of;
            }
            $comp_json = json_encode($comp);
        }
        if ($comp->parent) $is_section = true;
        if ($comp->arrangement_of) $is_arrangement = true;
    }

    // do edits
    //
    $message = '';
    $error_msg = '';
    $op = get_str('op', true);
    switch ($op) {
    case 'add_creator':
        $prole_code = get_str('prole_code', true);
        $prole_id = parse_code($prole_code, 'person_role');
        if (!$prole_id) {
            $error_msg = "
                <p class=\"text-danger h4\">
                Invalid or missing role code.
                </p>
                <p>
                To get a role code:
                <ul>
                <li> In a different browser tab,
                    locate the performer (person or ensemble).
                <li> Find the appropriate role, e.g. 'composer'.
                <li> Click on the Copy button next to the role;
                    this copies the role code to the clipboard.
                <li> Paste the role code (e.g. <code>rol1234</code>) into the form.
                </ul>
            ";
            break;
        }
        if (in_array($prole_id, $comp->creators)) {
            $error_msg = 'Duplicate creator';
        } else {
            $comp->creators[] = $prole_id;
            $comp_json = json_encode($comp);
            $message = 'Added creator';
        }
        break;
    case 'remove_creator':
        $prole_id = get_int('prole_id');
        $comp->creators = array_diff($comp->creators, [$prole_id]);
        $comp_json = json_encode($comp);
        $message = 'Removed creator';
        break;
    case 'add_ic':
        $ic_code = get_str('ic_code', true);
        $ic_id = parse_code($ic_code, 'inst_combo');
        if (!$ic_id) error_page('bad ic code');
        if (in_array($ic_id, $comp->instrument_combos)) {
            $error_msg = 'Duplicate instrumentation';
        } else {
            $comp->instrument_combos[] = $ic_id;
            $comp_json = json_encode($comp);
            $message = 'Added instrumentation';
        }
        break;
    case 'remove_ic':
        $ic_id = get_int('ic_id');
        $comp->instrument_combos = array_diff($comp->instrument_combos, [$ic_id]);
        $comp_json = json_encode($comp);
        $message = 'Removed instrumentation';
        break;
    case '':
        break;
    default:
        error_page("Bad op $op");
    }

    if ($is_section) {
        $x = sprintf('section of %s', $parent_comp->title);
    } else if ($is_arrangement) {
        $x = sprintf('arrangement of %s', $parent_comp->title);
    } else {
        $x = 'composition';
    }
    select2_head($id?"Edit $x":"Add $x");
    
    if ($message) echo "$message<p>\n";
    if ($error_msg) echo "$error_msg<p>\n";

    // show page

    echo sprintf('
        <form action=edit.php id=add_creator>
        <input type=hidden name=type value=%d>
        <input type=hidden name=comp value="%s">
        <input type=hidden name=op value=add_creator>
        <input type=hidden name=id value=%d>
        </form>
        ',
        COMPOSITION, urlencode($comp_json), $id
    );
    echo sprintf('
        <form action=edit.php id=add_inst>
        <input type=hidden name=type value=%d>
        <input type=hidden name=comp value="%s">
        <input type=hidden name=op value=add_ic>
        <input type=hidden name=id value=%d>
        </form>
        ',
        COMPOSITION, urlencode($comp_json), $id
    );

    // main form
    form_start('edit.php', 'get');
    form_input_hidden('comp', urlencode($comp_json));
    form_input_hidden('type', COMPOSITION);
    form_input_hidden('submit', true);
    form_input_hidden('id', $id);
    if (!$is_arrangement) {
        form_input_text('Title', 'title', $comp?$comp->title:'');
    }
    if (!$is_section && !$is_arrangement) {
        form_input_text('Opus', 'opus', $comp?$comp->opus_catalogue:'');
        select2_multi('Composition types', 'comp_types', comp_type_options(),
            $comp->comp_types
        );
    }

    // things that only main comps and arrangements have
    //
    if (!$is_section) {
        // creators
        form_row_start('Creators');
        foreach ($comp->creators as $prole_id) {
            $prole = DB_person_role::lookup_id($prole_id);
            echo sprintf("%s\n", person_role_str($prole));
            show_button(
                sprintf(
                    'edit.php?type=%d&comp="%s"&prole_id=%d&op=remove_creator',
                    COMPOSITION,
                    urlencode($comp_json),
                    $prole_id
                ),
                'Remove',
                '', BUTTON_CLASS_REMOVE
            );
        }
        if (!$comp->creators) echo dash('');
        echo sprintf('
            <p><p>
            <input type=submit class="%s" value="Add creator:" form=add_creator>
            <input name=prole_code placeholder="role code" form=add_creator>
            ',
            BUTTON_CLASS_ADD
        );
        form_row_end();

        // inst combos
        //
        form_row_start('Instrumentations');
        foreach ($comp->instrument_combos as $icid) {
            $ic = DB_instrument_combo::lookup_id($icid);
            echo instrument_combo_str($ic);
            show_button(
                sprintf(
                    'edit.php?type=%d&comp="%s"&ic_id=%d&op=remove_ic',
                    COMPOSITION,
                    urlencode($comp_json),
                    $icid
                ),
                'Remove',
                '', BUTTON_CLASS_REMOVE
            );
            echo '<p>';
        }
        if (!$comp->instrument_combos) echo dash('');
        echo sprintf('
            <p><p>
            <input type=submit class="%s" value="Add instrumentation:" form=add_inst>
            <input name=ic_code placeholder="instrumentation code" form=add_inst>
            ',
            BUTTON_CLASS_ADD
        );
        form_row_end();

        form_input_text('Composed', 'composed', DB::date_num_to_str($comp->composed));
        form_input_text('Published', 'published', DB::date_num_to_str($comp->published));
    }

    // things that only main comps can have
    //
    if (!$is_section && !$is_arrangement) {
        form_input_text('Dedication', 'dedication', $comp->dedication);
    }

    // things that only main comps and sections can have
    //
    if (!$is_arrangement) {
        form_input_text('Time signatures', 'time_signatures', $comp->time_signatures);
    }

    // things that they all can have
    //
    form_input_text('Tempo markings', 'tempo_markings', $comp->tempo_markings);
    form_input_text('Metronome markings', 'metronome_markings', $comp->metronome_markings);
    form_input_text('Keys', 'keys', $comp->_keys);
    form_input_text('Average duration, seconds', 'avg_duration_sec', $comp->avg_duration_sec);
    form_input_text('# measures', 'n_bars', $comp->n_bars);

    if ($is_section) {
        form_submit($id?'Update section':'Add section');
    } else if ($is_arrangement) {
        form_submit($id?'Update arrangement':'Add arrangement');
    } else {
        form_submit($id?'Update composition':'Add composition');
    }
    form_end();

    page_tail();
}

function composition_action($id) {
    //print_r($_GET); exit;
    $comp_json = urldecode(get_str('comp'));
    $comp = json_decode($comp_json);
    //print_r($comp); exit;
    $title = get_str('title', true);

    // get and check the title; make the long title
    //
    if ($comp->parent) {
        if (!$title) {
            edit_error_page('Title', $title);
        }
        if (!$id) {
            $c2 = DB_composition::lookup(
                sprintf("parent=%d and title='%s'",
                    $comp->parent, $title
                )
            );
            if ($c2) {
                error_page("A section named $title already exists.");
            }
        }
        $long_title = '';
    } else if ($comp->arrangement_of) {
        $title = '';
        $long_title = '';
    } else {
        $long_title = $title;
        if ($opus) $long_title .= ", $opus";
        if ($comp->creators) {
            $prole = DB_person_role::lookup_id($comp->creators[0]);
            $person = DB_person::lookup_id($prole->person);
            $long_title .= " ($person->last_name, $person->first_name)";
        }
    }

    // get other fields
    //
    $opus = get_str('opus', true);
    $comp_types = get_str('comp_types', true);
    if (!$comp_types) $comp_types = [];
    $dedication = get_str('dedication', true);
    $time_signatures = get_str('time_signatures', true);
    $tempo_markings = get_str('tempo_markings', true);
    $metronome_markings = get_str('metronome_markings', true);
    $keys = get_str('keys', true);
    $avg_duration_sec = get_int('avg_duration_sec', true);
    $n_bars = get_int('n_bars', true);
    $composed = get_date('Composed', 'composed');
    $published = get_date('Published', 'published');

    // do the update or insert
    //
    $id = get_int('id', true);
    if ($id) {
        $c = DB_composition::lookup_id($id);
        if (!$c) error_page("No composition $id");
        $q = sprintf(
            "long_title='%s', title='%s', opus_catalogue='%s', composed=%d, published=%d, dedication='%s', time_signatures='%s', tempo_markings='%s', metronome_markings='%s', _keys='%s', avg_duration_sec=%d, n_bars=%d, creators='%s', instrument_combos='%s', comp_types='%s'",
            DB::escape($long_title),
            DB::escape($title),
            DB::escape($opus),
            $composed,
            $published,
            DB::escape($dedication),
            DB::escape($time_signatures),
            DB::escape($tempo_markings),
            DB::escape($metronome_markings),
            DB::escape($keys),
            $avg_duration_sec,
            $n_bars,
            $comp->creators,
            $comp->instrument_combos,
            json_encode($comp_types, JSON_NUMERIC_CHECK)
        );
        //echo $q; exit;
        $c->update($q);
    } else {
        $q = sprintf(
            "(long_title, title, opus_catalogue, composed, published, dedication, time_signatures, tempo_markings, metronome_markings, _keys, avg_duration_sec, n_bars, creators, parent, arrangement_of, instrument_combos, comp_types)
                values('%s', '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', %d, %d, '%s', '%s')
            ",
            DB::escape($long_title),
            DB::escape($title),
            DB::escape($opus),
            $composed,
            $published,
            DB::escape($dedication),
            DB::escape($time_signatures),
            DB::escape($tempo_markings),
            DB::escape($metronome_markings),
            DB::escape($keys),
            $avg_duration_sec,
            $n_bars,
            json_encode($comp->creators),
            $comp->parent,
            $comp->arrangement_of,
            json_encode($comp->instrument_combos),
            json_encode($comp_types)
        );
        //echo $q; exit;
        $id = DB_composition::insert($q);

        // if new section, add to parent
        //
        if ($comp->parent) {
            $parent = DB_composition::lookup_id($comp->parent);
            if ($parent->children) {
                $children = json_decode($parent->children);
            } else {
                $children = [];
            }
            $children[] = $id;
            $parent->update(
                sprintf("children = '%s'",
                    json_encode($children, JSON_NUMERIC_CHECK)
                )
            );
        }
    }
    header(
        sprintf('Location: item.php?type=%d&id=%d', COMPOSITION, $id)
    );
}

function inst_combo_form() {
    $ids = get_str('ids', true);
    $counts = get_str('counts', true);
    if ($counts) {
        $ids = json_decode(urldecode($ids));
        $counts = json_decode(urldecode($counts));
    } else {
        $counts=[];
        $ids = [];
    }

    if (get_str('op', true) == 'add_inst') {
        $counts[] = get_int('new_count');
        $ids[] = get_int('new_id');
    }

    $n = count($counts);
    page_head('Add instrumentation');
    start_table();
    table_header('Instrument', 'Number');
    for ($i=0; $i<$n; $i++) {
        table_row(
            instrument_id_to_name($ids[$i]),
            $counts[$i]
        );
    }
    echo sprintf('
        <form action=edit.php>
        <input type=hidden name=type value=%d>
        <input type=hidden name=op value=add_inst>
        <input type=hidden name=ids value="%s">
        <input type=hidden name=counts value="%s">
        ',
        INST_COMBO,
        urlencode(json_encode($ids)),
        urlencode(json_encode($counts))
    );
    $s = ['<select name=new_id>'];
    foreach (instrument_options(true) as [$id, $name]) {
        $s[] = "<option value=$id>$name</option>";
    }
    $s[] = '</select>';
    $s = implode("\n", $s);

    $t = ['<select name=new_count>'];
    for ($i=1; $i<20; $i++) {
        $t[] = "<option value=$i>$i</option>";
    }
    $t[] = '</select>';
    $t = implode("\n", $t);

    table_row($s, $t);
    table_row('', '<input type=submit value="Add instrument">');
    echo '</form>';
    end_table();
    show_button(
        sprintf('edit.php?type=%d&counts=%s&ids=%s&submit=1',
            INST_COMBO,
            urlencode(json_encode($counts)), urlencode(json_encode($ids))
        ),
        'Create instrumentation'
    );
    page_tail();
}

function inst_combo_action() {
    $ids = json_decode(urldecode(get_str('ids')));
    $counts = json_decode(urldecode(get_str('counts')));
    $x = new StdClass;
    $x->count = $counts;
    $x->id = $ids;
    $ic_json = json_encode($x, JSON_NUMERIC_CHECK);
    $ic_md5 = md5($ic_json);
    // TODO: check for dup
    // TODO: sort by inst ID; same in populate_util.inc
    DB_instrument_combo::insert(
        sprintf("(instruments, md5) values ('%s', '%s')",
            DB::escape($ic_json),
            DB::escape($ic_md5)
        )
    );
    write_ser_instrument_combo();
    page_head('Instrumentation added');
    page_tail();
}

function score_form($id) {
    $score = null;
    if ($id) {
        $score = DB_score::lookup_id($id);
        $score->compositions = json_decode($score->compositions);
        $score->file_names = json_decode($score->file_names);
        $score->file_descs = json_decode($score->file_descs);
        $score->languages = $score->languages?json_decode($score->languages):[];
        $score->page_counts = $score->page_counts?json_decode($score->page_counts):[];
        $score_json = json_encode($score);
    } else {
        $score_json = get_str('score', true);
        if ($score_json) {
            $score_json = urldecode($score_json);
            $score = json_decode($score_json);
        } else {
            $score = new StdClass;
            $score->id = 0;
            $score->compositions = [];
            $score->file_names = [];
            $score->file_descs = [];
            $score->publisher = 0;
            $score->license = 0;
            $score->languages = [];
            $score->publish_date = 0;
            $score->edition_number = '';
            $score->page_counts = [];
            $score->image_type = '';
            $score->is_parts = 0;
            $score->is_selections = 0;
            $score->is_vocal = 0;
            $score_json = json_encode($score);
        }
    }
    // actions: add/remove file, add/remove comp
    $message = '';
    $error_msg = '';
    $op = get_str('op', true);
    switch ($op) {
    case 'add_file':
        $score->file_names[] = get_str('name');
        $score->file_descs[] = get_str('desc');
        $score->page_counts[]= get_int('page_counts');
        $message = 'Added composition';
        break;
    case 'remove_file':
        $index = get_str('index');
        array_splice($score->file_names, $index);
        array_splice($score->file_descs, $index);
        array_splice($score->page_counts, $index);
        $message = 'Removed composition';
        break;
    case 'add_comp':
        $comp_code = get_str('comp_code');
        $comp_id = parse_code($comp_code, 'composition');
        if ($comp_id) {
            $error_msg = comp_code_msg();
            break;
        }
        $score->compositions[] = $comp_id;
        $message = 'Added composition';
        break;
    case 'remove_comp':
        $comp_id = get_int('comp_id');
        $score->compositions = array_diff($score->compositions, [$comp_id]);
        $message = 'Removed composition';
        break;
    }

    select2_head($id?'Edit score':'Add score');
    if ($message) echo "$message<p>\n";
    if ($error_msg) echo "$error_msg<p>\n";

    form_row_start('Compositions');
    $i = 1;
    foreach ($score->compositions as $cid) {
        $comp = DB_composition::lookup_id($cid);
        echo sprintf(
            '%d. %s
            <p>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <a href=edit.php>Delete</a>
            <p>
            ',
            $i++,
            $comp->long_title
        );
    }
    echo '
        <p>
        <form action=edit.php>
        <p>
        <input type=submit value="Add composition">
        <input name=comp_code placeholder="Composition code">
        <p>
        </form>
    ';
    form_row_end();

    form_row_start('Files');
    $n = count($score->file_names);
    for ($i=0; $i<$n; $i++) {
        echo sprintf(
            '%d. %s
            <p>
            &nbsp;&nbsp;&nbsp;&nbsp;
            %s
            <br>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <a href=edit.php>Delete</a>
            <p>
            ',
            $i+1,
            $score->file_descs[$i],
            $score->file_names[$i]
        );
    }
    echo '<hr>
        <form action=edit.php>
        <p>
        Filename: <input name=name>
        <p>
        Description: <input name=desc>
        <p>
        Pages: <input name=pages>
        <p>
        <input type=submit value="Add file">
        </form>
    ';
    form_row_end();

    form_start('edit.php');
    form_select('Publisher', 'publisher', organization_options(), $score->publisher);
    form_select('License', 'license', license_options(), $score->license);
    select2_multi('Languages', 'languages', language_options(), $score->languages);
    form_input_text('Publish date', 'publish_date', $score->publish_date);
    form_input_text('Edition number', 'edition_number', $score->edition_number);
    form_select('Image type', 'image_type', image_type_options(), $score->image_type);
    $x = [
        ['is_parts', 'Separate parts', $score->is_parts],
        ['is_selections', 'Selections', $score->is_selections],
        ['is_vocal', 'Vocal score', $score->is_vocal]
    ];
    form_checkboxes('Attributes', $x);
    form_submit('OK');
    form_end();
    page_tail();
}

function score_action($id) {
    $score_json = urldecode(get_str('score'));
    $score = json_decode($score_json);

    if ($id) {
        $score = DB_score::lookup_id($id);
        if (!$score) error_page("No score $id");
        $q = sprintf(
            "",
        );
        $score->update($q);
    } else {
        $q = sprintf(
            "() values ()",
        );
        $id = DB_score::insert($q);
    }
    header(
        sprintf('Location: item.php?type=%d&id=%d', SCORE, $id)
    );
}

// lists: performers, files
function perf_form($id) {
}

function perf_action($id) {
}

$type = get_str('type', true);
$submit = get_str('submit', true);
$id = get_int('id', true);

switch ($type) {
case LOCATION:
    $submit?location_action($id):location_form($id);
    break;
case COMPOSITION:
    $submit?composition_action($id):composition_form($id);
    break;
case CONCERT:
    $submit?concert_action($id):concert_form($id);
    break;
case ENSEMBLE:
    $submit?ensemble_action($id):ensemble_form($id);
    break;
case ORGANIZATION:
    $submit?organization_action($id):organization_form($id);
    break;
case PERSON:
    $submit?person_action($id):person_form($id);
    break;
case PERSON_ROLE:
    $submit?person_role_action():person_role_form();
    break;
case VENUE:
    $submit?venue_action($id):venue_form($id);
    break;
case INST_COMBO:
    $submit?inst_combo_action():inst_combo_form();
    break;
case SCORE:
    $submit?score_action($id):score_form($id);
    break;
case PERFORMANCE:
    $submit?perf_action($id):perf_form($id);
    break;
default:
    error_page("unknown type $type");
}

?>
