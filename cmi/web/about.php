<?php
require_once('../inc/util.inc');
page_head('About CMI');
text_start();
echo "
<p>
The primary goal of CMI is to help performers
discover music they love to play and wouldn't ever see otherwise.
This goal is explored
<a href=https://continuum-hypothesis.com/music_discover.php>here</a>.
<p>
One aspect of this goal is to help music by living amateur composers -
many of whom are great but obscure -
get discovered, performed, and recorded.
<p>
Most classical musicians get music from <a href=https://imslp.org>IMSLP</a>,
which has a huge collection of out-of-copyright scores.
IMSLP is great if you're looking for a specific existing work,
but it doesn't have effective ways of discovering works you haven't heard of.
<p>
CMI supports two general approaches to music discovery:
<ul>
<li>
CMI is built on a very detailed
<a href=https://continuum-hypothesis.com/music_schema.php>schema</a>
(data model).
It describes instrumentations, arrangements, movements,
creators and their roles,
genders, nationalities, dates, and so on.
Data is stored in a relational database,
and arbitrary relational queries on this data are possible.
The current interface supports queries like
'show me all the arrangements of string quartets for two pianos'
or 'show me all the music for solo violin by female French composers'.
The interface could be extended to allow even more detailed queries.
<li>
CMI is designed to support music discovery by
social mechanisms, including 'collaborative filtering':
you can rate things (works, people, recordings)
and when there's a critical mass of such ratings,
CMI can make recommendations for music you'll probably like
that you don't know about yet.
</ul>

<p>
CMI is a non-profit project, created and operated by volunteers.
Its source code is open source and is available on
<a href=https://github.com/davidpanderson/scores/tree/master/cmi>Github</a>.

<p>
If you have questions or comments about CMI,
please <a href=contact.php>contact us</a>.

";
text_end();
page_tail();
?>
