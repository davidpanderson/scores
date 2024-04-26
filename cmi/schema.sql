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
    type                    integer         not null default 0,
    parent                  integer         not null default 0,
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
    born                    integer         not null default 0,
    birth_place             integer         not null default 0,
    died                    integer         not null default 0,
    death_place             text,
    locations               JSON,
    periods                 JSON,
    sex                     integer         not null default 0,
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
    started                 integer         not null default 0,
    ended                   integer         not null default 0,
    type                    integer         not null default 0,
    location                integer         not null default 0,
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
    name                    varchar(255)    not null,
    type                    integer         not null default 0,
    started                 integer         not null default 0,
    ended                   integer         not null default 0,
    location                text,           -- ideally should be an ID
    url                     varchar(255),
    primary key(id),
    unique(name)
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
    person                  integer         not null default 0,
    role                    integer         not null default 0,
    instrument              integer         not null default 0,
        -- if role is performer
    nratings1               integer         not null default 0,
    rating_sum1             integer         not null default 0,
    nratings2               integer         not null default 0,
    rating_sum2             integer         not null default 0,
    nreviews                integer         not null default 0,
    unique(person, role, instrument),
    primary key(id)
);

create table license (
    id                      integer         not null auto_increment,
    name                    varchar(190)    not null,
    unique(name),
    primary key(id)
);

create table composition (
    id                      integer         not null auto_increment,
    long_title              varchar(255)    not null,
        # title, opus (composer)
        # e.g. Symphony No. 11 in D major, K.84/73q (Mozart, Wolfgang Amadeus)
        # null for sections and arrangements
        # the main purpose is to avoid duplicate population of IMSLP comps
    title                   text,
        # e.g. Symphony No. 11
        # section: title of movement, if any
        # arrangement: null
    alternative_title       text,
    opus_catalogue          text,
    composed                integer         not null default 0,
    published               integer         not null default 0,
    performed               integer         not null default 0,
    dedication              text,
    tempo_markings          text,
    metronome_markings      text,           -- e.g. quarter=120
    _keys                   text,
        # 'key' is a reserved word in SQL
    time_signatures         text,
    comp_types              json,
    creators                json,
        -- person_role IDs
    parent                  integer         not null default 0,
    children                json,
    arrangement_of          integer         not null default 0,
    language                integer,
    instrument_combos       json,
    ensemble_type           integer         not null default 0,
    period                  integer         not null default 0,
    avg_duration_sec        integer         not null default 0,
    n_movements             integer         not null default 0,
    n_bars                  integer         not null default 0,
    nratings1               integer         not null default 0,
    rating_sum1             integer         not null default 0,
    nratings2               integer         not null default 0,
    rating_sum2             integer         not null default 0,
    nreviews                integer         not null default 0,
    primary key(id)
);
alter table composition add index comp_lt(long_title);
alter table composition add fulltext cindex (title);
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
    creators                json,           -- editor, translator
    files                   json,
        -- a list of objects, each with
        -- desc (e.g. 'Cellos and Basses')
        -- name
        -- pages
    section                 text,
        -- if not whole composition, the name of the section
    publisher               integer         not null default 0,
        -- organization
    license                 integer         not null default 0,
    languages               json,           -- language of editorial notes
    publish_date            integer         not null default 0,
    edition_number          text,
    image_type              text,           -- e.g. typeset, normal scan
    is_parts                tinyint         not null default 0,
        -- separate file per part
    is_selections           tinyint         not null default 0,
        -- not the whole composition
    is_vocal                tinyint         not null default 0,
        -- only the vocal parts
    nratings1               integer         not null default 0,
    rating_sum1             integer         not null default 0,
    nratings2               integer         not null default 0,
    rating_sum2             integer         not null default 0,
    nreviews                integer         not null default 0,
    primary key(id)
);
alter table score add index scomp( (cast(compositions->'$' as unsigned array)) );
alter table score add index score_crea( (cast(creators->'$' as unsigned array)) );
alter table score add index score_pub(publisher);

create table venue (
    id                      integer         not null auto_increment,
    name                    varchar(255),
    location                integer         not null default 0,
    address                 text,
    capacity                integer         not null default 0,
    started                 integer         not null default 0,
    ended                   integer         not null default 0,
    primary key(id)
);

-- a past or present performance, possibly recorded
-- may consists of several parts (e.g. movements)
-- You can rate a performance but not its parts.

create table performance (
    id                      integer         not null auto_increment,
    composition             integer         not null default 0,
    performers              json,
        -- person_roles
    ensemble                integer         not null default 0,
    is_recording            tinyint         not null default 0,

    -- the following relevant if recording
    files                   json,
        -- a list of objects, each with:
        -- desc (e.g. mvt title)
        -- name (depends on release type; e.g. IMSLP filenames)
        -- (could have type, encoding params, size and duration too)
    is_synthesized          tinyint         not null default 0,
    section                 text,
        -- 'Complete' or 'Selections' or name of section/mvt
    instrumentation         text,
        -- empty if native instrumentation
    license                 integer         not null default 0,
    publisher               integer         not null default 0,
        -- organization ID
    nratings1               integer         not null default 0,
    rating_sum1             integer         not null default 0,
    nratings2               integer         not null default 0,
    rating_sum2             integer         not null default 0,
    nreviews                integer         not null default 0,
    primary key(id)
);
alter table performance add index perf_comp(composition);

create table concert (
    id                      integer         not null auto_increment,
    _when                   integer         not null default 0,
    venue                   integer         not null default 0,
    audience_size           integer         not null default 0,
    organization            integer         not null default 0,
        -- sponsor or organizer
    program                 json,
        -- performances (in order)
    primary key(id)
);

-- a collection of performances
-- a CD or something analogous

create table _release (
    id                      integer         not null auto_increment,
    title                   text,           -- e.g. CD title
    performances            json,
    release_date            integer         not null default 0,
    catalog_num             text,
    url                     text,
    license                 integer         not null default 0,
    publisher               integer         not null default 0,
        # organization
    primary key(id)
);

-- things you can rate:
-- composition
-- score
-- performance
-- person_role

create table rating (
    id                      integer         not null auto_increment,
    created                 integer         not null default 0,
    user                    integer         not null default 0,
    target                  integer         not null default 0,
        -- ID of item being rated
    type                    integer         not null default 0,
        -- type of item being rated; see values in cmi_db.inc
    attr1                   integer,
        -- how much you like the composition or performance
        -- for scores, quality of edition
        -- null if no rating
    attr2                   integer,
        -- for compositions, difficulty
        -- for recorded performance, sound quality
        -- for scores, quality of scan
        -- null if no rating
    review                  text,
    primary key(id)
);
alter table rating add unique(user, target, type);

create table review_useful (
    id                      integer         not null auto_increment,
    created                 integer         not null default 0,
    user                    integer         not null default 0,
    rating                  integer         not null default 0,
    useful                  tinyint         not null default 0,
        -- 1 = useful, 2 = not useful, 3 = report
    primary key(id)
);
alter table review_useful add unique(user, rating);
