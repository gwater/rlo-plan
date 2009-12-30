<?php

require_once('db.inc.php');
require_once('misc.inc.php');
require_once('logger.inc.php');

class ovp_entry extends ovp_asset {
    private static $attributes = array('teacher', 'time', 'course',
                                       'subject', 'duration', 'sub',
                                       'change', 'oldroom', 'newroom');

    public static function get_attributes() {
        return self::$attributes;
    }

    public static function normalize_date($time) {
        return $time - ($time % (60 * 60 * 24));
    }

    private static function to_time($day, $time) {
        if (!preg_match('/(\d\d?).(\d\d?).((\d\d)?\d\d) (\d\d?).(\d\d?)/', $day.' '.$time, $matches)) {
            fail('invalid day or time format');
        }
        $unix = mktime($matches[5], $matches[6], 0, $matches[2], $matches[1], $matches[3]);
        if ($unix == false || $unix == -1) {
            fail('invalid day or time value');
        }
        return $unix;
    }

    /**
     * Adds an entry to the database.
     * @return: id of the new entry
     */
    public function add(db $db, $values) {
        $time = self::to_time($values['day'], $values['time']);
        $db->query(
           "INSERT INTO `entry` VALUES (
                NULL,
                '".$db->protect($values['teacher'])."',
                FROM_UNIXTIME('".$db->protect($time)."'),
                '".$db->protect($values['course'])."',
                '".$db->protect($values['subject'])."',
                '".$db->protect($values['duration'])."',
                '".$db->protect($values['sub'])."',
                '".$db->protect($values['change'])."',
                '".$db->protect($values['oldroom'])."',
                '".$db->protect($values['newroom'])."'
            )"
        );
        $row = $db->query(
            "SELECT `id` FROM `entry` WHERE
                `teacher`  = '".$db->protect($values['teacher'])."'  AND
                `time`     = FROM_UNIXTIME('".$db->protect($time)."') AND
                `course`   = '".$db->protect($values['course'])."'   AND
                `subject`  = '".$db->protect($values['subject'])."'  AND
                `duration` = '".$db->protect($values['duration'])."' AND
                `sub`      = '".$db->protect($values['sub'])."'      AND
                `change`   = '".$db->protect($values['change'])."'   AND
                `oldroom`  = '".$db->protect($values['oldroom'])."'  AND
                `newroom`  = '".$db->protect($values['newroom'])."'
            LIMIT 1")->fetch_assoc();
        return $row['id'];
    }

