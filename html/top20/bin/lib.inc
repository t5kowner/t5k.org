<?php

#t# 99% of the error messages here should not need translating.  So lets just leave it alone

// What belongs here are my basic library functions which are independent
// of the pages I am working on.  All of their names start with lib_ to make
// it clear where they are found.
//
//  Error Message Functions and misc
//
//    lib_mysql_warn(string,query) and lib_mysql_die(string,query)
//     print error messages (string, MySQL and then query)
//  lib_warn($message,$class='error',$log='log errors')
//  lib_die($message,$class='error',$log='log errors',$return='message')
//     print an error message (sets format for all error messages)
//      If return='silent' then don't tell the user anything
// lib_ErrorHandler($errno, $errstr, $errfile, $errline) Do not
//     call directly (see notes below). Used to display errors
//      caused by eval'ing strings stored in database.
//
//    lib_MyAddSlashes($st)  Add slashes, unless they already have
//     been added "magically."

//   Database Functions
//
//  lib_rows_in_table ($tablename,$db[,$where])
//      returns the number of rows in table(s) matching optional
//     $where, or dies
//  lib_list_fields ($db_name,$table_name[,$db])
//     Returns a ', ' delimited list of the fields in a given table
// lib_delete_row_by_id($id,$table_name,$dbh)
//       Should do the delete and print success screen
//    lib_confirm_delete_by_id($id,$TableName,$dbh)
//        Before deleting a row, call lib_confirm_delete_by_id
//     to see if that is what the user wants.
//   lib_add_row($table,$set[,$no_die])
//       Calls "INSERT $table $set".  Returns insert id or dies (unless
//       $no_die is un-empty in which case it returns 0)
//  lib_modify_row($table,$set,$where[,$die])
//        "UPDATE $table SET $set WHERE $where".  Returns number of rows altered
//       (0 or more); otherwise, if $die not empty, dies if no rows changed.
//  lib_get_enum_strings ($column_name,$tablename,$db_name)
//      Returns a "\0" delimited string of possible values
//       for BOTH 'enum' and 'set' type columns.
//      USE: $enum_list = explode("\0",lib_get_enum_strings(...));
//       for ($i=0; $i<count($enum_list); $i++) { echo $enum_list[$i] }
//   lib_get_column($where, $table, $fields, $db)
//     Performs "SELECT $fields FROM $table WHERE $where LIMIT 1"
//       and returns either the one column, an array ref for a hash
//       array of the columns or undef.
//
//
//   HTML Producing Functions (all return printable strings)
//
// lib_display_row($db_name,$table_name,$where,$flags)
//      Returns a single row from a table as HTML table string,
//      one row per field.  $flags is an array with flags (see the
//       code below for details) For example, if $flags['allow_empty']
//        is set, will not die if there is no matching row; otherwise will!
//        Uses: query = "SELECT * FROM $table_name WHERE $where"
//   lib_hidden_field ($name,$value)
//      returns a string for a hidden field in a form
//    lib_radio_button($name,$value[,$select])
//     Create a radio button with the given name and value.  If select (third
//       parameter) is present and equals the value, then this button is checked.
// lib_html_limit($string,$max_length=40)
//       truncates the string (if necessary) to given size then
//       quotes the HTML and returns a string
// lib_html_head ($t_title = 'no title')
//        Page heading, title, opening body tag
//    lib_html_tail ($string)
//      just $string followed by </BODY>...
//  lib_tr($string='')
//       returns <tr bgcolor=... $string> where the bgcolor alternates
//        for each subsequent call


//  Set color scheme; later may do this via a function call...

if (empty($GLOBALS['basic_color_scheme'])) {
    $GLOBALS['basic_color_scheme'] = 'blue';
}
lib_reset_colors($GLOBALS['basic_color_scheme']);

