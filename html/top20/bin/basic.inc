<?php

require_once __DIR__ . "/../../../library/constants/environment.inc";

function stopwatch($s)
{
    global $basic_start_time;
    echo "<li> At $s, " . sprintf('%.4f seconds.'),
        (array_sum(explode(" ", microtime())) - $basic_start_time) . '</li>';
}

foreach ($_REQUEST as $a => $b) {   # First, block standard evil access
    if (
        !empty($b) and
        ( !is_scalar($b) or preg_match('/\/\.\.\/\.\.\/\.\.\//', $b) or preg_match('/\/etc\/passwd/', $b) )
    ) {
        header("HTTP/1.0 404 Not Found");
        error_log("blocked $a=$b in $_SERVER[PHP_SELF]", 0);
        exit;
    }
}

// Okay, lib.inc contains most of my generic routines.  basic.inc includes
// those that are specific to the collection.

// FUNCTIONS            ACTIONS

// basic_DatabaseName       returns database name
// basic_CollectionName     returns the public collection name
// basic_db_connect             Connects or dies for user pages
// basic_db_admin_connect       Connects or dies (with extra privileges) for admin user
// basic_to_base()      returns a string like '../../' which will give us the
// relative path to the base e.g., "include(basic_to_base().'bin/parser/quad.inc')"

// basic_index          returns an index for the collection

// basic_sub_address($username) return 'mailto' type link if ok
// basic_address($role)     returns address string for editors

// basic_upload_max_size()  How many bytes do we allow in a file upload
// basic_upload_where()     Where do we place the uploaded files?

// basic_get_person_from_id Return an array with the requested columns (in the same
// basic_get_person_from_name   order the columns were listed).

// basic_tab_index()            The string " tabindex=# " where # starts at 1 and is
//                              incremented each call.  For seting HTML client focus order.

///// Initialization strings //////
$basic_start_time = (float) array_sum(explode(" ", microtime()));
        # current time for deciding how long it takes to create pages
$basic_color_scheme = 'brown';             # Must preceed including library: lib.inc
$basic_base_directory = 'top20';          # Used by basic_to_base
$basic_database_name = 'primes';
$basic_log_changes = 'yes';               # Set to non-empty if logging (bin/log.inc) enabled
$basic_Use_Database_for_Authorization = 1;  # Use the database to decide who can edit what

$basic_tab_index_counter = 0;           # Counter used in basic_tab_index()

function basic_tab_index()
{
    global $basic_tab_index_counter;
    $basic_tab_index_counter++;
    return " tabindex=$basic_tab_index_counter ";
}

// First, include the library functions.
include_once(basic_to_base() . 'bin/lib.inc');

// overide the color scheme
$ltcolor = '#F4F8FF';
$medcolor = '#DDEEFF';

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

function basic_upload_where()
{
# Where do we put uploaded files
    return basic_to_base() . "includes/gifs/";
}

// Sadly, where are we calling from affects how we include as I can not
// seem to reset $include_path outside of php.ini.
// Let's find where we are relative to /primes/ and work from there.
// Note this returns a relative path ending with a "/"

function basic_to_base($URL = '')
{
    global $basic_base_directory;
    if (empty($URL)) {
        $URL = $_SERVER['PHP_SELF'];
    }
  # returns a string like '../../' which will give us the relative path to the base
  # start by removing everything up to, and including the first /$basic_base_directory/
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

# I wanted e-mail addresses in just one place.  No in database though because
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

// basic_db_connect is our main function to connect to the database.  Use as:
//
// $db = basic_db_connect(); # Connects or dies

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
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);   # throws a PDOException, instead of a php warning or silence...
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);       # removes a type of code injection possibility
    } catch (PDOException $ex) {
        die("basic_db_conect: " . $ex->getMessage());
    }
    $basic_database_connection = $db;  // used in lib_warn ...
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

// Return a hash array with the requested columns (column name => value).
// The default is to get all columns.

function basic_get_person_from_id($person_id, $columns)
{
    global $basic_database_connection;
    lib_die("<li>basic_get_person_from_id untested... is it okay now? Is it ever used?");
    if (!preg_match('/^[\w ,()|*]*$/', $column)) {
        lib_warn("basic_get_person_from_id error 162: illegal characters in column '$column', using *");
        $columns = '*';
    }
    if (empty($columns)) {
        $columns = '*';  # Default is all
    }
    if (!empty($person_id)) {
        $person_id = $basic_database_connection->quote($person_id);
        $query = "SELECT $columns FROM person WHERE id = $person_id";
        if ($sth = lib_mysql_query($query, $basic_database_connection, "basic_get_person_from_id failed (170)", 'warn')) {
            return($sth->fetch(PDO::FETCH_ASSOC));
        }
    } else {
        lib_warn("basic_get_person_from_id passed an empty string (173)");
    }
    return(null);
}

function basic_get_person_from_username($username, $columns)
{
    global $basic_database_connection;
    lib_die("<li>basic_get_person_from_username untested... is it okay now? Is it ever used?");
  # Lastname is set to be unique, so there will just be one match at most
    if (!preg_match('/^[\w ,()|*]*$/', $column)) {
        lib_warn("basic_get_person_from_username error 184: illegal characters in column '$column', using *");
        $columns = '*';
    }
    if (empty($columns)) {
        $columns = '*';  # Default is all
    }
    if (!empty($username)) {
        $username = $basic_database_connection->quote($username);
        $query = "SELECT $columns FROM person WHERE username = $username";
        if ($sth = lib_mysql_query($query, $basic_database_connection, '', 'silent')) {
            if ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                return($row);
            }
        } else {
          # No matching id.  Lets not print a warning, NewProver.php uses this
          # to look for matching entries...
          # lib_mysql_warn("(remove this message?) basic_get_person_from_username failed",$query);
        }
    } else {
        lib_warn("basic_get_person_from_username (199) passed an empty string");
    }
    return(null);
}
