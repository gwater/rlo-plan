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
require_once('user.inc.php');
require_once('interfaces.inc.php');
require_once('entry.inc.php');

abstract class poster {
    public static $priv_req;
    abstract public function evaluate($post);
}

class post_user extends poster {
    public static $priv_req = ovp_user::VIEW_ADMIN;
    private $manager;

    public function __construct() {
        $this->manager = ovp_user_manager::get_singleton();
    }

    public function evaluate($post) {
        switch ($post['action']) {
        case 'add':
            if (isset($post['name']) && isset($post['password']) && isset($post['role'])) {
                $id = $this->manager->add($post['name'], $post['password'], $post['role']);
                if ($id) {
                    exit($id);
                } else {
                    ovp_http::fail('Hinzufügen gescheitert');
                }
            }
            ovp_http::fail('Daten unvollständig');
        case 'update':
            if (!isset($post['id'])) {
                ovp_http::fail('ID fehlt');
            }
            $user = new ovp_user($post['id']);
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
                    // DoNothing (tm)
                }
                if (!($result)) {
                    ovp_http::fail('Änderung gescheitert');
                }
            }
            exit('updated');
        case 'delete':
            if (!isset($post['id'])) {
                ovp_http::fail('ID fehlt');
            } else if ($this->manager->remove($post['id'])) {
                exit('deleted');
            }
            ovp_http::fail('ID ungültig');
        default:
            ovp_http::fail('Ungültige Anfrage');
        }
    }
}

class post_password extends poster {
    public static $priv_req = ovp_user::PRIV_LOGIN;
    private $user;

    public function __construct() {
        $manager = ovp_user_manager::get_singleton();
        $this->user = $manager->get_current_user();
    }

    public function evaluate($post) {
        if (isset($post['newpwd']) && isset($post['oldpwd'])) {
            if ($this->user->check_password($post['oldpwd'])) {
                if ($this->user->set_password($post['newpwd'])) {
                    exit('updated');
                } else {
                    ovp_http::fail('Passwort ändern gescheitert');
                }
            } else {
                ovp_http::fail('Altes Password inkorrekt');
            }
        } else {
            ovp_http::fail('Daten unvollständig');
        }
    }
}

class post_login extends poster {
    public static $priv_req = ovp_user::PRIV_LOGOUT;
    private $manager;

    public function __construct() {
        $this->manager = ovp_user_manager::get_singleton();
    }

    public function evaluate($post) {
        if (isset($post['name']) && isset($post['pwd'])) {
            if ($this->manager->login($post['name'], $post['pwd'])) {
                ovp_http::redirect($_GET['continue']);
            }
        }
        $link = ovp_http::get_source_link('login&attempt=failed&continue='.urlencode($_GET['continue']));
        ovp_http::redirect($link);
    }
}

class post_logout extends poster {
    public static $priv_req = ovp_user::PRIV_LOGIN;
    private $manager;

    public function __construct() {
        $this->manager = ovp_user_manager::get_singleton();
    }

    public function evaluate($post) {
        $this->manager->logout();
        $_SESSION = array();
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        session_destroy();
        ovp_http::redirect();
    }
}

class post_entry extends poster {
    public static $priv_req = ovp_user::VIEW_AUTHOR;
    private $manager;

    public function __construct() {
        $this->manager = ovp_entry_manager::get_singleton();
    }

