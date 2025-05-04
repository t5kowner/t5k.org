<?php

if (!function_exists('__')) {
    function __($t)
    {
        return $t;
    }
}

# If we are at the test site, lets display all errors!
if (file_exists("/var/www/html/TESTSITE")) {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
    ini_set('html_errors', true);
}

require_once __DIR__ . "/../../../library/constants/environment.inc";

if (isset($_REQUEST)) {
    foreach ($_REQUEST as $a => $b) {   # First, block standard evil access
    ##### changed to allow arrays through (from forms, for complete.php)  Is this a problem?
        if (
            !empty($b) and
            ( is_scalar($b) and (preg_match('/\/\.\.\/\.\.\/\.\.\//', $b) or preg_match('/\/etc\/passwd/', $b)) )
        ) {
          #print("blocked $a=".print_r($b)." in $_SERVER[PHP_SELF]");
            error_log("blocked $a=$b in $_SERVER[PHP_SELF]", 0);
            exit;
        }
    }
}

# The idea is simple, clean every variable in $_REQUEST, includes all get, post
# and cookie info past from the browser. Before calling security_scrub_variables
# define these strings (use in a preg_match): (examples)
#
#  $my_variables_digits       = '(curio_id|edit|number_id|deleted_id|rank)';
#  $my_variables_alphanumeric = '(short|showall)';
#  $my_variables_self_tested  = '()';
#  $my_variables_general      = '(submitter)';  # Example: Proth.exe
#
# Any unlisted variable will be set to ''.  General just removes these
# charachers < > [  ] / , : ; " ' { },  Self_tested are ignored and passed as
#  is.
#
#  Note: useless unless it comes before grabbing the variables from $_REQUEST

function security_scrub_variables(
    $my_variables_digits = '()',
    $my_variables_alphanumeric = '()',
    $my_variables_self_tested = '()',
    $my_variables_general = '()'
) {
  # Scrub the user input
    foreach ($_REQUEST as $a => $b) {
      # Why does it fail to translate &amp; to & ?
        if (preg_match('/^amp;(.*$)/', $a, $temp)) {
            $c = preg_replace('/^amp;/', '', $a);
            error_log("shortened var $a=$b to $c=$b in $_SERVER[PHP_SELF]", 0);
            $_REQUEST[$a] = '';
            $_REQUEST[$c] = $b;
            $a = $c;
        }

      # First the white-listed variables of the main categories

      # digits only
        if (preg_match('/^' . $my_variables_digits . '$/', $a)) {
            if (!empty($b) and  preg_match('/[^\d]/', $b)) {
                $c = preg_replace('/[^\d]/', '', $b);
            ####### error_log("scrubbed $a=$b to digits $c in $_SERVER[PHP_SELF]", 0);
                $_REQUEST[$a] = $c;
            }
        } elseif (preg_match('/^' . $my_variables_alphanumeric . '$/', $a)) {
            if (!empty($b) and  preg_match('/[^\w]/', $b)) {
                $c = preg_replace('/[^\w]/', ' ', $b);  # \w is letters digits and underscore
            #### error_log("scrubbed $a=$b to alphanum $c in $_SERVER[PHP_SELF]", 0);
                $_REQUEST[$a] = $c;
            }
        } elseif (preg_match('/^' . $my_variables_general . '$/', $a)) {
            if (!empty($b) and  preg_match('/[<\/:;"\'[{]/', $b)) {
                $c = preg_replace('/[<>\[\]\/,:;"\'{}]/', ' ', $b);
            ##### error_log("scrubbed $a=$b to $c in $_SERVER[PHP_SELF]", 0);
                $_REQUEST[$a] = $c;
            }
        } elseif (!preg_match('/^' . $my_variables_self_tested . '$/', $a)) {
           # Be silent only about cookies
    ##
    ##  Too many log enries for now
    ##       if (empty($_COOKIE{$a})) error_log("removed variable $a=$b in $_SERVER[PHP_SELF]", 0);
            $_REQUEST[$a] = '';
        }
    }
}


// Okay, lib.inc contains most of my generic routines.  basic.inc includes
// those that are specific to the collection.

// FUNCTIONS            ACTIONS

// basic_DatabaseName       returns database name
// basic_CollectionName     returns the public name (Prime Pages, Prime Curios!...)
// basic_db_connect     Connects or dies [used by user pages]
// basic_db_admin_connect   Connects as admin (more powers?) or dies [used by admin pages]
// basic_to_base()      returns a string like '../../' which will give us the
//  relative path to the base e.g., "include(basic_to_base().'bin/parser/quad.inc')"

