<?php

# Expects id to be a unique auto_increment integer.
# Expects modified/created to be timestamps for when the entry was modified/created.
# Note that an id of 0 is a new entry (and will be replaced by a valid id).

// We do not necessarily know the names of the variables returned by the
// edit form, but they will all be in HTTP_POST_VARS = _POST.  Lets make them global.

## while (list($key, $val) = @each($_POST)) {
foreach ($_POST as $key => $val) {
   // print "<li>\$GLOBALS[$key] = $val";
    $GLOBALS[$key] = $val;
}

// Exportable functions in this module
//
//  generic_put_row_by_id($parameters)
//              returns 1 for success, 0 for error (prints edit table), -1
//              for no erros, but table not updated
//  generic_show_edit_table($parameters,$error)
//
//  Parameters that might be passed include
//
//  database_name       [required] what database?
//  table_name      [required] which table?
//  entry_id        id number of entry to edit (Note generic_put_row_by_id uses
//              the id field from the generic_show_edit_table form and resets this entry)
//
//  editors_id      who is editing (id for entry in person table)
//  editors_username    who is editing (username entry from person table)
//              (Note if editor's ID is set, it is used by the function role, otherwise
//              the username is used to look the id up by that routine)
//
//  put_row_silent      tells generic_put_row not to print success/failure messages
//  extra_for_form      extra info to stick in the generic_show_edit form (so the
//              calling page can pass info to itself)

// Other functions
//
//  GetMetaRowData($TableName);
//  my_get_from_global ($name,$type,$parameters)
//  my_valid_URL($url) # To be used in meta_row.test  (Returns '' if URL okay, else error message)

function my_valid_URL($URL)
{
    if (!preg_match("/^\s*(https?|ftp):\/\/(.*?)\s*$/", $URL, $matches)) {
        return("URL's must start with 'http://' or 'https://'");
    } else {
        $URL = $matches[2];
        $allowed = '\w/#~:.?+=&%@!\-';
        if (preg_match("{^[$allowed]+(.?).*$}", $URL, $temp)) {
            if (!empty($temp[1])) {
                return("Invalid character in URL: '$temp[1]'");
            }
            return('');  # Success!!
        } else {
            return("Unknown Error in URL: $URL");
        }
    }
}

$MaxMetaRowOrder = 5;  //  The fields can be assigned orders to be evaluated
  // from 1 to $MaxMetaRowOrder.  Do not change without adjusting dtabases!

// generic_allow($role,$limit,$info) takes the limit (usually from the meta_table for the row)
// and decides if it can be edited/viewed in the given $role.  $info should be row name... for
// error messages

function generic_allow($role, $limit, $info = '')
{
    if (empty($role)) {
        print "error in generic_allow, $info \$role not defined, using 'all'<br>";
        $role = 'all';
    } elseif (!preg_match('/^(all|owner|admin|root|none)$/', $role)) {
        print "error in generic_allow, $info \$role not legal ($role)<br>";
        $role = 'all';
    }
    if (empty($limit)) {
        print "error in generic_allow, $info \$limit not defined, using 'root'.
	(This means that the root user did not define a meta_row entry for this
	field which specifies who can edit.)<br>";
        $limit = 'root';
    } elseif (!preg_match('/^(all|owner|admin|root|none)$/', $limit)) {
        print "error in generic_allow, $info \$limit not legal ($limit)<br>";
        $limit = 'none';
    }
    $value['all'] = 5;
    $value['owner'] = 4;
    $value['admin'] = 3;
    $value['root'] = 2;
    $value['none'] = 1;
    if ($value[$role] <= $value[$limit]) {
        return(true);
    }
    return false;
}

// This array is used to decide how long some input fields should be (in characters)
$MySQL_allowed_lengths = [
  'tinyint' => 4,
  'smallint' => 6,
  'mediumint' => 8,
  'int' => 11,
  'bigint' => 20,
  'double' => 20,
  'float' => 20,
  'datetime' => 19,
  'timestamp' => 19,
  'year' => 4,
  'time' => 8,
  'date' => 10,
];

$MySQL_allowed_max = [          // currently unused
    'tinyint' => 255,           // ints max for unsigned
    'smallint' => 65535,
    'mediumint' => 16777215,
    'int' => 4294967295,
    'bigint' => 18446744073709551615,
];


