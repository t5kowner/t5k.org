<?php

include('../bin/basic.inc');

foreach ($_REQUEST as $a => $b) {   # First, block standard evil access
    if (
        !empty($b) and
        ( !is_scalar($b) or preg_match('/\/\.\.\/\.\.\/\.\.\//', $b) or preg_match('/\/etc\/passwd/', $b) )
    ) {
        header("HTTP/1.0 404 Not Found");
        error_log("blocked $a=$b in $_SERVER[PHP_SELF]", 0);
        exit;
    }
}

$t_meta['description'] = "A routine to find test numbers for primality.";
$t_title = "A Primality Test";
$t_meta['add_keywords'] = "definitions, primes, terms, largest known primes";
// $t_subtitle = "";

$t_text = <<<HERE

<SCRIPT>
// The largest integer Java natively supports is 2^53-1, so these
// routines are designed to work for *positive* integers up to that.

// Currently the function check does the idiot proof to see only positive
// integers (not too large) are passed to the other routines.


// trial_divide(N,max) uses trial division to seek the smallest
// prime divisor of N, returns 0 if none found.

function trial_divide(N,max) {
  // Trial divides the positive integer N by the primes from 2 to max
  // Returns the first prime divisor found, or 0 if none found
  // Note: if N < max^2 is a prime, then N will be returned.
  if (N%2 == 0) return 2;
  if (N%3 == 0) return 3;
  // No need to go past the square root of our number
  var Stop = Math.min(Math.sqrt(N),max);
  // Okay, lets "wheel factor" alternately adding 2 and 4
  var di=2;
  for(i=5; i<=Stop; i+=di, di=6-di) {
    if (N%i == 0) return i;
  };
  if (N >= max*max) return 0;
  return N;
}


// modmult(a,b,N) finds a*b (mod N) where a, b, and N can be
// up to (2^53-1)/2.  Might up this to 2^53-1 eventually...

  function modadd(a,b,N) {
  // When the integers a, b satisfy a+b > 2^53-1, then (a+b)%N is wrong
  // so we add this routine to allow us to reach a, b = 2^53-1.
    if (a+b > 9007199254740991) {
      // Could reduce a and b (mod N) here, but assuming that has already been done
      // won't hurt if not... subtract 2^52 from one, 2^52-1 from the other and the
      // add it back modulo N (MaxInt+1)
      var t = ( (a-4503599627370496) + (b-4503599627370495) )%N;
      return ( t + (9007199254740991 % N) );
    }
    // Usual case: a + b is not too large:
    return ( (a+b)%N );
  }

function modmult(a,b,N) {
  if (a > N) a = a%N;
  if (b > N) b = b%N;
  if (a*b <= 9007199254740991) {
    return ((a*b)%N);
  } else {
    if (b > a) return modmult(b,a,N);

    // Right to left binary multiplication
    var t = 0;
    var f = a;
    while (b > 1) {
      if ((b & 1) == 1) t = modadd(t,f,N);
      b = Math.floor(b/2);
      f = modadd(f,f,N);
    };
    t = modadd(t,f,N);
    return t;
  }
}

// modpow(a,exp,N) finds a^exp (mod N) where a, b, and N are
// limited by modmult

function modpow(a,exp,N) {
  if (exp == 0) return 1;

  // Right to left binary exponentiation
  var t = 1;
  var f = a;
  while (exp > 1) {
    if ((exp & 1) == 1) {  // if exponent is odd
      t = modmult(t,f,N);
    }
    exp = Math.floor(exp/2);
    f = modmult(f,f,N);
  };
  t = modmult(t,f,N);
  return t;
}

// SPRP(N,a) checks if N (odd!) is a strong probable prime base a
// (returns true or false)

function SPRP(N,a) {
  var d = N-1; s = 1;  			// Assumes N is odd!
  while ( ((d=d/2) & 1) == 0) s++;	// Using d>>1 changed the sign of d!
  // Now N-1 = d*2^s with d odd
  var b = modpow(a,d,N);
  if (b == 1) return true;
  if (b+1 == N) return true;
  while (s-- > 1) {
    b = modmult(b,b,N);
    if (b+1 == N) return true;
  }
  return false;
}

// The idiot proofing, answer returning script

function check(){
  var TrialLimit = 1300; // Should be bigger, like 10000
  var N = document.primetest.input.value;
  var Result = "Unknow error";
  var a;

  if (N > 9007199254740991) {
    Result = "Sorry, this routine will only handle integers below 9007199254740991 "+
	"(try one of the links below).";
  } else if (N == 1) {
    Result = "The number 1 is neither prime or composite (it is the multiplicative identity).";
  } else if (N < 1) {
    Result = "We usually restrict the terms prime and composite to positive integers";
  } else if (N != Math.floor(N)) {
    Result = "We usually restrict the terms prime and composite to positive integers";
  } else {
    // Okay, N is of a resonable size, lets trial divide
    window.status = "Trial dividing " + N + " to " + TrialLimit + ".";
    i = trial_divide(N,TrialLimit);
    if (i > 0 && i != N) {
      Result = N+" is not a prime! It is "+i+" * "+N/i;
    } else if (N < TrialLimit*TrialLimit) {
      Result = N+" is a (proven) prime!";
    } else if ( SPRP(N,a=2) && SPRP(N,a=3) && SPRP(N,a=5) && SPRP(N,a=7)
    		&& SPRP(N,a=11) && SPRP(N,a=13) && SPRP(N,a=17)) {
      // Some of these tests are unnecessary for small numbers, but for
      // small numbers they are quick anyway.
      if (N < 341550071728321) {
        Result = N + " is a (proven) prime.";
      } else if (N == 341550071728321) {
        Result = N+" is not a prime! It is 10670053 * 32010157.";
      } else {
        Result = N + " is probably a prime (it is a sprp bases 2, 3, 5, 7, 11, 13 and  17).";
      };
    } else {
      Result = N+" is (proven) composite (failed sprp test base "+a+").";
    };
  };

  window.status= "Done!"; // here so says done when present alert box
  alert(Result);
}
</SCRIPT>

<p>Do you have an integer you would like
to test for primality?  If it is small (say less than <a
href="/curios/page.php/9007199254740881.html">9007199254740991</a> = 2<sup>53</sup> -
1), then try this script:</p>

  <FORM name=primetest class="alert-warning p-3 m-3 w-75" action="/curios/includes/primetest.php" onsubmit="return false">
    Is <INPUT size=16 name=input> prime?
    <INPUT onclick="check()" type=button value="Check!">
  </FORM>

<p>For larger numbers try Dario Alpern's exceptional on-line routine to <a
href="https://www.alpertron.com.ar/ECM.HTM">factor and prove primality</a>.
Other useful links include</p>

<ul>
  <li>The Prime Glossary's definition: <a href="/glossary/page.php/PRP.html">Probable-Prime</a>
  <li>The Javascript program above is based on the page <a href="/prove/prove2_3.html"> Strong
probable-primality and a practical test</a>.
  <li><a href="/notes/prp_prob.html"> Probable primes--how probable?</a>
</ul>

HERE;

$t_adjust_path = '../';
include("../template.php");
