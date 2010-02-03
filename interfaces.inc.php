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

class ovp_zipper {
    const filename = 'source.zip';

    public static function pack_dir() {
        $dir = getcwd();
        $files = self::list_files($dir);

        $zip = new ZipArchive;
        if ($zip->open(self::filename, ZipArchive::OVERWRITE) !== true) {
            return false;
        }
        foreach ($files as $file) {
            if ($file == 'config.inc.php') {
                $config = new ovp_config();
                $content = $config->get_backup();
                if (!$content) {
                    ovp_msg::fail('Konfigurationsbackup konnten icht gelesen werden');
                }
                $zip->addFromString('config.inc.php', $content);
            } else {
                $zip->addFile($file);
            }
        }
        return $zip->close();
    }

    private static function list_files($dir) {
        $handle = opendir($dir);

        $result = array();
        while (false !== ($file = readdir($handle))) {
            if (!is_dir($file) && $file != self::filename) {
                $result[] = $file;
            }
        }
        closedir($handle);

        return $result;
    }
}

class ovp_config {
    private $file;

    public function __construct($file = 'config.inc.php') {
        if (file_exists($file)) {
            $this->file = $file;
        } else {
            ovp_msg::fail('Konfigurationsdatei nicht gefunden');
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
        if (file_put_contents($this->file, $text)) {
            return true;
        } else {
            ovp_msg::fail('Ändern der Konfigurationsdatei gescheitert');
        }
    }

    public function create_backup() {
        $content = file_get_contents($this->file);
        return file_put_contents($this->file.'.orig', $content);
    }

    public function get_backup() {
        return file_get_contents($this->file.'.orig');
    }
}

class ovp_msg {
    // TODO: add $code as parameter
    public static function fail($msg) {
        header('HTTP/1.0 400 Bad Request');
        exit($msg);
    }

    public static function debug($msg) {
        if (DEBUG) {
            print($msg);
        }
    }
}

?>
