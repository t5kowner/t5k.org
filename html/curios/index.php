<?php

# Might be called with one of the following set
#
#   $start, $stop   log of range of numbers to index ($start-1 <= log10 < $stop)
#           (That was $stop = 2 will allow 99, but not 100)
#   $days       number of days to go back when considering things new
#   $changed    just list those new/modified in the last $changed days
#           $changed overrides $days.
#   $submitter  Just those with the given submitter
#   $xclass     Those of the given class 'unknown','other','composite','unit','prp','prime'

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies
$record_limit = 5000;    # Only allows so many Curios to be found per page

$start     = (isset($_REQUEST['start'])     ? $_REQUEST['start'] : '');
$stop      = (isset($_REQUEST['stop'])      ? $_REQUEST['stop'] : '');
$days      = (isset($_REQUEST['days'])      ? $_REQUEST['days'] : '');
$changed   = (isset($_REQUEST['changed'])   ? $_REQUEST['changed'] : '');
$submitter = (isset($_REQUEST['submitter']) ? $_REQUEST['submitter'] : '');
$xclass    = (isset($_REQUEST['xclass'])    ? $_REQUEST['xclass'] : '');
$edit      = (isset($_REQUEST['edit'])      ? $_REQUEST['edit'] : '');

# untaint

if (!is_numeric($start)) {
    $start = '';
}
if (!is_numeric($stop)) {
    $stop = '';
}
if (!is_numeric($days) or $days <= 0) {
    $days = 7;
}
if (!is_numeric($changed)) {
    $changed = '';
}
if (preg_match('/(\w+)/', $submitter, $match)) {
    $submitter = $match[1];
} else {
    $submitter = '';
}
if (!preg_match('/^(prime|unknown|other|composite|unit|prp)$/', $xclass)) {
    $xclass = '';
}
if (!preg_match('/^\d+/', $edit)) {
    $edit = '';
}


# FIX THIS SOON (MULTICOLUMNS)
$t_meta['add_lines'] = "<style>
	/* position list chunks side by side */
div.column2 {
direction: ltr;
float: left;
width: 49%;
}
/* position list chunks side by side */
div.column3 {
direction: ltr;
float: left;
width: 33%;
}
div.column4 {
direction: ltr;
float: left;
width: 24%;
}
div.column5 {
direction: ltr;
float: left;
width: 19%;
}
div.column6 {
direction: ltr;
float: left;
width: 16%;
}
ul.column  {
display: block;
list-style-type:none;
padding-left: 0px;
padding-right: 0px;
}
.columns {
-webkit-columns: 300px 2;
   -moz-columns: 300px 2;
        columns: 300px 2;
}
</style>";


# get started

$t_text = '';   # Will hold the index (and if stays blank we know
        # we have an error).

# Build page title
$t_title = 'Index: Numbers';  # title depends on query
if (is_numeric($start)) {
    $t_title .= " with $start";
    $t_title .= (is_numeric($stop) ? ($stop > $start + 1 ? " to $stop" : '') : ' or more');
    $t_title .= ' digits';
} elseif (is_numeric($stop)) {
    $t_title .= " with at most $stop digit" . ($stop != 1 ? 's' : '');
}
if (is_numeric($changed)) {
    $t_title .= " modified in the last $changed day(s)";
}
if ($submitter) {
    $t_title .= " with Curios! submitted by $submitter";
}
if (!empty($xclass)) {
    $t_title .= " which are '$xclass'";
}

# Build the variable part of the query
$where = '';
$having = '';
if (is_numeric($start)) {
    $start--;
    $where .= " log10 >= $start";
}
if (is_numeric($stop)) {
    if ($where) {
        $where .= ' AND';
    }
    $where .= " log10 < $stop";
}
if ($where) {
    $where .= " AND";  # More added below
}

# The cutoff date for marking whats new/modified (or displaying new/modified)
$cutoff = date("YmdHis", time() - $days * 24 * 60 * 60);
if (is_numeric($changed)) {
    $days = $changed;
    $cutoff = date("YmdHis", time() - $days * 24 * 60 * 60);
    $having = "\nHAVING modified >= DATE_SUB(NOW(),INTERVAL $days DAY) \n";
}

