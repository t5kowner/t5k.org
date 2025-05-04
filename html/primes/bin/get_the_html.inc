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


##### GET A HTML COMMENT
#
# Basic HTML field with modifications to adjust the text...
#
# Works with the form field xx_html
#
# Set the global variable $get_the_html_query to alter the query...
#
# Remember many routines use this--leave the wording as general as possible

include_once('bin/modify.inc');

function get_the_html($error_message = '')
{
    global $out, $help, $you_requested;
    global $get_the_html_query, $get_the_html_max_length;   # might be set to alter the query

    if (empty($get_the_html_query)) {
        $get_the_html_query = "Comments? (<A href=\"/bios/help/modify_html.php\">Help page</A>)";
    }

    if (empty($GLOBALS['get_the_html_box_width'])) {
        $get_the_html_box_width = 80;
    } else {
        $get_the_html_box_width = $GLOBALS['get_the_html_box_width'];
    }

  # How long can the submitted text be?
    if (!($get_the_html_max_length > 0)) {
        $get_the_html_max_length = 2000;
    }

  # Info from the form
    $xx_html   = stripslashes(isset($_REQUEST['xx_html']) ? $_REQUEST['xx_html'] : '');
    $edit      = (isset($_REQUEST['edit_html']) ? $_REQUEST['edit_html'] : '');

  # First, print any error message (continue editing in this case!)
    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = '<div class="alert alert-danger m-5" role="alert">ERROR: ' . $error_message . "</div>\n";
    }
    $out .= $error_message;

    global $modify_ErrorMessage;
    $xx_html = modify_adjust_html($xx_html);  # Ballance HTML, remove "evil" code
    if (empty($error_message)) {
        if (!empty($modify_ErrorMessage)) {
            return get_the_html($modify_ErrorMessage);
        }
    }

    if (!empty($error_message) or !empty($edit) or empty($xx_html)) {
        $out .= $get_the_html_query;
        if ($help) {
            $out .= "<p><font color=green>Help note: This field may contain simple
	HTML or just plain text.  (<A href=\"/bios/help/modify_html.php\">Help page</A>)
	</font></p>";
        }
        $out .= "<blockquote>\n<textarea class=\"form-control rounded-2\" name=xx_html rows=6 " . basic_tab_index()
        . ">$xx_html</textarea>\n</blockquote>\n";
        return(0);
    }

  # Okay, we have something.

    if (strlen($xx_html) > $get_the_html_max_length) {
        return(get_the_html("Submissions for this field can not be longer than $get_the_html_max_length characters.
	Yours is " . strlen($xx_html) . " characters."));
    }

    $out .= "<P>Comment:" . edit_button('html') .
    "<blockquote>" . modify_entities($xx_html) . "</blockquote>";
    $out .= lib_hidden_field('xx_html', htmlentities($xx_html));

    return(1);
}
