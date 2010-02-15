<?php

/**
 * This file is part of RLO-Plan.
 *
 * Copyright 2010 Tillmann Karras, Josua Grawitter
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
require_once('html.inc.php');
require_once('poster.inc.php');
require_once('interfaces.inc.php');

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE.utf8', 'deu');

session_start();

/* use this variable anywhere you need to decide between wizard usage and
 * general usage (eg redirections to the next wizard page in poster.inc.php)
 */
$is_wiz = true;
$config = ovp_config::get_singleton();

switch ($_GET['poster']) {
case 'mysql':
    $poster = new post_mysql($is_wiz);
    break;
case 'settings':
    $poster = new post_settings($is_wiz);
    break;
case 'account':
    $poster = new post_account($is_wiz);
    break;
default:
    // DoNothing (tm)
}

if (isset($poster)) {
    if ($config->get('FIRST_RUN')) {
        exit($poster->evaluate($_POST));
    }
    $poster_vars = get_class_vars(get_class($poster));
    $user = ovp_user_manager::get_current_user();
    if (!$user->is_authorized($poster_vars['priv_req'])) {
        ovp_http::fail('not logged in');
    }
    exit($poster->evaluate($_POST));
}

switch ($_GET['source']) {
case 'settings':
    $source = new ovp_settings();
    break;
case 'account':
    $source = new ovp_account();
    break;
case 'final':
    $config->set('FIRST_RUN', false);
    $source = new ovp_final();
    break;
case 'mysql':
default:
    if (!ovp_zipper::pack_dir()) {
        ovp_http::fail('Erstellen des Quellarchivs gescheitert');
    }
    $source = new ovp_mysql();
}

$source_vars = get_class_vars(get_class($source));
if (!$config->get('FIRST_RUN')) {
    $user = ovp_user_manager::get_current_user();
    $user->authorize($source_vars['priv_req']);
}
$navi = new ovp_navi_wizard($source_vars['type']);
$page = new ovp_page($source, $navi);
exit($page->get_html());

?>
