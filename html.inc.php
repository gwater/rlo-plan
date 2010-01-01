<?php

/**
 * This file is part of RLO-Plan.
 *
 * Copyright 2009 Tillmann Karras, Josua Grawitter
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
require_once('config.inc.php');
require_once('user.inc.php');
require_once('entry.inc.php');
require_once('logger.inc.php');

/**
 * This is the basic API for all content provided by rlo-plan
 *
 * There are several different sources which provide the different views each
 * party is allowed to see. They can either be included directly into an
 * existing page or pushed through the ovp_page wrapper to create a complete
 * html page.
 */
abstract class ovp_source {
    protected $db;

    public function __construct(db $db) {
        $this->db = $db;
    }

    abstract protected function generate_view();

    protected function generate_header() {
        return '';
    }

    public function get_header() {
        $header = '
          <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
          <link rel="stylesheet" href="style.css" type="text/css" media="all">
          <link rel="stylesheet" href="print.css" type="text/css" media="print">
          '.$this->generate_header();
        return $header;
    }

    public function get_view() {
        $html = $this->generate_view();
        return $html;
    }
}

/**
 * This source provides the public view for students
 * Sensitive information like teachers names is not included.
 */
class ovp_public extends ovp_source {
    public static $type = 'public';
    public static $title ='Standardansicht';
    public static $priv_req = ovp_logger::VIEW_PUBLIC;

    protected function generate_view() {
        $entries_by_date = ovp_entry::get_entries_by_date($this->db);
        $html = '
          <div class="ovp_container">
            <h1>'.self::$title.'</h1>';
        if ($entries_by_date) {
            foreach($entries_by_date as $entries_today) {
                foreach ($entries_today as $first_entry) {
                    break;
                }
                $html .= '
                <h2>'.$first_entry->get_date().'</h2>
                <table class="ovp_table" id="ovp_table_'.self::$type.'">
                  <tr>
                    <th>Uhrzeit</th>
                    <th>Klasse</th>
                    <th>Fach</th>
                    <th>Originalraum</th>
                    <th>Dauer</th>
                    <th>Änderung</th>
                    <th>Neuer Raum</th>
                  </tr>';
                foreach ($entries_today as $entry) {
                    $values = $entry->get_values();
                    $html .= '
                  <tr>
                    <td>'.$entry->get_time(). '</td>
                    <td>'.$values['course'].  '</td>
                    <td>'.$values['subject']. '</td>
                    <td>'.$values['oldroom']. '</td>
                    <td>'.$values['duration'].'</td>
                    <td>'.$values['change'].  '</td>
                    <td>'.$values['newroom']. '</td>
                  </tr>';
                }
                $html .= '
                </table>';
            }
        } else {
            $html .= '<p>Es sind keine Einträge vorhanden.</p>';
        }
        $html .= '
          </div>';
        return $html;
    }
}

/**
 * This source provides the traditional view for printout.
 * It displays only one day at a time.
 * since it contains sensitive information its access must be restricted to
 * school personnel.
 */
class ovp_print extends ovp_source {
    public static $type = 'print';
    public static $title ='Aushang';
    public static $priv_req = ovp_logger::VIEW_PRINT;
    private $date;
    private $today;
    private $yesterday;
    private $tomorrow;

