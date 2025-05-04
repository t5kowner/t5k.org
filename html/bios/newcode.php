<?php

#t# ready

$t_submenu =  "New Code";

$t_banner_message_all_submit_pages = '';

# Page 1 of 2 : Get and verify the info (newcode2.pgp will finish)

# This page allows us to create new proof-codes.  The person must already
# be defined and a password is necessary...  If possible I'd like to also use it
# to edit an existing code.   Note that if it is called with ?project=username
# it will attempt to fill in both the prover_program and the proof_program
# and the others field from the person.ProjectAlsoCredits database field.

# Several variables must be carried forward to newcode2.php
#
#   $prog_prefix        prefix used for generating a code ('g', 'x'...)
#   $complete_list_of_ids   everyone involved that must be changed

# Form variables:
#   $help, $done, and $edit_xxx.  If $help set, prints help sentences before the
#   fields, $edit_xxx is set to the name of a field to re-edit.
# Page variables: built in $out.  $done is set when the form is approved (every
#   thing checked and okayed by the user) otherwise is used by the program
#   to make sure each of the checking routines succeeded, and if so, then the
#   approve number button is sent.
# Each form field ($name, $username and $email)
#   has a routine to send out the appropriate form input boxes and
#   test the results when done.  These are get_the_number...  These routines
#   all add to $out which is the page we send the browser.

include_once("bin/basic.inc");
$db = basic_db_connect();   # Connects or dies

include_once("newcode.inc");

# we should make sure they are authorized.  This must come first as it alters the
# http headers!

$xx_person_id = (isset($_REQUEST['xx_person_id']) ? $_REQUEST['xx_person_id'] : '');
if (preg_match('/^\d+$/', $xx_person_id)) {
    include_once('bin/http_auth.inc');
    $is_authorized = my_auth($xx_person_id, 'log errors');
}

# Each form field should be able to print a short help message when the global flag
# $help is set (unless we are done editing that field).
#
# Help is run with a form button named either 'help' (add help) or
# 'no_help' (to remove the help).  When the help is on a hidden 'help' field is
# added to keep it on until the 'no_help' button is pressed.

$help = (!empty($_REQUEST['help']) and empty($_REQUEST['no_help']));

# We will build the appropriate button now in $help_str and add it to the page
# output ($out) somewhere below.

$help_str = "<input type=\"submit\" class=\"btn btn-secondary m-3\" ";
if ($help) {
    $help_str .= "value=\"Click here to remove help\" name=\"no_help\">\n";
    $help_str .= lib_hidden_field('help', 1);  # Help should stay on until turned off
} else {
    $help_str .= "value=\"Click here to add help\" name=\"help\">\n";
}

# Just a generic you requested line (so it looks the same in all form fields)
$you_requested = "<font color=green><b>(You requested to re-edit this next field.)</b></font><br>\n ";

# Start by cleaning project and others fields
$project = (empty($_REQUEST['project']) ? '' :
    preg_replace('/[^\w]*/', '', $_REQUEST['project']));
$others = (empty($_REQUEST['others']) ? '' :
    preg_replace('/[^\w,]*/', '', $_REQUEST['others']));

# If projects is set, adjust others
if (!empty($_GET['project']) and empty($_REQUEST['proof_program'])) {
  # If it is called with ?project=username attempt to fill in both the prover_
  # program and the proof_program and the others field from the
  # person.ProjectAlsoCredits database field.  Notice that this is a list
  # of surnames, starting with that of the prover program.
    try {
        $query = 'SELECT ProjectAlsoCredits FROM person WHERE surname=:surname LIMIT 1';
        $sth = $db->prepare($query);
        $sth->bindValue(':surname', $project);
        $sth->execute();
    } catch (PDOException $ex) {
        lib_mysql_die('error in newcode.php 93: ' . $ex->getMessage(), $query);
    }
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    $ProjectAlsoCredits = $row['ProjectAlsoCredits'];
    if (preg_match('/([^,]*),?(.*)/', $ProjectAlsoCredits, $temp)) {
        $_REQUEST['proof_program'] = $temp[1];
        $_REQUEST['others'] = $temp[2] . (empty($temp[2]) ? '' : ',') . $project;
    }
}
#print "<li>proof_program:".$_REQUEST['proof_program'] ;
#print "<li>others: ".$_REQUEST['others'];
#print "<li>(99) \$_REQUEST"; print_r($_REQUEST); print "<br><br>";

