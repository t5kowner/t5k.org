<?php

include("bin/basic.inc");
$dbh = basic_db_connect(); # Connects or dies

// Query the table staus to get the number of terms, links and last update time
// (which will be displayed on the bottom of the home page).

$query = "SHOW TABLE STATUS";
$sth = lib_mysql_query($query, $dbh, "Error while asking status");
while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    if ($row['Name'] == 'terms') {
        $terms = $row['Rows'];
        $update_time = $row['Update_time'];
    } elseif ($row['Name'] == 'links') {
        $links = $row['Rows'];
    }
}
$update_time = preg_replace(
    "/(\d+)\-0?(\d+)\-0?(\d+) 0?(\d+:\d+):\d+/",
    "$2/$3/$1 at $4 UTC",
    $update_time
);

// Grab the index too.

$index = basic_index();

// Okay, lets start filling in the template variables

$t_meta['description'] = "This glossary contains information on
      primes (and related subjects) for students in grade-school through
      graduate school.  Visit us to find out what a word means, to discover
      some new information about primes, to find a topic for a research paper,
      ...";
$t_title = "Glossary Home Page";

$t_text = <<< TEXT
  <p>"The Prime Glossary" is your Internet guide to the terminology of prime
numbers. We began this project at the <a HREF="/">PrimePages</a> in
early 1998 to provide simple, terse definitions of words and names related
to prime numbers. When appropriate, the glossary includes links to other
pages with fuller definitions and information.</p>

<div class="lead font-weight-bold">$index</div>

<h3>The goal and level of this glossary</h3>

<p>This glossary is purposefully written on two levels because it is
written to serve two audiences.  First, for school teachers and students
we wanted to define the basic words of elementary prime number theory and
list the unusual or curious terms that could be the subject of excellent
research papers.  So we define terms using the least amount of jargon
possible, even if this sometimes makes the wording slightly non-standard.
For example, we might reword a definition so that we can use "divides"
rather than "congruence" (even when the latter expression might be more
natural to a mathematician).  However, not all concepts can be easily
defined.</p>

<p>Second, for those who are familiar with the basic terminology (say an
undergraduate student in a number theory course), we wish to list some of
the useful (but perhaps less well known) words and ideas.  For this
audience words that describe the typological qualities of numbers (for
example), may seem frivolous--but they could provide an excellent test of
analytic and heuristic skills as you answer the usual questions (are there
infinitely many? can we quantify the number? generalize the concept?).  Of
course our site is concerned with prime number records, so we also try to
provide links and references to sites that give the current results.</p>

<p>One of the best features of this glossary is that this dual nature is
often found in a single definition.  We might begin with a rough
definition, then immediately move on to a technical version or advance
consequences.  This grants the university student immediate access to
information and gives the school age student a peek down the grand road
into the "Queen of Mathematics": number theory.</p>

<ul>
  <li>Current glosses: $terms</li>
  <li>Defined Links: $links</li>
  <li>Last updated: $update_time</li>
</ul>
TEXT;

include("template.php");
