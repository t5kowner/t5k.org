<?php

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

// This page should show the number of digits necessary for each form to make the list

// Okay, lets start filling in the template variables

$t_meta['description'] = "This page shows the size a prime must be to make the
	list of largest known primes in the various subcategories
	including twin, primorial, factorial, Woodall, Sophie Germain, Mersenne,
	and many other types of primes...";
$t_title = "How Fast are the Primes Growing?";
$t_submenu = "Trends (<a href=\"#intro\" class=\"breadcrumb-item active d-print-none\">Introduction</a>
	<a href=\"#primal\" class=\"breadcrumb-item active d-print-none\">A Primal Moore's Law</a>)";

# An icon linked to the top of the page:
$up = "<a class=up HREF=\"#top\"><img src=\"/top20/includes/gifs/up3.gif\"" .
        "  WIDTH=14 HEIGHT=14 ALT=\"(up)\"></a>";

$The5000th = lib_get_column('prime.rank = 5000', 'prime', 'digits', $db);
$t_text = "<h2 id='introduction'>$up Introduction</h2>
   <p id='top'>The Prime Pages keeps a list of the 5000 largest known primes, plus a
   few each of certain selected archivable forms and classes. These forms are
   defined in this collection's home page.

   To make the top 5000 today a prime must have $The5000th digits. This is
   increasing at roughly 50,000 digits per year.

   As can be seen in the logarithmic <a href='#graph'>graph below</a>, the
   number of digits in the nth largest known prime is vaguely linear with time.
   So there is a nearly exponential growth in the rate at which the number of
   digits is growing each year!</p>

   <img id='graph' src=\"/top20/includes/gifs/5year.png\"
   alt=\"Graph showing digits in nth year over last five years\"
   class=\"img-fluid mx-auto d-block\">

   <p>(Graphic originally by Phil Carmody.)</p>
";

$t_text .= "\n<h2 id='primal'>$up A Primal Moore's Law</h2>\n";

$t_text .= "

<p>In 1965, Gordon Moore, a co-founder of Intel, was asked to write an article predicting the development of
semiconductor industry for the next decade. Moore used the data from the previous six years to predict that the
number of components on the chips with the smallest manufacturing costs per component would double roughly every
year. In 1975 he reduced the estimate to doubling every two years. (The current rate seems to be closer to doubling
every four years.)</p>

<p>Later, an Intel colleague combined Moore's Law with the fact that clock speeds were increasing, to conclude that
<i>computing power</i> is doubling every 18 months. This power form of Moore's law may be the most common in circulation
today, even though it did not originate with Moore himself.  Recent prognosticators have restated Moore's law in an
economic form: the cost of computing power halves every <i>x</i> months.</p>

<img src=\"/gifs/From1945.png\" alt=\"log log digits in largest prime from 1945 to present\"
     class=\"img-fluid mx-auto d-block\">

<p>It seems reasonable (from graphs like those above and on the side) to assume a power form of Moore's law applies
search for large primes:

<blockquote>The computing power devoted to finding large primes is is increasing exponentially.</blockquote>

<p>This increase in power could come from the increased transistor density, from faster clock speeds, from
cheaper component costs, or from the aggregation of computing power into organized Internet projects like GIMPS and
BOINC.</p>

<p>As we have discussed elsewhere on this pages, the amount of time required to find a prime the same size as
<i>n</i> is about</p><blockquote>O((log <i>n</i>)<sup>3</sup> log log <i>n</i>).</blockquote><p>So
if the computing power
available for seeking primes doubles every <i>k</i> months, then the size of the largest known prime should double
every 3<i>k</i> months.  The slope 0.079 (over past 60 years) corresponds to doubling the digits every 3.8 years, or
46 months. So a quantified form of the primal Moore's law might be:</p>

<blockquote>The computing power available for seeking primes doubles every 16 months.</blockquote>
";

include("template.php");
