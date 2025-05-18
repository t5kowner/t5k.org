<?php

# Might be called with one of the following set
#
#   $match      Letters to index (b, or wxy)
#   $days       Number of days to go back when considering things new
#   $changed    Just list those new/modified in the last $changed days
#               $changed overrides $days.
#   $submitter  Just those with the given submitter  [NOT WORKING]


include("bin/basic.inc");  #
$dbh = basic_db_connect(); # Connects or dies

# Register and untaint the form variables:

$match     = isset($_REQUEST['match'])     ? $_REQUEST['match'] : '';
  # untainted just below
$days      = isset($_REQUEST['days'])      ? $_REQUEST['days'] : '';
if (!is_numeric($days) or $days <= 0) {
    $days = 7;  # How many days back do we lable new...
}
$changed   = isset($_REQUEST['changed'])   ? $_REQUEST['changed'] : '';
  $changed = preg_replace('/[^\d]/', '', $changed);
$submitter = isset($_REQUEST['submitter']) ?
    $dbh->quote($_REQUEST['submitter']) : '';

# Begin our work

$t_text = '';   # Will hold the index (and if stays blank we know
        # we have an error).

# Build the variable part of the query
$where = '';
$having = '';
if (!empty($match)) {
    if ($match == 'non-alphabetic') {
        $where .= " links.entry NOT REGEXP \"^[a-z]\"";
    } elseif (preg_match('/[a-z]*/', $match, $temp)) {
        $match = $temp[0];
        $where .= " links.entry REGEXP \"^[$match]\"";
    } else {
        $t_text .= "<p><font color=red>Illegal match string \"$match\"</font></p>\n";
        $match = '';
    }
}
if ($where) {
    $where .= " AND ";  # More added below
}

# Build page title
$t_title = (empty($match) ? 'Complete Glossary Index' : "Partial Glossary Index: $match");
if (is_numeric($changed)) {
    $t_title .= " modified in the last $changed day(s)";
} elseif ($submitter) {
    $t_title .= " submitted by $submitter";
}

# The cutoff date for marking whats new/modified (or displaying new/modified)
$cutoff = date("YmdHis", time() - $days * 24 * 60 * 60);
if (is_numeric($changed)) {
    $days = $changed;
    $cutoff = date("YmdHis", time() - $days * 24 * 60 * 60);
    $where .= "terms.modified >= DATE_SUB(NOW(),INTERVAL $days DAY) AND\n";
}

if ($submitter) {
  # This may match to much! Ca matches Caldwell and Candle as well as Ca.
    $where .= "\nterms.submitter LIKE BINARY '%$submitter%' AND ";
}

if ($where or $having) {
  # If neither $where or $having is set, then we are doing the default index page
  # below; else we have something to index, lets do it!

  # Note cutoff is still used, though it has problems!

  # Okay, perform the query, build the index
    $query = "SELECT terms.sort, links.entry,
	terms.modified+0 as modified, terms.created+0 as created
	FROM terms, links WHERE $where
	terms.sort = links.sort AND terms.visible='yes' AND terms.class!='curio'
	$having	ORDER BY links.entry LIMIT 500";
    $sth = lib_mysql_query($query, $dbh, 'Invalid query');

    $notes = 0;       # Count those with comments new/modified added
    $rows_count = 0;  # Count total rows (to limit page at 500)
    $old_sort = ''; # Do not link if the same as previous entry
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      # If we are matching the submitter field, then make sure that it is a
      # real match--not just Ca matching Caldwell
        if (!empty($submitter)) {
            if (!isset($row['submitter'])) {
                continue;
            }
            if (!preg_match("/\b$submitter\b/", $row['submitter'])) {
                continue;
            }
        }

      # Okay, have a row to display; add new/modified note?
        $rows_count++;
        if ($row['modified'] >= $cutoff) {
            $note = '<span title=' . $row['modified'] . '>(updated)</span>';
        } else {
            $note = '';
        }
        if ($row['created'] >= $cutoff) {
            $note = '<span title=' . $row['created'] . '>(new)</span>';
        }
        if ($note <> '') {
            $notes++;
        }

      # Let's display our row!
        if ($old_sort == $row['sort']) {
            $t_text .= " &nbsp; &nbsp; " . $row['entry'] . "<BR>\n";
        } else {
            $old_sort = $row['sort'];
            $t_text .= sprintf(
                "<li><a class=index href=xpage/%s.html>%s</a> %s<BR>\n",
                $row['sort'],
                $row['entry'],
                $note
            );
        }
    }

    if (empty($t_text)) {
        $t_text = "No such entries found.";
    } else {
        $t_text = "<ul>$t_text</ul>";
        if ($rows_count >= 500) {
            $t_text .= "<font size=-1>(Hit internal limit of 500
	records)</font><br>";
        }
        if ($notes > 0) {
            $t_text .= "<font size=-1>(Entries with the comments 'new'
	or 'modified' are new, or have been modified in the last
	$days days)</font>";
        }
    }
}


$t_meta['description'] = $t_title . " for the PrimePages' Prime Glossary.
      This glossary contains information on primes for
      students from kindergarten to graduate school.  It lists types
      of prime numbers, provides links to references, defines many
      of the key terms involved in the search for record primes.";

$header_ = '';
if (empty($t_text)) { # main index page needs comments
    $header_ = <<< HERE
<p>The "Prime Glossary" is an attempt to collect the most basic definitions about
primes, and make them accessible to students and seekers of all ages. Feel
free <a href="/primes/includes/mail.php">to comment</a>. New definitions are
added regularly, be sure to let us know of any that should be added, and
especially of any you would like to write.</p>
HERE;
    $t_text .= "<form action=\"index.php\" method=post>Or just see those modified in the last
	<input type=text size=2 name=changed value=7>days.
	<input type=submit value=go></form>";
}

# Add the index jumb bar
$t_text = "$header_ <div class=\"lead font-weight-bold\" id=\"index\">" . basic_index() . '</div>' . $t_text;

include("template.php");
