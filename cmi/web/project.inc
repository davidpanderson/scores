<?php

// Sample BOINC project web config file.
// Modify it to suit your project.
// Put your version in html/project/project.inc
//
// add tra() around visible strings to make them translatable
// see inc/translation.inc and
// https://github.com/BOINC/boinc/wiki/TranslateProject for details

require_once("../inc/util.inc");

define('GLOBAL_HEAD_EXTRA', '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>');

//-------------- Project name and owner

define("PROJECT", "Classical Music Index");
define("COPYRIGHT_HOLDER", "David P. Anderson");

//-------------- URLs and directories

$master_url = parse_config(get_config(), "<master_url>");
define("URL_BASE", $master_url);
define("IMAGE_PATH", "../user_profile/images/");
define("IMAGE_URL", "user_profile/images/");
define("PROFILE_PATH", "../user_profile/");
define("PROFILE_URL", "user_profile/");
define("LANGUAGE_FILE", "languages.txt");
define('NO_COMPUTING', true);
define('NO_STATS', true);
define('NO_HOSTS', true);
define('NO_GLOBAL_PREFS', true);
define('UNIQUE_USER_NAME', true);
define('DISABLE_TEAMS', true);
define('SHOW_USER_PAGE', 'user.php');
define('HOME_PAGE', 'user.php');
define('PM_RULES', '');
define('FORUM_RULES', '');

//-------------- contact info

define("SYS_ADMIN_EMAIL", "admin@$master_url");
define("UOTD_ADMIN_EMAIL", "admin@$master_url");
    // who gets emails about user of the day pool running low?
define("POST_REPORT_EMAILS", "moderator1@$master_url|moderator2@$master_url");
    // Email addresses separated by pipe ( | ) that will receive user reports
    // of offensive forum posts.

//-------------- PHP error reporting
// Show PHP errors in web pages.
// Comment this out when your project is public.
display_errors();

//-------------- CSS styling

// add your own stylesheets (included after bootstrap)
//define("STYLESHEET", "aaa.css");
//define("STYLESHEET2", "bbb.css");

//-------------- enable/disable web features

define("FORUM_QA_MERGED_MODE", true);
    // Set to true to merge Message boards and Q&A section
define ("DISABLE_PROFILES", true);
    // enable profiles only after enabling reCAPTCHA
    // https://github.com/BOINC/boinc/wiki/ProtectionFromSpam
define("USE_STOPFORUMSPAM", true);
    // use http://www.stopforumspam.com to suppress spammer accounts
define("RPC_DEBUG", false);
    // Enables PHP error messages in public Web RPCs
define("TERMSOFUSE_FILE", "../../terms_of_use.txt");
    // Defines the location of the terms of use file. Default location is in the project directory.

//-------------- Project-specific preferences

define('COLOR_PREFS', false);
    // user can select screensaver color scheme
define('GFX_CPU_PREFS', false);
    // user can limit % CPU used by screensaver
    // (lower frame rate if exceeded)
    // This is probably irrelevant if your screensaver uses OpenGL
define('APP_SELECT_PREFS', false);
    // user can choose which apps to run
define('NON_GRAPHICAL_PREF', false);
    // user can choose to run faster non-graphical app versions if available
define('MAX_JOBS_PREF', false);
    // user can choose max # of jobs in progress
define('MAX_CPUS_PREF', false);
    // user can choose max # of CPUs to use

//-------------- PHPMailer

// If you use PHPMailer, uncomment the following
// and complete the function definition based on your SMTP server
// (not all fields may be needed)
//
if (0) {
function make_php_mailer() {
    $mail = new PHPMailer();
    $mail->IsSMTP();
    //$mail->Mailer = 'smtp';
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = "tls";
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->Username = "john.doe@gmail.com";
    $mail->Password = "xxx";
        // Google's application-specific password,
        // if you are using the 2-Step Verification: 16 characters, no spaces.
        // OR: Put here the regular Gmail password if you are not using the
        // 2-Step Verification with your Gmail account.
        // See https://support.google.com/accounts/answer/185833?hl=en";
    $mail->SetFrom('admin@boincproject.com', 'John Doe');
    $mail->AddReplyTo("admin@boincproject.com", "John Doe");
    $mail->From = "admin@boincproject.com";
    return $mail;
}
}

//-------------- Header and footer

function navbar(
    $url_prefix,
        // prefix for links; needed for pages not in top dir
    $user,
        // logged-in user, if any
    $fixed=false,
        // if true, navbar is fixed at top of page.
        // NOTE: if you do this, you must set a global var $fixed_navbar
        // to true at compile time
        // (it needs to be set when page_head() is called).
    $inverse=false
        // white on black?
) {
    global $master_url;

    $brand = "<a class=\"navbar-brand\" href=$master_url>".PROJECT."</a>";
    navbar_start($brand, $fixed, $inverse);

    $x = array();
    if ($user) {
        $x[] = array(tra("Account"), 'account.php');
        $x[] = array(tra("Join"), $url_prefix."join.php");
    }
    $x[] = array(tra("About %1", PROJECT), "about.php");
    $x[] = array(tra("Contact"), "contact.php");
    navbar_menu(tra("Project"), $x);

    navbar_menu(tra("Community"), array(
        array(tra("Message boards"), $url_prefix."forum_index.php"),
        array(tra("User search"), $url_prefix."user_search.php"),
        array('Music Match', 'https://music-match.org'),
    ));
    navbar_menu(tra("Site"), array(
        array(tra("Site search"), $url_prefix."site_search.php"),
        array(tra("Languages"), $url_prefix."language_select.php")
    ));

    // add your own menu here if you want

    navbar_right($user);
    navbar_end();
}
// Put your project title and logo here
// If you include any links, prepend URL with $prefix
// if you need to open divs here close them in project_footer()
//
function project_banner($title, $prefix, $is_main) {
    navbar(secure_url_base(), get_logged_in_user(false), false);
    if ($title) {
        echo "<h2>$title</h2>\n";
    }
}
//$fixed_navbar = true;

