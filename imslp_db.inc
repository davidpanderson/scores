<?php

// An object-oriented PHP interface to the IMSLP database.
// Each table has a corresponding class with lookup/insert/enum functions

// Note: the classes all have member functions that are the same
// except for a table and class name.
// Maybe there's a way to move these to a base class; I can't figure it out.

require_once("db_conn.inc");

$exit_on_db_error = false;
    // set this to true in maintenance scripts;
    // show error details and exit

class DB extends DbConn {
    static $instance;

    static function get() {
        if (isset(self::$instance)) {
            return self::$instance;
        }
        $x = json_decode(file_get_contents("db.json"));
        $instance = new DbConn();
        $ret = $instance->init_conn(
            $x->db_user, $x->db_passwd, $x->db_host, $x->db_name
        );
        if (!$ret) {
            echo "Can't connect to DB";
            die();
        }
        self::$instance = $instance;
        return $instance;
    }

    static function begin_transaction() {
        $db = db::get();
        $db->do_query('begin');
    }
    static function commit_transaction() {
        $db = db::get();
        $db->do_query('commit');
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
    function update($clause) {
        $db = DB::get();
        return $db->update($this, 'person', $clause);
    }
}

class DB_style {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('style', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function lookup_id($id) {
        $db = DB::get();
        return $db->lookup_id($id, 'style', 'DB_style');
    }
    static function lookup($clause) {
        $db = DB::get();
        return $db->lookup('style', 'DB_style', $clause);
    }
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('style', 'DB_style', $where_clause, $order_clause);
    }
}

class DB_publisher {
    static function lookup_id($id) {
        $db = DB::get();
        return $db->lookup_id($id, 'publisher', 'DB_publisher');
    }
    static function lookup($clause) {
        $db = DB::get();
        return $db->lookup('publisher', 'DB_publisher', $clause);
    }
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('publisher', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('publisher', 'DB_publisher', $where_clause, $order_clause);
    }
}

class DB_work {
    static function lookup_id($id) {
        $db = DB::get();
        return $db->lookup_id($id, 'work', 'DB_work');
    }
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('work', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    function update($clause) {
        $db = DB::get();
        return $db->update($this, 'work', $clause);
    }
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('work', 'DB_work', $where_clause, $order_clause);
    }
}

class DB_copyright {
    static function lookup_id($id) {
        $db = DB::get();
        return $db->lookup_id($id, 'copyright', 'DB_copyright');
    }
    static function lookup($clause) {
        $db = DB::get();
        return $db->lookup('copyright', 'DB_copyright', $clause);
    }
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('copyright', $clause);
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
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('score_file_set', 'DB_score_file_set', $where_clause, $order_clause);
    }
}

class DB_score_file {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('score_file', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('score_file', 'DB_score_file', $where_clause, $order_clause);
    }
    function update($clause) {
        $db = DB::get();
        return $db->update($this, 'score_file', $clause);
    }
}

class DB_audio_file_set {
    static function lookup_id($id) {
        $db = DB::get();
        return $db->lookup_id($id, 'audio_file_set', 'DB_audio_file_set');
    }
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('audio_file_set', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('audio_file_set', 'DB_audio_file_set', $where_clause, $order_clause);
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
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('audio_file', 'DB_audio_file', $where_clause, $order_clause);
    }
    function update($clause) {
        $db = DB::get();
        return $db->update($this, 'audio_file', $clause);
    }
}

class DB_performer_role {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('performer_role', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function lookup_id($id) {
        $db = DB::get();
        return $db->lookup_id($id, 'performer_role', 'DB_performer_role');
    }
    static function lookup($clause) {
        $db = DB::get();
        return $db->lookup('performer_role', 'DB_performer_role', $clause);
    }
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('performer_role', 'DB_performer_role', $where_clause, $order_clause);
    }
}

class DB_ensemble {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('ensemble', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function lookup_id($id) {
        $db = DB::get();
        return $db->lookup_id($id, 'ensemble', 'DB_ensemble');
    }
    static function lookup($clause) {
        $db = DB::get();
        return $db->lookup('ensemble', 'DB_ensemble', $clause);
    }
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('ensemble', 'DB_ensemble', $where_clause, $order_clause);
    }
}

class DB_audio_performer {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('audio_performer', $clause);
        if (!$ret) return 0;
        return 1;       // table has no id
    }
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('audio_performer', 'DB_audio_performer', $where_clause, $order_clause);
    }
}

class DB_arrangement_target {
    static function insert($clause) {
        $db = DB::get();
        $ret = $db->insert('arrangement_target', $clause);
        if (!$ret) return 0;
        return $db->insert_id();
    }
    static function enum($where_clause, $order_clause=null) {
        $db = DB::get();
        return $db->enum('arrangement_target', 'DB_arrangement_target', $where_clause, $order_clause);
    }
    static function lookup($clause) {
        $db = DB::get();
        return $db->lookup('arrangement_target', 'DB_arrangement_target', $clause);
    }
    static function lookup_id($id) {
        $db = DB::get();
        return $db->lookup_id($id, 'arrangement_target', 'DB_arrangement_target');
    }
}

?>
