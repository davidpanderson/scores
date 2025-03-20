<?php

// forms and handlers for rating/review/useful
//
// things that can be rated:
// composition
// score
// performance
// person_role

require_once('../inc/util.inc');
require_once('cmi_db.inc');
require_once('ser.inc');
require_once('cmi.inc');

function review_form($type, $target) {
    $user = get_logged_in_user();
    $r = DB_rating::lookup(
        sprintf('user=%d and type=%d and target=%d',
            $user->id, $type, $target
        )
    );
    $review = '';
    if ($r) {
        $review = $r->review;
    }
    if ($review) {
        page_head('Edit review');
    } else {
        page_head('Write review');
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
            Please write a brief review of $person->first_name $person->last_name as $role,
            perhaps including:
            <ul>
            <li> Why you do or don't like their work.
            <li> What their work expresses to you.
            <li> Your experiences hearing or playing their work.
            </ul>
        ";
        break;
    case PERFORMANCE:
        $perf = DB_performance::lookup_id($target);
        $comp = DB_composition::lookup_id($perf->composition);
        $c = composition_str($comp);
        $performers = creators_str(json_decode2($perf->performers), false);
        echo "
            Please write a brief review of the performance of $c by $performers,
            perhaps including:
            <ul>
            <li> Why you do or don't like it.
            <li> What it expresses to you.
            <li> Your experiences hearing or playing it.
            </ul>
        ";
        break;
    case SCORE:
        echo "
            Please write a brief review of this score,
            perhaps including:
            <ul>
            <li> Its readability.
            <li> The quality of the editing.
            </ul>
        ";
        break;
    default: error_page('bad type');
    }
    echo '<p>Text only - no HTML tags.';
    form_start('rate.php');
    form_input_textarea('Review', 'review', $review);
    form_input_hidden('type', $type);
    form_input_hidden('target', $target);
    form_input_hidden('action', 'rev_action');
    form_submit2('OK');
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
            sprintf('Location: item.php?type=%d&id=%d', $type, $target)
        );
    }
}

// update the item's rating stats
//
function update_ratings($type, $id, $attr, $old, $new) {
    $nratings = "nratings$attr";
    $rating_sum = "rating_sum$attr";
    if ($old == NO_RATING) {
        $q = "$nratings=$nratings+1, $rating_sum=$rating_sum+$new";
    } else if ($new == NO_RATING) {
        $q = "$nratings=$nratings-1, $rating_sum=$rating_sum-$old";
    } else {
        $diff = $new - $old;
        $q = "$rating_sum=$rating_sum+$diff";
    }
    switch ($type) {
    case COMPOSITION:
        $item = new DB_composition;
        break;
    case SCORE:
        $item = new DB_score;
        break;
    case PERFORMANCE:
        $item = new DB_performance;
        break;
    case PERSON_ROLE:
        $item = new DB_person_role;
        break;
    }
    $item->id = $id;
    $item->update($q);
}

function do_rate($type, $id, $attr) {
    $user = get_logged_in_user();
    $val = get_int('val');
    $r = DB_rating::lookup(
        sprintf('user=%d and type=%d and target=%d',
            $user->id, $type, $id
        )
    );
    if ($r) {
        $field = "attr$attr";
        update_ratings($type, $id, $attr, $r->$field, $val);
        $r->update(
            sprintf('attr%d=%d, created=%d', $attr, $val, time())
        );
    } else {
        update_ratings($type, $id, $attr, NO_RATING, $val);
        DB_rating::insert(
            sprintf('(created, user, type, target, attr%d) values (%d, %d, %d, %d, %d)',
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
    do_rate(COMPOSITION, $target, 1);
    break;
case 'rate_comp_2':
    do_rate(COMPOSITION, $target, 2);
    break;
case 'rate_perf_1':
    do_rate(PERFORMANCE, $target, 1);
    break;
case 'rate_perf_2':
    do_rate(PERFORMANCE, $target, 2);
    break;
case 'rate_score_1':
    do_rate(SCORE, $target, 1);
    break;
case 'rate_score_2':
    do_rate(SCORE, $target, 2);
    break;
case 'rate_pr':
    do_rate(PERSON_ROLE, $target, 1);
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