// basic_index          returns an index for the collection

// basic_sub_address($lastname) return 'mailto' type link if ok
// basic_address($role)     returns address string for editors

// basic_number_id_via_short    returns id or NULL
// basic_number_short_via_id    returns short or NULL
// basic_number_long_via_id returns long or NULL
// basic_check_short($short)    returns '' (if okay) or error message (if not)
//  sets global vv_log10, vv_sign (used by following and in meta_table for numbers)
// basic_create_number($short,$long)
//              creates a number (if necessary) and returns the id
// basic_create_curio($text,$number_id,$visible,$submitter,$address)
//              creates a new curio and returns its id

// basic_upload_max_size()  How many bytes do we allow in a file upload
// basic_upload_where()     Where do we place the uploaded files?

// basic_tab_index()            The string " tabindex=# " where # starts at 1 and is
//                              incremented each call.  For seting HTML client focus order.

$basic_tab_index_counter = 0;           # COunter used in basic_tab_index()

function basic_tab_index()
{
    global $basic_tab_index_counter;
    $basic_tab_index_counter++;
    return " tabindex=$basic_tab_index_counter ";
}

///// Initialization strings //////
$basic_start_time = (float) array_sum(explode(" ", microtime()));
                # current time for deciding how long it takes to create pages
$basic_color_scheme   = 'gold';     # Must preceed including library: lib.inc
$basic_base_directory = 'curios';   # Used by basic_to_base
$basic_database_name  = 'curios';
$basic_collection_name = 'Prime Curios!';
# How is edit authorized? Via database or .htaccess file?:
$basic_Use_Database_for_Authorization = false;
# used in admin/security.inc to overide username as the key identifier:
$basic_Use_Database_for_Authorization_username = 'lastname';
///// end init strings /////

// First, include the library functions.
if (file_exists(basic_to_base() . 'bin/lib.inc')) {
    include(basic_to_base() . 'bin/lib.inc');
} else {
    include('/var/www/html/curios/bin/lib.inc');
}

function basic_DatabaseName()
{
    global $basic_database_name;
    if (empty($basic_database_name)) {
        print "You must define \$basic_database_name in /bin/basic.inc\n";
        die;
    }
    return($basic_database_name);
}

function basic_CollectionName()
{
    global $basic_collection_name;
    if (empty($basic_collection_name)) {
        print "You must define \$basic_collection_name in /bin/basic.inc\n";
        die;
    }
    return($basic_collection_name);
}

function basic_upload_max_size()
{
# How many bytes do we allow in a file upload
    return 100000;
}


// $use = server if it is to be used on the server, otherwise 'client'
// (for example curios/page.php/10.html is in the curios directory on the server
// but curios/page.php on the client)

function basic_upload_where($use = 'server')
{
# Where do we put uploaded files
    return basic_to_base('', $use) . "includes/gifs/";
# This failed when used with /page.php/10.html; basic_to_base gave a usable for
# the system, but not the client.
# GLOBAL $basic_base_directory;
#  return "/$basic_base_directory/includes/gifs/";
}

// Sadly, where are we calling from affects how we include as I can not
// seem to reset $include_path outside of php.ini.
// Let's find where we are relative to /$basic_base_directory/ and work from there.
// Note this returns a relative path ending with a "/"
// $use = server if it is to be used on the server, otherwise 'client'
// (for example curios/page.php/10.html is in the curios directory on the server
// but curios/page.php on the client)

function basic_to_base($URL = '', $use = 'server')
{
    global $basic_base_directory;
    if (empty($URL) and !empty($_SERVER['PHP_SELF'])) {
        $URL = $_SERVER['PHP_SELF'];
    }
  # print "<li>$URL\n";
    $URL = preg_replace("/(x|c)page\//", '', $URL);
  # returns a string like '../../' which will give us the relative path to the base
  # start by removing everything up to, and including the first /$basic_base_directory/
    $to_base = preg_replace("/^.*?\/$basic_base_directory\//", '', $URL);
  # page.php/10.htm is not a directory, remove it
    if ($use == 'server') {
        $to_base = preg_replace("/page.php\/.*$/", 'page.php', $to_base);
    }
  # now how many steps do we need to back up?
    $to_base = preg_replace("/[^\/]*\//", '../', $to_base);
    $to_base = preg_replace("/[^\/]*$/", '', $to_base);
    if (empty($to_base)) {
        $to_base = "./";
    }
    return $to_base;
}

function basic_address($role)
{
    return isset(T5K_EMAIL_EXCEPTIONS['curios ' . $role]) ? T5K_EMAIL_EXCEPTIONS['curios ' . $role] : 'admin@t5k.org';
}


