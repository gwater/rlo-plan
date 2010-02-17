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
    private $db;
    private $attr;
    private $attr_changed = false;

    // user privilege levels to authorize access to specific sources and posters
    const PRIV_LOGOUT = -2; // user is logged out
    const PRIV_LOGIN  = -1; // user is logged in
    const VIEW_NONE   =  0; // user has no privileges (account frozen)
    const VIEW_PUBLIC =  1; // user may see public data
    const VIEW_PRINT  =  2; // user may see sensitive data
    const VIEW_AUTHOR =  3; // user may add, remove and edit entries
    const VIEW_ADMIN  =  4; // user may add, remove and edit accounts

    private static $roles = array(
        self::VIEW_NONE   => 'none',
        self::VIEW_PUBLIC => 'public',
        self::VIEW_PRINT  => 'print',
        self::VIEW_AUTHOR => 'author',
        self::VIEW_ADMIN  => 'admin'
    );

    public final static function get_roles() {
        return self::$roles;
    }

    public static function role_to_privilege($newrole) {
        return array_search($newrole, self::$roles);
    }

    public function __construct($id = 'guest') {
        $this->db = ovp_db::get_singleton();
        if ($id == 'guest' || !($this->attr = $this->db->query(
           "SELECT
                `id`,
                `name`,
                `pwd_hash`,
                `privilege`
            FROM `user`
            WHERE `id` = '".$this->db->protect($id)."'
            LIMIT 1"
        )->fetch_assoc())) {
            unset($_SESSION['uid']);
            $config = ovp_config::get_singleton();
            $this->attr = array(
                'id' => 'guest',
                'privilege' => $config->get('PRIV_DEFAULT')
            );
        }
    }

    public function __destruct() {
        if ($this->attr_changed) {
            if (!$this->db->query(
               "UPDATE `user`
                SET
                    `name`      = '".$this->db->protect($this->attr['name'     ])."',
                    `pwd_hash`  = '".$this->db->protect($this->attr['pwd_hash' ])."',
                    `privilege` = '".$this->db->protect($this->attr['privilege'])."'
                WHERE `id`      = '".$this->db->protect($this->attr['id'       ])."'
                LIMIT 1"
            )) {
                ovp_http::fail('Konto konnte nicht aktualisiert werden');
            }
        }
    }

    public function get($attr_name) {
        if ($attr_name == 'pwd_hash' && !ovp_user_manager::get_current_user()->is_authorized(self::VIEW_ADMIN)) {
            ovp_http::fail('unberechtigter Zugriff');
        }
        return $this->_get($attr_name);
    }

    private function _get($attr_name) {
        $attr_value = $this->attr[$attr_name];
        if (isset($attr_value)) {
            return $attr_value;
        }
        return false;
    }

    public function set($key, $value) {
        switch ($key) {
        case 'name':
            if (($id = ovp_user_manager::get_singleton()->name_exists($value)) !== false) {
                if ($id === $this->attr['id']) {
                    return true;
                }
                ovp_http::fail('Name ist schon vorhanden');
            }
            break;
        case 'pwd':
            $key = 'pwd_hash';
            $value = hash('sha256', $value);
            break;
        case 'pwd_hash':
            if (strlen($value) != 64) {
                return false;
            }
            $value = $this->db->protect($value);
            break;
        case 'privilege':
            if ($_SESSION['uid'] == $this->attr['id']) {
                ovp_http::fail('Eigener Account darf nicht degradiert werden!');
            }
            if (!array_key_exists($value, self::$roles)) {
                ovp_http::fail('Unbekannte Rolle "'.htmlspecialchars($value).'"');
            }
            break;
        case 'role':
            return (($priv = self::role_to_privilege($value)) !== false) && $this->set('privilege', $priv);
        }
        if (!isset($this->attr[$key])) {
            ovp_http::fail('Unbekanntes Attribut "'.htmlspecialchars($key).'"');
        }
        $this->attr[$key] = $value;
        $this->attr_changed = true;
        return true;
    }

    public function check_password($password) {
        return $this->attr['pwd_hash'] == hash('sha256', $password);
    }

    public function is_authorized($priv_req = 1) {
        $logged_in = $this->attr['id'] != 'guest' && $this->session_ok();
        if ($priv_req == self::PRIV_LOGIN) {
            return $logged_in;
        } else if ($priv_req == self::PRIV_LOGOUT) {
            return !$logged_in;
        }
        if ($priv_req <= ovp_config::get_singleton()->get('PRIV_DEFAULT')) {
            return true;
        }
        return $logged_in && ($priv_req <= $this->get('privilege'));
    }

    public function authorize($priv_req = 1) {
        if (!$this->is_authorized($priv_req)) {
            if ($priv_req == self::PRIV_LOGOUT) {
                ovp_http::redirect(basename($_SERVER['SCRIPT_NAME']));
            }
            $continue = basename($_SERVER['SCRIPT_NAME']);
            if (!empty($_SERVER['QUERY_STRING'])) {
                $continue .= '?'.$_SERVER['QUERY_STRING'];
            }
            ovp_http::redirect('index.php?source=login&continue='.urlencode($continue));
        }
        return true;
    }

    private function session_ok() {
        if (!($ok = $_SESSION['ip'] == $_SERVER['REMOTE_ADDR'])) {
            ovp_user_manager::get_singleton()->logout();
        }
        return $ok;
    }
}

class ovp_user_manager {
    private static $singleton;
    private static $current_user = false;
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
        return $this->db->insert_id;
    }

    public function remove($id) {
        if ($_SESSION['uid'] == $id) {
            ovp_http::fail('Eigener Account darf nicht gelöscht werden!');
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

    public static function get_current_user() {
        if (self::$current_user === false) {
            if (isset($_SESSION['uid'])) {
                self::$current_user = new ovp_user($_SESSION['uid']);
            } else {
                self::$current_user = new ovp_user('guest');
            }
        }
        return self::$current_user;
    }
}

?>
