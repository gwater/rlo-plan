<?php

require_once('db.inc.php');
require_once('misc.inc.php');

session_start();
$db = new db();
if (!is_authorized(VIEW_AUTHOR)) {
    header('HTTP/1.0 401 Unauthorized');
    exit('you need to log in first');
}

switch ($_POST['action']) {
case 'add':
    if (!(isset($_POST['day'])     && isset($_POST['teacher'])  &&
          isset($_POST['time'])    && isset($_POST['course'])   &&
          isset($_POST['subject']) && isset($_POST['duration']) &&
          isset($_POST['sub'])     && isset($_POST['change'])   &&
          isset($_POST['oldroom']) && isset($_POST['newroom']))) {
        fail('parameter missing');
    }
    $entry = new entry($_POST);
    exit($db->add_entry($entry));
case 'update':
    if (!(isset($_POST['id'])      &&
          isset($_POST['day'])     && isset($_POST['teacher'])  &&
          isset($_POST['time'])    && isset($_POST['course'])   &&
          isset($_POST['subject']) && isset($_POST['duration']) &&
          isset($_POST['sub'])     && isset($_POST['change'])   &&
          isset($_POST['oldroom']) && isset($_POST['newroom']))) {
        fail('parameter missing');
    }
    $entry = new entry($_POST);
    if ($db->update_entry($entry)) {
        exit('updated');
    } else {
        fail('invalid data');
    }
case 'delete':
    if (!(isset($_POST['id']) && is_numeric($_POST['id']))) {
        fail('invalid id');
    }
    if (!$db->remove_entry($_POST['id'])) {
        fail('id not found');
    } else {
        exit('deleted');
    }
default:
    fail('invalid action');
}

function fail($msg) {
    header('HTTP/1.0 400 Bad Request');
    exit($msg);
}

?>
