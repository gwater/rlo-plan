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
require_once('interfaces.inc.php');

class ovp_db extends mysqli {
    private static $singleton;

    public static function get_singleton() {
        if (self::$singleton === null) {
            self::$singleton = new self;
        }
        return self::$singleton;
    }

    public static function check_creds($host, $base, $user, $pass) {
        $temp = new mysqli();
        @$temp->connect($host, $user, $pass);
        if ($temp->connect_error) {
            return 'Keine Verbindung zum DB-Server';
        }
        if ($base != '' && !$temp->select_db($base)) {
            return 'Datenbank nicht gefunden';
        }
        return NULL;
    }

    // only use when you need a config-hack
    public function __construct($config = false) {
        if ($config) {
            $host = $config->get('DB_HOST');
            $user = $config->get('DB_USER');
            $pass = $config->get('DB_PASS');
            $base = $config->get('DB_BASE');
        } else {
            $host = DB_HOST;
            $user = DB_USER;
            $pass = DB_PASS;
            $base = DB_BASE;
        }
        parent::__construct($host, $user, $pass);
        if ($this->connect_errno) {
            ovp_http::fail('Keine Verbindung zum DB-Server');
        }
        $this->query("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'");
        $this->query("SET time_zone = '+1:00'");
        $this->query("SET lc_time_names = 'de_DE'");
        if (!$this->select_db($base)) {
            if (!$this->create_db()) {
                ovp_http::fail('Datenbank kann nicht erstellt werden'); // need database or rights to create it
            }
        }
    }

    public function query($query) {
        if (!($result = parent::query($query))) {
            ovp_http::debug($this->error);
            ovp_http::fail('SQL-Anfrage ungültig');
        }
        return $result;
    }

    public function protect($str) {
        return $this->escape_string(htmlspecialchars($str));
    }

    private function create_db() {
        if ($this->query("CREATE DATABASE `".$this->protect(DB_BASE)."` CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci'") === false) {
            return false;
        }
        return $this->select_db(DB_BASE);
    }

    public function reset_tables() {
        /*
        This table holds the user data of all the students who have access.
        id:        unique user id used to identify user during their session
        name:      user name, e.g. 'jdoe' FIXME: Unique?
        pwd_hash:  sha256-hashed password
        privilege: privilege level
                     0 - no rights whatsoever (useful for suspending accounts)
                     1 - view all data except for teacher names, default (students)
                     2 - view all data (teachers)
                     3 - view all data, and modify entries (Mrs. Lange I)
                     4 - view all data, modify entries, and add new users (root)
        ip1, ip2: the current IPv6 address if the user is logged in
        */
        $this->query("DROP TABLE IF EXISTS `user`");
        $this->query(
           "CREATE TABLE `user` (
                `id`        INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name`      VARCHAR(20)      NOT NULL,
                `pwd_hash`  CHAR(64)         NOT NULL,
                `privilege` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `ip1`       BIGINT UNSIGNED  NULL     DEFAULT NULL,
                `ip2`       BIGINT UNSIGNED  NULL     DEFAULT NULL,
                `sid`       INT UNSIGNED     NULL     DEFAULT NULL
            )"
        );

        /*
        This table holds the timetable changes (including the good stuff such as cancelled classes...)
        id:       unique entry id used to identify an entry during modification
        time:     timestamp of the day and time the class would normally start (e.g. Friday, July 13th)
        teacher:  name of the absent teacher (e.g. Mr. Doe)
        subject:  name and type of the course or subject (e.g. Ma-LK)
        duration: new duration of this class in minutes (e.g. 75)
        course:   name of the course (e.g. '9.3')
        oldroom:  room the class was supposed to take place in originally (e.g. H2-3)
        sub:      name of the substitute teacher (e.g. 'Fr. Musterfrau')
        change:   what class takes place [where] instead (e.g. 'Geschichte H0-2' or 'Ausfall')
        */
        $this->query("DROP TABLE IF EXISTS `entry`");
        $this->query(
           "CREATE TABLE `entry` (
                `id`       INT UNSIGNED      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `date`     DATE              NULL     DEFAULT NULL,
                `teacher`  VARCHAR(30)       NULL     DEFAULT NULL,
                `time`     TIME              NULL     DEFAULT NULL,
                `course`   VARCHAR(5)        NULL     DEFAULT NULL,
                `subject`  VARCHAR(5)        NULL     DEFAULT NULL,
                `duration` SMALLINT UNSIGNED NULL     DEFAULT NULL,
                `sub`      VARCHAR(30)       NULL     DEFAULT NULL,
                `change`   VARCHAR(40)       NULL     DEFAULT NULL,
                `oldroom`  VARCHAR(5)        NULL     DEFAULT NULL,
                `newroom`  VARCHAR(5)        NULL     DEFAULT NULL
            )"
        );
    }
}
?>
