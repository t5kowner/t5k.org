<?php

#t# Ready??

# This routine actually create the page in one of the support functions listed below,
# so $t_xxxx variables ust be set/reset there or spotted globally

// If 'xx_person_id' (a database 'person' id number) is defined, this
// program uses HTTP basic authentication to allow access by
// comparing against database 'username' and 'pass'.  So no need to track
// that here.  So then just use 'xx_action' to decide what to do.

// Using 'xx_' to match the edit.inc standards which are this way to
// avoid colision with database column names.

// Okay, load up the include files
require_once "../../library/db_helpers/passwords.inc";

include('bin/basic.inc');
$db = basic_db_connect(); # Connects or dies
include_once('bin/log.inc');
include_once('bin/get_the_person.inc');

// Page title and form button names (as they will be stored in xx_action)

$t_title = 'Edit Prover-Account';
$t_allow_cache = 'yes!';

$btn_send_passwd = 'E-mail New Password';
$btn_start_edit  = 'Edit Prover-Account';
$btn_edit_passwd = 'Change Password';

// Are the key form variables set?
$xx_person_id = (isset($_REQUEST['xx_person_id']) ?
    htmlentities($_REQUEST['xx_person_id']) : '');
$xx_action    = (isset($_REQUEST['xx_action'])    ?
    htmlentities($_REQUEST['xx_action'])    : '');
$pwlink       = (isset($_REQUEST['pwlink'])       ? $_REQUEST['pwlink']   : '');
$username     = (isset($_REQUEST['username'])     ? $_REQUEST['username'] : '');

// Set $t_submenu for breadcrumb (because if the name is not set submited, will not reach the
// page where it wuld be set below)

if ($xx_action == $btn_edit_passwd) {
    $t_submenu = 'Change password';
} elseif ($xx_action == 'put') {     # returning from an edit form, put in db
    $t_submenu = 'Update database';
} elseif ($xx_action == $btn_send_passwd) {
    $t_submenu = 'e-mail password';
} else {    # send an edit form
    $t_submenu = 'Edit prover-account';
}

// get_the_person fills the username with the id or the username,
// in this case make sure the $xx_person_id is also set.

// WHY IS THIS HERE?  Doesn't get_the_person cover it?

$error = '';
$xx_person_id = (preg_match('/^\d+$/', $xx_person_id) ? $xx_person_id : '');
if (empty($xx_person_id)) {
    if (preg_match('/^\d+$/', $username)) {
        $xx_person_id = $username;
    } elseif (!empty($username)) {
        $temp = basic_get_person_from_username($username, 'id');
  # print "<li> here: ";print_r($temp);
        if (empty($temp)) {  // Not a legal username;
            $username  = preg_replace('/[\s<>"\'!@#%^&+\-[\]]/', '', $username);   // untaint as in get_the_person
            $error = '<div class="alert alert-danger m-5 bold" role="alert">' . sprintf("Sorry, but there is no database entry with the username %s.", "'$username'") . '</div>';
        } else {
            $xx_person_id = $temp['id'];
        }
    }
}


// Okay, what shall we do?

// print "<ul><li>xx_person_id : '$xx_person_id' <li>xx_action : '$xx_action' <li>username : '$username'</ul>";

if (empty($xx_person_id) or !preg_match('/^\d+$/', $xx_person_id)) {
    if (empty($error)) {  // Error not detected above?  Well, did they submit the form with no id?
        $error = ($xx_action ? 'Enter the database ID or username of your prover-account.' : '');
    }
    echo welcome_screen($xx_person_id, $error);
    exit;
} elseif ((!empty($xx_action) and $xx_action == $btn_send_passwd) or !empty($pwlink)) {
    send_password_screen($xx_person_id);
    exit;
} elseif (empty($xx_action)) {
    echo welcome_screen($xx_person_id, '');
    exit;
}

# If the id is set, we can check for authorization.
# This must come first as it alters the http headers!

if (!empty($xx_person_id)) {
    include_once('bin/http_auth.inc');
    $is_authorized = my_auth($xx_person_id, 'log errors');
}

