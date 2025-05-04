<?php

# This page allows the user to submit a prime_blob--full digit expansions of primes
# that do not have nice short mathematical descriptions.
# I expect only editors will be using this.  (??really??)

# Form variables: $text, $description, $digits, $log10, $xx_person_id, $full_digits,
#   $help, $done, and $edit_xxx.  If $help set, prints help sentences before the
#   fields, $edit_xxx is set to the name of a field to re-edit.
# Page variables: built in $out.  $done is set when the form is approved (every
#   thing checked and okayed by the user) otherwise is used by the program
#   to make sure each of the checking routines succeeded, and if so, then the
#   approve number button is sent.
# Each form field ($text, $description, $xx_person_id, $full_digits)
#   has a routine to send out the appropriate form input boxes and
#   test the results when done.  These are get_the_number...  These routines
#   all add to $out which is the page we send the browser.

include_once("bin/basic.inc");
$db = basic_db_connect();   # Connects or dies

include_once("bin/modify.inc"); # Don't move, causes an error inside a function
    # Used in get_the_description() to ballance html tags...
include_once("bin/get_the_person.inc"); # Grab function "get_the_person"

include_once("bin/log.inc");
include_once('bin/http_auth.inc');
if (my_is_ip_blocked()) {
    lib_die("You may not submit entries while your IP is blocked.", 'warning');
}


# Each form field should be able to print a short help message when the global
# flag $help is set (unless we are done editing that field).
#
# Help is run with a form button named either 'help' (add help) or
# 'no_help' (to remove the help).  When the help is on a hidden 'help' field
# is added to keep it on until the 'no_help' button is pressed.
#
$help = (!empty($_REQUEST['help']) and empty($_REQUEST['no_help']));
#
# We will build the appropriate button now in $help and add it to the page
# output ($out) below.
#
$help_str = "<input type=submit ";
if ($help) {
    $help_str .= "value=\"Click to remove help\" name=no_help>\n";
    $help_str .= lib_hidden_field('help', 1);
  # Help should stay on until turned off
} else {
    $help_str .= "value=\"Click here to add help\" name=help>\n";
}
$help_str .= "<P>\n";

# A generic message for each of the fields
$you_requested = "<font color=green><B>(You requested to re-edit this field!)" .
    "</B></font><br>\n ";

$out = '';  # Output string will be built in this variable

# request (or process) each part, $done will be 1 if all okay
$done = 1;

$done *= get_the_person();         # Each either prints an edit box or displays
$done *= get_the_number();     # and returns 1 iff succeeded (for formulas
$done *= get_the_description();  # may set text)
$done *= get_the_text();       # the results already approved from the form