// Set up an exception handler for database connections.
function exception_handler($exception)
{
    echo "Uncaught exception: " , $exception->getMessage(), "\n";
}
set_exception_handler('exception_handler');
// throw new Exception('basic.inc: Dieing using PDO exception handler');



// basic_db_connect is our main function to connect to the database.  Use as:
//
//      $db = basic_db_connect(); # Connects or dies

function basic_db_connect($server = 'localhost', $name = "primes_", $passwd = T5K_DB_PRIMES_PASSWORD, $dbase = 'curios')
{
    global $basic_database_name, $basic_database_connection;
// Return connection, or die
    if (empty($dbase)) {
        $dbase = "$basic_database_name";
    }
    try {
        $db = new PDO("mysql:host=$server;dbname=$dbase;charset=utf8", $name, $passwd);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);   # throws a PDOException, instead of a php warning or silence...
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);       # removes a type of  code injection possibility
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  # set default fetch mode
    } catch (PDOException $ex) {
        die("basic_db_connect error (219): " . $ex->getMessage());
    }
    $basic_database_connection = $db;  // used in lib_warn()
    return ($db);
}

function basic_db_admin_connect($server = 'localhost', $name = 'primes_admin', $passwd = T5K_DB_PRIMES_ADMIN_PASSWORD, $dbase = 'curios')
{
    global $basic_database_name, $basic_database_connection;
    if (empty($dbase)) {
        $dbase = "$basic_database_name";
    }
    return  basic_db_connect($server, $name, $passwd, $dbase);
}

// Can we get e-mail address line from the prover's last name?
// Returns a html anchor if so, empty string if not

function basic_sub_address($lastname = '')
{
    if (empty($lastname)) {
        return '';
    }

    $lastname = addslashes($lastname);
    $query = "SELECT name, email, visible, lastname
        FROM person WHERE lastname LIKE '%$lastname%'";
    $sth = lib_mysql_query($query, $GLOBALS['db'], 'Invalid query in this page view, contact Chris');

  # Might be several matches....
    while ($row = $sth->fetch()) {
        if (!preg_match("/\b$lastname\b/", $row['lastname'])) {
            continue;
        }
        $out = $row['name'];
        if ($row['visible'] == 'yes') {
            $out = '<a href="mailto:' . $row['email'] . "\">$out</a>";
        }
        return " ($out)";
    }

    return '';
}

// Index for home.php and index.php

function basic_index()
{
# Leaving all formating to the calling page
    $text = <<< TEXT
  digits:&nbsp;
   <a href="index.php?stop=1" class=index>1</a>&nbsp;
   <a href="index.php?start=2&amp;stop=2" class=index>2</a>&nbsp;
   <a href="index.php?start=3&amp;stop=3" class=index>3</a>&nbsp;
   <a href="index.php?start=4&amp;stop=4" class=index>4</a>&nbsp;
   <a href="index.php?start=5&amp;stop=5" class=index>5</a>&nbsp;
   <a href="index.php?start=6&amp;stop=6" class=index>6</a>&nbsp;
   <a href="index.php?start=7&amp;stop=7" class=index>7</a>&nbsp;
   <a href="index.php?start=8&amp;stop=9" class=index>8</a>&nbsp;
   <a href="index.php?start=10&amp;stop=11" class=index>10</a>&nbsp;
   <a href="index.php?start=12&amp;stop=15" class=index>12</a>&nbsp;
   <a href="index.php?start=16&amp;stop=19" class=index>16</a>&nbsp;
   <a href="index.php?start=20&amp;stop=24" class=index>20</a>&nbsp;
   <a href="index.php?start=25&amp;stop=36" class=index>25</a>&nbsp;
   <a href="index.php?start=37&amp;stop=78" class=index>37</a>&nbsp;
   <a href="index.php?start=79&amp;stop=142" class=index>79</a>&nbsp;
   <a href="index.php?start=143&amp;stop=700" class=index>143</a>&nbsp;
   <a href="index.php?start=701&amp;stop=4000" class=index>701</a>&nbsp;
   <a href="index.php?start=4001" class=index>4001</a>+
TEXT;
    return ($text);
}

// Returns the id or NULL.  Warning: short forms are not unique!

