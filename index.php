<?php

/**
 * This file is part of RLO-Plan.
 *
 * Copyright 2009, 2010 Tillmann Karras, Josua Grawitter
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
require_once('misc.inc.php');
require_once('poster.inc.php');

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE.utf8', 'deu');

session_start();

$db = new db();

switch ($_GET['poster']) {
case 'password':
    $poster = new post_password($db);
    break;
case 'user':
    $poster = new post_user($db);
    break;
case 'entry':
    $poster = new post_entry($db);
    break;
case 'login':
    $poster = new post_login($db);
    break;
case 'logout':
    $poster = new post_logout($db);
    break;
default:
    // DoNothing (tm)
}
if (isset($poster)) {
    $poster_vars = get_class_vars(get_class($poster));
    $logger = new ovp_logger($db);
    if (!$logger->is_authorized($poster_vars['priv_req'])) {
        header('HTTP/1.0 401 Unauthorized');
        exit('you need to log in first');
    }
    exit($poster->evaluate($_POST));
}

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
    $source = new ovp_login();
    break;
case 'password':
    $source = new ovp_password($db);
    break;
case 'about':
    $source = new ovp_about();
    break;
case 'public':
default:
    $source = new ovp_public($db);
}

$source_vars = get_class_vars(get_class($source));
$logger = new ovp_logger($db);
$logger->authorize($source_vars['priv_req']);
$navi = new ovp_navi($logger, $source_vars['type']);
$page = new ovp_page($source, $navi);
exit($page->get_html());

?>
