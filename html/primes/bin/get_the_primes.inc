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


##### GET A LIST OF PRIMES
#
# The first form a client is sent has a text box named 'primes' in which to add
# the primes and comments; one per line.  Until the parsing of all the primes is
# successful (no errors, though there may be warnings), this is just passed back
# to the client (unaltered) in the same edit box.  When the parsing succeeds;
# then the (perhaps canonically altered) list is displayed in the text and added
# to the global $carry_forward as a hidden form field (a base64_encoded
# serialized array which contains the altered forms). The unaltered $primes
# field is also encoded and hidden (in the form and in $carry_forward; as the
# field 'primes_enc') just in case the user wants to re-edit.
#
# The function will process the form textbox named 'primes' and build
# several arrays internally:
#
#   Error     A list of errors found (wordy messages), primes rejected
#   BadPrimes     A shorter list of rejects (very short error messages)
#   GoodPrimes    Array of the properly parsed/canonicalized primes (+ comments)
#   GoodPrimes2   An array of the log10's of the primes.  Later may hold more.
#   Warn      Warning messages (a few weeks to survive ...), primes accepted
#
# It reads the external global real $The5000thDigits and $The5000thLog10 (digits
# in 5000th and its log base 10); and sets the global  hash array $Approved
# (keys = descriptions, values = comments).

