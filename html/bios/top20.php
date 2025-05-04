<?php

if (file_exists("/var/www/html/TESTSITE")) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

$t_submenu =  "Top 20 Bios";

# Want to be able to print various top20 lists...  should be able to specify
# both the person.type (enum: person, program, project...; as 'type')
# and what to sort by (PrimesRank, ScoreRank, PrimesTotal, ScoreTotal; as
# 'by'); and why not also the center element of the list by 'at' (a rank?)
# and say which to make bold by 'id' (both integers or ignored)

# type defaults to person, by to primes number

include("bin/basic.inc");   # Basic funtionality
$db = basic_db_connect();   # Connects or dies

# Begin our work

$t_text = '';   # Will hold the text

# Build the variable part of the query

$where = '';
$limit = '';
$order = '';

# First, what column do we sort by?

$type = (isset($_REQUEST['type']) ? htmlentities($_REQUEST['type']) : 'person');
include("top20.inc");       # Various output strings (refers to $type)

# and where are we?  sort how?

$at   = (isset($_REQUEST['at'])   ? htmlentities($_REQUEST['at'])   : 0);
$at = preg_replace('/[^\d]/', '', $at);  # $at should be an integer or blank

$by   = (isset($_REQUEST['by'])   ? htmlentities($_REQUEST['by'])   : 'ScoreRank');
if (preg_match('/^(PrimesRank|ScoreRank)$/', $by)) {
  # Ranks, especially the lowest (for zero active primes) need tie breakers
    $TieBreaker = preg_replace('/Rank$/', 'Total', $by);
    $order = "ORDER BY $by, $TieBreaker DESC";
    if ($type == 'person') {
        $start = max(1, $at - 10); # ''-10 = -10
        $at = $start + 10;
        $stop  = $start + 19;
    } else {
        $start = 1;
        $stop = 20;
    }
  # Pehaps we'd like to center at a certain value?
    $where = "$by <= $stop AND $by >= $start";
    $rank_is = 'rank';  # Sometimes the first output column is rank
} elseif (preg_match('/^(PrimesTotal|ScoreTotal|ScoreNormal)$/', $by)) {
    $order = "ORDER BY $by DESC";
    $limit = "LIMIT 20";
} elseif (!empty($by)) {
    lib_die("Sorry, but '$by' is not a legal choice for \$by");
}
#if ($by != 'ScoreRank') $order .= ', ScoreRank'; # break ties
$order .= (empty($order) ? 'ORDER BY ' : ', ') . 'ScoreTotal DESC';

# Only show those with primes in the list (but don't create a $where
# if none exisit!)
$where .= (empty($where) ? '' : ' AND ' . 'person.PrimesTotal > 0');

# Now what type are we showing?

if (preg_match('/^(person|program|project|other)$/', $type)) {
    $where = "type = '$type'" . (empty($where) ? '' : ' AND ') . $where;
} elseif (!empty($type)) {
    lib_die("Sorry, but '$type' is not a legal choice for \$type");
}

$temp = $by . $type;
if ((empty($by) or empty($type)) and !empty($temp)) {
    lib_die("Sorry, you have an error while specifying \$by and \$type");
    $where = '';
}

# Do the query

$id   = (isset($_REQUEST['id'])   ? htmlentities($_REQUEST['id'])   : '');

