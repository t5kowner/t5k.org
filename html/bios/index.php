<?php

#t# ready?

$t_submenu =  "index";

# Might be called with one of the following set
#
#   $match      Letters to index (.g., b, or wxy)
#   $days       Number of days to go back when considering things new
#   $changed    Just list those new/modified in the last $changed days
#               $changed overrides $days.
#   $type       Just those of the given type (program, person...)
#   $edit       If set, displays those with no primes, those hidden, and add edit link to URLs

# Begin our work

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

# Let editors see those without primes, no one else can (should also pass this flag along...)
$edit = (isset($_REQUEST['edit']) ? htmlentities($_REQUEST['edit']) : '');
$match = (isset($_REQUEST['match']) ? htmlentities($_REQUEST['match']) : '');

# How many days back do we lable new, list on the just new...
$days = (isset($_REQUEST['days']) ? htmlentities($_REQUEST['days']) : '7');
if (!is_numeric($days) or $days <= 0) {
    $days = 7;
}

$t_text = '';   # Will hold the index (and if stays blank we know
        # we have an error).

# Name the buttons (as names show up in $type form variable)
$btn_All_Programs   = 'All Programs';
$btn_All_Projects   = 'All Projects';
$btn_Those_Modified = 'Those Modified';
$btn_Search_Entries = 'Search prover-accounts:';
$btn_Search_Code    = 'Search for proof-code:';

# Build the variable part of the query
$where = '';
if (!preg_match('/^(\w+|non-alphabetic)$/', $match)) {
    $match = '';  # untaint $match
}
if (!empty($match)) {
    if ($match == 'non-alphabetic') {
        $where .= " person.surname NOT REGEXP \"^[a-zA-Z]\"";
    } else {
      # surname is binary, and some used lower-case (but the index is upper-case)...
        $temp = strtolower($match) . strtoupper($match);
        $where .= " person.surname REGEXP \"^[$temp]\"";
    }
}

$type = (isset($_REQUEST['type']) ? htmlentities($_REQUEST['type']) : '');
if ($type == $btn_All_Programs) {
    $type = 'program';  # When type is set by an input
}
if ($type == $btn_All_Projects) {
    $type = 'project';  # form button it must be readable
}
if ($type == 'person' or $type == 'program' or $type == 'project' or $type == 'other') {
    if ($where) {
        $where .= " AND";  # More added below
    }
    $where .= "type = '$type'";
} else {
    $type = '';
}

$changed = (isset($_REQUEST['changed']) ? htmlentities($_REQUEST['changed']) : '');
# The cutoff date for marking whats new/modified (or displaying new/modified)
$cutoff = date("YmdHis", time() - $days * 24 * 60 * 60);
$having = '';
if (is_numeric($changed)) {
    $days = $changed;
    $cutoff = date("YmdHis", time() - $days * 24 * 60 * 60);
    $having = "\nHAVING modified >= DATE_SUB(NOW(),INTERVAL $days DAY) \n";
}

# Build page title
if ($match == 'non-alphabetic') {
    $match = 'non-alphabetic';
}
$t_title = (empty($match) ? 'Prover-Account Database' : 'Partial Index:' . " $match");
if ($type) {
    $t_title .= ' of $type';
}

if (is_numeric($changed)) {
    $t_subtitle = "Entries modified in the last $changed day(s)";
}


