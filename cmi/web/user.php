<?php

// show a user (possibly 'me', the logged-in user)
// left panel: ratings and reviews
// right panel: social features

require_once('../inc/util.inc');
require_once('cmi_db.inc');
require_once('cmi.inc');
require_once('rate.inc');

function activity($user, $is_me) {
    $ratings = DB_rating::enum("user=$user->id");

    echo '<h2>Ratings and reviews</h2>';
    $rated = false;
    $ratings2 = array_filter($ratings,
        function($x) {return $x->type == COMPOSITION;}
    );
    if ($ratings2) {
        $rated = true;
        echo '<h3>Compositions</h3>';
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
    }

    $ratings2 = array_filter($ratings,
        function($x) {return $x->type == PERFORMANCE;}
    );
    if ($ratings2) {
        $rated = true;
        echo '<h3>Recordings</h3>';
        start_table();
        table_header('Composition', 'Performer', 'Performance quality', 'Sound quality', 'Review');
        foreach ($ratings2 as $rating) {
        }
        end_table();
    }

    $ratings2 = array_filter($ratings,
        function($x) {return $x->type == SCORE;}
    );
    if ($ratings2) {
        $rated = true;
        echo '<h3>Scores</h3>';
        start_table();
        table_header('Composition', 'Edition quality', 'Scan quality', 'Review');
        foreach ($ratings2 as $rating) {
        }
        end_table();
    }

    $ratings2 = array_filter($ratings,
        function($x) {return $x->type == PERSON_ROLE;}
    );
    if ($ratings2) {
        $rated = true;
        echo '<h3>People</h3>';
        start_table();
        table_header('Person', 'Role', 'Rating', 'Review');
        foreach ($ratings2 as $rating) {
        }
        end_table();
    }

    if (!$rated) {
        echo '<p>No ratings or reviews yet.';
        if ($is_me) {
            echo '<p>
                When you view compositions,
                scores, recordings, and people,
                you can rate them and write reviews.
                <p>
                This will help CMI learn what kinds of things you like,
                so that it can suggest
                music you might not know about otherwise.
            ';
        }
    }

    echo "<hr><h2>Items added to CMI</h2>";
    $added = false;
    if (!$added) {
        echo '<p>No items added yet.';
        if ($is_me) {
            echo "<p>
                You can add items to CMI -
                perhaps scores or recordings,
                or you own compositions,
                or concerts you've attended or are organizing.
            ";
        }
    }
}
function left($arg) {
    [$user, $is_me]= $arg;
    panel('Activity',
        function() use ($user, $is_me) {
            activity($user, $is_me);
        }
    );
}

function right($arg) {
    [$user, $is_me]= $arg;
    panel('Community',
        function() use ($user, $is_me) {
            start_table();
            if ($is_me) {
                BoincForumPrefs::lookup($user);
                show_community_private($user);
            } else {
                $x = get_community_links_object($user);
                community_links($x, get_logged_in_user(false));
            }
            end_table();
        }
    );
}

function main($user, $is_me) {
    if ($is_me) {
        page_head("Your home page");
    } else {
        page_head("$user->name");
    }
    grid(null, 'left', 'right', 7, [$user, $is_me]);
    page_tail();
}

$user = get_logged_in_user(false);
$id = get_int('userid', true);
if ($user && $user->id==$id) $id=0;
if ($id) {
    $user = BOINCUser::lookup_id($id);
    main($user, false);
} else {
    $user = get_logged_in_user();
    main($user, true);
}

?>