# Each form field has a routine to send out the appropriate form input boxes and
# test the results when done.  These are get_the_number...  These routines
# all add to $out which is the page we send the browser.  The use the form fields
# edit_[identifier] to request re-editing a field and append hidden fields to
# $carry_forward to carry data necessary forward to submit2.php.

include("bin/get_the_person.inc");  # Grab function "get_the_person"
$get_the_person_query = "<p class=\"mt-3\"><b>What person will own this code? (Give the database ID or username of prover.)</b>";

# Okay, let's go!

if (is_numeric($xx_person_id) && !$is_authorized) {
    $out = "<p>You are not authorized to act as this person! (id = $xx_person_id)</p>";
} else {
  # request (or process) each part, $done will be 1 if all okay
    $done = 1;
    $out = '';    # Building the text in $out

    $done *= get_the_person();

  # This complete list will be built onto in the next two 'get_the' routines
    $complete_list_of_ids = ($done == 1 ? $xx_person_id : '');
    $done *= get_the_proof_program();     #
  # get_the_proof_program sets the global $prog_prefix
    $done *= get_the_others();        #

#print "<li>complete_list_of_ids: ".$complete_list_of_ids;
#print "<li>xx_person_id: ".$xx_person_id;
#print "<li>prog_prefix: ".$prog_prefix;
#print "<li>(144) \$_REQUEST"; print_r($_REQUEST); print "<br><br>";


    if ($done == 1 & empty($help)) {
      # If so, then we have a complete entry, submit it?  $button will allow the person to move
      # on (and complete) a second form will allow them to back up.
        $button = "<form method=post action=\"newcode2.php\">
        <input type=\"submit\" name=\"done\"  class=\"btn btn-primary m-3\"
	value=\"Press here to complete submission (or press 'change' to backup)\" " . basic_tab_index() . '>' .
        lib_hidden_field('complete_list_of_ids', $complete_list_of_ids) .
        lib_hidden_field('xx_person_id', $xx_person_id) .
        lib_hidden_field('prog_prefix', $prog_prefix) .
        "</form>";

        $out = "Review your entry, if it is correct, then press the submission button.
        Remember that all unused codes will be deleted after 24 hours, so do not create a proof-code until you need it.
	$button
	<form method=post action=\"$_SERVER[PHP_SELF]\">
	$out
	</form>
	$button";
        $out .= "<br><div class=technote>Technical note: This page is the first of two used for creating new codes.
	This page will verify the person via their password and make sure the code members all exist.  Though I
	call the second item the 'Proving program,' it is actually the database entry that owns the proof-code
	prefix--and this must specify the proving program (e.g., 'SB' specifies Proth.exe).</div>\n";
    } else {
        $out = "<p>Each prime has an associated proof-code which specifies the program used to prove that it is
	prime, the person running the program, and sometimes other persons, programs and projects.  You should
	reuse your proof-codes as longs as none of these change.  If something has changed, then use this page
	to create a new proof-code.</p>

	<p>Only use this page after you have created a prover-account. Each prover-account can have many
	proof-codes, but you may have only one prover-account.  Click <a href=\"newprover.php\">new</a> to create
	a prover-account if you do not have one.</p>

	<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">
        <input type=\"submit\" class=\"btn btn-primary my-3 ml-0\" value=\"Press here to submit this entry\"" . basic_tab_index() . ">
	$help_str
	<p>
	$out
        <input type=\"submit\" class=\"btn btn-primary my-3 ml-0\" value=\"Press here to submit this entry\"" . basic_tab_index() . ">
	</form>

	<div class=\"technote p-3 mt-5\">
	<p><b>Technical Notes:</b>
	<ol>
	<li> Do not create codes unless you need them immediately.</li>
	<li> If you do not add primes within 24 hours after creating this entry, it will be automatically deleted.</li>
	<li> You may not use HTML tags in the above fields.</li>
	<li> The editors reserve the right to edit all fields of all entries.</li>
	<li> When forming the list of the top twenty: provers, programs and projects; the persons
	  and projects in this code will share credit (e.g., two humans means each gets 1/2 credit).  Each
	  program will get full credit (this is to encourage proper reporting).  See the bottom of the page
	  <a href=\"/bios/top20.php?type=person&amp;by=ScoreRank\" class=\"none\">top twenty</a>
	  for more information.</li>
	<li> You are not allowed to specify the order in which members of a code are listed. They will be
	  canonically sorted when the code is created.</li>
	<li> Do not create codes simply for vanity purposes--e.g., to use a different proof-code  for each
	  type of prime you use.  If you do, you may lose privileges on these pages. Proof-codes should
	  accurately reflect the persons, programs and projects being used.</li>
	<li> Note x-codes must have a comment added to explain how the numbers were proven prime. If you do
	  not supply this information, we may change the entry.  (For example, we may convert the x-code to
	  a p-code or L-code, should the only proof we know about be our own verification with OpenPFGW or
	  LLR.)</li>
	</ol>
	</div>\n";
    }
}

