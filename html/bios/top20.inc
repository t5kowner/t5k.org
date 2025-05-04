<?php

#t# Omitted math

# Strings for the top20 page explaining various scoring systems...

# person.type (enum): person, program, project...; as 'type'
# 'by': PrimesRank, ScoreRank, PrimesTotal, ScoreTotal

$top20_page_top = "<table>\n <tr>\n  <td><p>   \n";
$top20_page_top = top20_choice_table() . "<p>At this site we keep several <a href=\"/lists/\">lists of primes</a>,
	most notably the list of the 5,000 largest known primes. Who found the most of these record primes? We keep
	separate counts for persons, projects and programs. To see these lists click on 'number' to the right.</p>\n
	<p>Clearly one 100,000,000 digit prime is much harder to discover than quite a few 100,000 digit primes.
	Based on the usual estimates we score the top persons, provers and projects by adding 
	&lrm;(log&nbsp;<I>n</I>)<SUP>3</SUP>&nbsp;log&nbsp;log&nbsp;<I>n</I>&lrm; for each of their
	primes <i>n</i>. Click on 'score' to see these lists.</p>\n
	<p>Finally, to make sense of the score values, we normalize them by dividing by the current score of the
	5000th prime. See these by clicking on 'normalized score' in the table on the right.</p>";

#####  Notes: for type and scoring method #####

# First, notes wrappers

$top20_notes_head = "<h4>Notes:</h4>\n";
$top20_notes_tail = "\n";

# Second, notes for type: person, program, project and other

$top20_person_notes = '';

$top20_program_notes = "\n<blockquote>\n" .
    'The list above show the programs that are used the most (either by number or score).
	In some ways this is useless because we are often comparing apples and oranges,
	that is why the comments in brackets attempt to say what each program does.
	See <a href="help/program_does.php">the help page</a> for some explanation of these vague categories' .
    "\n</blockquote>\n";

$top20_projects_notes = '';

$top20_other_notes = "\n<blockquote>\n" .
   "'Other' is a catch-all category for entities that are not programs, project or persons.  For example, Scott Kurowski provided an exponent reserving system for GIMPS. See the bottom of the page of the list of top twenty persons to
	see and explanation of prime counts and scores.\n</blockquote>\n";

#  Second, notes for method: PrimesRank, ScoreRank

$top20_ScoreNormal_notes = "\n\n<dl>\n<dt><b>normalized score</b>\n<dd><br>

    <p>Just how do you make sense out of something as vague as our 'score' for
    primes?  One possibility is to compare the amount of effort involved in
    earning that score, with the effort required to find the 5000th prime on the
    list.  The normalized score does this: it is the number of primes that are
    the size of the 5000th, required to earn the same score (rounded to the
    nearest integer).</p>

    <p>Note that if a $GLOBALS[type] stops finding primes, its normalized
    score will steadily drop as the size of the 5000th primes steadily increases.
    The non-normalized scores drop too, but not as quickly because they only drop
    when the $type's primes are pushed off the list.</p>
    </dl>\n\n";

$top20_PrimesRank_notes = "<br><dl>\n<dt><b>Number of primes</b>\n\n<dd><br>\n" .
    '<p>When counting primes we decided that if three people (persons) went together to find a prime, each should get credit for 1/3 of a prime.
	The same is true for projects, however programs get full credit for each prime (to encourage honest reporting of what programs where used).
	Persons, programs and projects are three separate categories and do not compete against each other.</p>' . "\n\n" .
    "<p>For example, suppose the persons '<a href=\"page.php?id=239\">Carmody</a>' and '<a href=\"page.php?id=9\">Caldwell</a>'
	worked together and used the program '<a href=\"page.php?id=411\">PRP</a>' to test
	candidates selected by the '<a href=\"page.php?id=441\">GFN 2^13 Sieving project</a>', then
	completed their proofs using '<a href=\"page.php?id=432\">Proth.exe</a>'. Then the persons
	'Carmody' and 'Caldwell' would get 1/2 credit for each prime found; but the project
	'GFN 2^13 Sieving project' and the programs 'PRP' and 'Proth.exe' would each get full credit.<\p></dl>\n";

