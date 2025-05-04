<?php

# Expects
#
#   $submitter  Just those with the given submitter
#
# Might be called with one of the following set
#
#   $days       number of days to go back when considering things new
#           (forced to be numeric)

# open connection to the database

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

# Get client data

$editor_action = ( isset($_REQUEST['edit']) and ctype_digit(strval($_REQUEST['edit'])) ? '&edit=' . $_REQUEST['edit'] : '');

$my_variables_digits       = '(days)';
$my_variables_alphanumeric = '()';
$my_variables_self_tested  = '(submitter)';
$my_variables_general      = '()';
security_scrub_variables(
    $my_variables_digits,
    $my_variables_alphanumeric,
    $my_variables_self_tested,
    $my_variables_general
);

$days      = (isset($_REQUEST['days'])      ? $_REQUEST['days'] : '');
$submitter = (isset($_REQUEST['submitter']) ? $_REQUEST['submitter'] : '');

# further untaints. We need to protect the html from $submitter, database will
# be protected by PDO's bindVariables.

if (!is_numeric($days) or $days <= 0) {
    $days = 7;    # How many days back do we label new...
}
$search_string = $submitter;  # to be escaped later
$submitter = htmlentities($submitter);

# get started

$record_limit = 5000;   # Only allows so many Curios to be found
$t_text = '';   # Will hold the Curios index (and if stays blank we know
        # we have an error).

# Build page title, headers
$t_title = "Submitter: $submitter";  # title depends on query
$t_meta['description'] = " :";
$t_subtitle = '(for the <A HREF="/">Prime Pages</A>\'
    Curios Collection)';

$t_text = <<< HERE
<p>Though quantity is not quality, the Prime Curios! submitters are listed by the
number they submitted on <a href="ByNumber.php">another page</a>.  This page is
about the one submitter '$submitter'.</p>\n
HERE;

$FAQ = "includes/FAQ.php";
if (empty($submitter)) {
    $t_text .= "<P><font color=red>You did not specify the submitters' name</font>";
} else {
  # First, get what we can from the database
    $query = "SELECT * FROM person WHERE lastname=:searchstring";
    $sth = $db->prepare($query);
    $sth->bindvalue(':searchstring', $search_string);
    $t_text .= "<p>We begin with the information on $submitter from our database on
	submitters:</p>";
  # Note: extra \n's and ' ''s are for HTML source readability!
    if ($sth->execute() and $row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $t_text .= "\n<table class=\"brdr td4 m-3\">
	<tr><th class=\"text-right $mdbltcolor\">'Last' name:</th><td>$row[lastname]</td></tr>
	<tr><th class=\"text-right $mdbltcolor\">Full name:</th><td>$row[name] &nbsp; </td></tr>
	<tr><th class=\"text-right $mdbltcolor\">E-mail:</th><td>" .
        (empty($row['email']) ? "(<a href=$FAQ#unknown>unknown</a>)" :
        ($row['visible'] == 'yes' ? $row['email'] :
        "(<a href=$FAQ#private>private</a>)")) . "</td></tr>
	<tr><th class=\"text-right $mdbltcolor\">Web page:</th><td>" .
        (empty($row['webpage']) ? 'none' :
        "<a href=\"$row[webpage]\">$row[webpage]</a>") . "</td></tr>
	<tr><th class=\"text-right $mdbltcolor\">Last altered:</th><td>" . date_($row['modified']) .
        "</td></tr>\n    </table>";
    } else {
        $t_text .= "<div class=\"m-3 p-3 alert alert-warning\">
	This name is not in the submitters database.&nbsp; There are
	several possible reasons.&nbsp; Perhaps
	we just did not enter the data yet.&nbsp; We may have no data on
	$submitter to enter.&nbsp; Is it spelled correctly?</div>";
    }

  # Call show_index to set global variables
    $temp = show_index($search_string, $db);  # Submitter already HTML exscaped
  # Note: extra \n's and ' ''s are for HTML source readability!
    $t_text .= "\n<p>Now we'll scan the curios database for curios attributed to" .
    " $submitter:</p>\n\n";
    $t_text .= "\n<table class=\"brdr td4 m-3\">\n
        <tr><th class=\"text-right $mdbltcolor\">First submitted:</th><td><B>" . date_($x_first) . "</B></td></tr>
        <tr><th class=\"text-right $mdbltcolor\">Last submitted:</th><td><b>" . date_($x_last) . "</b></td></tr>
        <tr><th class=\"text-right $mdbltcolor\">Last modified:</th><td><b>" . date_($x_modified) . "</b></td></tr>
        <tr><th class=\"text-right $mdbltcolor\">Total in collection:</th><td><b>$x_curios</b> curios
		about <b>$x_numbers</b> different numbers &nbsp; </td></tr>
        <tr><th class=\"text-right $mdbltcolor\">Unedited submissions:</th><td><b>$x_not_visible</b></td></tr>
        <tr><th class=\"text-right $mdbltcolor\">Submitted in last seven days:</th><td><b>$x_recent</b> (see 'unedited' in
		<a href=\"$FAQ#unedited_submission\">FAQ</a>)</td></tr>" .
        "</table>\n";
    $t_text .= "\n<p>The curios attributed to $submitter:</p>\n\n$temp";
}

