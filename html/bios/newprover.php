<?php

#t# ready

require_once __DIR__ . "/../../library/db_helpers/passwords.inc";

$t_submenu =  "New Prover";

#  my_is_ip_blocked

# This page allows us to begin new users.  The key is to just get the full name,
# username and e-mail address.  Then mail them a password.  Anything else
# they can adjust using edit.php (with the password which verifies they gave
# a correct e-mail address--at least for the moment)

$t_banner_message_all_submit_pages = '';

# Form variables:
#   $help, $done, and $edit_xxx.  If $help set, prints help sentences before the
#   fields, $edit_xxx is set to the name of a field to re-edit.
# Page variables: built in $out.  $done is set when the form is approved (every
#   thing checked and okayed by the user) otherwise is used by the program
#   to make sure each of the checking routines succeeded, and if so, then the
#   approve number button is sent.
# Each form field ($name, $username, $surname and $email)
#   has a routine to send out the appropriate form input boxes and
#   test the results when done.  These are get_the_number...  These routines
#   all add to $out which is the page we send the browser.

include_once('bin/basic.inc');
$db = basic_db_connect();   # Connects or dies

include_once('bin/http_auth.inc');
if (my_is_ip_blocked()) {
    lib_die("Can not create a prover-account while your IP is blocked.", 'warning');
}

