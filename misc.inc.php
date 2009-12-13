<?php

require_once('db.inc.php');

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
    return isset($_SESSION['privilege']) &&
        $_SESSION['privilege'] >= $requiredPrivilege &&
        ip2long($_SERVER['REMOTE_ADDR']) == $db->get_ip(session_id());
}

function authorize($requiredPrivilege = 1) {
    if (!is_authorized($requiredPrivilege)) {
        $continue = urlencode($_SERVER['REQUEST_URI']);
        redirect('index.php?view=login&continue='.$continue); // does not return
    }
    return true;
}

?>
