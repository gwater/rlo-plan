<?php

require_once('config.inc.php');
require_once('db.inc.php');
require_once('html.inc.php');

session_start();

$db = new db();

$source = new ovp_table_public($db);
$page = new ovp_page($source);
exit($page->get_html());

?>
