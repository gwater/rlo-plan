<?php

class entry {

    public $id;
    public $time;
    public $teacher;
    public $subject;
    public $duration;
    public $course;
    public $oldroom;
    public $sub;
    public $change;
    // TODO: what else?

    public function __construct($id, $time, $teacher, $subject, $duration, $course, $oldroom, $sub, $change) {
        $this->id = $id;
        $this->time = $time;
        $this->teacher = $teacher;
        $this->subject = $subject;
        $this->duration = $duration;
        $this->course = $course;
        $this->oldroom = $oldroom;
        $this->sub = $sub;
        $this->change = $change;
    }

}

?>
