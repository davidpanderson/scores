<?php

// create or edit items

require_once('../inc/util.inc');
require_once('cmi.inc');
require_once('write_ser.inc');

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

function concert_form() {
    // get concert params, from DB or from URL args
    //
    $con = null;
    $id = get_int('id', true);
    if ($id) {
        $con = DB_concert::lookup_id($id);
        if (!$con) {
            error_page("No concert: $id\n");
        }
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
            $error_msg = "
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
            $error_msg = "
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

    echo '<h3>Program</h3><p>';
    $n = 1;
    foreach ($con->program as $perf_id) {
        $perf = DB_performance::lookup_id($perf_id);
        $comp = DB_composition::lookup_id($perf->composition);
        echo sprintf('<h4>%d) %s</h4>', $n++, composition_str($comp));
        $roles = json_decode($perf->performers);

        echo "<ul>";
        foreach ($roles as $role_id) {
            $role = DB_person_role::lookup_id($role_id);
            echo sprintf("<li> %s\n", person_role_str($role));
        }
        echo "</ul>";
        echo sprintf('
            <p>
            <form action=edit.php>
            <input type=hidden name=type value=concert>
            <input type=hidden name=con value="%s">
            <input type=hidden name=op value=add_perf>
            <input type=hidden name=perf_id value=%d>
            <input type=submit value="Add performer">
            <input title="paste a role code here" name=prole_code placeholder="role code">
            </form>
            ',
            urlencode($con_json), $perf_id
        );
        echo sprintf('
            <p><p>
            <form action=edit.php>
            <input type=hidden name=type value=concert>
            <input type=hidden name=con value="%s">
            <input type=hidden name=perf_id value=%d>
            <input type=hidden name=op value=remove_comp>
            <input type=submit value="Remove composition">
            </form>
            ',
            urlencode($con_json),
            $perf_id
        );
        echo '<hr>';
    }
    echo sprintf('
        <form action=edit.php>
        <input type=hidden name=type value=concert>
        <input type=hidden name=con value="%s">
        <input type=hidden name=op value=add_comp>
        <input type=submit value="Add composition">
        <input name=comp_code placeholder="composition code">
        </form>
        ',
        urlencode($con_json)
    );
    echo '<h3>Venue</h3>';
    echo '<h3>Date/time</h3>';
    echo '<h3>Sponsor</h3>';
    echo 
    page_tail();
}

function concert_action() {
// clear tentative from performances
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
    form_input_hidden('type', 'location');
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

function person_form() {
    $id = get_int('id', true);
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
    form_input_hidden('type', 'person');
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
    form_submit('OK');
    form_end();
    page_tail();
}

function person_action() {
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
    $id = get_int('id', true);
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
    header("Location: item.php?type=person&id=$id");
}
function ensemble_form() {
    page_head('Ensemble');
    form_start('edit.php');
    form_input_text('Name', 'name');
    form_select('Type', 'type', ensemble_type_options());
    form_end();
    page_tail();
}
function ensemble_action() {
}
function person_role_form() {
    $pid = get_int('person_id');
    $person = DB_person::lookup_id($pid);
    if (!$person) error_page("No person $pid");

    page_head("Add role for $person->first_name $person->last_name");
    form_start('edit.php');
    form_input_hidden('type', 'person_role');
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
    header("Location: item.php?type=person&id=$person_id");
}

$type = get_str('type', true);
$submit = get_str('submit', true);

switch ($type) {
case 'location':
    $id = get_int('id', true);
    $submit?location_action($id):location_form($id);
    break;
case 'concert':
    $submit?concert_action():concert_form();
    break;
case 'person':
    $submit?person_action():person_form();
    break;
case 'ensemble':
    $submit?ensemble_action():ensemble_form();
    break;
case 'person_role':
    $submit?person_role_action():person_role_form();
    break;
default:
    error_page("unknown type $type");
}

?>
