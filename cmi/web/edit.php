<?php

// create or edit items

require_once('../inc/util.inc');
require_once('cmi.inc');

function concert_form() {
    form_start('edit.php', 'get');
    // venue
    // date/time
    // organization
    // performers
    // program: piece, performers
    // 'update' button: after add new items elsewhere
    form_end();
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
    form_select('Type', 'loc_type', location_type_options(), $loc->type);
    form_select('Parent', 'parent', location_options(), $loc->parent);
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
}

$type = get_str('type', true);
$id = get_int('id', true);
$submit = get_str('submit', true);

switch ($type) {
case 'location':
    $submit?location_action($id):location_form($id);
    break;
default:
    error_page("unknown type $type");
}

?>
