<?php

// forms and handlers for rating/review/useful

require_once('../inc/util.inc');
require_once('cmi_db.inc');

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
    default: error_page('bad type');
    }
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
    $rev = get_str('review');
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
    header(
        sprintf('Location: item.php?type=%d&id=%d', $type, $target)
    );
}

function do_comp($id, $is_diff) {
    $user = get_logged_in_user();
    $val = get_int('val');
    $r = DB_rating::lookup(
        sprintf('user=%d and type=%d and target=%d',
            $user->id, COMPOSITION, $id
        )
    );
    if ($r) {
        $r->update(
            sprintf('%s=%d, created=%d',
                $is_diff?'difficulty':'quality',
                $val, time()
            )
        );
    } else {
        DB_rating::insert(
            sprintf('(created, user, type, target, %s) values (%d, %d, %d, %d, %d)',
                $is_diff?'difficulty':'quality',
                time(), $user->id, COMPOSITION, $id, $val
            )
        );
    }
    header(
        sprintf('Location: item.php?type=%d&id=%d', COMPOSITION, $id)
    );
}

$action = get_str('action');
$type = get_int('type', true);
$target = get_int('target');
switch($action) {
case 'comp_q':
    do_comp($target, false);
    break;
case 'comp_d':
    do_comp($target, true);
    break;
case 'rev_form':
    review_form($type, $target);
    break;
case 'rev_action':
    review_action($type, $target);
    break;
}
?>
