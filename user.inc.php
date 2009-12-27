<?php

require_once('misc.inc.php');

abstract class ovp_asset {
    protected $id;
    protected $db;

    public function __construct(db $db, $id) {
        $this->db = $db;
        $this->id = $db->protect($id);
    }
}

class ovp_user extends ovp_asset {
    private static $roles = array(VIEW_NONE   => 'none',
                                  VIEW_PUBLIC => 'public',
                                  VIEW_PRINT  => 'print',
                                  VIEW_AUTHOR => 'author',
                                  VIEW_ADMIN  => 'admin');

    public final static function get_roles() {
        return self::$roles;
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

    public static function get_all_users(db $db) {
        $result = $db->query("SELECT `id`, `name` FROM `user`");
        $users = array();
        while ($row = $result->fetch_assoc()) {
            if ($row['name'] != 'admin') {
                $users[] = new ovp_user($db, $row['id']);
            }
        }
        return $users;

    }

    public static function role_to_privilege($newrole) {
        foreach (self::$roles as $priv => $role) {
            if ($newrole == $role) {
                return $priv;
            }
        }
        return VIEW_NONE;
    }

    public static function check_name(db $db, $name) {
        $result = $db->query(
           "SELECT `id` FROM `user`
            WHERE `name` = '".$db->protect($name)."'");
        if ($result->num_rows != 0) {
            return false;
        }
        return true;
    }

    public static function add(db $db, $name, $password, $role) {
        if (!self::check_name($db, $name)) {
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
        $db->query(
           "DELETE FROM `user`
            WHERE `id` = '".$db->protect($id)."'
            AND `name` != 'admin' LIMIT 1");
        return $db->affected_rows == 1;
    }

    public function __construct(db $db, $id) {
        $result = $db->query(
           "SELECT `id` FROM `user`
            WHERE `id` = '".$db->protect($id)."'
            LIMIT 1");
        if ($result->num_rows != 1) {
            fail('invalid user id');
        }
        parent::__construct($db, $id);
    }

    public function get_id() {
        return $this->id;
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
        if (!self::check_name($this->db, $name)) {
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
