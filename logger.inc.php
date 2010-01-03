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


    public function __construct(db $db, $user = false) {
        if (!$user) {
            $user = self::get_current_user($db);
        }
        $this->user = $user;
        $this->db = $db;
    }

    public function is_authorized($priv_req = 1) {
        if ($priv_req == self::PRIV_LOGIN) {
            return isset($this->user);
        } else if ($priv_req == self::PRIV_LOGOUT) {
            return !isset($this->user);
        } else if ($priv_req <= PRIV_DEFAULT) {
            return true;
        } else if (isset($this->user)) {
            if ($priv_req <= $this->user->get_privilege()) {
                return $this->session_ok();
            }
        }
        return false;
    }

    public function authorize($priv_req = 1) {
        if (!$this->is_authorized($priv_req)) {
            if ($priv_req == self::PRIV_LOGOUT) {
                self::redirect(basename($_SERVER['SCRIPT_NAME']));
            }
            $continue = basename($_SERVER['SCRIPT_NAME']);
            if ($_SERVER['QUERY_STRING'] != '') {
                $continue .= '?'.$_SERVER['QUERY_STRING'];
            }
            $link = self::get_source_link('login&continue='.urlencode($continue));
            self::redirect($link); // does not return
        }
        return true;
    }

    // checks if the current user's ip address matches the one in the database
    public function session_ok() {
        $result = $this->db->query(
           "SELECT
                `ip1`,
                `ip2`
            FROM `user` WHERE
                `sid`  = '".$this->db->protect(session_id())."'
            LIMIT 1"
        );
        if (!($row = $result->fetch_assoc())) {
            return false;
        }
        if ($row['ip2'] != NULL) {
            $ip = ($row['ip2'] << 64) + $row['ip1'];
        } else {
            $ip = $row['ip1'];
        }
        return $ip == ip2long($_SERVER['REMOTE_ADDR']);
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

    public static function login(db $db, $name, $pwd) {
        $result = $db->query(
           "SELECT
                `id`
            FROM `user` WHERE
                `name`      = '".$db->protect($name)."' AND
                `pwd_hash`  = '".$db->protect(hash('sha256', $pwd))."'"
        );
        if (!($row = $result->fetch_assoc())) {
            return false; // user not found or wrong password
        }
        $ip = ip2long($_SERVER['REMOTE_ADDR']);
        $ip1 = $ip & 0xFFFFFFFFFFFFFFFF;
        $ip2 = $ip >> 64;
        $db->query(
           "UPDATE `user` SET
                `ip1` = '".$db->protect($ip1)."',
                `ip2` = '".$db->protect($ip2)."',
                `sid` = '".$db->protect(session_id())."'
            WHERE
                `id` = '".$db->protect($row['id'])."'
            LIMIT 1"
        );
        return true;
    }

    public static function logout(db $db) {
        $db->query(
           "UPDATE `user` SET
                `ip1` = NULL,
                `ip2` = NULL,
                `sid` = NULL
            WHERE
                `sid` = '".$db->protect(session_id())."'
            LIMIT 1"
        );
        return $db->affected_rows == 1;
    }

    public static function get_current_user(db $db) {
        $ip = ip2long($_SERVER['REMOTE_ADDR']);
        $result = $db->query(
           "SELECT `id` FROM `user`
            WHERE
                `ip1` = '".$db->protect($ip & 0xFFFFFFFFFFFFFFFF)."' AND
                `ip2` = '".$db->protect($ip >> 64)."' AND
                `sid` = '".$db->protect(session_id())."'
            LIMIT 1")->fetch_assoc();
        if ($uid = $result['id']) {
            return new ovp_user($db, $uid);
        }
        return null;
    }
}

?>