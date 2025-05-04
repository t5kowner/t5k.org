<?php

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

# First we go through all of the curios and get their submitter fields.
# We use this to build the array $Count (key=submitter, value=number)
# and Score (key=submitter, value=weighted number).
# To do this we  split the submitter fields up so 'Caldwell and
# Georgia' gives 1/2 credit to each.

# We allow the URL to include '?show_raw=1' to see the unsplit data
if (!empty($_REQUEST['show_raw'])) {
    $show_raw = 1;
    $t_text = "<h3>The raw data</h3>\n<hr>rank number name<hr>";
} else {
    $t_text = '';
}

$query = "SELECT submitter, COUNT(*) as total
	FROM curios WHERE visible='yes' GROUP BY submitter
	ORDER BY total DESC, submitter";
$sth = lib_mysql_query($query, $db, 'Invalid query in this page view');

# These three used to set the ranks of each
$rank = 1;      # last rank printed
$last_num = 0;      # Number the last submitter had
$submitter_count = 1;   # Submitters to this point (the rank if no ties)

$total_curios = 0;
while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
    if ($row['submitter'] == null) {
        $row['submitter'] = '(NULL = submitter left blank)';
    } else {
      # Need to allow &#248; in a name...
        $temp = explode(
            '|',
            preg_replace("/(\s+and\s+|\s*,\s+)/", '|', $row['submitter'])
        );
        foreach ($temp as $name) {
            $Count[$name] = (isset($Count[$name]) ? $Count[$name] : 0) + $row['total'];
            $Score[$name] = (isset($Score[$name]) ? $Score[$name] : 0) + $row['total'] / count($temp);
        }
    }
    if ($row['total'] != $last_num) {
        $rank = $submitter_count;
    }
    $submitter_count++;
    $last_num = $row['total'];
    if (!empty($show_raw)) {
        $t_text .= "$rank $row[total] $row[submitter]<br>\n";
    }
    $total_curios += $row['total'];
}
if (!empty($show_raw)) {
    $t_text .= "<hr>$total_curios total<br><hr>";
}


# Okay, have the raw, have $Count and $Score, lets output the top
# in a table.  Tose with $cutoff or less will be printed in a list, not a table.
# $cutoff might be altered via the URL.

if (empty($cutoff)) {
    $cutoff = 5;
}

# Sort them before printing
arsort($Score);

# Format the table to hold the top scores.
$t_text .= "
	<table class=\"mx-auto text-center brdr td4 mb-3\">\n<tr class=\"$mdbltcolor\"><th>rank</th><th>number</th>
	<th>who</th><th>total</th></tr>
	<caption style=\"caption-side:top\" class=\"text-center\"><b>The Top Ranked Curio Submitters</b></caption>";
$done = "\n<tr class=\"$mdbltcolor\"><th>&nbsp;</th><th>&nbsp;</th>
	<th>&nbsp;</th><th>&nbsp;</th></tr></table>\n\n";

# again need these three to assign ranks (especially in case of a tie)

$rank = 1;
$submitter_count = 1;
$last_num = 0;
foreach ($Score as $name => $value) {
    if ($value != $last_num) {
        $rank = $submitter_count;
    }
    $submitter_count++;
    $last_num = $value;
    if ($value > $cutoff) {
        $t_text .=  "<tr align=right><td>$rank</td>" .
        "<td title=$value>" . sprintf("%.2f", $Score[$name]) . "</td>
	<td align=left><a href=\"ByOne.php?submitter=$name\">" .
        " $name</a> " . basic_sub_address($name) . "</td>
	<td>$Count[$name]</td></tr>\n";
    } else {
        $t_text .= $done;
        $done = '';
        $bottom[intval($value)] = (isset($bottom[intval($value)]) ?
        $bottom[intval($value)] : '') . "$name" . basic_sub_address($name) . ", ";
        $b_rank[intval($value)] = $rank;
    }
}

# The list of the top few is done, let's list the rest in the variable
# $rest, which will be combined with $t_text below when words are added.

$rest = '';
for ($i = $cutoff; $i >= 1; $i--) {
    if (!empty($bottom[$i])) {
        $bottom[$i] = preg_replace("/, $/", '.', $bottom[$i]);
        $rest .= "<div class=\"$mdbltcolor p-2 mt-4 mb-2 w-50 font-weight-bold\">Those with $i (rank $b_rank[$i]):</div>
	<blockquote>\n" . $bottom[$i] . "\n</blockquote>\n";
    }
}

# Now finish the page:

date_default_timezone_set("UTC");
$d = date("l M d, Y h:i a ");

$t_text = <<< TEXT
  <p>Ever wonder how many of the $total_curios Prime Curios! each of the $submitter_count
submitters contributed?&nbsp; We can answer that!&nbsp; But how do we count?&nbsp; We
decided that if a team of three folks together submit a curio, each should get
one-third credit.&nbsp;  This leads to the numbers below.</p>


<p class="small border-top">(Updated $d UTC.)</p>

$t_text

  <p>Important note: We would greatly prefer to list the top submitters by
quality, not quantity.&nbsp; An exceptional curio could be worth 133 poor
ones.&nbsp;  But this program cannot measure quality, only you can.&nbsp;
So as we list the rest of these names, do not view them as the 'lesser'
submitters!</p>

  $rest

  <p>We would like to say thanks to each one of you who submitted curios,
corrections, suggestions, and to those of you who took time to read some
of our pages.&nbsp; Thanks for making all of the time worthwhile.</p>

  <p>The Editors.</p>

TEXT;

# The template uses $t_text for the text, $t_title for the title, ...

$t_title = "The Top Submitters";
$t_meta['description'] = "On this page we list the 'top submitters' (by number
  of submissions) to the primes curios collection.  The number of submissions,
  a score, their rank, and links to each of their works are included.";

include("template.php");
