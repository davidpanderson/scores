# IMSLP DB schema
#
# To avoid confusion, field names are same of mediawiki template param names;
# in some cases these are not ideal.

# composer, librettist, etc.

create table person (
    id                      integer         not null auto_increment,
    first_name              text            not null,
    last_name               text            not null,
    # should have birth/death dates, nationality, etc.
    primary key(id)
);

create table language (
    id                      integer         not null auto_increment,
    name                    text            not null,
    primary key(id)
);

# classical, romantic etc.
#
create table style (
    id                      integer         not null auto_increment,
    name                    text            not null,
    primary key(id)
);

# actually license
#
create table copyright (
    id                      integer         not null auto_increment,
    name                    text            not null,
    primary key(id)
);

create table composition (
    id                      integer         not null auto_increment,
    redirect_id             integer         not null,
    title                   text            not null,
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
    number_of_movements_sections     text         not null,
    opus_catalogue          text            not null,
    piece_style_id          integer         not null,
    related_works           text            not null,
    searchkey               text            not null,
    searchkey_amarec        text            not null,
    searchkey_scores        text            not null,
    tags                    text            not null,
    year_date_of_composition text           not null,
    year_of_composition     integer         not null,
    year_of_first_publication text          not null,
    work_title              text            not null,
    primary key(id)
);

# a group of 1 or more score files (e.g. parts)
#
create table score_file_set (
    id                      integer         not null auto_increment,
    composition_id          integer         not null,
    hier1                   text            not null,
    hier2                   text            not null,
    hier3                   text            not null,
    amazon                  text            not null,
    arranger                text            not null,
    copyright_id            integer         not null,
    date_submitted          text            not null,
    editor                  text            not null,
    engraver                text            not null,
    file_tags               text            not null,
    image_type              text            not null,
        # Normal Scan, Typeset, Manuscript Scan
    misc_notes              text            not null,
    publisher_information   text            not null,
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
    composition_id          integer         not null,
    hier1                   text            not null,
    hier2                   text            not null,
    hier3                   text            not null,
    copyright_id            integer         not null,
    date_submitted          text            not null,
    misc_notes              text            not null,
    performer_categories    text            not null,
    performers              text            not null,
    publisher_information   text            not null,
    uploader                text            not null,
    primary key(id)
);

create table audio_file (
    id                      integer         not null auto_increment,
    audio_file_set_id       integer         not null,
    date_submitted          text            not null,
    file_name               text            not null,
    file_description             text            not null,
    primary key(id)
);

alter table composition add fulltext cindex (title, instrumentation);
alter table person add fulltext pindex (first_name, last_name);
