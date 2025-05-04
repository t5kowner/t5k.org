<?php

// What shows up in the log recently?

# Now let's keep the bad folks out
include_once('http_auth.inc');
include_once('../admin/permissions.inc');
$t_submenu = 'admin log';

if (!my_auth('allow any') or $permissions_array['view logs'][$http_auth_id] <> 'yes') {
    lib_die("You can not view this page without the proper authorization. [error: $http_auth_id]");
}

// First register two form variables.

$hours = isset($_REQUEST['hours']) ? $_REQUEST['hours'] : '';
if (preg_match('/(\d{1,5})/', $hours, $temp)) {
    $hours = $temp[1];
} else {
    $hours = 48;
}

$edit = !empty($_REQUEST['edit']) ? 'edit=' . preg_replace('/[^\d]/', '', $_REQUEST['edit']) : '';
$refresh = !empty($_REQUEST['refresh']) ? 'refresh=' . preg_replace('/[^\d]/', '', $_REQUEST['refresh']) : '';
$add2url = $edit . ((!empty($edit) and !empty($refresh)) ? '&amp;' : '') . $refresh;
$add2url = (!empty($edit) or !empty($refresh)) ? '?' . $add2url : '';
$edit = !empty($edit) ? '&amp;' . $edit : '';

$interval = preg_replace('/[^\d]/', '', $refresh);
if ($interval > 0) {
    $interval = 60 * $interval;
    $interval = "<META HTTP-EQUIV=Refresh CONTENT=$interval>";
} else {
    $interval = '';
}

$t_title = "Prime Database Log";
$t_text = "<p>A log that would allow me to trace back to see who
	did what.  Who is blocked? <a href=\"blocked.php$add2url\">Check here</a>.</p>\n";
$t_adjust_path = '../';