function html_scrub($str = '')
{
    return(htmlspecialchars(stripslashes($str)));
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

$help_str = '<input type="submit" class="btn btn-secondary m-3" ';
if ($help) {
    $help_str .= "value=\"Click here to remove help\" name=\"no_help\">\n";
    $help_str .= lib_hidden_field('help', 1);  # Help should stay on until turned off
} else {
    $help_str .= "value=\"Click here to add help\" name=\"help\">\n";
}

# Just a generic you requested line (so it looks the same in all form fields)
$you_requested = "<font color=\"green\"><b>You requested to re-edit this next field.</b></font><br>\n ";

if (!empty($_REQUEST['done'])) { # All done, form checked and approved, so let's submit
  # These leave a security hole?? suppose they change the values in the hidden values?
  # maybe later add a MD5 checksum...

    include_once('bin/log.inc');

    $name     = html_scrub($_REQUEST['name']);
    $username = html_scrub($_REQUEST['username']);
    $surname  = html_scrub($_REQUEST['surname']);
    $email    = html_scrub($_REQUEST['email']);

  # Make sure the surname is unique (necessary in codes)
    if (lib_rows_in_table('person', $GLOBALS['db'], "surname=" . $db->quote($surname))) {
      # This means that the surname is not unique, so we will try appending an integer
        $n = 1;
        while (lib_rows_in_table('person', $basic_database_connection, "surname=" . $db->quote($surname . $n))) {
            $n++;
        }
        $surname = "$surname$n";
    }

    $set = "(name,username,surname,email,created) VALUES (:name,:username,:surname,:email,NOW())";
    $query = "INSERT person $set";
    try {
        $sth = $db->prepare($query);
        $sth->bindValue(':name', $name);
        $sth->bindValue(':username', $username);
        $sth->bindValue(':surname', $surname);
        $sth->bindValue(':email', $email);
        $sth->execute();
        $id = $db->lastInsertId();
    } catch (Exception $ex) {
        lib_die("newprover error (95): " . $ex->getMessage());
    }

    $URL = "/bios/edit.php?username=$username";
    $collection = "PrimePages' Prover-Account Database";

    if (empty($id)) {
        $error = "Database system failure in newprover.php (error 98).
     Please let the editor know (you may use the link on the left)";
    } else {
        include('bin/mail_password.inc');
      // mailpassword($email,$password='',$collection='',$URL='',$Account_Name='',$Account_id='')
        $password = mailpassword($email, '', $collection, $URL, "$name [$username, $surname]", $id);
        if (empty($error) && !empty($password)) {
            if (!\DB_Helpers\update_password($db, $id, $password, true)) {
                $error = "Database error 107 in newprover.php.
	   Please let the editor know (you may use the link on the left)";
            }
        }
    }

    if (empty($error)) {
        $out = "A new password for the account $email has been sent by e-mail.\n" .
        "\n<blockquote>$username ($id) : $name</blockquote>\n";
        $out .= ' You may change your password and edit your database entry at the following URL.';
        $out .= "\n<blockquote><a href=\"$URL\">$URL</a></blockquote>\n";
        $out .= "Remember this entry will not appear in the index until you submit a prime and will be removed if you do not submit a valid prime in the next few days.";
        $out .= "\n<p>\n";
        $out .= "To add primes, you will next need a proof-code.  A proof-code is a short string like 'g234' which specifies what program(s) you you used and who you worked with.  Most folks do not change their program..., so will only need to do this step once.  A few others have multiple codes.  To get a proof-code, use the links at the bottom of your prover-account page.";
        $out .= "\n<blockquote>\n\t<a href=\"page.php?id=$id\">t5k.org/bios/page.php?id=$id</a>\n</blockquote>";
        log_action($db, $id, 'created', "person.id=$id", "new prover: $username ($name) $surname");
      # also log the ip and include a penalty (to avoid evil users creatng entry after entry)
      # http_auth_log_ip_error($id,$penalty='',$who='',$passwd='',$comment='')
        http_auth_log_ip_error($id, 2, $username, '(new prover)', 'new prover');
    } else {
        $out = "<font color=red size=+1>$error</font>";
        log_action($db, 'NULL', 'error', "person.id=$id", $error);
    }
} else {
  # request (or process) each part, $done will be 1 if all okay
    $done = 1;

    $out = '';    # Building the text in $out

    $done *= get_the_fullname();      # Each either prints an edit box, or displays
    $done *= get_the_username();      #
    $done *= get_the_surname();       #
    $done *= get_the_email();     # the results already approved from the form

  # Now $out has the form information (global from the get_the_* functions)

    if ($done == 1) {     # If so, then we have a complete entry, submit it?
        $temp  = "Review your entry, if it is correct, then press the submission button.\n";
        $temp .= "This entry will not appear in the index until you submit a prime and will be
	removed if you do not submit a valid prime in the next 30 days.";
        $out   = $temp . "<form method=post action=\"$_SERVER[PHP_SELF]\">\n$out\n\n";
        $out  .= '<input type=submit name=done value="'
        . "Press this to complete submission (or press 'change' to backup)"
        . "\"" . basic_tab_index() . ">\n</form><P>\n";
    } else {
        $button = '<input type="submit" class="btn btn-primary" value="Press here to submit this entry"' . basic_tab_index() . '>';
        $temp   = "<p>A prover-account is used to credit persons, projects and programs with the
	primes they find--they store the basic biographical and contact information. You must
	have one to submit primes (but you never need more than one).  For examples, just peruse
	the prover's index (linked in the menu).</p>\n";
        $temp  .= "\n<p>Currently only person accounts can be created without editor intervention. If
	you need a program or project account, create it as a person below, then use the CONTACT link
	to e-mail the editor.</p>\n\n<p>\n";
        $temp  .= "We only need four pieces of information to create a prover-account.  Obviously we need
	a name to credit with any discoveries you submit and we need to know which part of it is your
	surname. \n(Please use your real name and save your creativity for the username.)\n ";
        $temp  .= "We must have an e-mail address so we can mail you a password and can contact you with
	any questions.  Of course you also need a username (which you will use to access your
	entries). You will have the opportunity to add more information later if you want to.</p>";
        $out  = $temp . "\n\n<form method=post action=\"$_SERVER[PHP_SELF]\">\n$button\n$help_str\n
	<p>$out\n\n$button\n</form>\n";
        $out .= "<div class=\"technote mt-5 p-2\">
	<p>\n<strong class=\"lead font-weight-bold\">Technical Notes:</strong>\n<ol>
	<li> All parts of the form must be filled for your submission to be accepted.
	<li> After you create a prover-account, you must next create a proof-code.
	  These are different steps because the prover-account describes you, and the proof-code
	  describes the programs and projects involved in finding the prime.  You will have only one
	  prover-account, but you might have different codes for each different combinations of
	  programs and projects you are involved with.
	<li> A password will be mailed to the e-mail address listed above. This password is necessary
	  to submit primes, add images or text to your entry...
	<li> This entry may be removed immediately if the e-mail address is determined to be invalid.
	<li> This entry will not appear in the index until you submit a prime and will be removed if
	  you do not submit a valid prime in the next 30 days.
	<li> You may not use HTML tags in the above fields.
	<li> We prefer that the names are listed personal name first then the family name: 'John W. Smith'.
	  Please do not use all capitals. Save your creativity for the username. Finding a record prime
	  is a good reason to use your real name.
	<li> The editors reserve the right to edit all fields of all entries.
    </ol>\n</div>\n";
    }
}

# Set up variables for the template
$t_text = $t_banner_message_all_submit_pages . $out;
$t_title = "Create a New Prover-Account";
$t_allow_cache = 'true';    # They can leave this page and come back to it...

include("template.php");

function edit_button($field_name)
{
    return "\n &nbsp; &nbsp; &nbsp; <input type=submit value=\"change\" name=\"edit_$field_name\">\n";
}


function get_the_fullname($error_message = '')
{
    global $out, $help, $you_requested;
    global $name;   # Global *to* mail routine and summary above

  # New form data?
    $name = empty($_REQUEST['name']) ? '' : html_scrub($_REQUEST['name']);
    $name = preg_replace('/[;"\'()[\]{}!@#$%^&*()\-+\/\`~]/', '', $name);   # stop code injection!
    $edit = empty($_REQUEST['edit_fullname']) ? '' : $_REQUEST['edit_fullname'];

  # Remove any leading/trailing whitespace
    $name = preg_replace('/^\s+/', '', $name);
    $name = preg_replace('/\s+$/', '', $name);
    $name = preg_replace('/(\s+|&nbsp;)/', ' ', $name); # Why allow fancy whitespace?

  # Print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font><br>\n";
    }
    $out .= $error_message;

    $out .= "The full name of the person. This should be your real name!\n";

    if (!empty($name) and empty($edit)) {
        $out .= edit_button('fullname') . "<blockquote>\n$name\n</blockquote>\n";
        $out .= lib_hidden_field('name', $name);
        return(1); # Done!
    } else {
        if ($help) {
            $out .= "\n<p>\n<font color=green>Help note: Just give us your real name, first then last.
	We prefer that the names are listed personal name first then the family name:
	'John W. Smith'. Please do not use all capitals. Save your creativity for the username.
	Finding a record prime is a good reason to use your real name.
        Your full name will appear in the index, so no HTML tags are allowed (though you may use text entities).
	You may change this entry later.\n</font>\n</p>\n";
        }
        $out .= "<blockquote>\n  <input type=text name=name size=60" . basic_tab_index() .
        "value=\"$name\">\n</blockquote>\n";
        return(0); # Not done
    }
}

