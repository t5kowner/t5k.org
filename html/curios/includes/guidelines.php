<?php

$t_meta['description'] = "As editors we regularly need to decide whether or not to
   add a curio. The key question is will people find it curios (interesting, though-provoking, ...)? The
   'better' curios make the whole collection better.&nbsp; The 'poorer' curios discourage folks from reading any
   further.&nbsp; Of course 'better,' 'poorer,' 'interesting,' even 'curious' are all relative terms! Here are a few
   pointers to keep in mind";

$t_title = "Guidelines for Curio Authors";
$t_meta['add_keywords'] = "definitions, primes, terms, largest known primes,Guidelines for Curio Authors";
// $t_subtitle = "";

$t_text = <<<HERE
<p>As editors we regularly need to decide whether or not to
add a curio.&nbsp; The key question is will people find it
curios (interesting, though-provoking, ...)?&nbsp; The
"better" curios make the whole collection better.&nbsp; The
"poorer" curios discourage folks from reading any
further.&nbsp; Of course "better", "poorer", "interesting",
even "curious" are all relative terms!&nbsp; Here are a few
pointers to keep in mind:</p>
<h4>
  What makes a curios "better" (and likely to be accepted)
</h4>
<ul>
  <li>Using an active voice to write terse and lively
  prose.
  </li>
  <li>Correct spelling and grammar.
  </li>
  <li>The use of standard mathematical terminology,
  notation and symbols.
  </li>
  <li>That little extra--"the only example of" of the
  "smallest prime such that".
  </li>
  <li>Combining different ideas.&nbsp; (Not just "a
  palindrome", there are too many of these, but "the
  largest palindrome ever spoken by a US president in an
  inauguration speech.")
  </li>
  <li>A surprise element.&nbsp; "I wouldn't have guessed
  that!"
  </li>
  <li>Written about a prime number which has no curios yet.</li>
</ul>
<h4>What makes a curio "poorer" (and likely to be
rejected)</h4>
<ul>
  <li>An empty phrase such as "an interesting prime" or "a
  curios looking prime."
  </li>
  <li>Special terminology, especial names you made up: "A
  nacho-Brunner prime of the third spiral."
  </li>
  <li>Errors, even if common (such as 11 is the sum of the
  first four primes).
  </li>
  <li>Worded so that it is only true for at a specific
  time.&nbsp; For example "the largest known twin prime"
  should be reworded "On July 17th, 2001, this new record
  twin prime was discovered."
  </li>
  <li>Being like other entries (or likely to spawn many
  similar entries)!&nbsp; Some things are interesting once.
  A very few things twice.&nbsp; But virtually nothing is
  interesting twenty times in a row.
  </li>
  <li>A statement that is too often true such as "<i>n</i>
  is a plateau prime" (there are probably infinitely many
  of these) or "the integer part of <img src="/gifs/pi.gif"
    alt="pi">*e<sup>7</sup> is a prime" (there are
    uncountably many such real numbers!)
  </li>
  <li>Written about a prime which already has an abundance
  of curios (2, 3, 5, 7).</li>
  <li>
    <font color="red">If more than seven curios are
    submitted in a seven day period, the extras will be
    deleted and never even seen by an editor.</font>
  </li>
</ul>
<h4>Note</h4>
<p>Does this mean a Curio with one (or more) of the
better characteristics will always be accepted and one with
the poorer will not?&nbsp; No.&nbsp; These are just rough
guidelines. We always want to encourage experimentation and
creativity.&nbsp; A hard and fast set of rules would make a
dead collection.</p>
<p>
  Surprise us!
</p>
<p>
  The editors.
</p>
HERE;

$t_adjust_path = '../';
include("../template.php");
