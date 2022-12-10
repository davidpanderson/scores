<?php

$f = fopen('david_page_dump.txt', 'r');
$x = fgets($f);
$y = json_decode($x);
foreach ($y as $t => $z) {
    echo "======= $t =========\n$z\n";
}

?>
