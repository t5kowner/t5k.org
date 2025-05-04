<?php

include('/var/www/html/curios/bin/basic.inc');

$text = '';
# First, seek a nume rin the URL, is that what they want?
if (preg_match("/(\d+)/", $_SERVER['REDIRECT_URL'], $matches)) {
    $short = $matches[1];
    $db = basic_db_connect(); # Connects or dies
    if (lib_rows_in_table('numbers', $db, $where = "short='$short'") > 0) {
        $short = "page.php/$short.html";
        $text = "<P>The file you want <i>could</i> be
    <blockquote><a href=\"/curios/$short\">" .
        "https://t5k.org/curios/$short</a></blockquote>";
    }
}

$t_adjust_path = "/curios/";
# Not: basic_to_base($_SERVER[REDIRECT_URL]); it oftenfails with long ugly paths
# like https://primes.utm.edu/~caldwell/curios/page.php/includes/includes/includes/page.php

$t_title = "<font color=red>Page not found!</font>";
$t_subtitle = "(HTTP 404 - File not found)";
$temp = '';

# Next, does it have the now removed file.php in it?
if (preg_match('/file.php$/', $_SERVER['REDIRECT_URL'])) {
    $temp = <<<HERE
     <P>Are you looking for<ul>
       <li><a href="/curios/puzzios/">Prime Puzzios</a>
       <li><a href="/curios/includes/primetest.php">Test Numbers for Primality</a>
       <li><a href="/curios/includes/copyright.php">Our Copyright Notice</a>
       <li><a href="/curios/includes/palindromic_zipcode.php">Palindromic Zipcode</a>
       <li><a href="/curios/includes/FAQ.php">Our FAQ</a> (Frequently Asked Questions)
       <li><a href="/curios/includes/guidelines.php">Guidelines</a> (Guidelines for Submission)
       <li><a href="/curios/includes/paradox.php">Paradox</a> (Every Prime is Curious?)
     </ul>
HERE;
}

$t_text = "<img align=right src=\"/curios/includes/bug_12.gif\" alt=wasp
  title=\"Stings, don't it?\">
  It looks like you were seeking a file
  <blockquote>http://$_SERVER[HTTP_HOST]" . htmlentities($_SERVER['REDIRECT_URL']) . "</blockquote>
  which does not exist.&nbsp;
  $text
  <P>You might try our <span class=highlight>complete index</span>
  or <span class=highlight>search features</span>, both can be
  accessed by the links on the side menu.
  $temp";

if (!empty($_SERVER["HTTP_REFERER"])) {
    $t_text .= "<P>Please tell the owner of the page where you found this
	link ($_SERVER[HTTP_REFERER]?)about this problem.";
}

include("../template.php");
