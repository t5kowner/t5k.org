<?php

# maybe move create short from long to basics?

$t_banner_message_all_submit_pages = '';

$allow_submissions = 1;  # 1 to allow, 0 to disallow

# Form varibles: $number_id, $short, $long, $curio_text, $lastname, $agree, $help
#   $done, and $edit_???. (If $help set, prints help sentences before the fields, $edit
#   is set to the name of a field to re-edit: number_id (redoes short, long)
#   curio_text, lastname, or agree.  $edit_{fieldname} is set (e.g.,
#   $edit_lastname) if the user has requested to go back and edit that field.
# Page variables: built in $out.  $done is set when the form is approved (every
#   thing checked and okayed by the user) otherwise is used by the program
#   to make sure each of the checking routines succeeded, and if so, then the
#   approve curio button is sent.
# Each form field ($number_id, $short, $long together; $curio_text, $lastname,
#   and $agree) has a routine to send out th appropriate form input boxes and
#   test the results when done.  These are get_the_number...  These routines
#   all add to $out which is the page we send the browser.

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

# Register and untant form variables.  $long and $short set by  basic_number_long_via_id()
# from $number_id (if it is set) otherwise expects $long.
# $curio_text untainted below.

$agree = (isset($_REQUEST['agree']) ? $_REQUEST['agree'] : '');
  $agree = preg_replace('/$[^\w ]*$/', '', $agree);
$number_id = (isset($_REQUEST['number_id']) ? $_REQUEST['number_id'] : '');
if (!is_numeric($number_id)) {
    $number_id = '';
}
$long = (isset($_REQUEST['long']) ? $_REQUEST['long'] : '');
  $long = preg_replace('/[^\d\s,.\-]/', '', $long);
$short = (isset($_REQUEST['short']) ? $_REQUEST['short'] : '');
  $short = preg_replace('/[^\d\s,.\-]/', '', $short);
$done = (isset($_REQUEST['done']) ? 1 : '');
$lastname = (isset($_REQUEST['lastname']) ? $_REQUEST['lastname'] : '');
  $lastname = preg_replace('/[<>\'"`\\"\/:]/', '', $lastname);
$address = (isset($_REQUEST['address']) ? $_REQUEST['address'] : '');
  $address = preg_replace('/[^@.\w\d]/', '', $address);
$curio_text = (isset($_REQUEST['curio_text']) ? stripslashes($_REQUEST['curio_text']) : '');

# Collect and untaint any edit requests.

$edit_number_id = (isset($_REQUEST['edit_number_id'])   and
    $_REQUEST['edit_number_id']  == 'change' ? 'change' : '');
$edit_curio_text = (isset($_REQUEST['edit_curio_text']) and
    $_REQUEST['edit_curio_text'] == 'change' ? 'change' : '');
$edit_lastname = (isset($_REQUEST['edit_lastname'])     and
    $_REQUEST['edit_lastname']   == 'change' ? 'change' : '');
$edit_address = (isset($_REQUEST['edit_address'])       and
    $_REQUEST['edit_address']    == 'change' ? 'change' : '');
$edit_agree = (isset($_REQUEST['edit_agree'])           and
    $_REQUEST['edit_agree']      == 'change' ? 'change' : '');

# $help is set (unless we are done editing that field).
#
# Help is run with a form button named either 'help' (add help) or
# 'no_help' (to remove the help).  When the help is on a hidden 'help' field is
# added to keep it on until the 'no_help' button is pressed.
#
$help = (!empty($_REQUEST['help']) and empty($_REQUEST['no_help']));
#
# We will build the appropriate button now in $help and add it to the page
# output ($out) below.
#
$help_str = '<input type=submit class="' . $GLOBALS['mdbltcolor'] . '" ';
if ($help) {
    $help_str .= 'value="Click to remove help" name="no_help">' . "\n";
    $help_str .= lib_hidden_field('help', 1);  # Help should stay on until turned off
} else {
    $help_str .= 'value="Click here to add help" class="ml-3" name="help">' . "\n";
}


