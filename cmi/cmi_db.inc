<?php

require_once('db.inc');

class LOCATION_TYPE extends TABLE {
    public static $table_name = 'location_type';
    public static $class_name = 'LOCATION_TYPE';
}
class LOCATION extends TABLE {
    public static $table_name = 'location';
    public static $class_name = 'LOCATION';
}
class GENDER extends TABLE {
    public static $table_name = 'gender';
    public static $class_name = 'GENDER';
}
class RACE extends TABLE {
    public static $table_name = 'race';
    public static $class_name = 'RACE';
}
class ETHNICITY extends TABLE {
    public static $table_name = 'ethnicity';
    public static $class_name = 'ETHNICITY';
}
class PERIOD extends TABLE {
    public static $table_name = 'period';
    public static $class_name = 'PERIOD';
}
class PERSON extends TABLE {
    public static $table_name = 'person';
    public static $class_name = 'PERSON';
}
class LANGUAGE extends TABLE {
    public static $table_name = 'language';
    public static $class_name = 'LANGUAGE';
}
class COMPOSITION_TYPE extends TABLE {
    public static $table_name = 'composition_type';
    public static $class_name = 'COMPOSITION_TYPE';
}
class ENSEMBLE_TYPE extends TABLE {
    public static $table_name = 'ensemble_type';
    public static $class_name = 'ENSEMBLE_TYPE';
}
class ENSEMBLE extends TABLE {
    public static $table_name = 'ensemble';
    public static $class_name = 'ENSEMBLE';
}
class ORGANIZATION extends TABLE {
    public static $table_name = 'organization';
    public static $class_name = 'ORGANIZATION';
}
class INSTRUMENT extends TABLE {
    public static $table_name = 'instrument';
    public static $class_name = 'INSTRUMENT';
}
class INSTRUMENT_COMBO extends TABLE {
    public static $table_name = 'instrument_combo';
    public static $class_name = 'INSTRUMENT_COMBO';
}
class ROLE extends TABLE {
    public static $table_name = 'role';
    public static $class_name = 'ROLE';
}
class PERSON_ROLE extends TABLE {
    public static $table_name = 'person_role';
    public static $class_name = 'PERSON_ROLE';
}
class LICENSE extends TABLE {
    public static $table_name = 'license';
    public static $class_name = 'LICENSE';
}
class COMPOSITION extends TABLE {
    public static $table_name = 'composition';
    public static $class_name = 'COMPOSITION';
}
class SCORE extends TABLE {
    public static $table_name = 'score';
    public static $class_name = 'SCORE';
}
class VENUE extends TABLE {
    public static $table_name = 'venue';
    public static $class_name = 'VENUE';
}
class PERFORMANCE extends TABLE {
    public static $table_name = 'performance';
    public static $class_name = 'PERFORMANCE';
}
class CONCERT extends TABLE {
    public static $table_name = 'concert';
    public static $class_name = 'CONCERT';
}
class RECORDING extends TABLE {
    public static $table_name = 'recording';
    public static $class_name = 'RECORDING';
}
class RELEASE extends TABLE {
    public static $table_name = '_release';
    public static $class_name = 'RELEASE';
}

?>