function lib_reset_colors($color)
{

    global $ltcolor, $medcolor, $drkcolor, $bgcolor, $mdbltcolor, $mdbmedcolor, $mdbdrkcolor;
    $bgcolor  = '#ffffff';
# white
    if ($color == 'brown') {
        $ltcolor  = '#fff9f0';
# alt table rows
        $medcolor = '#ffeecc';
# table headings ...
        $drkcolor = '#cc9900';
# border around tables ...
        $mdbltcolor  = 'brown lighten-5';
# banner,
        $mdbmedcolor = 'brown lighten-3';
# page title, table headings ...
        $mdbdrkcolor = 'brown';
# navbar header, footer ...
    } elseif ($color == 'green') {
        $ltcolor  = '#f0fff9';
    # alt table rows
          $medcolor = '#ccffee';
    # table headings ...
          $drkcolor = '#00cc99';
    # border around tables ...
          $mdbltcolor  = 'green lighten-4';
    # banner,
          $mdbmedcolor = 'green lighten-2';
    # page title, table headings ...
          $mdbdrkcolor = 'green darken-3';
    # navbar header, footer ...
    } elseif ($color == 'gold') {
        $ltcolor  = '#ffe0b2';
    # alt table rows
          $medcolor = '#ff7043';
    # table headings ...
          $drkcolor = '#d84315';
    # border around tables ...
          $mdbltcolor  = 'orange lighten-4';
    # banner,
          $mdbmedcolor = 'deep-orange lighten-1';
    # page title, table headings ...
          $mdbdrkcolor = 'deep-orange darken-3';
    # navbar header, footer ...
    } else {
    # Blue
          $ltcolor  = '#f0f9ff';
    # very light blue; alt table rows
          $medcolor = '#cceeff';
    # light blue; table headings ...
          $drkcolor = '#0099cc';
    # gray blue; border around tables ...
          $mdbltcolor  = 'blue lighten-4';
    # banner,
          $mdbmedcolor = 'blue lighten-2';
    # page title, table headings ...
          $mdbdrkcolor = 'indigo darken-3';  # navbar header, footer ...
    }
}

// Error Message Functions and Misc

// lib_warn($message) and lib_die($message) print an error message and define
// the basic format of all lib_* error messages.  By default will log these
// errors.  Make $log anything else to stop logging.
// If $return='silent' then don't tell the user anything (not $log must be set to use this!)

function lib_die($message, $class = 'error', $log = 'log errors', $return = 'message')
{

    lib_warn($message, $class, $log, $return);
    die();
}

