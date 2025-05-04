<?php

// Set to return 1 to disable access to database to all  but 'Caldwell'

require_once __DIR__ . "/../../../library/db_helpers/passwords.inc";

function http_auth_lock_to_root()
{
    return 0;
}

// Example of use:
//
//  include_once('http_auth.inc');
//  if (my_auth($id))
//    echo '<P>You are authorized!</P>';
//  else exit;

// This routine seeks to authorize use by using the database table person.
// In particular, the person table should have an 'id' column and a 'username'
// column (unique integer and string respectively).  The database must
// have two password columns: pass and temppass.
// Both will be checked.  The idea is that when someone loses their password,
// just the temppass is reset and a new one mailed (just in case it was a
// spoofed request).  When they update a password, the temp is NULL'ed.
//
// The function my_auth($id) expects $id to be a users' numerical id
// (person.id) to verify or the string 'allow any' which will ask a username
// (person.username).  In the first case I put in a back door (see code)
//
// It will use the database to validate the name and password
// returning true if there is a match.  If not, it will request the
// user supply the name and password (using HTTP headers).  On failure
// it will print an error and return false.

// Here is how to clear authorization (these globals are also useful when
// using 'allow any' to see who was just authorized!):
//
//  unset($GLOBALS['_SERVER']['PHP_AUTH_USER']);
//  unset($GLOBALS['_SERVER']['PHP_AUTH_PW']);

# if not connected to a database, then connect. The calling program probably
# included basic.inc and defined the connection, but if not...
if (!isset($basic_database_connection)) {
    if (!function_exists('basic_db_connect')) {
        require_once "basic.inc";
    }
    $db = basic_db_connect();
}

function my_is_ip_blocked()
{
    global $http_auth_id;
  // Is this IP blocked?  (e.g., too many failed password attempts in the
  // last 72 hours from this IP address)

    $db = basic_db_connect();
    $query = "SELECT IFNULL(sum(penalty),0) AS num FROM failed
		WHERE created > SUBTIME(NOW(),'72:00:00') AND ip=?";
    try {
        $sth = $db->prepare($query);
        $sth->bindValue(1, $_SERVER['REMOTE_ADDR']);
        $sth->execute();
        $row = $sth->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        lib_mysql_die('http_auth: error 1 = ', $ex . '<br />' . $query);
    }
    $failed_penalty_sum = $row['num'];

  # print "<li> $_SERVER[REMOTE_ADDR] has penalty sum $failed_penalty_sum.";
    if ($failed_penalty_sum < 15 or preg_match('/^10\./', $_SERVER['REMOTE_ADDR'])) {
        return false;
    } else {
        http_auth_log_ip_error(0, 0, '', '', 'blocked attempt');
        return true;
    }
}

// Log failed attempts in the database table 'failed' to later block bad
// robots... The idea is to allow a penalty to be assesed for actions,
// usually 1.  Also logging successful submission such as primes to see
// where the are being submitted from (to block if they turn out to be
// composite).  Use penalty 0 in this case.

function http_auth_log_ip_error(
    $id,
    $penalty = '',
    $username = '',
    $passwd = '',
    $comment = '',
    $ip = ''
) {
    global $http_auth_id;  // Will be set to the id of the person if authorized.
  # Use PHP_AUTH_USER if set (e.g., tried authorization), otherise perhapes
  # get_the_person has set the username
    if (empty($username)) {
        $username    = !empty($_SERVER['PHP_AUTH_USER'])
        ? $_SERVER['PHP_AUTH_USER'] : (!empty($GLOBALS['username']) ?
        $GLOBALS['username'] : 'not known');
    }
  # If the password is not set, not sure how we can guess what it is.
    if (empty($passwd)) {
        $passwd = (!empty($_SERVER['PHP_AUTH_PW']))
        ? $_SERVER['PHP_AUTH_PW']   : '';
    }
  # The id and penalty must be small positive integers
    if (!isset($id)   or !preg_match('/^\d+$/', $id)) {
        $id = 0;
    }
    if (!isset($penalty) or !preg_match('/^\-?\d+$/', $penalty)) {
        $penalty = 1;
    }
  # is an ip set? (Might be if repropcessing a number found composite...)
    if (empty($ip)   or !preg_match('/^[\d\.]+$/', $ip)) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

  # let's try omiting PG (user id 950) from penalties
    if ($id == 950) {
        $penalty = 0;
        $comment .= ' (PG)';
    }

    try {
        $db = basic_db_connect();
        $sth = $db->prepare('INSERT failed (ip, page, username, comment,
		 person_id, penalty) VALUES (?,?,?,?,?,?)');
        $sth->bindValue(1, $ip);
        $sth->bindValue(2, $_SERVER['PHP_SELF']);
        $sth->bindValue(3, $username);
        $sth->bindValue(4, $comment);
        $sth->bindValue(5, $id);
        $sth->bindValue(6, $penalty);
        $sth->execute();
    } catch (PDOException $ex) {
        lib_mysql_die('http_auth failed to write to table "failed" => ', $ex . '<br>' . $query);
    }
  # print "<li>ip ".$ip;
  # print "<li>penalty ".$penalty;
  # print "<li>insert id ".$db->lastInsertId();
}

