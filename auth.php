<?php

/**
 * This page requires the GET parameter 'action' which can either be 'login' or 'logout'.
 * If it's 'login', the POST parameters 'name' and 'pwd' are required.
 * An optional GET parameter 'continue' sets the redirect target script.
 */

session_start();

switch($_GET['action']) {
case 'login':
    // TODO:
    // - copy session cookie and ip to the user table
    // - write include with an authenticate() function which retrieves the ip and session cookie from the user table
    /*
    $db = new db();
    $db->save_session(session_id(), $_SERVER['REMOTE_ADDR']);
    */
    break;
case 'logout':
    /*
    $db = new db();
    $db->delete_session(session_id());
    $_SESSION = array();
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    session_destroy();
    */
    break;
default:
    die('ERROR: no action parameter');
}

$continue = $_GET['continue'];
if (!$continue) {
    $continue = 'index.php';
}

$server = $_SERVER['SERVER_NAME'];
$path = dirname($_SERVER['PHP_SELF']);
header('Location: http://'.$server.$path.'/'.$continue);

?>
