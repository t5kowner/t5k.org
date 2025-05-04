<?php

// What shows up in the log recently?  First register two form variables.

$hours = isset($_REQUEST['hours']) ? $_REQUEST['hours'] : '';
if (preg_match('/(\d{1,5})/', $hours, $temp)) {
    $hours = $temp[1];
} else {
    $hours = 48;
}

$edit = !empty($_REQUEST['edit']) ? preg_replace('/[^\d]/', '', $_REQUEST['edit']) : '';
$refresh = !empty($_REQUEST['refresh']) ? 'refresh=' . preg_replace('/[^\d]/', '', $_REQUEST['refresh']) : '';
$add2url = ((!empty($edit) and !empty($refresh)) ? "edit=$edit&amp;" : '') . $refresh;
$add2url = (!empty($edit) or !empty($refresh)) ? '?' . $add2url : '';

$interval = preg_replace('/[^\d]/', '', $refresh);
if ($interval > 0) {
    $interval = 60 * $interval;
    $interval = "<META HTTP-EQUIV=Refresh CONTENT=$interval>";
} else {
    $interval = '';
}

$t_title = "Curios Database Log";
$t_text = "A log that would allow me to trace back to see who did what.\n";
$t_adjust_path = '../';

include("../bin/basic.inc");
$db = basic_db_connect(); # Connects or dies
$t_meta['add_lines'] = "<style type=\"text/css\">\n  <!--
    .warning    { background-color: #FC6;    }
    .rated      { background-color: #CC6;    }
    .password   { background-color: #CC9;    }
    .error      { background-color: #F03; border-style: none !important; padding: 0em !important }
    .deleted    { background-color: #FFBFBF; }
    .visibility { background-color: #BFBFFF; color: #000 !important; }
    .mailed     { background-color: #F0F9FF; }
    .other      { background-color: #FFF;    }
    .created    { background-color: #CFC; color: #000 !important; }
    .modified   { background-color: #DFD; color: #000 !important; }
    a 	  { background-color: transparent; }\n  -->\n</style>
" . $interval;

# Now let's keep the bad folks out (relying on .htaccess :( )
include_once('permissions.inc');
//// if (empty($permissions_array['view logs'][$edit]) OR $permissions_array['view logs'][$edit] <> 'yes')
////    lib_die("You can not view this page without the proper authorization. [error: edit=$edit]");

# Now .htaccess guarenteed they new a password, but does it match the $edit= id?
if ($PersonID = lib_get_column("lastname='$_SERVER[PHP_AUTH_USER]'", 'person', 'id', $db)) {
    if ($PersonID != $edit) {
        lib_die('Use the correct parameter for edit');
    }
} else {
    lib_die("Did not find a person with lastname '$_SERVER[PHP_AUTH_USER]'");
}


## To see these log pages you must be a Titan listed in ../admin/permissions.inc

# Let's work on the search.  Search form variables will be ind_[database row name]
# We will build the where part of the search (in $where) as we add row to the
# search table ($NavigatorTable).


  $NavigatorTable = '';  # Will build the search form in this variable

  $where = '';
if (1) {  # Why would we leave this out?  Remove the if?
    $ind_search1 = (isset($_REQUEST['ind_search1']) ?   # This is the search string
    $_REQUEST['ind_search1'] : '');
    $ind_search2 = ((isset($_REQUEST['ind_search2']) and preg_match("/^(\w+|curio\.id)$/", $_REQUEST['ind_search2']))
    ? $_REQUEST['ind_search2'] : '');       # This is the name of the database field to search

    $NavigatorTable .= "<tr><td>Seek
        <input type=text name=ind_search1 value=\"" .
      (!empty($ind_search1) ? $ind_search1 : '') . "\" size=12> in
        <select name=ind_search2>";
  # Get list of fields that might be searched
  # $temp = LookupMeta($TableName,'SearchFields');
  # if (empty($temp)) $temp = lib_list_fields(basic_DatabaseName(),$TableName);
    $valid_strings = "person_id, where_, what, notes, when_, curio_id, from_";
    $columns = explode(", ", $valid_strings);
    for ($i = 0; $i < count($columns); $i++) {
        $col = $columns[$i];
        $NavigatorTable .= "<option" . ($ind_search2 == $col ? ' selected' : '') .
        ">$col</option>\n";
    }
    $NavigatorTable .= "</select>";

    if (empty($AddDateSearch) or $AddDateSearch != 1) { # If 1, then a search button will be added later
        $NavigatorTable .= " <input type=submit name=ind_offset value=search> (% is a wildcard)";
        $SearchButtonAdded = 1;
    }
    $NavigatorTable .= "</td></tr>";

  # Now build the associated WHERE restriction to the query

    if (!empty($ind_search1)) {
        if ($ind_search2 == 'curio_id' and preg_match('/^\d+$/', $ind_search1)) {
            $where .= (empty($where) ? '' : ' AND ') . "where_ LIKE '%.id=$ind_search1'";
        } else {
            $where .= (empty($where) ? '' : ' AND ') . "$ind_search2 LIKE '$ind_search1'";
        }
    }
}

  # Get meta_row info about the rows (so we can expand enums...)

try {
    $sth = $db->query("DESCRIBE log");
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
        $enum_variables{$column} = preg_replace("/','/", "\0", $match[2]);
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

# Done with search table... Now let's store a list of names, lastnames by id.

$query = "SELECT lastname, name, id FROM person";
$sth = lib_mysql_query($query, $db, 'Invalid query');
while ($row = $sth->fetch()) {
    $id =  $row['id'];
    $x_id{ $row['lastname'] } = $id;
    $x_lastname{ $id } = $row['lastname'];
    $x_name{ $id }     = $row['name'];
  # print "<li>".$id.$x_name{$id}.$x_lastname{$id};
}

$t_text .= "Logged activities from the last $hours hours (reverse chronological order)\n<P>\n";

## Start by selecting the entries; everything from the log, lastname and name from person.
## What happens here if the person is undefined????  Do we need a left join or something?

$time = time();
$query = "SELECT log.*, unix_timestamp(NOW())-unix_timestamp(when_) as time_diff
        FROM log WHERE $where " . (empty($where) ? '' : ' AND ') . " (when_ > DATE_SUB(NOW(),INTERVAL $hours HOUR)) 
	ORDER BY id DESC";
$sth = lib_mysql_query($query, $db, 'Invalid query');

# Get the rows
$count = 0;

$out = "<table border=0>\n<tr bgcolor=\"$medcolor\"><th>Who</th><th>Did</th><th>What</th>
<th>Ago</th><th>From Where</th><th>Notes</th></tr>";

while ($row = $sth->fetch()) {
    $id   = $row['id'];
    $what = ($row['what'] ? $row['what'] : 'missing');
    $when = $row['when_'];
    $who  = $row['person_id'];

    $lookup_failed = 0;
    if (preg_match('/^\d+$/', $who) and !empty($x_lastname{$who})) {
        $lastname = $x_lastname{$who};
        $name = $x_name{$who};
    } elseif (!empty($x_id{$who})) {
        $lastname = $who;
        $name = $x_name{$x_id{$who}};
    } else {
        $lastname = $who;
        $name = $who;
        $lookup_failed = 1;
    }

    $diff = sprintf('%01.1f&nbsp;', $row['time_diff'] / 60 / 60);
    $where = $row['where_'];  # I try to make this table.row=identifier
    if ($what == 'deleted' and preg_match("/^(.*?curios\.id)=(\d+)(.*)$/", $where, $temp)) {
        $where = $temp[1] . '=<a href="/curios/page.php?deleted.id=' . $temp[2] . "&amp;edit=$edit\" class=none>$temp[2]</a>$temp[3]";
    } elseif (preg_match("/^(curios\.id)=(\d+)$/", $where, $temp)) {
        $where = $temp[1] . '=<a href="/curios/page.php?curio_id=' . $temp[2] . "&amp;edit=$edit\" class=$what>$temp[2]</a>";
    }
    $from = $row['from_'];
    $from = preg_replace('/remove_unused/', 'remove', $from);
    if (preg_match("/^(.*) via (.*)$/", $from, $temp)) {
  #    $from = "<a href=\"whois.php?ip=$temp[1]\" title=\"$temp[2]\" class=none>$temp[1]</a>";
        $from = "<a href=\"http://ip-whois-lookup.com/lookup.php?ip=$temp[1]\" title=\"$temp[2]\" class=none>$temp[1]</a>";
    }
# $notes = htmlentities(stripslashes($row['notes']));
# No--there are HTML entites there we want (links on purpose!) Just clean them before if necessary
    $notes = stripslashes($row['notes']);
    $notes = preg_replace("/<a href/i", "<a class=none href", $notes);
    $notes_title = $notes;
    $notes = preg_replace("/\b([\w\.]+@\w+\.[\w\.]+)\b/", '<a href="mailto:$1" class=none>$1</a>', $notes);
    if ($lookup_failed) {
        $who = $lastname;
    } else {
        $who = "<a href=\"/curios/ByOne.php?submitter=$lastname\" title=\"$name\"  class=none>$lastname</a>";
    }

    $out .= lib_tr("class=$what") . "<td align=right>$who</td>\n<td title=\"log entry $id\">$what</td>\n<td>$where</td>" .
        "<td title=\"$when\" align=center>$diff</td>\n<td>$from</td>\n<td title=\"" . htmlentities($notes_title) . "\">$notes</td>\n</tr>\n";
    $count++;
}
$out .= "\n</table>\n";

$t_text .= $out;

$t_text .= "<h4>Modify this display:</h4>
  <blockquote><form action=\"log.php\"><table><tr><td>
    Include those modified (for any reason) in the last <input type=text size=5 name=hours value='$hours'>
    hours.</td></tr>
    $NavigatorTable
    <input type=hidden name=edit value='$edit'>
  </table></form></blockquote>
";

$t_text .= "<div class=technote><hr>What is this server currently up to?<hr><pre><b>Load:</b> w " . shell_exec("w") . "\n" .
    shell_exec("ps -ef | grep client") . "\n<b>Query</b>: $query</pre></div>";

include("../template.php");
