<?php

require_once('../inc/util.inc');
require_once('../inc/more.inc');

define('DIFFICULTY_TERMS', [
    'extremely easy',
    'very easy',
    'easy',
    'fairly easy',
    'slightly easy',
    'medium',
    'slightly difficult',
    'fairly difficult',
    'difficult',
    'very difficult',
    'extremely difficult'
]);

define ('QUALITY_TERMS', [
    'terrible',
    'not good',
    'mediocre',
    'a few good parts',
    'some good parts',
    'average',
    'above average',
    'fairly good',
    'good',
    'very good',
    'excellent',
]);

function rating_buttons($is_diff, $sel, $action, $id) {
    $terms = $is_diff?DIFFICULTY_TERMS:QUALITY_TERMS;
    $x = '<nobr>';
    for ($i=0; $i<=10; $i++) {
        if ($i === $sel) {
            $x .= sprintf(
                '<button title="%s" style="height:%dpx;width:%dpx;font-size:%dpx;background-color:palegreen;border-width:.1px">%d</button>',
                $terms[$i], 20, 24, 10, $i
            );
        } else {
            $x .= sprintf(
                '<a href=rate.php?action=%s&target=%d&val=%d><button title="%s" style="height:%dpx;width:%dpx;font-size:%dpx">%d</button></a>',
                $action, $id, $i,
                $terms[$i], 20, 24, 10, $i
            );
        }
    }
    return $x.'</nobr>';
}

function rating_bar($frac) {
    if ($frac === null) return '---';
    $width = 264;   // 11*24
    $n = (int)$width*$frac;
    return sprintf('
        <table>
            <tr>
                <td width=%d align=center bgcolor=palegreen>%.1f</td>
                <td width=%d bgcolor=lightgray></td>
            </tr>
        </table>
        ',
        $n, $frac*10,
        $width - $n
    );
}

function avg_rating($type, $target) {
    $rs = DB_rating::enum(
        sprintf('type=%d and target=%d', $type, $target)
    );
    if (!$rs) return null;
    $qsum = 0;
    $qn = 0;
    $dsum = 0;
    $dn = 0;
    foreach ($rs as $r) {
        if ($r->quality !== null) {
            $qsum += $r->quality;
            $qn++;
        }
        if ($r->attr2 !== null) {
            $dsum += $r->attr2;
            $dn++;
        }
    }
    return [
        $qn?.1*$qsum/$qn:null,
        $dn?.1*$dsum/$dn:null
    ];
}

function show_review($rev) {
    show_text_more($rev, 80);
}

function get_users_ratings($user, $type, $target) {
    $rq = null;
    $rd = null;
    $review = null;
    $r = DB_rating::lookup(
        sprintf('user=%d and type=%d and target=%d',
            $user->id, $type, $target
        )
    );
    if ($r) {
        if ($r->quality !== null) $rq = (int)$r->quality;
        if ($r->attr2 !== null) $rd = (int)$r->attr2;
        $review = $r->review;
    }
    return [$rq, $rd, $review];
}

function show_reviews($type, $target, $user, $review) {
    echo '<b>Reviews</b><p>';
    start_table();
    if ($user) {
        $url = "rate.php?action=rev_form&type=$type&target=$target";
        if ($review) {
            row2_init(
                sprintf('Your review<br>%s',
                    button_text($url, 'edit', 'Edit your review', ' btn-success btn-xs')
                )
            );
            show_review($review);
            echo '</td></tr>';
        } else {
            row2('', button_text($url, 'Write a review'));
        }
    }
    $revs = DB_rating::enum(
        "type=$type and target=$target and review<>''"
    );
    foreach ($revs as $rev) {
        if ($user && $user->id == $rev->user) continue;
        $u = BOINCUser::lookup_id($rev->user);
        echo "<href=cmi_user.php?id=$rev->user>$user->name</a>";
        show_review($rev->review);
    }
    end_table();
}

// right panel for composition: rating and review stuff
//
function comp_right($arg) {
    $user = get_logged_in_user(true);
    [$c, $par] = $arg;
    if ($user) {
        [$rq, $rd, $review] = get_users_ratings($user, COMPOSITION, $c->id);
    }
    echo '<b>Composition quality</b>';
    start_table();
    if ($user) {
        row2('Your rating',
            rating_buttons(false, $rq, 'rate_comp_q', $c->id)
        );
    }
    [$qavg, $davg] = avg_rating(COMPOSITION, $c->id);
    row2('Average rating', rating_bar($qavg));
    end_table();
    echo '<b>Technical difficulty</b>';
    start_table();
    if ($user) {
        row2('Your rating', rating_buttons(true, $rd, 'rate_comp_d', $c->id));
    }
    row2('Average rating', rating_bar($davg));
    end_table();
    show_reviews(COMPOSITION, $c->id, $user, $review);
    echo '
        <p>
        <b>People who liked this also liked:</b>
        <p>
    ';
    show_recommended_comps($c);
}

// TODO: use data.  Show only unrated comps
//
function show_recommended_comps($c) {
    $comps = DB_composition::enum("id <> $c->id", 'order by rand() limit 3');
    echo '<ul>';
    foreach ($comps as $comp) {
        show_recommended_comp($comp);
    }
    echo '</ul>';
}

function show_recommended_comp($c) {
    echo sprintf('<li> <a href=item.php?type=%d&id=%d>%s</a>',
        COMPOSITION, $c->id, composition_str($c)
    );
}

// right panel for person
//
function person_right($p) {
    $user = get_logged_in_user(true);
    $prs = DB_person_role::enum("person=$p->id");
    foreach ($prs as $pr) {
        $role = DB_role::lookup_id($pr->role);
        echo "<b>As $role->name</b>";
        start_table();
        if ($user) {
            [$rq, $rd, $review] = get_users_ratings($user, PERSON_ROLE, $pr->id);
            row2('Your rating', rating_buttons(true, $rq, 'rate_pr', $pr->id));
        }
        [$qavg, $davg] = avg_rating(PERSON_ROLE, $pr->id);
        row2('Average rating', rating_bar($qavg));
        end_table();
        show_reviews(PERSON_ROLE, $pr->id, $user, $review);
        echo sprintf(
            '<p><b>People who liked %s %s as %s also liked:</b><p>',
            $p->first_name, $p->last_name, $role->name
        );
        show_recommended_prs($p, $role);
        echo '<hr>';
    }
}

// TODO: use data.  Show only unrated items
//
function show_recommended_prs($p, $role) {
    $prs = DB_person_role::enum(
        sprintf('role=%d', $role->id),
        'order by rand() limit 3'
    );
    echo '<ul>';
    foreach ($prs as $pr) {
        show_recommended_pr($pr);
    }
    echo '</ul>';
}

function show_recommended_pr($pr) {
    $person = DB_person::lookup_id($pr->person);
    echo sprintf('<li><a href=item.php?type=%d&id=%d>%s %s</a>',
        PERSON, $pr->person, $person->first_name, $person->last_name
    );
}

?>
