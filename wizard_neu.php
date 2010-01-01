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

/*
 * don't include this file anywhere. If you need a part,
 * move it to another file and include that file instead.
 */

/* use this constant anywhere you need to decide between wizard usage and
 * general usage (eg redirections to the next wizard page in poster.inc.php)
 */
define('WIZARD', true);


switch $_GET['source'] {
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

$navi = new ovp_navi_wizard();
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

class ovp_config {
    private $file; // config file

    public function __construct($file) {
        if ($file_exists($file)) {
            $this->file = $file;
        } else {
            fail('Konfigurationsdatei nicht gefunden');
        }
    }

    public function get($define) {
        $text = file_get_contents($this->file);
        if (preg_match('/(?<=define\(\''.$define.'\', ).+?(?=\);)/i', $text, $matches) == 0) {
            die('ERROR: define ' + $define  + ' not found');
        }
        return trim($matches[0], "'");
    }

    public function set($define, $value) {
        $text = file_get_contents($this->file);
        $text = preg_replace('/(?<=define\(\''.$define.'\', ).+?(?=\);)/i', $value, $text, 1);
        file_put_contents($this->file, $text);
    }


}

function goto_page($page) {
    //WTF?
    ovp_logger::redirect(basename($_SERVER['SCRIPT_NAME']).($page != '' ? '?page='.$page : ''));
}

?>