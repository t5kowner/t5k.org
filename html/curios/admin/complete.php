<?php

# The goal is to display all Curios, we will not use the template or style sheet.
# These will be raw and simple. start, stop set digit ranges for displayed curios.

// We must *first* start the session (before anything is output!)
session_start();

include("../bin/basic.inc");
$db = basic_db_admin_connect(); # Connects or dies

// Lazy way to get the variables from the
// edit form, but they will all be in HTTP_POST_VARS = _POST.  Lets make them global.

## while (list($key, $val) = @each($_POST)) {
foreach ($_POST as $key => $val) {
   // print "<li>\$GLOBALS[$key] = $val";
    $GLOBALS[$key] = $val;
}

if (isset($next)) {
    $start = $start + $number;
}

if (isset($visible)) {
    $_SESSION['complete_visible'] = $visible;
} elseif (isset($_SESSION['complete_visible'])) {
    $visible = $_SESSION['complete_visible'];
} else {
    $visible = 'either';
}

if (isset($number)) {
    $_SESSION['complete_number'] = $number;
} elseif (isset($SESSION_['complete_number'])) {
    $number = $_SESSION['complete_number'];
} else {
    $number = 100;
}

if (isset($start)) {
    $_SESSION['complete_start'] = $start;
} elseif (isset($_SESSION['complete_start'])) {
    $start = $_SESSION['complete_start'];
} else {
    $start = 1;
}

# add_delete not currently used
if (isset($add_delete)) {
    $_SESSION['complete_add_delete'] = $add_delete;
} elseif (isset($_SESSION['complete_add_delete'])) {
    $add_delete = $_SESSION['complete_add_delete'];
} else {
    $add_delete = 'no';
}


# The page can be massive, so we will print as we go.

# Build page title
$t_title = "$number Curios! starting with # $start";
echo "<H2 align=center>$t_title</H2>\n";
echo date("h:i A l F dS, Y\n");

$search = "<blockquote><table border=1 bgcolor=$drkcolor cellpadding=5>
  <form method=post action=\"" . $_SERVER['PHP_SELF'] . '?' . SID . "\"><tr><td bgcolor=$medcolor>
  Show <input type=text size=5 name=number value=$number>
  skipping first  <input type=text size=5 name=start value=$start><br>
  show those with visible = <input type=radio name=visible value=yes " .
  ($visible == 'yes' ? 'checked' : '') . "> yes
  <input type=radio name=visible value=no " .
  ($visible == 'no' ? 'checked' : '') . "> no or
  <input type=radio name=visible value=either " .
  ($visible == 'either' ? 'checked' : '') . "> either?
  <input type=submit value=SEARCH><br>
  <input type=submit value=\"SHOW NEXT $number\" name=next>
  </td></tr></form></table></blockquote>";
echo $search;

if (!empty($visible_yes)) {
    foreach ($visible_yes as $id) {
        set_visible('curios', $id, 'no');
        echo "<LI>made $id not visible\n";
    }
}

if (!empty($visible_no)) {
    foreach ($visible_no as $id) {
        set_visible('curios', $id);
        echo "<LI>made id=$id visible\n";
    }
}

if (!empty($delete)) {
    foreach ($delete as $temp) {
        if (preg_match('/^(\d+) (\w+)$/', $temp, $id)) {
            mail_reason($id[1], $id[2]);
            lib_delete_row_by_id($id[1], 'curios', $db);
            echo "<LI>deleted $id[1] because $id[2] \n";
        } else {
            print "error: did not recognize delete term '$temp'";
        }
    }
}

echo "<form method=post action=\"" . $_SERVER['PHP_SELF'] . '?' . SID . "\">
	<input type=submit value=\"Submit Changes\">";

