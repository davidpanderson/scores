<?php

// show a user's ratings and reviews

require_once('../inc/util.inc');
require_once('cmi_db.inc');
require_once('cmi.inc');
require_once('rate.inc');

function show_user($user) {
    page_head("Ratings by $user->name");
    $ratings = DB_rating::enum("user=$user->id");

    echo '<h3>Compositions</h3>';
    $ratings2 = array_filter($ratings,
        function($x) {return $x->type == COMPOSITION;}
    );
    if ($ratings2) {
        start_table();
        table_header('Composition', 'Quality', 'Difficulty', 'Review', 'When');
        foreach ($ratings2 as $rating) {
            $comp = DB_composition::lookup_id($rating->target);
            if ($rating->attr1==NO_RATING) {
                $r1 = dash();
            } else {
                $r1 = rating_bar($rating->attr1/10);
            }
            if ($rating->attr2==NO_RATING) {
                $r2 = dash();
            } else {
                $r2 = rating_bar($rating->attr2/10);
            }
            table_row(
                sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
                    COMPOSITION, $rating->target, composition_str($comp)
                ),
                $r1,
                $r2,
                more_review($rating->review),
                date_str($rating->created)
            );
        }
        end_table();
    } else {
        echo dash();
    }

    echo '<h3>Recordings</h3>';
    $ratings2 = array_filter($ratings,
        function($x) {return $x->type == PERFORMANCE;}
    );
    if ($ratings2) {
        start_table();
        table_header('Composition', 'Performer', 'Performance quality', 'Sound quality', 'Review');
        foreach ($ratings2 as $rating) {
        }
        end_table();
    } else {
        echo dash();
    }

    echo '<h3>Scores</h3>';
    $ratings2 = array_filter($ratings,
        function($x) {return $x->type == SCORE;}
    );
    if ($ratings2) {
        start_table();
        table_header('Composition', 'Edition quality', 'Scan quality', 'Review');
        foreach ($ratings2 as $rating) {
        }
        end_table();
    } else {
        echo dash();
    }

    echo '<h3>People</h3>';
    $ratings2 = array_filter($ratings,
        function($x) {return $x->type == PERSON_ROLE;}
    );
    if ($ratings2) {
        start_table();
        table_header('Person', 'Role', 'Rating', 'Review');
        foreach ($ratings2 as $rating) {
        }
        end_table();
    } else {
        echo dash();
    }
    page_tail();
}

$id = get_int('id', true);
if ($id) {
    $user = BOINCUser::lookup_id($id);
} else {
    $user = get_logged_in_user();
}

show_user($user);
?>
