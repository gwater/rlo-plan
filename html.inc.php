<?php

abstract class ovp_content {
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

class ovp_table_public extends ovp_content {
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

/* missing classes:
 * - ovp_table_lange
 * - ovp_table_print
 * - ovp_admin
 * - ovp_login
 */
?>