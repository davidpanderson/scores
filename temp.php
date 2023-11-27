<?php

function foo() {
    return null;
}

[$x, $y] = foo();
var_dump($x);

?>