    public function __construct($db, $date = -1) {
        parent::__construct($db);
        if ($date == -1) {
            $time = time() + 3600; // adjust GMT to CET
            $date = strftime("%Y-%m-%d", ($time));
        } else {
            if (!preg_match('/(\d{4})-(\d\d)-(\d\d)/', $date, $matches)) {
                exit('lol wut?');
            }
            // don't know where the missing two hours went (one is GMT-CET)
            $time = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]) + 7200;
        }
        $this->today = strftime("%A, %d.%m.%y", $time);
        $this->yesterday = strftime("%Y-%m-%d", $time - 24*60*60);
        $this->tomorrow = strftime("%Y-%m-%d", $time + 24*60*60);
        $this->date = $date;
    }

    protected function generate_view() {
        $entries_by_teacher = ovp_entry::get_entries_by_teacher($this->db, $this->date);
        $html =
         '<div class="ovp_container">
            <h1>'.self::$title.'</h1>
            <div class="ovp_day_links">
              <a href="index.php?source='.self::$type.'&date='.$this->yesterday.'">Einen Tag zurück</a>
              <a href="index.php?source='.self::$type.'&date='.$this->tomorrow.'">Einen Tag weiter</a>
            </div>
            <h2>'.$this->today.'</h2>
            <table  class="ovp_table" id="ovp_table_'.self::$type.'">
              <tr>
                <th>Uhrzeit</th>
                <th>Klasse</th>
                <th>Fach</th>
                <th>Dauer</th>
                <th>Vertretung durch</th>
                <th>Raum</th>
              </tr>';
        foreach ($entries_by_teacher as $teacher => $entries) {
                $html .=
             '<tr>
                <td class="ovp_cell_teacher" colspan="6">'.$teacher.'</td>
              </tr>';

            foreach ($entries as $entry) {
                $values = $entry->get_values();
                /* An ugly hack to properly merge the changes column follows */
                $changes = '';
                if (($values['sub'] != '') && ($values['change'] != '')) {
                    $changes = $values['sub'].', '.$values['change'];
                } else if ($values['sub'] != '') {
                    $changes = $values['sub'];
                } else if ($values['change'] != '') {
                    $changes = $values['change'];
                }

                $html .=
                 '<tr>
                    <td>'.$entry->get_time().'</td>
                    <td>'.$values['course'].    '</td>
                    <td>'.$values['subject'].   '</td>
                    <td>'.$values['duration'].  '</td>
                    <td>'.$changes.          '</td>
                    <td>'.$values['newroom'].   '</td>
                  </tr>';
              }
        }
        $html .=
           '</table>
          </div>';
        return $html;
    }
}

/**
 * This source provides the view for Frau Lange. It allows adding, removing
 * and editing entries of the plan and thus access must be restricted to
 * authorized school personnel.
 */
class ovp_author extends ovp_source {
    public static $type = 'author';
    public static $title ='Einträge verwalten';
    public static $priv_req = ovp_logger::VIEW_AUTHOR;

    public function __construct($db) {
        parent::__construct($db);
    }

