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

function authenticate($requiredPrivilege = 1, $continue = 'index.php') {
    global $db;
    if (isset($_SESSION['privilege']) &&
        $_SESSION['privilege'] >= $requiredPrivilege &&
        ip2long($_SERVER['REMOTE_ADDR']) == $db->get_ip(session_id())
       ) {
        return;
    } else {
        redirect('account.php?action=login&continue='.$continue);
    }
}

?>