<?php

/**
 * This file is part of RLO-Plan.
 *
 * Copyright 2009 Tillmann Karras, Josua Grawitter
 *
 * RLO-Plan is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * RLO-Plan is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with RLO-Plan.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once('logger.inc.php');
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
case 'about':
    $source = new ovp_about($db);
    break;
case 'public':
default:
    $source = new ovp_public($db);
}

$source_vars = get_class_vars(get_class($source));
$logger = new ovp_logger($db);
$logger->authorize($source_vars['priv_req']);
$page = new ovp_page($db, $source);
exit($page->get_html());

?>
