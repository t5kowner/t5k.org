<?php

//
// Goal: A generic edit page. This is not a smart page, just one to do the work.
// so it must be called with the following set:
//
//  $xx_person_id   Who is editing? (person.id)
//  $xx_edit    What is the id of the entry? (global to edit.inc)
//  $xx_TableName   What table are we editing? (global to edit.inc)
//
// and possibly
// ## This one not fully implemented
// ##   $xx_ReturnTo    If set, will jump to this page when done (with no
// ##           comment from delete) omit https://primes.utm.edu, e.g.,
// ##           $xx_ReturnTo = '&amp;xx_ReturnTo='.$_SERVER['PHP_SELF'];
//  $xx_action  What should we do? Default is to edit, alternative 'put'
//          return from an edit, put changes to db) or 'delete'
//          which assumes the table has a 'visible' column and sets
//          it to 'no'
//
// This program uses HTTP basic authentication to allow access by
// comparing against database 'username' and 'pass'.  So no need to track
// that here.
//
// Using 'xx_' to match the edit.inc standards which are this way to
// avoid collision with database column names.

// Okay, load up the include files
include_once("bin/basic.inc");
include_once("bin/log.inc");
$db = basic_db_connect(); # Connects or dies

// For error meassages. Turning off output buffer as print_r posts to standard
// out, I want info for the error log.  can't seem to see where these bad
// accessess are coming from!

ob_start();
   print "\n\nPost:";
   print_r($_POST);
   print "\nGet:";
   print_r($_GET);
   print (empty($_SERVER['HTTP_REFERER']) ? "\nReferrer unspecified." :
        "\nReferrer: " . $_SERVER['HTTP_REFERER']);
   $debug_info = ob_get_clean();

// Okay, are they authorized to be here?

   include_once("bin/http_auth.inc");      # Basic HTTP authentication
   if (my_is_ip_blocked()) {       #  Are they currently blocked?
       lib_die("Can not edit, your IP is blocked.", 'warning');
   }

# Are they passing something that could be a legitimate id?
   $xx_person_id = (empty($_REQUEST['xx_person_id']) ? '' : $_REQUEST['xx_person_id']);
   $xx_person_id = preg_replace("/[^0-9]/", "", $xx_person_id);
# Note: allowing -1 through as a commmon robot  error, let it be passed to the
# authorizing routine and maybe then be blocked.
# if ( $xx_person_id eq "-1'" ) { exit; }
# if (!preg_match("/^\d+$/",$xx_person_id))
#   lib_die("person id error. [$xx_person_id]".$debug_info,'warning','log errors','silent');

# Are they authorized to use this id?
   if (!my_auth($xx_person_id, 'log errors')) {
      # echo welcome_screen($xx_edit,'Unknown Authorization Error!');
      # Why help the hackers?
       exit;
   }

// Yep, they are authorized...

// Are the key form variables set?
   $xx_action = (empty($_REQUEST['xx_action']) ? '' : $_REQUEST['xx_action']);

   $xx_edit = (empty($_REQUEST['xx_edit']) ? '' : $_REQUEST['xx_edit']);
   if (!preg_match("/^\d+$/", $xx_edit)) {
       lib_die(
           "entry id error." . $debug_info,
           'warning',
           'log errors',
           'silent'
       );
   }

   $xx_TableName = (empty($_REQUEST['xx_TableName']) ? '' :
    $_REQUEST['xx_TableName']);
   if (!preg_match("/^(comment)$/", $xx_TableName)) {
       lib_die("table name error." . $debug_info, 'warning', 'log errors', 'silent');
   }

   $xx_ReturnTo = (empty($_REQUEST['xx_ReturnTo']) ? '' : $_REQUEST['xx_ReturnTo']);

   $t_title = "Edit Entry in $xx_TableName ";
   $t_allow_cache = 'yes!';

   $parameters = array('entry_id' => $xx_edit,
        'editors_id'     => $xx_person_id,
        'database_name'  => basic_DatabaseName(),
        'table_name'     => $xx_TableName,
    'extra_for_form' => "<input type=hidden name=xx_ReturnTo value=\"$xx_ReturnTo\">",
        'put_row_silent' => 1);



   if ($xx_action == 'put') {          # returning from an edit form, put in db
       include_once('admin/edit.inc');
       update_database_screen(generic_put_row_by_id($parameters));
   } elseif ($xx_action == 'delete') {    # does not really delete, sets not visible
       $query = "UPDATE $xx_TableName SET visible='no' WHERE id=$xx_edit;";
       lib_mysql_query($query, $db, "set_visible failed in generic_edit 102");
       log_action(
           $db,
           $xx_person_id,
           'deleted',
           $xx_TableName . '.id=' . $xx_edit,
           '"deleted" by making non-visible'
       );
       if (!empty($xx_ReturnTo)) {         # Ah, know where to go next!
           if (!headers_sent()) {
                 header('Location: ' . $xx_ReturnTo);
           } else {
               echo "<a href=\"$xx_ReturnTo\">continue</a>";
           }
       } else {
           echo "$xx_TableName deleted";
       }
   } else {                # else let's edit!  Send the form
       include_once('admin/edit.inc');
       echo ReturnButtons('index', 'page');
       echo "Please edit the $xx_TableName and then press the submit at the bottom
 		of the page.";
       generic_show_edit_table($parameters, '');
   }

   exit;

