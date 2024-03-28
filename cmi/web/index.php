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
            echo '
                <p>
                Classical Music Index (CMI)
                is a searchable and editable
                database of classical music information:
                compositions, people, ensembles, recordings, and concerts.
                <p>
                The goals of CMI, and its database schema,
                are described <a href=https://continuum-hypothesis.com/music_discover.php>here</a>.
                <p>
                CMI is currently based on data from
                <a href=https://imslp.org>IMSLP</a>.
                <p>
                The code behind CMI is
                <a href=https://github.com/davidpanderson/scores/tree/master/cmi>on Github</a>.
                <p><p>
            ';
            show_button('search.php?type=composition', 'Compositions');
            show_button('search.php?type=person', 'People');
            show_button('search.php?type=location', 'Locations');
            show_button('search.php?type=concert', 'Concerts');
            show_button('search.php?type=venue', 'Venues');
            show_button('search.php?type=organization', 'Organizations');
        }
    );
}

function right() {
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
