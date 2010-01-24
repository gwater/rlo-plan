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
                $zip->addFromString('config.inc.php', ovp_config::get_clean_copy());
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

    public static function get_clean_copy() {
        return "<?php

define('FIRST_RUN', true);            // this value is automatically set to false on first run
define('DEBUG', true);                 // true:  detailed error messages are shown
                                       // false: only very short or no error messages are shown
                                       // set this to false when uploading to IRL server!

define('DB_HOST', 'localhost');        // database server address
define('DB_BASE', '');         // database name
define('DB_USER', '');         // database user name
define('DB_PASS', ''); // database password

define('DELETE_OLDER_THAN', -1);       // positive: entries older than DELETE_OLDER_THAN days are automatically deleted;
                                       // negative: never delete anything
define('SKIP_WEEKENDS', true);         // true: weekends are skipped when calculating the age of an entry,
                                       //       e.g. it's Monday, entry is from Friday, DELETE_OLDER_THAN = 1 --> entry is _not_ deleted;
                                       // false: weekends are just like every other day (this would suck for obvious reasons)
define('PRIV_DEFAULT', 1);             // default privilege level for logged out visitors, ovp_logger::PRIV_DEFAULT

?>
";
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
