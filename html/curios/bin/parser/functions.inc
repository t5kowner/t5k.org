<?php

# Defines the functions called by the parser

# For user defined functions, if they have one parameter, it is passed
# as a gmp integer.  If they have several, they are passed as a single
# array of gmp integers.

# Not really, here is the code:

#       if (!empty($match)) {
#         $left = $evaluator($match,$left,$right);
#       } else {
#         $left = $evaluator($left,$right);
#       }

# Note that the array $parser_fun keeps info about each function.  key = call
# (e.g., 'gcd(a,b)') and the value is a short explanation.

global $parser_fun;
$parser_fun = array();

# First a private routine used in others:

function is_gmp_even($a)
{
    return (gmp_cmp(gmp_and($a, 1), 0) == 0);
}


$parser_fun = array_merge($parser_fun, array('digits(n)' =>
  'The number of digits in n (plus one if negative).'));

function digits($a)
{
    return strlen(my_gmp_strval($a));
}


$parser_fun = array_merge($parser_fun, array('gcd(a,b)' =>
  'The greatest (hence positive) common divisor'));

function gcd($a)
{
    return gmp_gcd($a[0], $a[1]);
}


$parser_fun = array_merge($parser_fun, array('prptest(a)' =>
  '0 for a composite, 1 for a probable prime, 2 if a known prime'));

function prptest($a)
{
    return gmp_prob_prime($a);
}


$parser_fun = array_merge($parser_fun, array('u(n)'
    => 'The Fibonacci series: 0,1,1,2,3... [Linear(0,1,1,2,n)]'));
