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
        <p>
        Please go back and correct this problem.
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
        <p>
        Please go back and correct this problem.
    ";
}

function ic_code_msg() {
    return "
        <p class=\"text-danger h4\">
        Invalid or missing instrumentation code.
        </p>
        <p>
        To get an instrumentation code:
        <ul>
        <li> In a different browser tab,
            locate the instrumentation you want (or create it).
        <li> Click on the Copy button next to the instrumentation;
            this copies the code to the clipboard.
        <li> Paste the instrumentation code (e.g. <code>ins1234</code>) into the form.
        </ul>
        <p>
        Please go back and correct this problem.
    ";
}

///////////////  CONCERT /////////////////

function empty_concert() {
    $con = new StdClass;
    $con->id = 0;
    $con->_when = '';
    $con->venue = 0;
    $con->organization = 0;
    $con->program = [];
    return $con;
}

function concert_form($id) {
    if ($id) {
        $con = DB_concert::lookup_id($id);
        if (!$con) error_page("No concert: $id\n");
        $con->program = json_decode($con->program);
    } else {
        $con = empty_concert();
    }

    page_head($id?'Edit concert':'Add concert');

    form_start('edit.php');
    form_input_hidden('id', $id);
    form_input_hidden('type', CONCERT);
    form_input_hidden('submit', true);

    form_row_start('Program');
    $n = 0;
    //echo '<table>';
    start_table();
    table_header('Title', 'Performers', 'Remove');
    foreach ($con->program as $perf_id) {
        $perf = DB_performance::lookup_id($perf_id);
        $comp = DB_composition::lookup_id($perf->composition);
        $roles = json_decode($perf->performers);

        echo '<tr><td valign=top >';
        echo composition_str($comp);
        echo '</td><td>';

        //echo '<table>';
        start_table();
        table_header('Name', 'Role', 'Remove');
        foreach ($roles as $role_id) {
            $prole = DB_person_role::lookup_id($role_id);
            [$name, $role] = person_role_array($prole);
            table_row(
                $name,
                $role,
                sprintf('<input type=checkbox name=remove_role_%d_%d>',
                    $n, $role_id
                )
            );
        }
        echo "</table>";

        echo 'Add performers ';

        echo sprintf(
            '<input title="paste role codes here" name=add_roles_%d placeholder="role codes">',
            $n
        );
        if ($n>0) {
            echo sprintf(
                '<p>
                Copy performers from previous composition <input type=checkbox name=copy_perfs_%d>',
                $n
            );
        }
        echo '<td>';
        echo sprintf('<input type=checkbox name=remove_perf_%d>', $perf_id);
        echo '</td></tr>';
        $n++;
    }
    echo '</table>';

    echo 'Add compositions: <input name=add_comps placeholder="composition codes">';
    form_row_end();

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
    if ($id) {
        $con = DB_concert::lookup_id($id);
        if (!$con) error_page("No concert: $id\n");
        $con->program = json_decode($con->program);
    } else {
        $con = empty_concert();
    }

    // handle changes to performances

    $perfs = [];
    foreach ($con->program as $perf_id) {
        $perf = DB_performance::lookup_id($perf_id);
        $perf->performers = json_decode($perf->performers);
        $perf->modified = false;
        $perfs[] = $perf;
    }

    // for each performance, handle
    // - add roles from previous perf
    // - delete roles
    // - add roles
    // don't worry about dups; unique when done
    //
    for ($i=0; $i<count($con->program); $i++) {
        $perf = $perfs[$i];
        if ($i>0) {
            if (get_str("copy_perfs_$i", true)) {
                $prev = $perfs[$i-1];
                $perf->performers = array_merge($perf->performers, $prev->performers);
                $perf->modified = true;
            }
        }
        foreach ($perf->performers as $pid) {
            if (get_str(
                sprintf('remove_role_%d_%d', $i, $pid),
                true
            )) {
                $perf->performers = array_diff($perf->performers, [$pid]);
                $perf->modified = true;
            }
        }
        $role_codes = get_str("add_roles_$i", true);
        if ($role_codes) {
            $role_codes = explode(' ', $role_codes);
            foreach ($role_codes as $role_code) {
                $role_id = parse_code($role_code, 'person_role');
                if (!$role_id) error_page(role_code_msg());
                $perf->performers[] = $role_id;
            }
            $perf->modified = true;
        }
    }

    foreach ($perfs as $perf) {
        if ($perf->modified) {
            $perf->performers = array_unique($perf->performers);
            $perf->update(
                sprintf(
                    "performers='%s'",
                    json_encode($perf->performers, JSON_NUMERIC_CHECK)
                )
            );
        }
    }

    // handle removal of performances

    foreach ($con->program as $perf_id) {
        $x = "remove_perf_$perf_id";
        if (get_str($x, true)) {
            $con->program = array_diff($con->program, [$perf_id]);
        }
        // could delete the perf
    }

    // handle addition of new performances

    $comp_codes = get_str('add_comps', true);
    if ($comp_codes) {
        $comp_codes = explode(' ', $comp_codes);
        foreach ($comp_codes as $comp_code) {
            $comp_id = parse_code($comp_code, 'composition');
            $comp = DB_composition::lookup_id($comp_id);
            if (!$comp) error_page(comp_code_msg());

            // see if comp is already in program
            foreach ($con->program as $perf_id) {
                $perf = DB_performance::lookup_id($perf_id);
                if ($perf->composition == $comp_id) {
                    error_page('That composition is already in the program.');
                }
            }

            $perf_id = DB_performance::insert(
                sprintf("(composition, performers, tentative) values (%d, '%s',1)",
                    (int)$comp_id,
                    json_encode([])
                )
            );
            $con->program[] = $perf_id;
        }
    }

    $venue = get_int('venue');
    $when = get_str('when', true);
    if ($when) {
        $when = DB::date_num_parse($when);
    } else {
        $when = 0;
    }
    $organization = get_int('organization');

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
    exit;
    header(
        sprintf('Location: item.php?type=%d&id=%d', CONCERT, $id)
    );
}