if (!empty($where)) {
    $key_column = $by;
    $The5000th = lib_get_column('prime.rank = 5000', 'prime', 'log(score)', $db);
    $query = "SELECT name, PrimesActive, ScoreActive, id, program_does, NameError, " .
    ($key_column == 'ScoreNormal' ? '' : "$by, ") . "
	floor(exp(ScoreActive-$The5000th)+0.5) as ScoreNormal
	FROM person WHERE $where $order $limit";
    $sth = lib_mysql_query($query, $db, 'Invalid query in this page view', $query);

  # Using tables three deep--two nested for nice border on list, one wrapped around
  # that to allow up and down list links.

    if ($type == 'person') {
        $t_type = 'person';
    } elseif ($type == 'program') {
        $t_type = 'program';
    } elseif ($type == 'project') {
        $t_type = 'project';
    } else {
        $t_type = 'other';
    }

    $t_text .= "<blockquote>\n <table>\n  <tr><td rowspan=2>\n" .
    "   <table class=\"table table-bordered text-bold table-sm table-hover\">\n" .
    "      <tr class=\"$mdbltcolor\"><th>" .
    ($by == 'ScoreNormal' ? 'normalized' : 'rank') . "</th><th>" . $t_type .
    "</th><th>primes</th><th>score</th></tr>\n";

  # Get the term

    $count = 0;
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['NameError']) and empty($edit)) {
            continue;   # If they do not submit a correct name, they do not get displayed
        }
        $name = $row['name'];
        $rank = $row[$by];
        $primes = $row['PrimesActive'];
        $score = $row['ScoreActive'];
        $nscore = $row['ScoreNormal'];
        $person_id = $row['id'];
        if ($type == 'program') {
            $does = "\n[" . preg_replace('/,/', ', ', $row['program_does']) . ']';
        } else {
            $does = '';
        }
        $temp = (!empty($id) and $row['id'] == $id) ? 'style="font-weight:bold"' : '';
      // I am leaving the primes aligned to the right for all languages
        $t_text .= "      " . lib_tr() . "<td align=right>$rank</td>
	<td dir=ltr align=left><a href=\"page.php?id=$person_id\" class=none>$name</a>$does</td>
	<td class=\"text-right\">$primes</td>
	<td title=\"$nscore\">" . sprintf('%.4f', $score) . "</td></tr>";
      # want a variable that is 0 when we have hit the bottom of the list:
        $seek_bottom = $primes + $score;
      # number shown
        $count++;
    }
    $t_text .= "    </table>\n";

  # Remove the at variable from the URI
    $URI = preg_replace('/&*([?&])at=\d+/', '', $_SERVER["REQUEST_URI"]);
    $URI = preg_replace('/&(by|type|person|id|at)/', '&amp;$1', $URI); # change & to &amp; in the URI
    $delim = (preg_match('/\?/', $URI) ? '&amp;' : '?');

  # Update the at variable in the URI (ist shows 20 starting with $at-10, so back up 30)
  # if $count is empty, something is wrong, don't go up...  But need to be able to go up if
  # we are at 12 (to see the top of the list)
    if ($type == 'person' and !empty($count) and ($up = $at - 11) > 0 and preg_match('/Rank$/', $by)) {
        $up = max($at - 20, 10);
        $temp = "<br><a href=\"$URI${delim}at=$up\" class=\"text-bold btn btn-indigo p-1 mt-3\">move up list &uarr;</a>";
    } else {
        $temp = '&nbsp;';
    }
  # Now add this to the outer of the three nested tables
    $t_text .= "  </td><td valign=top>$temp</td></tr>\n";

  # Finally add a "down link" (if necessary?)
    if ($type == 'person' and !empty($seek_bottom) and $down = $at + 20 and preg_match('/Rank$/', $by)) {
        $temp = "<a href=\"$URI${delim}at=$down\" class=\"text-bold btn btn-indigo p-1\" role=\"button\">move down list &darr;</a>";
    } else {
        $temp = '&nbsp;';
    }
  # Now add this to the outer of the three nested tables
    $t_text .= "  <tr><td valign=bottom>$temp</td></tr>\n </table>\n</blockquote>\n";

  # Add text to the top of the page

    $t_text = $top20_page_top . $t_text;
    $temp = ($by == 'PrimesRank' ? 'number of primes' : ( $by == 'ScoreNormal' ? 'normalized score' :  'score' ) );
    $t_title = "Top $type sorted by $temp";
} else {
    $t_title = 'The Top Twenty';
    $t_subtitle = 'Choose a Table to Display';
    $t_text = $top20_page_top . $t_text;
}

### Add extra text to the pages?

if (!empty($where)) {
    $t_text .= $top20_notes_head;
    if ($type == 'program') {
        $t_text .= $top20_program_notes;
    } elseif ($type == 'other') {
        $t_text .= $top20_other_notes;
    }
    $t_text .= ${'top20_' . $by . '_notes'};

    $t_text .= $top20_notes_tail;
}

include('template.php');
