<?php

/**
 * - cleanup_entries():
 *      just a maintenance thing. remove all data no longer needed from the db
 *      DATENSCHUTZ!!!
 * - verify_user($name, $pw) //???? no idea how that should work...
 * - remove_user($name)
 * - add_user($name, $pw_hash, $priv=0)
 */

require_once('config.inc.php');
require_once('entry.inc.php');

class db extends mysqli {

    public function __construct() {
        parent::__construct(DB_HOST, DB_USER, DB_PASS);
        if ($this->connect_errno) {
            $this->fail('could not connect to database server');
        }
        $this->query("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'");
        if (!$this->select_db(DB_BASE)) {
            $this->create_db();
        }
        if (false) { // TODO: check if tables exist
            $this->reset_tables();
        }
    }

    /**
     * Adds an entry to the database.
     */
    public function add_entry(entry $entry) {
        $this->query(
           "INSERT INTO `entry` VALUES (
                NULL,
                '".$entry->time."',
                '".$entry->teacher."',
                '".$entry->subject."',
                '".$entry->duration."',
                '".$entry->course."',
                '".$entry->oldroom."',
                '".$entry->newroom."',
                '".$entry->sub."',
                '".$entry->change."'
            )"
        );
    }

    /**
     * Deletes the entry reference by the id int $entry or entry $entry->id.
     * @return: true if the entry was found and deleted
     */
    public function remove_entry($entry) {
        if (is_numeric($entry)) {
            $id = $entry;
        } else {
            $id = $entry->id;
        }
        $this->query("DELETE FROM `entry` WHERE `id` = '".$id."' LIMIT 1");
        return $this->affected_rows == 1;
    }

    /**
     * Retrieves an array of entries from the database on the given day.
     * Format of $date: unix timestamp of midnight (the beginning) on the requested day.
     */
    public function get_entries($date = -1) {
        if ($date == -1) {
            $result = $this->query(
               "SELECT * FROM `entry` ORDER BY
                    `time` - MOD(`time`, 60*60*24),
                    `teacher`,
                    `time`"
            );
        } else {
            $result = $this->query(
               "SELECT * FROM `entry` WHERE
                    `time` >= '".$this->protect($date)."' AND
                    `time` <  '".$this->protect($date + 60*60*24)."'
                ORDER BY
                    `teacher`,
                    `time`"
            );
        }
        $entries = array();
        while ($row = $result->fetch_assoc()) {
            $entries[] = new entry($row['id'], $row['time'], $row['teacher'], $row['subject'], $row['duration'], $row['course'], $row['oldroom'], $row['sub'], $row['change']);
        }
        return $entries;
    }

    // returns the numerical representation of the ip address of the user with the specified session id
    public function get_ip($sid) {
        $result = $this->query(
           "SELECT
                `ip1`,
                `ip2`
            FROM `user` WHERE
                `sid`  = '".$this->protect($sid)."'"
        );
        if (!($row = $result->fetch_assoc())) {
            return -1; // session id not found
        }
        if ($row['ip2'] != NULL) {
            return ($row['ip2'] << 64) + $row['ip1'];
        } else {
            return $row['ip1'];
        }
    }

    public function login($name, $pwd, $ip, $session) {
        $result = $this->query(
           "SELECT
                `id`,
                `priv`
            FROM `user` WHERE
                `name` = '".$this->protect($name)."' AND
                `pwd`  = '".hash('sha256', $pwd)."'"
        );
        if (!($row = $result->fetch_assoc())) {
            return -1; // user not found or wrong password
        }
        $ip1 = $ip & 0xFFFFFFFFFFFFFFFF;
        $ip2 = $ip >> 64;
        $this->query(
           "UPDATE `user` SET
                `ip1` = '".$ip1."',
                `ip2` = '".$ip2."',
                `sid` = '".$this->protect($session)."'
            WHERE
                `id` = '".$row['id']."'"
        );
        return $row['priv']; // privilege is always positive
    }

    public function logout($sid) {
        $this->query(
           "UPDATE `user` SET
                `ip1` = NULL,
                `ip2` = NULL,
                `sid` = NULL
            WHERE
                `sid` = '".$this->protect($sid)."'"
        );
        return $this->affected_rows == 1;
    }

    // this function is only public because it was inherited as public! do not use form outside this class!
    public function query($query) {
        if (!($result = parent::query($query))) {
            if (DEBUG) {
                $this->fail($this->error);
            } else {
                $this->fail('invalid SQL query syntax');
            }
        }
        return $result;
    }

    private function protect($str) {
        return $this->escape_string(htmlspecialchars($str));
    }

    private function create_db() {
        $this->query("CREATE DATABASE `".DB_BASE."` CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci'");
        $this->select_db(DB_BASE);
    }

    private function reset_tables() {
        /*
        This table holds the user data of all the students who have access.
        id:        unique user id used to identify user during their session
        name:      user name, e.g. 'jdoe'
        pwd:       sha256-hashed password
        priv:      privilege level
                     0 - no rights whatsoever (useful for suspending accounts)
                     1 - view all data except for teacher names, default (students)
                     2 - view all data (teachers)
                     3 - view all data, and modify entries (Mrs. Lange I)
                     4 - view all data, modify entries, and add new users (root)
         ip1, ip2: the current IPv6 address if the user is logged in,
                   ip2 = NULL indicates that ip1 is an IPv4 address
         */
        $this->query("DROP TABLE IF EXISTS `user`");
        $this->query("CREATE TABLE `user` (
            `id`   INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(20)      NOT NULL,
            `pwd`  CHAR(64)         NOT NULL,
            `priv` TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `ip1`  BIGINT UNSIGNED  NULL     DEFAULT NULL,
            `ip2`  BIGINT UNSIGNED  NULL     DEFAULT NULL,
            `sid`  INT UNSIGNED     NULL     DEFAULT NULL)"
        );

        /*
        This table holds the timetable changes (including the good stuff such as cancelled classes...)
        id:       unique entry id used to identify an entry during modification
        time:     timestamp of the day and time the class would normally start (e.g. Friday, July 13th)
        teacher:  name of the absent teacher (e.g. Mr. Doe)
        subject:  name and type of the course or subject (e.g. Ma-LK)
        duration: duration of this class in minutes (e.g. 75) (TODO: old or new duration?)
        course:   name of the course (e.g. '9.3')
        oldroom:  room the class was supposed to take place in originally (e.g. H2-3)
        sub:      name of the substitute teacher (e.g. 'Fr. Musterfrau')
        change:   what class takes place [where] instead (e.g. 'Geschichte H0-2' or 'Ausfall')
        */
        $this->query("DROP TABLE IF EXISTS `entry`");
        $this->query("CREATE TABLE `entry` (
            `id`       INT UNSIGNED      NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `time`     TIMESTAMP         NULL     DEFAULT NULL,
            `teacher`  VARCHAR(30)       NULL     DEFAULT NULL,
            `subject`  VARCHAR(20)       NULL     DEFAULT NULL,
            `duration` SMALLINT UNSIGNED NULL     DEFAULT NULL,
            `course`   VARCHAR(3)        NULL     DEFAULT NULL,
            `oldroom`  VARCHAR(5)        NULL     DEFAULT NULL,
            `newroom`  VARCHAR(5)        NULL     DEFAULT NULL,
            `sub`      VARCHAR(30)       NULL     DEFAULT NULL,
            `change`   VARCHAR(50)       NULL     DEFAULT NULL)"
        );

        $this->query(
           "INSERT INTO `user` (
                `name`,
                `pwd`,
                `priv`
            ) VALUES (
                'admin',
                '".ADMIN_PWD."',
                '4'
            )"
        );
    }

    private function fail($msg) {
        die('ERROR: '.$msg);
    }
}
?>
