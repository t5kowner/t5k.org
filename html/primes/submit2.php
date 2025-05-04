<?php

session_start();
# Problems: check for already on list not correct with blobs, really need a
# seperate check if blob on list fuction

# Submission part two: In part one we got prover (verified via password), the
# code (checkes against prover's code list), and the primes (parsed and
# canonicalized).  Now in part two we will...

include_once("bin/basic.inc");
include_once("bin/on_list.inc");
include_once("bin/log.inc");

$db = basic_db_connect();   # Connects or dies


include_once('bin/http_auth.inc');
if (my_is_ip_blocked()) {
    lib_die("You may not submit entries while your IP is blocked.", 'warning');
}

# Use $xx_person_id to make sure they are authorized.  This must come first as
# it alters the http headers!

$xx_person_id = (isset($_REQUEST['xx_person_id']) ?
    $_REQUEST['xx_person_id'] : '');

if (preg_match('/^\d+$/', $xx_person_id)) {
    if (!my_auth($xx_person_id)) {
      # check authorization and assess a password error penalty
        http_auth_log_ip_error($xx_person_id, 2, '', '', 'password error2');
        lib_die(
            'You can not submit primes without the appropriate password.',
            'password'
        );
    }
} else {
    lib_die('What is going on?  Note $xx_person_id is not an integer.
	Do you want <a href="submit.php">submit.php</a>?');
}

# Get the other data from the form created in submit.php

$code_str = $_REQUEST['code_str'];
$GoodPrimes = $_SESSION['Submit']['GoodPrimes'];
$GoodPrimes2 = $_SESSION['Submit']['GoodPrimes2'];
unset($_SESSION['Submit']);

# Okay, we are authorized to proceed.  Let's get the person's email address...
# and start the page by reminding them who they are.

$temp = basic_get_person_from_id($xx_person_id, 'email,username,name', $db);
$email = $temp['email'];
$username = $temp['username'];
$name = $temp['name'];
$out = '';
$out .= "<P>Primes submitted by: [#$xx_person_id: $username] <b>$name</b>\n";
$out .= " (<a href=\"$email\">$email</a>)\n";
$out .= "using the code $code_str<br>\n";

# Do the hard work--process the list of primes

if (!is_array($GoodPrimes)) {
    log_action(
        $db,
        $xx_person_id,
        'error',
        "prime.id=$prime_id",
        "fatal error 76 in primes/submit2.php"
    );
    lib_die("Fatal error: No array of primes");
} else {
    $out .= "The primes submitted:\n<blockquote>\n";
    for ($i = 0; $i < count($GoodPrimes); $i++) {
        $line = $GoodPrimes[$i];
        $log10 = $GoodPrimes2[$i];
        $digits = floor(1 + $log10);
      # Primes come in two flavors: blobs ("a big prime" [blob_id 94] ECPP) or
      # parsable strings lets get the description in temp[1] and the comment
      # (if any) in temp[2].  (Note that the routine which creates the entry will
      # worry about the blob id (if any).)
        if (
            !preg_match('/^(.*? \[blob_id \d+\])\s*(.*)$/', $line, $temp)
            && !preg_match('/^\s*(\S+)\s*(.*)$/', $line, $temp)
        ) {
            lib_die("Fatal error: \"$line\" not recognized.");
        }
      # This next routine returns positive if prime added, negative if
      # already in database, 0 if fails
        $prime_id = basic_create_prime($temp[1], $temp[2], $log10, $code_str, $db);
        if ($prime_id > 0) {
            $out .= "Added <a href=page.php?id=$prime_id>$prime_id</a> :
	$temp[1] ($digits digits) $temp[2]<br>\n";
          # log_action($db, $who, $what, $where,  $notes)
            log_action(
                $db,
                $xx_person_id,
                'created',
                "prime.id=$prime_id",
                "submitted new prime: $line"
            );
          # also log this ip (note penalty is 1)
          # http_auth_log_ip_error($id,$penalty='',$who='',$passwd='',$comment='')
            http_auth_log_ip_error($xx_person_id, 1, '', '(omitted)', "prime=$prime_id");
        } elseif ($prime_id < 0) {
            $prime_id = -$prime_id;
            $out .= "<div class=error>Already in database <a href=page.php?id=$prime_id
	class=none>$prime_id</a> : $temp[1]</div><br>";
            log_action(
                $db,
                $xx_person_id,
                'warning',
                "prime.id=$prime_id",
                "submitted duplicate prime: $line"
            );
          # http_auth_log_ip_error($id,$penalty='',$who='',$passwd='',$comment='')
            http_auth_log_ip_error(
                $xx_person_id,
                1,
                '',
                '(omitted)',
                "duplicate prime=$prime_id"
            );
        } else {
            log_action(
                $db,
                $xx_person_id,
                'error',
                "prime.id=$prime_id",
                "fatal error: id is zero in primes/submit2.php"
            );
            lib_die("The command<P>basic_create_prime()
	failed.  This should not happen, mail this info to admin@t5k.org");
        }
    }
    $out .= "</blockquote>\n";
}

# Okay list has been submitted, print the rest of the success page

$out .= "<div class=technote>Technical note:
        This page is the second of two used for prime submission.&nbsp;  This
	page reverifies the username and password (invisible to the user), then
	checks to see if the primes are already on the list (in any form) and
	adds them if not.&nbsp; When added, the prime is flagged to be reparsed
	(numbers close to a power of ten may still be off by one digit at this
	stage).&nbsp; They are also flagged to be ranked, 'comment linted',
	and verified.&nbsp;  Once that work is done (via crontabs), they will
	be processed to see if the 'onlist' flag should be set
	so they can appear on the list of largest known primes.</div>\n";

# Set up variables for the template
$t_text = $out;
$t_title = "Submit One or More Primes";
$t_submenu = "submit";

include("template.php");



############### SUPPORT ROUTINES ##############

// basic_create_prime($description,$comment,$log10,$code_str)
// either creates a new prime and returns the database id for it; or
// fails and returns NULL and set $GLOBALS['message'] to an error message.
// Note that $description can be a parsable string or "blob_id \d+" where
// the digits are the id of the prime_blob row

function basic_create_prime($description, $comment, $log10, $code_str, $db)
{
  # If this is a blob, split description and id; other wise set blob_id to NULL
    if (preg_match('/^(.*?) \[blob_id (\d+)\]$/', $description, $temp)) {
        $blob_id = $temp[2];
        $description = $temp[1];
    } else {
        $blob_id = 'NULL';
    }

  # First, is it on the list?  Return the negative of the database id if so

  ### THIS IS NOT A SUFFICIENT BLOB TEST

    if ($blob_id > 0) {
        $temp = $GLOBALS['db']->quote($description);
        $id = lib_get_column("description = $temp", 'prime', 'id', $GLOBALS['db']);
    } else {
        $id = on_list($description, $log10);
    }

    if ($id > 0) {
        return -$id;
    }

  # Protect MySQL from primes with odd characters

  # Other necessary fields
    $status = "DigitRank,Reparse,Verify,Lint";
    $digits = floor($log10 + 1);
    $score  = pow((float)$log10 * log(10), 3) * log($log10 * log(10));

  # Form the $query, do the work.
    $query = "INSERT prime (description,log10,digits,
        comment,credit,submitted,removed,created,modified,
        status,prime,blob_id,score)
        VALUES (:description,$log10,$digits,
        :comment,'$code_str',NOW(),NULL,NOW(),NOW(),
        '$status','Untested',$blob_id,$score)";

    try {
        $sth = $db->prepare($query);
        $sth->bindValue(':comment', $comment, PDO::PARAM_STR);
        $sth->bindValue(':description', $description, PDO::PARAM_STR);
        $sth->execute();
        $count = $sth->rowCount();
        $insertId = $db->lastInsertId();
    } catch (PDOException $ex) {
        lib_mysql_die("basic_create_prime failed: " . $ex->getMessage(), $query);
    }

    if ($count > 0) {
        return ($insertId);
    }
    $GLOBALS['message'] = 'something went wrong, not sure what!';
    return(null);
}
