<?php

require_once('db.inc.php');
require_once('misc.inc.php');

session_start();
$db = new db();
if (!is_authorized(VIEW_AUTHOR)) {
    header('HTTP/1.0 401 Unauthorized');
    echo('you need to log in first');
    die;
}

switch ($_POST['action']) {
case 'delete':
    if (!(isset($_POST['id']) && is_numeric($_POST['id']))) {
        fail('invalid id');
    }
    if (!$db->remove_entry($_POST['id'])) {
        fail('id not found');
    }
    break;
default:
    fail('invalid action');
}

function fail($msg) {
    header('HTTP/1.0 400 Bad Request');
    echo($msg);
    die;
}

?>
