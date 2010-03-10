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
                    echo($id);
                    exit;
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
                    $result = $user->set('name', $value);
                    break;
                case 'password':
                    $result = $user->set('pwd', $value);
                    break;
                case 'role':
                    $result = $user->set('role', $value);
                    break;
                default:
                    // DoNothing (tm)
                }
                if (!$result) {
                    ovp_http::fail('Änderung gescheitert');
                }
            }
            exit('geändert');
        case 'delete':
            if (!isset($post['id'])) {
                ovp_http::fail('ID fehlt');
            } else if ($this->manager->remove($post['id'])) {
                exit('gelöscht');
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
        $this->user = ovp_user_manager::get_current_user();
    }

    public function evaluate($post) {
        if (isset($post['newpwd']) && isset($post['oldpwd'])) {
            if ($this->user->check_password($post['oldpwd'])) {
                if ($this->user->set('pwd', $post['newpwd'])) {
                    exit('geändert');
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
                echo($id);
                exit;
            }
            ovp_http::fail('Hinzufügen gescheitert');
        case 'update':
            if (!isset($post['id'])) {
                ovp_http::fail('ID fehlt');
            }
            $entry = new ovp_entry($post['id']);
            if ($entry->set_values($post)) {
                exit('geändert');
            }
            ovp_http::fail('Änderung gescheitert');
        case 'delete':
            if (!isset($post['id'])) {
                ovp_http::fail('ID fehlt');
            }
            if ($this->manager->remove($post['id'])) {
                exit('gelöscht');
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

class post_import extends poster {
    public static $priv_req = ovp_user::VIEW_ADMIN;
    private $msg;

    public function evaluate($post) {
        $upload_error = 'Fehler beim Hochladen der Datei';

        if (!isset($_FILES['data'])) {
            ovp_http::fail($upload_error);
        }
        switch ($_FILES['data']['error']) {
        case UPLOAD_ERR_OK:
            if (move_uploaded_file($_FILES['data']['tmp_name'], 'import.tmp')) {
                $overwrite = $_POST['overwrite'];
                if ($_POST['reset']) {
                    $db = ovp_db::get_singleton();
                    $db->reset_tables();
                    $overwrite = false;
                }
                $result = ($this->import('import.tmp', $overwrite) ? 'success' : 'error');
                $link = ovp_http::get_source_link('backup&import='.$result.'&msg='.urlencode($this->msg));
                unlink('import.tmp');
            } else {
                ovp_http::fail($upload_error.' (move_uploaded_file failed)');
            }
            break;
        case UPLOAD_ERR_NO_FILE:
            $link = ovp_http::get_source_link('backup&import=error&msg='.urlencode('Bitte wählen Sie eine Datei aus.'));
            break;
        default:
            ovp_http::fail($upload_error.(ovp_config::get_singleton()->get('DEBUG') ? ' (Fehlercode '.$_FILES['data']['error'].')' : ''));
        }
        ovp_http::redirect($link);
    }

    private function import($file, $overwrite) {
        $DOMDocument = new DOMDocument();
        $DOMDocument->load($file);

        $user_manager = ovp_user_manager::get_singleton();
        $DOMUserList = $DOMDocument->getElementsByTagName('user');
        for ($index = 0; $index < $DOMUserList->length; $index++) {
            $DOMUser = $DOMUserList->item($index);
            $values = array(
                'id'        => '',
                'name'      => '',
                'pwd_hash'  => '',
                'privilege' => ''
            );
            foreach ($values as $key => $value) {
                if (!$DOMUser->hasAttribute($key)) {
                    $this->msg = $key.'-Attribut fehlt';
                    return false;
                }
                $values[$key] = $DOMUser->getAttribute($key);
            }
            if (!$user_manager->import($values, $overwrite)) {
                $this->msg = 'Der Benutzer "'.$name.'" konnte nicht importiert werden.';
                return false;
            }
        }

        $entry_manager = ovp_entry_manager::get_singleton();
        $DOMEntryList = $DOMDocument->getElementsByTagName('entry');
        for ($index = 0; $index < $DOMEntryList->length; $index++) {
            $DOMEntry = $DOMEntryList->item($index);
            $values = array(
                'id'       => '',
                'date'     => '',
                'teacher'  => '',
                'time'     => '',
                'course'   => '',
                'subject'  => '',
                'duration' => '',
                'sub'      => '',
                'change'   => '',
                'oldroom'  => '',
                'newroom'  => ''
            );
            foreach ($values as $key => $value) {
                if (!$DOMEntry->hasAttribute($key)) {
                    $this->msg = $key.'-Attribut fehlt';
                    return false;
                }
                $values[$key] = $DOMEntry->getAttribute($key);
            }
            if (!$entry_manager->import($values, $overwrite)) {
                $this->msg = 'Der Eintrag "'.$id.'" konnte nicht hinzugefügt werden.';
                return false;
            }
        }

        $created = $DOMDocument->firstChild->getAttribute('created');
        $this->msg = 'Das Backup von '.$created.' wurde erfolgreich importiert.';
        return true;
    }
}

?>
