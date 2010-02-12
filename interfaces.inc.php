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
            if ($file != ovp_config::FILENAME) {
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

class ovp_http {
    // TODO: add $code as parameter
    public static function fail($msg) {
        header('HTTP/1.0 400 Bad Request');
        exit($msg);
    }

    public static function debug($msg) {
        $config = ovp_config::get_singleton();
        $debug = $config->get('DEBUG');
        if ($debug) {
            print($msg);
        }
    }

    public static function get_source_link($source = '') {
        return basename($_SERVER['SCRIPT_NAME']).($source == '' ? '' : '?source='.$source);
    }

    public static function get_poster_link($poster = '') {
        return basename($_SERVER['SCRIPT_NAME']).'?poster='.$poster;
    }

    public static function redirect($to = false) {
        if (!$to) {
            $to = self::get_source_link();
        }
        $server = $_SERVER['SERVER_NAME'];
        $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        header('Location: http://'.$server.$path.'/'.$to);
        exit;
    }
}

?>
