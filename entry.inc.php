<?php

class entry {

    public $id;       // unique id from the database
    public $teacher;  // the missing teacher like "Herr Brandes"
    public $time;     // starting time in a unix timestamp
    public $course;   // class id like "9.2" or "12/13"
    public $subject;  // the new subject or for 12/13 the course like "En-GK"
    public $duration; // new duration in minutes
    public $change;   // any other changes like "Ausfall" or "Aufgaben selbstständig"
    public $sub;      // substitute teacher like "Herr Eckert"
    public $oldroom;  // original room like "H1-7"
    public $newroom;  // replacement room like "H1-9"

    public function __construct($a) {
        if (isset($a['id'])) {
            $this->id = $a['id'];
        } else {
            $this->id = 'NULL';
        }
        $this->teacher  = $a['teacher'];
        if (isset($a['day'])) {
            $this->time = $this->to_time($a['day'], $a['time']);
        } else {
            $this->time = $a['time'];
        }
        $this->course   = $a['course'];
        $this->subject  = $a['subject'];
        $this->duration = $a['duration'];
        $this->sub      = $a['sub'];
        $this->change   = $a['change'];
        $this->oldroom  = $a['oldroom'];
        $this->newroom  = $a['newroom'];
    }

    private function fail($msg) {
        header('HTTP/1.0 400 Bad Request');
        exit($msg);
    }

    private function to_time($day, $time) {
        if (!preg_match('/(\d\d?).(\d\d?).((\d\d)?\d\d) (\d\d?).(\d\d?)/', $day.' '.$time, $matches)) {
            $this->fail('invalid day or time format');
        }
        $unix = mktime($matches[5], $matches[6], 0, $matches[2], $matches[1], $matches[3]);
        if ($unix == false || $unix == -1) {
            $this->fail('invalid day or time value');
        }
        return $unix;
    }

    public function get_date() {
        return strftime("%A, %d.%m.%Y", $this->time);
    }

    public function get_time() {
        return strftime('%H:%M', $this->time);
    }
}

?>
