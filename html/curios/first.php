<?php

# The templates uses $t_text for the text, $t_title...
$t_title = "First missing Curio!";
$t_meta['description'] = "What is the least prime without a Prime Curio! on our site?
   On this page we let you know, and then 'prove' there are prime curios
   for every integer--we just have not found them yet.";

  // The first missing curio must be between these two values!

  $start_search =  3000;
  $stop_search  = 30000;

  // Search the database for integers between those values

//  WAIT!!! This does not spot number missing from the database!

  include("bin/basic.inc");
  $db = basic_db_connect(); # Connects or dies
  $query = "SELECT numbers.short,
        COUNT(IF(curios.visible = 'yes',1,NULL)) as visible, COUNT(*) as count
        FROM numbers, curios
        WHERE FLOOR(short) = short AND curios.number_id = numbers.id
		AND short < $stop_search AND short > $start_search
        GROUP BY numbers.id
        ORDER BY sign, log10 ";
  $sth = lib_mysql_query($query, $db, "failed to query database");

  // Convert this to arrays $count and $invisible (the number of
  // each in the databas for that number = short).

while ($myrow = $sth->fetch()) {
    if (isset($count[$myrow['short']])) {
        $count[$myrow['short']] += $myrow['visible']; // Could be duplicates... bad eh?
    } else {
        $count[$myrow['short']] = 1;
    }

    if (isset($invisible[$myrow['short']])) {
        $invisible[$myrow['short']] += $myrow['count'] - $myrow['visible'];
    } else {
        $invisible[$myrow['short']] = 1;
    }
}

  // Now lets find what is missing!

  $max = 15;
$j = 0;
for ($i = $start_search + 1; $i <= $stop_search; $i++) {
    if (is_prime($i)) {
        if (empty($count[$i])) {
            if (empty($first)) {
                $first = $i;
            }
            $missing[$j] = $i;
          # $invisible[$i];
            if ($j++ >= $max) {
                break;
            }
        }
    }
}

  // Make a table

  $table = "\n" . '<table class="brdr td4">' . "\n";
  $width = 5;
for ($i = 0; $i * $width < $max; $i++) {
    $table .= "<tr>";
    for ($j = 1; $j <= $width; $j++) {
        $table .= "<td>" . $missing[$i * $width + $j] . "</td>";
    }
    $table .= "</tr>";
}
  $table .= "</table>";

  // $i is now the first missing curio number!

# Lets build the page in the variable $t_text

$t_text = "<p>We have presented prime curios for hundreds of integers, but still have missed
so many!&nbsp;  The first prime number which is missing a prime curio is</p>

<div class=\"row h-100 justify-content-center my-3\">
  <div class=\"col-auto my-auto btn btn-deep-orange\">$first</div>
  <div class=\"col-2 my-auto text-center\">followed by</div>
  <div class=\"col-auto\">$table</div>
</div>

<p>Does that mean there is no prime number related curiosity about this integer?</p>


<p>No, just that we have not found one worthy of inclusion yet.&nbsp;  In fact, below is a
proof (okay, a joke proof), that every positive integer has an associated prime curio.&nbsp;
So if you know a great curio for $first, please submit it today!</p>

<p>First we need a definition.&nbsp;  We will be a little stronger than <a
href=\"http://www.m-w.com/cgi-bin/dictionary?book=Dictionary&amp;va=curio\"
target=\"_blank\">Merriam-Webster's definition of curio</a> and make our curios short:</p>

<blockquote>A prime curio about <i>n</i> is a novel, rare or bizarre
 statement about primes involving <i>n</i> that can be typed using at most 100
keystrokes.</blockquote>

<div class=\"highlight my-3\" id=\"theorem\"><b>Theorem:</b> Every positive integer <i>n</i> has an associated
prime curio.</div>

<p><b>\"Proof\":</b> Let S be the set of positive integers for which there is no associated prime
curiosity.&nbsp;  If S is empty, then we are done.&nbsp;  So suppose, for <a target=\"_blank\"
href=\"http://mathworld.wolfram.com/ReductioadAbsurdum.html\">proof by contradiction</a>, that S is not
empty.&nbsp;  By the <a href=\"http://mathworld.wolfram.com/WellOrderingPrinciple.html\"
target=\"_blank\">well-ordering principle</a> S has a least element, call it <i>n</i>.&nbsp; Then
<b><i>n</i> is the least positive integer for which there is no associated <u>prime</u> curio</b>.&nbsp;
But our last statement is a prime curio for <i>n</i>, a contradiction showing S does not have a least
element and completing the proof.</p>

<p>(For further discussion of this pseudo-proof, see the page <a
href=\"includes/paradox.php\">a Curious Paradox</a>.)</p>

";
include("template.php");

function is_prime($n = 0)
{
    $primes = array (  2 , 3,  5,  7, 11, 13, 17, 19, 23, 29,
          31, 37, 41, 43, 47, 53, 59, 61, 67, 71,
          73, 79, 83, 89, 97,101,103,107,109,113);
    if ($n < 2) {
        return false;
    }
    if ($n == 2) {
        return true;
    }
    $sqrt = sqrt($n);
    foreach ($primes as $p) {
        if ($n % $p == 0) {
            return false;
        }
        if ($sqrt < $p) {
            return true;
        }
    }
    lib_my_die("$n too Large for is_prime");
}