function get_the_username($error_message = '')
{
    global $out, $help, $you_requested;
    global $username;

  # New form data? (Alters html, entities...)
    $username  = empty($_REQUEST['username']) ? '' : html_scrub($_REQUEST['username']);
    $username = preg_replace('/[;"\'()[\]{}!@#$%^&*()\-+\/\`~]/', '', $username);   # stop code injection!
    $edit      = empty($_REQUEST['edit_username']) ? '' : $_REQUEST['edit_username'];

  # Block any non-legal characters
    $username = preg_replace('/(\s|&nbsp;)/', '', $username);   # No whitespace at all!
    $username = preg_replace('/[^\w\.\^]/', '', $username);     # Remove illegal characters silently
#  $username = ucfirst($username);              # Capitalize first letter!

  # First, print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>";
    }
    $out .= $error_message;

    $out .= "What username do you want? &nbsp; ";
    if (!empty($username) and empty($error_message)) {
      # Can not be a number--this will be mistaken for an id by other routines
      # that accept either
        if (preg_match('/^\d+$/', $username)) {
            return get_the_username("A username may not be a number for technical reasons. ");
        }
      # Limit the lengh of a username
        if (strlen($username) > 13) {
            return get_the_username("This database field may not exceed 13 characters in length. ");
        }
      # Complain if the name is in use
        $x_name = stripslashes($username);
        if (basic_get_person_from_username($x_name, 'id')) {
            return(get_the_username("The username '$x_name' is already in use. Try adding a digit at the end?"));
        }
        $out .= edit_button('username') . "<blockquote>\n$x_name\n</blockquote>\n" .
        lib_hidden_field('username', $x_name);
        return(1); # Done!
    } else {
        if ($help) {
            $out .= "\n<p>\n<font color=green>\nHelp note: You need some sort of username
	to allow you access your prover-account at this site.  Many folks use a shortened
	version of their surname.  It must have only letters and digits with no whitespace
	or punctuation.</font>\n</p>";
        }
        $out .= "Only letters, no spaces, punctuation marks..." .
        "\n<blockquote>\n  <input type=text name=username size=13" .
        basic_tab_index() . "value=\"$username\">\n</blockquote>\n";
        return(0); # Not done
    }
}


