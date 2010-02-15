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

require_once('interfaces.inc.php');
require_once('db.inc.php');

class ovp_user {
    private $id;
    private $db;
    // user privilege levels to authorize access to specific sources and posters
    const PRIV_LOGOUT = -2; // user is logged out
    const PRIV_LOGIN  = -1; // user is logged in
    const VIEW_NONE   =  0; // user has no privileges (account frozen)
    const VIEW_PUBLIC =  1; // user may see public data
    const VIEW_PRINT  =  2; // user may see sensitive data
    const VIEW_AUTHOR =  3; // user may add, remove and edit entries
    const VIEW_ADMIN  =  4; // user may add, remove and edit accounts

    private static $roles = array(self::VIEW_NONE   => 'none',
                                  self::VIEW_PUBLIC => 'public',
                                  self::VIEW_PRINT  => 'print',
                                  self::VIEW_AUTHOR => 'author',
                                  self::VIEW_ADMIN  => 'admin');

    public final static function get_roles() {
        return self::$roles;
    }

    public static function role_to_privilege($newrole) {
        foreach (self::$roles as $priv => $role) {
            if ($newrole == $role) {
                return $priv;
            }
        }
        return self::VIEW_NONE;
    }

    public function __construct($id = 'guest') {
        $this->db = ovp_db::get_singleton();
        if ($id == 'guest') {
            $this->id = $id;
            return;
        }
        $result = $this->db->query(
           "SELECT `id`
            FROM `user`
            WHERE `id` = '".$this->db->protect($id)."'
            LIMIT 1"
        );
        if ($result->num_rows != 1) {
            ovp_http::fail('ID ungültig');
        }
        $this->id = $id;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_privilege() {
        $result = $this->db->query(
           "SELECT `privilege`
            FROM `user`
            WHERE `id` = '".$this->id."'
            LIMIT 1"
        )->fetch_assoc();
        return $result['privilege'];
    }

    public function get_name() {
        $result = $this->db->query(
           "SELECT `name`
            FROM `user`
            WHERE `id` = '".$this->id."'
            LIMIT 1"
        )->fetch_assoc();
        return $result['name'];
    }

    public function get_pwd_hash() {
        $user_manager = ovp_user_manager::get_singleton();
        $current_user = $user_manager->get_current_user();
        if ($current_user->is_authorized(self::VIEW_ADMIN)) {
            return $this->_get_pwd_hash();
        }
        return false;
    }

    private function _get_pwd_hash() {
        $row = $this->db->query(
           "SELECT `pwd_hash`
            FROM `user`
            WHERE `id` = '".$this->id."'
            LIMIT 1"
        )->fetch_assoc();
        return $row['pwd_hash'];
    }

    public function check_password($password) {
        return $this->_get_pwd_hash() == hash('sha256', $password);
    }

    public function set_privilege($newpriv) {
        $manager = ovp_user_manager::get_singleton();
        $admin = $manager->get_current_user();
        if ($admin->get_id() == $this->id) {
            ovp_http::fail('Eigener Account darf nicht degradiert werden');
        }
        foreach (self::$roles as $priv => $role) {
            if ($newpriv == $priv) {
                return $this->db->query(
                   "UPDATE `user`
                    SET `privilege` = '".$this->db->protect($newpriv)."'
                    WHERE `id` = '".$this->id."'
                    LIMIT 1"
                );
            }
        }
        return false;
    }

    public function set_role($role) {
        $priv = self::role_to_privilege($role);
        return $this->set_privilege($priv);
    }

    public function set_name($name) {
        $user_manager = ovp_user_manager::get_singleton();
        if (($id = $user_manager->name_exists($name)) !== false) {
            if ($id === $this->id) {
                return true;
            }
            ovp_http::fail('Name ist schon vorhanden');
        }
        return $this->db->query(
           "UPDATE `user`
            SET `name` = '".$this->db->protect($name)."'
            WHERE `id` = '".$this->id."'
            LIMIT 1"
        );
    }

    public function set_password($password, $is_hash = false) {
        if ($is_hash) {
            $hash = $this->db->protect($password);
        } else {
            $hash = hash('sha256', $password);
        }
        return $this->db->query(
           "UPDATE `user`
            SET `pwd_hash` = '".$hash."'
            WHERE `id` = '".$this->id."'
            LIMIT 1"
        );
    }

    public function is_authorized($priv_req = 1) {
        $logged_in = $this->id != 'guest';
        if ($priv_req == self::PRIV_LOGIN) {
            return $logged_in;
        } else if ($priv_req == self::PRIV_LOGOUT) {
            return !$logged_in;
        }
        $config = ovp_config::get_singleton();
        $priv_default = $config->get('PRIV_DEFAULT');
        if ($priv_req <= $priv_default) {
            return true;
        }
        if ($logged_in) {
            if ($priv_req <= $this->get_privilege()) {
                return $this->session_ok();
            }
        }
        return false;
    }

    public function authorize($priv_req = 1) {
        if (!$this->is_authorized($priv_req)) {
            if ($priv_req == self::PRIV_LOGOUT) {
                ovp_http::redirect(basename($_SERVER['SCRIPT_NAME']));
            }
            $continue = basename($_SERVER['SCRIPT_NAME']);
            if ($_SERVER['QUERY_STRING'] != '') {
                $continue .= '?'.$_SERVER['QUERY_STRING'];
            }
            $link = 'index.php?source=login&continue='.urlencode($continue);
            ovp_http::redirect($link); // does not return
        }
        return true;
    }

    // checks if the current user's ip address matches the one in the session
    public function session_ok() {
        return $_SERVER['REMOTE_ADDR'] == $_SESSION['ip'];
    }
}

class ovp_user_manager {
    private static $singleton;
    private $db;

