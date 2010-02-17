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

require_once('config.inc.php');
require_once('db.inc.php');
require_once('html.inc.php');
require_once('poster.inc.php');
require_once('entry.inc.php');

$config = ovp_config::get_singleton();
if ($config->get('FIRST_RUN')) {
   ovp_http::redirect('wizard.php');
}

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE.utf8', 'deu');

session_start();

switch ($_GET['poster']) {
case 'password':
    $poster = new post_password();
    break;
case 'user':
    $poster = new post_user();
    break;
case 'entry':
    $poster = new post_entry();
    break;
case 'mysql':
    $poster = new post_mysql();
    break;
case 'settings':
    $poster = new post_settings();
    break;
case 'backup':
    $poster = new post_import();
    break;
case 'login':
    $poster = new post_login();
    break;
case 'logout':
    $poster = new post_logout();
    break;
default:
    // DoNothing (tm)
}
if (isset($poster)) {
    $poster_vars = get_class_vars(get_class($poster));
    $user = ovp_user_manager::get_current_user();
    if (!$user->is_authorized($poster_vars['priv_req'])) {
        ovp_http::fail('Sie sind nicht eingeloggt.');
    }
    exit($poster->evaluate($_POST));
}

switch ($_GET['source']) {
case 'sub':
    if (isset($_GET['sub'])) {
        $source = new ovp_sub($_GET['sub']);
        break;
    } // else display ovp_print
case 'print':
    if (isset($_GET['date'])) {
        $source = new ovp_print($_GET['date']);
    } else {
        $source = new ovp_print();
    }
    break;
case 'author':
    $source = new ovp_author();
    break;
case 'admin':
    $source = new ovp_admin();
    break;
case 'settings':
    $source = new ovp_settings();
    break;
case 'mysql':
    $source = new ovp_mysql();
    break;
case 'backup':
    $source = new ovp_backup();
    break;
case 'login':
    $source = new ovp_login();
    break;
case 'password':
    $source = new ovp_password();
    break;
case 'about':
    $source = new ovp_about();
    break;
case 'public':
default:
    if (isset($_GET['course'])) {
        $source = new ovp_public($_GET['course']);
    } else {
        $source = new ovp_public();
    }
}

$source_vars = get_class_vars(get_class($source));
$user = ovp_user_manager::get_current_user();
$user->authorize($source_vars['priv_req']);
$navi = new ovp_navi($source_vars['type']);
$page = new ovp_page($source, $navi);
exit($page->get_html());

?>
