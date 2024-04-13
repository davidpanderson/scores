# CMI DB schema

# to recreate DB:
# mysql < create_prod.sql
# or mysql < create_dev.sql

# an indexed or unique text field must be varchar
# fulltext index can cover at most 256 char ?? still true?

# e.g. continent, subcontinent, country, province, city
create table location_type (
    id                      integer         not null auto_increment,
    name                    varchar(255),
    primary key(id),
    unique(name)
);

create table location (
    id                      integer         not null auto_increment,
    name                    varchar(255),
    adjective               varchar(255),
    name_native             varchar(255),
    adjective_native        varchar(255),
    type                    integer,
    parent                  integer,
    ancestors               json,
    primary key(id),
    unique(name, type, parent)
);

create table sex (
    id                      integer         not null auto_increment,
    name                    varchar(255),
    primary key(id),
    unique(name)
);

create table ethnicity (
    id                      integer         not null auto_increment,
    name                    varchar(255),
    primary key(id),
    unique(name)
);

# classical, romantic etc.
#
create table period (
    id                      integer         not null auto_increment,
    name                    varchar(190)    not null,
    unique(name),
    primary key(id)
);

# can create fulltext index on fields totaling < 256 chars

create table person (
    id                      integer         not null auto_increment,
    first_name              varchar(90)     not null,
    last_name               varchar(90)     not null,
    alternate_names         text,
    born                    integer,
    birth_place             integer,
    died                    integer,
    death_place             text,
    locations               JSON,
    periods                 JSON,
    sex                     integer,
    ethnicity               JSON,
    primary key(id)
);
alter table person add fulltext index (first_name, last_name);
alter table person add index iname(first_name, last_name);
alter table person add index inat( (cast(locations->'$' as unsigned array)) );
alter table person add index iper( (cast(periods->'$' as unsigned array)) );
alter table person add index psex (sex);

# written language
create table language (
    id                      integer         not null auto_increment,
    code                    varchar(64)     not null,
        # ISO 639-1 two letter code
    name                    varchar(64)     not null,
    primary key(id),
    unique(name)
);

create table composition_type (
    id                      integer         not null auto_increment,
    imslp_code              varchar(190)    not null,
    name                    varchar(190)    not null,
    descendant_ids          json,
    unique(name),
    unique(imslp_code),
    primary key(id)
);

# orchestra, string quartet, ...
create table ensemble_type (
    id                      integer         not null auto_increment,
    name                    varchar(190)    not null,
    primary key(id)
);

create table ensemble (
    id                      integer         not null auto_increment,
    name                    varchar(190)    not null,
    alternate_names         text,
    started                 integer,
    ended                   integer,
    type                    integer,
    location                integer,
    members                 json,
        # person_roles
    period                  integer         not null default 0,
    unique(name),
    primary key(id)
);

# publisher, recording company, conservatory
create table organization_type (
    id                      integer         not null auto_increment,
    name                    varchar(190)    not null,
    primary key(id)
);

create table organization (
    id                      integer         not null auto_increment,
    name                    text            not null,
    type                    integer,
    started                 integer,
    ended                   integer,
    location                integer,
    url                     varchar(255),
    primary key(id)
);


create table instrument (
    id                      integer         not null auto_increment,
    imslp_code              varchar(190)    not null,
    name                    varchar(190)    not null,
    ncombos                 integer         not null default 0,
    primary key(id),
    unique(name)
);

create table instrument_combo (
    id                      integer         not null auto_increment,
    instruments             json,
        # a structure consisting of 2 same-size lists:
        # count => array of counts (always >0)
        # id => array of instrument ids
        # this lets us search with member/overlap/contain
        # on (sets of) instrument IDs
    md5                     varchar(64),        # hash of instruments
    nworks                  integer         not null default 0,
    nscores                 integer         not null default 0,
    unique(md5),
    primary key(id)
);
alter table instrument_combo add index icinst( (cast(instruments->'$.id' as unsigned array)) );

# performer, composer, lyricist, etc.
create table role (
    id                      integer         not null auto_increment,
    name                    varchar(255),
    primary key(id)
);

# the combination of a person/ensemble and a role
# and an instrument if role is performer
create table person_role (
    id                      integer         not null auto_increment,
    person                  integer,
    ensemble                integer,
    instrument              integer,
    role                    integer,
    primary key(id)
);
alter table person_role add index (person);
alter table person_role add index (ensemble);

create table license (
    id                      integer         not null auto_increment,
    name                    varchar(190)    not null,
    unique(name),
    primary key(id)
);

