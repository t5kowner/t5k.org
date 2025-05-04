<?php

function __($t)
{
    return $t;
}

# If we are at the test site, lets display all errors!
if (file_exists("/var/www/html/TESTSITE")) {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
    ini_set('html_errors', true);
}

require_once __DIR__ . "/../../../library/constants/environment.inc";

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
// basic_CollectionName     returns the name of the collection (Primes Pages, ...)
// basic_db_connect     Connects or dies; used on 'user' pages
// basic_db_admin_connect   Connects or dies; used on admin pages--more authority?
// basic_to_base()      returns a string like '../../' which will give us the
//  relative path to the base e.g., "include(basic_to_base().'bin/parser/quad.inc')"

// basic_index          returns an index for the collection

// basic_sub_address($lastname) return 'mailto' type link if ok
// basic_address($role)     returns address string for editors

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
$basic_color_scheme = 'green';      # Must preceed including library: lib.inc
$basic_base_directory = 'glossary'; # Used by basic_to_base
$basic_database_name = 'glossary';
$basic_collection_name = 'Prime Glossary';
$basic_Use_Database_for_Authorization = false;  # How is edit authorized? Via database or .htaccess file?
$basic_Use_Database_for_Authorization_username = 'lastname'; # used in admin/security.inc to overide username as the key identifier
/////

// First, include the library functions.
include_once(basic_to_base() . 'bin/lib.inc');

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
    return "/glossary/includes/gifs/";
}

// Sadly, where are we calling from affects how we include as I can not
// seem to reset $include_path outside of php.ini.
// Let's find where we are relative to /$basic_base_directory/ and work from there.
// Note this returns a relative path ending with a "/"

function basic_to_base($URL = '')
{
    global $basic_base_directory;
    if (empty($URL)) {
        $URL = $_SERVER['PHP_SELF'];
    }
    $URL = preg_replace("/xpage\//", '', $URL);
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
//  $db = basic_db_connect(); # Connects or dies

function basic_db_connect($server = 'localhost', $name = 'primes_', $passwd = T5K_DB_PRIMES_PASSWORD, $dbase = '')
{
    global $basic_database_name, $basic_database_connection;
// Return connection, or die
    if (empty($dbase)) {
        $dbase = "$basic_database_name";
    }
    try {
        $db = new PDO("mysql:host=$server;dbname=$dbase;charset=utf8", $name, $passwd);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);       # throws a PDOException, instead of a php warning or silence...
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);               # removes a type of code injection possibility
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  # set default fetch mode
        $db->DatabaseName = $basic_database_name;
    } catch (PDOException $ex) {
        die("basic_db_conect: " . $ex->getMessage());
    }
    $basic_database_connection = $db;  // used in lib_warn()
    return ($db);
}

function basic_db_admin_connect($server = 'localhost', $name = 'primes_admin', $passwd = T5K_DB_PRIMES_ADMIN_PASSWORD, $dbase = '')
{
    return  basic_db_connect($server, $name, $passwd, $dbase);
}

// Index for home.php and index.php

function basic_index()
{
# This is the index as used on the home page!
    $text = <<< TEXT
<p><div align="center">
<hr class="index">
jump to:
   <a href="index.php?match=non-alphabetic" class="index">*</a>
   <a href="index.php?match=ab" class="index">a b</a>
   <a href="index.php?match=c" class="index">c</a>
   <a href="index.php?match=de" class="index">d e</a>
   <a href="index.php?match=f" class="index">f</a>
   <a href="index.php?match=g" class="index">g</a>
   <a href="index.php?match=hij" class="index">h i j</a>
   <a href="index.php?match=kl" class="index">k l</a>
   <a href="index.php?match=m" class="index">m</a>
   <a href="index.php?match=no" class="index">n o</a>
   <a href="index.php?match=p" class="index">p</a>
   <a href="index.php?match=qr" class="index">q r</a>
   <a href="index.php?match=s" class="index">s</a>
   <a href="index.php?match=t" class="index">t</a>
   <a href="index.php?match=uvw" class="index">u v w</a>
   <a href="index.php?match=xyz" class="index">x y z</a>
<hr class="index">
</div>
TEXT;
    return($text);
}
