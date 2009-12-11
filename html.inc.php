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
    private $type;
    private $db;
    private $title;

    public function __construct($type, $db, $title = '') {
        $this->type  = $type;
        $this->db    = $db;
        $this->title = $title;
    }

    abstract protected function generate_html();

    public function get_header() {
        $header = '
          <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
          <link rel="stylesheet" href="style.css" type="text/css">
          <title>'.$this->get_title().'</title>';
        return $header;
    }

    public function get_view() {
        $html = $this->generate_html();
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

    protected function generate_html() {
        $html = '
          <div class="ovp_container">
            <div class="ovp_heading">'.$this->title.'</div>
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
            $time = strptime($day, "%Y-%m-%d");
        }
        $this->entries = $db->get_entries($time);
        $this->today = strftime("%A, %d.%m.%y", $time);
        $this->yesterday = strftime("%Y-%m-%d", $time - 24*60*60)
        $this->tomorrow = strftime("%Y-%m-%d", $time + 24*60*60)
    }

    protected function generate_html() {
        $html =
         '<div class="ovp_container">
            <div class="ovp_heading">'.$this->title.'</div>
            <div class="ovp_date">'.$this->today.'</div>
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
              <a href="index.php?date='.$this->yesterday.'&view='.$this->type'" class="ovp_link_yesterday">Einen Tag zurück</a>
              <a href="index.php?date='.$this->tomorrow.'&view='.$this->type'" class="ovp_link_yesterday">Einen Tag weiter</a>
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

    protected function generate_html() {
        // FIXME: Not yet interactive
        $html =
         '<div class="ovp_container">
            <div class="ovp_heading">'.$this->title.'</div>';

        $olddate = '';
        $oldteacher = '';
        foreach ($this->entries as $entry) {
            if ($olddate != $entry->get_date()) {
                $html .=
           '<div class="ovp_date">'.$entry->get_date().'</div>
            <table class="ovp_table" id="ovp_table_'.$this->type.'">
              <tr class="ovp_row_first">
                <td class="ovp_column_time">Uhrzeit</td>
                <td class="ovp_column_course">Klasse</td>
                <td class="ovp_column_subject">Fach</td>
                <td class="ovp_column_duration">Dauer</td>
                <td class="ovp_column_sub">Vertretung durch</td>
                <td class="ovp_column_change">Weitere Änderungen</td>
                <td class="ovp_column_oldroom">Alter Raum</td>
                <td class="ovp_column_newroom">Neuer Raum</td>
              </tr>';
            }
            if ($oldteacher != $entry->teacher) {
                $html .=
             '<tr class="ovp_row_teacher">
                <td class="ovp_cell_teacher">'.$entry->teacher.'</td>
                <td><a href="">Vertretungsregelung hinzufügen</a></td>
              </tr>';
                $oldteacher = $entry->teacher;
            }
            $html .=
             '<tr class="ovp_row_entry">
                <td class="ovp_column_time">'.    $entry->get_time().'</td>
                <td class="ovp_column_course">'.  $entry->course.    '</td>
                <td class="ovp_column_subject">'. $entry->subject.   '</td>
                <td class="ovp_column_duration">'.$entry->duration.  '</td>
                <td class="ovp_column_sub">'.     $entry->sub.       '</td>
                <td class="ovp_column_change">'.  $entry->change.    '</td>
                <td class="ovp_column_oldroom">'. $entry->oldroom.   '</td>
                <td class="ovp_column_newroom">'. $entry->newroom.   '</td>
              </tr>';
            if ($olddate != $entry->get_date()) {
                $html .=
             '<tr class="ovp_row_newteacher"><a href="">Fehlenden Lehrer eintragen</a></tr>
            </table>';
                $olddate = $entry->get_date();
            }
        }
        $html .= '</div>';
        return $html;
    }
}

/**
 * This source provides a simple login interface to authenticate any access
 * to restricted views.
 * Naturally access is not restricted.
 */
class ovp_login extends ovp_source {
    public function __construct($db) {
        parent::__construct('login', $db, 'RLO Onlinevertretungsplan Login');
    }

    protected function generate_html() {
        $html =
         '<h1>Login</h1>
          <p>Um diese Seite öffnen zu können, benötigen Sie ein entsprechend autorisiertes Benutzerkonto.</p>
          <form action="account.php?action=login';
        if (isset($_GET['continue'])) {
            $html .= '&continue='.$_GET['continue'];
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

    protected function generate_html() {
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
        $this->content = $this->generate_html();
    }

    private function generate_html() {
        $html =
            '<!DOCTYPE html>
             <html>
             <head>
                '.$this->source->get_header().'
             </head>
             <body>
                <a class="ovp_logout_link" href="account.php?action=logout">Logout</a>
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
