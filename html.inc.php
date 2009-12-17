<?php

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

    public function __construct($db) {
        $this->db = $db;
    }

    abstract protected function generate_view();

    protected function generate_header() {
        return '';
    }

    public function get_header() {
        $header = '
          <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
          <link rel="stylesheet" href="style.css" type="text/css">';
        $header .= $this->generate_header();
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
    public static $priv_req = VIEW_PUBLIC;
    private $entries;

    public function __construct($db, $time = -1) {
        parent::__construct($db);
        if ($time == -1) {
            $time = time();
        }
        $this->entries = $db->get_entries();
    }

    private function refactor_entries($entries) {
        if (count($entries) == 0) {
            return $entries;
        }
        $old_date = $entries[0]->get_date();
        foreach ($entries as $entry) {
            if ($old_date != $entry->get_date()) {
                $old_date = $entry->get_date();
                $days[] = $day;
                unset($day);
            }
            $day[] = $entry;
        }
        $days[] = $day;
        return $days;
    }

    protected function generate_view() {
        $entries_by_date = $this->refactor_entries($this->entries);

        $html = '
          <div class="ovp_container">
            <h1>'.self::$title.'</h1>';
        foreach($entries_by_date as $entries_today) {
            $html .= '
            <h2>'.$entries_today[0]->get_date().'</h2>
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
                $html .= '
              <tr>
                <td>'.$entry->get_time().'</td>
                <td>'.$entry->course.    '</td>
                <td>'.$entry->subject.   '</td>
                <td>'.$entry->oldroom.   '</td>
                <td>'.$entry->duration.  '</td>
                <td>'.$entry->change.    '</td>
                <td>'.$entry->newroom.   '</td>
              </tr>';
            }
            $html .= '
            </table>';
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
    public static $priv_req = VIEW_PRINT;
    private $entries;
    private $today;
    private $yesterday;
    private $tomorrow;

    public function __construct($db, $date = -1) {
        parent::__construct($db);
        if ($date == -1) {
            $time = time();
            $time = $time - $time % 86400;
        } else {
            if (!preg_match('/(\d{4})-(\d\d)-(\d\d)/', $date, $matches)) {
                exit('lol wut?');
            }
            $time = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
        }
        $this->entries = $db->get_entries($time);
        $this->today = strftime("%A, %d.%m.%y", $time);
        $this->yesterday = strftime("%Y-%m-%d", $time - 24*60*60);
        $this->tomorrow = strftime("%Y-%m-%d", $time + 24*60*60);
    }

    protected function generate_header() {
        return '<link rel="stylesheet" type="text/css" href="print.css" media="print">';
    }

    protected function generate_view() {
        $html =
         '<div class="ovp_container">
            <h1>'.self::$title.'</h1>
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

        $oldteacher = '';
        foreach ($this->entries as $entry) {
            if ($entry->teacher != $oldteacher) {
                $html .=
             '<tr>
                <td class="ovp_cell_teacher" colspan="6">'.$entry->teacher.'</td>
              </tr>';
                $oldteacher = $entry->teacher;
            }

            /* An ugly hack to properly merge the changes column follows */
            $changes = '';
            if (($entry->sub != '') && ($entry->change != '')) {
                $changes = $entry->sub.', '.$entry->change;
            } else if ($entry->sub != '') {
                $changes = $entry->sub;
            } else if ($entry->change != '') {
                $changes = $entry->change;
            }

            $html .=
             '<tr>
                <td>'.$entry->get_time().'</td>
                <td>'.$entry->course.    '</td>
                <td>'.$entry->subject.   '</td>
                <td>'.$entry->duration.  '</td>
                <td>'.$changes.          '</td>
                <td>'.$entry->newroom.   '</td>
              </tr>';
        }
        $html .=
           '</table>
            <div class="ovp_day_links">
              <a href="index.php?source='.self::$type.'&date='.$this->yesterday.'">Einen Tag zurück</a>
              <a href="index.php?source='.self::$type.'&date='.$this->tomorrow.'">Einen Tag weiter</a>
            </div>
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
    public static $priv_req = VIEW_AUTHOR;
    private $entries;

    public function __construct($db) {
        parent::__construct($db);
        $this->entries = $db->get_entries();
    }

    /** a horribly complex algorithm to get from
     * $entry = $entries[day, teacher, time]
     * to
     * $entry = $entries[day][teacher][time]
     */
    private function refactor_entries($entries) {
        if (count($entries) == 0) {
            return $entries;
        }
        $old_date = $entries[0]->get_date();
        foreach ($entries as $entry) {
            if ($old_date != $entry->get_date()) {
                $old_date = $entry->get_date();
                $days[] = $day;
                unset($day);
            }
            $day[] = $entry;
        }
        $days[] = $day;
        foreach ($days as $i => $day) {
            $old_teacher = $day[0]->teacher;
            unset($teacher);
            unset($teachers);
            foreach ($day as $entry) {
                if ($old_teacher != $entry->teacher) {
                    $old_teacher = $entry->teacher;
                    $teachers[] = $teacher;
                    unset($teacher);
                }
                $teacher[] = $entry;
            }
            $teachers[] = $teacher;
            $days[$i] = $teachers;
        }
        return $days;
    }

    protected function generate_header() {
        $entries_by_date = $this->refactor_entries($this->entries);
        $script = '
            <script type="text/javascript" src="functions.js"></script>
            <script type="text/javascript">
            function fill_in_data() {
                var days = [];';

        foreach ($entries_by_date as $entries_by_teacher) {
            $today = strftime("%A, %d.%m.%Y", $entries_by_teacher[0][0]->time);
            $script .= '
                var teachers = [];';
            foreach ($entries_by_teacher as $entries_for_teacher) {
                $script .= '
                    var entries = [];';
                foreach ($entries_for_teacher as $entry) {
                    $script .= '
                        entries.push(newEntry('.
                        $entry->id.', ["'.
                        $entry->get_time().'", "'.
                        $entry->course.'", "'.
                        $entry->subject.'", "'.
                        $entry->duration.'", "'.
                        $entry->sub.'", "'.
                        $entry->change.'", "'.
                        $entry->oldroom.'", "'.
                        $entry->newroom.'"]));';
                }
                $script .= '
                    teachers.push(newTeacher("'.$entries_for_teacher[0]->teacher.'", entries));';
            }
            $script .= '
                days.push(newDay("'.$today.'", teachers));';
        }
        $script .= '
            insert_days(days);}</script>';
        return $script;
    }

    protected function generate_view() {
        $entries_by_date = $this->refactor_entries($this->entries);

        $html =
         '<div class="ovp_container">
            <img src="1x1.gif" onload="init()">
            <h1>'.self::$title.'</h1>
            <div id="ovp"></div>
          </div>';
        return $html;
    }
}

/**
 * This source provides a simple login interface to authorize access
 * to restricted views.
 * Naturally access is not restricted.
 */
class ovp_login extends ovp_source {
    public static $type = 'login';
    public static $title ='Login';
    public static $priv_req = VIEW_NONE;

    public function __construct($db) {
        parent::__construct($db);
    }

    protected function generate_view() {
        //FIXME: Add CSS hooks
        $html =
         '<div class="ovp_container">
          <h1>Login</h1>
          <p>Um diese Seite öffnen zu können, benötigen Sie ein entsprechend autorisiertes Benutzerkonto.</p>
          <form action="account.php?action=login';
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
          </form>
          </div>';
        return $html;
    }
}

/**
 * This source provides a simple administration interface which most
 * importantly allows setting the passwords of any user.
 * Access msut thus be seriously restricted.
 */
class ovp_admin extends ovp_source {
    public static $type = 'admin';
    public static $title ='Benutzer verwalten';
    public static $priv_req = VIEW_ADMIN;

    public function __construct($db) {
        parent::__construct($db);
    }

    protected function generate_view() {
        //FIXME: i need implementing ;-)
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

    public function __construct(ovp_source $source) {
        $this->source = $source;
        $source_vars = get_class_vars(get_class($source));
        $this->title = $source_vars['title'];
        $this->type = $source_vars['type'];
    }

    private function generate_view() {
        $source_vars = get_class_vars(get_class($this->source));

        $html =
           '<!DOCTYPE html>
            <html>
            <head>
              <title>RLO Onlinevertretungsplan - '.$this->title.'</title>
              '.$this->source->get_header().'
            </head>
            <body>
              '.$this->generate_navi().'
              '.$this->source->get_view().'
            </body>
            </html>';
        return $html;
    }

    private function generate_navi() {
        $sources = array();
        $sources[] = get_class_vars('ovp_public');
        $sources[] = get_class_vars('ovp_print');
        $sources[] = get_class_vars('ovp_author');
        $sources[] = get_class_vars('ovp_admin');

        $html =
             '<div id="ovp_navi">';
        foreach ($sources as $source) {
            if (is_authorized($source['priv_req'])) {
                if ($source['type'] != $this->type) {
                    $html .= '
                <a href="index.php?source='.$source['type'].'">'.$source['title'].'</a> |';
                } else {
                    $html .= '
                <span>'.$source['title'].'</span> |';
                }
            }
        }
        /* FIXME: Is the user logged in? */
        if (is_authorized(PRIV_DEFAULT + 1)){
            $html .= '
                <a href="account.php?action=logout">Logout</a>';
        }
        $html .= '
              </div>';
        return $html;
    }

    public function get_html() {
        return $this->generate_view();
    }
}

?>
