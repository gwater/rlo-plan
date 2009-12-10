<?php

require_once('config.inc.php');
require_once('db.inc.php');
require_once('html.inc.php');

session_start();

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE');

// Actual content generation:
$db     = new db();
$source = new ovp_table_public($db);
$page   = new ovp_page($source);
exit($page->get_html());

?>
