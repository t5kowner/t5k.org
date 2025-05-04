<?php

#t# ready

#####  Generic notes about these form field parts
#
# The idea is to write a routine to test, approve and process each of the
# key form fields.  They return 1 if done, 0 if not and at the same time add
# any appropriate text to the $out global variable--which will be the web page
# returned to the client.
#
# For these routines $error_message is a message from the routine to
# itself, because it calls itself if it finds an error.  They add output
# to the global variable $out (which will be the web page returned) and
# also read the globals: $help (non-empty if help text is to be displayed)
# $you_requested which is simply a "you requested to edit this field"
# message, and $edit_[field name] which will be set iff the user requests
# to re-edit the field.  They also append information necessary for submit2.php
# to the global $carry_forward.


##### GET A PERSON
#
# Who is making this submission?  We seek this info from the form field named
# username (which can be the username or an id number), or from the hidden
# field $xx_person_id (which is added to the output as a hidden field and is
# set as a global variable when this routine succeeds).
#
# If things go smoothly, the persons name, email address and its visibility,
# and a link to the edit bio pages is added; Also the global $persons_codes
# is set with a text list of the persons codes for use in bin/get_the_code.inc;
# and the globals $username, $xx_person_id are also set for other routines.
# If the data is not found (or is incorrect) then the edit field is
# again given to the client (usually with an error message).
#
# Set the global variable $get_the_person_only to 'person' to limit to persons.
# (or to some other type to limit to that other type).
#
# Set the global variable $get_the_person_query to alter the  query for id...
# note that a phrase will be added to it...
#
# Remember many routines use this--leave the wording as general as possible
# speak of "a submission" rather than "a prime"...

function get_the_person($error_message = '')
{
    global $username, $xx_person_id;    # Often used by programs
    global $persons_codes;      # primes/submit.php uses this to verify codes...
    global $get_the_person_only;    # might be set to 'person' to limit to persons
    global $out, $help, $you_requested;
    global $db;

    if (empty($GLOBALS['get_the_person_query'])) {
        $get_the_person_query =
        "Who should we contact with questions about this submission?";
    } else {
        $get_the_person_query = $GLOBALS['get_the_person_query'];
    }
  // trust the caller to have translated $get_the_person_query if it is set.
  // This routine calls itself, so do not use 'global $get_the_person_query'
  // or we might translate the translation

  # Sometimes the query is "Enter the database ID or username of your
  # prover-account." so why add it again?
    if (!preg_match('/the database ID/', $get_the_person_query)) {
        $get_the_person_query .= ' (Enter the database ID or username of your prover-account)';
    }

  # Info from the form

    $xx_person_id = (isset($_REQUEST['xx_person_id']) ?
    $_REQUEST['xx_person_id'] : '');
    $xx_person_id = preg_replace('/[^\d]/', '', $xx_person_id);
    $username  = (isset($_REQUEST['username']) ? $_REQUEST['username'] : '');
    $username  = preg_replace('/[\s<>"\'!@#%^&+\-[\]]/', '', $username);
    $edit      = (isset($_REQUEST['edit_username']) ? 'change' : '');

  #print "<li>username [$username]";
  #print "<li>xx_person_id [".htmlentities($xx_person_id)."]";
  #print "<li>edit [$edit]";

  # The form field is 'username', so if the xx_person_id is set, then we
  # already identified the person and can move on (unless $edit_username
  # is set).  Otherwise, we look at username--it might be an id or an
  # actual username (in which case we need to look up the id).

  # First, print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = '<div class="alert alert-danger m-5" role="alert">ERROR:' . " $error_message</div>";
    }
    $out .= $error_message;

    $username = trim($username);  # Clean up unnecessary whitespace

  # Do we need to ask for the username?  (will be filled in if xx_person_id set)

    if (!empty($error_message) or !empty($edit) or (empty($xx_person_id) and empty($username))) {
        if (empty($username)) {
            $username = $xx_person_id;  # E.g., when editing field
        }
        $out .= $get_the_person_query;
        if ($help) {
            $out .= "<p><font color=green>" .
            "Help note: This person indicated here is one who can answer questions about this submission.
	They must either be an editor, or one of the persons listed in our database.  If you are
	submitting a prime, the person listed must also be listed as prover for the prime.  Those
	creating a proof-code will be part of the code. This person's password may be required for
	final submission.</font></p>";
        }
        $out .= "<blockquote>\n  <input type=text class=\"form-control rounded-2 w-50 w-med-25\" name=username value=\"$username\"" . basic_tab_index()
        . " size=20>\n</blockquote>\n";
        return(0);
    }