function get_the_surname($error_message = '')
{
    global $out, $help, $you_requested;
    global $surname;

  # New form data? (Alters html, entities...)
    $surname   = empty($_REQUEST['surname']) ? '' : html_scrub($_REQUEST['surname']);
    $edit      = empty($_REQUEST['edit_surname']) ? '' : $_REQUEST['edit_surname'];

  # Block any non-legal characters
# This creates a segmentation fault!
#  if (preg_match('/^[\w]+(.?)/',$surname,$matches))
#   get_the_surname("Illegal character in last name: '$matches[1]'");
# so lets use this:
#  $surname = preg_replace('/[^\w]/','',$surname);

    $surname = preg_replace('/(\s|&nbsp;)/', '', $surname);     # No whitespace at all!
    $surname = preg_replace('/[^\w\.\^]/', '', $surname);       # No whitespace at all!
    $surname = ucfirst($surname);                 # Capitalize first letter!

  # First, print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>";
    }
    $out .= $error_message;

    $out .= "Your surname (last name or family name; used for alphabetizing and prover lists). ";
    if (!empty($surname) and empty($error_message)) {
      # Can not be a number--ths will be mistaken for an id by other routines
      # that accept either
        if (preg_match('/^\d+$/', $surname)) {
            return get_the_surname("We are seeking your surname (last name, family name). Your surname is not an integer!).");
        }
        $x_surname = stripslashes($surname);
        $out .= edit_button('surname') . "<blockquote>\n$x_surname\n</blockquote>\n" .
        lib_hidden_field('surname', $x_surname);
        return(1); # Done!
    } else {
        if ($help) {
            $out .= "\n<p>\n<font color=green>Help note: We use your surname (that is, your last
	or family name) to sort the index and in prover lists. It must have only letters and digits
	with no whitespace or punctuation.\n</font>\n</p>\n";
        }
        $out .= "Only letters, no spaces, punctuation marks...c\n<blockquote>\n  <input type=text name=surname" .
        basic_tab_index() . "size=20 value=\"$surname\">\n</blockquote>\n";
        return(0); # Not done
    }
}


function get_the_email($error_message = '')
{
    global $out, $help, $you_requested;
    global $email;

  # New form data?
    $email  = empty($_REQUEST['email']) ? '' : html_scrub($_REQUEST['email']);
    $edit   = empty($_REQUEST['edit_email']) ? '' : $_REQUEST['edit_email'];

# if (!preg_match("/^[\w\.\-]+\@[\w\-]+\.[\w\.\-]+$/",$email))
#   get_the_email('The e-mail address does not appear to be valid');

  # First, print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>\n";
    }
    $out .= $error_message;

    $out .= "What e-mail address can we use to contact you? \n";
    if (!empty($email) and empty($edit)) {
        $out .= edit_button('email') . "<blockquote>\n$email\n</blockquote>\n";
        $out .= lib_hidden_field('email', $email);
        return(1); # Done!
    } else {
        if ($help) {
            $out .= "<p><font color=green>Help note: We must be able to contact you if we
	have questions about the proof methods, or apparent typos or ...  This address will
	not be published unless you go to the edit page and request that it is. We will send
	your temporary password to this address.</font></p>\n";
        }
        $out .= "(This e-mail address will not be published unless you later request it.)\n<blockquote>\n" .
        "  <input type=text name=email size=30" . basic_tab_index() . "value=\"$email\">\n</blockquote>";
        return(0); # Not done
    }
}
