# IMSLP DB schema
#
# To avoid confusion, field names are same of mediawiki template param names;
# in some cases these are not ideal.

# person: composer, performer, librettist, etc.
# May want a way to allow 2 people with same name
#
create table person (
    id                      integer         not null auto_increment,
    first_name              varchar(255)    not null,
    last_name               varchar(255)    not null,
    alternate_names         varchar(255)    not null,
    birth_date              varchar(255)    not null,
    birth_place             varchar(255)    not null,
    born_year               integer         not null,
    born_month              integer         not null,
    born_day                integer         not null,
    death_date              varchar(255)    not null,
    death_place             varchar(255)    not null,
    died_year               integer         not null,
    died_month              integer         not null,
    died_day                integer         not null,
    flourished              varchar(255)    not null,
    is_composer             tinyint         not null,
    is_performer            tinyint         not null,
    picture                 varchar(255)    not null,
    picture_caption         varchar(255)    not null,
    sex                     varchar(255)    not null,
    signature               varchar(255)    not null,
    unique(first_name, last_name),
    primary key(id)
);
alter table person add fulltext index (first_name, last_name);

create table nationality (
    id                      integer         not null auto_increment,
    name                    varchar(255)    not null,
    unique(name),
    primary key(id)
);

# classical, romantic etc.
#
create table period (
    id                      integer         not null auto_increment,
    name                    varchar(255)    not null,
    unique(name),
    primary key(id)
);

create table person_nationality (
    person_id               integer         not null,
    nationality_id          integer         not null
);

create table person_period (
    person_id               integer         not null,
    period_id               integer         not null
);

# actually license
#
create table copyright (
    id                      integer         not null auto_increment,
    name                    varchar(255)    not null,
    unique(name),
    primary key(id)
);

create table work (
    id                      integer         not null auto_increment,
    redirect_id             integer         not null,
    title                   varchar(255)    not null,
        # from JSON key; includes title, opus, composer
    composer_id             integer         not null,
    alternative_title       text            not null,
    attrib                  text            not null,
    authorities             text            not null,
    average_duration        text            not null,
    comments                text            not null,
    dedication              text            not null,
    discography             text            not null,
    external_links          text            not null,
    extra_information       text            not null,
    first_performance       text            not null,
    incipit                 text            not null,
    instrdetail             text            not null,
    instrumentation         text            not null,
    _key                    text            not null,
        # 'key' is a reserved word in SQL
    language                text            not null,
    librettist              text            not null,
    manuscript_sources      text            not null,
    movements_header        text            not null,
    ncrecordings            text            not null,
    nonpd_eu                tinyint         not null,
    nonpd_us                tinyint         not null,
    number_of_movements_sections     text         not null,
    opus_catalogue          text            not null,
    period_id               integer         not null,
    related_works           text            not null,
    searchkey               text            not null,
    searchkey_amarec        text            not null,
    searchkey_scores        text            not null,
    tags                    text            not null,
    year_date_of_composition text           not null,
    year_of_composition     integer         not null,
    year_of_first_publication text          not null,
    work_title              text            not null,
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
    instruments             varchar(255)    not null,
    unique(instruments),
    primary key(id)
);


# a group of 1 or more score files (e.g. parts)
#
create table score_file_set (
    id                      integer         not null auto_increment,
    work_id                 integer         not null,
    hier1                   text            not null,
        # none, Parts, Arrangements and Transcriptions
    hier2                   text            not null,
        # none, Complete, Selections, the name of a movement
    hier3                   text            not null,
        # for Piano, for Piano (name of arranger)
    amazon                  text            not null,
    arranger                text            not null,
    arrangement_target_id   integer         not null,
    copyright_id            integer         not null,
    date_submitted          text            not null,
    editor                  text            not null,
    engraver                text            not null,
    file_tags               text            not null,
    image_type              text            not null,
        # Normal Scan, Typeset, Manuscript Scan
    misc_notes              text            not null,
    publisher_information   text            not null,
        # the following populated if {{P was used
    publisher_id            integer         not null,
    pub_date                text            not null,
    pub_edition_number      text            not null,
    pub_extra               text            not null,
    pub_plate_number        text            not null,
    pub_year                integer         not null,

    reprint                 text            not null,
    sample_filename         text            not null,
    scanner                 text            not null,
    sm_plus                 text            not null,
    thumb_filename          text            not null,
    translator              text            not null,
    uploader                text            not null,
    primary key(id)
);

# a single score file
create table score_file (
    id                      integer         not null auto_increment,
    score_file_set_id       integer         not null,
    date_submitted          text            not null,
    file_name               text            not null,
    file_description        text            not null,
    page_count              text            not null,
    sample_filename         text            not null,
    scanner                 text            not null,
    thumb_filename          text            not null,
    uploader                text            not null,
    primary key(id)
);

create table audio_file_set (
    id                      integer         not null auto_increment,
    work_id                 integer         not null,
    hier1                   text            not null,
        # none, Synthesized/MIDI
    hier2                   text            not null,
        # none, Complete, Selections, name of a mvt
    hier3                   text            not null,
        # for Piano, etc.
    copyright_id            integer         not null,
    date_submitted          text            not null,
    ensemble_id             integer         not null,
    misc_notes              text            not null,
    performer_categories    text            not null,
    performers              text            not null,
    publisher_information   text            not null,
    thumb_filename          text            not null,
    uploader                text            not null,
    primary key(id)
);

create table audio_file (
    id                      integer         not null auto_increment,
    audio_file_set_id       integer         not null,
    date_submitted          text            not null,
    file_name               text            not null,
    file_description        text            not null,
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

create table ensemble (
    id                      integer         not null auto_increment,
    name                    varchar(255)    not null,
    alternate_names         varchar(255)    not null,
    born_year               integer         not null,
    died_year               integer         not null,
    nationality_id          integer         not null,
        # could make this an association table
    period_id               integer         not null,
        # could make this an association table
    picture                 varchar(255)    not null,
    type                    varchar(255)    not null,
        # orchestra, piano trio, etc.
        # could make this a separate table
    unique(name),
    primary key(id)
);

create table audio_performer (
    audio_file_set_id       integer         not null,
    performer_role_id       integer         not null
);