# Set up variables for the template
$t_text = $t_banner_message_all_submit_pages . $out;
$t_title = "Create a New Proof-Code";

include("template.php");

function edit_button($field_name)
{
    return "\n &nbsp; &nbsp; &nbsp; <input type=\"submit\" class=\"btn btn-secondary m-1 p-1\"
	value=\"change\" name=\"edit_$field_name\">\n";
}

function get_the_proof_program($error_message = '')
{
    global $out, $help, $you_requested, $db;
    global $complete_list_of_ids;   # stores the complete list to be carried onto the next routine
    global $prog_prefix;        # prefix to use when creating the item

    $field_name = 'proof_program';
    $data  = (isset($_REQUEST[$field_name]) ? $_REQUEST[$field_name] : '');
    $edit  = (isset($_REQUEST['edit_' . $field_name]) ? $_REQUEST['edit_' . $field_name] : '');

#print "<li>edit [$edit]";
#print "<li>data [$data]";
#print "<li>(220) request ";print_r($_REQUEST);
#if (!empty($error_message)) { print "<li>error_message [$error_message]"; }
#print "<br>";

  # First, print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<div class=\"alert alert-danger\" role=\"alert\">ERROR: $error_message</div>";
    }
    if (!empty($error_message)) {
        $out .= $error_message;
    }

    if (empty($data) or !empty($error_message)) {
      # Need the edit fields--we are (still) editing

        $out .= "<p>We must first specify the proof method. For example, did you prove the number
	prime using Gallot's Proth.exe? If so, check the appropriate box below. The database contains
	some projects for which it knows the proof method (for example 'Seventeen or Bust' uses
	Gallot's program) so please select the best answer from the list below.</p>\n";

        if ($help) {
            $out .= "<p><font color=green>The list maintained here is the list of largest
	known proven primes--so we must know the proof method.  Below are the main proving methods
	the system knows about.  If you did not use one of these programs, then choose 'other' for
	now and we will contact you for more information later.</font>\n";
        }

        $query = "SELECT id, name, type from person
	WHERE prog_prefix != '' and prog_prefix != 'x' ORDER BY PrimesActive DESC, ScoreActive DESC, type, username";
        $sth = lib_mysql_query($query, $GLOBALS['db'], 'Invalid query (newcode 206)');

        $out .= "<blockquote>\n";
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
          # Okay, have a row to display; add new/modified note?
            $temp = ($data == $row['id'] ? ' checked' : '');
            $out .= "\n <input name=\"$field_name\" class=\"m-2 secondary\" value=$row[id] type=radio$temp" . basic_tab_index() . "> $row[name]<br>";
        }
        $out .= "\n <input name=\"$field_name\" class=\"m-2 mb-3\" value='-1' type=radio" . basic_tab_index() .
        "> other proof program (more information will be required)<br>";
        $out .= "\n</blockquote>\n";
    } else {
      # Have $data which should be -1 (other) or an id number.   Get the info from the database in $row
      # or exit with an error message

        if ($data == -1) {
            $prog_prefix = 'x';
            $id_out = "<ul><li>other proof program (more information will be required)</ul>\n";
        } else {
            $id_out = show_list_of_ids($data);  # Only one, but lets make things uniform

    #print "(264),[$id_out],".$GLOBALS['show_list_of_ids_errors'];

            if (!empty($GLOBALS['show_list_of_ids_errors'])) {
                return get_the_proof_program($GLOBALS['show_list_of_ids_errors']);
            }

            $prog_prefix = $GLOBALS['show_list_of_ids_prefix'];
            $id = $GLOBALS['show_list_of_ids_list'];
          # add this id to the complete list
            $complete_list_of_ids .= (empty($complete_list_of_ids) ? '' : ', ') . $id;
          # otherwise all is well!
        }

        $out .= '<p class="mt-3"><b>Proof program: </b>' . edit_button($field_name) . lib_hidden_field($field_name, $data) .
        "$id_out\n This will generate a code beginning with the letter(S) '$prog_prefix'. &nbsp; \n";
        if ($also = also_credits($prog_prefix, $id)) {
            if (!empty($GLOBALS['show_list_of_ids_errors'])) {
                return get_the_proof_program($GLOBALS['show_list_of_ids_errors']);
            }
            $out .= " This code will also automatically credit: <p class=\"mt-2\">" . show_list_of_ids($also);
          # add these 'also' ids to the complete list
            $complete_list_of_ids .= (empty($complete_list_of_ids) ? '' : ', ') . $GLOBALS['show_list_of_ids_list'];
        }
        return (1);
    }
}

