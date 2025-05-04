<?php

$t_meta['description'] = "Explanation of Terms on the Page Displaying a Single Prime from
        the database of the Largest Known Primes.";
$t_title = "Prime Puzzio: Mean Prime Gaps";
$t_meta['add_keywords'] = "definitions, primes, terms, largest known primes";
// $t_subtitle = "";

$t_text = <<<HERE
  <p>There are exactly 9 <a
href="/glossary/page.php?sort=PrimeGaps">gaps</a> and 18 <a
href="/glossary/page.php?sort=Composite">composites</a> below
29 (the 10<a href="/nthprime/">th</a>
prime number). Since 9 <a
href="/glossary/page.php?sort=Divides">divides</a> 18, we can
say the average (or mean) gap less than 29 is 2, i.e., an <a
href="http://mathworld.wolfram.com/Integer.html">integer</a>.
  <p> Here is a list of <i>record breaking <a
href="http://oeis.org/A049036">mean gaps</a></i>
which are integers:
  <p>
<pre>
  <i>n</i>                   <i>n</i>th prime             mean gap
  ------------------  ------------------    -----------------------------
  2                   3                      0 [Honaker, May 2000]
  10                  29                     2  "
  68                  337                    4  "
  438                 3061                   6 [Kok Seng Chua, May 2000]
  2616                23537                  8  "
  100350              1304539               12  "
  637198              9557957               14  "
  27066970            514272413             18  "
  179992840           3779849621            20  "
  55762149072         1505578024919         26 [Carmody, July 2003]
  382465573492        11091501631241        28  "
  2636913002950       81744303091421        30  "
  126979448983454     4444280714420857      34 [J. K. Andersen, April 2008]
  885992692751466     32781729631804207     36  &quot;
  43525513764814972   1784546064357413813   40  &quot;
  306268030480171300  13169525310647365859  42  &quot;
  ------------------  --------------------  -----------------------------
</pre>

  <p>
  <p>
Update: Phil Carmody has provided proof that odd mean gaps are
impossible.&nbsp;  Note that the mean gap <i>g</i> occurs
about exp(<i>g</i> + 2).
<!-- 1/(logx -1) = gaps - 1 -->
HERE;

$t_adjust_path = '../';
include("../template.php");
