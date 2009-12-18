<?php

require_once('db.inc.php');

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE.utf8', 'deu');

session_start();
$db = new db();
if (!is_authorized(PRIV_DEFAULT + 1)) {
    header('HTTP/1.0 401 Unauthorized');
    exit('you need to log in first');
}

if (isset($_POST['pwd'])) {
    $db->change_pwd($_POST['newpwd'], $_POST['oldpwd']);
    exit('updated');
}

fail('invalid action');

function fail($msg) {
    header('HTTP/1.0 400 Bad Request');
    exit($msg);
}

?>
