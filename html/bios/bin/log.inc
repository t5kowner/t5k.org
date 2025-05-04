<?php

#t# leave alone for now, few end user comments

// This routine should match the one in connect_db which does the same thing in Perl.

// This routine is for logging changes to the database.  It has one routine:
//
//  log_action($db, $who, $what, $where, $notes)
//
// where these variables are as follows
//
//  $who        (person.id) database id for the individual (log.person_id) or NULL
//          Use 'system' as $who for system programs (translates to 254 currently)
//  $where      where the change was made, e.g., "prime.id=62534", "curios.id=234" (log.where)
//          I hope to stick to this table.row=value format
//  $what       what type of change or error: that is, one of the strings
//
//              modified|created|deleted|mailed|other|warning|autoblocked|error
//
//          (curios database adds |visibility|rated).  Note errors need editor attention, warnings do not
//          (duplicate submissions, password errors...) (log.what)
//  $notes      a short text comment ("submitted a new prime" ...) (log.notes)
//
//  the routine will try to automatically fill in these fields:
//
//  when_       current timestamp (log.when_)
//  from_       IP address via browser type  (or if it is the system, a program name proceeded by '#')
//
//  MAKE SURE THIS ROUTINE CAN NOT FAIL (OR FAILS SILENTLY!) other than it returns 'FALSE' if fails
//  and the new log entry id if succeeds.

function log_action($db, $who, $what, $where, $notes)
{
  # set variables for each database
    if (basic_databasename() == 'primes') {
        $system_account_id = 254;
        $system_account_name = 'system';
        $allowed_what_values = '/^(modified|created|deleted|mailed|other|warning|password|error)$/';
    } elseif (basic_databasename() == 'curios') {
        $system_account_id = 144;
        $system_account_name = 'SYSTEM';
        $allowed_what_values = '/^(modified|created|deleted|mailed|visibility|rated|other|warning|password|error)$/';
    } else {
        lib_die('need to know the database in log.inc');
    }

  # start setting fields to store in the database
    if ($who == $system_account_name) {
        $who = $system_account_id;  # Current system account number
    }
    if (!preg_match('/^\d+$/', $who)) {
      # Disallowing NULL until later (need left join in log.php      && $who != 'NULL') {
        $notes .= "[Log error: who='$who'] $notes";
        $who = $system_account_id;
    }
  # 'modified','created','deleted','mailed','other','warning','error'
    if (!preg_match($allowed_what_values, $what)) {
        $notes .= "[Log error: what='$what' not in '$allowed_what_values'] $notes";
        $what = 'error';
    }

  # For diagnostic use later we add:

    $from = (php_sapi_name() == 'cli') ? 'command line' :
      $_SERVER['REMOTE_ADDR'] . ' via ' . $_SERVER['HTTP_USER_AGENT'];

#  $query = "INSERT log (person_id, where_, what, notes, from_)
#   VALUES ($who, '$where', '$what', '$notes', '$from')";
    $query = "INSERT log (person_id, where_, what, notes, from_) VALUES (:who, :where, :what, :notes, :from)";
  # Do not want to fail!  Don't die even if we do!

    try {
        $sth = $db->prepare($query);
        $sth->bindValue(':who', $who, PDO::PARAM_INT);
        $sth->bindValue(':what', $what, PDO::PARAM_STR);
        $sth->bindValue(':where', $where, PDO::PARAM_STR);
        $sth->bindValue(':notes', $notes, PDO::PARAM_STR);
        $sth->bindValue(':from', $from, PDO::PARAM_STR);
        $sth->execute();
        return($db->lastInsertId());
    } catch (Exception $e) {
        echo '<li>log.inc caught exception: ',$e->getMessage(),"</li>\n";
        return(false);
    }
}
