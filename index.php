<?php

require_once('config.inc.php');
require_once('misc.inc.php');
require_once('db.inc.php');
require_once('html.inc.php');

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE');

session_start();

// Actual content generation:
$db     = new db();

switch ($_GET['view']) {
case 'public':
    authenticate(1, 'index.php?view=public');
    $source = new ovp_public($db);
    break;
case 'print':
    authenticate(2, 'index.php?view=print');
    if (isset($_GET['date']) {
        $source = new ovp_print($db, $_GET['date'])
    } else {
        $source = new ovp_print($db);
    }
    break;
case 'author':
    authenticate(3, 'index.php?view=author');
    $source = new ovp_author($db);
    break;
case 'admin':
    authenticate(4, 'index.php?view=admin');
    $source = new ovp_admin($db);
    break;
case 'login':
default:
    $source = new ovp_login($db);
}

$page   = new ovp_page($source);
exit($page->get_html());

?>