    public function evaluate($post) {
        switch ($post['action']) {
        case 'add':
            if (!(isset($post['date'])  && isset($post['teacher'])  &&
                isset($post['time'])    && isset($post['course'])   &&
                isset($post['subject']) && isset($post['duration']) &&
                isset($post['sub'])     && isset($post['change'])   &&
                isset($post['oldroom']) && isset($post['newroom']))) {
                ovp_http::fail('Daten unvollständig');
            }
            if ($id = $this->manager->add($post)) {
                exit($id);
            }
            ovp_http::fail('Hinzufügen gescheitert');
        case 'update':
            if (!(isset($post['id'])    &&
                isset($post['date'])    && isset($post['teacher'])  &&
                isset($post['time'])    && isset($post['course'])   &&
                isset($post['subject']) && isset($post['duration']) &&
                isset($post['sub'])     && isset($post['change'])   &&
                isset($post['oldroom']) && isset($post['newroom']))) {
                ovp_http::fail('Daten unvollständig');
            }
            $entry = new ovp_entry($post['id']);
            if ($entry->set_values($post)) {
                exit('updated');
            }
            ovp_http::fail('Änderung gescheitert');
        case 'delete':
            if (!isset($post['id'])) {
                ovp_http::fail('ID fehlt');
            }
            if ($this->manager->remove($post['id'])) {
                exit('deleted');
            }
            ovp_http::fail('ID ungültig');
        default:
            ovp_http::fail('Ungültige Anfrage');
        }
    }
}

class post_mysql extends poster {
    public static $priv_req = ovp_user::VIEW_ADMIN;
    private $is_wiz;

    public function __construct($is_wiz = false) {
        $this->is_wiz = $is_wiz;
    }

    public function evaluate($post) {
        if (!(isset($post['host']) && isset($post['base']) && isset($post['user']) && isset($post['pass']))) {
            ovp_http::fail('Daten unvollständig');
        }
        $config = ovp_config::get_singleton();
        $config->set('DB_HOST', $post['host']);
        $config->set('DB_BASE', $post['base']);
        $config->set('DB_USER', $post['user']);
        $config->set('DB_PASS', $post['pass']);
        if ($error = ovp_db::check_creds($post['host'], $post['base'], $post['user'], $post['pass'])) {
            $link = ovp_http::get_source_link('mysql&error='.urlencode($error));
        } else {
            if ($this->is_wiz) {
                $db = ovp_db::get_singleton();
                $db->reset_tables();
                $link = ovp_http::get_source_link('settings');
            } else {
                $link = ovp_http::get_source_link('mysql');
            }
        }
        ovp_http::redirect($link);
    }
}

class post_settings extends poster {
    public static $priv_req = ovp_user::VIEW_ADMIN;
    private $is_wiz;

    public function __construct($is_wiz = false) {
        $this->is_wiz = $is_wiz;
    }

    public function evaluate($post) {
        if (!(isset($post['debug']) && isset($post['delold']) && isset($post['skipweekends']) && isset($post['privdefault']))) {
            ovp_http::fail('Daten unvollständig');
        }
        $config = ovp_config::get_singleton();
        $config->set('DEBUG',             $post['debug']);
        $config->set('DELETE_OLDER_THAN', $post['delold']);
        $config->set('SKIP_WEEKENDS',     $post['skipweekends']);
        $config->set('PRIV_DEFAULT',      $post['privdefault']);
        if ($this->is_wiz) {
            $link = ovp_http::get_source_link('account');
        } else {
            $link = ovp_http::get_source_link('settings');
        }
        ovp_http::redirect($link);
    }
}

class post_account extends poster {
    public static $priv_req = ovp_user::VIEW_ADMIN;
    private $manager;
    private $is_wiz;

    public function __construct($is_wiz = false) {
        $this->manager = ovp_user_manager::get_singleton();
        $this->is_wiz = $is_wiz;
    }

    public function evaluate($post) {
        if (!(isset($post['name']) && isset($post['pwd']))) {
            ovp_http::fail('Daten unvollständig');
        }
        if ($this->manager->name_exists($post['name'])) {
            $user = $this->manager->get_user_by_name($post['name']);
            $user->set_password($post['pwd']);
        } else {
            $this->manager->add($post['name'], $post['pwd'], 'admin');
        }
        if ($this->is_wiz) {
            $link = ovp_http::get_source_link('final');
        } else {
            $link = ovp_http::get_source_link('account');
        }
        ovp_http::redirect($link);
    }
}

?>