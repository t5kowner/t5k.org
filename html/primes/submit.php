<?php

session_start();
if (file_exists("/var/www/html/TESTSITE")) {
    error_reporting(E_ALL);
}
# show all errors on text pages

$t_banner_message_all_submit_pages = '';

# Submission part one: get prover (verify via password), code (check against
# prover's code list), and primes (parse and canonicalize).  Once approved, a
# button will allow them to proceed to part two (submit2.php): actual
# submission.

# This page allows the user to begin to submit one or more primes.  Though it
# stands alone, the plan is that folks will get here from bio/page.php so the
# first two fields will be filled and they will only need to get authorized.
# When done, should mail and log entry.

include_once("bin/basic.inc");
include_once("bin/log.inc");
$db = basic_db_connect();   # Connects or dies

include_once('bin/http_auth.inc');
if (my_is_ip_blocked()) {
    lib_die("You may not submit entries while your IP is blocked.", 'warning');
}

# The html page to be sent to the browser is built in the global variable $out.

# If $xx_person_id is set (it is imbedded in the form when
# bin/get_the_person.inc succeeds), we should make sure they are authorized.
# This must come first as it alters the http headers!

$xx_person_id = (isset($_REQUEST['xx_person_id']) ? $_REQUEST['xx_person_id'] : '');
if (preg_match('/^\d+$/', $xx_person_id)) {
  ### include_once('bin/http_auth.inc');
    $is_authorized = my_auth($xx_person_id, 'log errors');
  # See note below where this is used
}

# Each form field has a routine to send out the appropriate form input boxes
# and test the results when done.  These are get_the_number...  These routines
# all add to $out which is the page we send the browser. The use the form
# fields edit_[identifier] to request re-editing a field and append hidden
# fields to $carry_forward to carry data necessary forward to submit2.php.

include("bin/get_the_person.inc");  # Grab function "get_the_person"
$get_the_person_only = 'person';    # Will limit submitter's to humans
include("bin/get_the_code.inc");    # Grab the function "get_the_person"
include("bin/get_the_primes.inc");  # Grab the function "get_the_primes"

# Each form field should be able to print a short help message when the global
# flag $help is set (unless we are done editing that field).
#
# Help is run with a form button named either 'help' (add help) or
# 'no_help' (to remove the help).  When the help is on a hidden 'help' field is
# added to keep it on until the 'no_help' button is pressed.

$help = (!empty($_REQUEST['help']) and empty($_REQUEST['no_help']));

# We will build the appropriate button now in $help_str and add it to the page
# output ($out) somewhere below.

$help_str = "<div align=right><input type=submit ";
if ($help) {
    $help_str .= "value=\"Click to remove help\" name=no_help></div>\n";
    $help_str .= lib_hidden_field('help', 1);  # Should stay on until turned off
} else {
    $help_str .= "value=\"Click here to add help\" name=help></div>\n";
}

# Just a generic you requested line (so it looks the same in all form fields)
$you_requested = "<font color=green><B>(You requested to re-edit this
	field!)</B></font><br>\n ";

# Okay, let's go!