function get_the_primes($error_message = '')
{
    global $out, $help, $you_requested;
    global $The5000thDigits,  $The5000thLog10;  # set in submit.php
    global $basic_database_connection;  # database handle defined in basic.inc

    $AllowHistoric = false;  # Set to true to allow primes < 1000 digits

    $canonicalizer_url = '/primes/support/modules/canonical.cgi';

  # Grab the info from the form--it is either in primes or encoded in primes_enc.

    $primes = stripslashes(isset($_REQUEST['primes']) ? $_REQUEST['primes'] : '');
    if (empty($primes)) { # Perhaps the prime list was previously parsed and hidden
        $primes = base64_decode(isset($_REQUEST['primes_enc']) ?
        $_REQUEST['primes_enc'] : '');
    }
    $edit = (isset($_REQUEST['edit_primes']) ? $_REQUEST['edit_primes'] : '');

  # Sometimes the empty prime field is returned as a space from IE--hence 'trim'
    $primes  = trim($primes);
    $primes = preg_replace("/[<>'\"]/", "", $primes);  # acoid cross-site/sql injctn

  # Store the list as submitted should the client want to back up and edit...
    $primes_was = $primes;

    if ($edit) {
        $error_message = $you_requested;
    } elseif (!empty($error_message)) {
        $error_message = '<div class="alert alert-danger m-5" role="alert">ERROR: ' . "$error_message</div>\n";
    }
    $out .= $error_message;

    if (empty($error_message) and !empty($primes)) {  # Got some primes?
      # Allow backquotes as continuation characters (need to quote \\\n)
        $primes = preg_replace('/\\\\\\s+/s', '', $primes);

        $lines = preg_split('/[\n\r]+/', $primes);
        foreach ($lines as $Line) {
            if (preg_match('/^\s*$/', $Line)) {
                continue;    # Skip blank lines
            }

          # remove unnecessary space in line

          # leading/trailing
            $Line = preg_replace('/(^\s+|\s+$)/', '', $Line);
          # Space around binary operators:  - / ^ + *
            $Line = preg_replace('/\s*([\-\/\^+*])\s*/', '$1', $Line);
          # space before postfix operators: ) # !
            $Line = preg_replace('/\s+([\)#!])/', '$1', $Line);
          # Space after prefix operators:   ( -
            $Line = preg_replace('/([\(\-]+)\s+/', '$1', $Line);
          # $Line =~ s/([RpUV])\s*\(\s*/$1(/go;  # Functions : R p U V
          # Change . to *  (??cause trouble???)
            $Line = preg_replace('/\./', '*', $Line);
          # folks have used . for * in submission.  Tricked my routines.

          # Split line into description and comment

            preg_match('/(\S+)\s*(.*)/', $Line, $Matches);
            $old_desc = $Matches[1];
            $comment = $Matches[2];

          # Use the external routine canonical on the description

            $desc = shell_exec(
                '/var/www/html/primes/support/modules/canonical -e ' .
                escapeshellarg($old_desc) . ' 2>&1'
            );
            if ($old_desc != $desc) {
                $Warn[] = "The description \"$old_desc\" was
	modified to \"$desc\" in accordance with our
	<a href=\"$canonicalizer_url?string=" . rawurlencode($old_desc) .
                "\" class=none>canonicalization procedures</a>.";
            }

          # Now parse to see the size (also tests syntax!)  First we get the natural
          # log.  Two cases: either a string like 'prime_blob_94' where 94 is the
          # blob database id or a parsable string like '2*3^1000+3^7-1'.

          # case one: prime_blob_\d+

            if (preg_match('/^prime_blob_(\d+)$/', $desc, $temp)) {
                $blob_id = $temp[1];
                $temp = lib_get_column(
                    "id=$blob_id",
                    'prime_blob',
                    "description,log10,prime_id",
                    $GLOBALS['db']
                );
                if (empty($temp)) {
                          $Error[] = "Failed to find \"$desc\" in the prime_blob database.";
                          $log10 = 'NaN';
                } else {
                    if ($temp['prime_id'] > 0) {
                            $Warn[] = "Isn't this prime \"$desc\" already in the database?";
                    }
                    $log10 = $temp['log10'] * log(10);  # parser returns ln, not log_10
                    $desc = "$temp[description] [blob_id $blob_id]";
            # Note that the last part of this description is for submit2.php
                }

              # case two: a parsable string
            } else {
                $log10 = shell_exec(
                    '/var/www/html/primes/support/math/parser -e ' .
                    escapeshellarg($desc) . ' b 2>&1'
                );
            }

            if (!is_numeric($log10)) {
                $Error[] = "The number \"$desc\" was not recognized by our
          parser so it is being ignored.  If it is a valid prime please
          <a href=\"mailto:admin\@t5k.org\">let the admin know</a>! The
	  parser returned: <blockquote>$log10</blockquote>\n";
                $BadPrimes[] = "Not parsable: $Line";
                continue;
            } else {
                $log10 = $log10 / log(10);
                $digits = floor(1 + $log10);
            }

          # Is this prime too big? (Will check too small later)  Note that I allow
          # myself alot more room when submitting.  So when a user is bounced,
          # prehaps I can on the test pages.

            $multiplier = file_exists("/var/www/html/TESTSITE") ? 200 : 20;
            if (!empty($desc) and $digits > $multiplier * $The5000thDigits) {
                $Error[] = "The prime \"$desc\" is unusually large.  Primes this
	  size require must be submitted manually by e-mailing
	  <a href=\"mailto:admin\@t5k.org\">the admin</a> both the prime
	  and an explanation how it was proven prime (software, run time, ...)";
                $BadPrimes[] = "Too Large: $desc ($digits digits)";
              # Log this error
                include_once(basic_to_base() . 'bin/log.inc');
                include_once(basic_to_base() . 'bin/http_auth.inc');
              # If they are authorized, then http_auth_id is set, others perhaps
          # xx_person_id is...
                $http_auth_id = !empty($GLOBALS['http_auth_id']) ?
                $GLOBALS['http_auth_id'] : (!empty($GLOBALS['xx_person_id']) ?
                $GLOBALS['xx_person_id'] : 'system');
                log_action(
                    $basic_database_connection,
                    $http_auth_id,
                    'warning',
                    'get_the_primes.inc',
                    "Too Large: $desc ($digits digits >
		$multiplier * The5000thDigits)"
                );
              # also exact a penalty for this
                http_auth_log_ip_error($http_auth_id, 3, '', '', 'too large');
                continue;
            }

          # Is the prime's description too long? (to fit into the database field)

            if (!empty($desc) and strlen($desc) > 255) {
                $Error[] = "The prime \"$desc\" has an unusually long description.&nbsp;
		In fact it is too long to fit in the database field.&nbsp;
		Primes of this type must be submitted in a different way,
		please e-mail
	  <a href=\"mailto:admin\@t5k.org\">the admin</a>.";
                $BadPrimes[] = "Desc too Long: $desc ($digits digits)";
                continue;
            }

            if (empty($desc)) {
                continue;   # Still have a prime?
            }

          # Now finish

            $always_upper = '(ECPP|APR-CL|NSW|GF\()';
            $always_first_upper = '(Fermat|Woodall|Cullen|Sophie|Germain|Lucas|' .
            'Lehmer|Cunningham|Aurifeuillian|Fibonacci|Mersenne|Euler|Carmichael|' .
            'Gaussian|Wagstaff|Mills|Phi\(|F\()';

            if ($comment) {    # If there is a comment, should it be adjusted?
              # Remove Proth's comments like : "Is prime! (P = 1, Q =-1) [10005
          # digits]" or "Is prime! (a = 3)" (often just part of this comment is
          # included)

                $comment_was = $comment;
                $patterns = array('/Is prime\!/', '/\s*\(P = \d+, Q =\s*\-?\d+\)/',
                '/\s*\(a = \d+\)/', '/\s*\[\d+ digits\]/');
                $comment = preg_replace($patterns, '', $comment);
                if ($comment != $comment_was) {
                    $Warn[] = "Proth.exe comments like
		'Is prime!', '(P = 1, Q =-1)', '[10005 digits]' and '(a = 3)'
		should not be included, remove them please.";
                }
      #### Problem... credits like [BR] should not be messed with!!!!
                $comment = ucfirst(strtolower($comment)); # First character uppercased
                $comment = preg_replace_callback(
                    "/$always_upper/i",
                    function ($matches) {
                        #print_r($matches);
                        return strtoupper($matches[0]);
                    },
                    $comment
                );
                $comment = preg_replace_callback(
                    "/$always_first_upper/i",
                    function ($matches) {
                          #print_r($matches);
                          return ucfirst($matches[0]);
                    },
                    $comment
                );
            }

            if (
                $log10 >= $The5000thLog10 - 0.00000001 or (!empty($comment) and
                $digits >= 1000) or $AllowHistoric
            ) {
                $GoodPrimes[] = "$desc $comment";  # Add the prime
                $GoodPrimes2[] = $log10;  # The size of the prime
            # Guess how long it will remain on the list
            # select rank from prime where log10 < 150000 order by log10 desc limit 1;
                $appx_rank = lib_get_column("log10 < $log10 AND prime.rank IS NOT NULL ORDER
		BY log10 DESC", 'prime', 'prime.rank', $basic_database_connection);

                $lifetime = floor(0.5 + 52 * (5 / 4) * log(5000 / $appx_rank));
        # log_action($basic_database_connection, 254, 'other', 'get_the_primes',
        #   "$desc ($appx_rank) $lifetime weeks");
                if ($lifetime < 52) {     # and $lifetime >= 0) {
                    if (empty($comment)) {  #  Post warning if it will not be on list long
                        if ($log10 <= $The5000thLog10 + 0.0000001) {
                                $Warn[] = "The prime \"$desc\" is very very close to the 5000th
		prime.  I will add it temporarily to the list and use the more
		powerful sorting algorithms to see.";
                        } else {
                            $Warn[] = "At the rate at which primes have been added to this
		list in the past, the $digits digit prime \"$desc\" may only
		be on the list for about $lifetime weeks.";
                        }
                    } elseif ($digits < $The5000thDigits) {
            # Only makes it because of the comment
                        $Warn[] = "The prime \"$desc\" does not make the top 5000, so
		must be one of the <a href=\"/lists/top20/\"
		class=none>twenty largest</a> of
		one of the <a href=\"/lists/top20/archivable.html\"
		class=none>archivable forms</a> to remain in the database.&nbsp;
		See our <a href=\"/lists/top20/sizes.html\" class=none>list
		of sizes</a> of the
		twentieth for each form.&nbsp; This prime will be removed later
		if the comment is not valid.";
                    }
                }
            } else {  # Nope, not big enough!  reject it.
                if ($digits < 1000) {
                    $Error[] = "Prime \"$Line\" is too small ($digits digits).
		Primes must have at least 1000 digits to make this list.";
                } else {
                    if ($digits == $The5000thDigits) {
                        $Error[] = "Prime \"$Line\" is too small ($digits digits, log $log10
		verses $The5000thLog10).  It has the minimum number of digits,
		yet is still smaller than the 5000th prime.";
                    } else {
                        $Error[] = "Prime \"$Line\" is too small ($digits digits).
		Need $The5000thDigits digits or more for a general prime.
		Add a valid comment if you forgot it.";
                    }
                }
                $BadPrimes[] = "Too small: \"$desc\" ($digits digits)";
                continue;
            }
        }  # Done looping on lines in the 'primes' textarea

      # warn of any bad lines
        if (isset($Warn)) {
            $out .= "<p><font color=green>The following warnings have been noted:</p><ul>\n";
            foreach ($Warn as $x) {
                $out .= "<LI>$x\n";
            }
            $out .= "</ul></font>\n<p>If necessary, correct and resubmit the above.</p>\n";
        }

        if (!empty($Error)) {       # Errors--so lets have them re-edit
            $error_str = "<p>The following errors have been noted:</p><ul>\n";
            foreach ($Error as $x) {
                $error_str .= "<LI>$x\n";
            }
            $error_str .= "</ul>\nFix these errors and resubmit.\n";
            if (!empty($error_str)) {
                return get_the_primes($error_str);
            }
        } else {            # No errors! Print the primes and return 1.
            $out .= "The following primes are ready for submission: "
            . edit_button('primes') . "\n<blockquote>\n";
            foreach ($GoodPrimes as $desc) {
          # These might have <url,...,...> comments that must be altered to show
                $temp = htmlspecialchars(stripslashes($desc));
                $out .= "$temp<br>\n";
            }
            $out .= "</blockquote>\n";
            $out .= lib_hidden_field('primes_enc', base64_encode($primes_was));
            $_SESSION['Submit']['GoodPrimes'] = $GoodPrimes;
            $_SESSION['Submit']['GoodPrimes2'] = $GoodPrimes2;
            return(1);
        }
    } else {
        $out .= "Please list your primes in the box below using one line per prime.
        <A href=\"/top20/home.php\">Archivable
        primes</A> must be followed by a correctly formatted comment.<p>\n";
        if ($help) {
            $out .= "<p><font color=green>For example, <CODE>
        <BLOCKQUOTE>65*2^129925+1<BR>54896985*2^68150-1<BR>9*2^304607+1 Divides
        GF(304604,6)<BR>18458709*2^32612-1 Sophie Germain (2p+1)<BR>
	18458709*2^32611-1 Sophie Germain (p) </BLOCKQUOTE></CODE>Make sure you
	<B>only submit primes</B>. If, for example, PFGW states that it is a PRP
	(a probable-prime), then <B>do not submit it</B> until you complete a
	<B>primality proof</B>.</font></p>\n";
        }
        $out .= "<blockquote>\n<textarea name=primes" . basic_tab_index() .
        "cols=60 rows=10>$primes</textarea>\n</blockquote>\n<P>\n";
        return(0); # Not done
    }
}
