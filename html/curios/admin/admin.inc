<?php

// Support routines for admin pages

////// HTML Producing Functions //////

// The key for both of these menu functions to work is setting the variables
// in basic_DatabaseName().

// admin_TableMenu() prints menu bar of tables (e.g., top of admin pages)
// If xx_TableName is set, will not link to that TableName.

// Note: both the users System and root are considered root users.

function admin_TableMenu($TableName = '')
{
    global $db;
  # print menu bar of tables
    $out = '';
    $tables = explode(", ", admin_TableNames('', $db, 'non-meta'));
    $delim = '[ Edit ' . basic_DatabaseName() . ' : ';
    for ($i = 0; $i < count($tables); $i++) {
        $temp = $tables[$i];
        if ($temp == $TableName) {
            $out .= "$delim<B>$temp</B>\n"; # Don't need a link to the current table
        } else {
            $out .= "$delim<a href=\"index.php?xx_TableName=$temp\">$temp</a>\n";
        }
        $delim = "        | ";
    }

    $delim = ' ] <BR> [ Meta Edit : ';
    $tables = explode(", ", admin_TableNames('', $db, 'meta'));
    for ($i = 0; $i < count($tables); $i++) {
        $temp = $tables[$i];
        if (empty($temp)) {
            continue;
        }
        if ($temp == $TableName) {
            $out .= "$delim<B>$temp</B>\n"; # Don't need a link to the current table
        } else {
            $out .= "$delim<a href=\"index.php?xx_TableName=$temp\">$temp</a>\n";
        }
        $delim = "        | ";
    }
    return($out . ' ] ');
}

// admin_TableNames($DatabaseName,$db,$which) lists the tables in the database
// where $which is 'all', 'meta', 'non-meta'

function admin_TableNames($DatabaseName, $db, $which = 'all')
{
    if (empty($DatabaseName)) {
        $DatabaseName = basic_DatabaseName();
    }
    if (empty($db)) {
        die("admin_TableNames must be passed a database link");
    }

    $out = '';
    $delim = '';
    $result = $db->query("show tables");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
      # Must be logged in as root to see the meta tables
        if (preg_match("/^meta_\w+/", $row[0])) { # ahh, meta table
            if ($which == 'non-meta' or !preg_match('/^(System|root)$/', $GLOBALS['role'])) {
                continue;
            }
    # the above should do it
    #      if ($which == 'non-meta' or empty($_SERVER['PHP_AUTH_USER']) or
    #   !preg_match('/^(System|root)$/',$_SERVER['PHP_AUTH_USER'])) continue;
        } elseif ($which == 'meta') {
            continue;
        }
        $out .= $delim . $row[0];
        $delim = ', ';
    }
    return $out;
}

// admin_AddNewItemMenu() prints menu bar to add new items (e.g., index page)

function admin_AddNewItemMenu()
{
    global $db;
  # print menu bar to add new items to tables
    $tables = explode(", ", admin_TableNames('', $db));
    $delim = '[ Add new : ';
    $out = '';
    for ($i = 0; $i < count($tables); $i++) {
        $temp = $tables[$i];
        $out .= "$delim<a href=\"index.php?xx_TableName=$temp&amp;xx_edit=0\">$temp</a>\n";
        $delim = " | ";
    }
    return($out . ' ] ');
}

// Replacement for the above (added early 2003) to focus on the table at hand
// The above is still used for the main admin page, the one below for table indexes

function admin_AddNewItem()
{
    global $xx_TableName;
    $out = "[ <a href=\"index.php?xx_TableName=$xx_TableName&amp;xx_edit=0\">" .
    "Add new $xx_TableName</a> |\n" .
    " (add edit/delete by id here) ]";
    return($out);
}

// Page heading, title.  Used by admin pages.

function admin_html_head($title = 'no title')
{
    global $bgcolor, $ltcolor, $medcolor, $drkcolor;
    $out = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">" .
    "\n<html>\n<head>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
    <title>
      " . basic_DatabaseName() . " database interface: $title
    </title>
  </head>\n<body bgcolor=\"$bgcolor\">
  <table style=\"border:$drkcolor double 4px;\" cellSpacing=0 cellPadding=0 width=\"100%\" border=1>
  <tbody> <tr> <td>
    <TABLE cellPadding=3 width=\"100%\" border=0> <TBODY> <TR bgColor=\"$medcolor\">
      <TD align=left><font size=\"+2\">" . basic_DatabaseName() . " database interface:<br>
        $title</font></td>
      <td align=right>" .
    admin_TableMenu((!isset($_REQUEST['xx_edit']) and isset($_REQUEST['xx_TableName']))
        ? $_REQUEST['xx_TableName'] : ' ') . "<BR>
        [ PHP : <a href=\"http://www.php.net/manual/\">manual</a> |
        <a href=\"php_info.php\">local info</a> ] &nbsp;
        [ MySQL : <a href=\"http://dev.MySQL.com/doc/refman/5.5/en/index.html\">manual</a> ] &nbsp;
	";
    if ($title != "administrative index") {
        $out .= "<a href=\"index.php\">ADMIN</a>\n";
    } else {
        $out .= "<B>ADMIN</B>\n";
    }
    $out .= "	</td>
    </TR></TBODY></TABLE></TD></TR>
  </TBODY>\n</TABLE>\n<P>";

    return $out;
}

// Signature for administrative pages
function admin_html_end()
{
    global $bgcolor, $ltcolor, $medcolor, $drkcolor;
    return "<P><P>
   <TABLE style=\"border:$drkcolor double 4px;\" cellSpacing=0 cellPadding=0 width=\"100%\" border=1>
   <TBODY><TR><TD><TABLE cellPadding=3 width=\"100%\" border=0><TBODY>
    <TR bgColor=\"$medcolor\"><TD>PrimePages &copy; 1996-" . date('Y') . "&nbsp;&nbsp;&nbsp;
    <I><A href=\"mailto:admin@t5k.org\">Reginald McLean</A></I></TD></TR>
  </TBODY></TABLE></TD></TR></TBODY></TABLE>\n</BODY>\n</HTML>";
}

// How about some default text for these pages?

function admin_default_text()
{
    $out = "Well hello there! There is a menu on the top right corner of this page
for you to reach the various tables in the " . basic_DatabaseName() . " database
(repeated below for your viewing pleasure).

<form method=post action=\"index.php\">
 <table>
  <tr>
   <td>You are logged in as user <b>$_SERVER[PHP_AUTH_USER]</b> with role <b>$GLOBALS[role]</b>.</td>
   <td><input type=hidden name=xx_action value='log out'>
	<input value=\"log out\" type=submit></td>
  </tr>
 </table>
</form>

<ul>
  <li><a href=complete.php>complete.php</a> -- show it all!
  <LI><B>" . admin_TableMenu() . "</B>
  <li>" . admin_AddNewItemMenu() . "
  <li>Explanation of the <a href=\"../bin/modify.php\">Modify subroutines</a>
  <li><a href=upload.php>Upload image files</a>\n";
    if (preg_match('/^(System|root)$/', $GLOBALS['role'])) {
        $out .= "<li><a href=extra.php>Extra functions for root user</a>\n";
    }
    $out .= "</ul>\n";
    return $out;
}
