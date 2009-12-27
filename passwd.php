<?php

// TODO: integrate into post.php

require_once('db.inc.php');
require_once('misc.inc.php');
require_once('user.inc.php');

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE.utf8', 'deu');

session_start();
$db = new db();
if (!is_authorized(PRIV_LOGIN)) {
    header('HTTP/1.0 401 Unauthorized');
    exit('you need to log in first');
}

if (isset($_POST['newpwd']) && isset($_POST['oldpwd'])) {
    $user = ovp_user::get_current_user($db);
    if ($user->check_password($_POST['oldpwd'])) {
        if ($user->set_password($_POST['newpwd'])) {
            exit('updated');
        } else {
            fail('...la familia, Luigi...');
        }
    } else {
        fail('old password incorrect');
    }
} else {
    fail('parameters missing');
}

function fail($msg) {
    header('HTTP/1.0 400 Bad Request');
    exit($msg);
}

?>
