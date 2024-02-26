# CMI DB schema
#
drop database cmi;

# an indexed or unique text field must be varchar
# fulltext index can cover at most 256 char

create database cmi character set utf8mb4 collate utf8mb4_unicode_ci;

use cmi;

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

create table race (
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
    born                    date,
    birth_place             integer,
    died                    date,
    death_place             text,
    locations               JSON,
    periods                 JSON,
    sex                     integer,
    race                    integer,
    ethnicity               integer,
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
    started                 date,
    ended                   date,
    type                    integer,
    location                integer,
    members                 json,
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
    started                 date,
    ended                   date,
    location                integer,
    url                     varchar(255),
    primary key(id)
);


create table instrument (
    id                      integer         not null auto_increment,
    imslp_code              varchar(190)    not null,
    name                    varchar(190)    not null,
    ncombos                 integer         not null default 0,
    primary key(id)
);

create table instrument_combo (
    id                      integer         not null auto_increment,
    instruments             json,
        # a structure consisting of 2 same-size lists:
        # count => array of counts (always >0)
        # ids => array of instrument ids
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
    title                   varchar(190),
        # e.g. Symphony No. 11
        # movement: title of movement, if any
        # arrangement: null
    alternative_title       text,
    opus_catalogue          text,
    composed                date,
    published               date,
    performed               date,
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
    unique(long_title),
    primary key(id)
);
alter table composition add fulltext cindex (long_title);
alter table composition add index wlang( (cast(languages->'$' as unsigned array)) );
alter table composition add index wwt( (cast(comp_types->'$' as unsigned array)) );
alter table composition add index wic( (cast(instrument_combos->'$' as unsigned array)) );
alter table composition add index wperiod (period);

create table score (
    id                      integer         not null auto_increment,
    compositions            json,
    parent                  integer,
    instrument_combos       json,
    publisher               integer,
    license                 integer,
    languages               json,
    published               date,
    edition_number          text,
    page_count              integer,
    primary key(id)
);
alter table score add index sic( (cast(instrument_combos->'$' as unsigned array)) );
alter table score add index scomp( (cast(compositions->'$' as unsigned array)) );

create table venue (
    id                      integer         not null auto_increment,
    name                    varchar(255),
    location                integer,
    address                 text,
    capacity                integer,
    started                 date,
    ended                   date,
    primary key(id)
);

create table performance (
    id                      integer         not null auto_increment,
    composition             integer,
    performers              json,
    primary key(id)
);

create table concert (
    id                      integer         not null auto_increment,
    _when                   datetime,
    venue                   integer,
    audience_size           integer,
    organization            integer,
    program                 json,
    performers              json,
    primary key(id)
);

create table recording (
    id                      integer         not null auto_increment,
    performance             integer,
    concert                 integer,
    copyright_id            integer         not null default 0,
    date_submitted          text,
    ensemble_id             integer         not null default 0,
    instrument_combo_id     integer         not null default 0,
        # populated for recordings of arrangements
    misc_notes              text,
    performer_categories    text,
    performers              text,
    performer_role_ids      JSON,
    publisher_information   text,
    thumb_filename          text,
    uploader                text,
    primary key(id)
);

create table _release (
    id                      integer         not null auto_increment,
    title                   text,
    release_date            date,
    catalog_num             text,
    url                     text,
    license                 integer,
    recordings              json,
    publisher               integer,
    primary key(id)
);
