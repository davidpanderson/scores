<?php

// forms and handlers for rating/review/useful

require_once('../inc/util.inc');
require_once('cmi_db.inc');
require_once('ser.inc');

function review_form($type, $target) {
    $user = get_logged_in_user();
    $r = DB_rating::lookup(
        sprintf('user=%d and type=%d and target=%d',
            $user->id, $type, $target
        )
    );
    if ($r) {
        $review = $r->review;
        page_head('Edit review');
    } else {
        page_head('Review');
    }
    switch($type) {
    case COMPOSITION:
        $c = DB_composition::lookup_id($target);
        echo "
            Please write a brief review of $c->long_title,
            perhaps including:
            <ul>
            <li> Why you do or don't like it.
            <li> What it expresses to you.
            <li> Your experiences hearing or playing it.
            </ul>
        ";
        break;
    case PERSON_ROLE:
        $pr = DB_person_role::lookup_id($target);
        $role = role_id_to_name($pr->role);
        $person = DB_person::lookup_id($pr->person);
        echo "
            Please write a brief review of $person->first_name $person->last_name as a $role,
            perhaps including:
            <ul>
            <li> Why you do or don't like their work.
            <li> What their work expresses to you.
            <li> Your experiences hearing or playing their work.
            </ul>
        ";
        break;
    default: error_page('bad type');
    }
    echo 'Text only - no HTML tags.';
    form_start('rate.php');
    form_input_textarea('Review', 'review', $review);
    form_input_hidden('type', $type);
    form_input_hidden('target', $target);
    form_input_hidden('action', 'rev_action');
    form_submit('OK');
    form_end();
    page_tail();
}

function review_action($type, $target) {
    $user = get_logged_in_user();
    $rev = strip_tags(get_str('review'));
    $r = DB_rating::lookup(
        sprintf('user=%d and type=%d and target=%d',
            $user->id, $type, $target
        )
    );
    if ($r) {
        $r->update(sprintf("review='%s'", DB::escape($rev)));
    } else {
        DB_rating::insert(
            sprintf("(created, user, type, target, review) vaues (%d, %d, %d, %d, '%s')",
                time(), $user->id, $type, $target, DB::escape($rev)
            )
        );
    }
    if ($type == PERSON_ROLE) {
        $pr = DB_person_role::lookup_id($target);
        header(
            sprintf('Location: item.php?type=%d&id=%d', PERSON, $pr->person)
        );
    } else {
        header(
            sprintf('Location: item.php?type=%d&id=%d', $type, $id)
        );
    }
}

function do_rate($type, $id, $attr='attr1') {
    $user = get_logged_in_user();
    $val = get_int('val');
    $r = DB_rating::lookup(
        sprintf('user=%d and type=%d and target=%d',
            $user->id, $type, $id
        )
    );
    if ($r) {
        $r->update(
            sprintf('%s=%d, created=%d', $attr, $val, time())
        );
    } else {
        DB_rating::insert(
            sprintf('(created, user, type, target, %s) values (%d, %d, %d, %d, %d)',
                $attr, time(), $user->id, $type, $id, $val
            )
        );
    }
    if ($type == PERSON_ROLE) {
        $pr = DB_person_role::lookup_id($id);
        header(
            sprintf('Location: item.php?type=%d&id=%d', PERSON, $pr->person)
        );
    } else {
        header(
            sprintf('Location: item.php?type=%d&id=%d', $type, $id)
        );
    }
}

$action = get_str('action');
$type = get_int('type', true);
$target = get_int('target');
switch($action) {
case 'rate_comp_1':
    do_rate(COMPOSITION, $target, 'attr1');
    break;
case 'rate_comp_2':
    do_rate(COMPOSITION, $target, 'attr2');
    break;
case 'rate_perf_1':
    do_rate(PERFORMANCE, $target, 'attr1');
    break;
case 'rate_perf_2':
    do_rate(PERFORMANCE, $target, 'attr2');
    break;
case 'rate_score_1':
    do_rate(SCORE, $target, 'attr1');
    break;
case 'rate_score_2':
    do_rate(SCORE, $target, 'attr2');
    break;
case 'rate_pr':
    do_rate(PERSON_ROLE, $target);
    break;
case 'rev_form':
    review_form($type, $target);
    break;
case 'rev_action':
    review_action($type, $target);
    break;
default:
    error_page("No action $action");
}
?>
