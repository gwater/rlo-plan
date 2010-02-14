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

require_once('config.inc.php');
require_once('user.inc.php');
require_once('entry.inc.php');

/**
 * This is the basic API for all content provided by rlo-plan
 *
 * There are several different sources which provide the different views each
 * party is allowed to see. They can either be included directly into an
 * existing page or pushed through the ovp_page wrapper to create a complete
 * html page.
 */
abstract class ovp_source {
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

class ovp_sub extends ovp_source {
    public static $type = 'sub';
    public static $title ='Online-Vertreterplan';
    public static $priv_req = ovp_user::VIEW_PRINT;
    private $sub;
    private $subs;
    private $entries;

    public function __construct($sub) {
        if ($sub == '') { // No filter selected -> display all
            $link = ovp_http::get_source_link('print');
            ovp_http::redirect($link);
        }
        $manager = ovp_entry_manager::get_singleton();
        $this->entries = $manager->get_entries_for_sub($sub);
        $this->subs = $manager->get_subs();
        $this->sub = $sub;
    }

    protected function generate_view() {
        $link = ovp_http::get_source_link('sub');
        $html = '
          <div class="ovp_container">
            <h1>'.self::$title.'</h1>
            <form action="'.$link.'" method="GET">
            <input type="hidden" name="source" value="sub">
            <table><tr>
            <td>Vertretungslehrer:</td>
            <td><select name="sub">
                <option value="" '.($this->sub == '' ? 'selected="selected"' : '').'>Alle</option>';
        foreach ($this->subs as $sub) {
            $html .= '
                <option value="'.$sub.'" '.($sub == $this->sub ? 'selected="selected"' : '').'>'.$sub.'</option>';
        }
        $html .= '
            </select></td>
            <td><input type="submit" value="Filtern"></td>
            </tr></table>
            </form>';
        if ($this->entries) {
            foreach($this->entries as $entries_today) {
                foreach ($entries_today as $first_entry) {
                    break;
                }
                $html .= '
                <h2>'.$first_entry->get_date().'</h2>
                <table class="ovp_table" id="ovp_table_'.self::$type.'">
                  <tr>
                    <th>Uhrzeit</th>
                    <th>Dauer</th>
                    <th>Raum</th>
                    <th>Klasse</th>
                    <th>Fach</th>
                    <th>Änderung</th>
                  </tr>';
                foreach ($entries_today as $entry) {
                    $values = $entry->get_values();
                    $html .= '
                  <tr>
                    <td>'.$entry->get_time(). '</td>
                    <td>'.$values['duration'].'</td>
                    <td>'.$values['newroom']. '</td>
                    <td>'.$values['course'].  '</td>
                    <td>'.$values['subject']. '</td>
                    <td>'.$values['change'].  '</td>
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
 * This source provides the public view for students
 * Sensitive information like teachers names is not included.
 */
class ovp_public extends ovp_source {
    public static $type = 'public';
    public static $title ='Online-Vertretungsplan';
    public static $priv_req = ovp_user::VIEW_PUBLIC;
    private $course;
    private $courses;
    private $entries;

    public function __construct($course = '') {
        $manager = ovp_entry_manager::get_singleton();
        if ($course == '') {
            $this->entries = $manager->get_entries_by_date();
        } else {
            $this->entries = $manager->get_entries_for_course($course);
        }
        $this->courses = $manager->get_courses();
        $this->course = $course;
    }

    protected function generate_view() {
        $link = ovp_http::get_source_link('public');
        $html = '
          <div class="ovp_container">
            <h1>'.self::$title.'</h1>
            <form action="'.$link.'" method="GET">
            <input type="hidden" name="source" value="public">
            <table><tr>
            <td>Klasse/Kurs:</td>
            <td><select name="course">
                <option value="" '.($this->course == '' ? 'selected="selected"' : '').'>Alle</option>';
        foreach ($this->courses as $course) {
            $html .= '
                <option value="'.$course.'" '.($course == $this->course ? 'selected="selected"' : '').'>'.$course.'</option>';
        }
        $html .= '
            </select></td>
            <td><input type="submit" value="Filtern"></td>
            </tr></table>
            </form>';
        if ($this->entries) {
            foreach($this->entries as $entries_today) {
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
    public static $priv_req = ovp_user::VIEW_PRINT;
    private $today;
    private $yesterday;
    private $tomorrow;
    private $entries;
    private $subs;

    public function __construct($date = false) {
        $manager = ovp_entry_manager::get_singleton();
        if (!$date) {
            $date = $manager->get_today($date);
        }
        $today = $manager->adjust_date($date);
        $this->tomorrow = $manager->adjust_date($today, 1);
        $this->yesterday = $manager->adjust_date($today, -1);
        $this->entries = $manager->get_entries_by_teacher($today);
        $this->today = $manager->format_date($today);
        $this->subs = $manager->get_subs();
    }

    protected function generate_view() {
        $yesterday_link = ovp_http::get_source_link(self::$type.'&date='.$this->yesterday);
        $tomorrow_link = ovp_http::get_source_link(self::$type.'&date='.$this->tomorrow);
        $sub_link = ovp_http::get_source_link('sub');
        $html =
         '<div class="ovp_container">
            <h1>'.self::$title.'</h1>
            <form action="'.$sub_link.'" method="GET">
            <input type="hidden" name="source" value="sub">
            <table><tr>
            <td>Vertretungslehrer:</td>
            <td><select name="sub">
                <option value="" selected="selected">Alle</option>';
        foreach ($this->subs as $sub) {
            $html .= '
                <option value="'.$sub.'">'.$sub.'</option>';
        }
        $html .= '
            </select></td>
            <td><input type="submit" value="Filtern"></td>
            </tr></table>
            </form>
            <div class="ovp_day_links">
              <a href="'.$yesterday_link.'">Einen Tag zurück</a>
              <a href="'.$tomorrow_link.'">Einen Tag weiter</a>
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
        foreach ($this->entries as $teacher => $entries) {
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
    public static $priv_req = ovp_user::VIEW_AUTHOR;
    private $entries;

    public function __construct() {
        $manager = ovp_entry_manager::get_singleton();
        $this->entries = $manager->get_entries_by_teacher_and_date();
    }

    protected function generate_header() {
        $link = ovp_http::get_poster_link('entry');
        $script =
           '<script type="text/javascript" src="entry.js"></script>
            <script type="text/javascript" src="functions.js"></script>
            <script type="text/javascript">
            var url = "'.$link.'";
            function fill_in_data() {';
        if ($this->entries) {
            $script .= '
                    var days = [];';
            foreach ($this->entries as $entries_by_teacher) {
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
                $script .= '
                    days.push(newDay("'.$entry->get_date().'", teachers));';
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
    public static $priv_req = ovp_user::PRIV_LOGOUT;

    protected function generate_view() {
        if (isset($_GET['continue'])) {
            $link = ovp_http::get_poster_link('login&continue='.urlencode($_GET['continue']));
        } else {
            $link = ovp_http::get_poster_link('login');
        }
        $html =
         '<div class="ovp_container">
          <h1>Login</h1>
          <p>Um diese Seite öffnen zu können, benötigen Sie ein entsprechend autorisiertes Benutzerkonto.</p>
          <form action="'.$link.'" method="POST">
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
    public static $priv_req = ovp_user::VIEW_ADMIN;
    protected $users;

    public function __construct() {
        $manager = ovp_user_manager::get_singleton();
        $this->users = $manager->get_all_users();
    }

    protected function generate_header() {
        $roles = ovp_user::get_roles();
        $link = ovp_http::get_poster_link('user');
        $script = '
            <script type="text/javascript" src="admin.js"></script>
            <script type="text/javascript" src="functions.js"></script>
            <script type="text/javascript">
            var url = "'.$link.'";
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
              <tr>
                <th>Name</th>
                <th>Passwort</th>
                <th>Rolle</th>
                <th>Aktion</th>
              </tr>
            </table>
          </div>';
        return $html;
    }
}

class ovp_password extends ovp_source {
    public static $type = 'password';
    public static $title ='Passwort ändern';
    public static $priv_req = ovp_user::PRIV_LOGIN;
    private $user;


    public function __construct() {
        $manager = ovp_user_manager::get_singleton();
        $this->user = $manager->get_current_user();
    }

    protected function generate_header() {
        return '
            <script type="text/javascript" src="passwd.js"></script>
            <script type="text/javascript" src="functions.js"></script>';
    }

    protected function generate_view() {
        $link = ovp_http::get_poster_link(self::$type);
        $html =
         '<div class="ovp_container">
          <h1>'.self::$title.'</h1>
          <form action="'.$link.'" method="POST"><input type="hidden" name="xhr" value="false">
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
                <td>Wiederholung:</td>
                <td><input type="password" name="newpwd2"></td>
              </tr>
              <tr>
                <td id="ovp_status"></td>
                <td><input type="submit" name="submit" value="Ändern"></td>
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
    public static $priv_req = ovp_user::VIEW_NONE;

    public function generate_view() {
        ob_start();
        require('about.inc.php');
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }
}

class ovp_mysql extends ovp_source {
    public static $type = 'mysql';
    public static $title = 'MySQL Konfiguration';
    public static $priv_req = ovp_user::VIEW_ADMIN;

    public function generate_view() {
        $html = '<div class="ovp_container">
            <h1>'.self::$title.'</h1>';
        if (isset($_GET['error'])) {
            $html .= '<p><span class="ovp_error">ERROR: '.$_GET['error'].'</span></p>';
        }
        $config = ovp_config::get_singleton();
        $link = ovp_http::get_poster_link(self::$type);
        $html .= '
            <form action="'.$link.'" method="POST"><table>
                <tr><td>Server</td><td><input type="text" name="host" value="'.$config->get('DB_HOST').'"></td></tr>
                <tr><td>Datenbank</td><td><input type="text" name="base" value="'.$config->get('DB_BASE').'"></td></tr>
                <tr><td>Benutzer</td><td><input type="text" name="user" value="'.$config->get('DB_USER').'"></td></tr>
                <tr><td>Passwort</td><td><input type="text" name="pass" value="'.$config->get('DB_PASS').'"></td></tr>
                <tr><td></td><td><input type="submit" value="Speichern und weiter"></td></tr>
            </table></form>
            </div>';
        return $html;
    }
}

class ovp_account extends ovp_source {
    public static $type = 'account';
    public static $title = 'Administrationskonto anlegen';
    public static $priv_req = ovp_user::VIEW_ADMIN;

    public function generate_view() {
        $link = ovp_http::get_poster_link(self::$type);
        $html = '<div class="ovp_container">
            <h1>'.self::$title.'</h1>
            <form action="'.$link.'" method="POST"><table>
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
    public static $priv_req = ovp_user::VIEW_ADMIN;

    public function generate_view() {
        $html = '<div class="ovp_container">
            <h1>'.self::$title.'</h1>';
        if (isset($_GET['error'])) {
            $html .= '<p><span class="ovp_error">ERROR: '.$_GET['error'].'</span></p>';
        }
        $link = ovp_http::get_poster_link(self::$type);
        $config = ovp_config::get_singleton();
        $debug         = $config->get('DEBUG');
        $skip_weekends = $config->get('SKIP_WEEKENDS');
        $priv_default  = $config->get('PRIV_DEFAULT');
        $older = $config->get('DELETE_OLDER_THAN');
        $html .= '
            <form action="'.$link.'" method="POST"><table>
                <tr>
                  <td>Sollen detaillierte Fehlermeldungen angezeigt werden?</td>
                  <td><select name="debug">
                    <option value="'.true.'"'.($debug == true ? ' selected="selected"' : '').'>Ja</option>
                    <option value="'.false.'"'.($debug == false ? ' selected="selected"' : '').'>Nein</option>
                  </select></td>
                </tr>
                <tr>
                  <td>Nach wie vielen Tagen sollen alte Einträge automatisch gelöscht werden? (-1 = nie)</td>
                  <td><input type="text" name="delold" value="'.$older.'"></td>
                </tr>
                <tr>
                  <td>Sollen Wochenenden dabei <i>nicht</i> mitzählen?</td>
                  <td><select name="skipweekends">
                    <option value="'.true.'"'.($skip_weekends == true ? ' selected="selected"' : '').'>Ja</option>
                    <option value="'.false.'"'.($skip_weekends == false ? ' selected="selected"' : '').'>Nein</option>
                  </select></td>
                </tr>
                <tr>
                  <td>Welches Privileg sollen unangemeldete Besucher besitzen?</td>
                  <td><select name="privdefault">
                    <option value="'.ovp_user::VIEW_NONE  .'"'.($priv_default == ovp_user::VIEW_NONE   ? ' selected="selected"' : '').'>Keins</option>
                    <option value="'.ovp_user::VIEW_PUBLIC.'"'.($priv_default == ovp_user::VIEW_PUBLIC ? ' selected="selected"' : '').'>Public</option>
                    <option value="'.ovp_user::VIEW_PRINT .'"'.($priv_default == ovp_user::VIEW_PRINT  ? ' selected="selected"' : '').'>Print</option>
                    <option value="'.ovp_user::VIEW_AUTHOR.'"'.($priv_default == ovp_user::VIEW_AUTHOR ? ' selected="selected"' : '').'>Autor</option>
                    <option value="'.ovp_user::VIEW_ADMIN .'"'.($priv_default == ovp_user::VIEW_ADMIN  ? ' selected="selected"' : '').'>Admin</option>
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
    public static $title = 'Konfiguration abgeschlossen';
    public static $priv_req = ovp_user::VIEW_NONE;

    public function generate_view() {
        $html = '<div class="ovp_container">
              <h1>'.self::$title.'</h1>
              <p>Sie können jetzt die <a href="index.php">Startseite</a> öffnen.</p>
            </div>';
        return $html;
    }
}

class ovp_backup extends ovp_source {
    public static $type = 'backup';
    public static $title = 'Backup';
    public static $priv_req = ovp_user::VIEW_ADMIN;

    public function generate_view() {
        $link = ovp_http::get_poster_link(self::$type);
        $html =
           '<div class="ovp_container">
                <h1>'.self::$title.'</h1>
                <h2>Export</h2>
                <p><a href="export.php">Download</a></p>
                <h2>Import</h2>
                <form action="'.$link.'" method="POST" enctype="multipart/form-data"><input type="hidden" name="MAX_FILE_SIZE" value="10240"><table>
                    <tr><td>Datei:</td><td><input type="file" name="data"></td></tr>
                    <tr><td></td><td><input type="submit" value="Importieren"></td></tr>
                </table></form>';
        if (isset($_GET['import']) && isset($_GET['msg'])) {
            $html .=
               '<p><span class="ovp_'.htmlspecialchars($_GET['import']).'">'.htmlspecialchars($_GET['msg']).'</span></p>';
        }
        return $html.'</div>';
    }
}

class ovp_navi_wizard extends ovp_source {
    public static $type = 'navi_wizard';
    public static $title = 'Installationsnavigator';
    public static $priv_req = ovp_user::VIEW_ADMIN;
    private $current;

    public function __construct($current) {
        $this->current = $current;
    }

    public function generate_view() {
        $sources = array();
        $sources[] = get_class_vars('ovp_mysql');
        $sources[] = get_class_vars('ovp_settings');
        $sources[] = get_class_vars('ovp_account');
        $sources[] = get_class_vars('ovp_final');

        $html = '<div id="ovp_navi"><ol>';
        foreach ($sources as $source) {
            if ($this->current == $source['type']) {
                $html .= '<li>'.$source['title'].'</li><br>';
            } else {
                $link = ovp_http::get_source_link($source['type']);
                $html .= '<li><a href="'.$link.'">'.$source['title'].'</a></li><br>';
            }
        }
        return $html.'</ol></div>';
    }
}


class ovp_navi extends ovp_source {
    public static $type = 'navi';
    public static $title ='Navigationsleiste';
    public static $priv_req = ovp_user::VIEW_NONE;
    private $current;
    private $user;

    public function __construct($current) {
        $this->current = $current;
        $manager = ovp_user_manager::get_singleton();
        $this->user = $manager->get_current_user();
    }

    public function generate_view() {
        $sources = array();
        $sources[] = get_class_vars('ovp_public');
        $sources[] = get_class_vars('ovp_print');
        $sources[] = get_class_vars('ovp_author');
        $sources[] = get_class_vars('ovp_admin');
        $sources[] = get_class_vars('ovp_mysql');
        $sources[] = get_class_vars('ovp_settings');
        $sources[] = get_class_vars('ovp_backup');
        $sources[] = get_class_vars('ovp_about');
        $sources[] = get_class_vars('ovp_password');
        $sources[] = get_class_vars('ovp_login');

        $html =
             '<div id="ovp_navi">';
        $first = true;
        foreach ($sources as $source) {
            if ($this->user->is_authorized($source['priv_req'])) {
                if($first) {
                    $first = false;
                } else {
                    $html .= ' | ';
                }
                if ($source['type'] != $this->current) {
                    $link = ovp_http::get_source_link($source['type']);
                    $html .= '
                <a href="'.$link.'">'.$source['title'].'</a>';
                } else {
                    $html .= $source['title'];
                }
            }
        }
        if ($this->user->is_authorized(ovp_user::PRIV_LOGIN)){
            $link = ovp_http::get_poster_link('logout');
            $html .= ' |
                <a href="'.$link.'">Logout</a>';
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
    <title>'.$this->title.' - RLO-Plan</title>
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
