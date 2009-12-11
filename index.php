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
authenticate(1, 'index.php');
$source = new ovp_table_public($db);
$page   = new ovp_page($source);
exit($page->get_html());

?>
