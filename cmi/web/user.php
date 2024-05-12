<?php

// show a user (possibly 'me', the logged-in user)
// left panel: ratings and reviews
// right panel: social features

require_once('../inc/util.inc');
require_once('cmi_db.inc');
require_once('cmi.inc');
require_once('rate.inc');

function table_name($type) {
    static $names = [
        LOCATION => 'DB_location',
        PERSON => 'DB_person',
        PERSON_ROLE => 'DB_person_role',
        ENSEMBLE => 'DB_ensemble',
        ORGANIZATION => 'DB_organization',
        COMPOSITION => 'DB_composition',
        SCORE => 'DB_score',
        VENUE => 'DB_venue',
        PERFORMANCE => 'DB_performance',
        CONCERT => 'DB_concert'
    ];
    return $names[$type];
}

function table_desc($type) {
    static $descs = [
        LOCATION => 'Location',
        PERSON => 'Person',
        PERSON_ROLE => 'Person role',
        ENSEMBLE => 'Ensemble',
        ORGANIZATION => 'Organization',
        COMPOSITION => 'Composition',
        SCORE => 'Score',
        VENUE => 'Venue',
        PERFORMANCE => 'Recording',
        CONCERT => 'Concert'
    ];
    return $descs[$type];
}

function rating_header($type) {
    switch ($type) {
    case COMPOSITION:
        row_heading_array(
            ['Composition', 'Quality', 'Difficulty', 'Review', 'When'],
            null, 'bg-info'
        );
        break;
    case PERFORMANCE:
        table_header(
            ['Recording of', 'Performance quality', 'Sound quality', 'Review', 'When'],
            null, 'bg-info'
        );
        break;
    case SCORE:
        table_header(
            ['Score for', 'Edition quality', 'Scan quality', 'Review', 'When'],
            null, 'bg-info'
        );
        break;
    case PERSON_ROLE:
        row_heading_array(
            ['Person/role', 'Rating', '', 'Review', 'When'],
            null, 'bg-info'
        );
        break;
    }
}

define('BAR_WIDTH', 100);

function rating_item($type, $rating, $item) {
    switch ($type) {
    case COMPOSITION:
        table_row(
            composition_str($item),
            rating_bar($rating->attr1, BAR_WIDTH),
            rating_bar($rating->attr2, BAR_WIDTH),
            more_review($rating->review),
            date_str($rating->created)
        );
        break;
    case PERFORMANCE:
        $c = DB_composition::lookup_id($item->composition);
        table_row(
            composition_str($item),
            rating_bar($rating->attr1, BAR_WIDTH),
            rating_bar($rating->attr2, BAR_WIDTH),
            more_review($rating->review),
            date_str($rating->created)
        );
        break;
    case SCORE:
        table_row(
            score_str($item),
            rating_bar($rating->attr1, BAR_WIDTH),
            rating_bar($rating->attr2, BAR_WIDTH),
            more_review($rating->review),
            date_str($rating->created)
        );
        break;
    case PERSON_ROLE:
        table_row(
            person_role_str($item),
            rating_bar($rating->attr1, BAR_WIDTH),
            '',
            more_review($rating->review),
            date_str($rating->created)
        );
        break;
    }
}

function show_ratings_type($ratings, $type) {
    $ratings2 = array_filter($ratings,
        function($x) use($type) {return $x->type == $type;}
    );
    if (!$ratings2) return false;
    $table = table_name($type);
    rating_header($type);
    foreach ($ratings2 as $rating) {
        $item = $table::lookup_id($rating->target);
        rating_item($type, $rating, $item);
    }
    return true;
}

function show_ratings($user, $is_me) {
    $ratings = DB_rating::enum("user=$user->id");

    $rated = false;
    start_table();
    $rated |= show_ratings_type($ratings, COMPOSITION);
    $rated |= show_ratings_type($ratings, PERFORMANCE);
    $rated |= show_ratings_type($ratings, SCORE);
    $rated |= show_ratings_type($ratings, PERSON_ROLE);
    end_table();

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
                music you might not hear otherwise.
            ';
        }
    }
}

function show_added ($user, $is_me) {
    start_table();
    $added = false;
    $added |= show_added_type($user, LOCATION);
    $added |= show_added_type($user, PERSON);
    $added |= show_added_type($user, ENSEMBLE);
    $added |= show_added_type($user, ORGANIZATION);
    $added |= show_added_type($user, COMPOSITION);
    $added |= show_added_type($user, SCORE);
    $added |= show_added_type($user, VENUE);
    $added |= show_added_type($user, PERFORMANCE);
    $added |= show_added_type($user, CONCERT);
    end_table();
    if (!$added) {
        echo '<p>No items added yet.';
        if ($is_me) {
            echo "<p>
                You can add items to CMI -
                scores, recordings, you own compositions,
                concerts you've attended or are organizing.
            ";
        }
    }
}

function added_header($type) {
    row_heading_array(
        [table_desc($type), 'When'],
        null, 'bg-info'
    );
}

function added_item($type, $item) {
    switch ($type) {
    case LOCATION:
    case ORGANIZATION:
    case VENUE:
        return sprintf('<a href=item.php?type=%d&id=%d>%s</a>',
            $type, $item->id, $item->name
        );
    case PERSON:
        return sprintf('<a href=item.php?type=%d&id=%d>%s %s</a>',
            PERSON, $item->id, $item->first_name, $item->last_name
        );
    case COMPOSITION:
        return composition_str($item);
    case ENSEMBLE:
        return ensemble_str($item);
    case SCORE:
        return score_str($item);
        break;
    case PERFORMANCE:
        $c = DB_composition::lookup_id($item->composition);
        return composition_str($c);
        break;
    case CONCERT:
        return concert_str($item);
        break;
    }
}

function show_added_type($user, $type) {
    $table = table_name($type);
    $items = $table::enum("maker=$user->id");
    if (!$items) return false;
    added_header($type);
    foreach ($items as $item) {
        table_row(
            added_item($type, $item),
            date_str($item->create_time)
        );
    }
    return true;
}

function left($arg) {
    [$user, $is_me]= $arg;
    panel('Ratings and reviews',
        function() use ($user, $is_me) {
            show_ratings($user, $is_me);
        }
    );
    panel('Items added to CMI',
        function() use ($user, $is_me) {
            show_added($user, $is_me);
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
        page_head("CMI user $user->name");
    }
    echo '<p> </p>';
    grid(null, 'left', 'right', 7, [$user, $is_me]);
    page_tail();
}

$user = get_logged_in_user(false);
$id = get_int('userid', true);
if ($user && $user->id==$id) $id=0;
if ($id) {
    $user = BoincUser::lookup_id($id);
    main($user, false);
} else {
    $user = get_logged_in_user();
    main($user, true);
}

?>
