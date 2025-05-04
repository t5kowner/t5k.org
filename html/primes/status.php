<?php

# First, get the variables, if any, from the form.  `hours' is how long to look back in time.
# 'edit' is set if an editor is looking at the page.

if (isset($_REQUEST['hours']) and preg_match('/(\d+)/', $_REQUEST['hours'], $temp)) {
    $hours = $temp[1];
} else {
    $hours = 72;
}

# Start printing the page.

$t_title = "Prime Verification Status";
$t_text = "<p>Before primes are added to the List of Largest Known Primes, they
  must be first be verified, comments must be checked and they must meet the
  <a href=\"../top20/sizes.php\">size requirements</a>.&nbsp;  Below we show the
  status of these primes (if any) that are awaiting verificiation (of any age)";
if (!empty($hours)) {
    $t_text .= " as well as those
  modified (for any reason) in the last $hours hours";
}
$t_text .= ".&nbsp;  Click on the prime's id for more detailed information.&nbsp;
  The color code is at the bottom of the page.</p>";
$t_submenu = 'status';

include_once("bin/basic.inc");
include_once("bin/ShowPrimes.inc");
$db = basic_db_connect(); # Connects or dies

$time = time();
# COUNT( ) counts non-null entries, so to seek those with visible comments I have
# COUNT(IF(visible='yes','yes',NULL)); but perhaps the left join makes this unnecessary?

$query = "(SELECT prime.*, COUNT(IF(visible='yes','yes',NULL)) AS by_user FROM prime LEFT JOIN comment
	ON prime.id=comment.prime_id
	WHERE prime IN (1,2,3)
	GROUP BY prime.id)
  UNION
    (SELECT prime.*, COUNT(IF(visible='yes','yes',NULL)) AS by_user FROM prime LEFT JOIN comment
	ON prime.id=comment.prime_id
	WHERE list='Top 5000' AND prime.modified > DATE_SUB(NOW(),INTERVAL $hours HOUR)
	GROUP BY prime.id)
  ORDER BY log10 DESC LIMIT 10000";

# was ORDER BY log10 DESC, rank LIMIT 10000";  but that failed

$query_time = array_sum(explode(" ", microtime()));
$sth = lib_mysql_query($query, $db, 'Invalid query in this page view, contact the admin');
$query_time = array_sum(explode(" ", microtime())) - $query_time;

# Get the rows
$count  = 0;
$options['link rank'] = (empty($edit) ? 'yes' : $edit);
$options['color'] = 'yes';
$options['color_legend'] = 'yes';
$options['add links'] = 'yes';
$options['id'] = 'yes';
$options['description'] = 'MakePretty';

$t_text .= ShowHTML('head', $options);
while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    $t_text .= ShowHTML($row, $options);
    $count++;
}
$t_text .= ShowHTML('tail', $options);   # Prints ending info...

$t_text .= parser_legend();


$t_text .= "<h4 class=\"mt-5\">Modify this display:</h4>
  <blockquote><form action=\"$_SERVER[PHP_SELF]\">
    Include those modified (for any reason) in the last
    <input type=text size=3 name=hours value='$hours'>
    hours.  (Use 0 to just see those in process or awaiting verification.)
    <input type=\"submit\" value=\"SEARCH\" class=\"btn btn-primary p-2\">
  </form></blockquote>
";


# Add a technical note at the bottom about the size a prime must be.

$The5000th = lib_get_column('prime.rank = 5000', 'prime', 'digits', $db);

$t_text .= "<div class=\"technote p-2 my-5\">We at the PrimePages attempt to keep a list
   of the 5000 largest known primes plus a few each of certain selected
   <a href=\"../top20/home.php#archivable\" class=none>archivable forms</a>.&nbsp;
   To make the top 5000 <i>today</i> a prime must have $The5000th digits or meet
   the <a href=\"../top20/sizes.php\" class=none>size requirements</a> for it's
   archivable form.&nbsp; (Query time: " . round($query_time, 6)
   . " seconds.)</div>";

include("template.php");

function parser_legend()
{
    $out = '<blockquote class="m-5"><table class="td2">
    <tr class="' . $GLOBALS['mdbmedcolor'] . '"><th colspan=2 class="font-weight-bold text-center">Description Notation</th></tr>' . "\n";
    $out .= lib_tr() . "<td class=\"text-center font-weight-bold\">\\</td>
	<td class=\"pl-2\">back-quote (23\\ 45 = 2345, used to allow long integers to line wrap)</td></tr>\n";
    $out .= lib_tr() . "<td class=\"text-center font-weight-bold\">#</td>
	<td class=\"pl-2\"><a href=\"/glossary/page.php?sort=Primorial\">primorial</a> (9# = 7*5*3*2)</td></tr>\n";
    $out .= lib_tr() . "<td class=\"text-center font-weight-bold\">!, !<sub><i>n</i></sub></td>
 	<td class=\"pl-2\"><a href=\"/glossary/page.php?sort=Factorial\">factorial</a>,
	<a href=\"/glossary/page.php?sort=MultifactorialPrime\">multifactorial</a></td></tr>\n";
    $out .= lib_tr() . "<td class=\"text-center font-weight-bold\">Phi(<i>n</i>,<i>x</i>)</td>
   <td class=\"pl-2\"><i>n</i>th cyclotomic polynomial evaluated at <i>x</i></td></tr>\n";
    $out .= "</table></blockquote>";
    return($out);
}
