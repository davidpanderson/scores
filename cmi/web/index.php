<?php
// This file is part of BOINC.
// http://boinc.berkeley.edu
// Copyright (C) 2008 University of California
//
// BOINC is free software; you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation,
// either version 3 of the License, or (at your option) any later version.
//
// BOINC is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with BOINC.  If not, see <http://www.gnu.org/licenses/>.

// This is a template for your web site's front page.
// You are encouraged to customize this file,
// and to create a graphical identity for your web site.
// by customizing the header/footer functions in html/project/project.inc
// and picking a Bootstrap theme
//
// If you add text, put it in tra() to make it translatable.

require_once("../inc/db.inc");
require_once("../inc/util.inc");
require_once("../inc/news.inc");
require_once("../inc/cache.inc");
require_once("../inc/uotd.inc");
require_once("../inc/sanitize_html.inc");
require_once("../inc/text_transform.inc");
require_once("../project/project.inc");
require_once("../inc/bootstrap.inc");

require_once('cmi.inc');

$config = get_config();
$no_web_account_creation = parse_bool($config, "no_web_account_creation");
$project_id = parse_config($config, "<project_id>");

$stopped = web_stopped();
$user = get_logged_in_user(false);

// The panel at the top of the page
//
function panel_contents() {
}

function top() {
    global $stopped, $master_url, $user;
    if ($stopped) {
        echo '
            <p class="lead text-center">'
            .tra("%1 is temporarily shut down for maintenance.", PROJECT)
            .'</p>
        ';
    }
    //panel(null, 'panel_contents');
}

function left(){
    global $user;
    panel(
        'About',
        function() {
            echo "
                <p>
                Classical Music Index (CMI)
                is a database of classical music information.
                <ul>
                <li> It has
                    <a href=db_info.php>complex and detailed information</a>:
                    movements, arrangements, instrumentations,
                    creator roles, and so on.
                <li> It allows complex queries:
                    for example, you can find string quartets
                    by female French composers.
                <li> It is
                    <a href=editing.php>editable</a>:
                    you can add entries about your compositions,
                    your recordings,
                    or yourself.
                    Volunteer editors can fix or add details.
                <li> It supports
                    <a href=https://continuum-hypothesis.com/music_discover.php>music discovery</a>.
                    You can rate things, and you can find music you'll like
                    based on other people's ratings.
                <li> It links to scores and recordings on IMSLP.
                </ul>
            ";
            $user = get_logged_in_user(false);
            if (!$user) {
                echo 'To rate things, you must ';
                echo button_link('cmi_signup.php', 'create an account');
                echo '<p>';
            }
            echo 'Search for:<p>';
            start_table();
            show_type('Compositions', 'composition', 'Musical works, and associated scores and recordings');
            show_type('People', 'person', 'Composers, performers, arrangers, etc.');
            show_type('Ensembles', 'ensemble', 'Orchestras, choirs, chamber groups, etc.');
            show_type('Organizations', 'organization', 'Publishers and concert sponsors');
            show_type('Locations', 'location', 'Cities, provinces, countries, continents');
            show_type('Instrumentations', 'inst_combo', 'Combinations of instruments');
            show_type('Concerts', 'concert', 'Live performances, past and future');
            show_type('Venues', 'venue', 'Concert locations');
            end_table();
            echo '
                <p>
                The CMI code is open source and is
                <a href=https://github.com/davidpanderson/scores/tree/master/cmi>on Github</a>.
                <p>
                CMI is under development.
                The database may be reset,
                in which case items you add will be lost.
                Features based on ratings are simulated
                until we get a critical mass of ratings.
            ';
        }
    );
}

function show_type($title, $name, $desc) {
    row_array([
        "<a href=search.php?type=$name>$title</a>",
        $desc
    ]);
}
        
function right() {
    $user = get_logged_in_user(false);
    if ($user) {
        BoincForumPrefs::lookup($user);
        panel('Community',
            function() use($user){
                start_table();
                show_community_private($user);
                end_table();
            }
        );
    }
    panel(tra('News'),
        function() {
            include("motd.php");
            if (!web_stopped()) {
                show_news(0, 5);
            }
        }
    );
}

page_head(null, null, true);

grid('top', 'left', 'right');

page_tail(false, "", true);

?>
