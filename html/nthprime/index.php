<?php

# An interface to Andrew Bookers programs nthprime and piofx.  The variables
# that can be set are 'n' (calls nthprime with n) 'x' (calls piofx with x)
# or 'random' (calls ntprime with the string 'random').

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

# Register the form variables:

$my_variables_digits       = '()';
$my_variables_alphanumeric = '(random)';
$my_variables_self_tested  = '()';
$my_variables_general      = '(n|x)';  # Example: Proth.exe
security_scrub_variables($my_variables_digits, $my_variables_alphanumeric, $my_variables_self_tested, $my_variables_general);

# The idea is simple, clean every variable in $_REQUEST, includes all get, post
# and cookie info past from the browser. Before calling security_scrub_variables
# define these strings (use in a preg_match): (examples)
#
#  $my_variables_digits       = '(curio_id|edit|number_id|deleted_id|rank)';
#  $my_variables_alphanumeric = '(short|showall)';
#  $my_variables_self_tested  = '()';
#  $my_variables_general      = '(submitter)';  # Example: Proth.exe
#
# Any unlisted variable will be set to ''.  General just removes these
# charachets < > [  ] / , : ; " ' { },  Self_tested are ignored and passed as
#  is.
#
#  Note: useless unless it comes before grabbing the variables from $_REQUEST

function security_scrub_variables(
    $my_variables_digits = '()',
    $my_variables_alphanumeric = '()',
    $my_variables_self_tested = '()',
    $my_variables_general = '()'
) {
  # Scrub the user input
    foreach ($_REQUEST as $a => $b) {
      # First the white-listed variables of the main categories
      # digits only
        if (preg_match('/^' . $my_variables_digits . '$/', $a)) {
            if (!empty($b) and  preg_match('/[^\d]/', $b)) {
                $c = preg_replace('/[^\d]/', '', $b);
              ##### error_log("scrubbed $a=$b to digits $c in $_SERVER[PHP_SELF]", 0);
                $_REQUEST[$a] = $c;
            }
        } elseif (preg_match('/^' . $my_variables_alphanumeric . '$/', $a)) {
            if (!empty($b) and  preg_match('/[^\w]/', $b)) {
                $c = preg_replace('/[^\w]/', ' ', $b);
                error_log("scrubbed $a=$b to alphanum $c in $_SERVER[PHP_SELF]", 0);
                $_REQUEST[$a] = $c;
            }
        } elseif (preg_match('/^' . $my_variables_general . '$/', $a)) {
            if (!empty($b) and  preg_match('/[<\/:;"\'[{]/', $b)) {
                $c = preg_replace('/[<>\[\]\/,:;"\'{}]/', ' ', $b);
                error_log("scrubbed $a=$b to $c in $_SERVER[PHP_SELF]", 0);
                $_REQUEST[$a] = $c;
            }
        } elseif (!preg_match('/^' . $my_variables_self_tested . '$/', $a)) {
           # Be silent only about cookies
            if (empty($_COOKIE{$a})) {
                error_log("removed variable $a=$b in $_SERVER[PHP_SELF]", 0);
            }
            $_REQUEST[$a] = '';
        }
    }
}







# Data for my page template

$t_title = "The Nth Prime Page";
$t_subtitle = "A prime page by Andrew Booker";
$t_meta['description'] = "What is the n-th prime?  How many primes are
	less than n?  Here we offer the answer for all small values of n
	using a sparse list and a seive..";
$t_meta['add_keywords'] = "nth prime, prime counting function, pi";
$t_adjust_path = '../primes/';

# Now we will build the page text in $t_text.

# First though, how will we emphasize the answer?

function wrap($text)
{
    return "<table borderColor=#0099cc border=2 cellpadding=5>
	<tr><td><B>$text</B></td></tr></table>";
}

########################################################################
# First: The nth prime
########################################################################

