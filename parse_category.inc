require_once("mediawiki.inc");

// read JSON/mediawiki file with categories.
// Parse the fte:person template calls

// fields of #fte:person,performer,composer:
// Birth Date
// Born Year/Born Month/Born Day
// Death Date
// Died Year/Died Month/Died Day
// Time Period
// Time Period2
// Nationality ; sometimes "Armenian composers"
// Nationality2
// Nationality3
// Sex / female or Female
// Alternate Names
// Biography Link
// Picture
// Picture Caption
// Extra Information
// Flourished
//
// performer:
// Type (organization)
// Website
// Instrument

// Outside of template: {{MoreInfo

function parse_person($str) {
    $pos = 0;
    $other_text = '';
    while (true) {
        [$item, $new_pos] = parse_item($str, $pos);
        $pos = $new_pos;
        if ($item === false) break;
        if (is_string($item)) {
            $other_text .= " $item";
        } else {
            echo "got $item->name\n";
            if ($item->name === '#fte:performer') {
            }
            if ($item->name === '#fte:person') {
            }
            if ($item->name === '#fte:composer') {
        }
    }
}

