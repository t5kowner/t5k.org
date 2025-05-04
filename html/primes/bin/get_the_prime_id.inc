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
# message, and $edit_[fieldname] which will be set iff the user requests
# to re-edit the field.  They also append information necessary for submit2.php
# to the global $carry_forward.


##### GET A PRIME's ID
#
# ???
#
# Set the global variable $get_the_prime_id_query to alter the query...
#
# Remember many routines use this--leave the wording as general as possible

include_once('bin/modify.inc');

function get_the_prime_id($error_message = '')
{
    global $out, $help, $you_requested;
    global $get_the_prime_id_query; # might be set to alter the query

    if (empty($get_the_prime_id_query)) {
        $get_the_prime_id_query = "Database ID for the prime in question?";
    }

  # Info from the form
    $xx_prime_id = (isset($_REQUEST['xx_prime_id']) ? $_REQUEST['xx_prime_id'] : '');
    $edit    = (isset($_REQUEST['edit_prime_id']) ? $_REQUEST['edit_prime_id'] : '');

  # First, print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = '<div class="alert alert-danger m-5" role="alert">ERROR: ' . "$error_message</div>\n";
    }
    $out .= $error_message;

  # First, is it an integer?
    if (empty($error_message) and !empty($xx_prime_id)) {
        if (!preg_match('/^\d+$/', $xx_prime_id)) {
            return get_the_prime_id('Incorrect database id for prime');
        }
      # if so, is does it correspond to a prime?
        $description = lib_get_column(
            "id=$xx_prime_id",
            'prime',
            'description',
            $GLOBALS['db']
        );
        if (empty($description)) {
            return get_the_prime_id('This id does not
	correspond to a prime in the database');
        }
    }

    if (!empty($error_message) or !empty($edit) or empty($xx_prime_id)) {
        $out .= $get_the_prime_id_query;
        if ($help) {
            $out .= "<p><font color=green>Help note: This field may contain
	only id numbers associated with primes in the database.  Find the page
	on the individual prime in question to see the id.</font></p>";
        }
        $out .= "<blockquote>\n  <input type=text size=10  class=\"form-control rounded-2\" style=\"width:150px;\" name=xx_prime_id " .
        basic_tab_index() . "value=\"$xx_prime_id\">\n</blockquote>\n";
        return(0);
    }

  # Okay, we have something.

    $out .= "<P>Prime number with id $xx_prime_id:" . edit_button('prime_id') .
    "<blockquote>$description</blockquote>";
    $out .= lib_hidden_field('xx_prime_id', $xx_prime_id);

    return(1);
}
