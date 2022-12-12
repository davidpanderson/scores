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
    static function lookup($clause) {
        $db = IMSLP_DB::get();
        return $db->lookup('person', 'IMSLP_person', $clause);
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

class IMSLP_style {
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('style', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class IMSLP_external_link {
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('external_link', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class IMSLP_composition {
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('composition', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class IMSLP_imslp_user {
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('imslp_user', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class IMSLP_license {
    static function lookup($clause) {
        $db = IMSLP_DB::get();
        return $db->lookup('license', 'IMSLP_license', $clause);
    }
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('license', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class IMSLP_score_file_set {
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('score_file_set', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class IMSLP_score_file {
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('score_file', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class IMSLP_audio_file_set {
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('score_file', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class IMSLP_audio_file {
    static function insert($clause) {
        $db = IMSLP_DB::get();
        $ret = $db->insert('score_file', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}
