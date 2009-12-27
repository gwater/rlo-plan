<?php

require_once('db.inc.php');
require_once('misc.inc.php');
require_once('poster.inc.php');

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE.utf8', 'deu');

session_start();
$db = new db();

switch ($_GET['poster']) {
case 'password':
    $poster = new post_password($db);
    break;
case 'user':
    $poster = new post_user($db);
    break;
case 'entry':
    $poster = new post_entry($db);
    break;
case 'login':
    $poster = new post_login($db);
    break;
case 'logout':
    $poster = new post_logout($db);
    break;
default:
    fail('I mean, what could possibly go wrong?');
}

$poster_vars = get_class_vars(get_class($poster));

if (!is_authorized($poster_vars['priv_req'])) {
    header('HTTP/1.0 401 Unauthorized');
    exit('you need to log in first');
}

$poster->evaluate($_POST);

?>