$carry_forward = '';
if (is_numeric($xx_person_id) && !$is_authorized) {
  # When they first reach this they are not authorized simply because they have
  # yet to have been given a chance to include a username/password; the
  # authorization part above will have sent a not authorized header and unless
  # this is already the persons third attempt the browser will automatically
  # give them another chance, this time starting with the username/password box.
  # Only on the third ocasion will they see this $out text.

    $out = "<div class=error>You are not authorized to submit primes as this
	person (id = $xx_person_id)!</div>
	<P>Did you make sure your username is correct?  Check the
	Capitalization (it is case sensitive).  The bottom of the page to
	<a href=\"../bios/edit.php?xx_edit=$xx_person_id\">edit
	prover-accounts</a> has a lost password link.";

  # Note: This failure (if not the first time through) will be logged
  # by http_auth above...
} else {
  # request (or process) each part, $done will be 1 if all okay
    $done = 1;

    $temp = lib_get_column('prime.rank = 5000', 'prime', 'digits, log10', $db);
    $The5000thDigits = $temp['digits'];
    $The5000thLog10 = $temp['log10'];

    $out = "<p>Use this page to enter one or more primes with a single set of
	prover(s). Once this page is filled out, press submit at the bottom to
	get a confirmation screen.&nbsp;  You may use the submit button as often
	as you'd like to verify you have filled in each field correct.</p>

	<p>Currently <b>primes must have <span
	title=\"Log_10 $The5000thLog10\">$The5000thDigits</span>
	or more digits</b> to make the list (unless they have a correctly
	formatted <a href=\"/top20/sizes.php\">archivable comment</a>).&nbsp;
	All entries must be <b><a href=\"/prove\">proven primes</a></b>--not
	just
	<a href=\"/glossary/page.php?sort=PRP\">probable
	primes</a> (see <a href=\"#note\">note</a>).</p>&nbsp;\n\n";

    if (isset($is_authorized) and $is_authorized) {
        $out .= "<P>[Username/password verified for database id
		$xx_person_id]</P>";
    }

    $out .= "<form method=post action=\"$_SERVER[PHP_SELF]\">\n";
    $out .= $help_str;        # Add the help button

    $done *= get_the_person();    # Each either prints an edit box, or displays
    $done *= get_the_code();  #
    $done *= get_the_primes();    # the results already approved from the form

    if ($done == 1) {
      # If so, then we have a completed parsed list of primes, move to part two.
      # Note part two is done via submit2.php
        $url = preg_replace('/submit\.php/', 'submit2.php', $_SERVER['PHP_SELF']);
        $out .= "</form>\n<form method=post action=\"$url\">
	<input type=\"submit\" name=\"done\" value=\"Press to complete submission" .
        " (or press 'change' to backup)\"" . basic_tab_index() . "><br> <p>Press this
	button to complete the submission and get a confirmation message.</p>\n";
        $out .= $carry_forward;
    } else {
        $out .= "<input type=submit value=\"Press here to submit these prime(s)\"" .
        basic_tab_index() . "><p>\n";
    }
    $out .= "</form>\n";
    $out .= "<p>There is a <a href=\"submit_full.php\">separate submission
	page</a> for primes with very long mathematical descriptions.&nbsp; It
	is rarely needed.</p> <div class=technote><b>Technical note:</b>
	This page is the first of two used for submission, the
	second is the confirmation page.&nbsp;  This page will verify that the
	numbers submitted can be parsed and pass certain basic size
	restrictions.&nbsp; The numbers will be canonicalized.&nbsp; It requires
	that you \"own\" the code you are submitting under and requires a
	correct username and password before allowing you to proceed.&nbsp;  It
	does not check to see if the primes are duplicates of those already in
	the database or check the comment or make sure it is of a size that
	will fit in the current list--these test (and more) will be performed
	later.

	<P><a name=note></a><b>Warning note:</b> If it is discovered that you
	have submitted one or more numbers that are not proven primes, you may
	lose the right to continue submitting and/or have your credit for your
	primes reduced by sharing it with those running our verification
	programs.&nbsp; Please respect the integrity of this database and the
	students / researchers that rely on it.

	<p><b>Note:</b> Primes with the same number of digits as the 5000th
	prime, but very-slightly smaller, may currently make it through this
	submission process, but will soon be removed after the ranking process
	has definitively established the relative sizes.</div>\n";
}

# Set up variables for the generic page template

$t_text = $t_banner_message_all_submit_pages . $out;
$t_title = "Submit One or More Primes";
$t_submenu = 'submit';

include("template.php");

function edit_button($field_name)
{
    return "\n &nbsp; &nbsp; &nbsp; <input type=submit value=change
	name=\"edit_$field_name\">\n";
}