function project_footer($show_return, $show_date, $prefix) {
    // If you include any links, prepend URL with $prefix
    //
    echo '<br>
        <p class="text-center"> &copy;'.gmdate("Y ").COPYRIGHT_HOLDER.'</p>
    ';
}

function page_tail() {
    echo "<br>\n";
    project_footer(false, false, '');
    echo '
        <script src="'.secure_url_base().'/bootstrap.min.js"></script>
        </div>
        </body>
        </html>
    ';
}

//-------------- Caching

//define("MEMCACHE_SERVERS", "127.0.0.1:11211");

//-------------- Ops access control

// Authorize access to administrative pages.
// You can check for login, IP address, or whatever you want.
//
function auth_ops_example() {
    // if running from cmdline, skip checks
    //
    if (!$_SERVER['REMOTE_ADDR']) {
        return;
    }

    // example: require login as a particular user (id 1 in this case)
    //
    if (0) {
        auth_ops_userid(array(1));
        return;
    }

    // example: require that logged-in user have ADMIN or DEV flags
    // set in their forum_prefs.privilege
    //
    if (0) {
        auth_ops_privilege();
        return;
    }
}

//-------------- Customizable functions

function show_profile_heading1() {
    return tra("Your personal background.");
}

function show_profile_question1() {
    return tra("Tell us about yourself. You could tell us where you're from, your age, occupation, hobbies, or anything else about yourself.");
}

function show_profile_heading2() {
    return tra("Your opinions about %1", PROJECT);
}

function show_profile_question2() {
    return tra("Tell us your thoughts about %1<ol>
    <li>Why do you run %1?
    <li>What are your views about the project?
    <li>Any suggestions?
    </ol>", PROJECT);
}

function project_workunit($wu){
    // shown in the workunit page
}

function project_user_summary($user){
    // shown in the user summary page
}

function project_user_page_private($user){
    // shown in the private account page
}

function project_forum_post_rules() {
    // additional forum post rules
    return "";
}

function project_delete_account($user) {
    // a project defined function for deleting users if neither
    // obfuscate_account($user) or wipe_account($user) in
    // inc/delete_account.inc meets the need of the project
    die("This function must be implemented before it can be used");
}

//-------------- Delete Account
// TODO: get rid of this. Doesn't need to be configurable, or even exist.
//define("DELETE_DELAY", 2);
    // When deleting an account, invalidate the authenticator and then wait this
    // many seconds before proceeding with the delete.  This is intended to give the
    // an existing scheduler request sufficient time to complete.  Some projects
    // might want to increase this to a longer time.  Simply uncomment and set the
    // delay to what the project needs.

//-------------- Support for per-app credit

if (0) {

// show project-specific credit on user/team pages
//
function show_app_credit_user($user, $app_name, $appids) {
    $t = 0;
    $a = 0;
    $n = 0;
    foreach ($appids as $appid) {
        $cu = BoincCreditUser::lookup(
            "userid=$user->id and appid=$appid and credit_type=0"
        );
        if ($cu) {
            $t += $cu->total;
            $a += $cu->expavg;
            $n += $cu->njobs;
        }
    }
    row2("$app_name credit",
        format_credit_large($t)." total, ".
        format_credit($a)." average".
        " ($n tasks)"
    );
}
function show_app_credit_team($team, $app_name, $appids) {
    $t = 0;
    $a = 0;
    $n = 0;
    foreach ($appids as $appid) {
        $ct = BoincCreditTeam::lookup(
            "teamid=$team->id and appid=$appid and credit_type=0"
        );
        if ($ct) {
            $t += $ct->total;
            $a += $ct->expavg;
            $n += $ct->njobs;
        }
    }
    row2("$app_name credit",
        format_credit_large($t)." total, ".
        format_credit($a)." average".
        " ($n tasks)"
    );
}

// a list of "sub-projects", used in the display of per-app credit and badges.
// A subproject is:
// - a set of 1 or more apps; an app can belong to at most 1 subproject.
// - a name, shown on the web site
// - a short name, used in badge names.  Don't use "total".
//
$sub_projects = array(
    array("name" => "Remote Test", "short_name" => "RT", "appids" => array(16)),
    array("name" => "Uppercase", "short_name" => "UC", "appids" => array(1, 25)),
);

function project_user_credit($user){
    global $sub_projects;
    foreach ($sub_projects as $sp) {
        show_app_credit_user($user, $sp["name"], $sp["appids"]);
    }
}

function project_team_credit($team) {
    global $sub_projects;
    foreach ($sub_projects as $sp) {
        show_app_credit_team($team, $sp["name"], $sp["appids"]);
    }
}

}   // if(0)

?>