    /**
     * Deletes the entry referenced by the id $id.
     * @return: true if the entry was found and deleted
     */
    public function remove(db $db, $id) {
        $db->query(
           "DELETE FROM `entry` WHERE
                `id` = '".$db->protect($id)."'
            LIMIT 1");
        return $db->affected_rows == 1;
    }

    // used by ovp_print
    public static function get_entries_by_teacher(db $db, $time) {
        $date = self::normalize_date($time);
        $result = $db->query(
           "SELECT `teacher` FROM `entry`
            WHERE UNIX_TIMESTAMP(`time`) >= '".$db->protect($date)."' AND
                  UNIX_TIMESTAMP(`time`) <  '".$db->protect($date + 60*60*24)."'
            GROUP BY `teacher`
            ORDER BY SUBSTRING(`teacher`, 6)");
        $teachers = array();
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row['teacher'];
        }
        $entries_by_teacher = array();
        foreach ($teachers as $teacher) {
            $result = $db->query(
               "SELECT
                    `id`
                FROM `entry`
                WHERE UNIX_TIMESTAMP(`time`) >= '".$db->protect($date)."' AND
                      UNIX_TIMESTAMP(`time`) <  '".$db->protect($date + 60*60*24)."'
                AND `teacher` = '".$db->protect($teacher)."'
                ORDER BY `time`");
                $entries = array();
                while ($row = $result->fetch_assoc()) {
                    $entries[] = new ovp_entry($db, $row['id']);
                }
                $entries_by_teacher[$teacher] = $entries;
        }
        return $entries_by_teacher;
    }

    // used by ovp_author
    public static function get_entries_by_teacher_and_date(db $db) {
        $row = $db->query(
           "SELECT UNIX_TIMESTAMP(`time`) AS 'time' FROM `entry`
            ORDER BY `time` ASC LIMIT 1")->fetch_assoc();
        $start = $row['time'];
        $row = $db->query(
            "SELECT UNIX_TIMESTAMP(`time`) AS 'time' FROM `entry`
             ORDER BY `time` DESC LIMIT 1")->fetch_assoc();
        $stop = $row['time'] + 1;
        if (!isset($start) || !isset($stop)) {
            return false;
        }
        $entries_by_date = array();
        for ($date = $start; $date < $stop; $date += 60*60*24) {
            $entries_by_date[] = self::get_entries_by_teacher($db, $date);
        }
        return $entries_by_date;
    }

    // used by ovp_public
    public static function get_entries_by_date(db $db) {
        $row = $db->query(
           "SELECT UNIX_TIMESTAMP(`time`) AS 'time' FROM `entry`
            ORDER BY `time` ASC LIMIT 1")->fetch_assoc();
        $start = self::normalize_date($row['time']);
        $row = $db->query(
            "SELECT UNIX_TIMESTAMP(`time`) AS 'time' FROM `entry`
             ORDER BY `time` DESC LIMIT 1")->fetch_assoc();
        $stop = $row['time'] + 1;
        if (!isset($start) || !isset($stop)) {
            return false;
        }
        $entries_by_date = array();
        for ($date = $start; $date < $stop; $date += 60*60*24) {
            $result = $db->query(
               "SELECT
                    `id`
                FROM `entry` WHERE
                    UNIX_TIMESTAMP(`time`) >= '".$db->protect($date)."' AND
                    UNIX_TIMESTAMP(`time`) <  '".$db->protect($date + 60*60*24)."'
                ORDER BY
                    `time`");
            $entries = array();
            for ($i = 0; $i < $result->num_rows; $i++) {
                $row = $result->fetch_assoc();
                $entries[] = new ovp_entry($db, $row['id']);
            }
            $entries_by_date[] = $entries;
        }
        return $entries_by_date;
    }

    /**
     * Deletes entries older than DELETE_OLDER_THAN days.
     * @return: the number of deleted entries
     */
    public static function cleanup($db) {
        if (DELETE_OLDER_THAN >= 0) {
            $db->query(
               "DELETE FROM `entry` WHERE
                    DATEDIFF(CURDATE(), `time`) > '".$db->protect(DELETE_OLDER_THAN)."'"
            );
            return $db->affected_rows;
        } else {
            return 0;
        }
    }


    public function __construct(db $db, $id) {
        $result = $db->query(
           "SELECT `id` FROM `entry`
            WHERE `id` = '".$db->protect($id)."'
            LIMIT 1");
        if ($result->num_rows != 1) {
            fail('invalid entry id');
        }
        parent::__construct($db, $id);
    }

    public function get_values() {
        $row = $this->db->query(
           "SELECT  `teacher`,
                    UNIX_TIMESTAMP(`time`) AS 'time',
                    `course`,
                    `subject`,
                    `duration`,
                    `sub`,
                    `change`,
                    `oldroom`,
                    `newroom`
            FROM `entry`
            WHERE `id` = '".$this->id."' LIMIT 1")->fetch_assoc();
        return $row;
    }

    public function set_values($values) {
        $time = self::to_time($values['day'], $values['time']);
        foreach ($values as $attribute => $value) {
            if (!in_array($attribute, self::$attributes)) {
                // DoNothing (tm) -> eg $values['day']
            } else {
                if ($attribute == 'time') {
                    $value = self::to_time($values['day'], $values['time']);
                }
                if (!$this->set_value($attribute, $value)) {
                    return false;
                }
            }
        }
        return true;
    }

    private function get_value($attribute) {
        if ($attribute == 'time') {
            $row = $this->db->query(
               "SELECT UNIX_TIMESTAMP(`time`) AS 'time' FROM `entry`
                WHERE `id` = '".$this->id."' LIMIT 1")->fetch_assoc();
        } else {
            $row = $this->db->query(
               "SELECT `".$this->db->protect($attribute)."` FROM `entry`
                WHERE `id` = '".$this->id."' LIMIT 1")->fetch_assoc();
        }
        return $row[$attribute];
    }

    private function set_value($attribute, $value) {
        if ($this->get_value($attribute) == $this->db->protect($value)) {
            return true;
        }
        if ($attribute == 'time') {
            $this->db->query(
               "UPDATE `entry`
                SET `time` = FROM_UNIXTIME('".$this->db->protect($value)."')
                WHERE `id` = '".$this->id."' LIMIT 1");
        } else {
            $this->db->query(
               "UPDATE `entry`
                SET `".$this->db->protect($attribute)."` = '".$this->db->protect($value)."'
                WHERE `id` = '".$this->id."' LIMIT 1");
        }
        return $this->db->affected_rows == 1;
    }

    public function get_date() {
        $values = $this->get_values();
        return strftime("%A, %d.%m.%Y", $values['time']);
    }

    public function get_time() {
        $values = $this->get_values();
        $str = strftime('%H:%M', $values['time']);
        if ($str[0] == '0') {
            return substr($str, 1);
        }
        return $str;
    }

}


?>