if (!empty($_REQUEST['done']) && $done) {
    # All done, form checked and approved, so lets submit (and mail?)

    $text     = (isset($_REQUEST['text']) ? $_REQUEST['text'] : '');
#  $text    = preg_replace('/</','&lt;',$text);         # untaint (avoid cross-site scription)
#  $text    = preg_replace('/>/','&gt;',$text);         # untaint (avoid cross-site scription)

    $description  = (isset($_REQUEST['description']) ?
             stripslashes($_REQUEST['description']) : '');
#  $description  = preg_replace('/[<>]/','',$description);  # untaint (avoid cross-site scription)

    $digits   = (isset($_REQUEST['digits']) ? $_REQUEST['digits'] : '');
    $log10    = (isset($_REQUEST['log10']) ? $_REQUEST['log10'] : '');
    $xx_person_id = (isset($_REQUEST['xx_person_id']) ?
            $_REQUEST['xx_person_id'] : '');
    $full_digits  = (isset($_REQUEST['full_digits']) ?
            $_REQUEST['full_digits'] : '');

  # Need to submit the prime, mail the editor and that's all folks!
    $blob_id = basic_create_blob(
        $description,
        $text,
        $digits,
        $log10,
        $full_digits,
        $xx_person_id
    );

    if ($blob_id < 0) {
        $blob_id = -$blob_id;
        $out .= "<font color=red>ERROR: This exact prime is already in the database
 	(same digital expansion)--so it can not be resubmitted.&nbsp;  Instead,
	edit the prime that is there.&nbsp; It has database id $blob_id and can
	be entered in the prime submission page as
	<blockquote>prime_blob_$blob_id</blockquote>\n</font>\n";
        log_action(
            $db,
            $xx_person_id,
            'warning',
            "prime_blob.id=$blob_id",
            "submitted a duplicate prime blob"
        );
        http_auth_log_ip_error(
            $xx_person_id,
            1,
            '',
            '(omitted)',
            "duplicated primt_blob.id=$blob_id"
        );
    } elseif (!($blob_id > 0)) {
        log_action(
            $db,
            $xx_person_id,
            'error',
            "prime_blob.id=$blob_id",
            "basic_create_blob failed in primes/submit_full.php"
        );
        die("The command<P>basic_create_blob()<P>
	failed. This should not happen, mail this info to admin@t5k.org");
    } else {
        mail_result($blob_id); # Appends the success, error message to $out;
        log_action(
            $db,
            $xx_person_id,
            'created',
            "prime_blob.id=$blob_id",
            "submitted a new prime_blob"
        );
      # http_auth_log_ip_error($id,$penalty='',$who='',$passwd='',$comment='')
        http_auth_log_ip_error($xx_person_id, 1, '', '(omitted)', "blob=$blob_id");
    }

    $out .= "<form method=post action=\"$_SERVER[PHP_SELF]\">
	<input type=submit value=\"submit another prime_blob?\">" .
    lib_hidden_field('xx_person_id', $xx_person_id) .
    "</form>\n";
    $out .= "<form method=post action=\"submit.php\">
	<input type=submit value=\"submit this prime_blob to the list of largest\n" .
    "known primes? [step three of three]\">" .
    lib_hidden_field('xx_person_id', $xx_person_id) .
    lib_hidden_field('primes', "prime_blob_$blob_id") .
    "</form><br><br> (Note: you can add the prover code and any archivable
	comments on the next submission page after pressing this button.  Or
	you can go to that submission page and just enter 'prime_blob_$blob_id'
	as the prime at any later date.)";
} else {
  # Form the right submit button

    if ($done == 1) { # If so, then we have a complete entry, submit it?
        $submit = "<input type=submit name=done value=\"Press to complete submission" .
        " (or press 'change' to backup) [step two of three]\">";
    } else {
        $submit = "<input type=submit
	value=\"Press here to submit this blob [step one of three]\">\n$help_str";
    }

  # Add submit buttons, header... to the output already in $out.

    $out = "Use this page to enter a single prime that does not have a short algebraic
	description (or one that for some reason can not be calculated quickly by our
	parser such as partition numbers for large arguments).&nbsp;
	This page adds the prime to the 'prime_blob' table in the 'prime' database.&nbsp;
	Once there, it can be easily submitted to the prime list.&nbsp;
	(A 'blob' is just a MySQL term for a large chunk of data.)<P>
	<form method=post action=\"$_SERVER[PHP_SELF]\">
	$submit
	$out
	$submit
 	</form>\n";
}

# Set up variables for the template
$t_text = $out;
$t_title = "Submit a Single Prime 'Blob'";
$t_subtitle = "Using its' Full-Digital Expansion";
$t_submenu = 'submit full';

include("template.php");

function edit_button($field_name)
{
    return "\n &nbsp; &nbsp; &nbsp; <input type=submit value=change name=\"edit_$field_name\">\n";
}