function lib_warn($message, $class = 'warning', $log = 'log errors', $return = 'message')
{

    global $basic_database_connection, $http_auth_id;
// $db set in basic_db_connect
  // First, did they pass an error message?  If not, call it unknown.
    (isset($message) and !empty($message)) or $message = "Some unknown error occurred.";
// Now, display the message
    if ($return == 'message') {
        echo "<table><tr><td>\n<font color=red>Error: $message</font>\n</td></tr></table>\n";
    }
  // If the correct modules are loaded, add this error to the log
    if (function_exists('log_action') and isset($basic_database_connection)) {
// log_action($basic_database_connection, $who, $what, $where, $notes)
        $who = !empty($http_auth_id) ? $http_auth_id : 254;
// Note that 254 is the 'System' account id number.  $http_auth_id is the authorized person's id (if any)
        $from = (php_sapi_name() == 'cli') ? $_SERVER["SCRIPT_FILENAME"] : $_SERVER['REQUEST_URI'];
        $from = preg_replace('/\/\w\w_\w\w\//', '/', $from);
        if ($log == 'log errors') {
            log_action($basic_database_connection, $who, $class, $from, $message);
        }
    }
  // Finally, if messages are hidden from the user--do an abort!
    if ($return == 'silent' and php_sapi_name() != 'cli') {
# Abort!!! (unless from command line)
        header($_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized', true, 401);
    // header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
    }
}

// lib_mysql_warn(string) and lib_mysql_die(string)
// prints string (error message) (if defined) then prints $query and MySQL error messages (if defined)
// I use this after queries to the MySQL database.

function lib_mysql_die($string, $query = '')
{

    lib_mysql_warn($string, $query);
    die;
}

function lib_mysql_warn($string, $query)
{

    $message = '';
    if (isset($string)) {
        $message .= "$string\n";
    }
    $message .= '<BLOCKQUOTE><dl>';
    $qquery = preg_replace("/\n/", '<br>', $query);
    if (isset($query) and strlen($query) > 0) {
        $message .= "<dt>MySQL query<dd>$qquery\n";
    }
  // try { getData($basic_database_connection); } catch (PDOException $ex) { $message .= "<dt>error message<dd>".$ex->getMessage(); }
    $message .= "</DL></BLOCKQUOTE>\n";
    lib_warn($message);
}

// Error handler function meant for eval($eval_string) where $eval_string is global.
// Will not catch parse errors.  Possible usage:
//
//      error_reporting(E_ALL);
//      set_error_handler("lib_ErrorHandler");
//      GLOBAL $eval_string;
//      $eval_string = $string;
//      $result = eval($string);
//      restore_error_handler();
//
// I use this for the stored code in admin/edic.inc; it is wonderful!!!

function lib_ErrorHandler($errno, $errstr, $errfile, $errline)
{

  # Are certain error types ignored (E_NOTICE...)?
    if (!($errno & error_reporting())) {
        return;
    }

    $lines = explode("\n", $GLOBALS['eval_string']);
    echo "<table border=1 cellpadding=5><tr><td>
	<B>Error number $errno</b>: $errstr<br>(file $errfile)<br>
	in (or near) line $errline:
	<blockquote>\n";
    for ($no = 0; $no < count($lines); $no++) {
        if ($no + 1 == $errline) {
            echo "<font color=red><b>$lines[$no]</b></font><br>";
        } else {
            echo "$lines[$no]<br>";
        }
    }
    echo "</blockquote>\n</td></tr></table>";
}

// Now an odd ball or two:

function lib_MyAddSlashes($st)
{

  # Add slashes, unless they already have been added "magically"
#  if (get_magic_quotes_gpc()==1) {
#    return $st;
#  } else {
    return AddSlashes($st);
#  }
}


// Database Functions

// A new generic wrapper for access (similar to the old approach).
// But do not do this with data from users--using prepare/bind/execute; this function returns
// a PDO object (statement handle) which can be used as follows:
//
//   $row = $stmt->fetch(PDO::FETCH_NUM);
//   while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ...
//   $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

function lib_mysql_query($query, $db, $error_message = '')
{

    if ($db instanceof PDO) {
        try {
            $stmt = $db->query($query);
        } catch (PDOException $ex) {
            lib_mysql_die("Error 261 in lib.inc's lib_mysql_query: " . $ex->getMessage(), $query);
        }
      // while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { print_r($row); }
        return $stmt;
# a PDO object
    } else {
        $trace = debug_backtrace();
        list(, $caller) = debug_backtrace(false);
        $temp = "called by {$caller['function']}";
        if (isset($caller['class'])) {
            $temp .= " in {$caller['class']}";
        }
        lib_mysql_die(
            "Error 248 in lib_mysql_query: $temp without first " .
            "establishing connection to the database. (suberror: $error_message)",
            $query
        );
    }
}


// lib_rows_in_table ($tablename,$db[,$where]) either returns the number
// of rows in the given tables (comma delimited) matching the optional
// $where, or dies

function lib_rows_in_table($tablename, $db, $where = '')
{

  // How many rows in table $tablename?  Succeed or die.
    if ($where) {
        $where = "WHERE $where";
    }
    $query = "SELECT COUNT(*) FROM $tablename $where";
// print "<li>$query";
    try {
        $stmt = $db->query($query);
        $row = $stmt->fetch(PDO::FETCH_NUM);
    } catch (PDOException $ex) {
        lib_mysql_die('Error in lib_rows_in_table', $query);
    }
    return($row[0]);
}

//  lib_get_column($where, $table, $fields, $db)
//  Performs "SELECT $fields FROM $table WHERE $where LIMIT 1"
//   and returns either the one column, an array ref for a hash
//   array of the columns or undef.

function lib_get_column($where, $tablename, $fields, $db)
{
    if (empty($tablename)) {
        lib_my_die("lib_get_column must be passed a table name.\n");
    }
    if ($where) {
        $where = "WHERE $where LIMIT 1";
    }
    $query = "SELECT $fields FROM $tablename $where";
### echo "zzz <pre>$query</pre>";

    try {
        $stmt = $db->query($query);
    } catch (PDOException $ex) {
        lib_mysql_die("Error 308 in lib_get_column (database $db, error=$ex)", $query);
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
### echo print_r($row);

    if (preg_match("/,/", $fields)) {
        return $row;
# Return array reference
    } else {
        if (preg_match('/ as (\w+)$/i', $fields, $match)) {
            $fields = $match[1];
        } elseif ($fields == 'prime.rank') {
        # Annoying--but mysql made rank a function so I must call the row prime.rank
            # howerver pdo returns the column name 'rank' not 'prime.rank'.  Odd.
            $fields = 'rank';
        }
        return($row[$fields] ?? false); # Return the one column
    }
}

// Returns, as a string, a row from a table as a HTML table,
// one row per field.
// Uses: query = "SELECT * FROM $table_name WHERE $where"
// The array flags might have the following keys set
//
//    allow_empty don't die if no matching row
// allow_multiple  print tables for each row (not just the first)
//   nl2br       change new lines to BR's
// select      limit the fields selected (comma delimited string)


function lib_display_row($db_name, $table_name, $where, $flags = '')
{

    global $mdbltcolor, $db;
# If the $where does not include the id, then the routine calling may no know it.
    global $lib_display_row_id;
# Share the id of the row with other routines (or the last id if more than one row)

    $lib_display_row_id = '';
# First get the column info
    isset($db_name) or die("Must define db_name in display_row");
    isset($table_name) or die("Must define table_name in display_row");
    $query = "SELECT * FROM $table_name WHERE $where";
    try {
        $stmt = $db->query($query);
    } catch (PDOException $ex) {
        lib_mysql_die("lib_display_row failed (error 317), does the table
         $table_name exist in the database $db_name?", $query);
    }

  # Grab table meta data
    foreach (range(0, $stmt->columnCount() - 1) as $column_index) {
        $meta[] = $stmt->getColumnMeta($column_index);
    }

  # now process the rows into $out
    $out = '';
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $out .= '<blockquote><table class="td2 table-bordered table-hover table-striped">
        <tr class="' . $mdbltcolor . ' text-center"><th class="font-weight-bold">field</th><th class="font-weight-bold">value</th></tr>' . "\n";
        $count++;
        foreach ($row as $column_index => $column_value) {
            $column_info = $meta[$column_index];
            $column_name = $column_info['name'];
            $column_type = $column_info['native_type'];
            $column_length = $column_info['len'];
            if (empty($flags['select']) or preg_match("/\\b$column_name\\b/", $flags['select'])) {
                $out .= '<tr><td class="text-right" title="' . $column_type . '">' . $column_name . '</td><td title="' . $column_length . '">' .
                    (empty($flags['nl2br']) ? htmlspecialchars($column_value) :
                str_replace(array("\r\n", "\r", "\n"), "<br>", htmlspecialchars($column_value)))
                    . "</td></tr>\n";
                if ($column_name == 'id' and preg_match('/^\d+$/', $column_value)) {
                        $lib_display_row_id = $column_value;
                }
            }
        }
        $out .= "</table></blockquote>\n";
    # Do we allow multiple tables (rows) or are we now done?
        if (!empty($flags['allow_multiple'])) {
            $out .= "<P>\n";
        } else {
            break;
        }
    }

  # What if there were no matching rows?
    if ($count == 0) {
        if (empty($flags['allow_empty'])) {
            lib_mysql_die("No row matching the given query (360)", $query);
        } else {
            $out = '';
        }
    }
    return($out);
}

// Return a ', ' delimited list of the fields in a given table

function lib_list_fields($db_name, $TableName, $db = '')
{
    if (!($db instanceof PDO)) {
        $db = $GLOBALS['db'];
    }
  # Get meta info about the row
    try {
        $sth = $db->prepare("DESCRIBE $TableName");
#    $sth->bindParam(':TableName',$TableName);
        $sth->execute();
    } catch (PDOException $ex) {
        lib_mysql_die("lib_list_fields(378), does the table
         '$TableName' exist in the database '$db_name'", $ex);
    }

    $delim = '';
    $out = '';
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $out .= $delim . $row['Field'];
        $delim = ', ';
    }
  # print "<li>$out";
    return($out);
}

// Should do the delete and print success screen.  Probably should
// always call lib_confirm_delete_by_id first.

function lib_delete_row_by_id($id, $table_name, $dbh)
{

    if (!($dbh instanceof PDO)) {
        lib_die('$dbh must be a PDO database handle in lib_delete_row_by_id');
    }

    if (basic_DatabaseName() == 'curios' and $table_name == 'curios') {
        $query = "REPLACE INTO deleted SELECT curios.* FROM curios WHERE id='$id'";
        lib_mysql_query($query, $dbh, "lib_delete_row_by_id failed to replace", $query);
    }
    if (basic_DatabaseName() == 'primes' and $table_name == 'prime') {
        $query = "REPLACE INTO deleted SELECT prime.* FROM prime WHERE id='$id'";
        lib_mysql_query($query, $dbh, "lib_delete_row_by_id failed to replace", $query);
    }

    $query = "DELETE FROM $table_name WHERE id='$id'";
# quoted $id necessary if id is a string (or is mistaken for a column name)
    try {
        $sth = $dbh->prepare($query);
        if ($sth->execute()) {
            print ("<blockquote><font color=green>Delete from $table_name
	successful.</font></blockquote>");
        } else {
            print ("<blockquote><font color=red>Delete (lib_delete_row_by_id)
	failed.  Does the entry exist?</font></blockquote>");
        }
    } catch (PDOException $ex) {
        lib_mysql_die('lib_delete_row_by_id failed"', $query);
    }
}

// Before deleting a row, call lib_confirm_delete_by_id
// to see if that is what the user wants.  The originating
// page is called back with xx_action=confirmed or "not confirmed."
// In the first case xx_delete=$id is passed in a hidden field in
// the form.

function lib_confirm_delete_by_id($id, $TableName, $db)
{
    global $basic_database_name;
    echo "<P>Are you sure you want to delete this row (id=$id, table=$TableName)?
        <blockquote><table><tr><td><form method=post action=$_SERVER[PHP_SELF]>
        <input type=submit name=xx_action value=confirmed>" .
        lib_hidden_field('xx_TableName', $TableName) . # need to know which table!
    lib_hidden_field('xx_delete', $id) . "\n\t</form></td>
        <td><form method=post action=$_SERVER[PHP_SELF]>
        <input type=submit value=\"not confirmed\">
        </form></td></tr></table></blockquote>\n";
# Now display the row so they can make sure it is the right one!
    echo lib_display_row($basic_database_name, $TableName, "id='$id'");
  # quoted $id necessary if id is a string (or is mistaken for a column name)
}

// lib_add_row($table,$set)
//  Calls "INSERT $table $set".  Returns insert id or dies

function lib_add_row($table, $set, $no_die = '')
{
    global $basic_database_connection;
    lib_warn("dont call lib.inc's lib_add_row (untested, 450)");
# E.g., $set = "(short,long_,log10,sign,id,modified) VALUES (...)"
  # or $set = "SET short='23', long='2333', ..."
  # Form the $query, do the work.
    $query = "INSERT $table $set";
    if ($sth = $basic_database_connection->query($query)) {
        if ($sth->rowCount() == 0) {
            if (!empty($no_die)) {
                return(0);
            }
            lib_mysql_die("lib_add_row(461) failed? query succeeded, but no rows were effected!", $query);
        }
    } elseif (empty($no_die)) {
        lib_mysql_die("lib_add_row (463) failed", $query);
    } else {
        return(0);
    }
  # done; return the id
    return($basic_database_conection->lastInsertId());
}

// lib_modify_row($table,$set,$where[,$die])
//   "UPDATE $table SET $set WHERE $where".  Returns number of rows
// effected (unless $die is set). It returns 1 if 1 or more
// rows effected and zero otherwise; if die is set, it dies otherwise.

function lib_modify_row($table, $set, $where, $die = '')
{
    global $basic_database_connection;
    lib_warn("dont call lib.inc's lib_modify_row (untested, 476) called by $temp");
# Form the $query, do the work.
    $query = "UPDATE $table SET $set WHERE $where";
    $sth = lib_mysql_query($query, $basic_database_connection, "lib_modify_row(481) failed", $query);
    if (!empty($die)) {
        if ($sth->rowCount() == 0) {
            lib_mysql_die("lib_modify_row(484) failed? query succeeded, but no rows were effected!", $query);
        } else {
            return(1);
        }
    } else {
        return($sth->rowCount());
    }
}

// lib_get_enum_strings ($column_name,$tablename,$db_name)
//         Returns a comma delimited string of possible values
//

function lib_get_enum_strings($ColumnName, $TableName, $db_name)
{
    global $db;
    $query = "SHOW COLUMNS FROM $TableName FROM $db_name LIKE '$ColumnName'";
    $sth = lib_mysql_query($query, $db, 'lib_get_enum_strings (495):: Failed to describe table ' . $TableName);
    $row = $sth->fetch(PDO::FETCH_NUM) or
    lib_mysql_die('lib_get_enum_strings (497):: Failed to read enum data from database', $query);
    preg_match("/(enum|set)\('(.*?)'\)/", $row[1], $match);
    return preg_replace("/','/", "\0", $match[2]);
}


//        HTML Producing Functions

// Create a hidden field for a form

function lib_hidden_field($name, $value)
{
    return sprintf(
        "<input type=\"hidden\" name=\"%s\" value=\"%s\">\n",
        $name,
        $value
    );
}

// Create a radio button with the given name and value.  If select (third
// parameter) is present and equals the value, then this button is checked.

function lib_radio_button($name, $value, $select = null)
{
    $select = ($select == $value ? ' checked' : '');
    return ("<input type=radio name=\"$name\" value=\"$value\"$select>");
}

// Take a string and return a short form for the web (curios.text,
// numbers.long_).   Note that it quotes the HTML so as not to
// screw up an index table...

function lib_html_limit($string, $max_length = 40)
{
  # Print the value or a blank (if it is null)
    if (isset($string) and !empty($string)) {
        if (strlen($string) <= $max_length) {
            return(htmlspecialchars($string));
        } else {
            return(htmlspecialchars(substr($string, 0, $max_length - 4))
              . " <font color=red size=\"+2\">...</font>");
        }
    } else {
        return('&nbsp;');
    }
}

// Page heading, title

function lib_html_head($title = 'no title')
{
    return("<HTML>\n<HEAD>\n  <TITLE>$title</TITLE>\n<HEAD>\n<BODY>");
}

// End page

function lib_html_end($string = '')
{
    return("$string\n</BODY>\n</HTML");
}

// Returns <tr bgcolor=... $string> where the bgcolor alternates

function lib_tr($string = '')
{
    global $bgcolor, $ltcolor, $mdbltcolor;
    static $bg;
# wanted static $bg = $bgcolorl but PHP will not allow this, so
  # let's use an if to set the color.
#  ($bg == $bgcolor) ? $bg = $ltcolor : $bg = $bgcolor;
#  return("<tr bgcolor=\"$bg\" $string>");

    ($bg == 'white') ? $bg = $mdbltcolor : $bg = 'white';
    return("<tr class=\"$bg\" $string>");
}
