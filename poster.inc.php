<?php

require_once('db.inc.php');
require_once('user.inc.php');
require_once('misc.inc.php');

abstract class poster {
    public static $priv_req;
    protected $db;
    abstract public function evaluate($post);

    public function __construct(db $db) {
        $this->db = $db;
    }

}

class post_user extends poster {
    public static $priv_req = VIEW_ADMIN;

    public function __construct(db $db) {
        parent::__construct($db);
    }

    public function evaluate($post) {
        switch ($post['action']) {
        case 'add':
            if (isset($post['name']) && isset($post['password']) && isset($post['role'])) {
                $id = ovp_user::add($this->db, $post['name'], $post['password'], $post['role']);
                exit($id);
            }
            fail('parameter missing');
        case 'update':
            if (!isset($post['id'])) {
                fail('parameter missing');
            } else if (!is_numeric($post['id'])) {
                fail('invalid id');
            }
            $user = new ovp_user($this->db, $post['id']);
            $result = true;
            foreach ($post as $key => $value) {
                switch ($key) {
                case 'id':
                case 'action':
                    break;
                case 'name':
                    $result = $user->set_name($value);
                    break;
                case 'password':
                    $result = $user->set_password($value);
                    break;
                case 'role':
                    $result = $user->set_role($value);
                    break;
                default:
                    fail('invalid data');
                }
                if (!($result)) {
                    fail('Why Luigi, why?');
                }
            }
            exit('updated');
        case 'delete':
            if (!isset($post['id'])) {
                fail('parameter missing');
            } else if (!is_numeric($post['id'])) {
                fail('invalid id');
            } else if (ovp_user::remove($this->db, $post['id'])) {
                exit('deleted');
            }
            fail('id not found');
        default:
            fail('invalid action');
        }
    }
}

class post_password extends poster {
    public static $priv_req = PRIV_LOGIN;

    public function __construct(db $db) {
        parent::__construct($db);
    }

    public function evaluate($post) {
        if (isset($post['newpwd']) && isset($post['oldpwd'])) {
            $user = ovp_user::get_current_user($this->db);
            if ($user->check_password($post['oldpwd'])) {
                if ($user->set_password($post['newpwd'])) {
                    exit('updated');
                } else {
                    fail('...la familia, Luigi...');
                }
            } else {
                fail('old password incorrect');
            }
        } else {
            fail('parameters missing');
        }
    }
}

class post_login extends poster {
    public static $priv_req = PRIV_LOGOUT;

    public function __construct(db $db) {
        parent::__construct($db);
    }

    public function evaluate($post) {
        if (isset($post['name']) && isset($post['pwd'])) {
            $_SESSION['privilege'] = $this->db->login($post['name'], $post['pwd']);
            if ($_SESSION['privilege'] != -1) {
                redirect($_GET['continue']);
            }
        }
        redirect('index.php?source=login&attempt=failed&continue='.urlencode($_GET['continue']));
    }
}

class post_logout extends poster {
    public static $priv_req = PRIV_LOGIN;

    public function __construct(db $db) {
        parent::__construct($db);
    }

    public function evaluate($post) {
        $this->db->logout();
        $_SESSION = array();
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        session_destroy();
        redirect('index.php');
    }
}

class post_entry extends poster {
    public static $priv_req = VIEW_AUTHOR;

    public function __construct(db $db) {
        parent::__construct($db);
    }

    public function evaluate($post) {
        switch ($post['action']) {
        case 'add':
            if (!(isset($post['day'])     && isset($post['teacher'])  &&
                isset($post['time'])    && isset($post['course'])   &&
                isset($post['subject']) && isset($post['duration']) &&
                isset($post['sub'])     && isset($post['change'])   &&
                isset($post['oldroom']) && isset($post['newroom']))) {
                fail('parameter missing');
            }
            $entry = new entry($post);
            exit($this->db->add_entry($entry));
        case 'update':
            if (!(isset($post['id'])      &&
                isset($post['day'])     && isset($post['teacher'])  &&
                isset($post['time'])    && isset($post['course'])   &&
                isset($post['subject']) && isset($post['duration']) &&
                isset($post['sub'])     && isset($post['change'])   &&
                isset($post['oldroom']) && isset($post['newroom']))) {
                fail('parameter missing');
            }
            $entry = new entry($post);
            if ($this->db->update_entry($entry)) {
                exit('updated');
            } else {
                fail('invalid data');
            }
        case 'delete':
            if (!(isset($post['id']) && is_numeric($post['id']))) {
                fail('invalid id');
            }
            if (!$this->db->remove_entry($post['id'])) {
                fail('id not found');
            } else {
                exit('deleted');
            }
        default:
            fail('invalid action');
        }
    }

}

?>