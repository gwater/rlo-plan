<?php

require_once('db.inc.php');
require_once('user.inc.php');
require_once('misc.inc.php');
require_once('entry.inc.php');
require_once('logger.inc.php');

abstract class poster {
    public static $priv_req;
    protected $db;
    abstract public function evaluate($post);

    public function __construct(db $db) {
        $this->db = $db;
    }

}

class post_user extends poster {
    public static $priv_req = ovp_logger::VIEW_ADMIN;

    public function evaluate($post) {
        switch ($post['action']) {
        case 'add':
            if (isset($post['name']) && isset($post['password']) && isset($post['role'])) {
                $id = ovp_user::add($this->db, $post['name'], $post['password'], $post['role']);
                if ($id){
                    exit($id);
                } else {
                    fail('could not add user');
                }
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
                    fail('too much data');
                }
                if (!($result)) {
                    fail('invalid data');
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
    public static $priv_req = ovp_logger::PRIV_LOGIN;

    public function evaluate($post) {
        if (isset($post['newpwd']) && isset($post['oldpwd'])) {
            $user = ovp_logger::get_current_user($this->db);
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
    public static $priv_req = ovp_logger::PRIV_LOGOUT;

    public function evaluate($post) {
        if (isset($post['name']) && isset($post['pwd'])) {
            $_SESSION['privilege'] = ovp_logger::login($this->db, $post['name'], $post['pwd']);
            if ($_SESSION['privilege'] != -1) {
                ovp_logger::redirect($_GET['continue']);
            }
        }
        ovp_logger::redirect('index.php?source=login&attempt=failed&continue='.urlencode($_GET['continue']));
    }
}

class post_logout extends poster {
    public static $priv_req = ovp_logger::PRIV_LOGIN;

    public function evaluate($post) {
        ovp_logger::logout($this->db);
        $_SESSION = array();
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        session_destroy();
        ovp_logger::redirect('index.php');
    }
}

class post_entry extends poster {
    public static $priv_req = ovp_logger::VIEW_AUTHOR;

    public function evaluate($post) {
        switch ($post['action']) {
        case 'add':
            if (!(isset($post['date'])  && isset($post['teacher'])  &&
                isset($post['time'])    && isset($post['course'])   &&
                isset($post['subject']) && isset($post['duration']) &&
                isset($post['sub'])     && isset($post['change'])   &&
                isset($post['oldroom']) && isset($post['newroom']))) {
                fail('parameter missing');
            }
            if (ovp_entry::add($this->db, $post)) {
                exit('success');
            }
            fail('could no add entry');
        case 'update':
            if (!(isset($post['id'])    &&
                isset($post['date'])    && isset($post['teacher'])  &&
                isset($post['time'])    && isset($post['course'])   &&
                isset($post['subject']) && isset($post['duration']) &&
                isset($post['sub'])     && isset($post['change'])   &&
                isset($post['oldroom']) && isset($post['newroom']))) {
                fail('parameter missing');
            }
            $entry = new ovp_entry($this->db, $post['id']);
            if ($entry->set_values($post)) {
                exit('updated');
            } else {
                fail('invalid data');
            }
        case 'delete':
            if (!(isset($post['id']) && is_numeric($post['id']))) {
                fail('invalid id');
            }
            if (!ovp_entry::remove($this->db, $post['id'])) {
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