if ($where or $having) {
  # If neither $where or $having is set, then we are doing the default index page
  # below; else we have something to index, lets do it!

  # Note cutoff is still used, though it has problems!

  # Okay, perform the query, build the index
  # Fix this

  # Only show those with primes in the list (unless the $edit flag is set)
    if (empty($edit)) {
        $where .= (empty($where) ? '' : ' AND ') . '(person.PrimesTotal > 0 OR person.type!=\'person\')';
    }
    $query = "SELECT modified, created, id, name, PrimesActive, PrimesTotal, surname, NameError
	FROM person " . (empty($where) ? '' : "WHERE $where ") . "
	$having
	ORDER BY UPPER(person.surname) LIMIT 5000";
    $sth = lib_mysql_query($query, $db, 'Invalid query');
   # echo $query;

    $output = array();    # put names into a list first, then divide into columns
    $notes = 0;       # Count those with comments new/modified added
    $rows_count = 0;  # Count total rows (to limit page at 2000)
    $stars_showing = 0;   # No reason to explain the stars if they are not showing.
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
      # If they have not submitted correct name information, skip them
        if (!empty($row['NameError']) and empty($edit)) {
            continue;
        }

      # Okay, have a row to display; add new/modified note?
        $rows_count++;
        if ($row['modified'] >= $cutoff) {
            $note = '<span title=' . $row['modified'] . '>(updated)</span>';
        } else {
            $note = '';
        }

        if ($row['created'] >= $cutoff) {
            $note .= '<span title=' . $row['created'] . '>(new)</span>';
        }
        if ($row['PrimesTotal'] == 0) {
            $note .= ' <font color=red>(no primes!)</font> ';
        }
        if (!empty($row['NameError'])) {
            $note .= " <font color=red>($row[NameError])</font> ";
        }

        if ($row['surname'] == 'Unknown') {
            continue;
        }
        if ($row['surname'] == 'System') {
            continue;
        }
        if ($note <> '') {
            $notes++;
        }

      # Add stars
        if ($row['PrimesActive'] > 1) {
            $note = ' ' . $note;
            $stars = floor(log($row['PrimesActive']));
            $stars_showing++;
            while ($stars-- > 0) {
                $note = '*' . $note;
            }
        }

      # Let's display our row!  For below, must start <li and end </li>
        $output[]  .= sprintf(
            "> <li><a class=index href=\"page.php?id=%s\">%s</a>\n %s</li",
            $row['id'] . (empty($edit) ? '' : "&edit=$edit"),
            $row['name'],
            $note
        );
    }

  # Now divide this list of entries into columns which will helpfully flow to side by side

    $col_format = 'class="col-12 col-md-6 col-lg-4 col-xl-3 my-4"';
    $temp = "\n<div class=\"row\">\n<div $col_format>  <ul \n";  # Now build the table in $temp
    $columns = 4;
    $count = isset($output) ? ceil(count($output) / $columns) : 0;
    for ($i = 0; $i < count($output); $i++) {
        $temp .= $output[$i];
        if (($i + 1) % $count == 0 and $i + 1 < count($output)) {
            $temp .= "  ></ul>\n</div>\n\n<div $col_format>\n  <ul class=column\n";
        }
    }
    $temp .= "  ></ul>\n</div>\n</div>\n";

  // Now the above is either the list of <li> entries, or empty.

    if ($rows_count = 0) {
        $t_text = "No such entries found.";
    } else {
        $t_text = $temp;

        if ($rows_count >= 2000) {
            $t_text .= "<div class=technote>(Hit internal limit of 2000 records)</div>\n";
        }
        if ($notes > 0) {
            $t_text .= "<div class=technote>(Entries with the comments 'new' or 'modified' are new, or
	have been modified in the last $days $days).</div>\n";
        }
        if ($stars_showing > 0) {
            $t_text .= "<div class=technote>(The stars * after the names roughly indicate the
	number of primes each prover has on the current list.)</div>\n";
        }
    }
}


$t_meta['description'] = $t_title . " from the Prime Pages' list of
  provers, programs and projects involved in the search for titanic
  primes.  We list thse that have found the current prime number records.";

$header_ = '';
if (empty($t_text)) { # main index page needs comments
    $header_ = '<div style="margin-top: 10px; margin-bottom: 10px">The complete index for the provers, programs
	and projects involved in the search for the largest known primes. These pages list the number of
	primes each one has had on the list of the largest known primes, along with their current number,
	score and rank.  It also lists any descriptive or contact information they have chosen to share.</div>';

    $t_text .= '<h4 class="mt-5">Quick searches:' . "</h4>\n" . <<< HERE
<div class="row">

  <div class="col pl-4 mt-3">
    <form action="index.php" method="post">
      <input type="submit" class="btn btn-primary p-2" value="$btn_All_Programs" name="type"> &nbsp; &nbsp; &nbsp;
      <input type="submit" class="btn btn-primary p-2" value="$btn_All_Projects" name="type">
    </form>
  </div>

</div>
<hr>
<div class="row">

  <div class="col pl-4">
    <form action="index.php" method="post">
      <input type=submit class="btn btn-primary p-2" value="$btn_Those_Modified">
HERE;

    $t_text .= ' &nbsp; ' . sprintf(
        'in the last %s days',
        '&nbsp; <input type="text" size="2" name="changed" value="7">'
    ) . <<<HERE
    </form>
  </div>

</div>
<hr>
<div class="row">

  <div class="col pl-4">
    <form method="post" action="search.php">
      <input type="submit" class="btn btn-primary p-2 mr-3" value="$btn_Search_Entries">
      <input type="text" name=searchstring maxLength="256" size="30">
    </form>
  </div>

</div>
<hr>
<div class="row">

  <div class="col pl-4">
    <form method=post action="code.php">
      <input type="submit" class="btn btn-primary p-2 mr-3" value="$btn_Search_Code">
      <input type="text" name="code" maxLength="6" size="4">
    </form>
  </div>

</div>
HERE;
}

$t_text .= '<h4 class="mt-5">Top 20 prover-account pages:</h4>
<ul>
  <li><a href="top20.php?type=person&amp;by=PrimesRank">ranked by number of primes on the list</a>
  <li><a href="top20.php?type=person&amp;by=ScoreRank">ranked by score of primes on the list</a>
</ul>


<div>
    <h4 class="mt-5">Other links:</h4>
    <ul>
        <li><a href="edit.php">Edit Prover-Account</a>
        <li><a href="newprover.php">Create a New Prover-Account</a>
    </ul>
</div>';

# Add the index jump bar
$t_text = "$header_ " . basic_index() . '<p class=first>' . $t_text;

include("template.php");
