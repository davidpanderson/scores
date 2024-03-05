<?php

require_once('../inc/util.inc');
require_once('cmi_db.inc');

page_head('People');

$lts = DB_Location_type::enum();
start_table();
foreach ($lts as $lt) {
    row2($lt->id, $lt->name);
}
end_table();

page_tail();
?>
