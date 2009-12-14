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
    protected $type;
    protected $db;
    protected $title;

    public function __construct($type, $db, $title = '') {
        $this->type  = $type;
        $this->db    = $db;
        $this->title = $title;
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

    public function get_type() {
        return $this->type;
    }

    public function get_title() {
        return $this->title;
    }
}

/**
 * This source provides the public view for students
 * Sensitive information like teachers names is not included.
 */
class ovp_public extends ovp_source {
    private $entries;

    public function __construct($db, $time = -1) {
        parent::__construct('public', $db, 'RLO Onlinevertretungsplan');
        if ($time == -1) {
            $time = time();
        }
        $this->entries = $db->get_entries($time);
    }

    protected function generate_view() {
        $html = '
          <div class="ovp_container">
            <h1 class="ovp_heading">'.$this->title.'</h1>
            <table class="ovp_table" id="ovp_table_'.$this->type.'">
              <tr class="ovp_row_first">
                <td class="ovp_column_time">Uhrzeit</td>
                <td class="ovp_column_course">Klasse</td>
                <td class="ovp_column_subject">Fach</td>
                <td class="ovp_column_oldroom">Originalraum</td>
                <td class="ovp_column_duration">Dauer</td>
                <td class="ovp_column_change">Änderung</td>
                <td class="ovp_column_newroom">Neuer Raum</td>
              </tr>';
        foreach ($this->entries as $entry) {
            $html .= '
              <tr class="ovp_row_entry">
                <td class="ovp_column_time">'.    $entry->get_time().'</td>
                <td class="ovp_column_course">'.  $entry->course.    '</td>
                <td class="ovp_column_subject">'. $entry->subject.   '</td>
                <td class="ovp_column_oldroom">'. $entry->oldroom.   '</td>
                <td class="ovp_column_duration">'.$entry->duration.  '</td>
                <td class="ovp_column_change">'.  $entry->change.    '</td>
                <td class="ovp_column_newroom">'. $entry->newroom.   '</td>
              </tr>';
        }
        $html .= '
            </table>
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
    private $entries;
    private $today;
    private $yesterday;
    private $tomorrow;

    public function __construct($db, $day = -1) {
        parent::__construct('print', $db, 'Vertretungsplan');
        if ($day == -1) {
            $time = time();
        } else {
            if (!preg_match('/(\d{4})-(\d\d)-(\d\d)/', $day, $matches)) {
                exit('lol wut?');
            }
            $time = mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
        }
        $this->entries = $db->get_entries($time);
        $this->today = strftime("%A, %d.%m.%y", $time);
        $this->yesterday = strftime("%Y-%m-%d", $time - 24*60*60);
        $this->tomorrow = strftime("%Y-%m-%d", $time + 24*60*60);
    }

    protected function generate_view() {
        $html =
         '<div class="ovp_container">
            <h1 class="ovp_heading">'.$this->title.'</h1>
            <h2 class="ovp_date">'.$this->today.'</h2>
            <table class="ovp_table" id="ovp_table_'.$this->type.'">
              <tr class="ovp_row_first">
                <td class="ovp_column_time">Uhrzeit</td>
                <td class="ovp_column_course">Klasse</td>
                <td class="ovp_column_subject">Fach</td>
                <td class="ovp_column_duration">Dauer</td>
                <td class="ovp_column_sub">Vertretung durch</td>
                <td class="ovp_column_newroom">Raum</td>
              </tr>';

        $oldteacher = '';
        foreach ($this->entries as $entry) {
            if ($entry->teacher != $oldteacher) {
                $html .=
             '<tr class="ovp_row_teacher">
                <td class="ovp_cell_teacher">'.$entry->teacher.'</td>
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
                $change = $entry->change;
            }

            $html .=
             '<tr class="ovp_row_entry">
                <td class="ovp_column_time">'.    $entry->get_time().'</td>
                <td class="ovp_column_course">'.  $entry->course.    '</td>
                <td class="ovp_column_subject">'. $entry->subject.   '</td>
                <td class="ovp_column_duration">'.$entry->duration.  '</td>
                <td class="ovp_column_sub">'.     $changes.          '</td>
                <td class="ovp_column_newroom">'. $entry->newroom.   '</td>
              </tr>';
        }
        $html .=
           '</table>
            <div class="ovp_day_links">
              <a href="index.php?view='.$this->type.'&date='.$this->yesterday.'" class="ovp_link_yesterday">Einen Tag zurück</a>
              <a href="index.php?view='.$this->type.'&date='.$this->tomorrow.'" class="ovp_link_tomorrow">Einen Tag weiter</a>
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
    private $entries;

    public function __construct($db) {
        parent::__construct('author', $db, 'RLO Onlinevertretungsplan Zentrale');
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

        $oldtime = '';
        foreach ($entries as $entry) {
            if($oldtime != $entry-time && $oldtime != '') {
                $entries_by_date[count($entries_by_date)] = $entries_for_date;
                unset($entries_for_date);
            }
            $entries_for_date[count($entries_for_date)] = $entry;
        }
        $entries_by_date[count($entries_by_date)] = $entries_for_date;

        $oldteacher = '';
        foreach ($entries_by_date as $entries_for_date) {
            foreach ($entries_for_date as $entry) {
                if ($oldteacher != $entry->teacher && $oldteacher != '') {
                    $entries_by_teacher[count($entries_by_teacher)] = $entries_for_teacher;
                    unset($entries_for_teacher);
                }
                $entries_for_teacher[count($entries_for_teacher)] = $entry;
            }
            $entries_by_teacher[count($entries_by_teacher)] = $entries_for_teacher;
            $entries_by_date_new[count($entries_for_date_new)] = $entries_by_teacher;
        }

        return $entries_by_date_new;
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
            insertDays(days);}</script>';
        return $script;
    }

    protected function generate_view() {
        $entries_by_date = $this->refactor_entries($this->entries);

        $html =
         '<div class="ovp_container">
            <img src="1x1.gif" onload="init()">
            <h1 class="ovp_heading">'.$this->title.'</h1>
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
    public function __construct($db) {
        parent::__construct('login', $db, 'RLO Onlinevertretungsplan Login');
    }

    protected function generate_view() {
        //FIXME: Add CSS hooks
        $html =
         '<h1>Login</h1>
          <p>Um diese Seite öffnen zu können, benötigen Sie ein entsprechend autorisiertes Benutzerkonto.</p>
          <form action="account.php?action=login';
        if (isset($_GET['continue'])) {
            $html .= '&continue='.urlencode($_GET['continue']);
        }
        $html .= '" method="POST">
            <table>
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
        return $html;
    }
}

/**
 * This source provides a simple administration interface which most
 * importantly allows setting the passwords of any user.
 * Access msut thus be seriously restricted.
 */
class ovp_admin extends ovp_source {
    public function __construct($db) {
        parent::__construct('admin', $db, 'RLO Onlinevertretungsplan Admin');
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
    private $content; // the whole html page

    public function __construct(ovp_source $source) {
        $this->source = $source;
        $this->content = $this->generate_view();
    }

    private function generate_view() {
        //FIXME: Don't show the navibar before login
        $html =
           '<!DOCTYPE html>
            <html>
            <head>
                <title>'.$this->source->get_title().'</title>
                '.$this->source->get_header().'
            </head>
            <body>
              <div id="ovp_navi">
                <a href="index.php?view=public" class="ovp_link_navi">OVP</a> |
                <a href="index.php?view=print" class="ovp_link_navi">Aushang</a> |
                <a href="index.php?view=author" class="ovp_link_navi">Einträge verwalten</a> |
                <a href="index.php?view=admin" class="ovp_link_navi">Benutzer verwalten</a> |
                <a href="account.php?action=logout" class="ovp_link_navi">Logout</a>
                <!-- more links go here -->
              </div>
              '.$this->source->get_view().'
            </body>
            </html>';
        return $html;
    }

    public function get_html() {
        return $this->content;
    }
}

?>
