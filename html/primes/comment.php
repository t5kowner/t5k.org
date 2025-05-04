<?php

// Provides a page to add a comment to a prime.
//
//  *  If $xx_comment_id is set, just displays that one comment.
//  *  If $xx_person_id, $xx_html, $xx_prime_id are all already set and acceptable,
//     then can just add the comment to the database (do so immediately if xx_done
//     is non-empty, else give a confirmation screen).
//  *  Otherwise ($xx_person_id, $xx_html, $xx_prime_id not all set) displays a
//     form to finish submission.

$t_title = "Add Prime Comments ";
$t_allow_cache = 'yes!';
$t_submenu = 'Comment';

// Okay, load up the include files
include_once('bin/basic.inc');  # basic database access, system defaults, ...
include_once('bin/log.inc');    # allows logging of changes

$db = basic_db_connect(); # Connects or dies

include_once('bin/http_auth.inc');
if (my_is_ip_blocked()) {
    lib_die("You may not submit entries while your IP is blocked.", 'warning');
}

# If $xx_person_id is set (it is imbedded in the form when bin/get_the_person.inc succeeds),
# we should make sure they are authorized.  This must come first as it alters the
# http headers!  (If not empty $xx_person_id should be a positive integer).

$xx_person_id = (isset($_REQUEST['xx_person_id']) ? $_REQUEST['xx_person_id'] : '');
# if (is_numeric($xx_person_id)) {  Fails because $xx_person_id is returned as type string!
$authorized = 0;
$authorized_error = '';
if (preg_match('/^\d+$/', $xx_person_id)) {
    include_once('bin/http_auth.inc');
    if (!my_auth($xx_person_id, 'log errors')) {
        $xx_person_id = '';
    } else {
      # Good, they are in the database.  Do they have primes?
        $temp = basic_get_person_from_id($xx_person_id, 'PrimesTotal, CanComment', $db);
        if ($temp['PrimesTotal'] == 0) {
            $authorized_error = "Error: You have a prover account, but can not comment until a prime is verified.\n";
        } elseif ($temp['CanComment'] == 'no') {
            $authorized_error = "Error: Your prover account has not been authorized to add comments ($temp[1]).  Contact an
	editor if you have questions.\n";
        } else {
            $authorized = 1;
        }
    }
} else {
    $xx_person_id = '';
}

# Now we know $xx_person_id is either empty, or verified.

# If $xx_comment_id is set, display that one comment.  (At least do so if it
# is a positive integer, otherwise reset it to '' and continue.)

$xx_comment_id = (empty($_REQUEST['xx_comment_id']) ? '' : $_REQUEST['xx_comment_id']);
if (preg_match('/^\d+$/', $xx_comment_id)) {
    include_once('bin/modify.inc');  # Used to display comments safely
    $t_text = display_one_comment($xx_comment_id, $xx_person_id);
    $t_title = "Display One Prime Comment";
    include('template.php');
    exit;
} else {
    $xx_comment_id = '';
}

# If here--assuming want to add a new comment

$out  = ''; # Build the page output here
$done = 1;  # Will be one if all info is submitted correctly, else 0

# Just a generic you requested line (so it looks the same in all form fields)
$you_requested = "<font color=green><B>(You requested to re-edit this field!)</B></font><br>\n ";

function edit_button($field_name)
{
    return "\n &nbsp; &nbsp; &nbsp; <input type=submit value=change name=\"edit_$field_name\" class=\"btn btn-primary p-2\">\n";
}

include_once("bin/get_the_person.inc");      # Grab function "get_the_person" xx_person_id
$get_the_person_query = "<p>To add a comment to a prime you must be established in <a href=\"../bios/\">our
        prover database</a>.&nbsp; Enter your username or database id:</p>";
$done *= get_the_person();  # Returns 1 if we have that info, 0.  Either case adds to $out.

include_once('bin/get_the_html.inc');         # Also grab "get_the_html" (comment) xx_html
$done *= get_the_html();    # Returns 1 if we have that info, 0.  Either case adds to $out.

include_once('bin/get_the_prime_id.inc');
$done *= get_the_prime_id();    # Returns 1 if we have that info, 0.  Either case adds to $out.

# If $done = 1, all is well, so either confirm or submit; otherwise re-edit.

