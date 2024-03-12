<?php

// generic MySQL database interface

class DB {
    static $db_conn=null;
    static $exit_on_db_error = false;
    static $show_queries = false;
    static function error($q) {
        echo "Database error\nQuery: $q\n";
        echo static::$db_conn->error;
        echo "\n";
        if (static::$exit_on_db_error) die("DB error\n");
        return false;
    }
    static function init_conn() {
        if (static::$db_conn) return true;
        $dbinfo = json_decode(file_get_contents("db.json"));
        $host = $dbinfo->host;
        $x = explode(":", $host);
        if (sizeof($x)>1) {
            $host = $x[0];
            $port = $x[1];
        } else {
            $port = null;
        }
        mysqli_report(MYSQLI_REPORT_ERROR);
        static::$db_conn = @new mysqli(
            $host, $dbinfo->user, $dbinfo->passwd, $dbinfo->name, $port
        );
        if (mysqli_connect_error()) {
            return static::error('connect to server');
        }
        return true;
    }
    public static function do_query($q) {
        if (static::$show_queries) {
            echo "query: $q\n";
        }
        if (!static::init_conn()) return static::error($q);
        $ret = static::$db_conn->query($q);
        if (!$ret) {
            return static::error($q);
        }
        return $ret;
    }
    public static function insert_id() {
        if (!static::init_conn()) return static::error($q);
        return static::$db_conn->insert_id;
    }
    public static function escape($string) {
        if (!static::init_conn()) return static::error($q);
        return static::$db_conn->escape_string(trim($string));
    }
    public static function begin_transaction() {
        if (!static::init_conn()) return static::error('begin trans');
        if (!static::do_query('begin')) return static::error('begin trans');
    }
    public static function commit_transaction() {
        if (!static::init_conn()) return static::error('commit trans');
        if (!static::do_query('commit')) return static::error('commit trans');
    }
    public static function date_str($y, $m, $d) {
        if ($y < 1000 || $y > 2100) return null;
        if ($m < 1 || $m > 12) return null;
        if ($d < 1 || $m > 31) return null;
        return "$y-$m-$d";
    }
    public static function date_str_year($y) {
        if ($y < 1000 || $y > 2100) return null;
        return "$y-1-1";
    }
    public static function enum($query) {
        $result = static::do_query($query);
        if (!$result) return null;
        $x = [];
        while ($obj = $result->fetch_object()) {
            $x[] = $obj;
        }
        return $x;
    }
}

class Table {
    public static $table_name='undef';
    public static $class_name='undef';

    public static function insert($clause) {
        $ret = DB::do_query(
            sprintf("insert into %s %s",
                static::$table_name, $clause
            )
        );
        if (!$ret) return null;
        return DB::insert_id();
    }

    public static function enum($where_clause=null, $order_clause=null) {
        if ($where_clause) {
            $where_clause = "where $where_clause";
        }
        $result = DB::do_query(
            sprintf("select * from %s %s %s",
                static::$table_name, $where_clause, $order_clause
            )
        );
        if (!$result) return null;
        $x = [];
        while ($obj = $result->fetch_object(static::$class_name)) {
            $x[] = $obj;
        }
        return $x;
    }

    public static function lookup_id($id) {
        $result = DB::do_query(
            sprintf("select * from %s where id=%d",
                static::$table_name, (int)$id
            )
        );
        if (!$result) return null;
        $obj = $result->fetch_object(static::$class_name);
        $result->free();
        return $obj;
    }

    public static function lookup($clause) {
        $result = DB::do_query(
            sprintf("select * from %s where %s",
                static::$table_name, $clause
            )
        );
        if (!$result) return null;
        $obj = $result->fetch_object(static::$class_name);
        $result->free();
        return $obj;
    }

    public function update($clause) {
        return DB::do_query(
            sprintf("update %s set %s where id=%d",
                static::$table_name, $clause, $this->id
            )
        );
    }
}

?>
