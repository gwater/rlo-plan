<?php

/**
 * This page requires the GET parameter 'action' which can either be 'login' or 'logout'.
 * If it's 'login', the POST parameters 'name' and 'pwd' are required.
 * An optional GET parameter 'continue' sets the redirect target script.
 */

require_once('db.inc.php');
require_once('misc.inc.php');
require_once('html.inc.php');

session_start();

switch($_GET['action']) {
case 'login':
    if (isset($_POST['name']) && isset($_POST['pwd'])) {
        $db = new db();
        $_SESSION['privilege'] = $db->login($_POST['name'], $_POST['pwd'], ip2long($_SERVER['REMOTE_ADDR']), session_id());
        if ($_SESSION['privilege'] != -1) {
            redirect($_GET['continue']);
        }
    }
    exit(new ovp_page(new ovp_login($db))->get_html());
case 'logout':
    $db = new db();
    $db->logout(session_id());
    $_SESSION = array();
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    session_destroy();
    redirect('index.php');
default:
    die('ERROR: no action parameter');
}

?>