///////////////  LOCATION  /////////////////

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

///////////////  PERSON  /////////////////

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

///////////////  ENSEMBLE  /////////////////

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

///////////////  PERSON_ROLE  /////////////////

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

///////////////  VENUE  /////////////////

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

///////////////  ORGANIZATION  /////////////////

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

///////////////  COMPOSITION  /////////////////

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
    $is_section = false;
    $is_arrangement = false;

    // expand lists etc.

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
    } else {
        $comp_json = get_str('comp', true);
        if ($comp_json) {
            $comp_json = urldecode($comp_json);
            $comp = json_decode($comp_json);
        } else {
            $comp = empty_composition();
            $parent = get_int('parent', true);
            if ($parent) {
                $comp->parent = $parent;
            }
            $arrangement_of = get_int('arrangement_of', true);
            if ($arrangement_of) {
                $comp->arrangement_of = $arrangement_of;
            }
        }
        if ($comp->parent) {
            $parent_comp = DB_composition::lookup_id($comp->parent);
            $is_section = true;
        }
        if ($comp->arrangement_of) {
            $parent_comp = DB_composition::lookup_id($comp->arrangement_of);
            $is_arrangement = true;
        }
    }

    $comp_json = json_encode($comp);

    // show page
    //

    if ($is_section) {
        $x = sprintf('section of %s', $parent_comp->title);
    } else if ($is_arrangement) {
        $x = sprintf('arrangement of %s', $parent_comp->title);
    } else {
        $x = 'composition';
    }
    select2_head($id?"Edit $x":"Add $x");
    
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

    // things only main comps and arrangements have
    //
    if (!$is_section) {
        // creators
        form_row_start('Creators');
        if ($comp->creators) {
            echo '<table width="50%"';
            table_header('Name', 'Role', 'Remove');
            foreach ($comp->creators as $prole_id) {
                $prole = DB_person_role::lookup_id($prole_id);
                $person = DB_person::lookup_id($prole->person);
                table_row(
                    "$person->first_name $person->last_name",
                    role_id_to_name($prole->role),
                    "<input type=checkbox name=remove_creator_$prole_id>"
                );
            }
            echo '</table>';
        } else {
            echo dash('');
        }
        echo '
            <p><p>
            Add creators:
            <input name=prole_codes placeholder="role code(s)" >
        ';
        form_row_end();

        // inst combos
        //
        form_row_start('Instrumentations');
        if ($comp->instrument_combos) {
            echo '<table width="50%"';
            table_header('Name', 'Remove');
            foreach ($comp->instrument_combos as $icid) {
                $ic = DB_instrument_combo::lookup_id($icid);
                table_row(
                    instrument_combo_str($ic),
                    "<input type=checkbox name=remove_ic_$icid>"
                );
            }
            echo '</table>';
        }
        if (!$comp->instrument_combos) echo dash('');
        echo '
            <p><p>
            Add instrumentation:
            <input name=ic_codes placeholder="instrumentation code(s)">
        ';
        form_row_end();

        form_input_text('Composed', 'composed', DB::date_num_to_str($comp->composed));
        form_input_text('Published', 'published', DB::date_num_to_str($comp->published));
    }

    // things only main comps can have
    //
    if (!$is_section && !$is_arrangement) {
        form_input_text('Dedication', 'dedication', $comp->dedication);
    }

    // things only main comps and sections can have
    //
    if (!$is_arrangement) {
        form_input_text('Time signatures', 'time_signatures', $comp->time_signatures);
    }

    // things they all can have
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
    $opus = get_str('opus', true);

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
                error_page("A section named $title already exists.
                    Please go back and use a different title.
                ");
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

    foreach ($comp->creators as $prole_id) {
        $x = "remove_creator_$prole_id";
        if (get_str($x, true)) {
            $comp->creators = array_diff($comp->creators, [$prole_id]);
        }
    }

    $prole_codes = get_str('prole_codes', true);
    if ($prole_codes) {
        $prole_codes = explode(' ', $prole_codes);
        foreach ($prole_codes as $prole_code) {
            $prole_id = parse_code($prole_code, 'person_role');
            if (!$prole_id) {
                error_page(person_role_code_msg());
            }
        }
        if (in_array($prole_id, $comp->creators)) {
            error_page('Duplicate creator');
        } else {
            $comp->creators[] = $prole_id;
        }
    }
    foreach ($comp->instrument_combos as $ic_id) {
        $x = "remove_id_$ic_id";
        if (get_str($x, true)) {
            $comp->instrument_combos = array_diff($comp->instrument_combos, [$ic_id]);
        }
    }
    $ic_codes = get_str('ic_codes', true);
    if ($ic_codes) {
        $ic_codes = explode(' ', $ic_codes);
        foreach ($ic_codes as $ic_code) {
            $ic_id = parse_code($ic_code, 'inst_combo');
            if (!$ic_id) error_page(ic_code_msg());
            if (in_array($ic_id, $comp->instrument_combos)) {
                error_page('Duplicate instrumentation');
            } else {
                $comp->instrument_combos[] = $ic_id;
            }
        }
    }

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
            json_encode($comp->creators, JSON_NUMERIC_CHECK),
            json_encode($comp->instrument_combos, JSON_NUMERIC_CHECK),
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

///////////////  INSTRUMENT_COMBO  /////////////////

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

///////////////  SCORE  /////////////////

function empty_score() {
    $score = new StdClass;
    $score->id = 0;
    $score->compositions = [];
    $score->files = [];
    $score->publisher = 0;
    $score->license = 0;
    $score->languages = [];
    $score->publish_date = 0;
    $score->edition_number = '';
    $score->image_type = '';
    $score->is_parts = 0;
    $score->is_selections = 0;
    $score->is_vocal = 0;
    return $score;
}

function score_form($id) {
    $score = null;
    if ($id) {
        $score = DB_score::lookup_id($id);
        $score->compositions = json_decode($score->compositions);
        $score->files = json_decode($score->files);
        $score->languages = json_decode($score->languages);
    } else {
        $score_json = get_str('score', true);
        if ($score_json) {
            $score_json = urldecode($score_json);
            $score = json_decode($score_json);
        } else {
            $score = empty_score();
            $comp_id = get_int('comp_id', true);
            if ($comp_id) {
                $score->compositions = [$comp_id];
            }
        }
    }

    $score_json = json_encode($score);

    // show page

    select2_head($id?'Edit score':'Add score');

    form_start('edit.php');
    form_input_hidden('score', urlencode($score_json));
    form_input_hidden('type', SCORE);
    form_input_hidden('submit', true);
    form_input_hidden('id', $id);

    form_row_start('Compositions');
    if ($score->compositions) {
        echo '<table width="50%"';
        table_header('Name', 'Role', 'Remove');
        foreach ($score->compositions as $cid) {
            $comp = DB_composition::lookup_id($cid);
            table_row(
                composition_str($comp),
                "<input type=checkbox name=remove_comp_$cid>"
            );
        }
    } else {
        echo dash();
    }
    echo '
        <p><p>
        Add composition:
        <input name=comp_code placeholder="Composition code(s)">
        <p>
    ';
    form_row_end();

    form_row_start('Files');
    if ($score->files) {
        echo '<table width="50%"';
        table_header('Description', 'Name', 'Remove');
        foreach ($score->files as $file) {
            table_row(
                $file->desc,
                $file->name,
                "<input type=checkbox name=remove_file_$i>"
            );
        }
    } else {
        echo dash();
    }
    echo '
        <p><p>
        Add file:
        <input name=add_file_name placeholder="File name">
        <input name=add_file_desc placeholder="Description">
        <input name=add_file_pages placeholder="# pages">
    ';
    form_row_end();

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
    form_submit($id?'Update score':'Add score');
    form_end();
    page_tail();
}

function score_action($id) {
    $score_json = urldecode(get_str('score'));
    $score = json_decode($score_json);

    $publisher = get_int('publisher', true);
    $license = get_int('license', true);
    $languages = get_str('languages', true);
    if (!$languages) $languages=[];
    $publish_date = get_int('publish_date', true);
    $edition_number = get_str('edition_number', true);
    $image_type = get_str('image_type', true);
    $is_parts = get_int('is_parts', true);
    $is_selections = get_int('is_selections', true);
    $is_vocal = get_int('is_vocal', true);

    foreach ($score->compositions as $comp_id) {
        $x = "remove_comp_$comp_id";
        if (get_str($x, true)) {
            $score->compositions = array_diff($score->compositions, [$comp_id]);
        }
    }
    $comp_codes = get_str('comp_codes', true);
    if ($comp_codes) {
        $comp_codes = explode(' ', $comp_codes);
        foreach ($comp_codes as $comp_code) {
            $comp_id = parse_code($comp_code, 'composition');
            if (!$comp_id) {
                error_page(comp_code_msg());
            }
            if (in_array($comp_id, $score->compositions)) {
                error_page("Duplicate composition");
            }
            $score->compositions[] = $comp_id;
        }
    }

    $new_files = [];
    for ($i=0; $i<$score->files; $i++) {
        $x = "remove_file_$i";
        if (!get_str($x, true)) {
            $new_files[] = $score->files[$i];
        }
    }
    $score->files = $new_files;
    $fdesc = get_str('add_file_desc', true);
    if ($fdesc) {
        // todo: check for unique
        $fname = get_str('add_file_name', true);
        $fpages = get_int('add_file_pages', true);
        $x = new StdClass;
        $x->name = $fname;
        $x->desc = $fdesc;
        $x->pages = $fpages;
        $score->files[] = $x;
    }

    if ($id) {
        $s = DB_score::lookup_id($id);
        if (!$s) error_page("No score $id");
        $q = sprintf(
            "compositions='%s', files='%s', publisher=%d, license=%d, languages='%s', publish_date=%d, edition_number='%s', image_type='%s', is_parts=%d, is_selections=%d, is_vocal=%d",
            json_encode($score->compositions, JSON_NUMERIC_CHECK),
            json_encode($score->files, JSON_NUMERIC_CHECK),
            $publisher,
            $license,
            json_encode($languages, JSON_NUMERIC_CHECK),
            $publish_date,
            DB::escape($edition_number),
            DB::escape($image_type),
            $is_parts,
            $is_selections,
            $is_vocal
        );
        $s->update($q);
    } else {
        $q = sprintf(
            "(compositions, files, publisher, license, languages, publish_date, edition_number, image_type, is_parts, is_selections, is_vocal) values ('%s', '%s', %d, %d, '%s', %d, '%s', '%s', %d, %d, %d)",
            json_encode($score->compositions, JSON_NUMERIC_CHECK),
            json_encode($score->files, JSON_NUMERIC_CHECK),
            $publisher,
            $license,
            json_encode($languages, JSON_NUMERIC_CHECK),
            $publish_date,
            DB::escape($edition_number),
            DB::escape($image_type),
            $is_parts,
            $is_selections,
            $is_vocal
        );
        $id = DB_score::insert($q);
    }
    //echo $q; exit;
    header(
        sprintf('Location: item.php?type=%d&id=%d', SCORE, $id)
    );
}

///////////////  PERFORMANCE  /////////////////

function empty_perf() {
    $x = new StdClass;
    $x->composition = 0;
    $x->performers = [];
    $x->tentative = 0;
    $x->is_recording = 1;
    $x->files = [];
    $x->is_synthesized = 0;
    $x->section = '';
    $x->instrumentation = '';
    return $x;
}

// performance (e.g. recording)
// lists: performers, files
//
function perf_form($id) {
    if ($id) {
        $perf = DB_performance::lookup_id($id);
    } else {
        $perf_json = get_str('perf', true);
        if ($perf_json) {
            $perf_json = urldecode($perf_json);
            $perf = json_decode($perf_json);
        } else {
            $perf = empty_perf();
        }
    }

    // do edits
    //
    $message = '';
    $error_msg = '';
    $op = get_str('op', true);
    switch ($op) {
    case 'add_performer':
        $prole_code = get_str('prole_code', true);
        $prole_id = parse_code($prole_code, 'person_role');
        if (!$prole_id) {
            $error_msg = person_role_code_msg();
            break;
        }
        if (in_array($prole_id, $perf->performers)) {
            $error_msg = 'Duplicate performer';
        } else {
            $perf->performers[] = $prole_id;
            $message = 'Added performer';
        }
        break;
    case 'remove_performer':
        $prole_id = get_int('prole_id');
        $perf->performers= array_diff($perf->performers, [$prole_id]);
        $message = 'Removed performer';
        break;
    case 'add_files':
        break;
    case 'remove_files':
        break;
    default:
        error_page("Bad op $op");
    }

    $perf_json = json_encode($perf);

    // show page
    //

    page_head($id?'Edit recording':'Add recording');

    // main form
    form_start('edit.php');
    form_input_hidden('perf', urlencode($perf_json));
    form_input_hidden('type', PERFORMANCE);
    form_input_hidden('submit', true);
    form_input_hidden('id', $id);

    form_input_text('Instrumentation', 'instrumentation', $perf->instrumentation);
    form_submit($id?'Update recording':'Add recording');
    page_tail();
}

function perf_action($id) {
    $perf_json = urldecode(get_str('perf'));
    $perf = json_decode($perf_json);

    $id = get_int('id', true);
    if ($id) {
        $p = DB_performance::lookup_id($id);
        if (!$p) error_page("No performance $id");
        $q = sprintf(
            "",
        );
        $p->update($q);
    } else {
        $q = sprintf(
            "() values ()",
        );
        $id = DB_performance::insert($q);
    }

    header(
        sprintf('Location: item.php?type=%d&id=%d', PERFORMANCE, $id)
    );
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