# Global string for each form field
$you_requested = "<div class=\"my-2 alert-success font-weight-bold\">(You requested to re-edit this field!)</div> ";

include_once("bin/modify.inc"); # Don't move, causes an error inside a function

$validated = 1;

$out = '<div class="might_hide">';
$validated *= get_the_number(); # Each either prints an edit box, or displays
$validated *= get_the_curio();  # the results already approved from the form
$validated *= get_the_name();   # and returns 1 iff suceeded
$validated *= get_the_address();
$validated *= get_the_aggreement();
$validated *= $allow_submissions;   # Set above--so 0 will block all submissions

$out .= '</div>';

if (!empty($done) && $validated) { # All done, form checked and approved, so lets submit and mail
    $out .= '<style>.might_hide { display: none }</style>';

  # Need to submit the curio, mail the editor and that's all folks!
    if (
        !($curio_id = basic_create_curio(
            $curio_text,
            $number_id,
            'no',
            ($lastname == 'anonymous' ? '' : $lastname),
            $address
        ))
    ) {
        die("The command<P>basic_create_curio(\$curio_text,$number_id,'no',$lastname)
	<P>failed.  This should not happen, mail this info to admin@t5k.org");
    }

    include_once("bin/log.inc");
    log_action($db, $lastname, 'created', "curios.id=$curio_id", "$address");

    mail_result($curio_id); # Appends the success, error message to $out;
} else {
    if ($validated) { # If so, then we have a complete curio, submit it?
        $out = "<p>We are now ready to finalize your submision.&nbsp; Press the button below to
 	complete your submission or press a 'change' button to alter your submission.</p>
	<form method=post action=\"$_SERVER[PHP_SELF]\">
          <input type=submit class=\"$mdbltcolor\" value=\"Press here to complete submission\" name=done" . basic_tab_index() . ">
          <p>$out
          <input type=submit class=\"$mdbltcolor mb-5\" value=\"Press here to complete submission\" name=done" . basic_tab_index() . ">
        </form>
	<p>You must press one of the submission buttons above to complete your submission and move on to the confirmation page.</p>";
    } else {
        $out = "<p>Note that all five of the questions below must be answered (number, curio text, last name,
	e-mail address and agree) for a curio to be accepted.&nbsp; To encourage higher quality, submissions
	 are limited to one per day (see our
	<a href=\"https://t5k.org/curios/includes/guidelines.php\">guidelines</a>).</p>&nbsp;
	<form method=post action=\"$_SERVER[PHP_SELF]\">
	  <input type=submit class=\"$mdbltcolor\" value=\"Press here to submit number and curio\"" . basic_tab_index() . ">
          $help_str
          <p>$out
          <input type=submit class=\"$mdbltcolor mb-5\" value=\"Press here to submit number and curio\"" . basic_tab_index() . ">
        </form>";
    }
}

# Set up variables for the template
$t_text = $t_banner_message_all_submit_pages . $out;
$t_title = "Submit a Curio!";

include("template.php");

function edit_button($field_name)
{
    return "\n<input type=submit class=\"$GLOBALS[mdbltcolor] ml-2\" value=change name=\"edit_$field_name\">\n";
}

##### Step one:  THE NUMBER
#
# We already have it if $number_id set, might have it if $long set.
# If something goes wrong, calls itself again with $error_message set
# and ends with edit fields.  If nothing goes wrong, prints short form
# and imbeds number_id as a hidden field--then returns 1, else 0.
#   For these routines $error_message is a message from the routine to
# itself, because it calls itself if it finds an error.  They also
# call themselves if processing the field succeeds.

function get_the_number($error_message = '')
{
    global $short, $long, $number_id; # form variables we are setting
    global $out, $help, $edit_number_id; # other form and page variables
    global $you_requested;

  # First, print any error message (continue editing in this case!)
    if ($edit_number_id) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>";
    }
    $out .= $error_message;

  # First case: no error, no request to edit, and have the number's id -- so we print it

    if (empty($error_message) and is_numeric($number_id) and empty($edit_number_id)) {
      # If $number_id set, this part is done and the number
      # is in the database, so just print the number and move on
        $short = basic_number_short_via_id($number_id); # Should be id or NULL.
        $long = basic_number_long_via_id($number_id);
        if (! isset($short)) {
            get_the_number("Bad number_id ($number_id), failed to get short form!");
        }
        if (! isset($long)) {
            get_the_number("Bad number_id ($number_id), failed to get long form!");
        }

        $out .= "\n<p>What curio do you wish to submit for the following number?</p>
      <blockquote title=\"number_id = $number_id\">$short\n" .
        lib_hidden_field('short', $short) .
        edit_button('number_id') . "</blockquote>\n" . lib_hidden_field('long', $long) .
        lib_hidden_field('number_id', $number_id);
        # Need long if the user chooses to go back and edit again
        return(1);
    }

  # zero merits a special case! (again assumes no error message, no edit request)
  # Grab 0's number_id and recycle through this routine.

    if (empty($error_message) and isset($long) and $long == 0 and is_numeric($long) and empty($edit_number_id)) {
      # Special case, the number zero!
        $long = 0;  # Might they have submitted -0.0 ?
        $short = 0;
        if ($number_id = basic_number_id_via_short($short)) {
          # Now have the number, lets print it and move on
            return get_the_number(); # Recycle--this time $number_id will be set
        }

    # Non-zero number, no id yet, no error message and no edit request
    } elseif (empty($error_message) and !empty($long) and empty($edit_number_id)) {
      # They have filled in the number part of the form, let's process it
      # we check the long form first--using it to make a short form

      ### if (!empty($long)) { # Ah, they gave us a long form, lets shorten it
        # Remove any spaces, commas, continuation lines '\\\n';
        # Why do I need \\\\ to match \, but not \\s for whitespace?
        $new_long = preg_replace("/(\s+|\n|\\\\|,)/", '', $long);

        # Are there any unnecessary leading zeros?
        if (preg_match("/^(-?)(0+)(\.?)(.*)$/", $new_long, $match)) {
            $new_long = $match[1] . (empty($match[3]) ? '' : '0') . $match[3] . $match[4];
        }

        # Currently three valid long forms
        $digits = strlen($new_long);

        ## Integers
        if (preg_match("/^(-?)\d+$/", $new_long, $match)) {
            if ($match[1] == '-') {
                $digits -= 1; # minus signs don't count as digits
            }
            if ($digits < 24) {
                $short = $new_long;
            } elseif (preg_match("/^(-?)(\d{5})\d+?(\d{5})$/", $new_long, $match)) {
                $short = $match[1] . $match[2] . '...' . $match[3] . " ($digits-digits)";
            }

        ## Terminating decimal expansions
        } elseif (preg_match("/^(-?)(\d+)\.\d+$/", $new_long, $match)) {
            if ($match[1] == '-') {
                $digits -= 1; # minus signs don't count as digits
            }
            if ($digits < 25) {
                $short = $new_long;
            } elseif (
                    ($temp = (20 - strlen($match[2]))) > 0 and
                    preg_match("/^(-?\d+\.\d{1,$temp})/", $new_long, $match)
            ) {
                $short = "$match[1]...";
            } else {
                return get_the_number("This form is probably valid, but this routine
		can not yet handle it.  You will need an editor's help...");
            }

         ## Non-terminating decimal expansions
        } elseif (preg_match("/^(-?)(\d+)\.\d+\.\.\.$/", $new_long, $match)) {
            if ($match[1] == '-') {
                $digits -= 1; # minus signs don't count as digits
            }
            if ($digits < 25) {
                $short = $new_long;
            } elseif (
                    ($temp = (20 - strlen($match[2]))) > 0 and
                    preg_match("/^(-?\d+\.\d{1,$temp})/", $new_long, $match)
            ) {
                $short = "$match[1]...";
            } else {
                return get_the_number("This form is probably valid, but this routine
		can not yet handle it.  You will need an editor's help...");
            }
        } else {
            return get_the_number("Invalid long form for a number.  Can be
	  one of the three forms 234, 234.234 or 234.234...\n");
      # (or these with a negative sign prepended).\n");
        }
      ###}

      # Finally lets add spaces to long

        $long = $new_long;
        $long = preg_replace("/^(\d+)(\d{10})$/", "\\1 \\2", $long);
        while (preg_match("/^(\d+)(\d{10} .*)$/", $long, $match)) {
            $long = "$match[1] $match[2]";
        }

      # Here we should have a valid short form, we either find it in database

        if ($number_id = basic_number_id_via_long($new_long)) {
          # Now have the number, lets print it and move on
            return get_the_number(); # Recycle--this time $number_id will be set
        }

      # or create it

        if ($error = basic_check_if_valid($short)) { # Returns '' if valid
            return get_the_number($error); # recycle, print error this time
        }
      # Now it has been proven valid, then create this new number
        $number_id = basic_create_number($short, $long);
        return get_the_number(); # Recycle--this time $number_id will be set

    # Otherwise must have either an error message or no input yet
    } else {
      # Next get the number
        $out .= "<p>What number do you want to submit a Curio about?</p>
      <blockquote>
	<p>Input the number (all of the digits):</p>\n";

        if ($help) {
            $out .= '<div class="m-3 alert-success"><p><b>Help note:</b> If the number is already on
	the Prime Curios! page (and you want to add a new curio), go to that page
	and press \"submit curio\" there--this part of the form will be filled in for
	you!</p>

	<p>Otherwise, simply enter the
	entire number.&nbsp;  If it has 1000 digits, list them all.&nbsp;  They
	can be in one long line of digits, or have spaces every 10 characters,
	or have lines terminated by backslashes (common in UNIX programs),
	or even have commas.&nbsp;  All spaces, linefeeds, backslashes, tabs and
	commas will be eventaully removed by the program, but they may help you
	edit your entry.</p></div>' . "\n";
        }

        $out .= '<textarea name="long" cols="60" rows="7"' . basic_tab_index() . ">$long</textarea><P>
	</blockquote>\n";
    }
    return(0); # Not done--got here via printing edit fields
}

function get_the_curio($error_message = '')
{
    global $curio_text; # form variables we are setting
    global $out, $help, $edit_curio_text; # page and other variables
    global $you_requested;

  # First, print any error message (continue editing in this case!)
    if ($edit_curio_text) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></p>";
    }
    $out .= $error_message;

    global $modify_ErrorMessage;
    if (empty($error_message) and !empty($curio_text)) {
      # We need to ballance the text (close unclosed html tags, remove bad ones)
        $curio_text = modify_adjust_html($curio_text);

        if (!empty($modify_ErrorMessage)) {
            return get_the_curio("The following
	problem(s) with your curio were fixed (if it is okay now, just press
	submit): " . $modify_ErrorMessage);
        }

      # Now the changes that will be made at the time the page is displayed
      # Perhaps has TeX like entities we need to translate!
        $show_text = modify_entities($curio_text);
      # Definitely expect words to cross-link
        $show_text = modify_add_links($show_text);

        $out .= "\n<p>Proposed Curio text will look like (unless an editor changes it):</p>\n" .
        "<blockquote>\n$show_text" . edit_button('curio_text') . "</blockquote>\n" .
        # This is in a " "'d string, so lets alter "'s
        lib_hidden_field('curio_text', preg_replace('/"/', '&quot;', $curio_text));
        return(1); # Done, curio ready
    } else {
        $out .= "Proposed Curio text: (<a href=\"includes/guidelines.php\">guidelines</a>,
	the curio <i>must</i> be related to prime numbers)\n";
        if ($help) {
            $out .= '<div class="m-3 alert-success"><p><b>Help note:</b> This should be a short
	pithy statement about the number.&nbsp; Try to make it as accessable to the
	general public as possible, without making it long.&nbsp; Our editors
	reserve the rights to reword all Prime Curios! (one of the main jobs
	of any editor!)</p></div>' . "\n";
        }
        $out .= "<blockquote>\n" . '  <textarea name="curio_text" cols="60" rows="7"' . basic_tab_index() .
        ">$curio_text</textarea>\n</blockquote>";
        return(0); # Not done, Curio not submitted
    }
}


function get_the_name($error_message = '')
{
    global $lastname; # form variables we are setting
    global $out, $help, $edit_lastname; # other form and page variables
    global $you_requested;
    global $db;

  # First, print any error message (continue editing in this case!)
    if ($edit_lastname) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>";
    }
    $out .= $error_message;

    $lastname = trim($lastname);  # Clean up unnecessary whitespace
    $lastname = preg_replace("/\s+/", ' ', $lastname);

  # look for invalid characters
    if (empty($error_message) and preg_match("/\&/", $lastname)) {
        return get_the_name("Please use 'and' rather than &amp;.  See the help.");
    }
    if (empty($error_message) and preg_match("/([^\w.,;\- ]+)/", $lastname, $match)) {
        return get_the_name("Illegal character \"" . $match[1] . "\" in credit string.");
    }

    if (!empty($error_message) or empty($lastname)) {
        $out .= "Credit to who?&nbsp;  Usually the last(=family) name, or
	'anonymous' for anonymous\n";
        if ($help) {
            $out .= '<div class="m-3 alert-success"><p><b>Help note:</b> Use your last name, or the
	last name of the person you are crediting (Pomerance, Honaker...).&nbsp; If
	you would like "credit" for submitting what someone else did, you
	can put two names: "Pomerance; Caldwell" with yours second (note
	semi-colon).&nbsp; If several
	folks need credit, use commas: "Pomerance, Brent" (or, if you
        must, "Pomerance and Brent")</p></div>' . "\n";
        }
        $out .= "<blockquote>\n <input type=text name=lastname value=\"$lastname\"" . basic_tab_index() .
        "size=20>\n</blockquote>\n";
    } else {
      # We need to limit to one per day...
        $where = "NOW()-created < 86400 AND submitter=" . $db->quote($lastname) . " ORDER BY created DESC";
        $temp = lib_get_column($where, 'curios', 'id, TIMEDIFF(NOW(),created) as diff', $db);
      // Performs "SELECT $fields FROM $table WHERE $where LIMIT 1"
      # print "<li>diff $temp[diff] id $temp[id]";
        if (!empty($temp['id'])) {
            preg_match('/(\d\d):(\d\d):(\d\d)/', $temp['diff'], $temp2);
            return get_the_name("You have already submitted <a class=\"none\"
	href=\"https://t5k.org/curios/page.php?curio_id=$temp[id]\">a curio</a>
	today.&nbsp;  Everyone is limited to one curio each 24 hours (your last submission
	was " . ($temp2[1] + 0) . ' hours, ' . ($temp2[2] + 0) . ' minutes and ' . ($temp2[3] + 0) . ' seconds ago).&nbsp; Thank-you for your
	submissions.');
        } else {
            $out .= "<P>Crediting: $lastname" . edit_button('lastname') . "</P>\n";
            $out .= lib_hidden_field('lastname', $lastname);
            return(1);
        }
    }
    return(0); # Not done--got here via printing edit fields
}

function get_the_address($error_message = '')
{
    global $address; # form variables we are setting
    global $out, $help, $edit_address; # other form and page variables
    global $you_requested;

  # First, print any error message (continue editing in this case!)
    if ($edit_address) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>";
    }
    $out .= $error_message;

    $address = trim($address);  # Clean up unnecessary whitespace
    $address = preg_replace("/\s+/", ' ', $address);

    if (!empty($error_message) or empty($address)) {
        $out .= "What e-mail address can we contact if we have questions?\n";
        if ($help) {
            $out .= '<div class="m-3 alert-success"><p><b>Help note:</b> Sometimes we need
	more information or clarification.&nbsp; If this is the case, who do
	we e-mail? (This address will not be published in any way without
	your permission!)</p></div>' . "\n";
        }
        $out .= "<blockquote>\n  <input type=text name=address value=\"$address\"" . basic_tab_index() .
        "size=35>\n</blockquote>\n";
    } else {
        if (preg_match("/^[\w\.\-]+\@[\w\.\-]+\.\w\w+$/", $address)) {
            $out .= lib_hidden_field('address', $address) .
            "<P>Contact address: $address " . edit_button('address') . "</p>";
            return(1);
        } else {
            return get_the_address("We need a valid contact address");
        }
    }
    return(0);
}

function get_the_aggreement($error_message = '')
{
    global $agree; # form variables we are setting
    global $out, $help, $edit_agree; # page and other form variables
    global $you_requested;

  # First, print any error message (continue editing in this case!)
    if ($edit_agree) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>";
    }
    $out .= $error_message;

    if (empty($agree) or !empty($error_message)) {
        $out .= "Finally, do you agree to our <a target=blank_
	href=\"./includes/copyright.php\">
	licensing agreement and copyright rules</a>?\n";
        if ($help) {
            $out .= '<div class="m-3 alert-success"><p><b>Help note:</b> Basically we want to be able to
	later make a book if we so desire.&nbsp;  Or a CD-ROM, or whatever.&nbsp;  So read the pages,
	and if you do not want to let us use your submission(s) how we would like later,
	don\'t send them to us.</p></div>' . "\n";
        }
        $out .= "<blockquote><input type=radio name=agree value=yes " . basic_tab_index() .
        ($agree == 'yes' ? 'checked>' : '>') . " yes &nbsp; or &nbsp;
	<input class=\"mt-3\" type=radio name=agree value=no " . basic_tab_index() .
        ($agree == 'no' ? 'checked>' : '>') . " no</blockquote>\n";
    } elseif ($agree == 'no') {
        get_the_aggreement("Curios can not be added without agreeing to the conditions.");
    } else {
        $out .= lib_hidden_field('agree', $agree) . "<P>Agreed to copyright." .
          edit_button('agree') . "</p>\n";
        return(1);
    }
    return(0); # Not done--got here via printing edit fields
}

// Mail results, add success or failure method to $out, and return 1 or 0
// if succeeds.

function mail_result($curio_id)
{
    global $curio_text, $number_id, $short, $lastname, $address;
    global $out, $_SERVER, $HTTP_USER_AGENT, $HTTP_ACCEPT_LANGUAGE;

    $address = preg_replace('/[^@.\w\d]/', '', $address);
    $mail_to = basic_address('content editor');
    $mail_subject = "New Prime Curio about $short by $lastname";
    $mail_text = "There has been a new curio submitted for your approval:
    \n$short [number_id=$number_id]\n\n$curio_text [$lastname]\n\nSubmitter's e-mail address: $address\n\n\n";
    $more_text = "You may see, make visible, edit... this entry at\n
	https://t5k.org/curios/page.php?number_id=$number_id&edit=2\n";
  #  \nTechnical info about the submitter: \nConnected from: ".
  #  $_SERVER["REMOTE_ADDR"].
  #  "\nvia: $HTTP_USER_AGENT\naccepts languages: $HTTP_ACCEPT_LANGUAGE\n";
    $mail_headers = 'From: Prime Curios! automailer for <' . basic_address('errors to') . ">\n";
    if (mail($mail_to, $mail_subject, $mail_text . $more_text, $mail_headers, '-fadmin@t5k.org')) {
        $out .= '<p>The following mail has been successfully sent.  Your Curio
	will appear only after an editor has viewed and approved it.&nbsp; This
	may take up several weeks at times of peak submissions and when the
	editors get busy in their day jobs! (Remember that all curios unrelated
	to primes will be rejected!)</p><blockquote>' .
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