#echo "3";
  # Okay, we have something.  username is not empty--get xx_person_id.

    if (preg_match("/^\d+$/", $xx_person_id)) {
      # Should have all info then
    } elseif (preg_match("/^\d+$/", $username)) { # Should be a person's id number
        $xx_person_id = $username;
    } else {
      # print "<LI> seeking $username";
        $temp = basic_get_person_from_username($username, 'id', 'also check surnames');
        if (empty($temp)) {
            return get_the_person(sprintf(
                "Sorry, but there is no database entry with the username %s.",
                "'$username'"
            ) . ' ' . "The persons whose usernames you list must have biographical entries before you can list them here.");
        }
        $xx_person_id = $temp['id'];
    }

  # get data from database

    $temp = basic_get_person_from_id($xx_person_id, 'username,email,name,
	hide_email,codes,type,MaySubmitPrimes', $db);
    if (empty($temp)) {
        return get_the_person(sprintf(
            "Sorry, but there is no database entry with id %d.",
            "'$xx_person_id'"
        ));
    }
    $username = $temp['username'];
    $persons_email = $temp['email'];
  # Must hide e-mails, when requested, from other.  If logged in, don't hide.
    if (
        (!isset($GLOBALS['http_auth_id']) or $GLOBALS['http_auth_id'] != $xx_person_id)
        and $temp['hide_email'] != 'no'
    ) {
        $persons_email = '...';
    }
    $persons_name  = $temp['name'];
    $persons_codes = $temp['codes'];
    $persons_type  = $temp['type'];
    $persons_may_submit = $temp['MaySubmitPrimes'];

  # If get_the_person_only is set (usually to person), and the persons_type
  # does not match, the person_may_submit must be 'yes' or we die here with an
  # error message.  This may need to be altered if we every allow
  # get_the_person_only to be something other than a limit to persons.

    if (
        !empty($get_the_person_only) && ($get_the_person_only != $persons_type)
        && ($persons_may_submit != 'yes')
    ) {
        return get_the_person(sprintf(
            "Sorry, but %s is type %s, and only type(s) %s may submit.",
            $persons_name,
            $persons_type,
            $get_the_person_only
        ));
    }

    if ($temp['hide_email'] == 'yes') {
        $tt = 'e-mail address unpublished';
    } elseif ($temp['hide_email'] == 'munge') {
        $tt = 'e-mail address published munged';
    } else {
        $tt = 'e-mail address published';
    }

    $out .= '<p class="mt-3"><b>' . "Submitted by: &nbsp; #$xx_person_id: $username" .
    (function_exists('edit_button') ? edit_button('username') : '') .
    "<blockquote><a href=\"../bios/page.php?id=$xx_person_id\">$persons_name</a>
	($persons_email, $tt)</blockquote></b>\n(" . sprintf(
    "Use the %s page to update your address or change its visibility. Please keep it current!",
    "<a href=\"../bios/edit.php?xx_person_id=$xx_person_id\">Edit Biography</a>"
) . ")<P>\n";
    $out .= lib_hidden_field('xx_person_id', $xx_person_id);
  # When re-editing this field, username is nice
    $out .= lib_hidden_field('username', $username);
    $GLOBALS['carry_forward'] = (isset($GLOBALS['carry_forward']) ?
    $GLOBALS['carry_forward'] : '') .
    lib_hidden_field('xx_person_id', $xx_person_id);
    return(1);
}
