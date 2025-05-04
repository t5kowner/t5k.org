<?php

# Data for my page template

$t_title = "The Nth Prime Algorithm";
$t_subtitle = "A prime page by Booker, Carr, et al.";

$t_meta['description'] = "What is the n-th prime?  How many primes are
        less than n?  Here we offer describe the algorithm used on the
	n-th prime page to calculate these numbers.";
$t_meta['add_keywords'] = "nth prime, prime counting function, pi,
	algorithm, sieve";
$t_adjust_path = '../primes/';
$t_submenu = 'nth prime / algorithm';

$t_text = <<<HERE

<p>For small primes, namely those less than 30,000,000,000,000, we use the programs
and data provided by Andrew Booker (for large primes see below).  Here is his description:</p>

<blockquote>
<p>In order to find a prime quickly, the <i>n</i>th prime program uses a large
stored data table to get close to the right answer first, then finishes
with a relatively short computation.&nbsp;  To see how this works, imagine
the number line broken into bins, each of size <i>N</i>, i.e.  the first is
from 0 to <i>N</i>-1, the second from <i>N</i> to 2<i>N</i>-1, etc.&nbsp;  (In my implementation,
<i>N</i> equals 19219200.)&nbsp; In the table we store the number of primes in each
bin.&nbsp;  Then, to retrieve the <i>n</i>th prime, the program adds up the numbers
until the sum exceeds <i>n</i>.&nbsp;  We then know exactly which bin the <i>n</i>th prime
falls into.&nbsp;  The prime can then be found using a sieve algorithm on
that bin.</p>

<p>The sieve algorithm works as follows.&nbsp;  First, we compute a set of base
primes to be used in sieving.&nbsp;  The base primes consist of all the
primes up to the square root of the last number in the bin.&nbsp;  Second, we
keep an array (the sieve) which holds a flag byte for each number in
the bin.&nbsp;  (Actually, in practice the array is much smaller than the
size of a bin, so the bin is first broken into many sub-bins that are
sieved one at a time.)&nbsp;  We then "sieve" the bin by crossing out
(setting the flag byte for) the multiples of each base prime.&nbsp;  At the
end, the numbers that were not crossed out (did not have their flag
bytes set) are all the primes in the bin.&nbsp;  These primes are counted
until reaching our original number, n.&nbsp;  (For a better description of
sieve algorithms, see
<a href="/glossary/page.php?sort=SieveOfEratosthenes">
this entry</a> in 
<a href="/glossary/">
the Prime Glossary</a>.)</p>

<p>
Thus, if the bin size is small enough, the sieving can be done very
quickly.&nbsp;  In this way, most of the work in finding a prime was done in
computing the data table.&nbsp;  The table, in turn, was computed using a
modified sieve algorithm that is well suited to sieving many bins.&nbsp;  The
modified algorithm actually sieves many times, once for each residue
relatively prime to some number with many small prime factors.&nbsp;  (I used
30030 = 2*3*5*7*11*13.)&nbsp;  So, for example, in the first sieve, all of
the numbers have remainder 1 when divided by 30030, so instead of
having the flag bytes represent 0,1,2,... as in the standard sieve
algorithm, they represent 1,1+30030,1+2*30030,...&nbsp; This may sound like
it creates more work than before, but it turns out to be faster since
we only need to consider remainders which are relatively prime to
30030.&nbsp;  In this way, the multiples of 2,3,5,7,11, and 13 are
automatically eliminated, making the algorithm roughly 6 times faster.&nbsp;
What's more, the modified algorithm can be easily parallelized, as many
computers can each work on separate residues.&nbsp;  The table used by the
<i>n</i>th prime program was calculated over 9 hours by a lab of UltraSparcs
and SGIs.</p>

<a href="/nthprime/" class="btn btn-primary p-1">Return to the Nth Prime Page</a>
</blockquote>

<p>For larger primes (and selected smaller values) we use a data file supplied by
Andrey V. Kulsha:</p>

<blockquote>
<p>Andrey V. Kulsha has a file of the successive rounded values of 1.5*(&pi;(<i>x</i>)-li(<i>x</i>))
for the 100,000,000 multiples of 10<sup>9</sup> less than 10<sup>17</sup> (see 
<a href="http://www.primefan.ru/stuff/primes/table.html">the page on his table
here</a>).  He is still double checking these values, so for now our extension is just experimental.</p>

<p>In 2014, Andrew Carr and I converted these to actually differences (rather 
than successive differences) so we can find &pi;(x) for multiples of 10<sup>9</sup> 
with a single file read and a quick calculation of li(x).  For other values we use 
the nearest multiple of 10<sup>9</sup> and sieve the region between to find &pi;(x).  This is done using
<a href="http://primesieve.org/">primesieve</a> written by Kim Walisch.  This can be 
slow becuase our server is old, 32-bit,  and often busy verifying primes for our list
of the largest known primes.  (Contact Chris Caldwell if you want to donate a new one!)</p>

<p>For <i>p<sub>n</sub></i> we start with these terms of the asymptotic expansion 
for <i>p<sub>n</sub></i> [<a href="/references/refs.cgi/Cipolla1902">Cipolla1902</a>] </p>

<blockquote> <i>p<sub>n</sub></i> = <i>n</i> (log <i>n</i> + log log <i>n</i> - 1 + (log 
  log (<i>n</i>) - 2)/log <i>n</i> -
  ((log log (<i>n</i>))<sup>2</sup> - 6 log log (<i>n</i>) + 11)/(2 log<sup>2</sup>
  <i>n</i>)).
</blockquote>

<p>The error, O(<i>n</i>(log log <i>n</i> / log <i>n</i>)<sup>3</sup>)), still too large, so we apply Newton-Raphson iteration 
a couple times to find a solution to R(<i>x</i>)=<i>n</i>.  (Here R(<i>x</i>) is Riemann's prime counting function.) 
This will be comfortably within 10<sup>9</sup> of the correct value for our range.  
Now we calculate &pi;(x), iterate again to find the other end of a region to seive...  
Now we sieve as necessary to find the actual value of <i>p<sub>n</sub></i>.</p>

<p>We could think of no reason to extend the "random prime" link to beyond the first 1,000,000,000,000 primes.</p>

<a href="/nthprime/" class="btn btn-primary p-1">Return to the Nth Prime Page</a>
</blockquote>

HERE;

include("../primes/template.php");
