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
require_once('misc.inc.php');
require_once('html.inc.php');
require_once('zipper.inc.php');
require_once('poster.inc.php');
require_once('logger.inc.php');
require_once('db.inc.php');

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE.utf8', 'deu');

session_start();

/* use this variable anywhere you need to decide between wizard usage and
 * general usage (eg redirections to the next wizard page in poster.inc.php)
 */
$is_wiz = true;

switch ($_GET['poster']) {
case 'mysql':
    $poster = new post_mysql($is_wiz);
    break;
case 'settings':
    $poster = new post_settings($is_wiz);
    break;
case 'account':
    $poster = new post_account(new db(), $is_wiz);
    break;
default:
    // DoNothing (tm)
}

if (isset($poster)) {
    if (FIRST_RUN) {
        exit($poster->evaluate($_POST));
    }
    $poster_vars = get_class_vars(get_class($poster));
    $db = new db();
    $logger = new ovp_logger($db);
    if (!$logger->is_authorized($poster_vars['priv_req'])) {
        header('HTTP/1.0 401 Unauthorized');
        exit('you need to log in first');
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
    case 'login':
        $source = new ovp_login();
        break;
    case 'final':
        ovp_wizard::finalize();
        $source = new ovp_final();
        break;
    case 'mysql':
    default:
        ovp_wizard::initialize();
        $source = new ovp_mysql();
}

$source_vars = get_class_vars(get_class($source));
if (!FIRST_RUN) {
    $db = new db();
    $logger = new ovp_logger($db);
    $logger->authorize($source_vars['priv_req']);
}
$navi = new ovp_navi_wizard($source_vars['type']);
$page = new ovp_page($source, $navi);
exit($page->get_html());

class ovp_wizard {
    public static function initialize() {
        if (!ovp_zipper::pack_dir()) {
            fail('Erstellen des Quellarchivs gescheitert');
        }
        return true;
    }

    public static function finalize() {
        $config = new ovp_config();
        $config->set('FIRST_RUN', 'false');
    }
}

?>