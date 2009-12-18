<?php

require_once('config.inc.php');
require_once('entry.inc.php');
require_once('user.inc.php');

class db extends mysqli {

    public function __construct() {
        parent::__construct(DB_HOST, DB_USER, DB_PASS);
        if ($this->connect_errno) {
            $this->fail('could not connect to database server');
        }
        $this->query("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'");
        $this->query("SET @@time_zone = 'Europe/Berlin'");
        if (!$this->select_db(DB_BASE)) {
            $this->create_db();
        }
        if (FIRST_RUN) {
            $this->reset_tables();
            $config = file_get_contents('config.inc.php');
            $config = preg_replace('/(?<=define\(\'FIRST_RUN\', )true(?=\);)/i', 'false', $config, 1);
            file_put_contents('config.inc.php', $config);
        }
    }

    /**
     * Adds an entry to the database.
     * @return: id of the new entry
     */
    public function add_entry(entry $entry) {
        $this->query(
           "INSERT INTO `entry` VALUES (
                NULL,
                '".$this->protect($entry->teacher)."',
                FROM_UNIXTIME('".$this->protect($entry->time)."'),
                '".$this->protect($entry->course)."',
                '".$this->protect($entry->subject)."',
                '".$this->protect($entry->duration)."',
                '".$this->protect($entry->sub)."',
                '".$this->protect($entry->change)."',
                '".$this->protect($entry->oldroom)."',
                '".$this->protect($entry->newroom)."'
            )"
        );
        $row = $this->query(
            "SELECT `id` FROM `entry` WHERE
                `teacher`  = '".$this->protect($entry->teacher)."'  AND
                `time`     = FROM_UNIXTIME('".$this->protect($entry->time)."') AND
                `course`   = '".$this->protect($entry->course)."'   AND
                `subject`  = '".$this->protect($entry->subject)."'  AND
                `duration` = '".$this->protect($entry->duration)."' AND
                `sub`      = '".$this->protect($entry->sub)."'      AND
                `change`   = '".$this->protect($entry->change)."'   AND
                `oldroom`  = '".$this->protect($entry->oldroom)."'  AND
                `newroom`  = '".$this->protect($entry->newroom)."'")->fetch_assoc();
        return $row['id'];
    }

