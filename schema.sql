# composer, librettist, etc.
create table person (
    id                      integer         not null auto_increment,
    first_name              text            not null,
    last_name               text            not null,
    primary key(id)
);

create table language (
    id                      integer         not null auto_increment,
    name                    text            not null,
    primary key(id)
);

create table style (
    id                      integer         not null auto_increment,
    name                    text            not null,
    primary key(id)
);

create table external_link (
    id                      integer         not null auto_increment,
    type                    integer         not null,
        # wikipedia, amazon, ...
    name                    text            not null,
    primary key(id)
);

create table composition (
    id                      integer         not null auto_increment,
    redirect_id             integer         not null,
    composer_id             integer         not null,
    title                   text            not null,
    alternative_title       text            not null,
    opus                    text            not null,
    _key                    text            not null,
        # 'key' is a reserved word
    nmovements              integer         not null,
    movement_names          text            not null,
    incipit                 text            not null,
    dedication              text            not null,
    composition_date        text            not null,
    first_performance       text            not null,
    publication_date        text            not null,
    librettist_id           integer         not null,
    language_id             integer         not null,
    style_id                integer         not null,
    average_dur_min         integer         not null,
    instrumentation         text            not null,
    primary key(id)
);

create table composition_link_assoc (
    composition_id          integer         not null,
    external_link_id        integer         not null
);

create table imslp_user (
    id                      integer         not null auto_increment,
    name                    text            not null,
    primary key(id)
);

create table license (
    id                      integer         not null auto_increment,
    name                    text            not null,
    primary key(id)
);

# a group of 1 or more files (e.g. parts)
#
create table score_file_set (
    id                      integer         not null auto_increment,
    composition_id          integer         not null,
    hier1                   text            not null,
    hier2                   text            not null,
    hier3                   text            not null,
    arrangement_name        text            not null,
    editor_id               integer         not null,
    image_type              text            not null,
        # Normal Scan, Typeset, Manuscript Scan
    publisher_info          text            not null,
    license_id              integer         not null,
    misc_notes              text            not null,
    amazon_info             text            not null,
    arranger                text            not null,
    translator              text            not null,
    sm_plus                 text            not null,
    reprint                 text            not null,
    engraver                text            not null,
    file_tags               text            not null,
    primary key(id)
);

# a single file
create table score_file (
    id                      integer         not null auto_increment,
    score_file_set_id       integer         not null,
    name                    text            not null,
    thumb_filename          text            not null,
    sample_filename         text            not null,
    description             text            not null,
    scanner                 text            not null,
    uploader_id             integer         not null,
    date_submitted          text            not null,
    page_count              text            not null,
    primary key(id)
);

create table audio_file_set (
    id                      integer         not null auto_increment,
    composition_id          integer         not null,
    hier1                   text            not null,
    hier2                   text            not null,
    hier3                   text            not null,
    name                    text            not null,
    performers              text            not null,
    uploader_id             integer         not null,
    date_submitted          text            not null,
    publisher_info          text            not null,
    license_id              integer         not null,
    misc_notes              text            not null,
    primary key(id)
);

create table audio_file (
    id                      integer         not null auto_increment,
    audio_file_set_id       integer         not null,
    name                    text            not null,
    description             text            not null,
    primary key(id)
);
