<?php

include_once("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

// Query the table staus to get the 'Rows', 'Update_time', 'Data_length',
// 'Avg_row_length', and 'Comment'.  (Which will be displayed on the
// bottom of the home page).

foreach ($db->query('SHOW TABLE STATUS') as $row) {
    $status[$row['Name']] = $row;
}

// Grab the index too.

$index = basic_index();

// Okay, lets start filling in the template variables

$t_meta['description'] = "Home page for the lists of the largest known primes.
	Updated hourly, these pages contain research, records and results, on
	primes numbers, their discoverers and the software used to find them.
	Top 20 tables for twin, Sophie Germain, Mersenne, and many other
	types of primes...";
$t_title = 'The Largest Known Primes';
$t_submenu = '5000 largest';

$t_text = <<< TEXT
	<a href="/notes/image.html"><img src="/gifs/BigPrime.jpg" class="d-none d-sm-inline
	img-fluid overlay zoom z-depth-1-half" align=right alt="Image of a very large 2"></a>

      <p>Are you looking for the <b>largest known primes</b>?&nbsp; Lists of records?&nbsp;
        Information on who finds the biggest primes (and how?)&nbsp; You are at
        the right place!&nbsp;&nbsp;Here we keep the list of the 5000 largest
        known primes, <b>updated hourly</b> (hundreds of new primes are submitted
        every month).&nbsp; We are <b>the world's primary source</b> to the current
        prime number records.</p>
TEXT;

$t_text .= <<< TEXT
      <ol type="I">
        <li><b>The current list of primes </b>
          <ul>
	    <li><a href="/largest.html">One page summary</a> (introduction and a few records)
            <li><a href="download.php">Downloading the list </a> of the largest known primes
            <li><a href="search.php">Search the database of primes</a>
            <li><a href="/top20/index.php">The Top 20 Primes of Selected Forms</a>
          </ul>
        <li><b>Background information</b>
          <ul>
            <li><a href="/notes/introduction.html">A short introduction to primes</a>
            <li><a href="/prove/">How do you prove large numbers are prime? </a>
            <li><a href="/notes/finding.html">How can I find a record prime?</a>
		(current <a href="../bios/top20.php?type=program&amp;by=PrimesRank">top programs</a>)
            <li>What projects might I join?
		current <a href="/bios/top20.php?type=project&amp;by=PrimesRank">top projects</a>
          </ul>
        <li><b>Tell me more!</b>
          <ul>
            <li><a href="/glossary/">The Prime Glossary</a> -- definitions of prime terms
            <li><a href="/notes/faq/">Answers to frequently asked questions</a>
	    <li><a href="/curios/">Prime Curios!</a> amazing and odd facts about prime numbers
	    <li><a href="/bios/">Who found</a> these primes?
          </ul>
      </ol>
Note: these are the largest known primes--so they are very big (most have hundreds
      of thousands of digits!)&nbsp; If you want smaller primes, say the first ten thousand
      primes or first hundred million primes, see <a href="/lists/">lists of small primes</a>.
TEXT;

// Query the table status to get the 'Rows', 'Update_time', 'Data_length',
// 'Avg_row_length', and 'Comment'.  (Which will be displayed on the


$t_text .= '<P><div class="technote p-3">' . "\n";
$total = 0;
$temp = '';
foreach ($status as $name => $v) {
    if (!preg_match('/^(prime|person|archival_tag|code|log)$/', $name)) {
        continue;
    }
    $update_time = preg_replace(
        "/(\d+)\-0?(\d+)\-0?(\d+) 0?(\d+:\d+):\d+/",
        "$2/$3/$1 at $4 UTC",
        $v['Update_time']
    );
    $temp = "<dt>Table '<b>${name}</b>': $v[Rows] entries, last updated: $update_time.
	<dd>Total data length $v[Data_length] bytes (average $name entry length
	$v[Avg_row_length] $v[Row_format])
	$v[Comment]\n$temp";
    $total += $v['Data_length'];
}
$t_text .= '<dl class="mb-0">' . "\n$temp\n<dt>Combined size: <dd>" . number_format($total) . " bytes</dd></dl></div>\n";

include("template.php");
