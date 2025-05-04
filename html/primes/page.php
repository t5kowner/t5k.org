<?php

include_once("bin/basic.inc");
include_once("bin/ShowPrimes.inc");
include_once('bin/modify.inc');  # Used to display comments safely

$db = basic_db_connect(); # Connects or dies
$t_submenu = 'Display prime';

# This page displays entries depending on the first of the following which is
# defined:
#
#   $id     Display that one item.
#   $rank   Display the first prime with this rank (only one hopefully)
#   $desc   Again, this one with the given description
#
# (If multiple are set--just uses the first of the above.)

# People were causing ugly loops using wget on error messages, so now the
# paths when there are errors are hard coded (not relative).  Sad....

# Register the form variables:

if (isset($_REQUEST['id']) and is_numeric($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
}
if (isset($_REQUEST['rank']) and is_numeric($_REQUEST['rank'])) {
    $rank = $_REQUEST['rank'];
}
if (!empty($_REQUEST['desc'])) {
    $desc = $_REQUEST['desc'];
}
if (!empty($_REQUEST['edit'])) {
    $edit = $_REQUEST['edit'];
    $comment = 1;
} else {
    $edit = '';
}

$edit = (empty($edit) ? '' : "&amp;edit=$edit");

# To calculate the normalized score, we need the score of the 5000th prime
# (Recall the score stored in a prime is missing the final log used in other places)
$The5000th = lib_get_column('prime.rank = 5000', 'prime', 'score', $db);

# Begin our work

$t_text = '';   # Will hold the text (and if stays blank we know
        # we have an error).
$local_menu = ''; # A menu for this page (to jump to various parts)

# Build the variable part of the query

$where = '';
if (isset($id) and is_numeric($id) and $id > 0) {
  # Get single term to display
    $where = "id=$id";
} elseif (isset($rank) and is_numeric($rank) and $rank > 0 and $rank < 100000) {
  # Get single term to display
    $where = "rank=$rank";
} elseif (!empty($desc)) {  # Get single term to display
    $desc = preg_replace('/\s/', '', $desc);
  # Why was this here?  $desc = preg_replace('/ /','+',$desc);
    $desc = $db->quote($desc);
    $where = "description=$desc";
} else {
    $t_title = "Display a Single Prime";
    $t_text = display_query_form();
}

# Do the query
if ($where) {
  # The order by is really for searches on 'rank'; there may be a composite
  # with the same rank!  We want the prime.  Before I did a join here with the code
  # table--but when there were errors in the credit string (code.name) then this page
  # failed to show.
    $query = "SELECT * FROM prime WHERE $where ORDER BY prime DESC LIMIT 1";
  # print "[$query]";

    $query_time = microtime();
    try {
        $stmt = $db->query($query);
    } catch (PDOException $ex) {
        lib_mysql_die('Invalid query in this page view', $query);
    }
    $query_time = (float)microtime() - (float)$query_time;

    include("../../library/format_helpers/datestamp.inc");        # date printing routines

  # Get the term
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $row['id'];           # used for editor's menu
        $name = MakePretty($row['description']);    # used for $t_title... below...
        $rank = $row['rank'];       # used for next, previous below
        $t_title = $name;
        $t_title2 = preg_replace('/<sup>/', '^', $t_title);     # t_title2 is for the brower window when it cannot
        $t_title2 = preg_replace('/<\/sup>/', '', $t_title2);  # match the page title.
        $t_title2 = str_replace(['<span dir=ltr>', '</span>'], '', $t_title2);

        $t_page_title = $row['description'];

     # Color code composites, deleted, prps...
        if ($row['prime'] == 'Composite') {
              lib_reset_colors('red');
              $t_title = "Composite: $t_title";
              $t_text .= "<font color=red size='+3'>Warning: This
			number is composite and will be deleted soon!</font>\n";
            $t_meta['add_lines'] = '<meta name="robots" content="noindex, nofollow, noarchive">';
        } elseif ($row['list'] == 'historic') {
             lib_reset_colors('brown');
             $t_title = "Historical: $t_title";
             $t_text .= "<font color='$drkcolor' size='+1'>
		  Historical primes are those that were never large enough to make the list of the
		  5,000 Largest Known primes, but are included to 'fill out' some of the top-20 tables...<font>\n";
        } elseif ($row['prime'] == 'PRP') {
            lib_reset_colors('green');
        }


     # Add editor's line
        if (!empty($GLOBALS['edit'])) {
            $temp = "Editor actions (careful!): <strong>Prime</strong> [
	<a href=\"admin/index.php?xx_TableName=prime&amp;xx_edit=$id\" class=\"none\">edit</a> ]";
            $t_text .= '<div class="alert alert-danger font-weight-bold m3" role="alert">' . $temp . "</div>\n";
        }

 # Now lets develop a next/prev/random menu for the prime table
 # Will put this next to the opening paragraph

        $temp_menu = '';  # will hold the next/prev/rand menu

        if (!empty($name)) {
             $random = rand(1, 5000);
             $prev   = ($rank > 1 ? ($rank > 5000 ? 5000 : $rank - 1) : 5000);
             $next   = ($rank > 4999 ? 1 : $rank + 1);

            if (!isset($t_adjust_path)) {
                $t_adjust_path = '';
            }
            $temp_menu = "<div class=\"col-sm-3 text-right mt-n3 mb-4 mt-sm-2 d-print-none\" role=\"group\">
    <a href=\"${t_adjust_path}page.php?rank=$prev$edit\" class=\"btn btn-dark p-1\" role=\"button\">Previous</a>
    <a href=\"${t_adjust_path}page.php?rank=$next$edit\" class=\"btn btn-dark p-1\" role=\"button\">Next</a>
    <a href=\"${t_adjust_path}page.php?rank=$random$edit\" class=\"btn btn-dark p-1\" role=\"button\">Random</a></div>";
        }

        $t_text .= '<div class="row flex-row-reverse">' . $temp_menu . "</div>\n\n" . '<div class="col-sm-9 col-print-12">';
        $t_text .= "<p>At this site we maintain a list of <a href=/primes/>the 5000 Largest Known Primes</a>
	which is updated hourly.&nbsp; This list is the most important <a href=/>PrimePages</a> database:
	a collection of research, records and results all about prime numbers. This page summarizes our
	information about one of these primes.</p></div>";

        $t_text .= "<h4 class=\"mt-5\" id=\"info\">This prime's information:</h4>\n";
        $local_menu .= ' <a href="#info" class="none"' . basic_tab_index() . '
	title="The basic information (date, size, who, ...)">General Information</a> :';
        $t_text .= add_data('header', '');
        $t_text .= add_data('Description', '<span class="indigo-text font-weight-bold">' . $name . '</span>');
        $t_text .= add_data('Verification status <span class="deep-orange-text">(<a href="help/page.php#status" class="none">*</a>)</span>', $row['prime']);
        $t_text .= add_data(
            'Official Comment <span class="deep-orange-text">(<a href="help/page.php#official" class="none">*</a>)</span>',
            empty($row['comment']) ? '<span class="text-info">[none]</span>' : $row['comment']
        );

        $comment_html = display_comments($id); # sets the global $display_comments_visible
        if ($display_comments_visible > 0) {
            $t_text .= add_data(
                'Unofficial Comments',
                "This prime has $display_comments_visible <a href=\"#comments\">user comment" .
                ($display_comments_visible > 1 ? 's' : '') . ' below</a>.'
            );
        }

     # For the credit strings' html we do a separate database call; I have at times had credit errors!
        $temp = lib_get_column("code.name='$row[credit]'", 'code', 'display_html', $db);
        if (empty($temp)) {
            $temp = '(possible error in the credit string)';
        }
        $temp = preg_replace('/(<[^\/].*?)>/', '$1 class="none">', $temp);
        $t_text .= add_data(
            'Proof-code(s): <span class="deep-orange-text">(<a href="help/page.php#proofcode" class="none">*</a>)</span>',
            "<a href=\"../bios/code.php?code=$row[credit]\" class=\"none\">$row[credit]</a> : $temp"
        );

        $t_text .= add_data(
            'Decimal Digits',
            "$row[digits] &nbsp; (log<sub>10</sub> is $row[log10])"
        );
        $t_text .= add_data(
            'Rank <span class="deep-orange-text">(<a href="help/page.php#rank" class="none">*</a>)</span>',
            (empty($row['rank']) ? 'unranked' : $row['rank']) .
            " (digit rank is $row[digit_rank])"
        );
        $t_text .= add_data('Entrance Rank <span class="deep-orange-text">(<a href="help/page.php#e_rank" class="none">*</a>)</span>', $row['e_rank']);
        $t_text .= add_data('Currently on list? <span class="deep-orange-text">(<a href="help/page.php#onlist" class="none">*</a>)</span>', $row['onlist']);
        $t_text .= add_data('Submitted', \Format_Helpers\formatDatestamp($row['submitted'], $row['submitted_precision']));
        $t_text .= add_data('Last modified', \Format_Helpers\formatDatestamp($row['modified']));
        if (!empty($row['removed'])) {
            $t_text .= add_data(
                'Removed <span class="deep-orange-text">(<a href="help/page.php#removed" class="none">*</a>)</span>',
                \Format_Helpers\formatDatestamp($row['removed'], $row['removed_precision'])
            );
        }
        $t_text .= add_data('Database id', "<a href=\"page.php?id=$row[id]\" class=\"none\">$row[id]</a>");
        if (!empty($row['blob_id'])) {
              $temp = empty($edit) ? '' :
            " [<a href=\"admin/index.php?xx_TableName=prime_blob&amp;xx_edit=$row[blob_id]\">edit</a>]";
              $t_text .= add_data('Blob database id', $row['blob_id'] . $temp);
        }

        $temp = (empty($row['status']) ? 'none' : $row['status']);
        $temp = preg_replace('/,/', ', ', $temp);
        $t_text .= add_data('Status Flags', $temp);
        $temp = ' (normalized score ' . (floor($row['score'] / $The5000th * 10000) / 10000) . ')';
        $t_text .= add_data(
            'Score <span class="deep-orange-text">(<a href="help/page.php#score" class="none">*</a>)</span>',
            (floor(log($row['score']) * 10000) / 10000) . $temp
        );

        $t_text .= add_data('footer', '');

     # Add any blob info (should be indicated by blob_id being set)

        if (!empty($row['blob_id'])) {
             $t_text .= empty($edit) ? '' :
            '<div class="alert alert-danger font-weight-bold m-3" role="alert">' .
            "Editor actions (careful!): Blob data
		[<a href=\"admin/index.php?xx_TableName=prime_blob&amp;ind_search1=$row[blob_id]" .
            "&amp;ind_search2=id&amp;$edit\">edit</a>] </div>\n";

             # Now get the row to display (selecting full_digit for next block)
             $query2 = "SELECT text,full_digit FROM prime_blob WHERE id='$row[blob_id]'";
            try {
                $stmt = $db->query($query2);
            } catch (PDOException $ex) {
                lib_mysql_die('Failed to get row from prime_blob', $query2);
            }
            $row2 = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row2['text'] == 'NULL') {
                $temp = '[This prime has a pre-calculated decimal expansion (linked  blob)]';
            } else {
                $temp = preg_replace('/(\+|\-|\*)/', ' $1 ', $row2['text']);  # Allow long primes to split
            }

            $t_text .= "<h4 class=\"mt-5\" title='from prime_blob table' id='blob'>Description:
	(from blob table id=$row[blob_id])</h4>\n<blockquote>$temp\n</blockquote>\n";
            $local_menu .= ' <a href="#blob" class="none"' . basic_tab_index() . '
	title="Information on primes without short descriptions or hard to calculate">"blob" data</a> :';
        }

     # Allow the users to view the digits?

        if ($row['digits'] < 75000 or $row['blob_id']) {
            if (!empty($_REQUEST['all'])) {
                $t_text .= "<FORM METHOD=POST ACTION=\"page.php?id=$id\">
	  <input type=submit class=\"btn btn-secondary p-1\" value='Hide the digits' name=null>
	  </form>";

                if (!empty($row2['full_digit'])) { # Don't use parser if already parsed!
                    $full_digits = $row2['full_digit'];
                } elseif (preg_match('/p\(\d\d\d\d+\)/', $name)) {
                    $full_digits = "<font color=red>Partition numbers are too slow to calculate 'on the fly'
		and should be pre-calculated.
		Tell the editor this one is missing and it will be added</font>\n";
                } else {
                    $temp = preg_replace('/"/', '\"', $row['description']);
  #         $full_digits = shell_exec("/var/www/html/primes/support/math/parser -f \"$temp\"");
                    $full_digits = shell_exec("./support/math/parser -f \"$temp\"");
                }
                $t_text .= "\n<blockquote style=\"overflow-wrap: break-word;\">\n$full_digits\n</blockquote>\n";
            } else {
                $t_text .= "<form method=post action=\"page.php?id=$id\">
	  <input type=submit class=\"btn btn-secondary p-1 mb-5\" value='Show all of the digits' name=all>
	  </form>";
            }
        }
        if ($row['blob_id']) {
            $t_text .= "<form method=post action=\"blob_digits.php?id=$row[blob_id]\">
	  <input type=submit class=\"btn btn-secondary p-1\" value='Show just the digits'></form>";
        }

     # Add any archival_tag data; currently only does so if there is an official comment
        if (!empty($row['comment'])) {
            $t_text .= "\n<h4 class=\"mt-5\" id=\"tags\">Archival tags:</h4>\n";
            $local_menu .= ' <a href="#tags" class="none"' . basic_tab_index() . '
	title="Information primes that make the Top 20 lists">Archival Tags</a> :';
            $t_text .= "<blockquote>There are certain forms classed as
	<a href=\"/top20/home.php#archivable\">archivable</a>: these prime may (at times)
	remain on this list <a href=\"/top20/sizes.php\">even if they do not make</a>
	the Top 5000 proper.&nbsp; Such primes are tracked with archival
	tags.</blockquote>\n" . display_archival_tags($id);
        }

     # $comment_html defined above so I could know if there were comments
        if (
            $display_comments_visible > 0 or file_exists("/var/www/html/TESTSITE")
            or !empty($_REQUEST['comment']) or !empty($edit)
        ) {
            $t_text .= "\n<h4 class=\"mt-5\" id=\"comments\">User comments about this prime
		(<a href=\"/primes/includes/disclaimer.php\" class=\"none\">disclaimer</a>):</h4>
		$comment_html\n";
            $local_menu .= ' <a href="#comments" class="none"' . basic_tab_index() . '
		title="Comments by non-editors">User Comments</a> :';
        }

     # Add any verification data.

        $temp = empty($edit) ? '' :
        '<div class="alert alert-danger font-weight-bold m-3" role="alert">' .
        "Editor actions (careful!): <B>Verification data</B>
		[<a href=\"admin/index.php?xx_TableName=verify&amp;ind_search1=$id" .
        "&amp;ind_search2=prime_id&amp;$edit\" class=\"none\">edit</a>]</div>\n";
        $temp .= lib_display_row(
            'primes',
            'verify',
            "prime_id=$id ORDER BY modified DESC",
            array('allow_empty' => true,'nl2br' => true,'allow_multiple' => true)
        );
        $tempheader = '<h4 class="mt-5" id="verify">' . "Verification data:</h4>\n";
        if (!empty($temp)) {
            $t_text .= "$tempheader
		<blockquote>The Top 5000 Primes is a list for proven primes only. In order to maintain the
		integrity of this list, we seek to verify the primality of all submissions.&nbsp;
		We are currently unable to check all proofs (ECPP, KP, ...), but we will at least trial
		divide and <a href=\"/glossary/page.php/PRP.html\" class=glossary title=glossary>PRP</a>
		check every entry before it is included in the list.</blockquote>
		<div class=\"row d-flex justify-content-center\">$temp</div>\n";
            $local_menu .= ' <a href="#verify" class="none"' . basic_tab_index() . '
		title="Records of efforts to verify this prime">Verification Data</a> :';
        } elseif ($row['prime'] == 'External') {
            $t_text .= "$tempheader
		<blockquote>Some of the very largest primes are externally verified by
		trusted groups or individuals and we do not reverify them at this site.
		This is one of those.</blockquote>\n";
        }
    } else {
        header('HTTP/1.0 404 Not Found');
        $t_title = "Error: No Such Prime in Prime Database";
        $t_text = display_query_form('<font color=red>Query returned no results.&nbsp;
	One possibility is that you mistyped the URL.&nbsp; Another is that the
	prime has been deleted from the database (for being composite, or not fitting the
	submission criteria...).&nbsp; 
	<P>If you think there is an error in the system, 
	<a href="/primes/includes/mail.php">e-mail the technical editor</a>.</font><P>');
    }
}

# The templates uses $t_text for the text, $t_title...  adjust these

$t_meta['description'] = "This page contains information about a single
   prime (discoverer, verification data, submission dates...).\n";
if (!isset($t_adjust_path)) {
    $t_adjust_path = '';
}

if (!empty($name)) {
  # The quotes and <span> (in name) screw up the description (in template.php)
    $temp = preg_replace('/"/', '', $row['description']);
    $t_meta['description'] .=   "This page is about the prime $temp.";
}



# The quotes and <span> in some titles screw up the list of keywords
if (isset($name) and !preg_match('/(<|")/', $name)) {
    $add2_keywords = $name;
}

# Lets add timing info (if it exists)
if (isset($query_time)) {
    $t_text .= '<div class="technote p-2 my-5">Query times: ' . round($query_time, 4) . " seconds to select prime";
    if (isset($query_time2)) {
        $t_text .= ', ' . round($query_time2, 4) . ' seconds to seek comments.';
    }
    $t_text .= "</div>\n";
}

$t_text = (defined($local_menu) ? "<div align=center class=highlight>For this prime : $local_menu</div>\n" : '') . $t_text;

include("template.php");

exit;

function add_data($key, $value)
{
    if ($key == 'header' and $value == '') {
        return     '<div class="row-flex">
	<div class="table-responsive">
 	<table class="table table-sm table-hover m-3">' . "\n";
    }
    if ($key == 'footer' and $value == '') {
        return "    </table>\n</div>\n</div>\n";
    }
    $out = '      <tr><th class="text-right font-weight-bold">' . "$key:</th><td>$value</td></tr>\n";
    return $out;
}

function display_query_form($text = '')
{
    global $desc, $id, $rank;
    return $text .
    '<p class="mb-4">' . "This page is designed to show a single database entry. To do so, you must specify which using just one of the following.</p>
      <blockquote><form method=post action=page.php><table cellspacing=3 cellpadding=3>
      <tr><td class=\"$GLOBALS[mdbltcolor] text-right p-2 font-weight-bold\">database id</td>
	<td><input type=text size=6 name=id class=\"form-control rounded-2\" value=\"" .
    (isset($id) ? $id : '') . "\"></td>
	<td>(small positive integer)</td></tr>
      <tr><td class=\"$GLOBALS[mdbltcolor] text-right p-2 font-weight-bold\">prime rank</td>
	<td><input type=text size=6 name=rank class=\"form-control rounded-2\" value=\"" .
    (isset($rank) ? $rank : '') . "\"></td>
	<td>(small positive integer)</td></tr>
      <tr><td class=\"$GLOBALS[mdbltcolor] text-right p-2 font-weight-bold\">prime description</td>
	<td><input type=text size=24 name=desc class=\"form-control rounded-2\" value=\"" .
    (isset($desc) ? $desc : '') . "\"></td>
	<td>(no spaces)</td></tr>
      </table>
      <input type=submit value=\"Search for this one Prime\" class=\"btn btn-primary my-4\">
    </form></blockquote>\n";
}


$query_time2 = '(not sought)';

function display_comments($id)
{
    global $out, $get_the_person_query; # Global to get_the_person
    global $display_comments_visible, $query_time2, $db;

    $xx_prime_id = $id;
    $xx_person_id = (empty($_REQUEST['xx_person_id']) ? '' : $_REQUEST['xx_person_id']);

    $query = "SELECT person.name, comment.person_id, comment.text, comment.id, comment.visible,
	DATE_FORMAT(comment.modified, '%e %b %Y') AS date
	FROM comment, person WHERE comment.prime_id=$id AND person.id=comment.person_id
		AND comment.visible='yes'";
    $query_time2 = microtime();

    try {
        $stmt = $db->query($query);
    } catch (PDOException $ex) {
        lib_mysql_die('display_comments_tags error', $query);
    }
    $query_time2 = (float)microtime() - (float)$query_time2;
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $edit = (empty($GLOBALS['edit']) ? '' : "$GLOBALS[edit]");

  # Display the matching rows

    $out = 'User comments are allowed to convey mathematical information about this number, how it was proven
	prime.... See our <a href="help/comment.php">guidelines and restrictions</a>.';
    $display_comments_visible = 0;


    foreach ($results as $row) {
        if ($row['visible'] == 'yes') {
            $display_comments_visible++;
        }
        if ($row['visible'] == 'yes' or !empty($edit)) {
            $out .= "\n<p><form method=post action=\"generic_edit.php\">\n" .
              '<div class="purple lighten-5 p-2 mt-3 w-100">' .
               '<fieldset class="p-3">' .
            "<legend class=\"w-auto mb-n3\" title=\"id=$row[id]\"><b>
	<a href=\"../bios/page.php?id=$row[person_id]$edit\">$row[name]</a> writes ($row[date]):&nbsp;
        (<a href=\"includes/disclaimer.php\">report abuse</a>)</b></legend>\n";

            $form = "<input type=hidden name=xx_TableName value=comment>
	<input type=hidden name=xx_person_id value=$row[person_id]>
	<input type=hidden name=xx_edit value=$row[id]>
	<input type=hidden name=xx_ReturnTo value=\"$_SERVER[PHP_SELF]?id=$id$edit#comments\">
	<input type=submit name=xx_action class=\"btn btn-secondary p-1 m-1\" value=edit>
	<input type=submit name=xx_action class=\"btn btn-secondary p-1 m-1\" value=delete>\n";

            if (!empty($edit)) {
                $form .= "<div class=\"alert alert-danger p-1 m-1\"><a href=\"admin/index.php?xx_TableName=comment" .
                "&amp;xx_edit=$row[id]\" class=\"alert p-0 mx-0\">editor's EDIT</a></div>\n";
            }

            if ($row['visible'] == 'no') {
                $out .= ' <font color=red>not visible</font> ';
            }

            $out .= '<table><col style="width:90%">
	<col style="width:10%"><tr class="w-100"><td>' . modify_adjust_html($row['text']) . "</td>
	<td >$form</td></tr></table>\n</fieldset></div>\n</form>\n";
        }
    }

    if (!empty($out)) {
        $out = '<blockquote>' . $out . '</blockquote>';
    }

    if (empty($_REQUEST['comment']) and empty($edit)) {
        return $out;
    }

  # Form to add comments

    $out .= "<blockquote><form method=post action=\"comment.php\">
	<fieldset><legend>Add a Comment &nbsp; </legend>\n";
    include_once("bin/get_the_person.inc");      # Grab function "get_the_person"

    $get_the_person_query = '<p>To add a comment to a prime you must be listed in 
	<a href="../bios/">our prover database</a>.</p>';
    get_the_person(); # adds the form field xx_person_id
    $out .= "<input type=hidden name=xx_prime_id value=$id>\n";
    include_once("bin/get_the_html.inc");     # Also grab "get_the_html" (the comment)
    $GLOBALS['get_the_html_box_width'] = 60;
    get_the_html();   # adds the form field xx_html
    $out .= "<input type=submit value=\"Submit this Comment\">";
    return $out . "</fieldset></form></blockquote>";
}


function display_archival_tags($id)
{
    global $db;
    $edit = (empty($GLOBALS['edit']) ? '' : "$GLOBALS[edit]");
    $query = "SELECT onlist, category_id, archival_tag.id, weight, archival_tag.modified,
	name, rank_now, type, archival_tag.subcategory, visible
	FROM archival_tag, archivable WHERE prime_id=:id AND category_id=archivable.id";
    try {
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    } catch (PDOException $ex) {
        lib_mysql_die('display_archival_tags error', $query);
    }

  # Display the matching rows
    $out = '';
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['visible'] == 'yes' or !empty($edit)) {
            $out .= "<dt><a href=\"../top20/page.php?id=$row[category_id]$edit\">$row[name]</a> ";
        } else {
            $out .= "<dt>$row[name] ";
        }
        $out .= "($row[type] <span class=\"deep-orange-text\"><a href=\"/top20/home.php#archivable$edit\">*</a>)</span>
	</dt><dd>Prime on list: <b>$row[onlist]</b>, rank <b>" .
        (empty($row['rank_now']) ? '[not yet ranked]' : $row['rank_now']) .
        "</b>";
        if (!empty($row['weight'])) {
            $out .= ", weight $row[weight]";
        }
        $out .= "\n<br>Subcategory: \"$row[subcategory]\"<br>
	(archival tag id $row[id], tag last modified $row[modified])</dd>";
    }
    if (empty($out)) {
        return '';
    } else {
        return '<blockquote><dl>' . $out . '</dl></blockquote>';
    }
}