function basic_number_id_via_short($short)
{
    global $db;
    if (!empty($short) or (isset($short) and $short == 0)) {
        $query = "SELECT id FROM numbers WHERE short = :short";
        $sth = $db->prepare($query);
        $sth->bindParam(':short', $short);
        try {
            $sth->execute();
            if ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                return $row['id'];
            }
        } catch (PDOException $ex) {
            lib_mysql_warn("basic_number_id_via_short failed", $query);
        }
    } else {
        lib_warn("basic_number_id_via_short passed empty string");
    }
    return(null);
}

// Returns the id or NULL.  Warning: long forms may not be unique for
// decimal expansions (3.14159... verses 3.1415926535...)

function basic_number_id_via_long($long)
{
    global $db;
    if (!empty($long) or (isset($long) and $long == 0)) {
        $long = preg_replace("/\s/", '', $long);  # Remove any whitespace
        $digits = strlen($long);
        if ($digits < 23) {
            return(basic_number_id_via_short($long));
        }
        if (preg_match("/^-?(\d{16})/", $long, $match)) {
            $leading_digits = $match[1];
            $log10 = $digits - 16 + log($leading_digits) / log(10);
        } elseif (preg_match("/^-?(\d+\.?\d{0,15})/", $long, $match)) {
            $leading_digits = $match[1];
            $log10 = log($leading_digits) / log(10);
        }

        $log_max = $log10 * 1.000001;
        $log_min = $log10 / 1.000001;

        $query = "SELECT id, long_, short FROM numbers WHERE log10 > $log_min AND log10 < $log_max";

        if ($sth = lib_mysql_query($query, $db, "basic_number_id_via_long failed (355)")) {
            while ($row = $sth->fetch()) {
                $long2 = preg_replace("/\s/", '', $row[1] ?? '');  # Remove any whitespace
                if (empty($long2)) {
                    $long2 = $row[2] ?? '';
                }
                if (strcmp($long2, $long) == 0) {
                    return($row[0]);
                }
            }
            return(null);
        } else {
            lib_mysql_warn("basic_number_id_via_long failed (363)", $query);
        }
    } else {
        lib_warn("basic_number_id_via_long passed empty string (366)");
    }
    return(null);
}

// Returns the short form or NULL

function basic_number_short_via_id($number_id)
{
    global $db;
    if (isset($number_id) and $number_id > 0) {
        $query = "SELECT short FROM numbers WHERE id = $number_id";
        if (($sth = lib_mysql_query($query, $db, 'basic_number_short_via_id failed (377)')) and ($row = $sth->fetch(PDO::FETCH_NUM))) {
            return($row[0]);
        } else {
            lib_mysql_warn("basic_number_short_via_id failed (380)", $query);
        }
    } else {
        lib_warn("basic_number_short_via_id passed an invalid number id (passed \"$number_id\") (383)");
    }
    return(null);
}

// Returns the long form or NULL

function basic_number_long_via_id($number_id)
{
    global $db;
    if (isset($number_id) and $number_id > 0) {
        $query = "SELECT short, long_ FROM numbers WHERE id = " . $db->quote($number_id);
        if (($sth = lib_mysql_query($query, $db, "basic_number_long_via_id failed (391)")) and ($row = $sth->fetch(PDO::FETCH_NUM))) {
            if (!empty($row[1])) {
                return $row[1];
            }
            if (preg_match("/^\d+\.?\d*$/", $row[0])) {
                return($row[0]);
            }
            lib_mysql_warn("basic_number_long_via_id failed--no long form defined", $query);
            return($row[0]);
        } else {
            lib_mysql_warn("basic_number_long_via_id failed", $query);
        }
    } else {
        lib_warn("basic_number_long_via_id passed an invalid number id (passed \"$number_id\")");
    }
    return(null);
}

// basic_check_short($short) returns '' if the number is valid
// Otherwise it returns a HTML error notice