##### Support routines

   function ReturnButtons()
   {
   # Will appear on the top of the edit page
   #  $out = "<P><form method=post action=\"page.php?id=$GLOBALS[xx_person_id]\">
   #   <input type=submit value=\"JUMP TO BIOGRAPHICAL PAGE\"></form>";
   #  return $out;
       return '';
   }

#####  Now the various "screens"

   function welcome_screen($id, $error = '')
   {
       $t_title = $GLOBALS['t_title'] . (empty($error) ? ' Welcome Screen' : ' Error');
       if (!empty($error)) {
           $error = "<font color=red size=+1>$error</font>\n<P>\n";
       }

       $t_text = "$error Welcome.  <B>What is your user database id number?</b>
        (If you do not know, look on <a href=index.php>your biographical page</a>, there
        will be an \"edit this entry\" link by the database number.)
        <form method=post action=\"$_SERVER[PHP_SELF]\">
        <blockquote>
	  person_id: <input type=text size=5 name=xx_person_id value=\"$GLOBALS[xx_person_id]\"><br>
	  table name:  <input type=text size=15 name=xx_TableName value=\"$GLOBALS[xx_TableName]\"><br>
          entry id: <input type=text size=10 name=xx_edit value=\"$id\">
          <input type=submit name=xx_action value=\"Edit Entry\">
        </blockquote>
        <P>Obviously we need to protect the integrity of this data, so you will be asked to
        provide your username (username) and password before you are allowed to continue.&nbsp;
        </form>";

       include("template.php");
   }



   function update_database_screen($success)
   {
       global $xx_ReturnTo;
       $t_title = $GLOBALS['t_title'] .
       ($success == 1 ? 'Database Updated' : '<font color=red>Update Failure!</font>');

       if ($success == 1) {
           $t_text = "The database has been successfully updated.";
           if (!empty($xx_ReturnTo)) {       # Ah, know where to go next!
               if (!headers_sent()) {
                   header('Location: ' . $xx_ReturnTo);
               } else {
                   echo "<a href=\"$xx_ReturnTo\">continue</a>";
               }
           }
       } elseif ($success == -1) {
           $t_text = "<font color=red size=+1>Database unchanged.</font>  This <b>should be</b> because
	you did not change anything.&nbsp; If this is not the case, contact
	the technical editor (using the link on the left).";
       } else {
           exit;  # database update returns 0 when reprinting the edit screen!
       }
       $t_text .= "
	<blockquote>
	  <form method=post action=\"generic_edit.php\">
	    <input type=hidden name=xx_TableName value=\"$GLOBALS[xx_TableName]\">
	    <input type=hidden name=xx_edit value=\"$GLOBALS[xx_edit]\">
	    <input type=hidden name=xx_person_id value=\"$GLOBALS[xx_person_id]\">
  	    <input type=submit name=xx_action value=\"EDIT ENTRY AGAIN\"><br>
	  </form>
  	  <form method=post action=\"page.php?id=$GLOBALS[xx_person_id]\">
	    <input type=submit value=\"VIEW BIOGRAPHICAL PAGE\">
	  </form>
	</blockquote>\n";
       include("template.php");
   }
