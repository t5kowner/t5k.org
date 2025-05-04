<?php

include_once("bin/basic.inc");
include_once("bin/log.inc");
require_once "../../library/constants/environment.inc";

# This page is used to communicate between primality testing clients and
# the server.

# I don't expect null to be used outside cutover - can probably remove after everything's deployed
$password = array_key_exists("password", $_REQUEST) ? $_REQUEST["password"] : null;

if ((empty($_REQUEST['person']) and empty($_REQUEST['result'])) || !in_array($password, T5K_VERIFY_PASSWORDS, true)) {
  #404 would look more natural, but we don't have a 404 page for /primes currently
    http_response_code(403);
    echo "<h3>Forbidden</h3>";
    exit;
}
$db = basic_db_connect(); # Connects or dies
# Intaint a variable or two (used in both requests and returns)
# Less important now that we are switching to a PDO intefrace.

# Add 'modified=modified' to SQL?
$unmodified = (empty($_REQUEST['unmodified']) ? 0 : 1);

# If it is a request, return the prime.

if (!empty($_REQUEST['type'])) {
  # Must have a person id
    if (is_numeric($_REQUEST['person'])) {
        $person = $_REQUEST['person'];
    } else {
        print "error Need to specify person id from database";
        exit;
    }

  # Might have direction and machine; all must be untainted!

    $machine = (empty($_REQUEST['machine']) ? 'unspecified' : $_REQUEST['machine']);

  # $client is, but recorded for debugging
    $client = (empty($_REQUEST['client']) ? 'unknown' : $_REQUEST['client']);

  # These are untainted by limiting the values
  #$direction = ((!empty($_REQUEST['direction']) and $_REQUEST['direction'] == 'DESC') ? 'DESC' : 'ASC');
    $parse = (empty($_REQUEST['parse']) ? 0 : 1); # Need to expand the digits?

  # Grab a suitable 'prime'.  Should be marked to verify and not 'InProcess';
    $where = "WHERE prime = 'Untested'";

  # Need to double back quotes before MySQL gets it--it removes one when parsing,
  # so the second is left for the pattern match
    $type = $_REQUEST['type'];
    if ($type == 'n-1') {
        $where .= ' AND (description LIKE "%+1" OR description LIKE "Phi(%")';
    } elseif ($type == 'n+1') {
        $where .= ' AND description LIKE "%-1"';
    } elseif ($type == 'GF') {
        $where .= ' AND description REGEXP "^[0123456789]+\\\\^[0123456789]+\\\\+1$"';
    } elseif ($type == 'any composite') {
        $where = "WHERE prime = 'Composite' AND status LIKE '%Verify%'";
    } elseif ($type == 'prp') {
        $where = "WHERE prime = 'PRP' and digits < 2000";
    } elseif ($type == 'any') {
        $where = $where;
  #  } else if ($type == 'special') {  # unused, unadvertised in verify.txt
  #    $where = "WHERE prime = 'PRP' AND digits < 10000 AND description not like '%)^2%'";
    } else {
        die("unsupported type '$type'");
    }

  # if prime_id was set, override all else and get that prime!

    if (!empty($_REQUEST['prime_id']) and preg_match('/^\d+$/', $_REQUEST['prime_id'])) {
        $prime_id = $_REQUEST['prime_id'];
        $query = "SELECT id, description, digits, blob_id FROM prime WHERE id=:id";
    } else {
        $prime_id = 0;  # just for convience below
        $query = "SELECT id, description, digits, blob_id FROM prime
        $where ORDER BY id ASC, prime.rank DESC LIMIT 1";
    }

  # Let's seek a prime:

    try {
        $sth = $db->prepare($query);
      # getting an error about the number of vaiables below when :id is not used after being bound
        if ($prime_id > 0) {
            $sth->bindValue(':id', $prime_id);
        }
        $sth->execute();
    } catch (PDOException $ex) {
        lib_mysql_die('Invalid query (verify.php 101), contact the admin: ' . $ex->getMessage(), $query);
    }

    if ($row = $sth->fetch(PDO::FETCH_ASSOC)) {   # GOT A PRIME
        $description = $row['description'];

      # Do we need to expand this prime?
        if (!empty($row['blob_id'])) {  # In the blob table?
          # Now get the row to display (selecting full_digit for next block)
            $query2 = "SELECT full_digit FROM prime_blob WHERE id='$row[blob_id]'";
            $sth2 = lib_mysql_query($query2, $db, 'Failed to get row from prime_blob');
            $row2 = $sth2->fetch(PDO::FETCH_ASSOC);
            $description = $row2['full_digit'];
            $description = preg_replace('/ /', '', $description); # Remove spaces
        } elseif (!empty($parse)) {    # Oh well, lets parse it
            $temp = preg_replace('/"/', '\"', $description);
            log_action(
                $db,
                'system',
                'warning',
                "prime.id=" . $_REQUEST['prime_id'],
                "verify.php parsing $description"
            );
            $command = "/var/www/html/primes/support/parse \"$temp\"";
            $description = shell_exec($command);
            if (strlen($description) < 1000) {  # if short, something is wrong
                log_action(
                    $db,
                    'system',
                    'error',
                    "prime.id=" . $_REQUEST['prime_id'],
                    "parsing failed: $command returned '$description'"
                );
            }
            $description = preg_replace('/ /', '', $description); # Remove spaces
        }

      # Note that it is "checked out" in the 'verify' table. The 'null' client
      # just allows for requesting a prime; but records nothing, changes nothing.
      # I use this to test my client program. Setting the form variable 'silent'
      # does the same thing--for the same reason.

        if (($client <> 'null') && empty($_REQUEST['silent'])) {
            try {
                $query3 = "INSERT INTO verify (person_id,prime_id,machine,what,notes,created) VALUES
  	  ($person, $row[id], :machine, 'requested', :client, NOW())";
                $sth3 = $db->prepare($query3);
                $sth3->bindValue(':client', $client, PDO::PARAM_STR);
                $sth3->bindValue(':machine', "Using: $machine", PDO::PARAM_STR);
                $sth3->execute();
                $id = $db->lastInsertId();
            } catch (PDOException $ex) {
                lib_mysql_die('Failed to create verify entry (verify.php 139): ' .
                $ex->getMessage(), $query3);
            }
          # and the 'prime' table
            if (empty($unmodified)) {
                $query3 = "UPDATE prime SET prime='InProcess' WHERE id=$row[id]";
            } else {
                $query3 = "UPDATE prime SET prime='InProcess', modified=modified " .
                "WHERE id=$row[id]";
            }
            lib_mysql_query(
                $query3,
                $db,
                "failed to update prime.prime to inprocess, verify.php (150)"
            );
        } else {
            $id = 0;  # this will mean no reporting (i.e., "silent")
        }
      # Now return the response
        print "id $id<br>\ndescription $description<br>\ndigits $row[digits]<br>\nprime_id $row[id]";
    } else {                  # NO PRIMES LEFT!
        print "description none";
    }
} elseif (!empty($_REQUEST['result'])) {
  # Must have a database id
    if (is_numeric($_REQUEST['id'])) {
        $id = $_REQUEST['id'];
    } else {
        print "error: need to specify database id to return data";
        log_action($db, 'system', 'error', "unknown", 'verify.php: ' .
        "Need to specify database id to return data: " . print_r($_REQUEST, true));
        exit;
    }
    $result = $_REQUEST['result'];
    if (
        !( $result == 'prime' or $result == 'composite' or $result == 'prp' or $result == 'status'
        or $result == 'verified composite' or $result == 'error' or $result == 'failed')
    ) {
        log_action(
            $db,
            'system',
            'error',
            "prime.id=$id",
            "verify.pl client returned unknown response: '$result'."
        );
        die("Improper value of result.");
    }

    $notes = (empty($_REQUEST['notes']) ? '' : $_REQUEST['notes']);

  # For the first case below, add 'Rerank' first, then remove 'Verify', if there,
  # so it is now proceeded by a comma because of the orders set in the table
    if ($result == 'prime') {
        $set =
        "status = IF(status=0, 'Rerank', CONCAT_WS(',',status,'Rerank')),
	status = TRIM(BOTH ',' FROM REPLACE(status,',Verify','')),
	prime = 'Proven'";
    } elseif ($result == 'prp') {
        $set =
        "status = IF(status=0, 'Rerank', CONCAT_WS(',',status,'Rerank')),
	prime = IF(prime='Proven' or prime='External',prime,'PRP')";
    } elseif ($result == 'verified composite') {
        $set = "status = 'Remove',
        prime = 'Composite'";
    } elseif ($result == 'composite') {
        $set = "status = 'Rerank,Verify',
        prime = 'Composite'";
    } elseif ($result == 'status') {
        $set = "";
    } else {
      # For now, 'error' and 'failed' leaves the prime status as requested... so
      # let's warn the admins via log.  Could also have it mail them!
        log_action(
            $db,
            'system',
            'error',
            "verify.id=$id",
            "verify.pl client returned '$result' (prime still 'in process'): " .
            print_r($_REQUEST, true)
        );  # TRUE means return rather than output
        exit;
    }
  # Is the do not modify timestamps flag set.
    $set .= (empty($unmodified) ? '' : ', modified=modified');  # used in prime table
    $temp = (empty($unmodified) ? '' : ', modified=modified');  # used in verify table
    $temp .= ($result == "status") ? '' : ", what='$result'";

    $update_error = 0;
    $query = "UPDATE verify SET notes='$notes'$temp WHERE id=$id";
    if ($db->query($query)) {
        if ($result == 'status') {
            print 'result recorded';
            exit;
        }
        try {
          # Now update prime table--first grab the prime's id
            $query = "SELECT prime_id FROM verify WHERE id=$id";
            $sth = lib_mysql_query($query, $db, 'Invalid query (verify.php 222)');
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $prime_id = $row['prime_id'];
        } catch (PDOException $ex) {
            $update_error = '(error 213): ' . $ex->getMessage();
        }
        if (empty($update_error)) {
          # actual update
            lib_mysql_query(
                "UPDATE prime SET $set WHERE id=$prime_id",
                $db,
                "error in verify.php near 219"
            );
        }
        print "result recorded";
        log_action(
            $db,
            'system',
            'modified',
            "prime.id=$prime_id",
            "verify.pl client returned '$result'"
        );

      # After all is said an done, if someone submits a composite, should we block thier IP?
      # (and give back the penalty assigned when a prime was submitted)
        $penalty = 0;
        if ($result == 'composite') {
            $penalty = 4;
            $comment = "composite=$prime_id";
        }
        if ($result == 'prp' or $result == 'prime') {
            $penalty = -1;
            $comment = "proven=$prime_id";
        }
        if ($penalty <> 0) {
            $a = lib_get_column("comment='prime=$prime_id'", 'failed', 'ip,username,person_id', $db);
            if (!empty($a['ip']) and preg_match('/^[\d\.]+$/', $a['ip'])) {   # do we know where it was submitted?
                include_once("bin/http_auth.inc");
                http_auth_log_ip_error($a['person_id'], $penalty, $a['username'], '(omitted)', $comment, $a['ip']);
            }
        }
        exit;
    } else {
        $update_error .= "(error 245)";
    }

    log_action(
        $db,
        'system',
        'warning',
        "prime.id=" . $_REQUEST['prime_id'],
        "recording error: $update_error"
    );
} else {
    print "error: to request a number at least person, machine and type must be set.
	To return a result at least id and result must be set.";
}