function my_auth($id, $log_errors = false)
{
    global $http_auth_id;     // Will be set to the id of the person if authorized.
    $auth = false;        // Assume user is not authenticated
    $http_auth_id = false;

  // Is this IP blocked?  (10 failed password attempts in the last hour from this IP address)

    if (my_is_ip_blocked()) {
        return false;
    }

  // Open database connection

    $db = basic_db_connect();

  // First, are we passed an id to allow connection as, or 'allow any' so we get the
  // 'username' from the client

    $where = '';
    if (isset($id) and preg_match('/^\d+$/', $id)) {
        $where = 'WHERE id = ?';
        $data = $id;
    } elseif (!empty($_SERVER['PHP_AUTH_USER'])) {
        $where = 'WHERE username = ?';
        $data = $_SERVER['PHP_AUTH_USER'];
    } elseif ($id != 'allow any') {
        http_auth_log_ip_error('', 1, '', '', 'my_auth in http_auth.inc was not passed an id');
        lib_die("my_auth in http_auth.inc was not passed an id to verify (passed '$id')");
    }
  // else ($id == 'allow any')

  // Get the password, username and id fields

    if (!empty($where)) {
        try {
            $sth = $db->prepare('SELECT id, username FROM person ' . $where);
            $sth->bindValue(1, $data);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            lib_mysql_die('http_auth: error three = ', $ex . '<br>' . $query);
        }

        $id = $row['id'];
        $username = $row['username'];
    } else {
        $username = '';
    }

  // Now let's verify (if they have sent something to verify!)

    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
      // Lock database access to root only ?
        if (http_auth_lock_to_root()) {
            lib_die("Error: Sorry, for some reason database access has been temporarily turned off by the editor.");
        }

      // This routine does only basic authorization.
        if (
            isset($_SERVER['HTTP_AUTHORIZATION']) &&
            ! ereg("^Basic ", $_SERVER['HTTP_AUTHORIZATION'])
        ) {
            echo "Error: this routine can only handle basic HTTP authorization.<br>\n";
            return false;
        }

      // Now try to match to $PHP_AUTH_USER and $PHP_AUTH_PW.

        if ($username == "$_SERVER[PHP_AUTH_USER]") {
            $auth = \DB_Helpers\verify_password($db, $id, $_SERVER['PHP_AUTH_PW']);
        }
    }

    if (! $auth) {
      # If not authorized -- abort
        header("WWW-Authenticate: Basic realm=\"Your user name is '$username' (case-sensitive).\"");
        header('HTTP/1.0 401 Unauthorized');
        echo "Authorization is required to access this page.<br>\n";
      # echo "expected $password or $password2,<br> got $enc_pw and $enc_pw2
      #    from $_SERVER[PHP_AUTH_PW] for $_SERVER[PHP_AUTH_USER]";

      # If they sent something false, $log_errors is set (so we are hooked to the database
      #  and log.php is available) we should log it
        if ($log_errors and isset($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
            include_once(basic_to_base() . 'bin/log.inc');
            log_action(
                $GLOBALS['db'],
                $id,
                'password',
                "person.id=$id",
                "Password error $_SERVER[PHP_SELF] \"$_SERVER[PHP_AUTH_USER]\""
            );
        }

      # worth noting a failed attempt, but let's not store off the password or anything
        if (isset($_SERVER['PHP_AUTH_USER']) or isset($_SERVER['PHP_AUTH_PW'])) {
            http_auth_log_ip_error($id, (empty($_SERVER['PHP_AUTH_PW']) ? 0 : 2));
        }
    } else {
        $http_auth_id = $id; # set global authorized id.
    }
    return $auth;
}

// Must be nothing after the ending php tag below (or it will print it before
// the headers and an error will ensue).  Nothing!  No blank lines -- NADA!
