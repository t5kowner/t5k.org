<?php

// Humm!

include("../bin/basic.inc");
$db = basic_db_connect(); # Connects or dies; must come before admin_html_head

include('admin.inc');
echo admin_html_head('Special Functions');

$timenow = getdate();  # Used below in ago
#  print_r($timenow);

// not_yet_visible();

no_associated_curios(60);

echo admin_html_end();

function no_associated_curios($x_days_old)
{
// If $daysold is empty (blank or 0), then this routine shows all of the
// numbers without curios.  If it is a positive integer, it will delete
// all of these curios which are more than $x_days_old days old.
    global $db;

    echo "<h3>Numbers with no Curios</h3>";

    echo "Because the curios database stores numbers in a separate database, it is
	possible to have numbers with no curios.  (For example, if a curio is deleted, the
	number currently remains behind.)  This does not bother the pages at all, but
	why store numbers that are not used? 

	<P>Creating this list is a painfully slow process--give the server a moment...";

    global $timenow;
    $cutoff = $timenow[0] - $x_days_old * 24 * 60 * 60;  # In seconds since UNIX epoch
// Seems to be displaying the right ones.  Delete code not yet added.

  # Okay, perform the query, build the index
    $query = "SELECT numbers.short, numbers.id, numbers.long_,
	UNIX_TIMESTAMP(numbers.modified) as modified,
	COUNT(curios.id) AS total
        FROM numbers LEFT JOIN curios
	ON curios.number_id = numbers.id
	GROUP BY numbers.id
	HAVING total = 0 AND modified < $cutoff
	ORDER BY numbers.modified
        LIMIT 500";
    $sth = lib_mysql_query($query, $db, 'Invalid query', $query);

    $table_start = "<table borderColor=\"$GLOBALS[drkcolor]\"
        cellSpacing=0 cellPadding=0 border=1>
        <tr><td><table cellpadding=3>\n";
    $table_end = "</table>\n</table>\n";

    echo $table_start;
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $name = $row['short'];
        $id = $row['id'];
        $url = sprintf(
            "<a class=index href=\"../page.php?number_id=%s&amp;edit=1\">%s</a>",
            $id,
            $name
        );
        print lib_tr() . "<td>$url<br>\n" . EditorsLine($row['id'], 'numbers') . "</td>\n";
        print "<td>(" . ago($row['modified']) . ") $row[long_]</td></tr>\n";
    }
    echo $table_end;
}

function ago($unixtime)
{
#  $timenow = getdate();
    global $timenow;
    $t = $timenow[0] - $unixtime;
    if ($t < 60) {
        return "$t seconds";
    } $t = intval($t / 60);
    if ($t < 90) {
        return "$t minutes";
    } $t = intval($t / 60);
    if ($t < 30) {
        return "$t hours";
    } $t = intval($t / 24);
    if ($t < 60) {
        return "$t days";
    } $t = intval($t / 30.4375);
    if ($t < 20) {
        return "$t months";
    } $t = intval($t / 12);
    return "$t years";
}

function not_yet_visible()
{
    global $db;
    echo "<h3>Curios that are not yet visible:</h3>";

  # Okay, perform the query, build the index
    $query = "SELECT numbers.short, curios.id, curios.modified, curios.text
        FROM numbers, curios WHERE
        curios.number_id = numbers.id AND curios.visible='no'
        ORDER BY sign, log10 LIMIT 500";
    sth = lib_mysql_query($query, $db, 'Invalid query', 'warn');

    $table_start = "<table borderColor=\"$GLOBALS[drkcolor]\"
        cellSpacing=0 cellPadding=0 border=1>
        <tr><td><table cellpadding=3>\n";
    $table_end = "</table>\n</table>\n";

    echo $table_start;
    while ($row = $sth->fetch()) {
        $name = $row['short'];
        $url = preg_replace("/ /", '%20', $row['short']);
        $url = sprintf(
            "<a class=index href=\"../page.php/%s.html?edit=1\">%s</a>",
            $url,
            $row['short']
        );
        print lib_tr() . "<td>$url<br>\n" . EditorsLine($row['id'], 'curios') . "</td>\n";
        print "<td>" . $row['text'] . "</td></tr>\n";
    }
    echo $table_end;
}

function EditorsLine($id, $TableName)
{
  # When the user adds 'edit=1' to the path, then this line is added before
  # each curio.  I am not sure the edit number is necessary.
    $out = "<A href=\"index.php?xx_TableName=$TableName&amp;xx_edit=$id\">edit</A>
        <A href=\"index.php?xx_TableName=$TableName&amp;xx_delete=$id&amp;xx_action=confirmed\">delete</A>
        <A href=\"index.php?xx_TableName=$TableName&amp;xx_view=$id\">view</A>";
    return "[$out]";
}