$top20_ScoreRank_notes = "\n<br><dl>\n<dt><b>Score for Primes</b><br>\n\n<dd><p>" .
    "<p>To find the score for a person, program or project's primes, we give each prime
	<i>n</i> the score (log&nbsp;<I>n</I>)<SUP>3</SUP>&nbsp;log&nbsp;log&nbsp;<i>n</i>; 
	and then find the sum of the scores of their primes. For persons (and for projects),
	if three go together to find the prime, each gets one-third of the score.
	Finally we take the log of the resulting sum to narrow the range of the resulting scores.
	(Throughout this page log is the <a href=\"/glossary/page.php?sort=Log\">natural logarithm</a>.)</p>

	<p>How did we settle on (log&nbsp;<I>n</I>)<SUP>3</SUP>&nbsp;log&nbsp;log&nbsp;<I>n</I>?
	For most of the primes on the list the primality testing algorithms take
	roughly <A href=\"/glossary/page.php?sort=BigOh\" class=glossary>O</A>(log(<I>n</I>)) steps
	where the steps each take a set number of multiplications. FFT
	multiplications take about</p>

<blockquote>
  O( log&nbsp;<i>n</i> <SUP>.</SUP> log log <I>n</I> <SUP>.</SUP> log log log <I>n</I> )
</blockquote>

<p>operations. However, for practical purposes the
O(log&nbsp;log&nbsp;log&nbsp;<I>n</I>) is a constant for this range number
(it is the precision of numbers used during the FFT, 64 bits suffices for
numbers under about 2,000,000 digits).</p>

<p>Next, by <a href=\"/glossary/page.php?sort=PrimeNumberThm\" class=glossary>the prime
number theorem</a>, the number of integers we must test before finding a
prime the size of <I>n</I> is O(log&nbsp;<I>n</I>) (only the constant is
effected by prescreening using trial division).&nbsp; So to get a rough
estimate of the amount of time to find a prime the size of <I>n</I>, we
just multiply these together and we get</p>

<BLOCKQUOTE>
  O( (log&nbsp;<I>n</I>)<SUP>3</SUP> log log <I>n</I> ).
</BLOCKQUOTE>

<p>Finally, for convenience when we add these scores, we take the log of the
result.&nbsp; This is because log&nbsp;<i>n</i> is roughly 2.3 times the
number of digits in the prime <i>n</i>, so (log&nbsp;<I>n</I>)<SUP>3</SUP>
is quite large for many of the primes on the list.&nbsp;(The number of
decimal digits in <i>n</i> is <A
href=\"/glossary/page.php?sort=FloorFunction\"
class=glossary>floor</A>((log&nbsp;<I>n</I>)/(log&nbsp;10)+1)).</p>

</dl>
";

function top20_choice_table()
{

    $number = 'number';
    $score  = 'score';
    $nscore = 'normalized score';

    return("<table class=\"td2 hover ml-3 text-bold\" style=\"float: right\">
    <tr class=\"blue lighten-3\"><th colspan=4>The Prover-Account Top 20</th></tr>
    <tr><th class=\"blue lighten-5\">Persons by:</th>
	<td><a href=\"top20.php?type=person&amp;by=PrimesRank\" class=\"none\">$number</a></td>
	<td bgcolor=\"$GLOBALS[ltcolor]\"><a href=\"top20.php?type=person&amp;by=ScoreRank\">$score</a></td>
	<td><a href=\"top20.php?type=person&amp;by=ScoreNormal\" class=\"none\">$nscore</a></td>
    </tr>
    <tr><th class=\"blue lighten-5\">Programs by:</th>
	<td><a href=\"top20.php?type=program&amp;by=PrimesRank\" class=\"none\">$number</a></td>
	<td bgcolor=\"$GLOBALS[ltcolor]\"><a href=\"top20.php?type=program&amp;by=ScoreRank\">$score</a></td>
	<td><a href=\"top20.php?type=program&amp;by=ScoreNormal\" class=\"none\">$nscore</a></td>
    </tr>
    <tr><th class=\"blue lighten-5\" align=right>Projects by:</th>
	<td><a href=\"top20.php?type=project&amp;by=PrimesRank\" class=\"none\">$number</a></td>
	<td bgcolor=\"$GLOBALS[ltcolor]\"><a href=\"top20.php?type=project&amp;by=ScoreRank\">$score</a></td>
	<td><a href=\"top20.php?type=project&amp;by=ScoreNormal\" class=\"none\">$nscore</a></td>
    </tr>
  </table>\n");
}
