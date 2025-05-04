<?php

# If we are at the test site, lets display all errors!
if (file_exists("/var/www/html/TESTSITE")) {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
    ini_set('html_errors', true);
}

require_once __DIR__ . "/../../../library/constants/environment.inc";

# First, block standard evil access. The http access_log shows what is blocked.
foreach ($_REQUEST as $a => $b) {
    if (
        !empty($b) and
        ( !is_scalar($b) or preg_match('/\/\.\.\/\.\.\/\.\.\//', $b)
        or preg_match('/\/etc\/passwd/', $b) )
    ) {
      # check array whitelist
        if (!is_scalar($b) or preg_match('/^(PrimeStatus)$/', $a)) {
        } else {
            header("HTTP/1.0 404 Not Found");
            error_log("blocked $a=$b in $_SERVER[PHP_SELF]", 0);
            exit;
        }
    }
}

// Okay, lib.inc contains most of my generic routines.  basic.inc includes
// those that are specific to the collection.

// FUNCTIONS            ACTIONS

// basic_DatabaseName       returns database name
// basic_CollectionName     returns public collection name (PrimePages...)
// basic_db_connect             Connects or dies for user pages
//   also defines $basic_database_connection, the $db connection variable

// basic_db_admin_connect       Connects or dies with extra privileges for admin
// basic_to_base()      returns a string like '../../' which will give
//  us the relative path to the base e.g.,
//  "include(basic_to_base().'bin/parser/quad.inc')"

// basic_index          returns an index for the collection

// basic_sub_address($username) return 'mailto' type link if ok
// basic_address($role)     returns address string for editors

// basic_upload_max_size()  How many bytes do we allow in a file upload
// basic_upload_where()     Where do we place the uploaded files?

// basic_get_person_from_id Return an array with the requested columns (in
// basic_get_person_from_name   the same order the columns were listed).

// basic_tab_index()        The string " tabindex=# " where # starts at 1
//  and ii incremented each call.  For seting HTML client focus order.


// VARIABLES AND CONSTANTS
$basic_start_time = (float) array_sum(explode(" ", microtime()));
                # current time for deciding how long it takes to create pages
$basic_color_scheme = 'blue';           # Must precede including lib.inc
$basic_base_directory = 'primes';   # Used by basic_to_base
$basic_database_name = 'primes';    # The name of the database
$basic_collection_name = 'PrimePages'; #
$basic_log_changes = 'yes';     # Non-empty if logging (log.inc) enabled
$basic_Use_Database_for_Authorization = 1;# Use database to decide who can edit

$basic_tab_index_counter = 0;           # Counter used in basic_tab_index()
function basic_tab_index()
{
    global $basic_tab_index_counter;
    $basic_tab_index_counter++;
    return " tabindex=$basic_tab_index_counter ";
}


// Now the code

// First, include the library functions and internationalization routines
if (file_exists(basic_to_base() . 'bin/lib.inc')) {
    include_once(basic_to_base() . 'bin/lib.inc');
} elseif (file_exists('/var/www/html/primes/bin/lib.inc')) {
    include_once('/var/www/html/primes/bin/lib.inc');
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
    return 20000000;
}

function basic_upload_where()
{
# Where do we put uploaded files
    return basic_to_base() . "includes/uploads/";
}

// Sadly, where are we calling from affects how we include as I can not
// seem to reset $include_path outside of php.ini.
// Let's find where we are relative to $basic_base_directory and work from there
// Note this returns a relative path ending with a "/"

function basic_to_base($URL = '')
{
    global $basic_base_directory;
    if (empty($URL)) {
        $URL = $_SERVER['PHP_SELF'];
    }
    $URL = preg_replace('#//+#', '/', $URL);
  # returns a string like '../../' which will give us the relative path to the
  # base. We start by removing everything up to, and including the first
  # /$basic_base_directory/
    $to_base = preg_replace("/^.*?\/$basic_base_directory\//", '', $URL);
  # page.php/10.htm is not a directory, remove it
    $to_base = preg_replace("/page.php\/.*$/", '', $to_base);
  # now how many steps do we need to back up?
    $to_base = preg_replace("/[^\/]*\//", '../', $to_base);
    $to_base = preg_replace("/[^\/]*$/", '', $to_base);
    if (empty($to_base)) {
        $to_base = "./";
    }

    return $to_base;
}

# I wanted e-mail addresses in just one place.  Not in database though because
# they need to work when the database fails (for error messages...)

function basic_address($role)
{
    if ($role == 'content editor') {
        return 'admin@t5k.org';
    }
    if ($role == 'technical editor') {
        return 'admin@t5k.org';
    }
    if ($role == 'errors to') {
        return 'admin@t5k.org';
    }
    if ($role == 'spam') {
        return 'admin@t5k.org';
    }
  # Need a default just in case!
    return 'admin@t5k.org';
}


// Set up an exception handler for database connections.
function exception_handler($exception)
{
    echo "Uncaught exception: " , $exception->getMessage(), "\n";
}
set_exception_handler('exception_handler');


// basic_db_connect is our main function to connect to the database.  Use as:
//
//  $db = basic_db_connect(); # Connects or dies

