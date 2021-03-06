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
require_once('interfaces.inc.php');

class ovp_entry {
    private $db;
    private $id;
    private static $attributes = array('date', 'teacher', 'time', 'course',
                                       'subject', 'duration', 'sub',
                                       'change', 'oldroom', 'newroom');

    public static function get_attributes() {
        return self::$attributes;
    }

    public static function normalize_date($time) {
        return $time - ($time % (60 * 60 * 24));
    }

    public function __construct($id) {
        $this->db = ovp_db::get_singleton();
        $result = $this->db->query(
           "SELECT `id`
            FROM `entry`
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

    public function get_values() {
        $row = $this->db->query(
           "SELECT
                `date`,
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
            WHERE `id` = '".$this->id."'
            LIMIT 1"
        )->fetch_assoc();
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
           "SELECT `".$this->db->protect($attribute)."`
            FROM `entry`
            WHERE `id` = '".$this->id."'
            LIMIT 1"
        )->fetch_assoc();
        return $row[$attribute];
    }

    private function set_value($attribute, $value) {
        if ($this->get_value($attribute) == $this->db->protect($value)) {
            return true;
        }
        $this->db->query(
           "UPDATE `entry`
            SET `".$this->db->protect($attribute)."` = ".$this->db->prepare($value)."
            WHERE `id` = '".$this->id."'
            LIMIT 1"
        );
        return $this->db->affected_rows == 1;
    }

    public function get_date() {
        $row = $this->db->query(
           "SELECT DATE_FORMAT(`date`, '%W, %d.%m.%Y') AS 'date'
            FROM `entry`
            WHERE `id` = '".$this->id."'
            LIMIT 1"
        )->fetch_assoc();
        return $row['date'];
    }

    public function get_time() {
        $row = $this->db->query(
           "SELECT TIME_FORMAT(`time`, '%H:%i') AS 'time'
            FROM `entry`
            WHERE `id` = '".$this->id."'
            LIMIT 1"
        )->fetch_assoc();
        return $row['time'];
    }
}

class ovp_entry_manager {
    private static $singleton;
    private $db;

    private function __construct() {
        $this->db = ovp_db::get_singleton();
        $this->cleanup();
    }

    public static function get_singleton() {
        if (self::$singleton === null) {
            self::$singleton = new self;
        }
        return self::$singleton;
    }

    /**
     * Adds an entry to the database.
     * @return: id of the new entry
     */
    public function add($values) {
        $this->db->query(
           "INSERT INTO `entry` (
                `date`,
                `teacher`,
                `time`,
                `course`,
                `subject`,
                `duration`,
                `sub`,
                `change`,
                `oldroom`,
                `newroom`
            ) VALUES (
                ".$this->db->prepare($values['date'])    .",
                ".$this->db->prepare($values['teacher']) .",
                ".$this->db->prepare($values['time'])    .",
                ".$this->db->prepare($values['course'])  .",
                ".$this->db->prepare($values['subject']) .",
                ".$this->db->prepare($values['duration']).",
                ".$this->db->prepare($values['sub'])     .",
                ".$this->db->prepare($values['change'])  .",
                ".$this->db->prepare($values['oldroom']) .",
                ".$this->db->prepare($values['newroom']) ."
            )"
        );
        return $this->db->insert_id;
    }

