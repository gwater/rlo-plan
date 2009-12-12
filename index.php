<?php

require_once('config.inc.php');
require_once('misc.inc.php');
require_once('db.inc.php');
require_once('html.inc.php');

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE');

session_start();

// Actual content generation:
$db = new db();

switch ($_GET['view']) {
case 'print':
    authenticate(VIEW_PRINT, 'index.php?view=print');
    if (isset($_GET['date'])) {
        $source = new ovp_print($db, $_GET['date']);
    } else {
        $source = new ovp_print($db);
    }
    break;
case 'author':
    authenticate(VIEW_AUTHOR, 'index.php?view=author');
    $source = new ovp_author($db);
    break;
case 'admin':
    authenticate(VIEW_ADMIN, 'index.php?view=admin');
    $source = new ovp_admin($db);
    break;
case 'login':
    $source = new ovp_login($db);
    break;
case 'public':
default:
    authenticate(VIEW_PUBLIC, 'index.php?view=public');
    $source = new ovp_public($db);
}

$page = new ovp_page($source);
exit($page->get_html());

?>
