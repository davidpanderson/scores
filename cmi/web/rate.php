<?php

require_once('../inc/util.inc');
require_once('cmi_db.inc');

function do_comp($id, $val, $is_diff) {
    $user = get_logged_in_user();
    $r = DB_comp_rating::lookup("user=$user->id and composition=$id");
    if ($r) {
        $r->update(
            sprintf('%s=%d, created=%d',
                $is_diff?'difficulty':'quality',
                $val, time()
            )
        );
    } else {
        DB_comp_rating::insert(
            sprintf('(created, user, composition, %s) values (%d, %d, %d, %d)',
                $is_diff?'difficulty':'quality',
                time(), $user->id, $id, $val
            )
        );
    }
    header("Location: item.php?type=composition&id=$id");
}

$type = get_str('type');
$id = get_int('id');
$val = get_int('val');
switch($type) {
case 'composition_q':
    do_comp($id, $val, false);
    break;
case 'composition_d':
    do_comp($id, $val, true);
    break;
}
?>
