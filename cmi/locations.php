#! /usr/bin/env php

<?php

// - populate the location table
// - write location.ser

require_once('cmi_db.inc');
require_once('ser.inc');
require_once('ser_init.inc');

$cont_id=0;
$subcont_id=0;
$country_id=0;

function continent($name, $adj) {
    global $cont_id, $subcont_id, $country_id;
    $cont_id = DB_location::insert(
        sprintf(
            "(name, adjective, type) values ('%s', '%s', %d)",
            DB::escape($name),
            DB::escape($adj),
            location_type_name_to_id('continent')
        )
    );
    $subcont_id = 0;
    $country_id = 0;
}
function subcontinent($name, $adj) {
    global $cont_id, $subcont_id, $country_id;
    $subcont_id = DB_location::insert(
        sprintf(
            "(name, adjective, type, parent, ancestors) values ('%s', '%s', %d, %d, '%s')",
            DB::escape($name),
            DB::escape($adj),
            location_type_name_to_id('subcontinent'),
            $cont_id,
            json_encode([$cont_id], JSON_NUMERIC_CHECK)
        )
    );
    $country_id = 0;
}
function country($name, $adj, $name_native='', $adj_native='') {
    global $cont_id, $subcont_id, $country_id;
    $anc = $subcont_id?[$cont_id, $subcont_id]:[$cont_id];
    $country_id = DB_location::insert(
        sprintf(
            "(name, adjective, name_native, adjective_native, type, parent, ancestors) values ('%s', '%s', '%s', '%s', %d, %d, '%s')",
            DB::escape($name),
            DB::escape($adj),
            DB::escape($name_native),
            DB::escape($adj_native),
            location_type_name_to_id('country'),
            $subcont_id?$subcont_id:$cont_id,
            json_encode($anc, JSON_NUMERIC_CHECK)
        )
    );
}
function state($name, $adj, $name_native='', $adj_native='') {
    global $cont_id, $subcont_id, $country_id;
    $anc = $subcont_id?[$cont_id, $subcont_id, $country_id]:[$cont_id, $country_id];
    DB_location::insert(
        sprintf(
            "(name, adjective, name_native, adjective_native, type, parent, ancestors) values ('%s', '%s', '%s', '%s', %d, %d, '%s')",
            DB::escape($name),
            DB::escape($adj),
            DB::escape($name_native),
            DB::escape($adj_native),
            location_type_name_to_id('province/state'),
            $country_id,
            json_encode($anc, JSON_NUMERIC_CHECK)
        )
    );
}

continent('Africa', 'African');
country('Democratic Republic of the Congo', 'Congolese');
country('Nigeria', 'Nigerian');
country('South Africa', 'South African');
subcontinent('North Africa', 'North African');
country('Algeria', 'Algerian');
country('Egypt', 'Egyptian');

continent('Asia', 'Asian');
country('Azerbaijan', 'Azerbaijani');
country('Bashkortostan', 'Bashkir');
country('Kazakhstan', 'Kazakhstani');
country('Tajikistan', 'Tajikistani');
country('Uzbekistan', 'Uzbekistani');

subcontinent('East Asia', 'East Asian');
country('China', 'Chinese');
country('Hong Kong', 'Hong Konger');
country('Japan', 'Japanese', '日本');
country('Korea', 'Korean');
country('North Korean', 'North Korean');
country('South Korea', 'South Korean');
country('Taiwan', 'Taiwanese');
subcontinent('Middle East', 'Middle Easterner');
country('Iran', 'Iranian');
country('Iraq', 'Iraqi');
country('Israel', 'Israeli');
country('Lebanon', 'Lebanese');
country('Palestine', 'Palestinian');
country('Syria', 'Syrian');
subcontinent('South Asia', 'South Asian');
country('India', 'Indian');
subcontinent('Southeast Asia', 'Southeast Asian');
country('Malaysia','Malaysian');
country('Philippines', 'Filipino');
country('Singapore', 'Singaporean');
country('Thailand', 'Thai');

