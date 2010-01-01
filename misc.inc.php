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

function fail($msg) {
    header('HTTP/1.0 400 Bad Request');
    exit($msg);
}

function goto_page($page) {
    ovp_logger::redirect(basename($_SERVER['SCRIPT_NAME']).($page != '' ? '?page='.$page : ''));
}

class ovp_config {
    private $file;

    public function __construct($file = 'config.inc.php') {
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

?>