create table composition (
    id                      integer         not null auto_increment,
    long_title              varchar(255)    not null,
        # includes key, opus and/or composer
        # e.g. Symphony No. 11 in D major, K.84/73q (Mozart, Wolfgang Amadeus)
        # movement: mvt <parent_id> <ordinal>
        # arrangement: arr <parent_id> <ordinal>
    title                   text,
        # e.g. Symphony No. 11
        # movement: title of movement, if any
        # arrangement: null
    alternative_title       text,
    opus_catalogue          text,
    composed                integer,
    published               integer,
    performed               integer,
    dedication              text,
    tempo_markings          text,
    metronome_markings      text,
    _keys                    text,
        # 'key' is a reserved word in SQL
    time_signatures         text,
    comp_types              json,
    creators                json,
    parent                  integer,
    children                json,
    arrangement_of          integer,
    languages               json,
    instrument_combos       json,
    ensemble_type           integer,
    period                  integer,
    average_duration        text,
    n_movements             integer,
    nbars                   integer,
    unique(long_title),
    primary key(id)
);
alter table composition add fulltext cindex (title);
alter table composition add index wlang( (cast(languages->'$' as unsigned array)) );
alter table composition add index wwt( (cast(comp_types->'$' as unsigned array)) );
alter table composition add index wic( (cast(instrument_combos->'$' as unsigned array)) );
alter table composition add index comp_crea( (cast(creators->'$' as unsigned array)) );
alter table composition add index wperiod (period);
alter table composition add index comp_arr(arrangement_of);
alter table composition add index comp_parent(parent);

-- a scan of a score, or several of same edition
-- a set of parts counts as one score

create table score (
    id                      integer         not null auto_increment,
    compositions            json,           -- may be collection of comps
    file_names              json,
    file_descs              json,           -- e.g. 'Cellos and Basses'
    publisher               integer,        -- organization
    license                 integer,
    languages               json,
    published               integer,
    edition_number          text,
    page_count              integer,
    image_type              text,           -- e.g. typeset, normal scan
    is_parts                tinyint,
    is_selections           tinyint,
    is_vocal                tinyint,
    primary key(id)
);
alter table score add index scomp( (cast(compositions->'$' as unsigned array)) );

create table venue (
    id                      integer         not null auto_increment,
    name                    varchar(255),
    location                integer,
    address                 text,
    capacity                integer,
    started                 integer,
    ended                   integer,
    primary key(id)
);

-- a past or present performance, possibly recorded
-- may consists of several parts (e.g. movements)
-- You can rate a performance but not its parts.

create table performance (
    id                      integer         not null auto_increment,
    composition             integer,
    performers              json,
        -- person_roles
    tentative               tinyint,
        -- part of a concert being edited; can delete if old
    -- the following relevant if recording
    file_names              json,
        -- depends on release type; e.g. IMSLP filenames
    file_descs              json,
        -- parallel array of descriptions, e.g. mvt titles
    is_synthesized          tinyint,
    section                 text,
        -- 'Complete' or 'Selections' or name of section/mvt
    instrumentation         text,
        -- null if native instrumentation
    primary key(id)
);
alter table performance add index perf_comp(composition);

create table concert (
    id                      integer         not null auto_increment,
    _when                   integer,
    venue                   integer,
    audience_size           integer,
    organization            integer,
        -- sponsor or organizer
    program                 json,
        -- performances (in order)
    primary key(id)
);

-- a way that you can hear recordings:
-- a CD, IMSLP file set, YouTube video

create table _release (
    id                      integer         not null auto_increment,
    title                   text,           -- e.g. CD title
    performances            json,
    release_date            integer,
    catalog_num             text,
    url                     text,
    license                 integer,
    publisher               integer,
        # organization
    primary key(id)
);

-- things you can rate:
-- composition
-- score
-- performance
-- person-role

create table rating (
    id                      integer         not null auto_increment,
    created                 integer,
    user                    integer,
    target                  integer,
    type                    integer,        -- see cmi_db.inc
    quality                 integer,
        -- for scores, quality of edition
    attr2                   integer,
        -- for compositions, difficulty
        -- for recorded performance, sound quality
        -- for scores, quality of scan
    review                  text,
    primary key(id)
);
alter table rating add unique(user, target, type);

create table rating_useful (
    id                      integer         not null auto_increment,
    created                 integer,
    user                    integer,
    rating                  integer,
    useful                  tinyint,        -- 1 = useful, 0 = not useful
    primary key(id)
);
alter table rating_useful add unique(user, rating);