function basic_db_connect(
    $server = 'localhost',
    $name = 'primes_',
    $passwd = T5K_DB_PRIMES_PASSWORD,
    $dbase = ''
) {
    global $basic_database_name, $basic_database_connection;
// Return connection, or die
    if (empty($dbase)) {
        $dbase = "$basic_database_name";
    }
    try {
        $db = new PDO("mysql:host=$server;dbname=$dbase;charset=utf8", $name, $passwd);
    } catch (PDOException $ex) {
        die("basic_db_conect error 171: " . $ex->getMessage());
    }
  # on error, throw a PDOException, instead of a php warning or silence...
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  # removes a type of code injection possibility
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  # set default fetch mode
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
  # store for later use (ever used?)
    $db->DatabaseName = $basic_database_name;
    $basic_database_connection = $db;  // used in lib_warn()
    return ($db);
}

// basic_db_admin_connect       Connects or dies (with extra privileges) for admin user

function basic_db_admin_connect(
    $server = 'localhost',
    $name = 'primes_admin',
    $passwd = T5K_DB_PRIMES_ADMIN_PASSWORD,
    $dbase = ''
) {
    global $basic_database_name, $basic_database_connection;
    if (empty($dbase)) {
        $dbase = "$basic_database_name";
    }
    return  basic_db_connect($server, $name, $passwd, $dbase);
}

// Return a hash array with the requested columns. The default is to get all
// columns.

function basic_get_person_from_id($person_id, $columns = '', $db)
{
  # Protect the columns field.  Default is all
    if (empty($columns) or !preg_match('/^[\w ,]+$/', $columns)) {
        $columns = '*';
    }
    if (!preg_match('/^\d+$/', $person_id)) {
        lib_warn("basic_get_person_from_id passed '$person_id'");
    }
    if (!empty($person_id)) {
        $query = "SELECT $columns FROM person WHERE id = :id";
        try {
            $sth = $db->prepare($query);
            $sth->bindValue(':id', $person_id);
            $sth->execute();
        } catch (PDOException $ex) {
            lib_mysql_warn("basic_get_person_from_id error (basic.inc 209): " .
            $ex->getMessage(), $query);
        }
        if ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            return($row);
        }
    } else {
        lib_warn("basic_get_person_from_id passed an empty string");
    }
    return(null);
}

function basic_get_person_from_username($username, $columns, $also = '')
{
    global $basic_database_connection;
  # Might be called from stored code in admin/edit.inc, so the db handle
  # better be global username is set to be unique, so there will just be one
  # match at most but may also search on the lastname if $also is set, it may
  # not be unique

  # Protect the columns field.  Default is all
    if (empty($columns) or !preg_match('/^[\w ,]+$/', $columns)) {
        $columns = '*';
    }

    if (!empty($username)) {
        $query = "SELECT $columns FROM person WHERE username = :username";
        try {
            $sth = $basic_database_connection->prepare($query);
            $sth->bindValue(':username', $username);
            $sth->execute();
        } catch (PDOException $ex) {
            lib_mysql_warn("basic_get_person_from_username error (basic.inc 232): " . $ex->getMessage(), $query);
        }
        if ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            return($row);
        }

      # otherwise failed, if $also is not empty, then also try matching surnames
        if (!empty($also)) {
            $query = "SELECT $columns FROM person WHERE surname = :username";
            try {
                $sth = $basic_database_connection->prepare($query);
                $sth->bindValue(':username', $username);
                $sth->execute();
            } catch (PDOException $ex) {
                lib_mysql_warn("basic_get_person_from_username error (lib.inc 244): " . $ex->getMessage(), $query);
            }
            if ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                return($row);
            }
        }
      # If here, no matching id.  Lets not print a warning, NewProver.php uses
      # this to look for matching entries...
    } else {
        lib_warn("(215) basic_get_person_from_username passed an empty string");
    }
    return(null);
}

// Index for home.php and index.php

function basic_index()
{
    global $basic_base_directory;
# This is the index as used on the home page! (if any)

    if ($basic_base_directory == 'bios') {
        $text = "<P><div align=center>\n<hr class=index>\nProver Index:\n";
        $text .= <<< TEXT
   <a href="index.php?match=non-alphabetic" class=index title='non-alphabetic'>*</a>
   <a href="index.php?match=A" class=index>A</a>
   <a href="index.php?match=B" class=index>B</a>
   <a href="index.php?match=C" class=index>C</a>
   <a href="index.php?match=D" class=index>D</a>
   <a href="index.php?match=E" class=index>E</a>
   <a href="index.php?match=F" class=index>F</a>
   <a href="index.php?match=G" class=index>G</a>
   <a href="index.php?match=H" class=index>H</a>
   <a href="index.php?match=IJ" class=index>I J</a>
   <a href="index.php?match=K" class=index>K</a>
   <a href="index.php?match=L" class=index>L</a>
   <a href="index.php?match=M" class=index>M</a>
   <a href="index.php?match=N" class=index>N</a>
   <a href="index.php?match=O" class=index>O</a>
   <a href="index.php?match=P" class=index>P</a>
   <a href="index.php?match=QR" class=index>Q R</a>
   <a href="index.php?match=S" class=index>S</a>
   <a href="index.php?match=T" class=index>T</a>
   <a href="index.php?match=UV" class=index>U V</a>
   <a href="index.php?match=W" class=index>W</a>
   <a href="index.php?match=XYZ" class=index>X Y Z</a>
<hr class=index>
</div>
TEXT;
    } else {
        $text = <<< TEXT
<P><div align=center>
<hr class=index>
jump to: ah, what?
<hr class=index>
</div>
TEXT;
    }
    return($text);
}
