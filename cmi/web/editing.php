<?php

require_once('../inc/util.inc');
page_head('Editing in CMI');
text_start();
echo <<<EOT
<p>
<p>
CMI lets you add and modify items
(compositions, scores, concerts, people, etc.)
using a web interface.
To access these functions:
<ul>
<li> Create a CMI account and log in.
<li> CMI's front page has links to 'search pages'
for each item type.
Each of these has an Add button
that lets you add an item of that type.

<li> Each item has a corresponding 'item page'
showing its details.
These pages have an Edit button letting you edit the item.
These buttons appear only if you have access to the item (see below).
</ul>

<p>
<h2>Access control</h2>
<p>
<p>
All users can create items.
A user can edit items they created.
<p>
Certain users have 'editor' privileges.
Such users can edit any item.
To request editor privileges, contact CMI admins.

<h2>Item codes</h2>
<p>
Many CMI items link to items of other types.
For example, Concert links to Composition.
When the second table is small (a few hundred items or less)
the editing interface uses a popup menu.
<p>
This isn't practical if the second table is large -
e.g. Composition or Person.
So instead we use a mechanism called 'item codes'.
Say, for example, you're editing a Concert
and you want to add a Composition to the program.
<ul>
<li> Create or edit the Concert.
<li> In a separate browser tab, go to the Compositions search page.
Find the composition you want.
In the Code column, click on its Copy button.
This copies a 'composition code' to your clipboard.
You'll get an alert saying 'Copied code com_123'; click OK.
<li> In the Concert edit tab,
paste the composition code into the Add: field
(using, e.g. ctrl-V).
</ul>
<p>
An item's code is its database ID,
prepended with a string indicating its type
('com_' in this case).
This prevents you from accidentally using the wrong code.
<p>
In some cases (as in the above example)
you can specify multiple items.
List their item codes, separated by spaces.

<h2>Example: adding a composition</h2>
<p>
Let's say you've written a piece for, say, flute and piano,
and you want to add it do CMI.
<ul>
<li> Go to the Compositions page and click Add Composition.
This brings up a form.
The required fields are Title, Creators, and Instrumentations;
the rest are optional.
<li> The Title, Opus, Tempo Markings, and Dedication fields are text;
you can type whatever you want.
<li> Other fields are text, but require a certain format.
Dates must be YYYY or YYYY-MM-DD.
Time signatures must by N/M,
and metronome markings and keys have their own formats.
If you type something not in this format,
you'll get an error message.
<li> The 'Composition types' field is a popup menu;
it lets you pick multiple types.
<li> The 'Creators' field requires 'Person role' codes:
one for the composer, possibly others for lyricist
or additional composers.
You get each of these as follows (do this in a separate browser tab):
    <ul>
    <li> Find the person in the People page.
        If needed, create the person by clicking the 'Add person'
        button and filling out the form.
    <li> On the Person item page, click 'Add role'.
        Select e.g. 'composer' and click OK.
    <li> You'll now see 'composer' as one of the person's roles.
        Click the Copy button next to it.
    <li> Back on the first tab, paste the role code into
        the Creators field.
    </ul>
<li> In the Instrumentations field, you say
    what instrumentations the piece is for.
    There could be several: say, violin and piano, and flute and piano.
    This also uses item codes.
    For each instrumentation:
    <ul>
    <li> In a separate tab, go to the Instrumentations page.
        You'll see a long list of instrumentations.
    <li> To shorten the list, select one or more instruments
        (say, 'piano') and click Search.
        That will show only instrumentations containing piano,
        which will include 'flute and piano' and 'violin and piano'.
    <li> If you don't find the one you want, click
        'Add instrumentation' and create a new one.
    <li> When you find the instrumentation you want, click the Copy button,
        and paste the code into the Instrumentations field
        in the first tab.
    </ul>
<li> Click 'Add composition'.
That will check for errors,
create the composition, and show you its item page.
Click 'Edit composition' if you need to fix anything.
</ul>
<p>
This might seem like a lot of steps.
Why not just have you type in 'Violin and piano'?
<p>
Doing things this way - having you select from existing tables
instead of typing things - has big benefits.
It keeps the data clean - no spelling errors or variant spellings.
And it puts the information into a form that lets CMI
handle queries like 'show me pieces for piano, at least one bassoon,
and maybe other instruments'.

<h2>Example: adding a concert</h2>
<p>
Suppose you want to add an upcoming concert.
<ul>
<li> Go to the Concerts page and click 'Add concert'.
This brings up a form.
<li> Choose the venue from the popup menu.
If it's not listed there, create a venue:
    <ul>
    <li> In a different tab, click Add venue and fill out the form.
    <li> If the venue's location (city) isn't listed in the popup menu,
        open a 3rd tab and create the location.
        Then reload the Venue form; the venue will now appear in the menu.
    <li> Click OK in the venue form.
    <li> Reload the Concert form; the new venue will be in the menu.
    </ul>
<li> Similarly for 'Sponsoring organization'.
If the concert has a sponsor and it's not in the menu,
create the organization in another tab and reload the Concert form.
<li> Enter the concert date in YYYY-MM-DD format.
<li> In a separate tab, look up the Composition codes
for the pieces on the program.
Enter them, in order, in the 'composition codes' field.
<li> Click 'Add concert'.
This creates the concert and shows its details page.
But you still need to add performers.  Click Edit.
<li>
The program is shown as a table, one row per composition.
The Performers column lists the performers.
For each performer:
    <ul>
    <li> In a 2nd tab, look them up in the People page.  Create if needed.
    <li> In their Person page, see if the appropriate role
        (e.g. 'John Doe as performer (piano)') is listed.
        If not, add that role.
    <li> Copy the Person/role code and paste it into the 'role codes' field.
    </ul>
<li>
If an ensemble (e.g. an orchestra or choir) performed,
look it up (create if needed), copy its code,
and paste the code into the Ensemble field.
<li> If consecutive pieces have the same performers and ensemble,
fill these in for the first one
and check 'Copy from previous' for the others.
<li> Click 'Update concert'.
</ul>
EOT;
text_end();
page_tail();
?>