$n = (isset($_REQUEST['n']) ? $_REQUEST['n'] : '');
$nth = '';
if (!empty($n)) {
    putenv("QUERY_STRING=n=$n");
    $nth = shell_exec("/var/www/html/nthprime/nthprime");
    $nth = preg_replace('/^.*<body>/s', '', $nth);
    $nth = preg_replace('/<hr>.*$/s', '', $nth);
    $nth = wrap($nth);
}

$t_text = <<<HERE
<p>Welcome to the Nth Prime Page!</p>

<h3 class="mt-4" id="nth">Nth prime</h3>
   <p>Here's how it works: Enter a value for <i>n</i> below,
from 1 to 10<sup>12</sup>, inclusive.&nbsp;  The server will return the
<i>n</i>th prime number (counting 2 as the first).&nbsp;  Commas and
scientific notation (e.g. 1.0e12) are allowed.&nbsp;  For example,
entering either 1,000,000,000,000 or 1.0e12 will tell you '<code>The
1,000,000,000,000th prime is 29,996,224,275,833.</code>'&nbsp; Depending
on the load of the server and the number you pick, your query may take up
to 10 seconds.</p>

<blockquote>
  $nth
  <form method=POST action="/nthprime/index.php#nth">
    <input type="text" name="n" size="20" maxlength="18" value="$n">
    <input type="submit"  class="btn btn-primary py-2">
  </form>
</blockquote>
HERE;

########################################################################
# Second: pi(x)
########################################################################

$x = (isset($_REQUEST['x']) ? $_REQUEST['x'] : '');
$pi_x = '';
if (!empty($x)) {
    putenv("QUERY_STRING=x=$x");
    $pi_x = shell_exec("/var/www/html/nthprime/piofx");
    $pi_x = preg_replace('/^.*<body>/s', '', $pi_x);
    $pi_x = preg_replace('/<hr>.*$/s', '', $pi_x);
    $pi_x = wrap($pi_x);
}

$t_text .= <<<HERE
<h3 class="mt-4" id="piofx">Pi function</h3>
  <p>Enter a value for <i>x</i> below, from 1 to
3*10<sup>13</sup>.&nbsp;  The server will return pi(<i>x</i>), the number
of primes not exceeding <i>x</i>.&nbsp;  For example, entering
29,996,224,275,833 will tell you '<code>There are 1,000,000,000,000 primes
less than or equal to 29,996,224,275,833.</code>'</p>

<blockquote>
  $pi_x
  <form method=POST action="/nthprime/index.php#piofx">
    <input type="text" name="x" size="20" maxlength="18" value="$x">
    <input type="submit" class="btn btn-primary py-2">
  </form>
</blockquote>
HERE;

########################################################################
# Third: random prime
########################################################################


$random = (isset($_REQUEST['random']) ? $_REQUEST['random'] : '');
$nth = '';
if (!empty($random)) {
    putenv("QUERY_STRING=n=random");
#  $nth = shell_exec("/home/caldwell/html/nthprime/nthprime");
    $nth = shell_exec("/var/www/html/nthprime/nthprime");
    $nth = preg_replace('/^.*<body>/s', '', $nth);
    $nth = preg_replace('/<hr>.*$/s', '', $nth);
    $nth = wrap($nth);
}

$t_text .= <<<HERE
<h3 class="mt-4" id="random">Random prime</h3>
  <p>Click below to get a "random" prime chosen from the
first 10<sup>12</sup> primes:</p>

<blockquote>
  $nth
  <form method=POST action="/nthprime/index.php#random">
    <input type="submit" name="random" value="random_prime"  class="btn btn-primary py-2">
  </form>
</blockquote>
HERE;

$t_submenu = 'nth prime';
$t_text .= <<<HERE
<h3 class="mt-4">Algorithm</h3>
  For a description of the algorithm used, <a href="algorithm.php"> click
here</a>.&nbsp; The text of this page, the programs, and all of the
necessary data sets were provided by Andrew Booker.

HERE;

#require_once("../primes/bin/lib.inc");   ##### THIS DOES NOT BELONG HERE-FAILED WITHOUT IT 6/2020
include("../primes/template.php");
