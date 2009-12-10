<?php

class entry {

    public $id;         //unique id from the database
    public $time;       //starting time in a unix timestanp
    public $duration;   //new duration in minutes
    public $subject;    //the new subject or for 12/13 the course like "en-gk"
    public $course;     //class id like "9.2" or for 12/13 "12/13"
    public $teacher;    //the missing teacher like "Herr Brandes"
    public $sub;        //replacement teacher like "Herr Eckert"
    public $oldroom;    //original room like "H1.7"
    public $newroom;    //replacement room like "H1.9"
    public $change;     //any other changes like "Ausfall" or "Aufgaben selbstständig"
    // TODO: what else?

    public function __construct($id, $time, $teacher, $subject, $duration, $course, $oldroom, $sub, $change, $newroom) {
        $this->id = $id;
        $this->time = $time;
        $this->teacher = $teacher;
        $this->subject = $subject;
        $this->duration = $duration;
        $this->course = $course;
        $this->oldroom = $oldroom;
        $this->newroom = $newroom;
        $this->sub = $sub;
        $this->change = $change;
    }

    public function get_date() {
        return strftime("%A, %d.%m.%y", $this->time);
    }

    public function get_time() {
        return strftime('%H:%M', $this->time)
    }
}

?>
