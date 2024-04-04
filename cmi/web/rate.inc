<?php

require_once('../inc/util.inc');

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
                '<a href=rate.php?action=%s&id=%d&val=%d><button title="%s" style="height:%dpx;width:%dpx;font-size:%dpx">%d</button></a>',
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

function avg_rating_comp($comp) {
    $rs = DB_comp_rating::enum("composition=$comp->id");
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
        if ($r->difficulty !== null) {
            $dsum += $r->difficulty;
            $dn++;
        }
    }
    return [
        $qn?.1*$qsum/$qn:null,
        $dn?.1*$dsum/$dn:null
    ];
}

function comp_right($arg) {
    $user = get_logged_in_user(true);
    [$c, $par] = $arg;
    if ($user) {
        $rq = null;
        $rd = null;
        $r = DB_comp_rating::lookup("user=$user->id and composition=$c->id");
        if ($r) {
            if ($r->quality !== null) $rq = (int)$r->quality;
            if ($r->difficulty !== null) $rd = (int)$r->difficulty;
        }
    }
    echo '<b>Composition quality</b>';
    start_table();
    if ($user) {
        row2('Your rating',
            rating_buttons(false, $rq, 'comp_q', $c->id)
        );
    }
    [$qavg, $davg] = avg_rating_comp($c);
    row2('Average rating', rating_bar($qavg));
    end_table();
    echo '<b>Technical difficulty</b>';
    start_table();
    if ($user) {
        row2('Your rating', rating_buttons(true, $rd, 'comp_d', $c->id));
    }
    row2('Average rating', rating_bar($davg));
    end_table();
    echo '<b>Reviews</b><p>';
    start_table();
    if ($user) {
        if ($r && $r->review) {
            row2('Your review', show_review($r->review));
        } else {
            row2('',
                button_text(
                    "rate.php?action=comp_rev_form&id=$c->id", 'Write a review'
                )
            );
        }
    }
    $revs = DB_comp_rating::enum("composition=$c->id and review<>''");
    foreach ($revs as $rev) {
        if ($user && $user->id == $rev->user) continue;
        $u = BOINCUser::lookup_id($rev->user);
        echo "<href=cmi_user.php?id=$rev->user>$user->name</a>";
        show_review($rev->review);
    }
    end_table();
    echo '
        <p>
        <b>People who liked this also liked:</b>
        <p>
    ';
}

?>
