#! /usr/bin/sh

# mysql < schema.sql

echo "populating language, instrument, work type"
populate_lang.php
populate_inst.php
populate_work_type.php

echo "running populate_category.php"
populate_category.php > populate_category.out

echo "running populate_work.php"
populate_work.php > populate_work.out

echo "making serial files"
make_ser.php

echo "running populate_sample.php"
populate_sample.php

echo "running digest.php"
digest.php

echo "running digest"
digest
