<?php

# $xx_TableName must be made into a global before these routines are used

function my_get($item)
{
  // Expects a name for a variable (here called item) like 'ind_visible'.
  // This routine will register a version in _SESSION with 'ind' replaced by $TableName
  // (to keep settings in one index from bothering another).
  //    If this item has a value from a form input, it will use it to set
  // the session version; if not, the session's version (if any) will be used
  // to set it.
  //    Returns value if any.

    $TableName = $GLOBALS['xx_TableName'] or lib_die('<P>Fatal Error: xx_TableName not defined');
    is_string($item) or lib_die("<P>Fatal Error: my_get called with non-string \"$item\".\n");

    preg_match("/ind_(\w+)/", $item, $matches);
    $root = $matches[1];
    if (isset($_REQUEST[$item])) {
        $_SESSION["${TableName}_$root"] = $_REQUEST[$item];
    } elseif (isset($_SESSION["${TableName}_$root"])) {
    } else {
        $_SESSION["${TableName}_$root"] = null;
    }
    return $_SESSION["${TableName}_$root"];
}


function display_index()
{
    global $db; # $db is the database connection from index.php

    $TableName = $GLOBALS['xx_TableName'] or lib_die('Fatal Error: xx_TableName not defined');

  # Start with "Header" text, comes before the search table...
    $temp = LookupMeta($TableName, 'Header');
    if (empty($temp)) {
        $temp = "Define 'meta_table_defaults' -> 'Header'
    in meta_table to replace this.<P>";
    }
    echo "$temp<P>\n";

  ##############################################################################
  #  Set up a next/previous button
  ##############################################################################

  # We start at $ind_start, showing $ind_number rows.  Previous/next buttons set
  # $ind_offset to 'previous' or 'next'--so adjust for these.  Otherwise, if
  # they pushed search; reset $ind_start.  We need $ind_old_number because if
  # we last showed 10, and they set number to 100 then push next, we need to
  # move up just 10, then show 100.

    $ind_start      = my_get('ind_start');
    $ind_offset     = my_get('ind_offset');
    $ind_number     = my_get('ind_number');
    $ind_old_number = my_get('ind_old_number');

  # Are the variables defined?  If not, use the default.
    $temp = LookupMeta($TableName, 'IndexSize', 15);    # First get default size
    (is_numeric($ind_number) and $ind_number > 0)
    or $ind_number = $temp;
    (is_numeric($ind_old_number) and $ind_old_number > 0)
    or $ind_old_number = $ind_number;
    (is_numeric($ind_start) and $ind_start >= 0) or $ind_start = 0;

  # Did they push a next, previous or search button (named ind_offset) ?
    if ($ind_offset == 'next') {
        $ind_start += $ind_old_number;
    }
    if ($ind_offset == 'previous') {
        $ind_start = max($ind_start - $ind_number, 0);
    }
    if ($ind_offset == 'search') {
        $ind_start = 0;
    }

  # Now the "new" number is the old number for next call!
    $ind_old_number = $ind_number;

  # The query for the database will be built using these parts/variables

    $select = LookupMeta($TableName, 'IndexSelect', '*');
    $where  = LookupMeta($TableName, 'IndexWhere', '');
    $from   = LookupMeta($TableName, 'IndexFrom', $TableName); # which tables
        # (could be plural!)
    $order  = LookupMeta($TableName, 'IndexOrder', '');
    $limit  = "$ind_start, $ind_number";  #  Don't change this one!
    $dbName = basic_DatabaseName();

  ##############################################################################
  #  Establish the $where while building the navigator bar (table) for browser
  ##############################################################################

  # If there are fields (columns) named 'modified', 'log10' and 'number_id'
  # then we will add a date, digits or number search respectively.
    $AddDateSearch = 0;
    $AddDigitsSearch = 0;
    $AddNumberSearch = 0;
  # Get meta_row info about the row

    try {
        $sth = $db->prepare("DESCRIBE $TableName");
#    $sth->bindParam(':TableName',$TableName);
        $sth->execute();
    } catch (PDOException $ex) {
        lib_mysql_die("index.inc failed to list the fields (97), does the table
         '$TableName' exist in the database '$dbName'?", $ex);
    }

    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
     # print "<li>"; print_r($row);
     # This is an enum field if the Type includes the word enum
        $column = $row['Field'];
        if (preg_match("/\benum\b/", $row['Type'])) {
            preg_match("/(enum|set)\('(.*?)'\)/", $row['Type'], $match);
            $enum_variables[$column] =  preg_replace("/','/", "\0", $match[2]);
        }
        if ($column == 'modified') {
            $AddDateSearch = 1;
        }
        if ($column == 'log10') {
            $AddDigitsSearch = 1;
        }
        if ($column == 'number_id') {
            $AddNumberSearch = 1;
        }
    }

  # Now let's begin the table (my usual double table for looks, surrounded
  # by a third to contain side text next to the index table)
    $NavigatorTable = "\n<table align=center><tr><td valign=middle>
	<table style=\"border:$GLOBALS[drkcolor] double 4px;\"
        cellSpacing=0 cellPadding=0 border=1>
        <tr><td><table cellpadding=3>\n";

  # Add the generic row "add new" menu items
    $NavigatorTable .= "<tr bgcolor=\"$GLOBALS[medcolor]\"><td>" .
    admin_AddNewItem() . "</td></tr><tr>\n\n";

  # All of the options should be in one form to protect the values
  # of the variables.  Also $ind_start, $ind_old_number must be
  # hidden there for next/previous buttons.

    $NavigatorTable .= '<form method=post action="' . $_SERVER['PHP_SELF'] . '?' . SID . "\">\n" .
#   lib_hidden_field('PHPSESSID',$_SESSION['PHPSESSID']).
    lib_hidden_field('xx_TableName', $TableName) .
    lib_hidden_field('ind_start', $ind_start) .
    lib_hidden_field('ind_old_number', $ind_old_number);


  # What about a search to limit the displayed entries using 'LIKE'

    if (1) {  # Why would we leave this out?  Remove the if?
        $ind_search1 = my_get('ind_search1');
        $ind_search2 = my_get('ind_search2');

        $NavigatorTable .= "<tr><td>Seek
	<input type=text name=ind_search1 value=\"" .
        (!empty($ind_search1) ? $ind_search1 : '') . "\" size=35> in
	<select name=ind_search2>";
      # Get list of fields that might be searched
        $temp = LookupMeta($TableName, 'SearchFields');
        if (empty($temp)) {
            $temp = lib_list_fields(basic_DatabaseName(), $TableName);
        }
        $columns = explode(", ", $temp);
        for ($i = 0; $i < count($columns); $i++) {
            $col = $columns[$i];
            $NavigatorTable .= "<option" . ($ind_search2 == $col ? ' selected' : '') .
            ">$col</option>\n";
        }
        $NavigatorTable .= "</select>";

        if ($AddDateSearch != 1) { # If 1, then a search button will be added later
            $NavigatorTable .= " <input type=submit name=ind_offset value=search>";
            $SearchButtonAdded = 1;
        }
        $NavigatorTable .= "</td></tr>";

      # Now build the associated WHERE restriction to the query

        if (!empty($ind_search1)) {
            $where .= (empty($where) ? '' : ' AND ') . "$TableName.$ind_search2 LIKE " .
            $db->quote($ind_search1);
        }
    }

  ####################################################################
  # Now the rows of the navigator table specific to this $TableName
  ####################################################################

  # Make sure that you add these as complete rows to $NavigatorTable
  # and at the same time add the necessary query info to $Where

  # Now build the WHERE restriction for the query

  # For many uses, entries may be visible or not.  Email address hidden or
  # not.  Here we add search fields for those enum cases.

##  while (list($key, $val) = @each($enum_variables)) {
    foreach ($enum_variables as $key => $val) {
      # The string '_either_' is a value which indicates not to restrict the search
      # using that field.  Used just in this while loop... Hopefully will never be an
      # enum value.

      # If no restriction specified, use '_either_' (any).
        $field = my_get('ind_' . $key);  # Load the value (if any) into $_SESSION
        if (empty($field)) {
            $field = '_either_';
        }

      # Now add a set of radio buttons for each enum column
        $NavigatorTable .= "<tr><td> &nbsp; " . ucwords($key) . "? \n";
        $enum_list = explode("\0", $val);
        for ($i = 0; $i < count($enum_list); $i++) {
            $choice = $enum_list[$i];
            $NavigatorTable .= "\t" . lib_radio_button('ind_' . $key, $choice, $field) .
            " $choice, \n";
        }
      # and one for the '_either_' (any) choice:
        $NavigatorTable .= "\t" . lib_radio_button('ind_' . $key, '_either_', $field) .
        ' or ' . (count($enum_list) > 2 ? 'any' : 'either') . ".</td></tr>\n";

      # Now adjust the where part of the query string
        if (!empty($field) and $field != '_either_') {
            $where = (empty($where) ? '' : "$where AND ") . "$key=\"" . $field . '"';
        }
    }

  # For numbers we can jump to those with a specified number of digits
  # Must allow this to be empty

    if ($AddDigitsSearch == 1) { # Set to 1 above if 'log10' field exists in table
        $ind_num_digits = my_get('ind_num_digits');

        $NavigatorTable .= "<tr><td>
	Just those with <input type=text name=ind_num_digits
	value=\"$ind_num_digits\" size=5> (or more) digits";
        if ($AddDateSearch != 1 and !$SearchButtonAdded) {  # Don't need two search buttons
            $NavigatorTable .= " <input type=submit name=ind_offset value=search>";
        }
        $NavigatorTable .= "</td></tr>";

      # Now build the WHERE restriction for the query

        if (isset($ind_num_digits) and is_numeric($ind_num_digits)) {
            $ind_num_digits--;
            $where = (empty($where) ? '' : "$where AND ") . "log10 >= $ind_num_digits";
        }
    }

  # For curios, if one of $ind_num_id, $ind_num_short is defined
  # we will just list the curios for that number.  First add a navigator
  # row to allow this preference...

    if ($AddNumberSearch == 1) {
        $ind_num_id = my_get('ind_num_id');
        $ind_num_short = my_get('ind_num_short');

        $NavigatorTable .= "<tr><td>Just those for number
      short = <input type=text name=ind_num_short value=" .
        (is_numeric($ind_num_short) ? $ind_num_short : '""') . " size=25>
      id = <input type=text name=ind_num_id value=" .
        (is_numeric($ind_num_id) ? $ind_num_id : '""') . " size=5>
      </td></tr>";

      # Now build the WHERE restriction for the query

        if (is_numeric($ind_num_id)) {
            $where = "numbers.id = $ind_num_id AND $where";
        } elseif (!empty($ind_num_short)) {
            if ($ind_num_id = basic_number_id_via_short($ind_num_short)) {
                $where = (empty($where) ? '' : "$where AND ") .
                "numbers.id = $ind_num_id";
            } else {
                lib_warn("No numbers with that short form in database");
            }
        }
    }

  # There could also be a "in the last ?? days" type restriction
  # Lets first add the appropriate navigator table row

    if ($AddDateSearch == 1) { # Set to 1 above if 'modified' field exists in table
        $ind_last1  = my_get('ind_last1');
        $ind_last2  = my_get('ind_last2');
        $NavigatorTable .= "<tr><td>Modified in the last
	<input type=text name=ind_last1 value=" .
        (is_numeric($ind_last1) ? $ind_last1 : '""') . " size=5>
	<select name=ind_last2>
	<option" . ($ind_last2 == 'minutes' ? ' selected' : '') . ">minutes</option>
	<option" . ($ind_last2 == 'hours' ? ' selected' : '') . ">hours</option>
	<option" . ($ind_last2 == 'days' ? ' selected' : '') . ">days</option>
	<option" . ($ind_last2 == 'months' ? ' selected' : '') . ">months</option>
	</select>
	<input type=submit name=ind_offset value=search>
	</td></tr>";

      # Now build the associated WHERE restriction to the query

        if (is_numeric($ind_last1)) {
            $cutoff = $ind_last1; # Will be the number of seconds
            if ($ind_last2 == 'minutes') {
                $cutoff *= 60;
            } elseif ($ind_last2 == 'hours') {
                $cutoff *= 3600;
            } elseif ($ind_last2 == 'days') {
                $cutoff *= 86400;
            } elseif ($ind_last2 == 'months') {
                $cutoff *= 2592000;
            } else {
                lib_die("ind_last2 has illegal value = $ind_last2");
            }
            $cutoff = date("YmdHis", time() - $cutoff);
            $where .= (empty($where) ? '' : ' AND ') . "$TableName.modified+0 >= $cutoff";
        }
    }

  # Count the number of rows in the total query (using our $TableName, $where)
  # This must come after $where is totally built, but before the next/previous
  # buttons are added.
    $total = lib_rows_in_table($from, $db, $where);

    $NavigatorTable .= "\n<tr bgcolor=\"$GLOBALS[medcolor]\"><td>";
    if ($total == 0) {
        $NavigatorTable .= "Sorry, no such rows are found!&nbsp; \n";
    } elseif ($ind_start == 0 and $ind_number >= $total) {
        $NavigatorTable .= "Showing all $total rows.&nbsp; \n";
    } else {
        $NavigatorTable .= "Showing rows " . ($ind_start + 1) . " to " .
        min($ind_start + $ind_number, $total) .
        " of $total rows. &nbsp; \n";
    }

    $ShowTheNumber = 0;
    if ($ind_start > 0) {
        $NavigatorTable .= "<input type=submit name=ind_offset value=previous> &nbsp; \n";
        $ShowTheNumber = 1;
    }
    if ($total > $ind_start + $ind_number) {
        $NavigatorTable .= "<input type=submit name=ind_offset value=next> &nbsp; \n";
        $ShowTheNumber = 1;
    }
    if ($ShowTheNumber) {
        $NavigatorTable .= " <input type=text name=ind_number value=\"" .
        $ind_number . "\" size=5></td></tr>\n";
    }

  # That's it.  Where should be built, complete/print the navigator table,
  # add the help side bar...

    $NavigatorTable .= "\n  </table></td></tr>\n</table></td></form>\n";
    $temp = LookupMeta($TableName, 'SearchHelp', "For 'seek' you may use the
	wildcards % (match anything) and _ (to match any single character).");
    $NavigatorTable .= "<td width=150 valign=middle>$temp</td></tr></table>\n";
    echo $NavigatorTable;


  ##############################################################################
  #  Now conduct the query
  ##############################################################################

  # Now set up the query

    $where and $where = "WHERE $where"; # $where could be empty
    $query = "SELECT $select FROM $from $where";
    $order = trim($order);
    if (!empty($order)) {
        $query .= " ORDER BY $order";
    }
    if (!empty($limit)) {
        $query .= " LIMIT $limit";
    }
    $sth = lib_mysql_query($query, $db)
    or lib_mysql_die("Failed to complete index query", $query);

  ##############################################################################
  # Build a second table and fill with the results
  ##############################################################################

  # The meta_table has the IndexHeading, what the first row should look like
    $temp = LookupMeta($TableName, 'IndexHeading');
    if (empty($temp)) { # Default is to just list them all
        $temp = lib_list_fields(basic_DatabaseName(), $TableName);
        $temp = 'action' . (empty($temp) ? '' : ", $temp");
        $temp = "<th>" . preg_replace("/,\s*/", "</th><th>", $temp) . "</th>";
    }
    echo "\n<P><TABLE style=\"border:$GLOBALS[drkcolor] double 4px;\"
        cellSpacing=0 cellPadding=0 border=1 align=center>
        <tr><td><table border=0 cellspacing=4 cellpadding=2>
	<tr bgcolor=\"$GLOBALS[medcolor]\">$temp</tr>\n";

  # Spit out the rows (eval'ing here, not in LookUpMeta, so $myrow and $TableName
  # will be local.  So far I have had trouble making them global and eval'ing.

    global $eval_string;  # Make sure lib_ErrorHandler can see $eval_string
    $eval_string = LookupMeta($TableName, 'IndexRow', null, 'Do not evaluate!');

  # The eval_string should not be empty, if it is, lets list all
  # Chris: You could also use this as a fall back if the one in the database
  # fails to evaluate!

    if (empty($eval_string)) { # Default is to just list them all
        $eval_string = lib_list_fields(basic_DatabaseName(), $TableName);
        $eval_string = 'return ("<td>
      <a href=$_SERVER[PHP_SELF]?xx_TableName=$TableName&xx_edit=$myrow[id]>edit</a>
      <a href=$_SERVER[PHP_SELF]?xx_TableName=$TableName&xx_view=$myrow[id]>view</a>
      <a href=$_SERVER[PHP_SELF]?xx_TableName=$TableName&xx_delete=$myrow[id]>delete</a>
      </td><td>$myrow[' .
        preg_replace("/,\s*/", ']</td><td>$myrow[', $eval_string) . ']</td>");';
    }

    while ($myrow = $sth->fetch(PDO::FETCH_ASSOC)) {
        set_error_handler("lib_ErrorHandler"); # Set handler to mine from library
        $temp = eval($eval_string);
        restore_error_handler(); # Okay, restore the regular handler
        echo lib_tr() . "\n" . $temp . "</tr>\n";
    }

  # close up and be done
    echo "</table>\n</td></tr></table>\n";
}

# Data about each table (hopefully) resides in the database table
# meta_table.  GetMetaTableData ($TableName) gets that meta  data
# and dumps it into the global $meta_table (defaults meta_table_defaults
# loaded first) and sets $meta_type to 'string' or 'eval' (the
# latter are eval'd and the result returned)

function GetMetaTableData($TableName)
{
    global $meta_table, $meta_type, $db;
    static $TableLoaded;

    if ($TableLoaded == $TableName) {
        return(1); # Already loaded!
    }

    if (empty($TableName)) {
        lib_die('$TableName must be specified in a call to GetMetaTableData');
    }

  # first load the default meta_table data (applies to all tables)
    $stmt = $db->query("SELECT * FROM meta_table WHERE table_='meta_table_defaults'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $meta_table[$row['key_']] = $row['value'];
        $meta_type[$row['key_']] = $row['type'];
    }

  # now the table specific meta_row data
    $query = 'SELECT * FROM meta_table WHERE table_=?';   # '$TableName'";
    try {
        $sth = $db->prepare($query);
        $sth->bindParam(1, $TableName);
        $sth->execute();
    } catch (PDOException $ex) {
        lib_mysql_die('Error in lib_rows_in_table', $query);
    }

    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      # $key = $row['key_']; echo "<dt>$key defined as <dd>".$row['value'];
        $meta_table[$row['key_']] = $row['value'];
        $meta_type[$row['key_']] = $row['type'];
    }

    $TableLoaded = $TableName;
    return 1;
}

// Is there an entry for $TableName, $key in meta_table ?  If not, try
// meta_table_defaults, $key; and if not, use $default, and if not defined
// complain and return ''.
// Do not evaluate an evaluate string if !empty($no_eval)

function LookupMeta($TableName, $key, $default = null, $no_eval = null)
{
    global $meta_table, $meta_type;
    global $eval_string;  # Make sure lib_ErrorHandler can see this for error messages

    if (empty($key)) {
        lib_die("You must specify a key in LookupMeta(TableName,Key)");
    }
    GetMetaTableData($TableName);

    if (isset($meta_table[$key])) {
        $eval_string = $meta_table[$key];
        if ($meta_type[$key] == 'eval' and empty($no_eval)) {
          # Tricky case--code to evaluate; may want to bypass to make sure variables
          # are local...
          # error_reporting(E_ALL);
            set_error_handler("lib_ErrorHandler"); # Set handler to mine from library
            $result = eval($eval_string);
            restore_error_handler(); # Okay, restore the regular handler
            return($result);
        } else { # Easy case, a string
            return($eval_string);
        }
    }

    if (isset($default)) {
        return($default);
    }

  # This next statement is true here because defaults already loaded
  # into the meta_* arrays by GetMetaTableDate().
    echo("<font color=red><b>There is no default \"$key\" for table
	\"$TableName\", returning an empty string. &nbsp; (These should
	be defined by a root user in the table \"meta_table\".)</b></font>");
    return('');
}
