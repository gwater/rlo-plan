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
require_once('misc.inc.php');
require_once('logger.inc.php');

class ovp_entry extends ovp_asset {
    private static $attributes = array('date', 'teacher', 'time', 'course',
                                       'subject', 'duration', 'sub',
                                       'change', 'oldroom', 'newroom');

    public static function get_attributes() {
        return self::$attributes;
    }

    public static function normalize_date($time) {
        return $time - ($time % (60 * 60 * 24));
    }

    /**
     * Adds an entry to the database.
     * @return: id of the new entry
     */
    public function add(db $db, $values) {
        $db->query(
           "INSERT INTO `entry` VALUES (
                NULL,
                '".$db->protect($values['date'])    ."',
                '".$db->protect($values['teacher']) ."',
                '".$db->protect($values['time'])    ."',
                '".$db->protect($values['course'])  ."',
                '".$db->protect($values['subject']) ."',
                '".$db->protect($values['duration'])."',
                '".$db->protect($values['sub'])     ."',
                '".$db->protect($values['change'])  ."',
                '".$db->protect($values['oldroom']) ."',
                '".$db->protect($values['newroom']) ."'
            )"
        );
        $row = $db->query(
            "SELECT `id` FROM `entry` WHERE
                `date`     = '".$db->protect($values['date'])    ."' AND
                `teacher`  = '".$db->protect($values['teacher']) ."' AND
                `time`     = '".$db->protect($values['time'])    ."' AND
                `course`   = '".$db->protect($values['course'])  ."' AND
                `subject`  = '".$db->protect($values['subject']) ."' AND
                `duration` = '".$db->protect($values['duration'])."' AND
                `sub`      = '".$db->protect($values['sub'])     ."' AND
                `change`   = '".$db->protect($values['change'])  ."' AND
                `oldroom`  = '".$db->protect($values['oldroom']) ."' AND
                `newroom`  = '".$db->protect($values['newroom']) ."'
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
    public static function get_entries_by_teacher(db $db, $date) {
        $result = $db->query(
           "SELECT `teacher` FROM `entry`
            WHERE `date` = '".$db->protect($date)."'
            GROUP BY `teacher`
            ORDER BY SUBSTRING(`teacher`, 6) ASC");
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
                WHERE `date` = '".$db->protect($date)."'
                AND `teacher` = '".$db->protect($teacher)."'
                ORDER BY `time` ASC");
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
        $dates = self::get_dates($db);
        if (!$dates) {
            return false;
        }
        $entries_by_date = array();
        foreach ($dates as $date) {
            if ($entries_by_teacher = self::get_entries_by_teacher($db, $date)) {
                $entries_by_date[] = $entries_by_teacher;
            }
        }
        return $entries_by_date;
    }

    // used by ovp_public
    public static function get_entries_by_date(db $db) {
        $dates = self::get_dates($db);
        if (!$dates) {
            return false;
        }
        $entries_by_date = array();
        foreach ($dates as $date) {
            $result = $db->query(
               "SELECT `id` FROM `entry`
                WHERE `date` = '".$db->protect($date)."'
                ORDER BY `time` ASC");
            $entries = array();
            while ($row = $result->fetch_assoc()) {
                $entries[] = new ovp_entry($db, $row['id']);
            }
            if (count($entries) > 0) {
                $entries_by_date[] = $entries;
            }
        }
        return $entries_by_date;
    }

    public static function get_dates(db $db) {
        $result = $db->query(
           "SELECT `date` FROM `entry`
            GROUP BY `date`
            ORDER BY `date` ASC");
        $dates = array();
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row['date'];
        }
        return $dates;
    }

    /**
     * Deletes entries older than DELETE_OLDER_THAN days.
     * @return: the number of deleted entries
     */
    public static function cleanup($db) {
        if (DELETE_OLDER_THAN >= 0) {
            $adjust = 0;
            if (SKIP_WEEKENDS) {
                $row = $db->query(
                   "SELECT DAYOFWEEK(CURDATE()) AS 'weekday'")->fetch_assoc();
                if ($row['weekday'] == 7) {
                    $adjust = 1; // Saturday
                } else if ($row['weekday'] == 1) {
                    $adjust = 2; // Sunday
                }
            }
            $db->query(
               "DELETE FROM `entry` WHERE
                    DATEDIFF(CURDATE(), `date`) > '".$db->protect(DELETE_OLDER_THAN + $adjust)."'"
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
            fail('ID ungültig');
        }
        parent::__construct($db, $id);
    }

    public function get_values() {
        $row = $this->db->query(
           "SELECT  `date`
                    `teacher`,
                    `time`,
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
        $affected = false;
        foreach ($values as $attribute => $value) {
            if (in_array($attribute, self::$attributes)) {
                if ($this->set_value($attribute, $value)) {
                    $affected = true;
                }
            } else {
                // DoNothing (tm)
            }
        }
        return $affected;
    }

    private function get_value($attribute) {
        $row = $this->db->query(
           "SELECT `".$this->db->protect($attribute)."` FROM `entry`
            WHERE `id` = '".$this->id."' LIMIT 1")->fetch_assoc();
        return $row[$attribute];
    }

    private function set_value($attribute, $value) {
        if ($this->get_value($attribute) == $this->db->protect($value)) {
            return true;
        }
        $this->db->query(
           "UPDATE `entry`
            SET `".$this->db->protect($attribute)."` = '".$this->db->protect($value)."'
            WHERE `id` = '".$this->id."' LIMIT 1");
        return $this->db->affected_rows == 1;
    }

    public function get_date() {
        $row = $this->db->query(
           "SELECT DATE_FORMAT(`date`, '%W, %d.%m.%Y') AS 'date' FROM `entry`
            WHERE `id` = '".$this->id."' LIMIT 1")->fetch_assoc();
        return $row['date'];
    }

    public function get_time() {
        $row = $this->db->query(
           "SELECT TIME_FORMAT(`time`, '%h:%i') AS 'time' FROM `entry`
            WHERE `id` = '".$this->id."' LIMIT 1")->fetch_assoc();
        return $row['time'];
    }

}


?>