if ($done == 1 and empty($_REQUEST['xx_done'])) { # Complete, but not confirmed
    $out = "<p>Your submission appears to be complete.&nbsp; Press the submission button below to
        complete submission or press a 'change' button to alter your submission.</p>
        <form method=post action=\"$_SERVER[PHP_SELF]\">
        <blockquote><input type=submit value=\"Press here to complete submission (step 2 of 2)\" name=xx_done class=\"btn btn-primary p-2 mt-4\">
        <p>$out</p>
        <input type=submit value=\"Press here to complete submission (step 2 of 2)\" name=xx_done class=\"btn btn-primary p-2\"></blockquote></form>
        <p class=\"mb-5\">Press this button to complete the submission and get a confirmation message.</p>";
} elseif (!$done) {    # Still editing (errors or empty fields)
    $out = "<p>Those who have found primes, and so are in our prover database, are allowed to comment
	on any prime in the database.&nbsp;  Please do not abuse this privilege.</p>
        <form method=post action=\"$_SERVER[PHP_SELF]\">
	<blockquote><input type=submit value=\"Submit this comment (step 1 of 2)\" class=\"btn btn-primary p-2 mt-4\">
        <p>$out</p>
	<input type=submit value=\"Submit this comment (step 1 of 2)\" class=\"btn btn-primary p-2 mb-4\"></blockquote></form>";
} elseif ($done and !empty($_REQUEST['xx_done']) and $authorized) { # Ah, done and confirmed.  Add it!
  # Form the $query, do the work.
    $xx_prime_id = $_REQUEST['xx_prime_id'];
    $xx_html = $_REQUEST['xx_html'];

    $query = "INSERT comment (prime_id,person_id,text,created) VALUES (:xx_prime_id,:xx_person_id,:xx_html,NOW())";
    try {
        $sth = $db->prepare($query);
        $sth->bindValue(':xx_html', $xx_html, PDO::PARAM_STR);
        $sth->bindValue(':xx_prime_id', $xx_prime_id, PDO::PARAM_INT);
        $sth->bindValue(':xx_person_id', $xx_person_id, PDO::PARAM_INT);
        $success = $sth->execute();
        $insert_id = $db->lastInsertId();
    } catch (PDOException $ex) {
        lib_mysql_die("code to create a comment table entry failed: " .
        $ex->getMessage(), $query);
    }

    if ($success) {
        $out = "Comment successsfully added. \n";
        log_action(
            $db,
            $xx_person_id,
            'created',
            "comment.id=$insert_id",
            "submitted new comment prime_id=$xx_prime_id"
        );
        mail(
            "admin@t5k.org",
            "New unofficial comment needs review",
            "Comment $insert_id was added to prime $xx_prime_id. It should be reviewed to determine if it should be an official comment or is inappropriate.",
            "From: T5K Automailer <admin@t5k.org>",
            "-fadmin@t5k.org"
        );
        $edit = (empty($_REQUEST['edit']) ? '' : "&amp;edit=$_REQUEST[edit]");
        if (!headers_sent()) {  # If the headers are not yet sent, bounce back to the prime page
            header('Location: http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\')
                     . '/page.php?id=' . "$xx_prime_id$edit#comments");
        }
    } else {
        $out = "<font color=red>Database not updated!</font>  This should be because you did not change anything.
	Otherwise, contact an editor.";
    }
    $out .= "\n<blockquote>
	<form method=post action=\"page.php?id=$xx_prime_id\">
	  <input type=submit value=\"Return to the Prime's Page\">
	</form>
	<form method=post action=\"../bios/page.php?id=$GLOBALS[xx_person_id]\">
	  <input type=submit value=\"View your Prover-entry Page\">
	</form>\n</blockquote>\n";
} elseif (!empty($authorized_error)) {
    $out .= "<font color=red>$authorized_error</font>";
} else {
    $out .= "UNKNOWN ERROR";
}

$t_text = $out;
include('template.php');

exit;


function display_one_comment($comment_id, $prime_id = '', $person_id = '')
{
    $query = "SELECT person.name, comment.person_id, comment.text, comment.id,
	comment.prime_id, prime.description, comment.visible
        FROM comment, person, prime WHERE comment.id=$comment_id AND person.id=comment.person_id
	AND prime.id=comment.prime_id";
    $sth = lib_mysql_query($query, $GLOBALS['db'], 'show_one_comment db error');

    $edit = (empty($_REQUEST['edit']) ? '' : "&amp;edit=$_REQUEST[edit]");
  # Display the matching rows
    $out = '';
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { # Currently should be one
        if ($row['visible'] == 'no') {
            $out .= "<font color=red>This comment is no longer visible</font>\n";
        }
        if ($row['visible'] == 'yes' or !empty($edit)) {
            $out .= "RE: <b>$row[description]</b>\n (prime id:
	<a href=\"page.php?id=$row[prime_id]$edit\">$row[prime_id]</a>)<P>\n";
            $out .= "<fieldset><legend title='id=$row[id]'>
        <a href=\"../bios/page.php?id=$row[person_id]$edit\">$row[name]</a> writes:\n";
            if (!empty($edit)) {
                $out .= ' (editor: <a href="admin/index.php?xx_TableName=comment&amp;xx_edit=' .
                $row['id'] . '">edit</a> <a href="admin/index.php?xx_TableName=comment&amp;xx_delete=' .
                $row['id'] . "\">true delete</a>)\n";
            }
            $out .= ' (owner may: <a href="generic_edit.php?xx_TableName=comment&amp;xx_person_id=' .
            "$row[person_id]&amp;xx_edit=$row[id]&amp;xx_comment_id=$row[id]\">edit</a> " .
            '<a href="generic_edit.php?xx_TableName=comment&amp;xx_person_id=' .
            "$row[person_id]&amp;xx_delete=$row[id]&amp;xx_comment_id=$row[id]\">delete</a>)";
            $out .= "&nbsp; </legend>\n<blockquote>\n" . modify_adjust_html($row['text']) . "</blockquote>\n</fieldset>\n";
        }
    }

    if (empty($out)) {
        $out = "<div class='error'>No such comment.</div>";
    }
    return $out;
}