// For the rest of the options, we need to specify rules for the database edit

$parameters = array('entry_id' => $xx_person_id,
    'editors_id' => $xx_person_id,
    'database_name' => basic_DatabaseName(),
    'table_name' => 'person',
    'page_title' => 'Edit Prover-Account',
    'put_row_silent' => 1);

// and they must be authorized

if (!$is_authorized) {
    echo failed_password_screen($xx_person_id, '');
    exit;

# If get past here it means the password checked out!
} elseif ($xx_action == $btn_edit_passwd) {
    change_password_screen($xx_person_id, '');
} elseif ($xx_action == 'put') {     # returning from an edit form, put in db
    include_once('admin/edit.inc');
    $GLOBALS['basic_Use_Database_for_Authorization'] = 1;
    update_database_screen(generic_put_row_by_id($parameters));
} elseif ($xx_action == $btn_start_edit) {   # send an edit form
    include_once('admin/edit.inc');
    echo ReturnButtons('index', 'page');
    generic_show_edit_table($parameters, '');
} else {
    print "Unknown error: <ul><li>xx_person_id : $xx_person_id <li>xx_action : $xx_action";
}

exit;



##### Support routines

function ReturnButtons()
{
  # Make index and page not empty to show the corresponding links
    $out = "<P><form method=post action=\"page.php?id=$GLOBALS[xx_person_id]\">
	<input type=\"submit\" class=\"btn btn-primary p-2\" value=\"Return to Your Prover-Account Page\"></form>";
    return $out;
}

#####  Now the various "screens"

function welcome_screen($id, $error)
{
    global $out, $mdbdrkcolor, $mdbmedcolor, $mdbltcolor, $t_submenu;
    $t_title = $GLOBALS['t_title'] . (empty($error) ? '' : ' ERROR:');

  // get_the_person_query builds it response in $out;
    $out = '';
    $GLOBALS['get_the_person_query'] = 'Enter the database ID or username of your prover-account.';
    get_the_person();

    if (!empty($error)) {
        $error = '<div class="alert alert-danger m-5 bold" role="alert">' . "$error</div>\n";
    }
    $t_text = "$error\n<form method=post action=\"$_SERVER[PHP_SELF]\">
        $out\nChoose what you would like to do:
	<blockquote>
	  <input type=\"submit\" name=xx_action class=\"btn btn-primary p-2\" value=\"$GLOBALS[btn_start_edit]\">
	  <input type=\"submit\" name=xx_action class=\"btn btn-primary p-2\" value=\"$GLOBALS[btn_edit_passwd]\">
	</blockquote>
	<p>If you have forgotten your password, the button below will e-mail you a link to request another password.
	This link will be mailed to your stored e-mail address.</p>
	<blockquote>
	  <input type=\"submit\" name=\"xx_action\" class=\"btn btn-primary p-2\" value=\"$GLOBALS[btn_send_passwd]\">
	</blockquote>\n</form>";

    include('template.php');
}

function failed_password_screen($id)
{
    global $mdbdrkcolor, $mdbmedcolor, $mdbltcolor, $t_submenu;
    $t_submenu = 'Password error';
    welcome_screen($id, 'You must provide the correct username and password.');
}

