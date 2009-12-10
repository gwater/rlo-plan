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

    public function __construct($type, $db, $title="") {
        $this->type = $type;
        $this->db = $db;
        $this->title = $title;
    }

    abstract public function get_header();
    abstract public function get_view();

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
class ovp_table_public extends ovp_source {
    private $entries;

    public function __construct($db) {
        parent::__construct("public", $db, "RLO Onlinevertretungsplan");
        $this->entries = $db->get_entries(time());
    }

    public function get_header() {
        $header = "<title>".get_title()."</title>\n";
        return $header;
    }

    public function get_view() {
        $html = generate_html();
        return $html;
    }

    private function generate_html() {
        $html =
         '<div class="ovp_container">
            <div class="ovp_table_heading">'.get_title().'</div>
            <table class="ovp_table" id="ovp_table_public">
              <tr class="ovp_table_firstline">
                <td class="ovp_column_time">Uhrzeit</td>
                <td class="ovp_column_course">Klasse</td>
                <td class="ovp_column_subject">Fach</td>
                <td class="ovp_column_duration">Dauer</td>
                <td class="ovp_column_sub">Vertretung durch</td>
                <td class="ovp_column_room">Raum</td>
              </tr>';
        foreach ($entries as $entry) {
            $html .=
             '<tr class="ovp_table_entryline">
                <td class="ovp_column_time">'.strftime('%H:%M', $entry->time).'</td>
                <td class="ovp_column_course">'.  $entry->course.  '</td>
                <td class="ovp_column_subject">'. $entry->subject. '</td>
                <td class="ovp_column_oldroom">'. $entry->oldroom. '</td>
                <td class="ovp_column_duration">'.$entry->duration.'</td>
                <td class="ovp_column_change">'.  $entry->change.  '</td>
              </tr>';
        }
        $html .=
           '</table>
          </div>';
    }

}

/**
 * This source provides the traditional view for printout.
 * since it contains sensitive information its access must be restricted to
 * school personnel.
 */
class ovp_table_print extends ovp_source {
    public function __construct($db) {
        parent::__construct("print", $db);
    }

    public function get_header() {
        $header = "<title>RLO Offlinevertretungsplan</title>\n";
        return $header;
    }

    public function get_view() {
    /* TODO: At this point I need a working database connection
     * and knowledge of the database layout.
     */
   }

}

/**
 * This source provides the view for Frau Lange. It allows adding, removing
 * and editing entries of the plan and thus access must be restricted to
 * authorized school personnel.
 */
class ovp_lange extends ovp_source {
    public function __construct($db) {
        parent::__construct("lange", $db);
    }

    public function get_header() {
        $header = "<title>RLO Onlinevertretungsplan Kontrolle</title>\n";
        return $header;
    }

    public function get_view() {
    /* TODO: At this point I need a working database connection
     * and knowledge of the database layout.
     */
   }

}

/**
 * This source provides a simple login interface to authenticate any access
 * to restricted views.
 * Naturally access is not restricted.
 */
class ovp_login extends ovp_source {
    public function __construct($db) {
        parent::__construct("login", $db);
    }

    public function get_header() {
        $header = "<title>RLO Onlinevertretungsplan Login</title>\n";
        return $header;
    }

    public function get_view() {
    /* TODO: At this point I need a working database connection
     * and knowledge of the database layout.
     */
   }

}

/**
 * This source provides a simple administration interface which most
 * importantly allows setting the passwords of any user.
 * Access msut thus be seriously restricted.
 */
class ovp_admin extends ovp_source {
    public function __construct($db) {
        parent::__construct("admin", $db);
    }

    public function get_header() {
        $header = "<title>RLO Onlinevertretungsplan Administration</title>\n";
        return $header;
    }

    public function get_view() {
    /* TODO: At this point I need a working database connection
     * and knowledge of the database layout.
     */
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
