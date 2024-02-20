<?php

// generic MySQL database interface

class DB {
    static $db_conn=null;
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
            echo "Can't connect to DB\n";
            return false;
        }
        return true;
    }
    public static function do_query($q) {
        echo "query: $q\n";
        if (!static::init_conn()) return null;
        $ret = static::$db_conn->query($q);
        if (!$ret) {
            echo "Database error\nQuery: $q\n";
            echo static::$db_conn->error;
            return null;
        }
        return $ret;
    }
    public static function escape($string) {
        if (!static::init_conn()) return null;
        return static::$db_conn->escape_string(trim($string));
    }
}

class TABLE {
    public static $table_name='undef';
    public static $class_name='undef';

    public static function insert($clause) {
        return DB::do_query(
            sprintf("insert into %s %s",
                static::$table_name, $clause
            )
        );
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
