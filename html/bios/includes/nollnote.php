
<?php
include_once('../bin/basic.inc');
$t_adjust_path = '../';

$t_title = "Curt Noll's Primes";
$t_text = <<< HERE
      The 25th and 26th Mersenne primes were found by Laura Nickel and Landon
      Curt Noll at age 18. Although both Noll and Nickel were in high school at 
      the time, they were both studying number theory under Dr. Lehmer (who developed 
      the modern test for Mersenne primes) and Dr. Jurca (a CSUH math professor). 
      Noll was also attending Cal State University at Hayward as a freshman. After 
      their results were confirmed by Dr. Lehmer and an abstract was received 
      by Math Comp, their press announcement was reported around the world (NBC 
      nightly news, BBC, Tass, ...). 
      <p>Noll and Nickel began their Mersenne prime search at M<sub>21000</sub> 
        using idle cpu time on the Cal State University Cyber 174. A primary motivation 
        for the search was the Noll-Nickel Mersenne island conjecture. Tuckerman, 
        the discoverer of M<sub>19937</sub> had stopped at M<sub>21000</sub>: 
        "barely on the beach" of a Mersenne island. In the optimistic words of 
        Dr. Lehmer that: 
      <blockquote>"happiness is just around the corner"</blockquote>
      Noll and Nickel discovered, on 30 Oct 1978, that Tuckerman's island contained 
      M<sub>21701</sub>. Using an improved search program, Noll independently 
      went on to show on 9 Feb 1979 that this same island also contained M<sub>23209</sub>. 
      <p>Between the M<sub>21701</sub> and M<sub>23209</sub> discovery, Noll engaged 
        in several exchanges with David Slowinski and Harry Nelson - co-discoverers 
        of M<sub>44497</sub>. Noll gave them extensive factoring tables, suggestions 
        on how to perform a fast modulus has well as his prediction that the next 
        Mersenne island was likely to be near the Mersenne island centered near 
        M<sub>44500</sub>. Slowinski and Nelson missed the discovery of M<sub>23209</sub> 
        by a few weeks. 
      <p>Noll's Mersenne prime searching days largely ended when he came to the 
        end of the Mersenne island at M<sub>24500</sub>. Even so, Noll continued 
        to investigate better methods for primality testing. Motivated by a conversation 
        with Gene Smith at a West Coast Number Theory conference, Noll helped 
        co-form the 'Amdahl 6' team of: 
      <blockquote> Joel Smith, John Brown, Landon Curt Noll, Bodo Parady, Gene 
        Smith and Sergio Zarantonello </blockquote>
      Using by Noll's observation that: 
      <blockquote> Even though the Lucas-Lehmer test is the most efficient known 
        definitive test for large primes, searching for Mersenne primes is not 
        the most efficient way to discover a new largest known prime </blockquote>
      the 'Amdahl 6' team developed a general primality search method for primes 
      of the form <i>k</i>*2^<i>n</i>+/-1. On 6 Aug 1989 the 'Amdhal 6', using 
      an Amdahl 1200 proved that 391581*2^216193-1. At the time of discovery, 
      it was the largest known prime and is still the largest known non-Mersenne 
      prime (as of March 1996). 
      <p>The 'Amdahl 6' team went on to discover, 663777*2^7650+/-1, 571305*2^7701+/-1 
        and 1706595*2^11235+/-1; each of which was a 'largest known twin prime' 
        at the time of discovery. The 'Amdahl 6' also contributed many large Titanic 
        primes including what they like to call the "largest easy to remember 
        prime": 235235*2^70000-1. :-) 
      <p>Laura Nickel is now known as Ariel Glenn and is rumored to be somewhere 
        at NYU. Brown, Noll and Zarantonello currently consume large number cpu 
        cycles while working for Silicon Graphics (who now owns Cray Research). 
        Noll is also the Vice Mayor of Sunnyvale. Gene Smith is doing post-doc 
        mathematics work, Bodo Parady works for Sun Microsystems and Joel Smith 
        works for Amdahl. 
	<P>(This page was written in 1995)
HERE;

include("../template.php");