    private function __construct() {
        $this->db = ovp_db::get_singleton();
    }

    public static function get_singleton() {
        if (self::$singleton === null) {
            self::$singleton = new self;
        }
        return self::$singleton;
    }

    public function get_all_users() {
        $result = $this->db->query(
           "SELECT `id`
            FROM `user`
            ORDER BY `name`"
        );
        $users = array();
        while ($row = $result->fetch_assoc()) {
            $users[] = new ovp_user($row['id']);
        }
        return $users;
    }

    public function get_user_by_name($name) {
        if ($row = $this->db->query(
           "SELECT `id`
            FROM `user`
            WHERE `name` = '".$this->db->protect($name)."'
            LIMIT 1"
        )->fetch_assoc()) {
            return new ovp_user($row['id']);
        }
        return NULL;
    }

    public function name_exists($name) {
        $row = $this->db->query(
           "SELECT `id`
            FROM `user`
            WHERE `name` = '".$this->db->protect($name)."'
            LIMIT 1"
        )->fetch_assoc();
        if ($row) {
            return $row['id'];
        }
        return false;
    }

    public function import($values, $overwrite = false) {
        if (!is_numeric($values['id']) ||
            $values['name'] === '' ||
            strlen($values['pwd_hash']) != 64 ||
            !is_numeric($values['privilege'])) {
            return false;
        }
        $method = $overwrite ? 'REPLACE' : 'INSERT';
        return $this->db->query(
            $method." `user` (
                `id`,
                `name`,
                `pwd_hash`,
                `privilege`
            ) VALUES (
                ".$this->db->prepare($values['id'       ]).",
                ".$this->db->prepare($values['name'     ]).",
                ".$this->db->prepare($values['pwd_hash' ]).",
                ".$this->db->prepare($values['privilege'])."
            )",
        false) || !$overwrite;
    }

    public function add($name, $password, $role) {
        if (self::name_exists($name)) {
            return false;
        }
        $hash = hash('sha256', $password);
        $privilege = ovp_user::role_to_privilege($role);
        $this->db->query(
           "INSERT INTO `user` (
                `name`,
                `pwd_hash`,
                `privilege`
            ) VALUES (
                '".$this->db->protect($name)."',
                '".$this->db->protect($hash)."',
                '".$this->db->protect($privilege)."'
            )"
        );
        $row = $this->db->query(
           "SELECT `id`
            FROM `user`
            WHERE
                `name`      = '".$this->db->protect($name)."' AND
                `pwd_hash`  = '".$this->db->protect($hash)."' AND
                `privilege` = '".$this->db->protect($privilege)."'
            LIMIT 1"
        )->fetch_assoc();
        return $row['id'];
    }

    public function remove($id) {
        if ($_SESSION['uid'] == $id) {
            ovp_http::fail('Eigener Account darf nicht gelöscht werden');
        }
        $this->db->query(
           "DELETE FROM `user`
            WHERE `id` = '".$this->db->protect($id)."'
            LIMIT 1"
        );
        return $this->db->affected_rows == 1;
    }

    public function login($name, $pwd) {
        if (!($row = $this->db->query(
           "SELECT `id`
            FROM `user`
            WHERE
                `name`      = '".$this->db->protect($name)."' AND
                `pwd_hash`  = '".$this->db->protect(hash('sha256', $pwd))."'
            LIMIT 1"
        )->fetch_assoc())) {
            return false; // user not found or wrong password
        }
        $_SESSION['uid'] = $row['id'];
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        return true;
    }

    public function logout() {
        $_SESSION = array();
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        session_destroy();
    }

    public function get_current_user() {
        if (isset($_SESSION['uid'])) {
            return new ovp_user($_SESSION['uid']);
        }
        return new ovp_user('guest');
    }
}

?>
