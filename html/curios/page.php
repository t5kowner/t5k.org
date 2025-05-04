<?php

# Much of the time is used adding links (not loading the links database).  Store with links added?
# Add more security for editor's menu.

# This page displays curios depending on the first of the following which is
# defined:
#
#   $PATH_INFO  $_SERVER[PHP_SELF]/23.html gives $short=23.  (Because of  search engines
#       this is a common page access method!)
#
#   $curio_id   Display that one Curio.
#   $number_id  Display all Curios for that number.
#   $rank   Display all curios for the number with given rank
#   $short  Display all Curios for number given by $short form  (see (*) warnings)
#   $edit       Allow editor function menu (should be set to editer's id)
#
# By setting $showall to non-empty, it will print all digits of long numbers
# Setting $submitter will limit results to (submitters that include) that submitter.
#
# (*) Warnings about $short
#
#   (1) short is also used to carry the search string from the search box on the side of
#       the page, so may be a word or prase
#   (2) shorts for large numbers are not numerical
#   (3) we search on short as a number first, then pass it to search.php as a keyword
#
# So be careful with $short.


$t_text = '';   # Will hold the Curios text (and if stays blank we know
        # we have an error).

require_once("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

$sth = $db->query("SELECT MAX(numbers.rank) FROM numbers WHERE numbers.rank < 99999");
$row = $sth->fetch(PDO::FETCH_NUM);
$max = $row[0]; # How many numbers are in the database? Random will jump to a rank up to this high...

# Register the form variables:

$my_variables_digits       = '(curio_id|edit|number_id|deleted_id|rank)';
$my_variables_alphanumeric = '(short|showall)';
$my_variables_self_tested  = '()';
$my_variables_general      = '(submitter)';  # Example: Proth.exe
security_scrub_variables($my_variables_digits, $my_variables_alphanumeric, $my_variables_self_tested, $my_variables_general);

$curio_id  = (isset($_REQUEST['curio_id'])  ? $_REQUEST['curio_id'] : '');
$deleted_id = (isset($_REQUEST['deleted_id']) ? $_REQUEST['deleted_id'] : '');
$number_id = (isset($_REQUEST['number_id']) ? $_REQUEST['number_id'] : '');
$rank      = (isset($_REQUEST['rank'])      ? $_REQUEST['rank'] : '');
$short     = (isset($_REQUEST['short'])     ? $_REQUEST['short'] : '');
$edit      = (isset($_REQUEST['edit'])      ? $_REQUEST['edit'] : '');
$submitter = (isset($_REQUEST['submitter']) ? preg_replace('/[^\w ]/', '', $_REQUEST['submitter']) : '');


# Two ways to pass the 'short' information; in the variable or via path info
# e.g. ...page.php/23.html  sets $short to 23.

$t_adjust_path = '';
if (!empty($_SERVER['PATH_INFO']) and empty($short)) {
    if (preg_match("/\/(.*?)\.html?$/", $_SERVER['PATH_INFO'], $matches)) {
        $short = $matches[1];  # matches[0] is whole match
    }
}

# Quick tests to untaint

if (!is_numeric($curio_id)) {
    $curio_id = '';
}
if (!is_numeric($deleted_id)) {
    $deleted_id = '';
}
if (!is_numeric($number_id)) {
    $number_id = '';
}
if (!is_numeric($rank)) {
    $rank = '';
}
if (!is_numeric($edit)) {
    $edit = '';
}
$short = preg_replace('/,/', '', $short);
if (!empty($submitter)) {
    preg_match('/([\w.,;\- ]+)/', $submitter, $match);
    $submitter = $match[1];
}

$t_adjust_path = "/curios/";

# Build the variable part of the query

$where = '';
$how2link = ''; # They should not link to a page using the rank!

if (is_numeric($curio_id)) {  # Get single curio to display
    $where = "curios.id=$curio_id";
} elseif (is_numeric($deleted_id)) {
    $where = "deleted.id=$deleted_id";
} elseif (is_numeric($number_id)) {
    $where = "numbers.id=$number_id";
} elseif (is_numeric($rank)) {
    $where = "numbers.rank=$rank";
    $how2link = 'To link to this page use ';
} elseif (!empty($short) or (is_numeric($short) and $short == 0)) {
    if ($number_id = basic_number_id_via_short($short)) {
        $where = "numbers.id=$number_id";
        if (!preg_match("/^\d+$/", $short)) {
            $how2link = 'To link to this page use ';
        }
    } elseif (is_numeric($short)) {
        $name = "Missing number";
        $index = basic_index();
        $t_text = "<p>No curios for the number \"$short\" are currently in
	our database.&nbsp;  Do you know some good ones?</p>
        <p>If you are looking for a number and did not find it, you might
	try the search or index links above.</p>";
    } else {
        header('Location: /curios/search.php?searchstring=' . $short);
        exit;
    }
} else {
    $name = "Display Curios";
    $t_text = display_query_form();
    $t_adjust_path = '/curios/'; # avoid wget problems!
}

if (!empty($submitter) and !empty($where)) {
    $where .= " AND submitter LIKE '%$submitter%'";
    $t_text .= "<p class=highlight>Just showing those entries submitted by '$submitter':
	(Click <a href=\"/curios/page.php?number_id=$number_id\">here to show all</a>)</p>\n";
    $t_subtitle = "(just those submitted by $submitter)";
}

// $cutoff = date("YmdHis",time()-$days*24*60*60);

# May seek a single deleted curio
if (is_numeric($deleted_id)) {
    $table = 'deleted';
} else {
    $table = 'curios';
}

if (!empty($edit) && preg_match('/^\d+$/', $edit)) {
    $extra1 = ', ratings.rating';
    $extra2 = 'LEFT JOIN ratings ON ratings.curio_id = ' . $table . '.id AND ratings.person_id = ' . $edit;
} else {
    $extra1 = $extra2 = '';
}

# Do the query
if ($where) {
    $query = "SELECT $table.text, $table.sort, numbers.short, numbers.long_, numbers.rank, numbers.log10,
		$table.book, numbers.id, numbers.link, numbers.equation, $table.submitter, numbers.picture,
		$table.visible, $table.id AS curio_id, numbers.class, $table.created, $table.modified $extra1
	FROM ($table,numbers) $extra2
	WHERE $where AND numbers.id = $table.number_id";

  # print "<li>$query";
    $stmt = lib_mysql_query($query, $db, 'page.php: Invalid query in this page view, contact the admins');

    $invisible = 0;  # $invisible counts the non-visible curios (none I'd hope!)
  # Add the first Curio
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      # These really just need to be set once
        $name = $row['short'];
        $rank = $row['rank'];
        $number_id = $row['id'];
        $class = $row['class'];
        $t_class = $class;

        if ($number_id) {
            $canonical = 'https://t5k.org/curios/page.php?number_id=' . $number_id;
        }

        $temp = 'a ' . $class;
        if ($t_class === 'unit') {
            $temp .= ' (so is neither prime nor composite)';
        }
        if ($t_class === 'prp') {
            $temp = 'a probable-prime (likely to be a prime but we have not (re-)proven it on this site)';
        }
        if ($t_class === 'other') {
            $temp = 'neither prime nor composite';
        }
        $temp = 'This number is ' . $temp . '.';
        if ($t_class === 'unknown') {
            $temp = 'This number has not yet been tested at this site.';
        }

        $display_number = "<p><small>$temp</small></p>" . display_long(
            $row['long_'],
            $name,
            $number_id,
            $row['link'],
            $row['equation'],
            $row['log10']
        );

      # This is added each time (so is repeated below)
        if (!empty($GLOBALS['edit'])) {
            if (empty($deleted_id)) {  # no edit bar for deleted entries!
                $t_text .= EditorsNumberLine($number_id, $class, $row['rank']) . "<P>";
                $t_text .= EditorsCurioLine($row['curio_id'], $row['visible'], $row['rating'], $row['sort']);
            } else {
                $t_text .= "<div class=error>This curio is deleted! (It is no longer in the curios table, but stored in the deleted table and cannot be edited.)</div><br />";
            }
        }
        if ($row['visible'] == 'yes' or !empty($GLOBALS['edit'])) {
            $submitter = SplitSubmitter($row['submitter']);  # Split/Link submitters  name(s)
            $t_text .= '<p class="2">';
            if (!empty($curio_id)) {
                $t_text .= "<div class=\"small $GLOBALS[mdbltcolor] mb-3 pl-2\">Single Curio View:  &nbsp; (Seek <a href=/curios/page.php?number_id=$number_id>other curios for this number</a>)</div>\n";
            }
            if (!empty($row['picture']) and empty($curio_id)) {
                $t_text .= $row['picture'];
            }
            if (!empty($edit) and $row['book'] > 3.5) {
                $t_text .= '<img src="/curios/includes/gifs/book.gif" align="left"> &nbsp; ';
            }
            if (empty($curio_id)) {
                $t_text .= "<a href=\"/curios/cpage/$row[curio_id].html\"><img src=\"/gifs/check.gif\" title=\"$row[curio_id]\" alt=\"+\"></a> \n";
            }
            $t_text .= $row['text'] . " $submitter</p 2>\n\n";

            if (!empty($curio_id)) {
                $t_text .= "<div class=\"small $GLOBALS[mdbltcolor] mt-3 pl-2\"> Submitted: $row[created]; &nbsp; Last Modified: $row[modified].</div>\n";
            }
        } else {
            $invisible++;
        }
    } else {
        $name = "Error seeking number";
        $t_text .= "<p class=\"alert alert-error\">Query returned no results\n<blockquote>
	One possibility is that the number record has just been created, and
	no curios have been associated with it yet.  If you know this to be
	the case (e.g., you got here from the administrators' directory),
	then you do not need to tell the admins, just add the planned
	curios.<p>\n<pre>$query</pre>\n</blockquote>\n</font>";
        $t_adjust_path = '/curios/'; # avoid wget problems!
    }

  # Now the rest
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($GLOBALS['edit'])) {
            $t_text .= EditorsCurioLine($row['curio_id'], $row['visible'], $row['rating'], $row['sort']);
        }
        if ($row['visible'] == 'yes' or !empty($GLOBALS['edit'])) {
            $submitter = SplitSubmitter($row['submitter']);  # Split/Link submitters name(s)
            $t_text .= '<p class="1">';
            if (!empty($edit) and $row['book'] > 3.5) {
                $t_text .= '<img src="/curios/includes/gifs/book.gif" align="left"> &nbsp; ';
            }
            if (empty($curio_id)) {
                $t_text .= "<a href=\"/curios/cpage/$row[curio_id].html\"><img src=\"/gifs/check.gif\" title=\"$row[curio_id]\" alt=\"+\"></a> \n";
            }
            $t_text .= $row['text'] . " $submitter</p 3>\n\n";
        } else {
            $invisible++;
        }
    }

  # Are there non-visible entries?
    if ($invisible) {
        $t_text .= '<DIV style="FONT-SIZE: smaller">(There ' .
        ($invisible == 1 ? 'is one curio' : "are $invisible curios") .
        " for this number that " . ($invisible == 1 ? 'has' : 'have') .
        " not yet been approved by an editor.)<P></DIV>";
    }

    include("bin/modify.inc");
  # Perhaps has TeX like entities we need to translate!
    $t_text = modify_entities($t_text);
  # Definitely expect words to cross-link
    $t_text = modify_add_links($t_text, '', '', 'curio');

  # Now add the number, for some reason a few clash with
  # modify_add_links, but why bother?
    if (isset($display_number)) {
        $t_text = $display_number . $t_text;
    }

    if (!empty($how2link)) {
        $how2link .= "/curios/page.php?number_id=$number_id";
        $t_text .= "<div class=\"small border-top pl-2 mt-4\">$how2link</div>\n";
    }
}