function basic_check_if_valid($short, $long = '')
{
    global $vv_sign, $vv_log10; # Sets these to be used by other programs

  # Pick off the sign
    if (preg_match("/^([-])(.*)$/", $short, $match)) {
        $vv_sign = '-';
        $short = $match[2];
    } else {
        $vv_sign = '+';
    }

    if (preg_match("/^\d+$/", $short)) {
        if (strlen($short) > 23) {
            return "short form is too long";
        }
        $vv_log10 = log10($short);
        if (!empty($long)) {
            return "long should be left empty for short numbers";
        }
    } elseif (preg_match("/^(\d{5})\.\.\.\d{5} \((\d+)\-digits\)$/", $short, $x)) {
        $vv_log10 = log10($x[1]) + $x[2] - strlen($x[1]);
    } elseif (preg_match("/^(\d+\.\d+)\.\.\.\$/", $short, $x)) {
        if (strlen($short) > 24) {
            return "short form is too long, truncate the expansion";
        }
        $vv_log10 = log10($x[1]);
    } elseif (preg_match("/^(\d+\.\d+)$/", $short, $x)) {
        if (strlen($short) > 24) {
            return "short form is too long, truncate the expansion";
        }
        $vv_log10 = log10($x[1]);
    } else {
        return("short form \"$short\" unrecognized.  Examples:
      <blockquote><table border=1 cellpadding=3>
	<tr><td>31415</td><td>Positive integer, with at most 23 digits.</td></tr>
	<tr><td>-1456</td><td>Negative integer (at most 23 digits.)</td></tr>
	<tr><td>1.456...</td><td>Real number, aim for 20 total digits (plus
	  three dots).</td></tr>
	<tr><td>1.456</td><td>Rational number, maximum 23 total digits.</td></tr>
	<tr><td>21456...32123&nbsp;(23-digits)</td>
	  <td>Large integer: five digits <b>exactly</b> at each end, dash necessary,
	  parenthesis necessary, single space necessary.</td></tr>
      </table></blockquote>");
    }
    return ''; # All okay!
}

// basic_create_number($short,$long) creates a new number and returns
// its id (or if one with $short exists, just returns that id)
// Succeeds or dies.

function basic_create_number($short, $long = '')
{
    global $vv_log10, $vv_sign;  # shared with previous routine
    global $db;
  # If it is already in database, we are done!
    if ($id = basic_number_id_via_long($short)) {
        return $id;
    }

  # Is it valid?  If not, print error and die. (Really die?)
    if ($error = basic_check_if_valid($short)) {
        lib_warn($error);
        die("basic_create_number failed because \$short was invalid");
    }

  # quote long, the rest quoted in $query
    if (empty($long)) {
        $long = 'NULL';
    } else {
        $long = "'$long'";
    }

  # Form the $query, do the work.
    $query = "INSERT numbers (short,long_,log10,sign,id,modified,digits)
        VALUES('$short',$long,$vv_log10,'$vv_sign',NULL,NOW(),floor($vv_log10+1))";
    $sth = lib_mysql_query($query, $db, "basic_create_number failed (472)");
    if ($sth->rowCount() == 0) {
        lib_mysql_die("basic_create_number failed? query succeded, but no
	rows were effected!", $query);
    }

  # done; return the id
    return $db->lastInsertId();
}

// basic_create_curio($text,$number_id,$visible,$submitter,$address)
// creates a new curio and returns its id.  Succeeds or dies.

function basic_create_curio($text, $number_id, $visible, $submitter, $address)
{
    global $db;
    $text = AddSlashes($text);

  # See if the curio is already there--if so, complain and die.
    try {
        $sth = $db->prepare('SELECT COUNT(*) FROM curios WHERE number_id=:number_id AND text=:text');
        $sth->bindValue(':number_id', $number_id);
        $sth->bindValue(':text', $text);
        $sth->execute();
        $row = $sth->fetch(PDO::FETCH_NUM);
    } catch (PDOException $ex) {
        lib_mysql_die('basic_create_curio error (496)', $query . '<p>' . $ex->getMessage());
    }
    if ($row[0] > 0) {
        lib_mysql_die("This exact curio is already in the database (" . $sth->rowCount() . ")--it can not be resubmitted.", '');
    }

  # Form the $query, do the work.
    $query = "INSERT curios (number_id,text,visible,submitter,email,modified,created,id)
        VALUES(:number_id,:text,:visible,:submitter,:address,NOW(),NOW(),NULL)";
    try {
        $sth = $db->prepare($query);
        $sth->bindValue(':number_id', $number_id);
        $sth->bindValue(':text', $text);
        $sth->bindValue(':visible', $visible);
        $sth->bindValue(':submitter', $submitter);
        $sth->bindValue(':address', $address);
        $sth->execute();
    } catch (PDOException $ex) {
        mail('admin@t5k.org', 'Curios! Database error!', "$ex\n number_id: $number_id\n text: $text\n submitter:$submitter\n address: $address\n $query");
        $repair_query = "repair table curios, numbers, person";
        lib_mysql_query($repair_query, $db, "basic_create_curio failed, then repair failed! (518)");
        $sth->execute() or lib_mysql_die("basic_create_curio failed (after repair!) (519)", $query);
        mail('admin@t5k.org', 'Curios! Database error FIXED!', $query);
    }

    if ($sth->rowCount() > 0) {
        return ($db->lastInsertId());
    }

    lib_mysql_die("basic_create_curio failed? query succeded, but no
	rows were effected!", $query);
    return(null);
}
