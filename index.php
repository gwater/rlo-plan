<?php

require_once('config.inc.php');
require_once('db.inc.php');

session_start();

$db = new db();
/*
example code to get and display a page from html.inc.php:

$source = new ovp_table_public($db);
$page = new ovp_page($source);
$html = $page->get_html();
echo $html;

*/
?>