function generic_show_edit_table($parameters, $error = '')
{
  // Currently this code block is duplicated in
  // Which database and table?

  # foreach($parameters as $parameter => $item) print "<li> $parameter = $item";

    if (!isset($parameters['database_name'])) {  # Defaults set for backward compatibility
        $parameters['database_name'] = basic_DatabaseName();
    }
    if (!isset($parameters['table_name'])) {     # Defaults set for backward compatibility
        $parameters['table_name'] = $GLOBALS['xx_TableName'] or die('xx_TableName is not defined! (1)');
    }
    if (!isset($parameters['entry_id'])) {        # Defaults set for backward compatibility
        $parameters['entry_id'] = $GLOBALS['xx_edit'];
    }
  # No id?  Get it from the username via role().  Let's get a default last name
    if (!isset($parameters['editors_id']) and !isset($parameters['editors_username'])) {
        $parameters['editors_username'] = $GLOBALS['_SERVER']['PHP_AUTH_USER'];
    }

  // print "xxxxxxxxxxxxxxxxx $_SERVER[PHP_AUTH_USER] $_SERVER[PHP_AUTH_PW]\n";

  # Are they authorized?  With what limits?  Code duplicated in generic_put_row_by_id.
    # What is their role? ('all','owner','admin','root','none')
    include_once('security.inc');
    $generic_editors_role = role($parameters); # Sets/verifies $parameters['editors_id'] or dies
    if (empty($generic_editors_role)) {
        $generic_editors_role = 'all';  # 'all' is most restricted
    }
    $temp = (isset($parameters['editors_username']) ? " ($parameters[editors_username])" : '');
    // if (empty($generic_put_row_silent))
    //   echo "[Editor's id: $parameters[editors_id]$temp, role: $generic_editors_role]";

  # First, is there an error? (say from failed attempt to submit?)

    if (!empty($error)) {
      # Package error for inclusion into table caption
        $error = " &nbsp; &nbsp; <font color=red><b>Error: $error</b></font>";
    }

  # Table caption and hidden fields (to tell us what to do next!)

    echo "\n<form method=post action=\"" . $_SERVER['PHP_SELF'] . "\">\n" .
    # Using PHP_SELF necessary to remove extra path info
    lib_hidden_field('xx_edit', $parameters['entry_id']) .  # With edit and put set, will
    lib_hidden_field('xx_action', 'put') . # attempt to put this record
    lib_hidden_field('xx_TableName', $parameters['table_name']) . # need to know which table!
    lib_hidden_field('xx_person_id', $parameters['editors_id']) . # to put need to know who is doing it
    (isset($parameters['extra_for_form']) ? $parameters['extra_for_form'] : '') .
        # These fields are used by the php routines to know what to do

    "\n<table border=0 cellspacing=3 cellpadding=3>\n" .
    "<caption title=\"TableName $parameters[database_name]; DatabaseName $parameters[database_name]; " .
    "id $parameters[entry_id]\">\n  <font size=\"+2\">";

  # Were we given a page title to use?
    if (isset($parameters['page_title'])) {
        $temp = $parameters['page_title'];
    } else {
        $temp = "Edit $parameters[database_name].$parameters[table_name], entry id=$parameters[entry_id]";
    }

    echo  "$temp</font>$error\n</caption>\n" .
    "<tr bgcolor=\"$GLOBALS[medcolor]\"><th>column</th><th>value</th>\n" .
    "  <th title=\"From the database table meta_row\">information</th></tr>";

  # There may also be info in the database in the table 'meta_row' *if* the
  # database manager put it there.
  # info=text to put by field in edit table; edit=who can edit (all, admin or no)

    global $meta_row_info, $meta_row_edit, $meta_row_order, $meta_row_view;
    GetMetaRowData($parameters['table_name']);
    global $MaxMetaRowOrder;

  # Connect to the dataase then get the column info for this table

    $db = basic_db_admin_connect();
    try {
        $sth = $db->prepare("DESCRIBE $parameters[table_name]");  # Can't seem to pull this out and bind
        $sth->execute();
        $table_meta = $sth->fetchall(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        lib_mysql_die("edit.inc: (457) generic_show_edit_table error", $ex);
    }
  # print "<li>edmin.inc 281 \$table_meta: <pre>"; print_r($table_meta); print "</pre>";

  # Now get the row to display

    if ($parameters['entry_id'] == '0') {
      # 0 indicate new item
        $row = 0;
    } else {
        try {
            $sth = $db->query("SELECT * FROM $parameters[table_name] WHERE id = '$parameters[entry_id]' LIMIT 1");
            $sth->execute();
        } catch (PDOException $ex) {
            lib_mysql_die("edit.inc: (452) generic_show_edit_table error", $ex);
        }
        $count = $sth->rowCount();
        if ($count == 0) {
            lib_mysql_die("No row corresponding to the given id ($parameters[entry_id])", $query);
            return(0);
        } else {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
        # print "<li> row"; print_r($row); print "<br><br>";
        }
        $sth->closeCursor();
    }

  # make sure all have an order so all will be processed.

    for ($i = 0; $i < count($row); $i++) {
        $name = $table_meta[$i]['Field'];
        if (!isset($meta_row_order[$name]) or !is_numeric($meta_row_order[$name])) {
            $meta_row_order[$name] = $MaxMetaRowOrder;
        }
    }

  # We will display the row in a HTML table, three columns wide.  This has a dumb
  # outer loop which loops through the pssible meta orders and we print only those
  # columns which match that order each pass

    $count_editable = 0;  # There may be no editable fields.
    for ($j = 1; $j <= $MaxMetaRowOrder; $j++) {
      # use meta_row_order to do first things first

  ####    for ($i = 0; $i < count($row); $i++) {
        for ($i = 0; $i < count($table_meta); $i++) {
          # get column meta data
            $meta = $table_meta[$i];

          # MySQL's meta_row info about field
            $name = $meta['Field'];
            if ($meta_row_order[$name] != $j) {
                continue;
            }
            $type = $meta['Type'];

          # Skip the fields that can not be viewed (do not allow id to be omitted!)
            if (
                !generic_allow($generic_editors_role, isset($meta_row_view[$name]) ?
                $meta_row_view[$name] : '', "row $name") and $name != 'id'
            ) {
                continue;
            }

          # This is an enum or set field if the type starts with enum or set
            $enum = 0;
            $set  = 0;
            if (preg_match("/\benum\b/", $type)) {
                preg_match("/(enum|set)\('(.*?)'\)/", $type, $match);
                $enum = preg_replace("/','/", "\0", $match[2]);
            } elseif (preg_match("/\bset\b/", $type)) {
                preg_match("/(enum|set)\('(.*?)'\)/", $type, $match);
                $set = preg_replace("/','/", "\0", $match[2]);
            }

          # actual data from row (or default if new item)
            if (!empty($error)) {
              # Returned from previous edit attempt.  Values should be set in global
              # from returned form
                $data = my_get_from_global($name, $type, $parameters);
            } else {
              # Ah, first timer eh?
                if (isset($row[$name])) {
                    $data = htmlspecialchars($row[$name]);  # Will this cause problems?
                } else {
                    $data = my_get_from_global($name, $type, $parameters);
                }
            }

          # Remember id = 0 indicates new item, so $data will be undefined
            if ($name == 'id' and $parameters['entry_id'] == 0) {
                $data = 0;
            }

        # first column of the edit table to be displayed is the field name
            echo lib_tr() . "\n  <td align=right title=\"field type $type; evaluation order $meta_row_order[$name]\">$name</td>\n";

        # second column of the edit table is the data in a form element

        # Now the data which may just be informational (if here they can view, but perhaps not edit)
        # or it will be a form element
            $editable = generic_allow($generic_editors_role, isset($meta_row_edit[$name]) ? $meta_row_edit[$name] : '', "row $name");
            if (!$editable) {
                echo "  <td title=\"This field is not editable.\">";
                echo $data;
            } else {
                echo "  <td title=\"This field is editable.\">";
                $count_editable++;

              # the different types of data must be treated separately.

                if ($name == 'id') {
            # Never editable, but still id must be passed
                    echo lib_hidden_field($name, $data),$data;
                } elseif ($enum) {
                    $enum_list = explode("\0", $enum);
                    for ($i2 = 0; $i2 < count($enum_list); $i2++) {
                        if ($data) { # need to check this item if it matched $data
                            $checked = ($enum_list[$i2] == $data ? 'checked' : '');
                        } elseif (!$meta['Null'] == 'YES') {
            # if no data (yet), and there must be some, default is the first (SQL standard for ENUM)
                            $checked = ($i2 == 0 ? 'checked' : '');
                        } else {
                            $checked = '';
                        }
                        echo "\n\t<input type=radio name=\"$name\" value=\"" . $enum_list[$i2] .
                        "\" $checked>" . $enum_list[$i2];
                    }
                } elseif ($set) {
              # Note use of ':::' to keep one checkbox from wiping out another
                    $enum_list = explode("\0", $set);
                    for ($i2 = 0; $i2 < count($enum_list); $i2++) {
                        if ($data) { # need to check this item if it matched $data
                            $checked = (preg_match("/$enum_list[$i2]/", $data) ? 'checked' : '');
                        } elseif (!$meta['Null'] == 'YES') {
                # if no data (yet), and there must be some, default is the first (SQL standard for ENUM)
                            $checked = ($i2 == 0 ? 'checked' : '');
                        } else {
                            $checked = '';
                        }
                        echo "\n\t<input type=checkbox name=\"$name:::$i2\" value=\"" . $enum_list[$i2] .
                        "\" $checked>" . $enum_list[$i2];
                    }
                } elseif (preg_match('/^(|var)(char|binary)\((\d*)\)/', $type, $match)) {
                    $length = $match[3];
                    if ($length > 512) {
                                echo "<textarea rows=7 cols=120 name=\"$name\">$data</textarea>";
                    } elseif ($length > 128) {
                        echo "<textarea rows=2 cols=120 name=\"$name\">$data</textarea>";
                    } else {
                        echo "<input type=text maxlength=$length size=" . min($length, 128) .
                        " name=\"$name\" value=\"$data\">";
                    }
                } elseif (preg_match('/^(|tiny|medium|long)(blob|text)/', $type, $match)) {
                    $length = 'undefined';
                    if ($match[1] == 'tiny') {
                            echo "<textarea rows=2 cols=120 name=\"$name\">$data</textarea>";
                    } else {
                                    echo "<textarea rows=7 cols=120 name=\"$name\">$data</textarea>";
                    }
                } elseif (preg_match('/^(double|datetime|tinyint|int|smallint|mediumint|bigint|timestamp)/', $type, $match)) {
                    $length = $GLOBALS['MySQL_allowed_lengths'][$match[1]];
                    echo "<input type=text maxlength=$length size=$length name=\"$name\" value=\"$data\">";
                } else {
                # Fields I do not know how to handle!
                    echo lib_hidden_field($name, $data),$data,"<P><font color=red>This
	    display edit table routine does not know how to handle field
	    type $type; .</font>";
                }
                echo "</td>\n";
            }

        # third column of the edit table is info, if any from the meta_row table in the database
            $temp = (isset($meta_row_info[$name]) ? $meta_row_info[$name] : '&nbsp;');
            echo '  <td title="edit ' . (isset($meta_row_edit[$name]) ? $meta_row_edit[$name] : '') .
            ", view " . (isset($meta_row_view[$name]) ? $meta_row_view[$name] : '') . '">' .
            $temp . "</td>\n";

        # End the column
            echo "</tr>\n";
        }
    }

  # Finish by closing the table
    echo "<tr bgcolor=\"$GLOBALS[medcolor]\"><td>&nbsp;</td>\n";
    if ($count_editable > 0) {
        echo "  <td><input type=submit value=\"Submit\" name=\"submit\">
		 &nbsp; <input type=reset name=submit value=\"reset\"></td>\n";
    } else {
        echo "  <td>&nbsp;</td>\n";
    }
    echo "  <td>&nbsp;</td>\n</tr>\n</table>\n</form>\n";
    if ($count_editable == 0) {
        echo "<font color=red size=\"+1\">There are no fields in this table entry that you are able to edit.</font>";
    }
    return(0);  # generic_put_row_by_id() uses this! Must be 0.
}


// Now a routine to get the data from the previous routine via returned form data,
// make sure it is in the right format, then store it & log it & return.

function generic_put_row_by_id($parameters)
{
  # Set this to keep this routine from printing success/failure message.
    $generic_put_row_silent = (isset($parameters['put_row_silent']) ? $parameters['put_row_silent'] : false);
  # print "<li>parameters: ";print_r($parameters);

  # Which database and table?
    if (!isset($parameters['database_name'])) {  # Defaults set for backward compatibility
        $parameters['database_name'] = basic_DatabaseName();
    }
    if (!isset($parameters['table_name'])) {     # Defaults set for backward compatibility
        $parameters['table_name'] = $GLOBALS['xx_TableName'] or die('xx_TableName is not defined! (1)');
    }

### $WHY??? 7/2014  $parameters['entry_id'] = (isset($GLOBALS['id']) ? trim($GLOBALS['id']) : '');

  # No editors_id?  Get it from the username via role().  Let's get a default last name
    if (!isset($parameters['editors_id']) and !isset($parameters['editors_username'])) {
        $parameters['editors_username'] = $GLOBALS['_SERVER']['PHP_AUTH_USER'];
    }

  # Are they authorized?  With what limits?  Code duplicated in generic_put_row_by_id.
    include_once('security.inc');
    $generic_editors_role = role($parameters);
    if (empty($generic_editors_role)) {
        $generic_editors_role = 'all';  # 'all' is most restricted
    }

  # Pause to get the column info

    try {
        $db = basic_db_admin_connect();
        $sth = $db->prepare("DESCRIBE $parameters[table_name]");  # Can't seem to pul$
        $sth->execute();
        $table_meta = $sth->fetchall(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        lib_mysql_die("edit.inc: (157) generic_show_edit_table error", $ex);
    }
  # print "<li> meta: "; print_r($table_meta);

  # make sure all have an order so all will be processed.

    global $MaxMetaRowOrder;
    for ($i = 0; $i < count($table_meta); $i++) {
        $name = $table_meta[$i]['Field'];
        if (!isset($meta_row_order[$name]) or !is_numeric($meta_row_order[$name])) {
            $meta_row_order[$name] = $MaxMetaRowOrder;
        }
    }

  # There should also be info (especially testing routines) in the database
  # table 'meta_row' *if* the database manager put it there.  Let's get it.
  # info=text to put by field in edit table; edit=who can edit (all, admin or no)
  # test = string function to test field before storing.

    global $meta_row_test, $meta_row_order, $meta_row_edit, $meta_row_view;
    GetMetaRowData($parameters['table_name']);

  # Check the rows, one at a time.  We just assume the form fields
  # match those in the database--same names...  Unchangable fields should
  # be there hidden...  Of course id, created and modified are special
  # so are handled as exceptions...

  # make sure all have an order so all will be processed.
    for ($i = 0; $i < count($table_meta); $i++) {
        $name = $table_meta[$i]['Field'];
        if (!is_numeric($meta_row_order[$name])) {
            $meta_row_order[$name] = $MaxMetaRowOrder;
        }
    }

    $count = -1;  # Index for DATA and NAME -- fields actually being updated
        # This will not be all of them even if the editor's role  is 'root'

    $error = '';  # Please let this stay empty!

    for ($j = 1; $j <= $MaxMetaRowOrder; $j++) {
      # use meta_row_order to do first things first

        for ($i = 0; $i < count($table_meta); $i++) {
          # MySQL's meta_row info about field
            $type = $table_meta[$i]['Type'];
            $name = $table_meta[$i]['Field'];
            if ($meta_row_order[$name] != $j) {
                continue;
            }

          # skip the fields that can not be edited.
            if (!generic_allow($generic_editors_role, $meta_row_edit[$name], "row $name") or $name == 'id') {
            # print "<li>$i: Skipping $name ";
                continue;
            }

          # Get data from the form and trim.  Leaving these global prevents
          # the names from clashing with local variable names.
            $data = my_get_from_global($name, $type, $parameters);

          # First, test against code stored in meta_row table (comes first so
          # the code can redefine (or even define) the entry (using other fields))
            $test = $meta_row_test[$name];
          // print "<li>$name: $test";

            if (!empty($test)) {  # ah, there is some testing code...
          # Note we reset the error handler to be more informative,
          # then switch back when done
          # error_reporting(E_ALL); # restore later if set now
                set_error_handler("lib_ErrorHandler");
                global $eval_string;  # Necessary for lib_ErrorHandler to point out
                $eval_string = $test; # the error exactly where it occurs in the string
                if ($local_error = eval($test)) { # Should return nothing if things are fine
                    # This also includes syntax errors--e.g., bad test code!
                    restore_error_handler();
                    # PHP adds slashes magically, MySQL eats them up, so entries from
                    # MySQL have no added slashes, but the error field, from PHP, does?
                    if (!empty($local_error)) {
                        $error .= "(490, eval'ing field \"$name\") $local_error<br>";
                    }
                    # Moved the following so all fields are processed... slashes removed...
                    # return generic_show_edit_table($parameters,"(eval'ing field \"$name\") $local_error");
                } else {
                    restore_error_handler();
                }
            }

    ## help!!      # Is it too long? (Should we do this after we quote?)
    ##      if (strlen($data) > $length)
    ##  generic_show_edit_table($parameters,
    ##      "The field \"$name\" must not be over $length characters long.");

          # Set DATA and NAME for each field (column).  Data may need to be quoted...
            $NAME[++$count] = $name;
          # echo ("<li>($type) $name : $data\n");

          # Special fields: id, created, modified
          # because string id's (e.g., name) must be filled it
            if ($name == 'id' and $parameters['entry_id'] == 0) {
                $DATA[$count] = 'NULL';
            } elseif ($name == 'blob_id' and (!isset($DATA[$count]) or $DATA[$count] == 0)) {
                $DATA[$count] = 'NULL';
            } elseif ($name == 'modified') {
                $DATA[$count] = 'NOW()';
            } elseif ($name == 'created' and empty($data)) {
                $DATA[$count] = 'NOW()';
            } else { # Everything else
              # Now make sure it is not empty if it declared NOT NULL
                if (empty($data) and $name != 'id' and preg_match("/\bnot_null\b/", $type)) {
                    return generic_show_edit_table(
                        $parameters,
                        "The field \"$name\" must not be left empty!"
                    );
                }
              # I am sure there is a better way to do this!
                $DATA[$count] = "'" . lib_MyAddSlashes($data) . "'";
            }

          # print "$name is now ".$DATA[$count]."<br><br>\n";
        }
    }

    if (!empty($error)) {
        return generic_show_edit_table($parameters, $error);
    }

  # Form the right query
    if ($parameters['entry_id'] == 0) {  # 0 indicates a new item
      # New item, so use insert
        $query = "INSERT INTO $parameters[table_name] (";
        $query2 = "VALUES (";
        $delim = '';
        for ($i = 0; $i < count($DATA); $i++) {
            $query  .= $delim . $NAME[$i];
            $query2 .= $delim . $DATA[$i];
            $delim = ', ';
        }
        $query .= ")\n$query2)";
      # Use this to log changes in log table
        $log_note = 'Added a new entry';
    } else {
      # updated item, so use update
        $query = "UPDATE $parameters[table_name] SET ";
        $delim = '';
        for ($i = 0; $i < count($DATA); $i++) {
            if ($NAME[$i] != 'id') { # Covered by WHERE part of query
            #print "<li>$i: '$NAME[$i]' = $DATA[$i]";
                $query  .= $delim . $NAME[$i] . '=' . $DATA[$i];
                $delim = ', ';
            }
        }
        $query .= " WHERE id='$parameters[entry_id]'";
      # Use this to log changes in log table
        $log_note = "Altered entry";
    }

  # Do the work
    if (count($DATA) == 0) {
        $error = 'no fields returned to update';
        if (!$generic_put_row_silent) {
            print "<blockquote><font color=red>$error</font></blockquote>";
        }
        return(-1);
    } else {
        try {
        // $db = basic_db_admin_connect();
            $sth = $db->prepare($query);
            $sth->execute();
        } catch (PDOException $ex) {
            lib_mysql_die("edit.inc: (556) generic_put_by_id error", "$query<p><p>" . $ex->getMessage());
        }
    }

  # If we give the modify command, but nothing is modified, rowCount should be 0
    if ($sth->rowCount() > 0) {
        if (!$generic_put_row_silent) {
            print ("<blockquote>
	<font color=green>Table $parameters[table_name] successfully updated.</font></blockquote>");
        }

     # If bin/log.inc exists, include it and try to log the result
        if (is_readable(basic_to_base() . 'bin/log.inc')) {
            include_once(basic_to_base() . 'bin/log.inc');
            $temp_id = empty($parameters['editors_id']) ? '' : $parameters['editors_id'];
            log_action(
                $GLOBALS['db'],
                $temp_id,
                'modified',
                "$parameters[table_name].id=$parameters[entry_id]",
                "$log_note as $generic_editors_role"
            );
        }

        return(1);
    } else {
        $error = "Table not updated (no rows affected), did you change anything?";
        if (!$generic_put_row_silent) {
            print "<blockquote><font color=red>$error</font></blockquote>";
        }
        return(-1);
    }
}

# Data about each row in each table (hopefully) resides in the database
# table meta_row.  GetMetaRowData ($TableName) gets that meta_row data.
# Rather than return the meta_row info, GetMetaRowData defines the global
# hash arrays $meta_row_info (strings), $meta_row_edit (all, admin or no),
# $meta_row_test (strings to eval), and $meta_row_order (1,2,...$MaxMetaRowOrder)

function GetMetaRowData($TableName)
{
    global $meta_row_info, $meta_row_edit, $meta_row_test;
    global $meta_row_order, $meta_row_view, $MaxMetaRowOrder;

# Note $meta_row_order is given a default value of $MaxMetaRowOrder in
# generic_put_row_by_id( above--because some field may not be processed
# here (e.g., they are not yet listed in meta_row table).

    if (empty($TableName)) {
        die('$TableName must be specified in a call to GetMetaRowData');
    }

  # first load the default meta_row data (applies to all tables)
    $db = basic_db_admin_connect();
    $sth = $db->query("SELECT * FROM meta_row WHERE table_='meta_row_defaults'");
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      # print "<li> meta default: "; print_r($row);
        $name = $row['row_'];
        $meta_row_info[$name] = $row['info'];
        $meta_row_edit[$name] = $row['edit'];
        $meta_row_view[$name] = $row['view_'];
        $meta_row_test[$name] = $row['test'];
        if (is_numeric($row['order_'])) {
            $meta_row_order[$name] = min(max($row['order_'], 1), $MaxMetaRowOrder);
        }
    }

  # now the table specific meta_row data
    $sth2 = $db->query("SELECT * FROM meta_row WHERE table_='$TableName'");
    while ($row = $sth2->fetch(PDO::FETCH_ASSOC)) {
      # print "<li> meta specific: "; print_r($row);
        $name = $row['row_'];
        $meta_row_info[$name] = $row['info'];
        $meta_row_edit[$name] = $row['edit'];
        $meta_row_view[$name] = $row['view_'];
        $meta_row_test[$name] = $row['test'];
        if (is_numeric($row['order_'])) {
            $meta_row_order[$name] = min(max($row['order_'], 1), $MaxMetaRowOrder);
        }
    }

    return 1;
}

function my_get_from_global($name, $type, $parameters)
{
  # Want to: $data = trim($GLOBALS[$name]), but 'SET' type columns are a pain.

    if (preg_match("/\bset\b/", $type)) {
      # Note that set columns data are in checkboxes with the :::1, :::2, ...
      # appended to the names so they do not over write each other.
      # We want to combine these into a comma delimited list
        $set_list = lib_get_enum_strings($name, $parameters['table_name'], $parameters['database_name']);
        $set_list = explode("\0", $set_list);
        $data = '';
        $delim = '';
        for ($i2 = 0; $i2 < count($set_list); $i2++) {
            $temp = trim(isset($GLOBALS["$name:::$i2"]) ? $GLOBALS["$name:::$i2"] : '');
            if (!empty($temp)) {
                $data .= $delim . $temp;
                $delim = ',';
            }
        }
    } else {
      #  HEY NEED TO INTELLEGENTLY HANDLE NULL FIELDS -- FIX THIS!
        $data = (isset($GLOBALS[$name]) ? trim($GLOBALS[$name]) : '');
    }
    return $data;
}
