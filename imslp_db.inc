<?php

// An object-oriented PHP interface to the IMSLP database.
// Each table has a corresponding class with lookup/insert/enum functions

require_once("db_conn.inc");

class DB extends DbConn {
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
    // Important!  Prevents SQL injection attacks.
    //
    static function escape($string) {
        $db = DB::get();
        return $db->base_escape_string(trim($string));
    }
}

class DB_person {
    static function lookup_id($id) {
        $db = DB::get();
        return $db->lookup_id($id, 'person', 'DB_person');
    }
    static function lookup($clause) {
        $db = DB::get();
        return $db->lookup('person', 'DB_person', $clause);
    }

    // clause is like "(a,b) values (0, 1)"
    //
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('person', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('person', 'DB_person', $where_clause, $order_clause);
    }
}

class DB_style {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('style', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class DB_external_link {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('external_link', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class DB_composition {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('composition', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    function update($clause) {
        $db = DB::get();
        return $db->update($this, 'composition', $clause);
    }
}

class DB_imslp_user {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('imslp_user', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class DB_license {
    static function lookup($clause) {
        $db = DB::get();
        return $db->lookup('license', 'DB_license', $clause);
    }
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('license', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class DB_score_file_set {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('score_file_set', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    function update($clause) {
        $db = DB::get();
        return $db->update($this, 'score_file_set', $clause);
    }
}

class DB_score_file {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('score_file', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}

class DB_audio_file_set {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('audio_file_set', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    function update($clause) {
        $db = DB::get();
        return $db->update($this, 'audio_file_set', $clause);
    }
}

class DB_audio_file {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('audio_file', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
}
