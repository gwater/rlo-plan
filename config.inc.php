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

require_once('user.inc.php');

class ovp_config {
    const FILENAME = 'config.xml';
    private static $singleton;
    private $root;
    private $document;

    private function __construct() {
        if (!file_exists(self::FILENAME)) {
            self::init();
        }
        $document = new DOMDocument('1.0', 'UTF8');
        $document->load(self::FILENAME);
        $this->root = $document->getElementById('root');
        $this->document = $document;
    }

    public function __destruct() {
        $dir = dirname($_SERVER['SCRIPT_FILENAME']);
        $this->document->save($dir.'/'.self::FILENAME);
    }

    public static function get_singleton() {
        if (self::$singleton === null) {
            self::$singleton = new self;
        }
        return self::$singleton;
    }

    public static function init() {
        $document = new DOMDocument('1.0', 'UTF8');
        $root = $document->createElement('Root');
        $root = $document->appendChild($root);
        $root->setAttribute('xml:id', 'root');
        $root->setAttribute('FIRST_RUN', true);
        $root->setAttribute('DEBUG', true);
        $root->setAttribute('SKIP_WEEKENDS', true);
        $root->setAttribute('PRIV_DEFAULT', ovp_user::VIEW_NONE);
        $root->setAttribute('DELETE_OLDER_THAN', -1);
        $root->setAttribute('DB_HOST', 'localhost');
        $document->save(self::FILENAME);
    }

    public function set($attribute, $value) {
        $this->root->setAttribute($attribute, $value);
    }

    public function get($attribute) {
        return $this->root->getAttribute($attribute);
    }
}

ini_set('session.hash_function', '1');
ini_set('session.hash_bits_per_character', '6');

?>
