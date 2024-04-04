<?php

// forms and handlers for rating/review/useful

require_once('../inc/util.inc');
require_once('cmi_db.inc');

function comp_review_form($id) {
    $user = get_logged_in_user();
    $c = DB_composition::lookup_id($id);
    $review = '';
    $r = DB_comp_rating::lookup("user=$user->id&id=$id");
    if ($r) {
        $review = $r->review;
    }
    page_head('Review');
    echo "
        Please write a brief review of $c->long_title,
        perhaps including:
        <ul>
        <li> Why you do or don't like it.
        <li> What it expresses to you.
        <li> Your experiences hearing or playing it.
        </ul>
    ";
    form_start('rate.php');
    form_input_textarea('review', $review);
    form_submit('OK');
    form_end();
    page_tail();
}

function comp_review_action($id) {
    $user = get_logged_in_user();
    $rev = get_str('review');
    header("Location: item.php?type=composition&id=$id");
    $r = DB_comp_rating::lookup("user=$user->id and composition=$id");
    if ($r) {
        $r->update(sprintf("review='%s'", DB_escape($rev)));
    } else {
        DB_comp_rating::insert(
            sprintf("(created, user, composition, review) vaues (%d, %d, %d, '%s')",
                time(), $user->id, $id, DB::escape($rev)
            )
        );
    }
    header("Location: item.php?type=composition&id=$id");
}

function do_comp($id, $is_diff) {
    $user = get_logged_in_user();
    $val = get_int('val');
    $r = DB_comp_rating::lookup("user=$user->id and composition=$id");
    if ($r) {
        $r->update(
            sprintf('%s=%d, created=%d',
                $is_diff?'difficulty':'quality',
                $val, time()
            )
        );
    } else {
        DB_comp_rating::insert(
            sprintf('(created, user, composition, %s) values (%d, %d, %d, %d)',
                $is_diff?'difficulty':'quality',
                time(), $user->id, $id, $val
            )
        );
    }
    header("Location: item.php?type=composition&id=$id");
}

$action = get_str('action');
$id = get_int('id');
switch($action) {
case 'comp_q':
    do_comp($id, false);
    break;
case 'comp_d':
    do_comp($id, true);
    break;
case 'comp_rev_form':
    comp_review_form($id);
    break;
case 'comp_rev_action':
    comp_review_action($id);
    break;
}
?>
