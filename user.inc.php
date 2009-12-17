<?php

class user {
    // FIXME: No need for $id; $name is unique
    public $id;
    public $name;
    public $pwd_hash;
    public $privilege;

    public function __construct($a) {
        $roles = array(VIEW_NONE   => 'none',
                       VIEW_PUBLIC => 'public',
                       VIEW_PRINT  => 'print',
                       VIEW_AUTHOR => 'author',
                       VIEW_ADMIN  => 'admin');

        if (isset($a['id']) && is_numeric($a['id'])) {
            $this->id = $a['id'];
        }
        if (isset($a['name'])) {
            $this->name = $a['name'];

        }
        if (isset($a['privilege'])){
            $this->privilege = $a['privilege'];
        } else if (isset($a['role'])) {
            $this->privilege = $a['role'];
            foreach ($roles as $i => $role){
                if ($a['role'] == $role){
                    $this->privilege = $i;
                }
            }
        }
        // FIXME: Not very elegant...
        if (isset($a['password']) && ($a['password'] != '***')) {
            $this->pwd_hash = hash('sha256', $a['password']);
        }
    }
}

?>
