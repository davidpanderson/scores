<?php

//require_once("../inc/db.inc");

// represents a connection to a database.
// Intended to be subclassed (e.g., BoincDb, BossaDb)
//
class DbConn {
    var $db_conn;
    var $db_name;
    var $readonly;

    function init_conn($user, $passwd, $host, $name) {
        $x = explode(":", $host);
        if (sizeof($x)>1) {
            $host = $x[0];
            $port = $x[1];
        } else {
            $port = null;
        }
        $this->db_conn = @new mysqli($host, $user, $passwd, $name, $port);

        // mysqli returns an object even if the connection is not established
        if (mysqli_connect_error()) {
            return false;
        }
        if (!$this->db_conn) {
            return false;
        }
        $this->db_name = $name;
        return true;
    }

    // in keeping with PHP/MySQL convention, return true (nonzero) on success.
    //
    function do_query($q) {
        global $generating_xml;
        $q = str_replace('DBNAME', $this->db_name, $q);
        //echo "query: $q<br>\n";
        $ret = $this->db_conn->query($q);
        if (!$ret) {
            if (!$generating_xml) {
                echo "Database Error<br>\n";
                exit;
            }
            //echo ": ", $this->base_error(), "\n<pre>";
            //var_dump(debug_backtrace());
            //echo "</pre>query: $q\n";
            return null;
        }
        return $ret;
    }

    // # rows affected by last query
    //
    function affected_rows() {
        return $this->db_conn->affected_rows;
    }

    function lookup_fields($table, $classname, $fields, $clause) {
        $query = "select $fields from DBNAME.$table where $clause";
        $result = $this->do_query($query);
        if (!$result) {
            return null;
        }
        $obj = $result->fetch_object($classname);
        $result->free();
        return $obj;
    }
    function lookup($table, $classname, $clause) {
        return $this->lookup_fields($table, $classname, "*", $clause);
    }

    function lookup_id($id, $table, $classname) {
        $id = (int)$id;
        return $this->lookup($table, $classname, "id=$id");
    }

    function enum_general($classname, $query) {
        $result = $this->do_query($query);
        if (!$result) return null;
        $x = array();
        while ($obj = $result->fetch_object($classname)) {
            $x[] = $obj;
        }
        $result->free();
        return $x;
    }

    function enum_fields(
        $table, $classname, $fields, $where_clause, $order_clause
    ) {
        $x = array();
        if ($where_clause) {
            $where_clause = "where $where_clause";
        }
        $query = "select $fields from DBNAME.$table $where_clause $order_clause";
        return $this->enum_general($classname,$query);
    }

    function enum($table, $classname, $where_clause=null, $order_clause=null) {
        return self::enum_fields(
            $table, $classname, '*', $where_clause, $order_clause
        );
    }

    function update($obj, $table, $clause) {
        $query = "update DBNAME.$table set $clause where id=$obj->id";
        return $this->do_query($query);
    }
    function update_aux($table, $clause) {
        $query = "update DBNAME.$table set $clause";
        return $this->do_query($query);
    }
    function insert($table, $clause) {
        $query = "insert into DBNAME.$table $clause";
        return $this->do_query($query);
    }
    function delete($obj, $table) {
        $query = "delete from DBNAME.$table where id=$obj->id";
        return $this->do_query($query);
    }
    function delete_aux($table, $clause) {
        $query = "delete from DBNAME.$table where $clause";
        return $this->do_query($query);
    }
    function insert_id() {
        return $this->db_conn->insert_id;
    }
    function get_int($query, $field) {
        $result = $this->do_query($query);
        if (!$result) return false;
        $x = $result->fetch_object("StdClass");
        $result->free();
        if ($x) return $x->$field;
        return false;
    }
    function get_double($query, $field) {
        $result = $this->do_query($query);
        if (!$result) return false;
        $x = $result->fetch_object("StdClass");
        $result->free();
        if ($x) return (double)$x->$field;
        return false;
    }
    function count($table, $clause="TRUE") {
        $query = "select count(*) as total from DBNAME.$table where $clause";
        return $this->get_int($query, 'total');
    }
    function sum($table, $field, $clause="") {
        $query = "select sum($field) as total from DBNAME.$table $clause";
        return $this->get_double($query, 'total');
    }
    function max($table, $field, $clause="") {
        $query = "select max($field) as total from DBNAME.$table $clause";
        return $this->get_double($query, 'total');
    }
    function replace($table, $clause) {
        $query = "replace into DBNAME.$table set $clause";
        return $this->do_query($query);
    }
    function base_escape_string($string) {
        return $this->db_conn->escape_string($string);
    }
    function base_error() {
        return $this->db_conn->error;
    }
    function base_errno() {
        return $this->db_conn->errno;
    }
}

?>
