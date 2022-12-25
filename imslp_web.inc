<?php

// IMSLP-specific web utility functions

function hier_string($fs) {
    $x = [];
    if ($fs->hier1) $x[]=$fs->hier1;
    if ($fs->hier2) $x[]=$fs->hier2;
    if ($fs->hier3) $x[]=$fs->hier3;
    return implode('/', $x);
}

?>