if ($submitter) {
  # So why doesn't Ca match Caldwell and Candle as well as Ca?
    $where .= "\ncurios.submitter LIKE BINARY :submitter AND ";
}

if (!empty($xclass)) {
#  $where .= " class = '$xclass' AND ";
    $where .= " class = :xclass AND ";
}

if ($where or $having) {
  # If neither $where or $having is not set, then we are doing the default index page
  # below; else we have something to index, lets do it!

  # Note cutoff is still used, though it has problems!

  # Okay, perform the query, build the index.  The +0 changes the timestamp format
    $query = "SELECT numbers.short, numbers.id, numbers.class, curios.submitter,
	MAX(curios.modified+0) as modified, MAX(curios.created+0) as created
	FROM numbers, curios WHERE $where
	curios.number_id = numbers.id AND curios.visible='yes'
	GROUP BY numbers.id $having " .
#   ORDER BY rank
    "ORDER BY numbers.sign DESC, IF(numbers.sign=\"-\",-1,1)*numbers.log10 ASC " .
        "LIMIT " . $record_limit;
  //    ORDER BY sign DESC, digits, log10 LIMIT ".$record_limit;

    try {
        $sth = $db->prepare($query);
        if (!empty($submitter)) {
            $sth->bindValue(':submitter', '%' . $submitter . '%');
        }
        if (!empty($xclass)) {
            $sth->bindValue(':xclass', $xclass);
        }
        $sth->execute();
    } catch (Exception $ex) {
        lib_die('index.php error (149): ' . $ex->getMessage());
    }

    $notes = 0;       # Count those with comments new/modified added
    $rows_count = 0;  # Count total rows (to limit page at 500)
    $max_width = 1;   # widest entry (to determine # of columns)
    $output = array();    # results are dumped in this array, then split up to the correct number of columns for output
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $name = $row['short'];
        $class = $row['class'];
        $width = strlen($row['short']);

      # If we are matching the submitter field, then make sure that it is a
      # real match--not just Ca mathching Caldwell
        if (!empty($submitter)) {
            if (!preg_match("/\b$submitter\b/", $row['submitter'])) {
                continue;
            }
        }

      # Okay, have a row to display; add new/modified note?
        $rows_count++;
        if ($row['modified'] >= $cutoff) {
            $note = '<img title="updated on ' . $row['modified'] . '" src="includes/gifs/clock.gif" width=16 height=16 alt=updated> ';
            $width = $width + 3;
        } else {
            $note = '';
        }
        if ($row['created'] >= $cutoff) {
            $note = '<img title="created on ' . $row['created'] . '" src="includes/gifs/new.gif" width=27 height=11 alt=new> ';
            $width = $width + 4;
        }
        if ($note <> '') {
            $notes++;
        }

      # Let's pop our row in an array that will be placed in a table

        if ($class == 'prime') {
            $note = '<img title="prime" src="includes/gifs/check.gif" width=16 height=16 alt=prime> ' . $note;
            $width = $width + 3;
        }
        if ($class == 'unknown') {
            $note = '<img title="unknown" src="includes/gifs/bulb.gif" width=16 height=16 alt=unknown> ' . $note;
            $width = $width + 3;
        }
        if (is_numeric($row['short'])) {
            $output[] .= sprintf(
                "    ><li><a class=mono href=\"page.php/%s.html%s\" title=\"$class\">%s</a> %s</li\n",
                $row['short'],
                (empty($edit) ? '' : "?edit=$edit"),
                $row['short'],
                $note
            );
        } else {
            $output[] .= sprintf(
                "    ><li><a class=mono href=\"page.php?number_id=%s\" title=\"$class\">%s</a> %s</li\n",
                $row['id'] . (empty($edit) ? '' : "&amp;edit=$edit"),
                $row['short'],
                $note
            );
        }

        if ($width > $max_width) {
            $max_width = $width;
        }
    }

  # Now divide this list of entries into two to six columns, placed side by side via css code

    $columns =  max(min(floor(64 / $max_width), 6), 2);
    $output_list = "\n<div class=\"column$columns\">\n  <ul class=column\n";  # Now build the table in $output_list
  # $offset = is_numeric($changed) ? 6 : 2;  # put more in the first column when $changed is set (less intro text)
    $offset = 0;
    $count = isset($output) ? ceil((count($output) + $offset) / $columns) : 0;
    if (isset($output)) {
        for ($i = 0; $i < count($output); $i++) {
            $output_list .= $output[$i];
            if (($i + 1) % $count == 0 and  $i + 1 != count($output)) {
                $output_list .= "  ></ul>\n</div>\n\n<div class=\"column$columns\">\n  <ul class=column\n";
            }
        }
    }
    $output_list .= "  ></ul>\n</div>\n\n<br style=\"clear: both\">\n";

  # Create the legend fo the list of entries we found
    $legend = '<p>';
    if ($notes > 0) {
        $legend = "Entries with the symbols
      <img src=\"includes/gifs/clock.gif\" width=16 height=16 alt=\"updated\">
      (updated) or
      <img src=\"includes/gifs/new.gif\" width=27 height=11 alt=\"new\">
      (new) have curios that have been modified, or are new, in the last $days days.&nbsp; ";
    }
    $legend .= 'Entries marked with a <img title="prime" src="includes/gifs/check.gif" width=16 height=16 alt=prime>
       (check) are primes.</p>';


    if (!isset($output) or count($output) == 0) {
        $t_text = "No such entries found.";
    } else {
        $t_text = $output_list;
        if ($rows_count >= $record_limit) {
            $t_text .= "(Hit internal limit of
	$record_limit records)<br>";
        }
        $t_text = $legend . $t_text . $legend;
    }
}