# The others should be a comma delimited list of ids and last names, perhaps with
# empty strings and duplicated.  OR it is the string 'none'.

function get_the_others($error_message = '')
{
    global $out, $help, $you_requested;
    global $complete_list_of_ids;   # stores the complete list to be carried onto the next routine

    $field_name = 'others';
    $data  = isset($_REQUEST[$field_name]) ? $_REQUEST[$field_name] : '';
    $data  = preg_replace('/\s*,\s*/', ', ', $data);

    $edit  = (isset($_REQUEST['edit_' . $field_name]) ? $_REQUEST['edit_' . $field_name] : '');

#print "<li>edit [$edit]";
#print "<li>data [$data]";
#print "<li>(304) request ";print_r($_REQUEST);

  # First, print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = '<div class="alert alert-danger m-5" role="alert">ERROR: ' . "$error_message</div>\n";
    }
    $out .= "<P>" . $error_message;

    if (empty($data) or !empty($error_message)) {
        $out .= "<P>You may have worked with other persons, projects or programs that you feel
	deserve credit.  List those are to be included using either their usernames or database
	ids (separated by commas).  If there are no others to list, then just enter the English word 'none'.";
        if ($help) {
            $out .= "<p><font color=\"green\">For example, you could write '9, OpenPFGW, PRP'
	to credit database entry #9 (Caldwell) as well as the programs OpenPFGW and PRP. If
	you are not sure of the username for the person, program or project; look on the 
	prover-account pages again.</font>";
        }
        if (empty($data)) {
            $data = 'none';
        }
        $out .= "<blockquote>\n  <input type=text name=$field_name value=\"$data\"" . basic_tab_index() .
        "size=80>\n</blockquote>\n";
    } else {
        if (preg_match('/^\s*\'?none\'?\s*$/', $data)) {
            $ids = '<ul><li>none</ul>';
        } else {
            $ids = show_list_of_ids($data);
            if (!empty($GLOBALS['show_list_of_ids_errors'])) {
                return get_the_others($GLOBALS['show_list_of_ids_errors']);
            }
          # add these ids to the complete list
            $complete_list_of_ids .= (empty($complete_list_of_ids) ? '' : ',') . $GLOBALS['show_list_of_ids_list'];
        }
        $out .= '<p class="mt-3"><b>Other persons, programs and projects:</b> ' . edit_button($field_name)
        . lib_hidden_field($field_name, $data) . $ids;
        return (1);
    }
}
