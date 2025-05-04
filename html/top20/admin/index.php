<?php

// We must *first* start the session (before anything is output!)
# ini_alter("session.use_cookies","0");
session_start();

# If we are at the test site, lets display all errors!
if (file_exists("/var/www/html/TESTSITE")) {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
    ini_set('html_errors', true);
}

if (!function_exists('__')) {
    function __($t)
    {
        return $t;
    }
}

// If came from somewhere else, remember so we can add a link back!
if (isset($_SERVER["HTTP_REFERER"]) and !preg_match("/admin\/index.php(.*)$/", $_SERVER["HTTP_REFERER"])) {
    $_SESSION["referer"] = $_SERVER["HTTP_REFERER"];
}

// register some globals
$xx_TableName   = isset($_REQUEST['xx_TableName']) ? $_REQUEST['xx_TableName']  : '';
$xx_edit    = isset($_REQUEST['xx_edit'])      ? $_REQUEST['xx_edit']   : '';
$xx_action  = isset($_REQUEST['xx_action'])    ? $_REQUEST['xx_action']     : '';
$xx_view    = isset($_REQUEST['xx_view'])      ? $_REQUEST['xx_view']   : '';
$xx_delete  = isset($_REQUEST['xx_delete'])    ? $_REQUEST['xx_delete']     : '';

// Okay, load up the include files
include_once("../bin/basic.inc");

// Does the person want to log out?  Can't make this work!!!!!!!!!!!!!!!!!!!!!!!
if ($xx_action == 'log out') {
    unset($GLOBALS['_SERVER']['PHP_AUTH_USER']);
    unset($GLOBALS['_SERVER']['PHP_AUTH_PW']);
    echo header("HTTP/1.0 401 Unauthorized");
    echo header("Location: $_SERVER[PHP_SELF]");
}

# Connects or dies; must come before admin_html_head
# Note we use a different user for these admin tasks so it can have more  priveleges
$db = basic_db_admin_connect();

# echo "[".basic_DatabaseName()."][".$GLOBALS['_SERVER']['PHP_AUTH_USER']."]<br>";

// Use the database authorization?
if (!empty($basic_Use_Database_for_Authorization)) {
    include_once('../bin/http_auth.inc');
    if (!my_auth('allow any')) {
        lib_die("You are not authorized to be here.", 'password');
    }
} # else relying on .htaccess and .htpasswd

// Okay for them to be here?  The .htaccess forces the creation of the auth user
include_once('security.inc');
$parameters = array('database_name' => basic_DatabaseName(),'table_name' => '*',
    'editors_username' => $GLOBALS['_SERVER']['PHP_AUTH_USER']);
$role = role($parameters);

if ($role != 'root' and $role != 'admin') {
    lib_die("You ('$parameters[editors_username]', id=$parameters[editors_id]) are not
	authorized to be here (role '$role').  You are not in the permissions table for
	'$parameters[database_name].*'");
}
### print "index.php 48 debug: db=$parameters[database_name], id=$parameters[editors_id], ".
### "name=$parameters[editors_username], table=$parameters[table_name], role=$role\n";


// Okay, lets get started
include_once('admin.inc');
echo admin_html_head(!empty($xx_TableName) ? "$xx_TableName table" : "administrative index");

// If xx_TableName is not defined, prints the generic (menu) page.
// When xx_TableName is defined, if one of xx_edit, xx_delete, xx_view is defined
// it does that (edit, delete, or view).  When these are all empty
// it starts with an index of the given xx_TableName.

// Requires $TableName to have the (primary) key: 'id'; that is what xx_edit
// xx_delete and xx_view would be set to.  Edit can also be 0 for a new entry.

$DatabaseName = basic_DatabaseName();

if (empty($xx_TableName)) { # Can't do anything without a table name!
    echo ReturnButtons('', 'page');
    echo admin_default_text();    # so send the default (menu) text
} elseif ($xx_view) {      # Passed an id, lets display it's fields
    echo ReturnButtons('index', 'page');
    echo "<P>The row with id=$xx_view is as follows:\n";
    echo lib_display_row($DatabaseName, $xx_TableName, "id=$xx_view");
} elseif (!empty($xx_edit) || $xx_edit == '0') {
  # Passed edit=id (0 = new), lets edit it
  # Not !empty($xx_edit)) or will not recognize 0 = new.
  # Not is_set($xx_edit) because we set it when we registered it (even if empty)

  # Perpare info to pass to the edit/display routines
    $parameters['entry_id'] = $xx_edit;
    $parameters['database_name'] = $DatabaseName;
    $parameters['table_name'] = $xx_TableName;

    include_once('edit.inc');

# print "xxx3 debug : db=$parameters[database_name] id=$parameters[editors_id] name=$parameters[editors_username] table=$parameters[table_name]\n";

    if ($xx_action == 'put') {  # returning from an edit form, put in db
        echo ReturnButtons('', 'page');
        if (generic_put_row_by_id($parameters)) {  # Might be 1 (success) or -1 (no change)
            include_once('index.inc');
            display_index();
        }
    } else {          # send an edit form
        echo ReturnButtons('index', 'page');
        generic_show_edit_table($parameters, '');
    }
} elseif (!empty($xx_delete)) {# Passed xx_delete=id, lets delete it
    if ($xx_action == 'confirmed') {# delete was confirmed
        include_once("../bin/log.inc");
        lib_delete_row_by_id($xx_delete, $xx_TableName, $db);
        log_action(
            $GLOBALS['db'],
            $parameters['editors_id'],
            'deleted',
            "$xx_TableName.id=$xx_delete",
            "Deleted $xx_TableName manually"
        );
        echo ReturnButtons('', 'page');
        include_once('index.inc');
        display_index();
    } else {          # otherwise ask to make sure
        lib_confirm_delete_by_id($xx_delete, $xx_TableName, $db);
    }
} else {            # Otherwise, lets show the index
    echo ReturnButtons('', 'page');
    include_once('index.inc');
    display_index();
}

echo admin_html_end();

function ReturnButtons($index = '', $page = '')
{
  # Make index and page not empty to show the corresponding links
    $out = '<form method=post action="' . $_SERVER['PHP_SELF'] . '?' . SID . "\">";
    if (!empty($index)) {
        $out .= "<input type=submit value=\"RETURN TO ADMIN INDEX\">
	<input type=hidden name=xx_TableName value=\"" .
        $GLOBALS['xx_TableName'] . "\">";
    }
    if (!empty($page) and !empty($_SESSION['referer'])) {
        $out .= "(<a href=\"$_SESSION[referer]\">Return to calling page</a>)";
    }
    $out .= "</form>\n";
    return $out;
}
