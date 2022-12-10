<?php

// An object-oriented PHP interface to the IMSLP database.
// Each table has a corresponding class with lookup/insert/enum functions

require_once("db_conn.inc");

class IMSLP_DB extends DbConn {
    static function get() {
        $x = json_decode(file_get_contents("db.json"));
        $instance = new DbConn();
        $ret = $instance->init_conn(
            $x->db_user, $x->db_passwd, $x->db_host, $x->db_name
        );
        if (!$ret) {
            echo "Can't connect to DB";
            die();
        }
        return $instance;
    }

    // Escape quotes in a string to be inserted in the DB.
    // Important!  prevents SQL injection attacks
    //
    static function escape_string($string) {
        $db = IMSLP_DB::get();
        return $db->base_escape_string(trim($string));
    }
}

class IMSLP_person {
    static function lookup_id($id) {
        $db = IMSLP_DB::get();
        return $db->lookup_id($id, 'person', 'IMSLP_person');
    }
    // clause is like "(a,b) values (0, 1)"
    //
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('person', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function enum($where_clause, $order_clause=null) {
        $db = IMSLP_DB::get();
        return $db->enum('person', 'IMSLP_person', $where_clause, $order_clause);
    }
}

class IMSLP_language {
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('language', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}