function change_password_screen($id, $error = '')
{
    global $db, $mdbdrkcolor, $mdbmedcolor, $mdbltcolor, $t_submenu;
    $t_submenu = 'Change Password';

  # get info from form
    $pass1 = (empty($_REQUEST['pass1'])   ? '' : $_REQUEST['pass1']);
    $pass2 = (empty($_REQUEST['pass2'])   ? '' : $_REQUEST['pass2']);
    $old   = (empty($_REQUEST['oldpass']) ? '' : $_REQUEST['oldpass']);

  # Check the old (against verified one)
    if (!empty($old) & $old != $_SERVER['PHP_AUTH_PW'] & empty($error)) {
        $_REQUEST['oldpass'] = '';
        return change_password_screen($id, 'You must provide the correct username and password.');
    }

  # Compare the two new passwords
    if ($pass1 != $pass2 & empty($error)) {
        $_REQUEST['pass1'] = '';
        $_REQUEST['pass2'] = '';
        return change_password_screen($id, 'Your two new password entries do not match.');
    }

  # get name data from database person table
  # This block of code should be it's own routine in bin/basic.inc
  # also used in bin/get_the_person.inc

    $temp = basic_get_person_from_id($id, 'email,username,name,hide_email', $db);
    if (empty($temp)) {
        return change_password_screen(
            $id,
            sprintf("Sorry, but there is no database entry with id %d.", "'$id'")
        );
    }

    if ($temp['hide_email'] == 'yes') {
        $tt = 'e-mail address unpublished';
    } elseif ($temp['hide_email'] == 'munge') {
        $tt = 'e-mail address published munged';
    } else {
        $tt = 'e-mail address published';
    }

    $name_string = "<blockquote><a href=\"../bios/page.php?id=$id\">$temp[name]</a>
        ($temp[email], $tt)</blockquote>";
    $t_title = "Change Password: '$temp[username]'" . (empty($error) ? '' : ' ERROR:');

  # Update or ask again...
    if (!empty($error) or empty($pass1) or empty($old)) { # If error or no data--send form
        $t_text = (empty($error) ? '' : '<div class="alert alert-danger m-5 bold" role="alert">' . "$error</div>\n" ) . "
	<form method=post action=\"$_SERVER[PHP_SELF]\">
	<p>First, (re)enter the current password for your account</p>
	<blockquote>
	  <input type=password size=20 name=oldpass value=\"" . htmlspecialchars($old) . "\"> (current password)<br>
	</blockquote>
	<p>Now please enter your new password twice:</p>
	<blockquote>
	  <input type=password size=20 name=pass1 value=\"" . htmlspecialchars($pass1) . "\"> (requested password)<br>
	  <input type=password size=20 name=pass2 value=\"" . htmlspecialchars($pass2) . "\"> (repeat to verify)<br>
	  <input type=hidden name=xx_person_id value=\"$id\">
	</blockquote>
	<input type=submit class=\"btn btn-primary p-2\" value=\"$GLOBALS[btn_edit_passwd]\" name=xx_action>
        </form>\n";
        include('template.php');
    } elseif (!empty($pass1) & empty($error)) {  # No error + new password
      # Can we log out?  We have a new password...
        unset($GLOBALS['_SERVER']['PHP_AUTH_USER']);
        unset($GLOBALS['_SERVER']['PHP_AUTH_PW']);

        $success = (\DB_Helpers\update_password($db, $id, $pass1) ? 1 : -1);
        update_database_screen($success);
    }
}


function update_database_screen($success)
{
    global $mdbdrkcolor, $mdbmedcolor, $mdbltcolor, $t_submenu;
    $t_submenu = 'Updated';

    $t_title = $GLOBALS['t_title'] .
    ($success == 1 ? 'Database Updated' : '<font color=red>Update Failure!</font>');

    if ($success == 1) {
        $t_text = 'The database has been successfully updated.';
    } elseif ($success == -1) {
        $t_text = '<div class="alert alert-danger m-5 bold" role="alert">Database unchanged.' . "</div>\n" .
          'Apparently you did not change anything.';
    } else {
        exit;  # database update returns 0 when reprinting the edit screen!
    }

    $t_text .= "
	<blockquote>
	  <form method=post action=\"edit.php?xx_person_id=$GLOBALS[xx_person_id]\">
 	    <input type=submit class=\"btn btn-primary p-2\" name=xx_action value=\"$GLOBALS[btn_start_edit]\"><br>
	    <input type=submit class=\"btn btn-primary p-2\" name=xx_action value=\"$GLOBALS[btn_edit_passwd]\"><br>
	  </form>
  	  <form method=post action=\"page.php?id=$GLOBALS[xx_person_id]\">
	    <input type=submit class=\"btn btn-primary p-2\" value=\"Return to Your Prover-Account Page\">
	  </form>
	</blockquote>\n";
    include('template.php');
}

