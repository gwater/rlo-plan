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

    public function is_authorized($requiredPrivilege = 1) {
        if ($requiredPrivilege == self::PRIV_LOGIN) {
            return isset($this->user);
        } else if ($requiredPrivilege == self::PRIV_LOGOUT) {
            return !isset($this->user);
        }
        if ($requiredPrivilege <= PRIV_DEFAULT) {
            return true;
        }
        return isset($_SESSION['privilege']) &&
            $_SESSION['privilege'] >= $requiredPrivilege &&
            $this->session_ok();
    }

    public function authorize($requiredPrivilege = 1) {
        if (!$this->is_authorized($requiredPrivilege)) {
            if ($requiredPrivilege == self::PRIV_LOGOUT) {
                self::redirect('index.php');
            }
            $continue = urlencode(basename($_SERVER['SCRIPT_NAME']).'?'.$_SERVER['QUERY_STRING']);
            self::redirect('index.php?source=login&continue='.$continue); // does not return
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

    public static function redirect($to = false) {
        if (!$to) {
            $to = 'index.php';
        }
        $server = $_SERVER['SERVER_NAME'];
        $path = dirname($_SERVER['SCRIPT_NAME']);
        header('Location: http://'.$server.$path.'/'.$to);
        exit;
    }

    public static function login(db $db, $name, $pwd) {
        $result = $db->query(
           "SELECT
                `id`,
                `privilege`
            FROM `user` WHERE
                `name`      = '".$db->protect($name)."' AND
                `pwd_hash`  = '".$db->protect(hash('sha256', $pwd))."'"
        );
        if (!($row = $result->fetch_assoc())) {
            return -1; // user not found or wrong password
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
        return $row['privilege']; // privilege is always positive
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