##### Step one:  THE NUMBER
#
# We already have it if $number_id set, might have it if $long set.
# If something goes wrong, calls itself again with $error_message set
# and ends with edit fields.  If nothing goes wrong, prints short form
# and imbued number_id as a hidden field--then returns 1, else 0.
#   For these routines $error_message is a message from the routine to
# itself, because it calls itself if it finds an error.  They also
# call themselves if processing the field succeeds.
#   Note that the number may be a long decimal expansion or a long
# formula.  Back quotes (slashes) and whitespace are permitted in either.
# Decimal expansions may include commas.
function get_the_number($error_message = '')
{
    global $out, $help, $you_requested;
    global $short, $log10, $digits, $full_digits;   # Routine might call itself

    if (empty($log10)) {  # Set if the routine calls itself
        $full_digits  = stripslashes((isset($_REQUEST['full_digits']) ? $_REQUEST['full_digits'] : ''));
        $full_digits = preg_replace('/[\'\"<>;]/', '', $full_digits);  # untaint
        $edit     = (isset($_REQUEST['edit_number']) ? $_REQUEST['edit_number'] : '');
    }

  # First, print any error message (continue editing in this case!)
    if (!empty($edit)) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>";
    }
    $out .= $error_message;

    if (empty($error_message) and is_numeric($log10) and empty($edit_number)) {
      # If $log set, this part is done, so just print the number and move on
        $out .= "\nThe number is" . edit_button('number') .
        "\n<blockquote title=\"log10 = $log10, digits = $digits\">$full_digits\n" .
        "</blockquote>\n" .
        lib_hidden_field('log10', $log10) .
        lib_hidden_field('short', $short) .
        lib_hidden_field('digits', $digits) .
        lib_hidden_field('full_digits', $full_digits);
        return(1);
    }

    if (empty($error_message) and !empty($full_digits) and empty($edit_number)) {
      # They have filled in the number part of the form, let's process it
      # we check the long form first--using it to find digits, log10

      # Is it a number or a formula that is in $full_digits?
        if (preg_match("/^(\d+|\s+|\n|\\\\|,)+$/", $full_digits)) { # number
          # Remove any spaces, commas, continuation lines '\\\n';
          # Why do I need \\\\ to match \, but not \\s for whitespace?
            $new_long = preg_replace("/(\s+|\n|\\\\|,)/", '', $full_digits);

          # Are there any unnecessary leading zeros or plus signs?  Grab trailing non-digits.
            if (preg_match("/^(\+?)(0*)(\d+)(.*?)$/", $new_long, $match)) {
                $new_long = $match[3];
            }
            if (!empty($match[4])) {
                return get_the_number("Primes should only contain digits (with
 	  possibly commas, blanks, back quotes and carriage returns for ease of
	  reading.)");
            }
        } else { # formula
            if (strlen($full_digits) < 64) {
                return get_the_number("The formula \"$full_digits\" is too short to need
	  this submission routine.&nbsp; Try <a href=submit.php>the usual
	  submission process</a>.");
            }

            $new_long = shell_exec('/var/www/html/primes/support/math/parser -f ' .
            escapeshellarg($full_digits) . ' 2>&1');
            if (!preg_match("/^\d+$/", $new_long)) {
                return get_the_number("The formula \"$full_digits\" was not recognized by our
          parser so it is being ignored.  If it is a valid prime please
          <a href=\"mailto:admin\@t5k.org\">let the admin know</a>! The
          parser returned: <blockquote>$new_long</blockquote>\n");
            }
          # If the form field 'text' is empty (the explanation), lets put the formula there
            if (empty($_REQUEST['text'])) {
                $_REQUEST['text'] = $full_digits;
            }
        }

      # Set digits and log10
        $digits = strlen($new_long);
        if ($digits < 64) {
            return get_the_number("Numbers with less than 64 digits (yours has $digits)
	can be entered directly in the prime database where the description is the full number.
	But of course these short numbers will be rejected as too short!.\n");
        }

        if (preg_match("/^\d{16}/", $new_long, $match)) {
            $leading_digits = $match[0];
        }
        $log10 = $digits - 16 + log($leading_digits) / log(10);

      # Used by mail program; also suggested as description
        if (preg_match("/^(\d{20})\d+?(\d{20})$/", $new_long, $match)) {
            $short = "$match[1]...(" . ($digits - 40) . " other digits)...$match[2]";
        } else {
        }
        # error message!

      # Here we should have a valid form.  Reformat and preform some checks

        $full_digits = '';
      # note $digits = strlen($new_long);
        $length_lead_block = 10 - ($digits % 10);
        for ($i = 0; $i < $digits; $i++) {
            $full_digits .= $new_long[$i];
            if ((($i + 1 + $length_lead_block) % 10) == 0) {
                if ($i + 1 < $digits) {
                    $full_digits .= ' ';  # avoid a trailing space
                }
            }
        }

      # ? checks ?

      # Now have the number, lets print it and move on
        return get_the_number(); # Recycle--print reformated number with log10 and move on

    # Otherwise must have either an error message or no input yet
    } else {
      # Next get the number
        $out .= "What number do you want to submit? &nbsp; (<a
	href=help/blob_guidelines.php#hints>Suggestions</a>)
      <blockquote>
	Input the number (all of the digits, whitespace okay):<br>";

        if ($help) {
            $out .= "<p><font color=green>Help note: Simply enter the
	entire number.&nbsp;  If it has 20000 digits, list them all.&nbsp;  They
	can be in one long line of digits, or have spaces/linefeeds spread
	throughout or have lines terminated by backslashes (common in UNIX
        programs), or even have commas.&nbsp;  All spaces, linefeeds,
	backslashes, tabs and commas will be eventaully removed by the program,
	and it will be reformatted to show the digits in blocks of ten
	deliminated by spaces.&nbsp; Some browsers have trouble when you cut
	and paste large numbers--so make sure all of the digits appear on the
	confirmation screen.</font></p>";
        }

        $out .= "<textarea name=full_digits cols=80 rows=20>$full_digits</textarea><P>
	</blockquote>";
    }
    return(0); # Not done--got here via printing edit fields
}

function get_the_description($error_message = '')
{
    global $out, $help, $you_requested;
    global $description;

    $description  = (isset($_REQUEST['description']) ?
    stripslashes($_REQUEST['description']) : '');
    $description  = preg_replace('/[<>]/', '', $description); # untaint (avoid cross-site scription)
  # print "<li>$description";
  # print "<li>$description";

    if (empty($description) and isset($_REQUEST['short'])) {
        $description =
        stripslashes($_REQUEST['short']);
    }
    if (empty($description)) {
      # If formed in short, it is not parsable, so must be quoted.
        $temp = $GLOBALS['short'];
        if (!empty($temp)) {
            $description = '"' . $temp . '"';
        }
    }
    $edit = (isset($_REQUEST['edit_description']) ?
    $_REQUEST['edit_description'] : '');

  # First, print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>";
    }
    $out .= $error_message;

    global $modify_ErrorMessage;
    if (empty($error_message) and !empty($description)) {
        $description = preg_replace('/</', '&lt;', $description); # No HTML
        $out .= "\nDescription (the line that will show on the prime list). Quote " .
        "if not parsable\n" . edit_button('description') .
        "<blockquote>\n$description\n</blockquote>\n" .
        # This is in a " "'d string, so lets alter "'s
        lib_hidden_field('description', preg_replace('/"/', '&quot;', $description));
        return(1); # Done, description ready
    } else {
        $out .= "Description for the prime (line as it will show in prime list--place
	in double quotes if not parsable) (<a
	href=help/blob_guidelines.php#description>guidelines</a>)
	&nbsp;  <b>Suggestion:
	Leave blank to have the routine automatically fill it in!</b>";
        if ($help) {
            $out .= "<p><font color=green>Help note: A short, single line
	describing the prime.  It will be used as the description field on the
	list of primes.  Examples<ul><li>
	\"69943392751539865432...(15032 other digits)...6238463797677935111\"
	<li>\"[e*10^1230]/36037\"<li>\"'css_descramble.c.gz'*65536+2083\"</ul>
	This title will be linked on the list to the next field, here called
	text.&nbsp; No HTML mark-up is allowed in this field.  </font></p>";
        }
      # This is in a " "'d string, so lets alter "'s
        $description = preg_replace('/"/', '&quot;', $description);
        lib_hidden_field('description', $description);
        $out .= "<blockquote><input type=text name=description size=80
	value=\"$description\"></blockquote>";
        return(0); # Not done
    }
}

function get_the_text($error_message = '')
{
    global $text; # form variables we are setting
    global $out, $help, $you_requested;

  # $text is not empty if this routine calls itself after modify...
    if (empty($text)) {
        $text = (isset($_REQUEST['text']) ?
        stripslashes($_REQUEST['text']) : '');
    }

    $edit = (isset($_REQUEST['edit_text']) ? $_REQUEST['edit_text'] : '');

  # First, print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>";
    }
    $out .= $error_message;

    global $modify_ErrorMessage;
    if (empty($error_message) and !empty($text)) {
      # We need to ballance the text (close unclosed html tags, remove bad ones)
        $text = modify_adjust_html($text);
        if (!empty($modify_ErrorMessage)) {
            return get_the_text("The following
	problems with your explanation were fixed (if it is okay now, just
	continue): " . $modify_ErrorMessage);
        }

      # Now the changes that will be made at the time the page is displayed
      # Perhaps has TeX like entities we need to translate!
        $show_text = modify_entities($text);

        $out .= "\nProposed explanation (to be linked to the prime)\n" .
        edit_button('text') . "<blockquote>\n$show_text\n</blockquote>\n" .
        # This is in a " "'d string, so lets alter "'s
        lib_hidden_field('text', preg_replace('/"/', '&quot;', $text));
        return(1); # Done, text description ready
    } else {
        $out .= "Explanation for the prime (will be linked to the prime, use NULL for
        none): (<a href=help/blob_guidelines.php#text>guidelines</a>)<p>";
        if ($help) {
            $out .= "<p><font color=green>Help note: This should explain what
	 this this number is.  You may use HTML, links... just keep it under 16k.
	Try to make it as accessable to the general public as possible. As
	always, our editors reserve the right to edit all entries.</font></p>";
        }
  # NO     # This is in a " "'d string, so lets alter "'s
  # urls!   lib_hidden_field('text',preg_replace('/"/','&quot;',$text));
        $out .= "<blockquote><textarea name=text cols=80 rows=7>$text</textarea><P>
	</blockquote>";
        return(0); # Not done
    }
}





// basic_create_blob($description,$text,$digits,$log10,$full_digits,$xx_person_id)
// creates a new prime_blob and returns its id.  If the blob is already there,
// then it returns the negative of its id.

function basic_create_blob($description, $text, $digits, $log10, $full_digits, $xx_person_id)
{
  # See if the blob is already there--if so, return negative of its database id.
  # lib_get_column($where, $table, $fields, $db)
    $blob_id = lib_get_column("full_digit = '$full_digits'", 'prime_blob', 'id', $GLOBALS['db']);
    if ($blob_id > 0) {
        return(-$blob_id);
    }

  # Form the $query, do the work.
    $query = "INSERT prime_blob (text,description,digits,log10,full_digit,person_id,modified,created,id)
        VALUES(:text,:description,:digits,:log10,:full_digits,:person_id,NOW(),NOW(),NULL)";
    try {
        $sth = $GLOBALS['db']->prepare($query);
        $sth->bindValue(':description', $description);
        $sth->bindValue(':text', $text);
        $sth->bindValue(':digits', $digits);
        $sth->bindValue(':log10', $log10);
        $sth->bindValue(':full_digits', $full_digits);
        $sth->bindValue(':person_id', $xx_person_id);
        $success = $sth->execute();
        $insert_id = $GLOBALS['db']->lastInsertId();
    } catch (PDOException $ex) {
        lib_mysql_die("basic_create_blob failed (434): " . $ex->getMessage, $query);
    }

    if ($success) {
        return ($insert_id);
    }

    lib_mysql_die("basic_create_blob failed? query succeeded, but no
        rows were effected!", $query);
    return(null);
}

// Mail results, add success or failure method to $out, and return 1 or 0
// if succeeds.

function mail_result($blob_id)
{
# global $text, $full_digits
    global $description, $xx_person_id, $db;
    global $out, $_SERVER, $HTTP_USER_AGENT, $HTTP_ACCEPT_LANGUAGE;

    $short = $_REQUEST['short'];

  # get data from database

    $temp = basic_get_person_from_id($xx_person_id, 'username,email,name', $db);
    if (empty($temp)) {
        return
        get_the_person("Sorry, but '$xx_person_id' is not a valid id.");
    }
    $username = $temp['username'];
    $email = $temp['email'];
    $name = $temp['name'];
    $description = (empty($description) ? '[description field left empty]' :
    $description );

    $mail_to = basic_address('content editor');
    $mail_subject = "New prime_blob: $short by $username";
    $mail_text = "There has been a new prime_blob [blob_id=$blob_id] added by\n
	$name ($email):\n
	short form:  $short\n
	description: $description\n\n\n";
    $more_text = "You may see, edit... this entry at\n
	https://t5k.org/primes/admin/index.php?xx_TableName=" .
    "prime_blob&xx_edit=$blob_id
    \nTechnical info about the submitter: \n\n\tConnected from: " .
    $_SERVER["REMOTE_ADDR"] .
    "\n\tvia: $_SERVER[HTTP_USER_AGENT]\n\taccepts languages: " .
        "$_SERVER[HTTP_ACCEPT_LANGUAGE]\n";
    $mail_headers = "From: Prime automailer for <$email>\n";
    if (mail($mail_to, $mail_subject, $mail_text . $more_text, $mail_headers)) {
        $out .= "<b>Warning--you are not yet done!!!</b>

	<p>You have successfully added an entry to the auxiliary table of
	\"prime blobs\".&nbsp;  This table stores the numbers that are too long
	or too difficult to calculate/&nbsp; These entries <i>can</i> be linked
	to from the main database table, but this linking is <i>not</i>
	automatic!&nbsp; You may begin to enter this prime to the list of
	largest known primes <b>by using the button below</b> or later by
	entering \"prime_blob_$blob_id\" as the description of the prime in the
	<a href=submit.php>usual submission page</a>.

	<p>The following mail has also been sent to the editor
	<blockquote>" .
        nl2br(htmlentities("To: editor
	Subject: $mail_subject
	$mail_headers
	$mail_text")) . "</blockquote>";
        return(1);
    } else {
        $out .= lib_die("Sendmail failed! This should not happen, use a different
      method to let our technical editor know : " . basic_address('errors to'));
        return(0);
    }
}
