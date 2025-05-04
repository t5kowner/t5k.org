<?php

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



##### GET A CODE (assumes we use get_the_person first)
#
# What code are these to be submitted under?  This routine looks for
# information in the form field "code_str" and checks it against the person's
# list of codes ($persons_codes which is global from get_the_person()).
# If something goes wrong, calls itself again with $error_message set
# and ends with edit fields.  If nothing goes wrong, prints the long
# (linked HTML) form of the code credit string imbued the short form
# as the (now hidden) form field named "code_str" (also appended to the
# global $carry_forward).


function get_the_code($error_message = '')
{
    global $out, $help, $you_requested;
    global $basic_database_connection;  # database handle
    global $persons_codes;          # Defined in get_the_person();
    global $get_the_codes_id;       # Used in code_edit.php
    global $get_the_code_query;     # What question is asked?

  # Do we have a global query passed to us?

    if (empty($get_the_code_query)) {
        $get_the_code_query = "Enter the proof-code %s under which you would like to submit these primes";
    }

  # The prover should submit a code to store this prime under.  We must verify that
  # such a code exists and expand it into a html list (for the prover to verify
  # by choosing to confirm in the final step)

  # Of course if $persons_code has only one code--use it! else try the form data
    if (empty($_REQUEST['code_str']) and preg_match('/^\w{1,4}$/', $persons_codes)) {
        $_REQUEST['code_str'] = $persons_codes;
    }
    $code_str = (empty($_REQUEST['code_str']) ? '' : $_REQUEST['code_str']);
    $code_str = preg_replace('/ /', '', $code_str);  # remove any spaces

  # First, print any error message (continue editing in this case!)
    $edit = (isset($_REQUEST['edit_code']) ? $_REQUEST['edit_code'] : '');
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = "<p><font color=red>ERROR: $error_message</font></P>";
    }
    $out .= $error_message;

    if (empty($error_message) and !empty($code_str)) { # Might have it!
        if (empty($persons_codes)) {
            return get_the_code('It is necessary to fill in the person first, then the code');
        }
      # Codes are short, with only letters and digits (though this also allows underscores)
        if (!preg_match('/\w{1,4}/', $code_str)) {
            return get_the_code('Error: No Such Proof-code in Database');
        }
      # Lets see if this is in the person's code list
        if (!preg_match("/\b$code_str\b/", $persons_codes)) {
            return get_the_code(
                sprintf("The code %s is not one of your codes: %s", $code_str, $persons_codes)
            );
        }
      # Now get the linked list of the code's members
        $query = "SELECT display_html, id FROM code WHERE name='$code_str'";
        if ($sth = lib_mysql_query($query, $basic_database_connection, 'error get_the_code 80')) {
            if ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $get_the_codes_id = $row['id'];
                $temp = $row['display_html'];
            } else {
                $temp = "Error: No Such Proof-code in Database";
            }
            $out .= "\nCredit String (those credited with finding this prime)\n" .
            edit_button('code') .
            "<blockquote><b><a href=\"../bios/code.php?code=$code_str\">$code_str</a>&nbsp;
	  ($temp)</b></blockquote>\n" .
            lib_hidden_field('code_str', $code_str);
            $GLOBALS['carry_forward'] .= lib_hidden_field('code_str', $code_str);
            return(1); # Done, description ready
        } else {
            return get_the_code('Failed to get row from table using: ', $query);
        }
    } else {
        $temp = (empty($persons_codes) ? '' : "($persons_codes)");
        $out .= sprintf($get_the_code_query, $temp);
        if ($help) {
            $out .= "\n<p><font color=green>\nHelp note: The proof-codes are used in the
	database as a short way to refer to the provers. If you enter you last name in above, then
	you will be shown a list of your codes. Or just use the buttons on the bottom of your
	prover-account page to automatically fill in these first two fields.\n</font></p>";
        }
        $out .= "<blockquote>\n  <input type=text name=code_str size=4 " . basic_tab_index() .
        "value=\"$code_str\">\n</blockquote>\n";
        return(0); # Not done
    }
}
