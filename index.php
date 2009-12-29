<?php

require_once('config.inc.php');
require_once('misc.inc.php');
require_once('db.inc.php');
require_once('html.inc.php');

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE.utf8', 'deu');

session_start();

// Actual content generation:
$db = new db();

switch ($_GET['source']) {
case 'print':
    if (isset($_GET['date'])) {
        $source = new ovp_print($db, $_GET['date']);
    } else {
        $source = new ovp_print($db);
    }
    break;
case 'author':
    $source = new ovp_author($db);
    break;
case 'admin':
    $source = new ovp_admin($db);
    break;
case 'login':
    $source = new ovp_login($db);
    break;
case 'password':
    $source = new ovp_password($db);
    break;
case 'public':
default:
    $source = new ovp_public($db);
}

$source_vars = get_class_vars(get_class($source));
authorize($source_vars['priv_req']);
$page = new ovp_page($source);
exit($page->get_html());

?>