$parser_fun = array_merge($parser_fun, array('U(p,q,n)'
    => 'The general "Fibonacci" series: U(0) = 0, U(1) = 1,
	   U(<I>n</I>) = <I>p</I>V(<I>n</I>-1) - <I>q</I>V(<I>n</I>-2).
	   See <a href="/prove/prove3_2.html">proving 
	   primality section 3.2</a>. [Linear(0,1,p,p<sup>2</sup>-q,n)]'));
function u($arg_list)
{
    if (is_array($arg_list)) {
        $p = $arg_list[0];
        $q = $arg_list[1];
        $n = $arg_list[2];
        $p2 = gmp_mul($p, $p);
        return Linear(array( 0, 1, $p, gmp_sub($p2, $q), $n ));
    } else {
        return Linear(array(0,1,1,2,$arg_list));
    }
}


$parser_fun = array_merge($parser_fun, array('primU(*)'
    => 'Primitive part of the Fibonacci series'));
function primU($arg_list)
{
    if (is_array($arg_list)) {
        $p = $arg_list[0];
        $q = $arg_list[1];
        $n = $arg_list[2];
        $p2 = gmp_mul($p, $p);
        return primLinear(array( 0, 1, $p, gmp_sub($p2, $q), $n ));
    } else {
        return primLinear(array(0,1,1,2,$arg_list));
    }
}

$parser_fun = array_merge($parser_fun, array('v(n)'
    => 'The Lucas series: 2,1,3,4,7...'));
$parser_fun = array_merge($parser_fun, array('V(p,q,n)'
    => 'The general Lucas series: V(0) = 2, V(1) = <I>p</I>,
	   V(<I>n</I>) = <I>p</I>V(<I>n</I>-1) - <I>q</I>V(<I>n</I>-2).
	   See <a href="/prove/prove3_2.html">proving 
	   primality section 3.2</a>.'));
function v($arg_list)
{
    if (is_array($arg_list)) {
        $p = $arg_list[0];
        $q = $arg_list[1];
        $n = $arg_list[2];
        $p2 = gmp_mul($p, $p);
        return Linear(array( 2, $p, gmp_sub($p2, gmp_mul(2, $q)),
        gmp_mul($p, gmp_sub($p2, gmp_mul(3, $q))), $n ));
    } else {
        return Linear(array(2,1,3,4,$arg_list));
    }
}

$parser_fun = array_merge($parser_fun, array('primV(*)'
    => 'Primitive part of the Lucas series  [primU(p,q,2n)]'));
function primV($arg_list)
{
  #  primV is primU(...,2n)
    if (is_array($arg_list)) {
        $p = $arg_list[0];
        $q = $arg_list[1];
        $n = $arg_list[2];
        $p2 = gmp_mul($p, $p);
        return primLinear(array( 0, 1, $p, gmp_sub($p2, $q), gmp_mul(2, $n) ));
    } else {
        return primLinear(array(0,1,1,2,gmp_mul(2, $arg_list)));
    }
}

$parser_fun = array_merge($parser_fun, array('S(n)'
    => 'NSW series I: 1,1,3,7,17... [Linear(1,1,3,7,n)]'));
function S($n)
{
    return Linear(array(1,1,3,7,$n));
}

$parser_fun = array_merge($parser_fun, array('W(n)'
    => 'NSW series II: 0,1,2,5,12...  [Linear(0,1,2,5,n)]'));
function W($n)
{
    return Linear(array(0,1,2,5,$n));
}

$parser_fun = array_merge($parser_fun, array(
    'Linear(a0,a1,a2,a3,n)' => "(avoid this) If the linear arithmetic sequence
        satisfies a realtionship of the form a(n+1) = c0*a(n) + c1*a(n-1)  
        with c0, c1 integers; then Linear(a0,a1,a2,a3,<i>n</i>) is the <i>n</i>th term.
        For example, the Fibonacci numbers are Linear(0,1,1,2,<i>n</i>)."
));

function Linear($arg_list)
{
    include_once(basic_to_base() . 'bin/parser/linear.inc');
    return linear_Linear($arg_list);
}

$parser_fun = array_merge($parser_fun, array(
    'primLinear(a0,a1,a2,a3,n)' => "(Do not use!) The primitive of Linear, is rarely defined
	in a useful manner)"));

function primLinear($arg_list)
{
    include_once(basic_to_base() . 'bin/parser/linear.inc');
    return linear_primLinear($arg_list);
}

$parser_fun = array_merge($parser_fun, array(
    'C(n,r)' => 'The binomial coefficient "n choose r" [inefficient, 
	does n!/(r!*(n-r!))]'));

function C($a)
{
    return gmp_div(
        gmp_div(eval_fact($a[0]), eval_fact($a[1])),
        eval_fact(gmp_sub($a[0], $a[1]))
    );
}

### Test function!

function f($N)
{
    $factors = factor(my_gmp_intval($N));
    $delim = '';
    foreach ($factors as $p => $a) {
        print $delim . my_gmp_strval($p);
        if ($a > 1) {
            print "^$a";
        }
        $delim = '*';
    }
    return 1;
}

function factor($N)
{
  // Trial divides the positive integer N by the primes from 2 to max
  // Returns the first prime divisor found, or 0 if none found
  // Note: if N < max^2 is a prime, then N will be returned.

    $pwr = 0;
    while ($N % 2 == 0) {
        $N = $N / 2;
        $factors[2] = ++$pwr;
    }

    $pwr = 0;
    while ($N % 3 == 0) {
        $N = $N / 3;
        $factors[3] = ++$pwr;
    }

  // No need to go past the square root of our number
    $Stop = sqrt($N);

  // Okay, lets "wheel factor" alternately adding 2 and 4
    $di = 2;
    $i = 5;
    while ($i <= $Stop) {
        if ($N % $i == 0) {
            $pwr = 0;
            while ($N % $i == 0) {
                $N = $N / $i;
                $factors[$i] = ++$pwr;
            }
            $Stop = sqrt($N);
        }
        $i += $di;
        $di = 6 - $di;
    };
    if ($N > 1) {
        $factors[$N] = 1;
    }
    return $factors;
}
