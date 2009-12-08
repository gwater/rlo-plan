<?php

// login/logout page

switch($_GET['action']) {
case 'login':
    break;
case 'logout':
    break;
default:
    die('ERROR: no action parameter');
}

$continue = $_GET['continue'];
if (!$continue) {
    $continue = 'index.php';
}
header('Location: http://ovp.rlo-gsv.de/'.$continue); // TODO: determine location automatically

?>