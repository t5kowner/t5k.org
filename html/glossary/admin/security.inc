<?php

# Access to database table is controlled by the routine role (which takes an authorized users
# database id or username) along with the table name, database name and entry id; then
# determines the persons role. If a table does not have an entry in the permissions_array
# then we look to see if the database has a default 'database_name.*'; if not, every one is
# 'all'.
#
# DOES NOT ALLOW THE CREATION OF NEW ENTRIES FOR SECURITY REASON (how to verify not
# spooofing identity?)
#
# The roles are 'none', 'root', 'admin', 'owner', and 'all'.  Note 'all' is the least
# privileged class ("the whole world") and 'none' is the supposedly never granted most
# priveleged.  Never grant higher than 'root' or you are opening up possible troubles!
#
# Which fields the person can change in the given table is then determined by the table
# meta_row, this table also controls what rows each role can view (via View_).  If
# meta_row.Edit='admin'; then 'admin' and higher ('root', 'none'; but there are no 'none')
# can edit. Same for View_.
#
# If a table does not have corresponding meta_row entries set, the defaults will be
# Edit='root' and View='admin'. Classifying each person role is done by the hardcoded
# this hardcoded routine "role" to hopefully be both secure and easier to set up the first
# time.
#
# It is assumed that the person has been validated via my_auth in http_auth.inc
# or some other way to make sure they are who they claim.

# include_once(basic_to_base().'admin/permissions_array.inc');
include_once('permissions.inc');

# Security note: if a later routine tries to redefine a constant, it will not succeed
# but only generates a (usually invisible) warning (not an error)


function role(&$parameters)
{
    global $db, $permissions_array, $basic_Use_Database_for_Authorization, $basic_Use_Database_for_Authorization_username;
  # The admin's and roots are listed in the array of arrays: $permissions_array, owners
  # are handled below.

  # Which database and table?
    $DatabaseName = $parameters['database_name'] or lib_die('Database name is not defined in role');
    $TableName = $parameters['table_name'] or lib_die('Table name is not defined in role');
    $EntryID = (isset($parameters['entry_id']) ? $parameters['entry_id'] : '');
  # echo "[role for table $TableName in $DatabaseName with id $EntryID]";

  # Who is it trying to edit?
    if (isset($parameters['editors_id'])) {
        $PersonID = $parameters['editors_id'];
    } elseif (isset($parameters['editors_username'])) { # Use username (or lastname) if id not set
        if (empty($db)) {
            $db = basic_db_connect();
        }
        $column = 'username';
        if (!empty($basic_Use_Database_for_Authorization_username)) {
            $column = $basic_Use_Database_for_Authorization_username;
        }
        if ($PersonID = lib_get_column("$column='$parameters[editors_username]'", 'person', 'id', $db)) {
            $parameters['editors_id'] = $PersonID; # let's store this ID in the bundle of parameters passed about
        } else {
            lib_die("Did not find a person with $column '$parameters[editors_username]' in $DatabaseName.$TableName");
        }
    } else {
        lib_die("role must be passed either the editors id (editors_id) or his/her last name (editors_username)");
    }
  # the last error can be caused by the htpauth system nt working

  # Are they who they claim?  (path differs depending on where called from
    if (!empty($basic_Use_Database_for_Authorization)) {
        include_once(basic_to_base() . 'bin/http_auth.inc');
        if (!my_auth($PersonID, 'log errors')) {
            lib_die('You are not authorized to be here!', 'password', 'do not log this error');
        }
    } else {
      # relying on .htaccess/.htpasswd to enforece access
    }

  # Okay, is the person assigned an editing role ('root' or 'admin')?  If so return it.
  # Note: since these overide the tests for ownership, setting a person's role to 'all'
  # in $permissions_array will keep them from even editing the entires they "own." But
  # making them 'none' there would give them powers not even root has! (So don't!)
    if (isset($permissions_array["$DatabaseName.$TableName"])) {
        if (isset($permissions_array["$DatabaseName.$TableName"]["$PersonID"])) {
            return($permissions_array["$DatabaseName.$TableName"]["$PersonID"]);
        }
    # Now check for a default list of editors for all tables
    } elseif (isset($permissions_array["$DatabaseName.*"]["$PersonID"])) {
        return($permissions_array["$DatabaseName.*"]["$PersonID"]);
    }
  # print "<li>Not set: \$permissions_array[$DatabaseName.$TableName][$PersonID]";
  # print "<li>Not set: \$permissions_array[$DatabaseName.*][$PersonID]";

  # Okay, not an editor.  Without knowing the entry id we can not determine ownership (own
  # everything and you might as well be an editor!) but if the id is 0, then this is a new
  # entry and you are defacto owner!
  # if ($EntryID==0) return('owner');  # Hummm, causes lots of security problems
#  if (!empty($parameters['allow_new_as_owner'])) {
#    if ($EntryID==0) return('owner'); }
#  else
    if (empty($EntryID)) {
        return('all');  # 'all' is the least privileged class, 'none' the most.
    }

  # Determining ownership from here is dependent on the database and table
    if ("$DatabaseName.$TableName" == 'primes.person') {
        return ($EntryID == $PersonID ? 'owner' : 'all');
    } elseif ("$DatabaseName.$TableName" == 'primes.comment') {
      # Owner if PersonID = person_id from entry EntryID in primes.comment
  ## Does not come here for new primes? (see previous paragraph)
  ## if ($EntryID == 0) return ('owner'); # New entry--it will be theirs!
        $temp = lib_get_column("id='$EntryID'", 'comment', 'person_id', $db);
        return ($temp == $PersonID ? 'owner' : 'all');
    } elseif ("$DatabaseName.$TableName" == 'primes.code') {
      # To be an owned need that the codes' name be on the list of the person (human's) codes.
      # No need to match wild_codes I think
        $temp = lib_get_column("id='$parameters[editors_id]'", 'person', 'codes, username, type', $db);
        if (!isset($temp['codes']) or $temp['type'] != 'person') {
            return 'all';
        }
        $codes_name = lib_get_column("id='$parameters[entry_id]'", 'code', 'name', $db);
        return (preg_match("/\b$codes_name\b/", $temp['codes']) ? 'owner' : 'all');
    }
}
