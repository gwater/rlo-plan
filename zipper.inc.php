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

class ovp_zipper {
    public static function pack_dir() {
        $dir = getcwd();
        $files = self::list_files($dir);

        $zip = new ZipArchive;
        $res = $zip->open('source.zip', ZipArchive::CREATE);
        if (!$res) {
            return false;
        }
        foreach ($files as $file) {
            $zip->addFile($file);
        }
        $zip->close();
        return true;
    }

    private static function list_files($dir) {
        $handle = opendir($dir);

        $result = array();
        while (false !== ($file = readdir($handle))) {
	    if ($file != '.' && $file != '..') {
                $result[] = $file;
            }
        }
        closedir($handle);

        return $result;
    }
}


?>
