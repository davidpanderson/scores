<?php

require_once('../inc/util.inc');

page_head("The CMI database");
text_start();
echo <<<EOT
<p>
CMI stores information about classical music:
things like compositions, scores, recordings, concerts, and people.
CMI stores this information in a SQL database (DB)
which is a set of 'tables'.
Tables are like spreadsheets:
they consist of rows, and each row contains fields.
This document describes the various tables in CMI,
and how they are connected.
<h2>People and roles</h2>
<p>
Let's start with compositions and composers.
CMI has a table of compositions.
This table has a row for the Moonlight Sonata by Beethoven.
This row stores the title of the piece,
the year it was composed, and so on.
The row could potentially contain the composer's name - 'Ludwig van Beethoven'.
<p>
<img width=400 src=comp1.jpg>
<p>
But Beethoven wrote a lot of pieces.
If the name appeared in all these rows,
it would create the possibility of inconsistency:
one instance might be misspelled or capitalized differently.
<p>
To avoid this possibility, CMI has a separate table for people.
In this table, each row represents a person,
and contains their name, their birth/death dates, and so on.
This is the only place the Beethoven's name appears in the CMI database,
so no inconsistency is possible.
<p>
A row in one table can have fields that 'link' to rows in other tables.
So the Moonlight Sonata row in the Composition table
(and the rows for all of Beethoven's compositions)
could link to the Beethoven row in the Person table.
<p>
<img width=500 src=comp2.jpg>
<p>
This would work for the Moonlight Sonata.
But what if a composition has two composers,
or a composer and a librettist?
<p>
To handle these situations, CMI defines a set of of musical 'roles':
composer, performer, arranger, lyricist, librettist, conductor.
These are stored in a table called Role.
<p>
Then, CMI uses a Person-Role table to describe
a musical role by a particular person.
Each row links to a Role and a Person.
One row, for example, would link to Beethoven and 'composer'.
<p>
Thus, CMI's structure for the Moonlight Sonata is
<p>
<img width=600 src=comp3.jpg>
<p>

and the structure for a piece with multiple creators might be

<p>
<img width=600 src=person_role.jpg>
<p>

Person-roles are also used for Performances (see below).
A person might have many musical roles:
composer, performer, arranger, conductor.
They would have a separate Person-Role entry for each role.
<p>
In a Person-Role, if the role is 'performer',
the item also links to an Instrument (see below).
If a person plays multiple instruments,
they would have multiple Person-Roles with the 'perfomer' role.

<p>
Person-roles let CMI accurately represent the
creators of compositions and performances.
In addition, they provide a meaningful way
for CMI users to rate musicians.
CMI lets you rate Person-roles.
Marc-Andre Hamelin is both a performer (piano) and a composer.
Maybe you like him as a performer but not as a composer.
CMI lets you express this.

<h2>Sections and arrangements</h2>
<p>
In CMI, a Composition is something
that could plausibly be performed as a unit.
<p>
Compositions can have 'sections':
for example, the movements of a sonata.
CMI represents each movement as a Composition.
The main Composition has a list of sections,
and each section has a 'parent' link to the main Composition.
<p>
An 'arrangement' (a version of a piece for different instrumentation)
is represented as a separate composition,
with an 'arrangement_of' link to the main Composition.
<p>
Thus we might have the following structure:
<p>
<img width=500 src=comp.jpg>
<p>
Modeling sections as Compositions lets us
store their separate details: key, tempo indications, metronome markings.
It also means that CMI users can rate sections separately:
you might like one movement but not another.


<h2>Instruments and Instrumentations</h2>
<p>
The Instrument table represents
<ul>
<li> Individual instruments, such as Piano.
<li> An instrument played a specific way, such as Piano 4 hands.
<li> A voice, with range that's specified ('mezzosoprano')
or unspecified ('singer').
<li> An incompletely specified set of instruments,
such as 'orchestra' or 'male chorus'.
</ul>

<p>
An Instrumentation is a list of instruments,
each with a specified count.
For example: 'piano, 2 violins, and bassoon'
or 'violin and orchestra'.

<p>
Each Composition links to the Instrumentation(s) it's written for.
This high-resolution information allows CMI to handle queries like
'show compositions for piano, at least 2 violins,
and possibly other instruments'.

<h2>Performances</h2>
<p>
A Performance represents a performance - past or future -
of a Composition.
It links to Person-Roles for the performers,
and optionally to an Ensemble.
Concerts (see below) contain links to the Performances
that make up the program.
<p>
For performances in the past,
a Performance can optionally describe a recording of the performance:
a list of files (e.g. on IMSLP, YouTube, or Soundcloud),
and the publisher and license.
<p>
CMI users can rate Performances.
They rate the quality of the performance itself,
and (if it's a recording) the sound quality.

<h2>Scores</h2>
<p>
A Score represents a musical score.
It links to one or more Compositions,
and has information about the publisher and license.
It links to Person-Roles for roles such as editor and translator.
<p>
If the score is available as a set of files (PDF
or some other digital format)
it includes a list of descriptions of these files.
There may be separate files for the sections of the piece,
or 'parts' for the various instruments.

<h2>Concerts</h2>
<p>
A Concert represents a concert, past or future.
Its program is represented as a list of Performancs (see above).
It also links to Venue (a description of the concert venue)
and the sponsoring Organization, if any.
For past concerts, it can include the audience size.
EOT;
text_end();

page_tail();
?>
