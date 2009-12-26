<?php

require_once('db.inc.php');
require_once('user.inc.php');

function redirect($to = '') {
    if (!$to) {
        $to = 'index.php';
    }
    $server = $_SERVER['SERVER_NAME'];
    $path = dirname($_SERVER['PHP_SELF']);
    header('Location: http://'.$server.$path.'/'.$to);
    exit;
}

function is_authorized($requiredPrivilege = 1) {
    global $db;
    if ($requiredPrivilege == PRIV_LOGIN) {
        $logged_user = ovp_user::get_current_user($db);
        return isset($logged_user);
    }
    return isset($_SESSION['privilege']) &&
        $_SESSION['privilege'] >= $requiredPrivilege &&
        $db->session_ok();
}

function authorize($requiredPrivilege = 1) {
    if (!is_authorized($requiredPrivilege)) {
        $continue = urlencode(basename($_SERVER['REQUEST_URI']));
        redirect('index.php?source=login&continue='.$continue); // does not return
    }
    return true;
}

?>