function send_password_screen($id)
{
  # New password takes three steps -- all in this routine
    global $db, $btn_send_passwd, $pwlink, $mdbdrkcolor, $mdbmedcolor, $mdbltcolor, $t_submenu;
    $t_submenu = 'Mail Password';

    $t_title = $GLOBALS['t_title'] . (empty($error) ? ': Change Password' : 'ERROR:');
    $temp = basic_get_person_from_id($id, 'username,pass,temppass,email,name', $db);
# print "<li> $id";
    if (empty($temp)) {
        return welcome_screen(
            $id,
            sprintf("Sorry, but there is no database entry with id %d.", "'$id'")
        );
    }

  # Third step (others follow): Click on link from step two, so send the password

    if (!empty($pwlink)) {
      # First, verify the link and block false attempts:
        include_once('bin/mail_password.inc');
        if (mailpassword_verify_link($pwlink, $temp['email'], $temp['name'], $id) <> 1) {
            log_action($db, $id, 'password', "/primes/edit.php", "invalid password request link '$pwlink'");
            return(welcome_screen($id, 'Expired link.'));
        }

      # Good link, lets get to work:
        $URL = "https://t5k.org/bios/edit.php?xx_person_id=$id";
        $collection = "Prime Pages' Prover-Account Database";

        $password = mailpassword($temp['email'], '', $collection, $URL, $temp['name'], $id);
        if (!empty($password)) {  # Yes--successfully mailed
            if (\DB_Helpers\update_password($db, $id, $password, true)) {
              # announce success
                $t_text = sprintf(
                    "A new password for the account %s has been sent by e-mail.",
                    "<blockquote>$temp[name]</blockquote>"
                );
      #   if ($GLOBALS['http_auth_Administrative_Spoof'] == $_SERVER['PHP_AUTH_USER']) xxx
                log_action($db, $id, 'modified', "person.temppass", "temporary password set and mailed");
            } else {
                log_action($db, $id, 'error', "person.temppass", "temporary password mailed, but failed db insert");
                return(welcome_screen($id, "You have been sent e-mail, but this program
		has failed to store the new password----system error!"));
            }
        } else { # darn, failed to mail...
            log_action($db, $id, 'error', "person.temppass", "failed to mail temporary password");
            return(welcome_screen($id, "Failed to mail new password--system error!"));
        }
    } elseif (empty($_REQUEST['ConfirmEmailPassword'])) {    # First step:  Send the screen to request a link
        $t_text = 'This routine will e-mail you a link to allow you to change your password. The link will be mailed to the address stored in the database for this account.'
        . "<blockquote>$temp[username] (id $id) : <b>$temp[name]</b></blockquote>\n" .
        'Once you receive the e-mail, you can use the link to request that a temporary password be mailed to you. Is this really what you want?'
        . "\n<blockquote>
          <form method=post action=\"$_SERVER[PHP_SELF]\">
            <input type=submit class=\"btn btn-primary p-2 my-4\" name=ConfirmEmailPassword
		value=\"Yes, E-mail a Link for a New Password\">
            <input type=hidden name=xx_person_id value=\"$id\">
            <input type=hidden name=xx_action value=\"$btn_send_passwd\">
          </form>
        </blockquote>\n";
    } else {          # Second step: Confirmed request, so send the password request link
        $URL = "https://t5k.org/bios/edit.php?xx_person_id=$id&";
        $collection = "Prime Pages' Prover-Account Database";
        include_once('bin/mail_password.inc');

        if (mailpassword_link($temp['email'], $collection, $URL, $temp['name'], $id)) {
            $t_text = '<p>' . "A link to request a temporary password has be mailed to the address stored in the database for this account."
            . "</p>\n<blockquote class=\"font-weight-bold\">$temp[username] (id $id) : $temp[name]</blockquote>\n<p>" .
            "Once you receive the e-mail, you must use the link within 24 hours to request a new password." . '</p>';
            log_action($db, $id, 'mailed', "person.pass_link", "mailed request password link");
        } else { # darn, failed to mail...
            log_action($db, $id, 'error', "person.pass_link", "failed to mail password link");
            return(welcome_screen($id, "Failed to mail new password link--system error!  Contact the editors."));
        }
    }
    include('template.php');
}
