<?php

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

// Query the table status to get the 'Rows', 'Update_time', 'Data_length',
// 'Avg_row_length', and 'Comment'.  (Which will be displayed on the
// bottom of the home page).

$query = "SHOW TABLE STATUS";
$sth = lib_mysql_query($query, $db, 'Error while asking status');
while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    $status[$row['Name']] = $row;
}

// Okay, lets start filling in the template variables

$t_meta['description'] = "The top 20 primes of certain selected forms
	including twin, primorial, factorial, Woodall, Sophie Germain, Mersenne,
	and many other types of primes...";
$t_title = "Top Twenty's Home Page";
$t_submenu = 'Archivable Primes';
$t_subtitle = "(Definitions of Archivable Form and Class)";

$t_text = '<p>The Prime Pages maintains the database of the largest known primes. This collection started when Samuel Yates
    listed all of the primes with at least 1,000 digits and called them <a href="/glossary/xpage/TitanicPrime.html"
    class="glossary">titanic primes</a> <a href="/references/refs.cgi/Yates84">[Yates84]</a>.  By 1996 that list had
    grown to 50,000 primes, and was growing by roughly 1,000 primes each month.  Unfortunately, most of these new primes were
    just barely larger than the original 1,000 digits--people were seeking more primes, but not larger primes!</p>

    <p>At that point we decided to return the focus to record primes by just keeping <a href="/primes/">the 5000 largest known
    primes</a> (and every prime that has ever made this list). We also included up to twenty each of certain selected forms--even
    if they do not make the top 5000.  We call these forms <b>archivable</b>. Which primes are these? In 1997, after a great deal
    of debate, we settled on the following definition.</p>

    <blockquote id="archivable">An <b>archivable form</b> of prime is one which is <em>the subject</em> of more than one mathematical journal article
    written by more than one set of authors. Two articles from a single author are sufficient if at least one is in a major
    refereed journal such as <a href="https://www.ams.org/publications/journals/journalsframework/mcom">Math. Comp.</a>,
    otherwise four articles from single authors are required. To prove a number is archivable, one need only supply the article
    references. The Prime Pages will keep the top 5000 primes plus up to twenty (20) of each of the archivable primes.</blockquote>

    <p>The fact that we do not keep a certain prime (smaller than the 5000th) does not make it unworthy or uninteresting; it
    just means we do not keep records of that form of prime.</p>

    <p>In September 2000, it became necessary to make a ruling on certain <i>classes of forms</i> of primes such as the primes
    in arithmetic progression and Cunningham chains. We adopted the following rule.<p>

    <blockquote>Sometimes the form itself is not archivable, but the primes of that form belong to a larger class that is. We call
    these <b>archivable classes</b>. For example, primes in arithmetic progressions are an archivable class,
    so we will keep up to five each of the terms in an arithmetic progressions of primes starting with the third term. Other
    examples include: Cunningham chains, Cunningham chains of the second kind, triplets, quadruplets and quintuplets. We keep
    the top five (not the top twenty) of selected terms in an archivable class.
    </blockquote>

    <p>We will eventually make a <a href="index.php">Top 20 page</a> for each of the archivable forms. If we are missing your
    favorite form, then just e-mail us the appropriate references (or write the articles if necessary). The Top 5000 list
    only stores primes with at least 1,000 digits, so the forms are restricted to those for which we know large examples.
    This excludes excellent forms such as the <a href="/glossary/xpage/WilsonPrime.html"  class="glossary">Wilson primes</a>
    and <a href="/glossary/xpage/WieferichPrime.html" class="glossary">Wieferich primes</a>.
    All known primes of these two types are already listed in the <a href="/glossary" class=glossary>Prime Glossary</a>.</p>

    <p>Finally, there are some classes of primes we list for historical reasons (such as they were so commented on Yates\' lists).
    These are <b>tolerated</b> on the current list, and only appear on the list if the prime there for some other reason.</p>

    <p>Use the sizes tab above to see how large a prime needs to be to make the list.</p>';

// Query the table status to get the 'Rows', 'Update_time', 'Data_length',
// 'Avg_row_length', and 'Comment'.

$total = 0;
$out = '';
foreach ($status as $name => $v) {
  # if (Preg_Match('/^meta/',$name)) continue;
    if (!Preg_Match('/^(archivable|prime|archival_tag)$/', $name)) {
        continue;
    }
    $update_time = preg_replace(
        "/(\d+)\-0?(\d+)\-0?(\d+) 0?(\d+:\d+):\d+/",
        "$2/$3/$1 at $4 UTC",
        $v['Update_time']
    );
    $out = "    <dt><b>${name}</b>s: $v[Rows] (last updated: $update_time)
    <dd>Total data length $v[Data_length] bytes (average $name entry length $v[Avg_row_length] $v[Row_format])<br>
	$v[Comment]\n$out";
    $total += $v['Data_length'];
}
$t_text .= "<div class=\"technote p-3\">
  <dl>
    <dt>Status of database tables from which these pages are generated:<br><br>
       <dd><dl>\n$out\n</dl></dd>
    <dt>Total length: $total.</dt><dd></dd>
  </dl>
</div>\n";

include("template.php");
