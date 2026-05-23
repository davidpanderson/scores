<?php

// generate web page for popular composition searches

require_once('quick.inc');

function main() {
    page_head('Popular composition searches');
    quick_search();
    page_tail();
}

main();
?>