# Okay, perform the query, build the index
if (1) {
    $where = 'numbers.id = curios.number_id';
    if ($visible != 'either') {
        $where .= " AND curios.visible='$visible'";
    }

    $query = "SELECT curios.text, numbers.short, numbers.log10, numbers.id,
	curios.visible, curios.id AS curios_id, curios.submitter
        FROM curios, numbers
        WHERE $where
	ORDER BY sign, log10
	LIMIT $start,$number";

    $sth = lib_mysql_query($query, $db, 'Invalid query');

    include("../bin/modify.inc");  # Modify the text entities like \pi

    $id = 0;
    $close = '';
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        if ($id <> $row['id']) {  # New number, add a number header
            echo $close,
            "\n<table width=100% bgcolor=$drkcolor cellspacing=2 cellpadding=2>\n",
            "<tr bgcolor=$medcolor><td width=75%>
	<a href=\"../page.php?number_id=$row[id]\" target=_blank>$row[short]</a></td>
	<td align=right width=15%>id=$row[id]</td></tr>\n";
            $id = $row['id'];
            $close = "</table><BR>\n";
        }
        $t_text = modify_entities($row['text']) . " &nbsp; &nbsp; [$row[submitter]]\n";
        $t_text .= " &nbsp; [<a href=\"index.php?xx_TableName=curios&amp;xx_edit=$row[curios_id]\"
	target=_blank>edit</a>]";
        $edit_bar = "<input type=checkbox name=\"visible_$row[visible][]\" value=$row[curios_id]>
 	make " . ($row['visible'] == 'yes' ? 'not ' : '') . "visible\n";
        $edit_bar .= "<br>Delete:
      <input type=checkbox name=\"delete[]\" value=\"$row[curios_id] silent\"> silent
      <input type=checkbox name=\"delete[]\" value=\"$row[curios_id] too_many\"> too many
      <input type=checkbox name=\"delete[]\" value=\"$row[curios_id] general\"> general";
        echo lib_tr(),'<td>',$t_text,"</td><td",
        ($row['visible'] == 'no' ? ' bgcolor=#FFCCCC>' : '>'),
        "$edit_bar</td></tr>\n";
    }
    echo $close;
}

print "</form>";


// Given a table name, sets the 'visible' field of the record with id=$id
// Sets it to yes, unless $not is not empty

function set_visible($table_name, $id, $not = 0)
{
    global $db;
    empty($not) ? $not = 'yes' : $not = 'no';
    $query = "UPDATE $table_name SET visible='$not' WHERE id=$id;";
    $sth = lib_mysql_query($query, $db, "set_visible($table_name,$id,$not) failed");
    $sth->rowCount();
}

// Now mail the reasons

function mail_reason($curio_id, $reason)
{
    return;
    $mail_to = basic_address('content editor');
    $mail_subject = "Deleted Prime Curio about $short by $username";
    $mail_text = "There has been a new curio submitted for your approval:
    \n$short [number_id=$number_id]\n\n$curio_text [$username]\n\n\n";
    $more_text = "You may see, make visible, edit... this entry at\n
        https://t5k.org/curios/page.php?number_id=$number_id&edit=1
    \nTechnical info about the submitter: \nConnected from: " .
    $_SERVER["REMOTE_ADDR"] .
    "\nvia: $HTTP_USER_AGENT\naccepts languages: $HTTP_ACCEPT_LANGUAGE\n";
    $mail_headers = "From: Prime Curios! automailer for <$address>\n";
    if (mail($mail_to, $mail_subject, $mail_text . $more_text, $mail_headers)) {
        $out .= 'The following mail has been successfully sent.  Your Curio
        will appear only after an editor has viewed and approved it.&nbsp; This
        may take up several weeks at times of peak submissions and when the
        editors get busy in their day jobs! (Remember that all curios unrelated
        to primes will be rejected!)<blockquote>' .
        nl2br(htmlentities("To: editor
        Subject: $mail_subject
        $mail_headers
        $mail_text")) . "</blockquote>";
        return(1);
    } else {
        $out .= lib_die("Sendmail failed! This should not happen, use a different
      method to let our technical editor know : " . basic_address('errors to'));
        return(0);
    }
}