    protected function generate_header() {
        $entries_by_date = ovp_entry::get_entries_by_teacher_and_date($this->db);
        $script =
           '<script type="text/javascript" src="entry.js"></script>
            <script type="text/javascript" src="functions.js"></script>
            <script type="text/javascript">
            function fill_in_data() {';
        if ($entries_by_date) {
            $script .= '
                    var days = [];';
            foreach ($entries_by_date as $entries_by_teacher) {
                $script .= '
                    var teachers = [];';
                foreach ($entries_by_teacher as $teacher => $entries) {
                    $script .= '
                    var entries = [];';
                    foreach ($entries as $entry) {
                        $values = $entry->get_values();
                        $script .= '
                    entries.push(newEntry('.
                            $entry->get_id().   ', ["'.
                            $entry->get_time(). '", "'.
                            $values['course'].  '", "'.
                            $values['subject']. '", "'.
                            $values['duration'].'", "'.
                            $values['sub'].     '", "'.
                            $values['change'].  '", "'.
                            $values['oldroom']. '", "'.
                            $values['newroom']. '"]));';
                    }
                    $script .= '
                    teachers.push(newTeacher("'.$teacher.'", entries));';
                }
                $today = strftime("%A, %d.%m.%Y", $values['time']);
                $script .= '
                    days.push(newDay("'.$today.'", teachers));';
            }
            $script .= '
                    insert_days(days);';
        }
        $script .= '}</script>';
        return $script;
    }

    protected function generate_view() {
        $html =
         '<div class="ovp_container">
            <img src="1x1.gif" onload="init()" style="display: none">
            '.$this->get_tip().'
            <h1>'.self::$title.'</h1>
            <div id="ovp"></div>
          </div>';
        return $html;
    }

    private function get_tip() {
        $tips = file('tips.txt');
        if (!$tips) {
            return '';
        }
        $rand = rand(0, count($tips) - 1);
        return '<p id="ovp_tip">Tipp: '.$tips[$rand].'</p>';
    }
}

/**
 * This source provides a simple login interface to authorize access
 * to restricted views.
 * Naturally access is restricted to all users not logged in yet.
 */
class ovp_login extends ovp_source {
    public static $type = 'login';
    public static $title ='Login';
    public static $priv_req = ovp_logger::PRIV_LOGOUT;

    public function __construct($db) {
        parent::__construct($db);
    }

    protected function generate_view() {
        $html =
         '<div class="ovp_container">
          <h1>Login</h1>
          <p>Um diese Seite öffnen zu können, benötigen Sie ein entsprechend autorisiertes Benutzerkonto.</p>
          <form action="post.php?poster=login';
        if (isset($_GET['continue'])) {
            $html .= '&continue='.urlencode($_GET['continue']);
        }
        $html .= '" method="POST">
            <table id="ovp_table_'.self::$type.'">
              <tr>
                <td>Name:</td>
                <td><input type="text" name="name"></td>
              </tr>
              <tr>
                <td>Passwort:</td>
                <td><input type="password" name="pwd"></td>
              </tr>
              <tr>
                <td></td>
                <td><input type="submit" value="Login"></td>
              </tr>
            </table>
          </form>';
        if ($_GET['attempt'] == 'failed') {
            $html .= '<p><span class="ovp_error">Benutzername nicht gefunden oder falsches Passwort</span></p>';
        }
        $html .= '</div>';
        return $html;
    }
}

/**
 * This source provides a simple administration interface which most
 * importantly allows setting the passwords of any user.
 * Access must thus be seriously restricted.
 */
class ovp_admin extends ovp_source {
    public static $type = 'admin';
    public static $title ='Benutzer verwalten';
    public static $priv_req = ovp_logger::VIEW_ADMIN;
    protected $users;


    public function __construct($db) {
        parent::__construct($db);
        $this->users = ovp_user::get_all_users($db);
    }


    protected function generate_header() {
        $roles = ovp_user::get_roles();
        $script = '
            <script type="text/javascript" src="admin.js"></script>
            <script type="text/javascript" src="functions.js"></script>
            <script type="text/javascript">
            var roles = [';
        $first = true;
        foreach ($roles as $i => $role) {
            if ($first) {
                $first = false;
            } else {
                $script .= ', ';
            }
            $script .= '"'.$role.'"';
        }
        $script .= '];
            function fill_in_data() {
                var users = [];';
        foreach ($this->users as $user) {
            $role = $roles[$user->get_privilege()];
            $script .= '
                users.push(newUser("'.$user->get_id().'", "'.$user->get_name().'", "***", "'.$role.'"));';
        }
        $script .= '
                insertUsers(users);
            }</script>';
        return $script;
    }


    protected function generate_view() {
        $html =
         '<div class="ovp_container">
            <h1>'.self::$title.'</h1>
            <table id="ovp_table_users" class="ovp_table">
            <th>Name</th>
            <th>Passwort</th>
            <th>Rolle</th>
            <th>Aktion</th>
            </table>
            <img src="1x1.gif" onload="init_admin()">
          </div>';
        return $html;
    }
}

class ovp_password extends ovp_source {
    public static $type = 'password';
    public static $title ='Passwort ändern';
    public static $priv_req = ovp_logger::PRIV_LOGIN;
    private $user;


    public function __construct(db $db) {
        $this->user = ovp_logger::get_current_user($db);
    }

    protected function generate_view() {
        // TODO: new password two times?
        $html =
         '<div class="ovp_container">
          <h1>'.self::$title.'</h1>
          <form action="post.php?poster=password" method="POST">
            <table id="ovp_table_'.self::$type.'">
              <tr>
                <td>Name:</td>
                <td>'.$this->user->get_name().'</td>
              </tr>
              <tr>
                <td>Altes Passwort:</td>
                <td><input type="password" name="oldpwd"></td>
              </tr>
              <tr>
                <td>Neues Passwort:</td>
                <td><input type="password" name="newpwd"></td>
              </tr>
              <tr>
                <td></td>
                <td><input type="submit" value="Bestätigen"></td>
              </tr>
            </table>
          </form>
          </div>';
        return $html;
    }
}

class ovp_about extends ovp_source {
    public static $type = 'about';
    public static $title ='Über RLO-Plan';
    public static $priv_req = ovp_logger::VIEW_NONE;

    public function generate_view() {
        return file_get_contents('about.inc.html');
    }
}

class ovp_mysql extends ovp_source {
    public static $type = 'mysql';
    public static $title = 'MySQL Konfiguration';
    public static $priv_req = ovp_logger::VIEW_ADMIN;

    public function __construct() {}

    public function generate_view() {
        $html = '<div class="ovp_container">'
        if (isset($_GET['error'])) {
            $html .= '<p><span class="ovp_error">ERROR: '.$_GET['error'].'</span></p>';
        }
        $html .= '
            <form action="'.$_SERVER['SCRIPT_NAME'].'?page=mysql&action=save" method="POST"><table>
                <tr><td>Server</td><td><input type="text" name="host" value="'.get('DB_HOST').'"></td></tr>
                <tr><td>Datenbank</td><td><input type="text" name="base" value="'.get('DB_BASE').'"></td></tr>
                <tr><td>Benutzer</td><td><input type="text" name="user" value="'.get('DB_USER').'"></td></tr>
                <tr><td>Passwort</td><td><input type="text" name="pass" value="'.get('DB_PASS').'"></td></tr>
                <tr><td></td><td><input type="submit" value="Speichern und weiter"></td></tr>
            </table></form>
            </div>';
        return $html;
    }
}

class ovp_account extends ovp_source {
    public static $type = 'account';
    public static $title = 'Admnistrator anlegen';
    public static $priv_req = ovp_logger::VIEW_ADMIN;

    public function __construct() {}

    public function generate_view() {
        $html = '
            <div class="ovp_container">
            <form action="'.$_SERVER['SCRIPT_NAME'].'?page=admin&action=save" method="POST"><table>
                <tr><td>Benutzer</td><td><input type="text" name="name" value="admin">
                <tr><td>Passwort</td><td><input type="password" name="pwd" value="">
                <tr><td></td><td><input type="submit" value="Speichern und weiter">
            </table></form>
            </div>';
        return $html;
    }
}

class ovp_settings extends ovp_source {
    public static $type = 'settings';
    public static $title = 'Konfiguration';
    public static $priv_req = ovp_logger::VIEW_ADMIN;

    public function generate_view() {
        $html = '<div class="ovp_container">';
        if (isset($_GET['error'])) {
            $html .= '<p><span class="ovp_error">ERROR: '.$_GET['error'].'</span></p>';
        }
        // FIXME
        $debug         = get('DEBUG');
        $skip_weekends = get('SKIP_WEEKENDS');
        $priv_default  = get('PRIV_DEFAULT');
        $html .= '
            <form action="'.$_SERVER['SCRIPT_NAME'].'?page=settings&action=save" method="POST"><table>
                <tr>
                  <td>Sollen detaillierte Fehlermeldungen angezeigt werden?</td>
                  <td><select name="debug">
                    <option value="true"'.($debug == 'true' ? ' selected="selected"' : '').'>Ja</option>
                    <option value="false"'.($debug == 'false' ? ' selected="selected"' : '').'>Nein</option>
                  </select></td>
                </tr>
                <tr>
                  <td>Nach wie vielen Tagen sollen alte Einträge automatisch gelöscht werden? (-1 = nie)</td>
                  <td><input type="text" name="delold" value="'.get('DELETE_OLDER_THAN').'"></td>
                </tr>
                <tr>
                  <td>Sollen Wochenenden dabei <i>nicht</i> mitzählen?</td>
                  <td><select name="skipweekends">
                    <option value="true"'.($skip_weekends == 'true' ? ' selected="selected"' : '').'>Ja</option>
                    <option value="false"'.($skip_weekends == 'false' ? ' selected="selected"' : '').'>Nein</option>
                  </select></td>
                </tr>
                <tr>
                  <td>Welches Privileg sollen unangemeldete Besucher besitzen?</td>
                  <td><select name="privdefault">
                    <option value="0"'.($priv_default == '0' ? ' selected="selected"' : '').'>Keins</option>
                    <option value="1"'.($priv_default == '1' ? ' selected="selected"' : '').'>Public</option>
                    <option value="2"'.($priv_default == '2' ? ' selected="selected"' : '').'>Print</option>
                    <option value="3"'.($priv_default == '3' ? ' selected="selected"' : '').'>Autor</option>
                    <option value="4"'.($priv_default == '4' ? ' selected="selected"' : '').'>Admin</option>
                  </select></td>
                </tr>
                <tr><td></td><td><input type="submit" value="Speichern und weiter"></td></tr>
            </table></form>
            </div>';
        return $html;
    }
}

class ovp_final extends ovp_source {
    public static $type = 'final';
    public static $title = 'Konfigurationsabschluss';
    public static $priv_req = ovp_logger::VIEW_ADMIN;

    public function __construct() {}

    public function generate_view() {
        $html = '<div class="ovp_container"><p>Sie können jetzt die <a href="index.php">Startseite</a> öffnen.</p></div>';
    }
}

class ovp_navi_wizard extends ovp_source {
    public static $type = 'navi_wizard';
    public static $title = 'Installationsnavigator';
    public static $priv_req = ovp_logger::VIEW_ADMIN;

    public function __construct() {}

    public function generate_view() {
        $html = '<div id="ovp_navi"><ol>';
        // FIXME
        $pages = array('mysql' => 'MySQL Credentials', 'settings' => 'Misc. Settings', 'admin' => 'Admin Account', 'done' => 'Save and Clean Up');
        foreach ($pages as $page => $title) {
            if ($_GET['page'] == $page) {
                $html .= '<li><b>'.$title.'</b></li><br>';
            } else {
                $html .= '<li><a href="'.basename($_SERVER['SCRIPT_NAME']).'?page='.$page.'">'.$title.'</a></li><br>';
            }
        }
        return $html.'</ol></div>';
    }
}


class ovp_navi extends ovp_source {
    public static $type = 'navi';
    public static $title ='Navigationsleiste';
    public static $priv_req = ovp_logger::VIEW_NONE;
    private $current;
    private $logger;

    public function __construct($logger, $current) {
        $this->current = $current;
        $this->logger = $logger;
    }

    public function generate_view() {
        $sources = array();
        $sources[] = get_class_vars('ovp_public');
        $sources[] = get_class_vars('ovp_print');
        $sources[] = get_class_vars('ovp_author');
        $sources[] = get_class_vars('ovp_admin');
        $sources[] = get_class_vars('ovp_about');
        $sources[] = get_class_vars('ovp_password');
        $sources[] = get_class_vars('ovp_login');

        $html =
             '<div id="ovp_navi">';
        $first = true;
        foreach ($sources as $source) {
            if ($this->logger->is_authorized($source['priv_req'])) {
                if($first) {
                    $first = false;
                } else {
                    $html .= ' |';
                }
                if ($source['type'] != $this->current) {
                    $html .= '
                <a href="index.php?source='.$source['type'].'">'.$source['title'].'</a>';
                } else {
                    $html .= '
                <span>'.$source['title'].'</span>';
                }
            }
        }
        if ($this->logger->is_authorized(ovp_logger::PRIV_LOGIN)){
            $html .= ' |
                <a href="post.php?poster=logout">Logout</a>';
        }
        $html .= '
              </div>';
        return $html;
    }
}

/**
 * This class acts as a wrapper around any ovp_source object and provides
 * complete html pages based on the content provided by the source.
 *
 * Its use is optional and merely provided for convenience and simple setups.
 */
class ovp_page {
    private $source; // the ovp_source object used to generate the page
    private $title;
    private $type;
    private $db;
    private $navi;

    public function __construct(ovp_source $source, ovp_source $navi) {
        $this->source = $source;
        $source_vars = get_class_vars(get_class($source));
        $this->title = $source_vars['title'];
        $this->navi = $navi;
    }

    public function get_html() {
        $html =
'<!DOCTYPE html>
<html>
  <head>
    <title>RLO Onlinevertretungsplan - '.$this->title.'</title>
    <meta name="ROBOTS" content="NOINDEX, NOFOLLOW">
    '.$this->source->get_header().'
  </head>
  <body>
    '.$this->navi->get_view().'
    '.$this->source->get_view().'
  </body>
</html>';
        return $html;
    }
}

?>
