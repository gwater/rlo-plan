<?php

class user {

    public $id;
    public $name;
    public $pwd_hash;
    public $privilege;

    public function __construct($a) {
        if (isset($a['id'])) {
            $this->id = $a['id'];
        }
        $this->name = $a['name'];
        if (isset($a['pwd'])) {
            $this->pwd_hash = hash('sha256', $a['pwd']);
        }
        $this->privilege = $a['privilege'];
    }
}

?>