include_once("basic.inc");
$dbh = basic_db_connect(); # Connects or dies
# missing should not exist!
$t_meta['add_lines'] = "<style type=\"text/css\">\n  <!--
    .warning  { background-color: #FC6;    }
    .password { background-color: #CC9;    }
    .error    { background-color: #F03;    }
    .missing  { background-color: #F33;    }
    .deleted  { background-color: #FA6;	   }
    .mailed   { background-color: #F0F9FF; }
    .other    { background-color: #FFF;    }
    .created  { background-color: #CFC;    }
    .modified { background-color: #DFD;    }
    a 	  { background-color: transparent; }\n  -->\n</style>
" . $interval;

## To see these log pages you must be a Titan listed in ../admin/permissions.inc

# Let's work on the search.  Search form variables will be ind_[database row name]
# We will build the where part of the search (in $where) as we add row to the
# search table ($NavigatorTable).

  $NavigatorTable = '';  # Will build the search form in this variable

  $where = '';
if (1) {  # Why would we leave this out?  Remove the if?
    $ind_search1 = (isset($_REQUEST['ind_search1']) ?   # This is the search string
    $_REQUEST['ind_search1'] : '');
    $ind_search2 = ((isset($_REQUEST['ind_search2']) and preg_match("/^(\w+|prime\.id)$/", $_REQUEST['ind_search2']))
    ? $_REQUEST['ind_search2'] : '');       # This is the name of the database field to search

    $NavigatorTable .= "<tr><td>Seek
        <input type=text name=ind_search1 value=\"" .
      (!empty($ind_search1) ? $ind_search1 : '') . "\" size=12> in
        <select name=ind_search2>";
  # Get list of fields that might be searched
  # $temp = LookupMeta($TableName,'SearchFields');
  # if (empty($temp)) $temp = lib_list_fields(basic_DatabaseName(),$TableName);
    $valid_strings = "person_id, where_, what, notes, when_, prime_id, from_";
    $columns = explode(", ", $valid_strings);
    for ($i = 0; $i < count($columns); $i++) {
        $col = $columns[$i];
        $NavigatorTable .= "<option" . ($ind_search2 == $col ? ' selected' : '') .
        ">$col</option>\n";
    }
    $NavigatorTable .= "</select>";

    if (empty($AddDateSearch) or $AddDateSearch != 1) { # If 1, then a search button will be added later
        $NavigatorTable .= " <input type=submit name=ind_offset value=search class=\"btn btn-primary py-1\"> (% is a wildcard)";
        $SearchButtonAdded = 1;
    }
    $NavigatorTable .= "</td></tr>";

  # Now build the associated WHERE restriction to the query

    if (!empty($ind_search1)) {
        if ($ind_search2 == 'prime_id' and preg_match('/^\d+$/', $ind_search1)) {
            $where .= (empty($where) ? '' : ' AND ') . "where_ LIKE '%.id=$ind_search1'";
        } else {
            $where .= (empty($where) ? '' : ' AND ') . "$ind_search2 LIKE '$ind_search1'";
        }
    }
}

#  # Get meta_row info about the rows (so we can expand enums...)
#  $result = mysql_list_fields(basic_DatabaseName(),'log',$db)
#        or lib_mysql_die ("mysql_list_fields failed, does the table
#            \"log\" exist in the database \"".basic_DatabaseName().'"?','');

try {
    $sth = $dbh->query("DESCRIBE log");
    $table_meta = $sth->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    lib_mysql_die("log.php: (57) describe table error", $ex);
}
  # print "<li> meta: "; print_r($table_meta);


for ($i = 0; $i < count($table_meta); $i++) {
    # This is an enum field if the $flags includes the word enum
    $column = $table_meta[$i]['Field'];
    $type = $table_meta[$i]['Type'];
    if (preg_match("/\benum\b/", $type)) {
        preg_match("/(enum|set)\('(.*?)'\)/", $type, $match);
        $enum_variables[$column] = preg_replace("/','/", "\0", $match[2]);
    }
}

  # For many uses, entries may be visible or not.  Email address hidden or
  # not.  Here we add search fields for those enum cases.

##  while (list($key, $val) = @each($enum_variables)) {
foreach ($enum_variables as $key => $val) {
   # The string '_either_' is a value which indicates not to restrict the search
    # using that field.  Used just in this while loop... Hopefully will never be  an
    # enum value.

    # If no restriction specified, use '_either_' (any).
    $field = (isset($_REQUEST['ind_' . $key]) ? $_REQUEST['ind_' . $key] : '');

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
        $where = (empty($where) ? '' : "$where AND ") . "$key='$field'";
    }
}
  $where = (empty($where) ? '' : 'AND ' . $where);

# Done with search table....



$t_text .= "<p>Logged activities from the last $hours hours (reverse chronological order).</p>\n";

## Start by selecting the entries; everything from the log, username and name from person.
## What happens here if the person is undefined????  Do we need a left join or something?

$time = time();
$query = "SELECT log.*, person.username, person.name, person.PrimesTotal,
	unix_timestamp(NOW())-unix_timestamp(when_) as time_diff FROM log,person
	WHERE log.person_id=person.id $where
	AND (when_ > DATE_SUB(NOW(),INTERVAL $hours HOUR)) ORDER BY id DESC";

$sth = lib_mysql_query($query, $dbh, 'Invalid query in this page view, contact Chris');

# Get the rows
$count = 0;

$out = "<table border=0 id=\"myTable\" class=\" \">
<thead><tr bgcolor=\"$medcolor\">
<th>Who</th>
<th>Did</th>
<th>What</th>
<th>Ago&nbsp;&nbsp;&nbsp;&nbsp;</th>
<th>From Where&nbsp;&nbsp;&nbsp;&nbsp;</th>
<th>Notes</th>
</tr>\n</thead>\n<tbody>";

while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    $id = $row['id'];
    $what = ($row['what'] ? $row['what'] : 'missing');
    $when = $row['when_'];
    $diff = sprintf('%01.1f&nbsp;', $row['time_diff'] / 60 / 60);
    $where = $row['where_'];  # I try to make this table.row=identifier
    if (preg_match("/^(prime\.id)=(\d+)$/", $where, $temp)) {
        $where = $temp[1] . '=<a href="/primes/page.php?id=' . $temp[2] . "$edit\" class=none>$temp[2]</a>";
    } elseif (preg_match("/^(.*?deleted\.id)=(\d+)(.*)$/", $where, $temp)) {
        $where = $temp[1] . '=<a href="/primes/page.php?deleted=true&amp;id=' . $temp[2] . "$edit\" class=none>$temp[2]</a>$temp[3]";
    } elseif (preg_match("/^(person\.id)=(\d+)$/", $where, $temp)) {
        $where = $temp[1] . '=<a href="/bios/page.php?id=' . $temp[2] . "$edit\" class=none>$temp[2]</a>";
    } elseif (preg_match("/^(person\.codes)=(\d+)([,\d]*)$/", $where, $temp)) {
        $where = $temp[1] . ' <a href="/bios/page.php?id=' . $temp[2] . "$edit\" class=none>$temp[2]</a>$temp[3]";
    } elseif (preg_match("/^(code\.name)=(\w+)$/", $where, $temp)) {
        $where = $temp[1] . '=<a href="/bios/code.php?code=' . $temp[2] . "\" class=none>$temp[2]</a>";
    } elseif (preg_match("/^(code\.id)=(\w+)$/", $where, $temp)) {
        $where = $temp[1] . '=<a href="/bios/code.php?id=' . $temp[2] . "\" class=none>$temp[2]</a>";
    } elseif (preg_match("/^(archivable\.id)=(\w+)$/", $where, $temp)) {
        $where = $temp[1] . '=<a href="/top20/page.php?id=' . $temp[2] . "\" class=none>$temp[2]</a>";
    } elseif (preg_match("/^(comment\.id)=(\w+)$/", $where, $temp)) {
        $where = $temp[1] . '=<a href="/primes/comment.php?xx_comment_id=' . $temp[2] . "\" class=none>$temp[2]</a>";
    } elseif (preg_match("/^(prime_blob\.id)=(\w+)$/", $where, $temp)) {
        $where = $temp[1] . '=<a href="/primes/admin/index.php?xx_TableName=prime_blob&xx_view=' . $temp[2] . "\" class=none>$temp[2]</a>";
    }
    $from = $row['from_'];
    $from = preg_replace('/remove_unused/', 'remove', $from);
    if (preg_match("/^(.*) via (.*)$/", $from, $temp)) {
        if ($temp[1] == '::1') {
            $from = 'localhost';
        } else {
            $from = "<a href=\"whois.php?ip=$temp[1]\" title=\"$temp[2]\" class=none>$temp[1]</a>";
        }
    }
  # $notes = htmlentities(stripslashes($row['notes']));
  # No--there are HTML entites there we want (links on purpose!) Just clean them before if necessary
    $notes = stripslashes($row['notes']);
    $notes = preg_replace("/<a href/i", "<a class=none href", $notes);
    $notes_title = $notes;
    $notes = preg_replace("/(\(step 2 of 2\) UPDATE person SET).*/s", '$1...', $notes);
    $notes = preg_replace("/(code \w+ rebuilt).*/s", '$1...', $notes);

    $who = $row['person_id'];
    $temp = "";
  ## if ($row['PrimesTotal'] == 0 and $row['person_id'] != 254) $temp =  "<img src=\"/gifs/warning.gif\">";
    if (!empty($row['PrimesTotal']) and $row['PrimesTotal'] > 0 and !empty($edit)) {
        $temp =  "<img src=\"/gifs/check.gif\" title=$row[PrimesTotal] alt=\"+\">";
    }

    if ($who != 'NULL') {
        $who = "$temp <a href=\"/bios/page.php?id=$who\"
	title=\"$row[name]\"  class=none>$row[username]</a>";
    }

    $out .= "\n<tr class=\"$what\">
    <td align=right>$who</td>
    <td title=\"log entry $id\">$what</td>
    <td>$where</td>
    <td title=\"$when\" align=center>$diff</td>
    <td>$from</td>
    <td title=\"" . htmlentities($notes_title) . "\">$notes</td>
</tr>\n";
    $count++;
}
$out .= "\n</tbody></table>\n";

$t_text .= $out;

$t_text .= "<h4 class=\"mt-4\">Modify this display:</h4>
  <blockquote><form action=\"log.php\"><table><tr><td>
    <p>Include those modified (for any reason) in the last
    <input type=text size=5 name=hours value='$hours'>
    hours.</p>
    </td></tr>
    $NavigatorTable
  </table></form></blockquote>
";

$t_text .= "<div class=technote><hr>What is the server up to?<hr><pre><b>Load:</b> w " . shell_exec("w") . "\n" .
    shell_exec("ps -ef | grep client") . "\n<b>Query</b>: $query</pre></div>";

include("../template.php");
