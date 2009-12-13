<?php

require_once('config.inc.php');
require_once('misc.inc.php');
require_once('db.inc.php');
require_once('html.inc.php');

// make sure basename always returns the script name and not the directory
if (preg_match('/^index.php/i', basename($_SERVER['REQUEST_URI'])) == 0) {
    redirect('index.php');
}

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE.utf8', 'deu');

session_start();

// Actual content generation:
$db = new db();

switch ($_GET['view']) {
case 'print':
    authorize(VIEW_PRINT);
    if (isset($_GET['date'])) {
        $source = new ovp_print($db, $_GET['date']);
    } else {
        $source = new ovp_print($db);
    }
    break;
case 'author':
    authorize(VIEW_AUTHOR);
    $source = new ovp_author($db);
    break;
case 'admin':
    authorize(VIEW_ADMIN);
    $source = new ovp_admin($db);
    break;
case 'login':
    $source = new ovp_login($db);
    break;
case 'public':
default:
    authorize(VIEW_PUBLIC);
    $source = new ovp_public($db);
}

$page = new ovp_page($source);
exit($page->get_html());

?>