    /**
     * Updates the entry referenced by $entry->id with the data inside $entry.
     * @return: true if the update was successful
     */
    public function update_entry(entry $entry) {
        $this->query(
            "UPDATE `entry` SET
                `teacher`  = '".$this->protect($entry->teacher)."',
                `time`     = FROM_UNIXTIME('".$this->protect($entry->time)."'),
                `course`   = '".$this->protect($entry->course)."',
                `subject`  = '".$this->protect($entry->subject)."',
                `duration` = '".$this->protect($entry->duration)."',
                `sub`      = '".$this->protect($entry->sub)."',
                `change`   = '".$this->protect($entry->change)."',
                `oldroom`  = '".$this->protect($entry->oldroom)."',
                `newroom`  = '".$this->protect($entry->newroom)."'
             WHERE
                `id` = '".$this->protect($entry->id)."'");
        return $this->affected_rows == 1;
    }

    /**
     * Deletes the entry referenced by the id $entry_id.
     * @return: true if the entry was found and deleted
     */
    public function remove_entry($entry_id) {
        $this->query("DELETE FROM `entry` WHERE `id` = '".$this->protect($entry_id)."' LIMIT 1");
        return $this->affected_rows == 1;
    }

    /**
     * Retrieves an array of entries from the database on the given day.
     * Format of $date: unix timestamp of midnight (the beginning) on the requested day.
     */
    public function get_entries($date = -1) {
        if ($date == -1) {
            $result = $this->query(
               "SELECT
                    `id`,
                    `teacher`,
                    UNIX_TIMESTAMP(`time`) AS 'time',
                    `course`,
                    `subject`,
                    `duration`,
                    `sub`,
                    `change`,
                    `oldroom`,
                    `newroom`
                FROM `entry` ORDER BY
                    UNIX_TIMESTAMP(`time`) - MOD(UNIX_TIMESTAMP(`time`), 60*60*24),
                    `teacher`,
                    `time`"
            );
        } else {
            $result = $this->query(
               "SELECT
                    `id`,
                    `teacher`,
                    UNIX_TIMESTAMP(`time`) AS 'time',
                    `course`,
                    `subject`,
                    `duration`,
                    `sub`,
                    `change`,
                    `oldroom`,
                    `newroom`
                FROM `entry` WHERE
                    UNIX_TIMESTAMP(`time`) >= '".$this->protect($date)."' AND
                    UNIX_TIMESTAMP(`time`) <  '".$this->protect($date + 60*60*24)."'
                ORDER BY
                    `teacher`,
                    `time`"
            );
        }
        $entries = array();
        while ($row = $result->fetch_assoc()) {
            $entries[] = new entry($row);
        }
        return $entries;
    }

    /**
     * Deletes entries older than DELETE_OLDER_THAN days.
     * @return: the number of deleted entries
     */
    public function cleanup_entries() {
        if (DELETE_OLDER_THAN >= 0) {
            $this->query(
               "DELETE FROM `entry` WHERE
                    DATEDIFF(CURDATE(), `time`) > '".$this->protect(DELETE_OLDER_THAN)."'"
            );
            return $this->affected_rows;
        } else {
            return 0;
        }
    }

    /**
     * Adds a user to the database.
     * @return: the id of the new user
     */
    public function add_user(user $user) {
        $this->query(
           "INSERT INTO `user` (
                `name`,
                `pwd_hash`,
                `privilege`
            ) VALUES (
                '".$this->protect($user->name)."',
                '".$this->protect($user->pwd_hash)."',
                '".$this->protect($user->privilege)."'
            )"
        );
        $row = $this->query(
            "SELECT `id` FROM `user` WHERE
                `name`      = '".$this->protect($user->name)."'  AND
                `pwd_hash`  = '".$this->protect($user->pwd_hash)."' AND
                `privilege` = '".$this->protect($user->privilege)."'")->fetch_assoc();
        return $row['id'];
    }

    /**
     * Deletes the user referenced by the id $user_id.
     * @return: true if the user was found and deleted
     */
    public function remove_user($user_id) {
        $this->query("DELETE FROM `user` WHERE `id` = '".$this->protect($user_id)."' LIMIT 1");
        return $this->affected_rows == 1;
    }

    /**
     * Retrieves an array of user accounts from the database.
     * The array will contain at most USERS_PER_PAGE user objects
     * ordered by their field $sortby (id, name, or privilege).
     * @params: $page   - the page to retrieve
     *          $sortby - the field name by which the array is to be sorted
     * @return: array of user objects or null if the parameters are invalid
     */
    public function get_users($page, $sortby) {
        $result = $this->query(
           "SELECT
                `id`,
                `name`,
                `privilege`
            FROM `user` LIMIT ".(($page - 1) * USERS_PER_PAGE).", ".USERS_PER_PAGE);
        $users = array();
        while ($row = $result->fetch_assoc()) {
            $users[] = new user($row);
        }
        return $users;
    }

    public function get_current_user() {
        $ip = ip2long($_SERVER['REMOTE_ADDR']);
        $result = $this->query(
           "SELECT
                `id`,
                `name`,
                `privilege`
            FROM `user` WHERE
                `ip1` = '".$this->protect($ip & 0xFFFFFFFFFFFFFFFF)."' AND
                `ip2` = '".$this->protect($ip >> 64)."' AND
                `sid` = '".$this->protect(session_id())."'"
        );
        if (!($row = $result->fetch_assoc())) {
            return NULL; // ip or sid not found
        }
        return new user($row);
    }

    // checks if the current user's ip address matches the one in the database
    public function session_ok() {
        $result = $this->query(
           "SELECT
                `ip1`,
                `ip2`
            FROM `user` WHERE
                `sid`  = '".$this->protect(session_id())."'"
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

    public function login($name, $pwd) {
        $result = $this->query(
           "SELECT
                `id`,
                `privilege`
            FROM `user` WHERE
                `name`      = '".$this->protect($name)."' AND
                `pwd_hash`  = '".$this->protect(hash('sha256', $pwd))."'"
        );
        if (!($row = $result->fetch_assoc())) {
            return -1; // user not found or wrong password
        }
        $ip = ip2long($_SERVER['REMOTE_ADDR']);
        $ip1 = $ip & 0xFFFFFFFFFFFFFFFF;
        $ip2 = $ip >> 64;
        $this->query(
           "UPDATE `user` SET
                `ip1` = '".$this->protect($ip1)."',
                `ip2` = '".$this->protect($ip2)."',
                `sid` = '".$this->protect(session_id())."'
            WHERE
                `id` = '".$this->protect($row['id'])."'"
        );
        return $row['privilege']; // privilege is always positive
    }

    public function logout() {
        $this->query(
           "UPDATE `user` SET
                `ip1` = NULL,
                `ip2` = NULL,
                `sid` = NULL
            WHERE
                `sid` = '".$this->protect(session_id())."'"
        );
        return $this->affected_rows == 1;
    }

    /**
     * Changes the password of the user specified by $userid to $newpwd
     * (set $oldpwd to anything you like, preferrably NULL).
     * --- OR ---
     * Changes the password of the current user from $oldpwd to $newpwd
     * (leave out $userid).
     */
    public function change_pwd($newpwd, $oldpwd, $userid = -1) {
        if ($userid > 0) {
            $this->query(
               "UPDATE `user` SET
                    `pwd_hash` = '".$this->protect(hash('sha256', $newpwd))."'
                WHERE
                    `id` = '".$this->protect($userid)."'
                LIMIT 1");
            return $this->affected_rows == 1;
        } elseif ($oldpwd) {
            $ip = ip2long($_SERVER['REMOTE_ADDR']);
            $ip1 = $ip & 0xFFFFFFFFFFFFFFFF;
            $ip2 = $ip >> 64;
            $this->query(
               "UPDATE `user` SET
                    `pwd_hash` = '".$this->protect(hash('sha256', $newpwd))."'
                WHERE
                    `pwd_hash` = '".$this->protect(hash('sha256', $oldpwd))."' AND
                    `sid` = '".$this->protect(session_id())."' AND
                    `ip1` = '".$this->protect($ip1)."' AND
                    `ip2` = '".$this->protect($ip2)."'
                LIMIT 1");
            return $this->affected_rows == 1;
        }
        die('urnotdoinitrite');
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
        $this->query("CREATE DATABASE `".$this->protect(DB_BASE)."` CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci'");
        $this->select_db(DB_BASE);
    }

    private function reset_tables() {
        /*
        This table holds the user data of all the students who have access.
        id:        unique user id used to identify user during their session
        name:      user name, e.g. 'jdoe'
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
        $this->query("CREATE TABLE `user` (
            `id`        INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name`      VARCHAR(20)      NOT NULL,
            `pwd_hash`  CHAR(64)         NOT NULL,
            `privilege` TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `ip1`       BIGINT UNSIGNED  NULL     DEFAULT NULL,
            `ip2`       BIGINT UNSIGNED  NULL     DEFAULT NULL,
            `sid`       INT UNSIGNED     NULL     DEFAULT NULL)"
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
            `teacher`  VARCHAR(30)       NULL     DEFAULT NULL,
            `time`     TIMESTAMP         NULL     DEFAULT NULL,
            `course`   VARCHAR(5)        NULL     DEFAULT NULL,
            `subject`  VARCHAR(5)        NULL     DEFAULT NULL,
            `duration` SMALLINT UNSIGNED NULL     DEFAULT NULL,
            `sub`      VARCHAR(30)       NULL     DEFAULT NULL,
            `change`   VARCHAR(40)       NULL     DEFAULT NULL,
            `oldroom`  VARCHAR(5)        NULL     DEFAULT NULL,
            `newroom`  VARCHAR(5)        NULL     DEFAULT NULL)"
        );

        $admin = new user(array('name'=>'admin', 'pwd'=>ADMIN_PWD, 'privilege'=>4));
        $this->add_user($admin);
    }

    private function fail($msg) {
        die('ERROR: '.$msg);
    }
}
?>
