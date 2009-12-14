<?php

class user {
    public $priv;
    public $id;
    public $name;

    public function __construct($id, $name, $priv) {
        $this->name = $name;
        $this->id = $id;
        $this->priv = $priv;
    }
}

?>
