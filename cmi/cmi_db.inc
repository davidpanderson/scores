<?php

require_once('db.inc');

class DB_location_type extends Table {
    public static $table_name = 'location_type';
    public static $class_name = 'DB_location_type';
}
class DB_location extends Table {
    public static $table_name = 'location';
    public static $class_name = 'DB_location';
}
class DB_sex extends Table {
    public static $table_name = 'sex';
    public static $class_name = 'DB_sex';
}
class DB_ethnicity extends Table {
    public static $table_name = 'ethnicity';
    public static $class_name = 'DB_ethnicity';
}
class DB_period extends Table {
    public static $table_name = 'period';
    public static $class_name = 'DB_period';
}
class DB_person extends Table {
    public static $table_name = 'person';
    public static $class_name = 'DB_person';
}
class DB_language extends Table {
    public static $table_name = 'language';
    public static $class_name = 'DB_language';
}
class DB_composition_type extends Table {
    public static $table_name = 'composition_type';
    public static $class_name = 'DB_composition_type';
}
class DB_ensemble_type extends Table {
    public static $table_name = 'ensemble_type';
    public static $class_name = 'DB_ensemble_type';
}
class DB_ensemble extends Table {
    public static $table_name = 'ensemble';
    public static $class_name = 'DB_ensemble';
}
class DB_organization_type extends Table {
    public static $table_name = 'organization_type';
    public static $class_name = 'DB_organization_type';
}
class DB_organization extends Table {
    public static $table_name = 'organization';
    public static $class_name = 'DB_organization';
}
class DB_instrument extends Table {
    public static $table_name = 'instrument';
    public static $class_name = 'DB_instrument';
}
class DB_instrument_combo extends Table {
    public static $table_name = 'instrument_combo';
    public static $class_name = 'DB_instrument_combo';
}
class DB_role extends Table {
    public static $table_name = 'role';
    public static $class_name = 'DB_role';
}
class DB_person_role extends Table {
    public static $table_name = 'person_role';
    public static $class_name = 'DB_person_role';
}
class DB_license extends Table {
    public static $table_name = 'license';
    public static $class_name = 'DB_license';
}
class DB_composition extends Table {
    public static $table_name = 'composition';
    public static $class_name = 'DB_composition';
}
class DB_score extends Table {
    public static $table_name = 'score';
    public static $class_name = 'DB_score';
}
class DB_venue extends Table {
    public static $table_name = 'venue';
    public static $class_name = 'DB_venue';
}
class DB_performance extends Table {
    public static $table_name = 'performance';
    public static $class_name = 'DB_performance';
}
class DB_concert extends Table {
    public static $table_name = 'concert';
    public static $class_name = 'DB_concert';
}
class DB_recording extends Table {
    public static $table_name = 'recording';
    public static $class_name = 'DB_recording';
}
class DB_release extends Table {
    public static $table_name = '_release';
    public static $class_name = 'DB_release';
}
class DB_rating extends Table {
    public static $table_name = 'rating';
    public static $class_name = 'DB_rating';
}
class DB_rating_useful extends Table {
    public static $table_name = 'rating_useful';
    public static $class_name = 'DB_rating_useful';
}

define('COMPOSITION', 1);
define('PERSON_ROLE', 2);
define('ENSEMBLE', 3);
define('VENUE', 4);
define('PERFORMANCE', 5);
define('CONCERT', 6);
define('PERSON', 7);
define('LOCATION', 8);
define('ORGANIZATION', 9);
define('INST_COMBO', 10);

?>