# The templates uses $t_text for the text, $t_title...

$edit = (empty($edit) ? '' : "&amp;edit=$edit");
if (empty($t_text) or  $name == "Error seeking number") {
    if (preg_match("/^\d+$/", $short)) {
        $name = "Missing number";
        $t_title = $name;
        $index = basic_index();
        $t_text = "<p>No curios for the number \"$short\" are currently in
	our database.&nbsp;  Do you know some good ones?</p>
        <p>If you are looking for a number and did not find it, you might
	try the search or index links above.</p>";
    } else {
        $t_title = "No such curios found!";
        $t_text = display_query_form('Query returned no results.
        One possibility is that you mistyped the URL.&nbsp; If your query is correct, you
        might try again later.  If you think there is an error
        in the system, e-mail the technical editor using the contact link above.<P>');
        $t_submenu = "";
        $t_meta['description'] = "One of many pages of prime number
      curiosities and trivia.  This page just contains an error message, try the
      index or search engine linked above.";
        $t_adjust_path = '/curios/'; # avoid wget problems!
    }
} else {
    $t_title = $name;
    $t_submenu = "
    <a href=\"" . $t_adjust_path . "page.php?rank=" . (($rank > 1 and $rank <= $max) ? $rank - 1 : $max) . "$edit\" class=\"text-white d-print-none\">&bull; Previous</a> &nbsp; &nbsp; &nbsp;
    <a href=\"" . $t_adjust_path . "page.php?rank=" . (($rank < $max and $rank > 0) ? $rank + 1 : 1) . "$edit\" class=\"text-white d-print-none\">&bull; Next</a> &nbsp; &nbsp; &nbsp;
    <a href=\"" . $t_adjust_path . "page.php?rank=" . rand(1, $max) . "$edit\" class=\"text-white d-print-none\">&bull; Random</a>";
    $t_meta['description'] = "One of many pages of prime number
    curiosities and trivia.  This page discusses $name
    Come explore a new prime today!";
    $t_text .= "\n</form>";
}

function display_query_form($text = '')
{
    global $curio_id, $number_id, $rank, $short;
    return "$text <p>This page is designed to show curios.&nbsp; To do so, you
    must specify which using just one of the following (and in most cases only the last
    is useful):</p>
    <form method=get action=\"$_SERVER[PHP_SELF]\"><table class=\"td4 m-3 table-hover\">
      <tr><th class=\"text-right $GLOBALS[mdbltcolor]\">a curio's database id</th>
        <td><input type=number length=6 name=curio_id value=\"$curio_id\"></td>
        <td>(small positive integer)</td></tr>
      <tr><th class=\"text-right $GLOBALS[mdbltcolor]\">a number's database id</th>
        <td><input type=number length=6 name=number_id value=\"$number_id\"></td>
        <td>(small positive integer)</td></tr>
      <tr><th class=\"text-right $GLOBALS[mdbltcolor]\">a number's rank</th>
        <td><input type=number length=6 name=rank value=\"$rank\"></td>
        <td>(small positive integer)</td></tr>
      <tr><th class=\"text-right $GLOBALS[mdbltcolor]\">a number</th>
        <td><input type=text size=30 name=short value=\"$short\"></td>
        <td>(short form)</td></tr>
      </table>
      <input type=submit value=\"Search for These\" class=\"mx-3 mb-3 p-2 $GLOBALS[mdbmedcolor]\">
    </form></blockquote>
  <p>Never use the rank in a URL; it changes as numbers are added.  However,
  curio id's and number id's are both stable.</p>\n";
}


// When a number is long (over 22 characters) it will not fit into the title.
// This routine decides how to display such numbers.  $long (numbers.long_
// might be empty and equation or link might be there instead)

function display_long($long, $short, $number_id, $link, $equation, $log10)
{
    global $t_class;  # is it prime, composite, ...?

    if ($short == $long) {
        return ''; # The number is in the title
    }

    $class_temp = 'brown-text';  # e.g., class='unknown'
    if ($t_class === 'prime') {
        $class_temp = 'green-text';
    }
    if ($t_class === 'composite') {
        $class_temp = 'deep-orange-text';
    }
#  if ($t_class === 'unit') $class_temp = 'purple-text';
    if ($t_class === 'prp') {
        $class_temp = 'indigo-text';
    }

    $out =  '<div class="' . $class_temp . '" title="' . $equation . "\">\n";

    if (!empty($link)) { # If numbers.link exist (link to digits), then link away
        $out .= "(<a href=\"$link\">See all of the digits.</a>)";
    }

    if (!empty($long) or !empty($equation) and $log10 < 500000) {
      # If short enough, or asked too, show all digits
        if ($log10 < 400 or !empty($_REQUEST['showall'])) {
            include("bin/parser/parser.inc");
            if (empty($long)) {
                $long = parser_parse($equation);
            }
            $out .= parser_WrapNumber($long, 10, 7) . "\n";
        }
      # otherwise, give a button to push to see these digits
        else {
            $temp = preg_replace("/ /", '%20', $_SERVER['PHP_SELF']);
            $out .= "<form action=\"$temp?number_id=$number_id\" method=post>
     <center><input type=submit value=\"Show all digits?\"
	name='showall'></center>\n</form>\n";
        }
    }

    $out .= "</div>\n";
    return "<blockquote>\n$out\n</blockquote>\n";
}

function EditorsCurioLine($id, $visible, $rating = '', $sort = 'missing')
{
    global $number_id, $t_adjust_path,$edit;
  # When the user adds 'edit=$edit' to the path, then this line is added before
  # each entry.
  ## Removed menu items:
  ##    <A href=\"admin/index.php?xx_TableName=curios&amp;xx_view=$id\">view</A>";
  ##    <A href=\"admin/index.php?xx_TableName=curios&amp;xx_delete=$id&amp;".
  ##    "xx_action=confirmed\">immediate</A> or
    $out = "<A href=\"" . $t_adjust_path . "admin/index.php?xx_TableName=curios&amp;xx_edit=$id\">edit</A> |
	delete:
	<A href=\"" . $t_adjust_path . "admin/index.php?xx_TableName=curios&amp;xx_delete=$id\">confirm</A> |  ";

    $out .= " make
    <a href=\"" . $t_adjust_path . "admin/visible.php?number_id=$number_id&amp;edit=$edit&amp;curio_id=$id&amp;set_visible=";
    if ($visible == 'no') {
        $out .= "yes\">visible</a> ";
    } else {
        $out .= "no\">not visible</a> ";
    }
    $out = "Curio #$id (sort $sort) [ $out ] ";

    if ($visible == 'no') {
        $out .= "<font color=red><B>NOT VISIBLE</B></font> ";
    }

  # Now let's add a radio button for rating the curios
    $out .= " &nbsp; &nbsp; Rate: ";
    for ($i = 1; $i < 5; $i++) {
        $checked = ((!empty($rating) and $i == $rating) ? ' checked' : '');
        $out .= "\n\t<input type=radio name=\"rating$id\" value=\"$i\"$checked> $i";
    }
    $checked = (!empty($rating) and 'NULL' == $rating ? ' checked' : '');
    $out .= "\n\t<input type=radio name=\"rating$id\" value=\"NULL\" $checked> unrated";

    return "<nolink><div class=\"small $GLOBALS[mdbltcolor] pl-2\">$out</div></nolink>\n";
}

function EditorsNumberLine($id, $class, $rank)
{
    global $t_adjust_path;
    if (empty($id)) {
        $out = '[no number id]';
    } else {
      ## Removed menu items
      ##  <A href=\"admin/index.php?xx_TableName=numbers&amp;xx_delete=$id&amp;".
      ##  "xx_action=confirmed\">immediate</A> or
      ##  delete:
      ##  <A href=\"".$t_adjust_path."admin/index.php?xx_TableName=numbers&amp;xx_delete=$id\">confirm</A> |

        $out = "Number #$id [ <A
	href=\"" . $t_adjust_path . "admin/index.php?xx_TableName=numbers&amp;xx_edit=$id\">edit</A> |
	<A href=\"" . $t_adjust_path . "admin/index.php?xx_TableName=numbers&amp;xx_view=$id\">view</A> ]
	(This number is '$class', rank $rank)";
        $out .= "\n &nbsp; &nbsp; &nbsp; or ";
    }
    $out .= "see <A href=\"" . $t_adjust_path . "admin/index.php?xx_TableName=curios&amp;ind_visible=no\">
	menu of all non-visible Curios</a>";

  # Wrap it with the highligh element to make it stand out
    $out = "<div class=\"small $GLOBALS[mdbltcolor] pl-2\">$out</div>\n";

  # now need to open a form to allow ratings
    $out .= "\n<FORM METHOD=GET ACTION=\"${t_adjust_path}admin/ratings.php\">
	<input type=submit value=\"submit ratings\" class=\"$GLOBALS[mdbltcolor] mt-2 font-weight-bold\">
	<input type=hidden name=number_id value=\"$GLOBALS[number_id]\">
	<input type=hidden name=edit value=\"$GLOBALS[edit]\">
  ";

    return '<nolink>' . $out . '</nolink>';
}

function SplitSubmitter($sub = '')
{
    global $t_adjust_path;
# Humm, splits names like "Caldwell, G. L. & Dubner; Erd&oouml;s", links to ByOne Page
    $out = '';
    if (empty($sub)) {
        return '';
    }
    while (preg_match("/^(.*?)(\s*?([,;&]| and)\s+)(.*)$/", $sub, $m)) {
      # Notice the \s+ above, this keeps it from matching HTML entities &pi;
        $sub = $m[4];
        $out .= "<a href=\"" . $t_adjust_path . "ByOne.php?submitter=$m[1]\">$m[1]</a> $m[3]\n";
    }
    $out .= "<a href=\"" . $t_adjust_path . "ByOne.php?submitter=$sub\">$sub</a>";

    return " [$out]\n";
}

include("template.php");