continent('Europe', 'European');
country('Albania', 'Albanian');
country('Armenia', 'Armenian');
country('Austria', 'Austrian');
country('Basque Country', 'Basque');
    // province?
country('Belarus', 'Belarusian');
country('Belgium', 'Belgian');
country('Bohemia', 'Bohemian');
country('Bosnia', 'Bosnian');
country('Britain', 'British');
country('Bulgaria', 'Bulgarian');
country('Byzantine Empire', 'Byzantine');
country('Catalonia', 'Catalan');
country('Corsica', 'Corsican');
country('Croatia', 'Croatian');
country('Cyprus', 'Cypriot');
country('Czech Republic', 'Czech');
country('Denmark', 'Danish');
country('Holland', 'Dutch');
country('England', 'English');
country('Estonia', 'Estonian');
country('Finland', 'Finnish');
country('Flanders', 'Flemish');
country('France', 'French');
country('Galicia', 'Galician');
country('Gascony', 'Gascon');
country('Georgia', 'Georgian');
country('Germany', 'German');
country('Greece', 'Greek');
country('Hungary', 'Hungarian');
country('Iceland', 'Icelandic');
country('Ireland', 'Irish');
country('Italy', 'Italian', 'Italia', 'Italiano');
country('Latvia', 'Latvian');
country('Liechtenstein', 'Liechtensteinian');
country('Lithuania', 'Lithuanian');
country('Luxembourg', 'Luxembourgish');
country('Macedonia', 'Macedonian');
country('Malta', 'Maltese');
country('Moldova', 'Moldovan');
country('Monaco', 'Monegasque');
country('Naples', 'Neapolitan');
    // city? province? country?
country('Norway', 'Norwegian');
country('Poland', 'Polish');
country('Portugal', 'Portuguese');
country('Prussia', 'Prussian');
country('Rome', 'Roman');
country('Romania', 'Romanian');
country('Russia', 'Russian');
country('San Marino', 'Sammarinese');
country('Scotland', 'Scottish');
country('Serbia', 'Serbian');
country('Slovakia', 'Slovakian');
country('Slovenia', 'Slovenian');
country('Soviet Union', 'Soviet');
country('Spain', 'Spanish');
country('Sweden', 'Swedish');
country('Switzerland', 'Swiss');
country('Transnistria', 'Transnistrian');
country('Turkey', 'Turkish');
country('Ukraine', 'Ukrainian');
country('Venice', 'Venetian');
    // city? province? country?
country('Wales', 'Welsh');
country('Yugoslavia', 'Yugoslav');

continent('North America', 'North American');
country('Canada', 'Canadian');
country('United States', 'American');
state('Hawaii', 'Hawaiian');
    // although Hawaii was a country at one point
subcontinent('Central America', 'Central American');
country('Costa Rica', 'Costa Rican');
country('Cuba', 'Cuban');
country('Dominican Republic', 'Dominican');
country('Guatemala', 'Guatemalan');
country('Haiti', 'Haitian');
country('Honduras', 'Honduran');
country('Mexico', 'Mexican');
country('Puerto Rico', 'Puerto Rican');
country('El Salvador', 'Salvadoran');

continent('South America', 'South American');
country('Argentina', 'Argentinian');
country('Bolivia', 'Bolivian');
country('Brazil', 'Brazilian');
country('Chile', 'Chilean');
country('Colombia', 'Colombian');
country('Ecuador', 'Ecuadorian');
country('Paraguay', 'Paraguayan');
country('Peru', 'Peruvian');
country('Uruguay', 'Uruguayan');
country('Venezuela', 'Venezuelan');

continent('Oceania', 'Oceanian');
country('Australia', 'Australian');
country('New Zealand', 'New Zealander');

function main() {
    write_ser(DB_location::enum(), 'location');
}

main();
?>
