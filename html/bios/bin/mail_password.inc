<?php

// Sloppy code to block an abusive user.  Will fail to mail passwords to adress that match these:
// Will fix this later.  Silently blocks any attempt to send a password if email or prover-account
// name matches this string (i.i, claims it has been sent, but does not, logs this so I can notice
// if the wrong folks are blocked.)

// Routines:
//
//  genpassword($length)
//      Generates a "pronounceable" random password of (at least) the
//      given length
//
//  mailpassword($email,$password='',$collection='',$URL='',$Account_Name='',$Account_id='')
//      Emails a password to the given address.  If $password is set, then
//      that is the password it sends, otherwise uses genpassword(10).
//      Returns the password if succeeds.  0 if fails.
//      The collection is the name of the collection for which they
//      are requesting a password and the $URL is where they can change
//      this password...   If $Account_Name and $Account_id
//              are set, then includes them in the e-mail.
//
//  mailpassword_create_link($email,$Account_Name,$Account_id)
//  mailpassword_verify_link($email,$Account_Name,$Account_id)
//      Creates and verifies a link.  The first returns the link, the second 1 or 0.
//
//  mailpassword_link($email,$collection='',$URL='',$Account_Name='',$Account_id='')
//      E-mails a link to request a new password.  $Account_Name and $Account_id
//      must be set, they are used to create the link and included them in the e-mail.
//      The URL link sent will be $URL."pwlink=$link" with $link created by the
//      routine above.

//  Lets generate a "pronounceable" random password of (at least) the given length

function genpassword($length)
{
    srand((double)microtime() * 1000000);
    $vowels = array("a", "e", "i", "o", "u");
    $cons = array("b", "c", "d", "g", "h", "j", "k", "l", "m", "n", "p",
        "r", "s", "t", "v", "w", "z", "tr", "cr", "br", "fr", "th", "dr",
            "ch", "ph", "wr", "st", "sp", "sw", "pr", "sl", "cl", "sch", "str");
    $num_vowels = count($vowels);
    $num_cons = count($cons);

    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $cons[rand(0, $num_cons - 1)] . $vowels[rand(0, $num_vowels - 1)];
        if (rand(0, 1) == 1) {
            $password .= rand(0, 100);
        }
    }
    return substr($password, 0, $length);
}

//  mailpassword_create_link($email,$Account_Name,$Account_id)
//  mailpassword_verify_link($email,$Account_Name,$Account_id)
//      Creates and verifies a link.  The first returns the link, the second 1 or 0.

function mailpassword_create_link($email, $Account_Name, $Account_id)
{
  // Create a MD5 string whic will identify the user, append a time string to limit to 24 hours.
  // With the time stamp encoded into, and also appended to the string, should be essentially unbreakable
    $time_stamp = time();
    return md5("$email $Account_Name $Account_id $time_stamp") . $time_stamp;
}

function mailpassword_verify_link($link, $email, $Account_Name, $Account_id)
{
    if (preg_match("/(.*)(\d{10})$/", $link, $matches)) {
      #  print_r("<pre>$link\n$matches[2]\n".(time())."\n".($matches[2]+86400)."</pre>");
        if ($matches[2] + 86400 < time()) {
            return 0; # link too old
        }
        if (md5("$email $Account_Name $Account_id $matches[2]") == $matches[1]) {
            return 1;
        }
    }
    return 0;
}

// Alright, e-mail the password...

function mailpassword($email, $password = '', $collection = '', $URL = '', $Account_Name = '', $Account_id = '')
{
  # If not given a password, make one up!
    if (empty($password)) {
        $password = genpassword(10);
    }

    if (!preg_match('/^\S+?@\S+?\.\S+$/', $email)) {
        echo '<font color=red size=+1>' . sprintf("Invalid e-mail address: %s", "'$email'") . '</font>';
        return(0);
    }

    $text = sprintf("Below is the temporary password for the %s database entry that you requested.", $collection);
    $text .= "\n\n\t$password\n\n";
    $text .= "Both this new temporary password and your previous password (if any) will continue to work until you change your password.";
    $text .= ' You may change your password and edit your database entry at the following URL.';
    $text .= "\n\n\t$URL\n\n";
    $text .= sprintf("Thank-you, the %s editors.", $collection);
    $text .= "\n\n\n\n-----------------------------\nThis mail was requested\n  from: $_SERVER[REMOTE_ADDR]\n";
    $text .= "  via:  $_SERVER[HTTP_USER_AGENT]\n" .
    (!empty($Account_Name) ? "  for:  $Account_Name (id: $Account_id)\n" : '');

    $mail_headers = "From: the Prime Pages' Automailer <admin@t5k.org>";
  # mail('admin@t5k.org',"Password for $email ".$collection, $text,$mail_headers);
    if (mail($email, 'Password for ' . $collection, $text, $mail_headers)) {
        return($password);
    } else {
        return(0);
    }
}

//  mailpassword_link($email,$collection='',$URL='',$Account_Name='',$Account_id='')
//  E-mails a link to request a new password.  $Account_Name and $Account_id
//  must be set, they are used to create the link and included them in the e-mail.
//  The URL link sent will be $URL."pwlink=$link" with $link created by the
//  routine above (so start $URL with http:// for best success in e-mail clients).

function mailpassword_link($email, $collection = '', $URL = '', $Account_Name = '', $Account_id = '')
{
    if (empty($Account_Name)) {
        echo("Account_Name must be set in mailpassword_link()");
        return(0);
    }
    if (empty($Account_id)) {
        echo("Account_id must be set in mailpassword_link()");
        return(0);
    }

    if (!preg_match('/^\S+?@\S+?\.\S+$/', $email)) {
        echo "<font color=red size=+1>Invalid e-mail address: '$email'</font>";
        return(0);
    }

    include_once('bin/http_auth.inc');
    if (my_is_ip_blocked()) {
        lib_die("Will not mail link while your IP is blocked.", 'warning');
    }

    $link = $URL . 'pwlink=' . mailpassword_create_link($email, $Account_Name, $Account_id);

    $text = sprintf(
        "Here is the link to request a temporary password for the Prime Pages' database for the account '%s' (database id: %s).",
        $Account_Name,
        $Account_id
    );
    $text .= "\n\n\t$link\n\n";
    $text .= "If you did not request this link, then just delete this e-mail.  No change to the database has been made.  Otherwise, be sure to use the link within 24 hours.  After that you will need to request a new link.";
    $text .= "\n\n";
    $text .= sprintf("Thank-you, the %s editors.", $collection);
    $text .= "\n\n\n\n-----------------------------\nThis mail was requested\n  from: $_SERVER[REMOTE_ADDR]\n";
    $text .= "  via:  $_SERVER[HTTP_USER_AGENT]\n  for:  $Account_Name (id: $Account_id)\n";
  # mail('admin@t5k.org',"Password link for $email".$collection, $text,$mail_headers);
    $mail_headers = "From: the Prime Pages' Automailer <admin@t5k.org>";
    if (mail($email, 'Password link for ' . $collection, $text, $mail_headers)) {
        return(1);
    } else {
        return(0);
    }
}
