<?php

# Might be called with one of the following set
#
#   $days       Number of days to go back when considering things new
#   $changed    Just list those new/modified in the last $changed days
#               $changed overrides $days.
#   $edit       Shows invisibles too, links to pages in edit mode

$t_submenu = 'Index';

# Register the form variables:

if (!empty($_REQUEST['days'])) {
    $days     = $_REQUEST['days'];
} else {
    $days = 60;
}
$days = preg_replace('/[^\d]/', '', $days);
if (!empty($_REQUEST['edit'])) {
    $edit     = $_REQUEST['edit'];
} else {
    $edit = '';
}
$edit = preg_replace('/[^\d]/', '', $edit);
if (!empty($_REQUEST['changed'])) {
    $changed  = $_REQUEST['changed'];
} else {
    $changed = '';
}
$changed = preg_replace('/[^\d]/', '', $changed);

# Begin our work

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

if (!is_numeric($days) or $days <= 0) {
    $days = 60;  # How many days back do we label new...
}

$t_text = '';   # Will hold the index (and if stays blank we know we have an error).

# Build page title
$t_title = "Top Twenty's Complete Index";
if (is_numeric($changed)) {
    $t_title = "Entries modified in the last $changed day(s)";
}

# Build the variable part of the query

$where = "WHERE visible = 'yes'";
if (!empty($edit)) {
    $where = '';
}

# The cutoff date for marking whats new/modified (or displaying new/modified)
$cutoff = date("Y-m-d H:i:s", time() - $days * 24 * 60 * 60);
$having = '';
if (is_numeric($changed)) {
    $days = $changed;
    $cutoff = date("Y-m-d H:i:s", time() - $days * 24 * 60 * 60);
    $having = "\nHAVING modified >= DATE_SUB(NOW(),INTERVAL $days DAY) \n";
}

  # Note cutoff is still used, though it has problems!

  # Okay, perform the query, build the index
  # Fix this

  $query = "SELECT modified, created, id, name, visible, purpose, type
	FROM archivable $where
	$having
	ORDER BY name LIMIT 2000";

  // print "<pre>$query</pre>";
  $sth = lib_mysql_query($query, $db, 'Invalid query, contact Chris');

  $output = array();
  $notes = 0;       # Count those with comments new/modified added
  $rows_count = 0;  # Count total rows (to limit page at 2000)
  $old_sort = ''; # Do not link if the same as previous entry
while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    # Okay, have a row to display; add new/modified note?
    $rows_count++;
    $note = '';
    if ($row['modified'] >= $cutoff) {
        $note .= '<span title="' . $row['modified'] . '">(modified)</span>';
    }
    if ($row['created'] >= $cutoff) {
        $note .= '<span title="' . $row['created'] . '">(new)</span>';
    }
    if ($row['visible'] == 'no') {
        $note .= " <font color=red>(not visible, $row[type])</font>";
    } elseif ($row['visible'] != 'yes') {
        $note .= " <font color=red>(visible flag is undefined, $row[type])</font>";
    }
    # the enum column purpose says whether this entry is for top20 pages, lint, or both.
    if (!preg_match('/top20/', $row['purpose'])) {
        $note .= " <font color=red>(not a top20 entry, $row[type])</font>";
    }
    if ($note <> '') {
        $notes++;
    }

    # Let's pop our row in an array that will be placed in a table
    if (!empty($edit)) {
        $edit = '&edit=1';
    }
    # the enum column purpose says whether this entry is for top20 pages, lint, or both.
    if (preg_match('/top20/', $row['purpose']) or !empty($edit)) {
        $output[] = sprintf(
            "    ><li><a href=\"page.php?id=%s$edit\">%s</a> %s</li\n",
            $row['id'],
            $row['name'],
            $note
        );
    } else {
        $output[] = sprintf("    ><li><font color=red>%s %s</font></li\n", $row['name'], $note);
    }
}

  # Now divide this list of entries into two columns, placed side by side via css code

  $temp = "\n<div class=\"column\">\n  <ul class=column\n";  # Now build the table in $temp
  $columns = 2;
  $offset = is_numeric($changed) ? 6 : 2;  # put more in the first column when $changed is set (less intro text)
  $count = isset($output) ? round((count($output) + $offset) / $columns) : 0;
for ($i = 0; $i < count($output); $i++) {
    $temp .= $output[$i];
    if ($i % $count == 0 and $i != 0) {
        $temp .= "  ></ul>\n</div>\n\n<div class=\"column\">\n  <ul class=column\n";
    }
}
  $temp .= "  ></ul>\n</div>\n\n<br style=\"clear: both\">\n";

if (count($output) == 0) {
    $t_text = "<blockquote>No such entries found.</blockquote>\n";
} else {
    $t_text = $temp;
    if ($rows_count >= 2000) {
        $t_text .= "<font size=-1 style=error>(Hit internal limit of 2000
	records)</font><br>";
    }
    if ($notes > 0) {
        $t_text .= "<div class=technote>
        Entries with the comments 'new' or 'modified' are new, or have been modified in the last $days days
	</div>\n";
    }
}

  # Done!

$t_meta['description'] = "$t_title The Top 20 is a collection of
	lists of record primes.  These records correspond to archivable forms and classes
	defined for the list of the 5000 Largest Known primes.";

  $t_text .= "<br><form action=index.php>View those modified in the last $days days
	&nbsp; <input type=text size=3 name=changed value=$days> &nbsp;
	&nbsp; <input type=submit value=Search></form>\n";

# Add the top of the page's text

if (empty($changed)) {
    $t_text = "<p>The Prime Pages keeps a list of the 5000 largest known primes,
     plus a few each of certain selected archivable forms and classes. These
     forms are defined in this collection's home page. Below we list those
     archivable forms and classes, each linked to an explanation and a list of
     records for that type of prime.</p>\n" . $t_text;
} else {
    $t_text = "<p>Entries modified in the last $days day(s)</p>\n" . $t_text;
}

include("template.php");
