<?php

# The goal is to display all Curios, we will not use the template or style sheet.
# These will be raw and simple. start, stop set digit ranges for displayed curios.

include("../bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

# regeister form variables

$start  = isset($_POST['start'])  ? $_POST['start']  :  1;
$number = isset($_POST['number']) ? $_POST['number'] : 20;

# The page can be massive, so we will print as we go.
$start  = ((is_numeric($start)  and $start  > 0) ? $start  :  1);
$number = ((is_numeric($number) and $number > 0) ? $number : 20);

# Build page title
$title = "$number Glossary Entries starting with # $start";
echo "<H2 align=center>$title</H2>\n";
echo date("h:i A l F dS, Y\n");
echo "<form method=post action=\"" . $_SERVER['PHP_SELF'] . "\">Show
	  <input type=text length=5 name=number value=$number>
  skipping first
  <input type=text length=5 name=start value=",$start + $number,">
  <input type=submit value=GO>\n</form>\n";

# Okay, perform the query, build the index
if (1) {
    $query = "SELECT name, text, refs_tr, id, sort,
	UNIX_TIMESTAMP(modified) AS modified,
	UNIX_TIMESTAMP(created) AS created
        FROM terms ORDER BY name LIMIT $start,$number";

    $sth = lib_mysql_query($query, $db, 'Invalid query');

    include("../bin/modify.inc");  # Modify the text entities like \pi

    $id = 0;
    $close = '';
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        if ($id <> $row['id']) {  # New number, add a number header
            echo $close,
            "\n<table width=100% bgcolor=$drkcolor cellspacing=2>\n",
            "<tr bgcolor=$medcolor><td width=80% colspan=2>",$row['name'],
            '</td><td align=center width=20%>id = ',$row['id'],"</td></tr>\n";
            $id = $row['id'];
            $close = "</table><BR>\n";
        }
        $text = modify_entities($row['text']);
        $created = date("M jS, Y\n", $row['created']);
        $modified = date("M jS, Y\n", $row['modified']);
        echo '<tr bgcolor=white><td colspan=3>',$text,"</td></tr>\n";
        echo "<tr bgcolor=$medcolor><td width=60%>",$row['sort'],
        "</td><td width=20% align=center>",
        $created,'</td><td align=center width=20%>',
        $modified,"</td></tr>\n";
    }
    echo $close;
}
