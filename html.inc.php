<?php

/**
 * I need the following functions in class db:
 * - get_entries():
 *      should return an array of entry_objects which directly map the
 *      entry table from the db, optionally with the possibility to select
 *      special classes/courses or timeframes.
 *
 * - remove_entry($entry):
 *      removes an entry from the db. is given n entry_object as an argument.
 * - add_entry($entry):
 *      adds an entry to the db. is given an entry_object as an argument.
 * - cleanup_entries():
 *      just a maintenance thing. remove all data no longer needed from the db
 *      DATENSCHUTZ!!!
 *
 * - verify_user($name, $pw) //???? no idea how that should work...
 * - remove_user($name)
 * - add_user($name, $pw_hash, $priv=0)
 */


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

    public function __construct($type, $db) {
        $this->type = $type;
        $this->db = $db;
    }

    abstract public function get_header();
    abstract public function get_view();

    public function get_type() {
        return $this->type;
    }
}

/**
 * This source provides the public view for students
 * Sensitive information like teachers names is not included.
 */
class ovp_table_public extends ovp_source {
    public function __construct($db) {
        parent::__construct("public", $db);
    }

    public function get_header() {
        $header = "<title>RLO Onlinevertretungsplan</title>\n";
        return $header;
    }

    public function get_view() {
    /* TODO: At this point I need a working database connection
     * and knowledge of the database layout.
     */

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

    public function __construct($source) {
        $this->source = $source;
        $this->content = generate_html();
    }

    private function generate_html() {
        $html = "<!doctype html>\n"
                ."<html>\n<head>\n"
                .$source->get_header()
                ."</head>\n<body>\n"
                .$source->get_view()
                ."</body>\n</html>\n";

        return $html;
    }

    public function get_html() {
        return $content;
    }
}

?>