function date_($timestamp)
{
  # Time stamp is 2003-01-03 15:20:41
    if (preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d)/", $timestamp, $matches)) {
      # remove leading zeros
        $matches[2] = preg_replace("/^0/", '', $matches[2]);
        $matches[3] = preg_replace("/^0/", '', $matches[3]);
        return $matches[2] . '/' . $matches[3] . '/' . $matches[1];
    } else {
        return($timestamp);
    }
}


function show_index($submitter, $db)
{
    global $x_first, $x_last, $x_numbers, $x_curios, $x_not_visible, $x_modified,
     $x_recent, $FAQ;
# These will hold stuff for submiter summary info on the top of the outputted
# HTML page.

  # Okay, perform the query, build the index

    $query = 'SELECT numbers.short, curios.submitter, numbers.id,
	MAX(curios.modified) as modified, MAX(curios.created) as created,
	COUNT(IF(curios.visible = \'yes\',1,NULL)) as visible,
	COUNT(*) as count,
	COUNT(IF(NOW()-created < 7000000,1,NULL)) as recent
	FROM numbers, curios
 	WHERE curios.submitter REGEXP :submitter AND
	curios.number_id = numbers.id
	GROUP BY numbers.id
	ORDER BY sign, log10 LIMIT ' . $GLOBALS['record_limit'];
    $sth = $db->prepare($query);
    $sth->bindValue(':submitter', '(,| and |^) *' . $submitter . ' *(,| and |$)');
    $sth->execute();

  # print "<li>$query<li>:submitter= ".'\'(,| and |^) *'.$submitter.' *(,| and |$)\'';

    $x_curios = 0;    # Total number of curios attributed to $submitter
    $x_numbers = 0;   # These are about how many numbers?
    $x_not_visible = 0;   # How many are not visible (usually 0)
    $x_recent = 0;    # Counts submissions in the last 7 days
            # (7 is fixed--by "7 curios per 7 days" rule)

    $cutoff = date("Y-m-d H:i:s", time() - $GLOBALS['days'] * 24 * 60 * 60);
    $out = '';

    $notes = 0;
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $name = $row['short'];

        $x_curios += $row['count'];
        $x_recent += $row['recent'];
        $x_numbers++;

      # Something new? Put in in $note
        if ($row['modified'] >= $cutoff) {
            $note = '<span title="' . $row['modified'] . '">(updated)</span>';
        } else {
            $note = '';
        }
        if ($row['created'] >= $cutoff) {
            $note = '<span title="' . $row['created'] . '">(new)</span>';
        }
        if ($note <> '') {
            $notes++;
        }

        if (empty($x_first) or $row['created'] <= $x_first) {
            $x_first = $row['created'];
        }
        if (empty($x_last) or $row['created'] >= $x_last) {
            $x_last = $row['created'];
        }
        if (empty($x_modified) or $row['modified'] >= $x_modified) {
            $x_modified = $row['modified'];
        }

      # Count visible and unedited submissions
        $temp = ($row['visible'] > 0 ? "$row[visible] curios" : '');
        $new = $row['count'] - $row['visible'];
        if ($new > 0) {
            $temp .= (empty($temp) ? '' : ', ') . "$new unedited submission" . ($new > 1 ? 's' : '') .
            " <a href=\"$FAQ#unedited_submission\">*</a>";
        }
        if ($temp != '') {
            $temp = " ($temp)";
        }

        if ($row['visible'] > 0 or !empty($GLOBALS['editor_action'])) {
            $out .= sprintf(
                "  <li><a class=index href=\"page.php?number_id=%s&amp;submitter=%s$GLOBALS[editor_action]\" title=\"number_id is $row[id]\">%s</a>",
                $row['id'],
                $submitter,
                $row['short']
            );
        } else {
            $out .= "  <li>$row[short]";
        }

        $out .= " $temp $note<br>\n";
        $x_not_visible += $row['count'] - $row['visible'];
    }

    if (empty($out)) {
        $out = "No such curios found.";
    } else {
        $out = "<ul>\n$out</ul>";
        if ($x_numbers >= $GLOBALS['record_limit']) {
            $out .= "<font size=-1>(Hit internal
	limit of $GLOBALS[record_limit] records)</font><br>";
        }
        if ($notes > 0) {
            $out .= "<font size=-1>(Numbers with the comments 'new'
	or 'modified' have curios that are new, or have been modified in the last
	$GLOBALS[days] days)</font>";
        }
    }

    $x_curios -= $x_not_visible;
    return $out;
}

include("template.php");
