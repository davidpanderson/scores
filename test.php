<?php

require_once('imslp_db.inc');

if (0) {
    $c = DB_copyright::lookup_id(64);
    $x = [1,4,5];
    $c->update(sprintf("x='%s'", json_encode($x)));
    $c = DB_copyright::lookup_id(64);
    print_r($c);
}

if (0) {
    $cs = DB_copyright::enum("2 member of (x->'$')");
    print_r($cs);
}

if (0) {
    $ics = DB_instrument_combo::enum();
    $x = [];
    foreach ($ics as $i) {
        $x[$i->md5] = $i;
    }
    print_r($x['d8ad6812c7e01492aaff6bc37507e759']->id);
}

if (0) {
    $x = [];
    $x[0] = 3;
    print_r($x);
    echo json_encode($x, JSON_NUMERIC_CHECK);
}

if (0) {
    $a = new StdClass;
    $b = new StdClass;
    $a->foo = 'a';
    $b->foo = 'b';
    $x = [0=>$a, 1=>$b];
    print_r($x);

    function foo($n) {
        global $x;
        return $x[$n];
    }

    $y = foo(0);
    $y->foo = 'c';
    print_r($x);
        
}

if (0) {
    echo is_numeric('4a')?'yes':'no';
}

//print_r(unserialize(file_get_contents('inst_by_code.ser')));
// alter table copyright add index ix( (cast(x->'$' as unsigned array)) )

if (0) {
    $x = 'PMLP01521-Mozart_-_Symphony_No.12_in_G,_K.110_-autograph-.pdf';
    echo md5($x);
}

if (1) {
    print_r(array_merge([1,2],[2,3]));
}

?>
