# IMSLP DB schema
#
# To avoid confusion, field names are same of mediawiki template param names;
# in some cases these are not ideal.

drop database imslp;

create database imslp character set utf8mb4 collate utf8mb4_unicode_ci;

use imslp;

# person: composer, performer, librettist, etc.
# May want a way to allow 2 people with same name
#
create table person (
    id                      integer         not null auto_increment,
    first_name              varchar(90)     not null,
    last_name               varchar(90)     not null,
    alternate_names         text,
    birth_date              text,
    birth_place             text,
    born_year               integer         not null default 0,
    born_month              integer         not null default 0,
    born_day                integer         not null default 0,
    death_date              text,
    death_place             text,
    died_year               integer         not null default 0,
    died_month              integer         not null default 0,
    died_day                integer         not null default 0,
    flourished              text,
    is_composer             tinyint         not null default 0,
    is_performer            tinyint         not null default 0,
    nationality_ids         JSON,
    period_ids              JSON,
    picture                 text,
    picture_caption         text,
    sex                     text,
    signature               text,
    unique(first_name, last_name),
    primary key(id)
);
alter table person add fulltext index (first_name, last_name);
alter table person add index inat( (cast(nationality_ids->'$' as unsigned array)) );
alter table person add index iper( (cast(period_ids->'$' as unsigned array)) );

create table nationality (
    id                      integer         not null auto_increment,
    name                    varchar(190)    not null,
    unique(name),
    primary key(id)
);

# classical, romantic etc.
#
create table period (
    id                      integer         not null auto_increment,
    name                    varchar(190)    not null,
    unique(name),
    primary key(id)
);

# actually license
#
create table copyright (
    id                      integer         not null auto_increment,
    name                    varchar(190)    not null,
    unique(name),
    primary key(id)
);

create table work (
    id                      integer         not null auto_increment,
    redirect_id             integer         not null default 0,
    title                   varchar(190)    not null,
        # from JSON key; includes title, opus, composer
    composer_id             integer         not null,
    alternative_title       text,
    attrib                  text,
    authorities             text,
    average_duration        text,
    comments                text,
    dedication              text,
    discography             text,
    external_links          text,
    extra_information       text,
    first_performance       text,
    incipit                 text,
    instrdetail             text,
    instrumentation         text,
    instrument_combo_ids    JSON,
    _key                    text,
        # 'key' is a reserved word in SQL
    language                text,
    language_ids            JSON,
    librettist              text,
    manuscript_sources      text,
    movements_header        text,
    ncrecordings            text,
    nonpd_eu                tinyint         not null default 0,
    nonpd_us                tinyint         not null default 0,
    number_of_movements_sections     text,
    opus_catalogue          text,
    period_id               integer         not null default 0,
    related_works           text,
    searchkey               text,
    searchkey_amarec        text,
    searchkey_scores        text,
    tags                    text,
    year_date_of_composition text,
    year_of_composition     integer         not null default 0,
    year_of_first_publication text,
    work_title              text,
    work_type_ids           JSON,
    unique(title),
    primary key(id)
);
alter table work add fulltext cindex (title, instrumentation);

create table publisher (
    id                      integer         not null auto_increment,
    name                    text            not null,
    imprint                 text            not null,
    location                text            not null,
    primary key(id)
);

# e.g. Viola and Piano, etc.
create table arrangement_target (
    id                      integer         not null auto_increment,
    instruments             varchar(190)    not null,
    unique(instruments),
    primary key(id)
);


# a group of 1 or more score files (e.g. parts)
#
create table score_file_set (
    id                      integer         not null auto_increment,
    work_id                 integer         not null,
    hier1                   text,
        # none, Parts, Arrangements and Transcriptions
    hier2                   text,
        # none, Complete, Selections, the name of a movement
    hier3                   text,
        # for Piano, for Piano (name of arranger)
    amazon                  text,
    arranger                text,
    arrangement_target_id   integer         not null default 0,
    copyright_id            integer         not null default 0,
    date_submitted          text,
    editor                  text,
    engraver                text,
    file_tags               text,
    image_type              text,
        # Normal Scan, Typeset, Manuscript Scan
    instrument_combo_ids    JSON,
        # if different from work (e.g. arrangement)
    misc_notes              text,
    publisher_information   text,
        # the following populated if {{P was used
    publisher_id            integer         not null default 0,
    pub_date                text,
    pub_edition_number      text,
    pub_extra               text,
    pub_plate_number        text,
    pub_year                integer         not null default 0,

    reprint                 text,
    sample_filename         text,
    scanner                 text,
    sm_plus                 text,
    thumb_filename          text,
    translator              text,
    uploader                text,
    primary key(id)
);

# a single score file
create table score_file (
    id                      integer         not null auto_increment,
    score_file_set_id       integer         not null default 0,
    date_submitted          text,
    file_name               text,
    file_description        text,
    page_count              text,
    sample_filename         text,
    scanner                 text,
    thumb_filename          text,
    uploader                text,
    primary key(id)
);

create table audio_file_set (
    id                      integer         not null auto_increment,
    work_id                 integer         not null default 0,
    hier1                   text,
        # none, Synthesized/MIDI
    hier2                   text,
        # none, Complete, Selections, name of a mvt
    hier3                   text,
        # for Piano, etc.
    copyright_id            integer         not null default 0,
    date_submitted          text,
    ensemble_id             integer         not null default 0,
    misc_notes              text,
    performer_categories    text,
    performers              text,
    performer_role_ids      JSON,
    publisher_information   text,
    thumb_filename          text,
    uploader                text,
    primary key(id)
);
alter table audio_file_set add index ipr( (cast(performer_role_ids->'$' as unsigned array)) );

create table audio_file (
    id                      integer         not null auto_increment,
    audio_file_set_id       integer         not null default 0,
    date_submitted          text,
    file_name               text,
    file_description        text,
    primary key(id)
);

# the combination of a person and a musical role
# (instrument name(s) or conductor)
create table performer_role (
    id                      integer         not null auto_increment,
    person_id               integer         not null,
    role                    varchar(255)    not null,
    primary key(id)
);
alter table performer_role add index (person_id);

create table ensemble (
    id                      integer         not null auto_increment,
    name                    varchar(190)    not null,
    alternate_names         varchar(4096)   not null default '',
    born_year               integer         not null default 0,
    died_year               integer         not null default 0,
    nationality_id          integer         not null default 0,
        # could make this an association table
    period_id               integer         not null default 0,
        # could make this an association table
    picture                 varchar(255)    not null default '',
    type                    varchar(255)    not null default '',
        # orchestra, piano trio, etc.
        # could make this a separate table
    unique(name),
    primary key(id)
);

create table work_type (
    id                      integer         not null auto_increment,
    code                    varchar(190)    not null,
    name                    varchar(190)    not null,
    descendant_ids          json,
    nworks                  integer         not null default 0,
    unique(name),
    unique(code),
    primary key(id)
);

create table instrument (
    id                      integer         not null auto_increment,
    code                    varchar(190)    not null,
    name                    varchar(190)    not null,
    primary key(id)
);

create table instrument_combo (
    id                      integer         not null auto_increment,
    instruments             json,               # array of [count, id]
    md5                     varchar(64),        # hash of instruments
    primary key(id)
);

create table language (
    id                      integer         not null auto_increment,
    code                    varchar(190)    not null,
    name                    varchar(190)    not null,
    primary key(id)
);
