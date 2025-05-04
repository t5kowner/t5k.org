<?php

$t_meta['description'] = "Rules and guidelines for submitting unusal primes
        to the database of the Largest Known Primes.";
$t_title = "Guidelines for Submissions to the prime_blob Table";
$t_meta['add_keywords'] = "comments, guidelines, primes, largest known primes";
// $t_subtitle = "";

$t_adjust_path = '../';

$t_text = <<<HERE

<h2>Introduction</h2>

Remember, the whole point of the auxiliary 'prime_blob' table is to hold primes
that can not be placed in the main table 'prime'.&nbsp; The most common reasons
are:
<blockquote><dl>
  <dt>The prime has no short description
  <dd>Some primes do not have simple algebraic expressions to define them.&nbsp;
This is especially true of some of the prime proven by methods such as
ECPP.&nbsp;  Others have algebraic expressions, but they are longer than the 255
characters allowed in the main 'prime' table.
  <dt>The prime is too hard to calculate
  <dd>Other primes may have a short description, but like p(2448422) take too
long to calculate.&nbsp; The full digit parser always checks for a prime_blob
entry containing the entire expansion before recalculating; so adding such
primes to the 'prime_blob' table will keep the server from timing out on these
difficult primes.
</dl></blockquote>

<a name=hints></a>Below we discuss each of the form fields you must complete,
but first, <b>two hints</b>:

<blockquote><dl>
  <dt>If you are entering a long formula
  <dd>If you are here because the algebraic expression for your prime is over
255 characters, the just fill in the person field and the number field
(with the formula).&nbsp; The server will then calculate the expansion and use
it as the number, it will form a short version of this number for the
description field, and finally it will move the formula to the explanation
('text') field.&nbsp;
  <dt>If you are entering a long decimal expansion
  <dd>Again start with the person and the number field, and then add a
reasonable short explanation of this prime to the explanation
('text') field.&nbsp;  Make sure that your browser did not truncate when you cut
and paste--some browsers truncate after about 32k characters.
</dl></blockquote>

<a name=description></a><h2>The description field</h2>
This is used to describe the prime.&nbsp; It should be plain text, not HTML, and
often you should just leave it blank and let the server fill it in with
something like this:
<blockquote><ul>
  <li>"69943392751539865432...(15032 other digits)...6238463797677935111"
</ul></blockquote>
  The one time you might not want to is if the prime has a short description,
but is hard to calculate, then descriptions like the following will be more
informative:
<blockquote><ul>
  <LI>"[e*10^1230]/36037"
  <LI>"'css_descramble.c.gz'*65536+2083"</LI>
  <li>p(2448422)
</ul></blockquote>
Note the use of double-quotes around the expressions that are not parsable.

<a name='text'></a><h2>The explanation field (text)</h2>

This should explain what this this number is.&nbsp; You may use HTML,
links...&nbsp; Try to make it as accessible to the general public as
possible.&nbsp; You can write as much as necessary (up to 16k) but keep it as
short as is reasonable.

<P>As always, our editors reserve the right to edit all fields of all entries!
HERE;

include("../template.php");
