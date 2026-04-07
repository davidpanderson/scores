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

require_once("../inc/util.inc");
require_once("../inc/user.inc");
require_once("../inc/boinc_db.inc");
require_once("../inc/forum.inc");

// show account info of logged-in user

function main() {
    $user = get_logged_in_user();
    page_head('Account settings');
    start_table();
    row1(tra("Account information"), 2, 'heading');
    show_user_info_private($user);
    show_preference_links();
    show_user_stats_private($user);

    if (function_exists('show_user_donations_private')) {
        show_user_donations_private($user);
    }
    end_table();
    if (!NO_COMPUTING) {
        show_other_projects($user, true);
    }
    if (function_exists("project_user_page_private")) {
        project_user_page_private($user);
    }
    page_tail();
}

main();
?>
