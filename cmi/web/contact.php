<?php

require_once("../inc/util.inc");

function form($user) {
    page_head("Contact CMI");
    echo "
        <p>
        Please <a href=mailto:continuum873@gmail.com>email us</a> if
        <p>
        <ul>
        <li> something doesn't work or is confusing;
        <li> there's a feature you'd like to see;
        <li> other users are behaving inappropriately
            (spam, abusive language, etc.)
        </ul>
    ";
    echo "
        <p>
        If you're familiar with Github,
        you can also create an 'issue' on
        <a href=https://github.com/davidpanderson/scores/tree/master/cmi>the CMI Github repository</a>.
    ";

    page_tail();
}

function action($user) {
    global $recaptcha_private_key;
    $message = post_str('message');
    if (!$message) {
        error_page('No message');
    }

    if (strpos($message, 'SEO')!==false) {
        error_page('go away');
    }
    if (strpos($message, 'Viagra')!==false) {
        error_page('go away');
    }
    if (strpos($message, 'ryptocurrency')!==false) {
        error_page('go away');
    }
    if ($user) {
        $message = "(message from user $user->name email $user->email_addr ID $user->id)\n".$message;
    } else {
        //if (!boinc_recaptcha_isValidated($recaptcha_private_key)) {
        //    error_page(
        //        tra("Your reCAPTCHA response was not correct. Please try again.")
        //    );
        //}
        $e = post_str('email_addr');
        $message = "(message from $e)\n".$message;
    }
    $user = new StdClass;
    $user->email_addr = SYS_ADMIN_EMAIL;
    $user->name = "Music Match admin";
    send_email($user, "Music Match feedback", $message);

    page_head("Message sent");
    echo "
        Thanks for your feedback.
    ";
    page_tail();
}

if (post_str('submit', true)) {
    $user = get_logged_in_user(false);
    action($user);
} else {
    $user = get_logged_in_user(false);
    form($user);
}

?>
