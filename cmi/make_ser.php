#! /usr/bin/env php

<?php

// Serialize tables that are populated
// during the population of person and composition,
// so you need to do this after each run of populate_comp.php

require_once('write_ser.inc');

write_ser_period();
write_ser_license();
write_ser_instrument_combo();
?>
