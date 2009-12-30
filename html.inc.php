<?php

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
    private $time;
    private $today;
    private $yesterday;
    private $tomorrow;

    public function __construct($db, $date = -1) {
        parent::__construct($db);
        if ($date == -1) {
            $time = time() + 3600; // adjust GMT to CET
            $time = $time - $time % 86400;
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
        $this->time = $time;
    }

    protected function generate_view() {
        $entries_by_teacher = ovp_entry::get_entries_by_teacher($this->db, $this->time);
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


    public function __construct($db) {
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

class ovp_navi extends ovp_source {
    public static $type = 'navi';
    public static $title ='Navigationsleiste';
    public static $priv_req = ovp_logger::VIEW_NONE;

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
            if (ovp_logger::is_authorized($this->db, $source['priv_req'])) {
                if($first) {
                    $first = false;
                } else {
                    $html .= ' |';
                }
                if ($source['type'] != $this->type) {
                    $html .= '
                <a href="index.php?source='.$source['type'].'">'.$source['title'].'</a>';
                } else {
                    $html .= '
                <span>'.$source['title'].'</span>';
                }
            }
        }
        if (ovp_logger::is_authorized($this->db, ovp_logger::PRIV_LOGIN)){
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

    public function __construct(db $db, ovp_source $source) {
        $this->db = $db;
        $this->source = $source;
        $this->navi = new ovp_navi($db);
        $source_vars = get_class_vars(get_class($source));
        $this->title = $source_vars['title'];
        $this->type = $source_vars['type'];
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
