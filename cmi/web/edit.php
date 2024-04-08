<?php

// create or edit items

require_once('../inc/util.inc');
require_once('cmi.inc');
require_once('write_ser.inc');

define('BUTTON_CLASS_ADD', 'btn btn-md btn-success py-0');
define('BUTTON_CLASS_REMOVE', 'btn btn-xs btn-warning');

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

    echo sprintf('
        <div class="form-group">
            <label align=right class="%s">%s</label>
            <div class="%s">
        ',
        FORM_LEFT_CLASS, 'Program', FORM_RIGHT_CLASS
    );
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
    echo '</div></div><p>&nbsp;</p>';

    form_start('edit.php');
    form_input_hidden('con', urlencode($con_json));
    form_input_hidden('id', $id);
    form_input_hidden('type', CONCERT);
    form_input_hidden('submit', true);
    form_select('Venue', 'venue', venue_options());
    form_input_text('Date', 'when', '', 'text', 'placeholder="YYYY-MM-DD"');
    form_select('Sponsor', 'organization', organization_options());
    form_submit($id?'Update concert':'Add concert');
    form_end();

    page_tail();
}

function concert_action() {
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

    $id = get_int('id', true);
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

function composition_form($id) {
    $comp = null;
    if ($id) {
        $comp = DB_composition::lookup_id($id);
        $comp->creators = json_decode($comp->creators);
        if ($comp->instrument_combos) {
            $comp->instrument_combos = json_decode($comp->instrument_combos);
        } else {
            $comp->instrument_combos = [];
        }
        if ($comp->comp_types) {
            $comp->comp_types = json_decode($comp->comp_types);
        } else {
            $comp->comp_types = [];
        }
        $comp_json = json_encode($comp);
    } else {
        $comp_json = get_str('comp', true);
        if ($comp_json) {
            $comp_json = urldecode($comp_json);
            $comp = json_decode($comp_json);
        } else {
            $comp = new StdClass;
            $comp->title = '';
            $comp->opus_catalogue = '';
            $comp->creators = [];
            $comp->instrument_combos = [];
            $comp_json = json_encode($comp);
        }
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

    select2_head($id?'Edit composition':'Add composition');
    
    if ($message) echo "$message<p>\n";
    if ($error_msg) echo "$error_msg<p>\n";

    // show page
    //

    // creators
    echo sprintf('
        <div class="form-group">
            <label align=right class="%s">%s</label>
            <div class="%s">
        ',
        FORM_LEFT_CLASS, 'Creators', FORM_RIGHT_CLASS
    );
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
    echo sprintf('
        <p>
        <form action=edit.php>
        <input type=hidden name=type value=%d>
        <input type=hidden name=comp value="%s">
        <input type=hidden name=op value=add_creator>
        <input type=hidden name=id value=%d>
        <input type=submit class="%s" value="Add creator:">
        <input name=prole_code placeholder="role code">
        </form>
        ',
        COMPOSITION,
        urlencode($comp_json), $id,
        BUTTON_CLASS_ADD
    );
    echo '</div></div><p>&nbsp;</p>';

    // inst combos
    echo sprintf('
        <div class="form-group">
            <label align=right class="%s">%s</label>
            <div class="%s">
        ',
        FORM_LEFT_CLASS, 'Instrumentations', FORM_RIGHT_CLASS
    );
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
    echo sprintf('
        <form action=edit.php>
        <input type=hidden name=type value=%d>
        <input type=hidden name=comp value="%s">
        <input type=hidden name=op value=add_ic>
        <input type=hidden name=id value=%d>
        <input type=submit class="%s" value="Add instrumentation:">
        <input name=ic_code placeholder="instrumentation code">
        </form>
        ',
        COMPOSITION,
        urlencode($comp_json), $id,
        BUTTON_CLASS_ADD
    );
    echo '</div></div><p>&nbsp;</p>';

    form_start('edit.php', 'get');
    form_input_hidden('comp', urlencode($comp_json));
    form_input_hidden('type', COMPOSITION);
    form_input_hidden('submit', true);
    form_input_hidden('id', $id);
    form_input_text('Title', 'title', $comp?$comp->title:'');
    form_input_text('Opus', 'opus', $comp?$comp->opus_catalogue:'');
    select2_multi('Composition types', 'comp_types', comp_type_options(),
        $comp->comp_types
    );
    form_submit($id?'Update composition':'Add composition');
    form_end();
    page_tail();
}

function composition_action($id) {
    $comp_json = urldecode(get_str('comp'));
    $comp = json_decode($comp_json);
    $title = get_str('title');
    $opus = get_str('opus');
    $comp_types = get_str('comp_types');

    if (!$comp->creators) {
        error_page("Must specify a composer");
    }
    $prole = DB_person_role::lookup_id($comp->creators[0]);
    $person = DB_person::lookup_id($prole->person);
    $long_title = $title;
    if ($opus) $long_title .= ", $opus";
    $long_title .= " ($person->last_name, $person->first_name)";

    $id = get_int('id', true);
    if ($id) {
        $c = DB_composition::lookup_id($id);
        if (!$c) error_page("No composition $id");
        $q = sprintf(
            "long_title='%s', title='%s', opus_catalogue='%s', creators='%s', instrument_combos='%s', comp_types='%s'",
            DB::escape($long_title),
            DB::escape($title),
            DB::escape($opus),
            json_encode($comp->creators),
            json_encode($comp->instrument_combos),
            json_encode($comp_types)
        );
        $c->update($q);
    } else {
        $q = sprintf(
            "(long_title, title, opus_catalogue, creators, instrument_combos, comp_types) values('%s', '%s', '%s', '%s', '%s', '%s')",
            DB::escape($long_title),
            DB::escape($title),
            DB::escape($opus),
            json_encode($comp->creators),
            json_encode($comp->instrument_combos),
            json_encode($comp_types)
        );
        $id = DB_composition::insert($q);
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

$type = get_str('type', true);
$submit = get_str('submit', true);

switch ($type) {
case LOCATION:
    $id = get_int('id', true);
    $submit?location_action($id):location_form($id);
    break;
case COMPOSITION:
    $id = get_int('id', true);
    $submit?composition_action($id):composition_form($id);
    break;
case CONCERT:
    $submit?concert_action():concert_form();
    break;
case ENSEMBLE:
    $id = get_int('id', true);
    $submit?ensemble_action():ensemble_form();
    break;
case ORGANIZATION:
    $id = get_int('id', true);
    $submit?organization_action($id):organization_form($id);
    break;
case PERSON:
    $submit?person_action():person_form();
    break;
case PERSON_ROLE:
    $submit?person_role_action():person_role_form();
    break;
case VENUE:
    $id = get_int('id', true);
    $submit?venue_action($id):venue_form($id);
    break;
case INST_COMBO:
    $submit?inst_combo_action():inst_combo_form();
    break;
default:
    error_page("unknown type $type");
}

?>
