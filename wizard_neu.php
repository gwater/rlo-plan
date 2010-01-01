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

date_default_timezone_set('Europe/Berlin');
setlocale(LC_TIME, 'de_DE.utf8', 'deu');

/*
 * don't include this file anywhere. If you need a part,
 * move it to another file and include that file instead.
 */

/* use this constant anywhere you need to decide between wizard usage and
 * general usage (eg redirections to the next wizard page in poster.inc.php)
 */
define('WIZARD', true);

switch ($_GET['source']) {
    case 'mysql':
        $source = new ovp_mysql();
        break;
    case 'settings':
        $source = new ovp_settings();
        break;
    case 'account':
        $source = new ovp_account();
        break;
    case 'final':
        ovp_wizard::finalize();
        $source = new ovp_final();
        break;
    default:
        ovp_wizard::initialize();
        goto_page('mysql'); //FIXME
}

$source_vars = get_class_vars(get_class($source));
$navi = new ovp_navi_wizard($source_vars['type']);
$page = new ovp_page($source, $navi);
exit($page->get_html());

class ovp_wizard {
    public static function initialize() {
        if (!ovp_zipper::pack_dir()) {
            return false; //fail?
            //FIXME
        } else if (!copy($config, $temp)) {
            return false; //fail?
        }
        return true;
    }

    public static function finalize() {
        $wizard = file_get_contents('wizard.php');
        $replacement = '$logger = new ovp_logger(new db()); $logger->authorize(ovp_logger::VIEW_ADMIN);';
        $wizard = preg_replace('|/\* authorization placeholder \*/|', $replacement, $wizard, 1);
        file_put_contents('wizard.php', $wizard);
    }
}

?>