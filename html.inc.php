<?php

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

class ovp_table_public extends ovp_source {
    public function __construct() {
        parent::__construct("public");
    }

    public function get_header() {
        $header = "<title>RLO Onlinevertretungsplan</title>";
        return $header;
    }

    public function get_view() {
    /* TODO: At this point I need a working database connection
     * and knowledge of the database layout.
     */

   }

}

class ovp_page {

    private $source; // the ovp_source object used to generate the page
    private $content; // the whole html page


    public function __construct($source) {
        $this->source = $source;
        $this->content = "<html></html>";
    }

    public function get_html() {
        return $content;
    }
}

/* missing classes:
 * - ovp_table_lange
 * - ovp_table_print
 * - ovp_admin
 * - ovp_login
 */
?>