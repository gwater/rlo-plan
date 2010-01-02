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
require_once('logger.inc.php');

abstract class ovp_asset {
    protected $id;
    protected $db;

    public function __construct(db $db, $id) {
        $this->db = $db;
        $this->id = $db->protect($id);
    }

    public function get_id() {
        return $this->id;
    }

}

class ovp_user extends ovp_asset {
    private static $roles = array(ovp_logger::VIEW_NONE   => 'none',
                                  ovp_logger::VIEW_PUBLIC => 'public',
                                  ovp_logger::VIEW_PRINT  => 'print',
                                  ovp_logger::VIEW_AUTHOR => 'author',
                                  ovp_logger::VIEW_ADMIN  => 'admin');

    public final static function get_roles() {
        return self::$roles;
    }

    public static function get_all_users(db $db) {
        $result = $db->query("SELECT `id`, `name` FROM `user`");
        $users = array();
        while ($row = $result->fetch_assoc()) {
            $users[] = new ovp_user($db, $row['id']);
        }
        return $users;
    }

    public static function get_user_by_name(db $db, $name) {
        $result = $db->query("SELECT `id` FROM `user` WHERE `name` = '".$db->protect($name)."'");
        if ($row = $result->fetch_assoc()) {
            return new ovp_user($db, $row['id']);
        }
        return NULL;
    }

    public static function role_to_privilege($newrole) {
        foreach (self::$roles as $priv => $role) {
            if ($newrole == $role) {
                return $priv;
            }
        }
        return ovp_logger::VIEW_NONE;
    }

    public static function name_exists(db $db, $name) {
        $result = $db->query(
           "SELECT `id` FROM `user`
            WHERE `name` = '".$db->protect($name)."'");
        return $result->num_rows != 0;
    }

    public static function add(db $db, $name, $password, $role) {
        if (self::name_exists($db, $name)) {
            return false;
        }
        $hash = hash('sha256', $password);
        $privilege = self::role_to_privilege($role);
        $db->query(
           "INSERT INTO `user` (
                `name`,
                `pwd_hash`,
                `privilege`
            ) VALUES (
                '".$db->protect($name)."',
                '".$db->protect($hash)."',
                '".$db->protect($privilege)."'
            )"
        );
        $row = $db->query(
           "SELECT `id` FROM `user` WHERE
                `name`      = '".$db->protect($name)."' AND
                `pwd_hash`  = '".$db->protect($hash)."' AND
                `privilege` = '".$db->protect($privilege)."'
            LIMIT 1")->fetch_assoc();
        return $row['id'];
    }

    public static function remove(db $db, $id) {
        $user = ovp_logger::get_current_user($db);
        if ($user->get_id() == $id) {
            ovp_msg::fail('Eigener Account darf nicht gelöscht werden');
        }
        $db->query(
           "DELETE FROM `user`
            WHERE `id` = '".$db->protect($id)."'
            LIMIT 1");
        return $db->affected_rows == 1;
    }

    public function __construct(db $db, $id) {
        $result = $db->query(
           "SELECT `id` FROM `user`
            WHERE `id` = '".$db->protect($id)."'
            LIMIT 1");
        if ($result->num_rows != 1) {
            ovp_msg::fail('ID ungültig');
        }
        parent::__construct($db, $id);
    }

    public function get_privilege() {
        $result = $this->db->query(
            "SELECT `privilege` FROM `user`
             WHERE `id` = '".$this->id."'
             LIMIT 1")->fetch_assoc();
        return $result['privilege'];
    }

    public function get_name() {
        $result = $this->db->query(
           "SELECT `name` FROM `user`
            WHERE `id` = '".$this->id."'
            LIMIT 1")->fetch_assoc();
        return $result['name'];
    }

    public function check_password($password) {
        $hash = $this->db->query(
           "SELECT `pwd_hash` FROM `user`
            WHERE `id` = '".$this->id."'
            LIMIT 1");
        if ($hash == hash('sha256', $password)) {
            return true;
        } else {
            return false;
        }
    }

    public function set_privilege($newpriv) {
        foreach (self::$roles as $priv => $role) {
            if ($newpriv == $priv) {
                return $this->db->query(
                   "UPDATE `user`
                    SET `privilege` = '".$this->db->protect($newpriv)."'
                    WHERE `id` = '".$this->id."' LIMIT 1");
            }
        }
        return false;
    }

    public function set_role($role) {
        $priv = self::role_to_privilege($role);
        return $this->set_privilege($priv);
    }

    public function set_name($name) {
        if (self::name_exists($this->db, $name)) {
            return false;
        }
        return $this->db->query(
           "UPDATE `user`
            SET `name` = '".$this->db->protect($name)."'
            WHERE `id` = '".$this->id."' LIMIT 1");
    }

    public function set_password($password) {
        $hash = hash('sha256', $password);
        return $this->db->query(
           "UPDATE `user`
            SET `pwd_hash` = '".$this->db->protect($hash)."'
            WHERE `id` = '".$this->id."' LIMIT 1");
    }
}

?>