    public function import($values, $overwrite = false) {
        $method = $overwrite ? 'REPLACE' : 'INSERT';
        return $this->db->query(
            $method." `entry` (
                `id`,
                `date`,
                `teacher`,
                `time`,
                `course`,
                `subject`,
                `duration`,
                `sub`,
                `change`,
                `oldroom`,
                `newroom`
            ) VALUES (
                ".$this->db->prepare($values['id'      ]).",
                ".$this->db->prepare($values['date'    ]).",
                ".$this->db->prepare($values['teacher' ]).",
                ".$this->db->prepare($values['time'    ]).",
                ".$this->db->prepare($values['course'  ]).",
                ".$this->db->prepare($values['subject' ]).",
                ".$this->db->prepare($values['duration']).",
                ".$this->db->prepare($values['sub'     ]).",
                ".$this->db->prepare($values['change'  ]).",
                ".$this->db->prepare($values['oldroom' ]).",
                ".$this->db->prepare($values['newroom' ])."
            )",
        false) || !$overwrite;
    }

    /**
     * Deletes the entry referenced by the id $id.
     * @return: true if the entry was found and deleted
     */
    public function remove($id) {
        $this->db->query(
           "DELETE FROM `entry`
            WHERE `id` = '".$this->db->protect($id)."'
            LIMIT 1"
        );
        return $this->db->affected_rows == 1;
    }

    // used by ovp_print
    public function get_entries_by_teacher($date) {
        $result = $this->db->query(
           "SELECT `teacher`
            FROM `entry`
            WHERE `date` = '".$this->db->protect($date)."'
            GROUP BY `teacher`
            ORDER BY SUBSTRING(`teacher`, 6)"
        );
        $teachers = array();
        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row['teacher'];
        }
        $entries_by_teacher = array();
        foreach ($teachers as $teacher) {
            $result = $this->db->query(
               "SELECT `id`
                FROM `entry`
                WHERE `date` = '".$this->db->protect($date)."'
                AND `teacher` = '".$this->db->protect($teacher)."'
                ORDER BY `time`"
            );
            $entries = array();
            while ($row = $result->fetch_assoc()) {
                $entries[] = new ovp_entry($row['id']);
            }
            $entries_by_teacher[$teacher] = $entries;
        }
        return $entries_by_teacher;
    }

    // used by ovp_author
    public function get_entries_by_teacher_and_date() {
        $dates = $this->get_dates();
        if (!$dates) {
            return false;
        }
        $entries_by_date = array();
        foreach ($dates as $date) {
            if ($entries_by_teacher = $this->get_entries_by_teacher($date)) {
                $entries_by_date[] = $entries_by_teacher;
            }
        }
        return $entries_by_date;
    }

    // used by ovp_public
    public function get_entries_by_date() {
        $dates = $this->get_dates();
        if (!$dates) {
            return false;
        }
        $entries_by_date = array();
        foreach ($dates as $date) {
            $result = $this->db->query(
               "SELECT `id`
                FROM `entry`
                WHERE `date` = '".$this->db->protect($date)."'
                ORDER BY `time`"
            );
            $entries = array();
            while ($row = $result->fetch_assoc()) {
                $entries[] = new ovp_entry($row['id']);
            }
            if (count($entries) > 0) {
                $entries_by_date[] = $entries;
            }
        }
        return $entries_by_date;
    }

    // used by ovp_public?course=...
    public function get_entries_for_course($course) {
        $dates = $this->get_dates();
        if (!$dates) {
            return false;
        }
        $course = $this->db->protect($course);
        $entries_by_date = array();
        foreach ($dates as $date) {
            if (!($year = substr($course, 0, strpos($course, '.')))) {
                $year = $course;
            }
            $result = $this->db->query(
               "SELECT `id`
                FROM `entry`
                WHERE
                    `date` = '".$this->db->protect($date)."' AND (
                        `course` = '".$course."' OR
                        `course` = '".$year."' OR
                        SUBSTR(`course`, 1, LOCATE('/', `course`) - 1) = '".$year."' OR
                        SUBSTR(`course`, LOCATE('/', `course`) + 1) = '".$year."' OR
                        `course` = 'alle'
                    )
                ORDER BY `time`"
            );
            $entries = array();
            while ($row = $result->fetch_assoc()) {
                $entries[] = new ovp_entry($row['id']);
            }
            if (count($entries) > 0) {
                $entries_by_date[] = $entries;
            }
        }
        return $entries_by_date;
    }

    public function get_entries_for_sub($sub) {
        $dates = $this->get_dates();
        if (!$dates) {
            return false;
        }
        $sub = $this->db->protect($sub);
        $entries_by_date = array();
        foreach ($dates as $date) {
            $result = $this->db->query(
               "SELECT `id`
                FROM `entry`
                WHERE
                    `date` = '".$this->db->protect($date)."' AND
                    `sub` = '".$sub."'
                ORDER BY `time`"
            );
            $entries = array();
            while ($row = $result->fetch_assoc()) {
                $entries[] = new ovp_entry($row['id']);
            }
            if (count($entries) > 0) {
                $entries_by_date[] = $entries;
            }
        }
        return $entries_by_date;
    }

    public function get_courses() {
        $result = $this->db->query(
           "SELECT `course`
            FROM `entry`
            WHERE `course` IS NOT NULL
            GROUP BY `course`"
        );
        $courses = array();
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row['course'];
        }
        return self::clean_courses($courses);
    }

    private static function clean_courses($courses) {
        // copy, to avoid conflicts
        $others = $courses;
        foreach ($courses as $key => $course) {
            if (strpos($course, '/') !== false) {
                unset($courses[$key]);
            } else if (strpos($course, '.') === false) {
                // don't show courses like '9' when there are classes like '9.1'
                foreach ($others as $other) {
                    if (strpos($other, $course.'.') !== false || ($course == 'alle')) {
                        unset($courses[$key]);
                    }
                }
            }
        }
        // repair the array
        return array_values($courses);
    }

    public function get_dates() {
        $result = $this->db->query(
           "SELECT `date`
            FROM `entry`
            GROUP BY `date`"
        );
        $dates = array();
        while ($row = $result->fetch_assoc()) {
            $dates[] = $row['date'];
        }
        return $dates;
    }

    public function get_subs() {
        $result = $this->db->query(
           "SELECT `sub`
            FROM `entry`
            WHERE `sub` IS NOT NULL
            GROUP BY `sub`"
        );
        $subs = array();
        while ($row = $result->fetch_assoc()) {
            $subs[] = $row['sub'];
        }
        return $subs;
    }

    /**
     * Deletes entries older than DELETE_OLDER_THAN days.
     * @return: the number of deleted entries
     */
    public function cleanup() {
        $config = ovp_config::get_singleton();
        $delete_older_than = $config->get('DELETE_OLDER_THAN');
        if ($delete_older_than >= 0) {
            $today = $this->get_today();
            $oldest_date = $this->adjust_date($today, -$delete_older_than);
            $this->db->query(
               "DELETE FROM `entry`
                WHERE DATEDIFF('".$oldest_date."', `date`) > 0"
            );
            return $this->db->affected_rows;
        } else {
            return 0;
        }
    }

    /**
     * @brief Adjusts the date by $adjust and SKIP_WEEKEND
     * @returns for $adjust < 0: a date at least $adjust days before $date
     *          for $adjust >= 0: a date at least $adjust days after $date
     *          (additional days compensate weekends if SKIP_WEEKENDS is enabled)
     */
    public function adjust_date($date, $adjust = 0) {
        if ($adjust != 0) {
            $row = $this->db->query(
               "SELECT DATE_ADD('".$date."', INTERVAL '".$adjust."' DAY) AS 'date'
                LIMIT 1"
            )->fetch_assoc();
            $date = $row['date'];
        }
        $config = ovp_config::get_singleton();
        $skip = $config->get('SKIP_WEEKENDS');
        if ($skip) {
            $row = $this->db->query(
               "SELECT DAYOFWEEK('".$date."') AS 'weekday'
                LIMIT 1"
            )->fetch_assoc();
            if ($adjust < 0) {
                $adjust = 0;
                if ($row['weekday'] == 7) {
                    $adjust = -1; // Saturday->Friday
                } else if ($row['weekday'] == 1) {
                    $adjust = -2; // Sunday->Friday
                }
            } else {
                $adjust = 0;
                if ($row['weekday'] == 7) {
                    $adjust = 2; // Saturday->Monday
                } else if ($row['weekday'] == 1) {
                    $adjust = 1; // Sunday->Monday
                }
            }
            $row = $this->db->query(
               "SELECT DATE_ADD('".$date."', INTERVAL '".$adjust."' DAY) AS 'date'
                LIMIT 1"
            )->fetch_assoc();
            $date = $row['date'];
        }
        return $date;
    }

    public function get_today() {
        $row = $this->db->query(
           "SELECT CURDATE() AS 'today'
            LIMIT 1"
        )->fetch_assoc();
        return $row['today'];
    }

    public function format_date($date) {
        $row = $this->db->query(
           "SELECT DATE_FORMAT('".$date."', '%W, %d.%m.%Y') AS 'date'
            LIMIT 1"
        )->fetch_assoc();
        return $row['date'];
    }

}

?>
