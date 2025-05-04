<?php

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

// This page should show the number of digits necessary for each form to make the list

// Okay, lets start filling in the template variables

$t_meta['description'] = "This page shows the size a prime must be to make the
	list of 5000 largest known primes in the various subcategories
	including twin, primorial, factorial, Woodall, Sophie Germain, Mersenne,
	and many other types of primes...";
$t_title = 'How big is big enough?';
$t_add_menu = "\n <a href=\"#intro\" class=menu>" . 'Introduction' . "</a>\n" .
    " | <a href=\"#minimal\" class=menu>" . 'Table of minimal sizes' . "</a>\n" .
    " | <a href=\"#more\" class=menu>" . 'Sometimes more?' . "</a> \n";
$t_submenu = 'Sizes';

# An icon linked to the top of the page:
$up = '<a class=up HREF="#top"><img src="/top20/includes/gifs/up3.gif"' .
        '  WIDTH=14 HEIGHT=14 ALT="(up)"></A>';

$The5000th = lib_get_column('prime.rank = 5000', 'prime', 'digits', $db);
$t_text = "<h2 id='intro'>$up Introduction </h2>
   <p id='top'>The Prime Pages keeps a list of the 5000 largest known primes, plus a few
   each of certain selected archivable forms and classes. These forms are
   defined in this collection's home page. To make the top 5000 today a prime
   must have $The5000th digits. This is increasing at roughly 50,000 digits per
   year. Click on the trends tab above to view the change over the last few
   years.</p>

   <p>Smaller primes, those not large enough to be in the top 5000, may stay on
   the list if they are in the first few (either 5 or 20). Below we list how
   large they must be to make our list. But be careful, this is a moving
   target--every month the size of these records increase. So if you want to
   stay on the list for awhile, do not search for a prime with just a few digits
   more, aim for thousands of digits more!</p>

   <h2 id='minimal'>$up Table of minimal sizes</h2>

   <p>Smallest prime of special forms on the list (the smallest that make
   the list on the merit of the indicated form or class alone).</p>\n\n";

# Okay, let's get the data!

$query = "SELECT min(prime.digits) as digits, archival_tag.subcategory,
	count(*) as count, archivable.type, archivable.visible,
	archivable.name, archivable.id, archivable.match_
	from prime, archival_tag, archivable
	where prime_id=prime.id and category_id=archivable.id and prime.onlist > 1
	group by archival_tag.subcategory order by archival_tag.subcategory";
$sth = lib_mysql_query($query, $db, "Error while asking status");

$tolerated = ''; // will hold a list of tolerated comments and their counts
$t_text .= "<blockquote>
  <table class='table-striped td2'>
    <tr class=\"brown lighten-4\">
      <th class=\"text-right font-weight-bold\">digits required</th>
      <th class=\"text-center font-weight-bold\">archivable form or class</th>
      <th class=\"text-center font-weight-bold\">number archived</th>
      <th class=\"text-center font-weight-bold\">number on list</th>
    </tr>\n";
while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    $subcat = $row['subcategory'];
    if ($row['visible'] == 'yes') {
        $subcat = "<a href=\"page.php?id=$row[id]\">$subcat</a>";
    }
    if ($row['type'] == 'archivable') {
        $limit = 20;
    } elseif ($row['type'] == 'archivable class') {
        $limit = 5;
    } else {
        $tolerated .= "$row[subcategory] ($row[count]), ";
        continue;
    };

    if ($row['count'] < $limit) {
        $digits = '<b class="text-success">1000</b>(*)';
    } else {
      # Notice the use of archivable.match_ here!  This will query fail to return a result
      # without it--that means primes that don't make the list by their own merits (arith. prog.
      # terms 1 and 2; twin (p+2) will not have digits set by the query.
        if (empty($row['match_'])) {
            $row['match_'] = "category_id = $row[id]";
        }
        $query = "SELECT prime.digits as digits, prime.id as prime_id FROM prime, archival_tag
	WHERE prime_id=prime.id AND category_id=$row[id] AND archival_tag.rank_now=$limit
	AND archival_tag.subcategory='$row[subcategory]' AND $row[match_] AND prime.onlist > 1";
        $sth2 = lib_mysql_query($query, $db, "Error while asking status");
        $row2 = $sth2->fetch(PDO::FETCH_ASSOC);
        $digits = isset($row2['digits']) ? "<a href=\"/primes/page.php?id=$row2[prime_id]\">$row2[digits]</a>" : '(**)';
    }

    if (!isset($row2['digits'])) {
        $limit = '(**)';
    }
    $t_text .= lib_tr() . "<td class=\"text-right\">$digits</td>
	<td>$subcat</td>
	<td class=\"text-center\">$limit</td>
	<td class=\"text-center\">$row[count]</td></tr>\n";
}

// Query the table status to get the 'Rows', 'Update_time', 'Data_length',
// 'Avg_row_length', and 'Comment'.  (Which will be displayed on the
// bottom of the home page).

$query = "SHOW TABLE STATUS";
$stmt = lib_mysql_query($query, $db, "Error while asking status", $query);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $status[$row['Name']] = $row;
}
$v = $status['prime'];

$tolerated = preg_replace('/, $/', '', $tolerated);
$tolerated = "<blockquote class=wrapper>\n$tolerated\n</blockquote>\n";
$tolerated = "<p>Below are the comments that are currently tolerated in the
   official comment field, but which appear on the list only if the prime is
   already on the list for some other reason.  Note that provers can add
   unofficial comments that appear on the individual prime's page, but not in
   the official comment field.</p>\n$tolerated\n<p>The number in parenthesis
   is the number currently on the list.</p>\n";

$t_text .= "\n<tr><td colspan=4 class=\"text-left\">\n<div class=technote>\n
   &nbsp;&nbsp;&nbsp;&nbsp;(*)&nbsp;&nbsp; Less than the allowed number are known.<br>

   &nbsp;&nbsp;(**)&nbsp;&nbsp;These primes do not make the list on their own
   merits, but make the list because a companion prime does (e.g., a 'Twin (p+2)'
   will be on the list if and only if the associate 'Twin (p)' prime is.<br>

   (***)&nbsp; Database last updated: " . $v['Update_time'] .
   "</div>\n</td></tr></table>\n</blockquote>\n$tolerated\n\n";

$t_text .= "\n<h2 id='more'>$up Why are there more than allowed of some
   forms?</h2>\n\n<p>What? Sometimes there are more primes on the list than the
   number allowed for that form? This happens for the following two reasons.</p>

   <p>First, any prime in the top 5000 will automatically be archived, and
   sometimes there are many of the given form that fit there. When these primes
   get too small for the top 5000, they will be removed from the list. For example,
   we may not archive any of a certain form (such as generalized uniques), but
   there may be some on the list because they fit in the top 5000.</p>

   <p>Second, a prime outside of the top 5000 may remain on the list due to
   another comment. For example, for a long time the only Mills' prime on the
   list was one of the largest known ECPP primes. It was the latter comment
   that allowed it to remain on the list.</p>\n";

include("template.php");
