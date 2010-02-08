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

require_once('db.inc.php');
require_once('user.inc.php');
require_once('config.inc.php');

class ovp_logger {
    // user privilege levels to authorize access to specific sources and posters
    const PRIV_LOGOUT = -2; // user is logged out
    const PRIV_LOGIN  = -1; // user is logged in
    const VIEW_NONE   =  0; // user has no privileges (account frozen)
    const VIEW_PUBLIC =  1; // user may see public data
    const VIEW_PRINT  =  2; // user may see sensitive data
    const VIEW_AUTHOR =  3; // user may add, remove and edit entries
    const VIEW_ADMIN  =  4; // user may add, remove and edit accounts
    private $user;
    private $db;

    public function __construct($user = false) {
        if (!$user) {
            $user = self::get_current_user($db);
        }
        $this->user = $user;
        $this->db = ovp_db::get_singleton();
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
