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

define ('COMPOSITION_TERMS', [
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

define ('QUALITY_TERMS', [
    'terrible',
    'bad',
    'mediocre',
    'barely OK',
    'decent',
    'average',
    'above average',
    'fairly good',
    'good',
    'very good',
    'excellent',
]);

function rating_buttons($terms, $sel, $action, $id) {
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

function rating_bar($val, $width=240) {
    if ($val == NO_RATING) return dash('');
    $frac = $val/10;
    $n = (int)$width*$frac;
    return sprintf('
        <table width=%d>
            <tr>
                <td width=%d align=center bgcolor=palegreen>%.1f</td>
                <td width=%d bgcolor=lightgray></td>
            </tr>
        </table>
        ',
        $width*1.1,
        ($width/10)+$n, $frac*10,
        $width - $n
    );
}

function avg_rating($type, $target) {
    $rs = DB_rating::enum(
        sprintf('type=%d and target=%d', $type, $target)
    );
    if (!$rs) return [NO_RATING, NO_RATING];
    $qsum = 0;
    $qn = 0;
    $dsum = 0;
    $dn = 0;
    foreach ($rs as $r) {
        if ($r->attr1 != NO_RATING) {
            $qsum += $r->attr1;
            $qn++;
        }
        if ($r->attr2 != NO_RATING) {
            $dsum += $r->attr2;
            $dn++;
        }
    }
    return [
        $qn?$qsum/$qn:NO_RATING,
        $dn?$dsum/$dn:NO_RATING
    ];
}

function more_review($rev) {
    return show_text_more_aux($rev, 80);
}

function get_users_ratings($user, $type, $target) {
    $rq = NO_RATING;
    $rd = NO_RATING;
    $review = null;
    $r = DB_rating::lookup(
        sprintf('user=%d and type=%d and target=%d',
            $user->id, $type, $target
        )
    );
    if ($r) {
        if ($r->attr1 != NO_RATING) $rq = (int)$r->attr1;
        if ($r->attr2 != NO_RATING) $rd = (int)$r->attr2;
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
            row2(
                sprintf('<p>Your review<p>%s',
                    button_link($url, 'edit', 'Edit your review', ' btn-success btn-xs')
                ),
                more_review($review)
            );
        } else {
            row2('', button_link($url, 'Write a review'));
        }
    }
    $revs = DB_rating::enum(
        "type=$type and target=$target and review<>''"
    );
    foreach ($revs as $rev) {
        if ($user && $user->id == $rev->user) continue;
        $u = BoincUser::lookup_id($rev->user);
        row2(
            sprintf('Review by <a href=user.php?userid=%d>%s</a><br><small>%s</small>',
                $rev->user, $u->name,
                date_str($rev->created)
            ),
            sprintf('%s%s',
                $rev->attr1==NO_RATING?'':"(Rating: $rev->attr1)<br>",
                more_review($rev->review)
            )
        );
    }
    end_table();
}

// right panel for composition: rating and review stuff
//
function comp_right($arg) {
    $user = get_logged_in_user(false);
    [$c, $par] = $arg;
    if ($user) {
        [$rq, $rd, $review] = get_users_ratings($user, COMPOSITION, $c->id);
    }
    echo '<b>Composition quality</b>';
    $x = 'composition';
    if ($c->arrangement_of) $x = 'arrangement';
    if ($c->parent) $x = 'section';
    echo "<br><small>How much do you like this $x?</small>";
    start_table();
    if ($user) {
        row2('Your rating',
            rating_buttons(COMPOSITION_TERMS, $rq, 'rate_comp_1', $c->id)
        );
    } else {
        row2('<a href=login_form.php>Log in</a> to rate', '');
    }
    [$qavg, $davg] = avg_rating(COMPOSITION, $c->id);
    row2('Average rating', rating_bar($qavg));
    end_table();
    echo '<b>Technical difficulty</b>';
    echo "<br><small>If you play this $x: how difficult is it for you?</small>";
    start_table();
    if ($user) {
        row2('Your rating', rating_buttons(DIFFICULTY_TERMS, $rd, 'rate_comp_2', $c->id));
    } else {
        row2('<a href=login_form.php>Log in</a> to rate', '');
    }
    row2('Average rating', rating_bar($davg));
    end_table();
    if ($user) {
        show_reviews(COMPOSITION, $c->id, $user, $review);
    } else {
        show_reviews(COMPOSITION, $c->id, null, null);
    }
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
    $comps = DB_composition::enum('', 'limit 3');
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
    $user = get_logged_in_user(false);
    $prs = DB_person_role::enum("person=$p->id");
    foreach ($prs as $pr) {
        $role = DB_role::lookup_id($pr->role);
        $pers_as_role = "$p->first_name $p->last_name as $role->name";
        start_table();
        row_heading($pers_as_role);
        if ($user) {
            [$rq, $rd, $review] = get_users_ratings($user, PERSON_ROLE, $pr->id);
            row2('Your rating', rating_buttons(QUALITY_TERMS, $rq, 'rate_pr', $pr->id));
        } else {
            row2('<a href=login_form.php>Log in</a> to rate', '');
        }
        [$qavg, $davg] = avg_rating(PERSON_ROLE, $pr->id);
        row2('Average rating', rating_bar($qavg));
        end_table();
        if ($user) {
            show_reviews(PERSON_ROLE, $pr->id, $user, $review);
        } else {
            show_reviews(PERSON_ROLE, $pr->id, null, null);
        }
        echo sprintf(
            '<p><b>People who liked %s also liked:</b><p>',
            $pers_as_role
        );
        show_recommended_prs($p, $role);
    }
}

// TODO: use data.  Show only unrated items
//
function show_recommended_prs($p, $role) {
    $prs = DB_person_role::enum(
        sprintf('role=%d', $role->id),
        'limit 3'
    );
    echo '<ul>';
    foreach ($prs as $pr) {
        $person = DB_person::lookup_id($pr->person);
        echo sprintf('<li><a href=item.php?type=%d&id=%d>%s %s</a>',
            PERSON, $pr->person, $person->first_name, $person->last_name
        );
    }
    echo '</ul>';
}

function perf_right($perf) {
    $user = get_logged_in_user(false);
    if ($user) {
        [$rq, $rd, $review] = get_users_ratings($user, PERFORMANCE, $perf->id);
    }
    echo '<b>Performance quality</b>';
    start_table();
    if ($user) {
        row2('Your rating',
            rating_buttons(QUALITY_TERMS, $rq, 'rate_perf_1', $perf->id)
        );
    } else {
        row2('<a href=login_form.php>Log in</a> to rate', '');
    }
    [$qavg, $davg] = avg_rating(PERFORMANCE, $perf->id);
    row2('Average rating', rating_bar($qavg));
    end_table();
    if ($perf->files) {
        echo '<b>Audio quality</b>';
        start_table();
        if ($user) {
            row2('Your rating', rating_buttons(QUALITY_TERMS, $rd, 'rate_perf_2', $perf->id));
        } else {
            row2('<a href=login_form.php>Log in</a> to rate', '');
        }
        row2('Average rating', rating_bar($davg));
        end_table();
    }
    if ($user) {
        show_reviews(PERFORMANCE, $perf->id, $user, $review);
    } else {
        show_reviews(PERFORMANCE, $perf->id, null, null);
    }
    echo '
        <p>
        <b>People who liked this recording also liked recordings of:</b>
        <p>
    ';
    show_recommended_perfs($perf);
}

function show_recommended_perfs($perf) {
    $ps = DB_performance::enum('', 'limit 3');
    echo '<ul>';
    foreach ($ps as $p) {
        $comp = DB_composition::lookup_id($p->composition);
        echo sprintf('<li><a href=item.php?type=%d&id=%d>%s</a>',
            PERFORMANCE, $p->id, $comp->long_title
        );
    }
    echo '</ul>';
}

function score_right($score) {
    $user = get_logged_in_user(false);
    if ($user) {
        [$rq, $rd, $review] = get_users_ratings($user, SCORE, $score->id);
    }
    echo '<b>Edition quality</b>';
    start_table();
    if ($user) {
        row2('Your rating',
            rating_buttons(QUALITY_TERMS, $rq, 'rate_score_1', $score->id)
        );
    } else {
        row2('<a href=login_form.php>Log in</a> to rate', '');
    }
    [$qavg, $davg] = avg_rating(SCORE, $score->id);
    row2('Average rating', rating_bar($qavg));
    end_table();
    echo '<b>Scan image quality</b>';
    start_table();
    if ($user) {
        row2('Your rating', rating_buttons(QUALITY_TERMS, $rd, 'rate_score_2', $score->id));
    } else {
        row2('<a href=login_form.php>Log in</a> to rate', '');
    }
    row2('Average rating', rating_bar($davg));
    end_table();
    if ($user) {
        show_reviews(SCORE, $score->id, $user, $review);
    } else {
        show_reviews(SCORE, $score->id, null, null);
    }
}

?>