$t_meta['description'] = "This is the complete index for
      the prime curiosity collection--an exciting collection of curiosities,
      wonders and trivia related to prime numbers and integer factorization.
      Pleasant browsing for those who love mathematics at all levels; containing
      information on primes for students from kindergarten to graduate school.";

# So if we have output, lets give it with a jumpbar on too;
# otherwise we need a form (intro paragraph, jump bar plus search for a number)

$advanced = '<form action="' . $_SERVER['PHP_SELF'] . '">
	<table><tr>
	<td>Minimum number of digits <input type=text size=10 value=1 name=start></td>
	<td>Maximum number of digits <input type=text size=10 value=3 name=stop></td></tr>

	<tr><td>Submitted by <input type=text name=submitter value="' . $submitter . '" size=10></td>
	<td>Modified in the last <input type=text size=2 name=changed value=""> days.</td></tr>

	<tr><td colspan=2>Limit to: <input type=radio name=xclass value=prime> prime,
	<input type=radio name=xclass value=unknown ' . ($xclass = 'unknown' ? 'checked' : '') . '> unknown,
	<input type=radio name=xclass value=other ' . ($xclass = 'other' ? 'checked' : '') . '> other,
	<input type=radio name=xclass value=composite ' . ($xclass = 'composite' ? 'checked' : '') . '> composite,
	<input type=radio name=xclass value=unit ' . ($xclass = 'unit' ? 'checked' : '') . '> unit,
	<input type=radio name=xclass value=prp ' . ($xclass = 'prp' ? 'checked' : '') . '>prp, or
	<input type=radio name=xclass value=any ' . ($xclass = 'any' ? 'checked' : '') . '> any.
	</td></tr>
	<tr><td><input type=submit></td><td>&nbsp;</td></tr>
	</table></form>';

if (empty($t_text)) { # main index page needs comments
    $t_text = '<p id="index">This is the index for the "Prime Curios!" an exciting collection of
	curiosities, wonders and trivia related to prime numbers.&nbsp;  Pleasant
	browsing for those who love mathematics at all levels!</p>

  	<p class="border-bottom p-1 mx-3 border-top deep-orange lighten-5 text-center text-large" id="index">'
        . basic_index() .

#   $advanced.

    '<form action="' . $_SERVER['PHP_SELF'] . '" class="mt-5 mb-3">Or just see those modified in the last
	<input type=text size=2 name=changed value=7>days.
	<input type=submit value=go></form>

	<p>You may also just enter the number you want curios about:</p>

	<form method=post action="page.php" class="m-3">
	<input type=text size=30 name=short value="">
	<input type="submit" value="Search"> Enter Number<br>
	<b>Example:</b> <span class="deep-orange-text">23</span>
	</form>';
} else {
    $t_text = '<p class="border-bottom p-1 mx-3 border-top deep-orange lighten-5 text-center text-large" id="index">'
        . basic_index() . '</p>' . $t_text;
}

include("template.php");
