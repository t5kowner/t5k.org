<?php

# The goal is to have one program to print the prime tables (no matter
# what the form: HTML, Text...)  I actually have two: this one for all
# php web pages; and the routines in LoadPrimes.pm which are used by the
# perl maintance routines (which does HTML, text, LaTeX...).

# These routines only print the primes, you must pass the prime array to
# these routines.  The following fields of the table' prime' must be defined:
#
#   rank, prime.id, prime, onlist, submitted, description,
#   digits, credit, comment
#
# and hopefully also 'by_user' (a count of visible user comment per prime)

$monthNames = array('-','Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

# This routine first reformats (when desired) the hidden links imbedded
# with angle brackets place within the desc and comment field of the db.
# Format for these links:
#
#    <ref,reference (from refs.cgi),optional additional unlinked text>
#    <rel,relative reference (from prime pages root),optional link text>
#    <url,url (rel or absolute,optional link text>
#
# Uses global $AdjustLinks to decide if to adjust (or just delete these)



# The central prime output routines: ShowHTML and ShowText
# Now both can be called via &ShowPrime(\%Prime,\%Options) where
# $Options->style = 'HTML' or 'Text'.
#
# Routines
#
#   &ShowHTML(\%Prime,\%Options)  Show the prime as a table row.
#       Call with 'head' (or 'tail')  as the first parameter to
#       start/stop the table (so call with 'head' first).
#   Options can control display and some are set with each prime
#   'p' or in head 'h' (or both).
#
#   -----   -------------   --------------------------------------
#   where   what        why
#   -----   -------------   --------------------------------------
#   h   renumber    renumber the ranks 1, 2, 3, ...
#   h   no rank     omit the rank column
#   h   comment     rename the comment column
#   ht  wrapper     wrap in a table with a border
#   hp  id      print id's not ranks
#   p   description function name to apply to descriptions
#   p   add links   adds links from the comments to the glossary
#   -----   -------------   --------------------------------------
#
#   &ShowText(\%Prime,\%Options)  Return number as a text string
#       Let the second parameter be { 'add links' => 1} if you
#       want comments linked to the glossary and codes linked.
#       Use 'wrap' => 1 to remove "<rel,notes/13466917/,notes>"
#       from comments and wrap the lines.
#
#   -----   -------------   --------------------------------------
#   where   what        why
#   -----   -------------   --------------------------------------
#   h   renumber    renumber the ranks 1, 2, 3, ...
#   h   no rank     omit the rank column
#   h   comment     rename the comment column
#   p   comm_func   function name to apply to the comment
#   hp  id      print id's not ranks
#   p   description function name to apply to descriptions
#   p   add links   adds links from the comments to the glossary
#   -----   -------------   --------------------------------------
#
#   &MakePretty($String[,\%Options]) reformats math for HTML display.
#   Changes ^ to sup (e.g., 2^(3+4) -> 2<sup>3+4</sup>)...
#   adds white-space and backquotes when necessary to help
#   line wrapping

include_once('make_prime_pretty.inc');

# Combined routine

function ShowPrime($p, $options)
{
    return ($options['style'] == 'Text' ? ShowText($p, $options) :
    ShowHTML($p, $options) );
}

function ShowHTML($p, $options)
{
    global $HTMLReRank, $HTMLIdRank, $HTMLNoHide;
    $_UnTested  = 'grey lighten-4';   #DDDDDD
    $_Composite = 'deep-orange darken-2'; #FF6666
    $_InProcess = 'orange lighten-3'; #FFCCAA
    $_PRP       = 'lime lighten-4';   #DDFFCC
    $_Proven    = 'green lighten-5';  #BBFFDD
    $_External  = 'teal lighten-2';   #99CCCC
    $_Remove    = 'red lighten-2';    #FF6677

    if (empty($p)) {
        return('no prime to show.');
    }

    if ($p == 'head') {
        $HTMLReRank = (!empty($options['renumber']) ? 1 : 0);
        $HTMLIdRank = (!empty($options['id']) ? 1 : 0);
        $HTMLNoHide = (!empty($options['no hide']) ? 1 : 0);

        $temp = '';
        if (!empty($options['wrapper'])) {
            $temp .= "<table class=wrapper><tr><td>\n";
        }
        $temp .= '<table border=0 class="td2"><tr class="' . $GLOBALS['mdbmedcolor'] . '">';
        if (empty($options['no rank'])) {
            $temp .= ($HTMLIdRank ? '<th class="text-center font-weight-bold">id</th>' : '<th class="text-center font-weight-bold">rank</th>');
        }
        $temp .= '<th class="text-center font-weight-bold">prime</th>' . "\n" . '<th class="text-center font-weight-bold">';
        $temp .= 'digits</th><th class="text-center font-weight-bold">';
        $temp .= 'who</th><th class="text-center font-weight-bold">';
        $temp .= 'when' . "</th>";
        $temp .= '<th class="text-center font-weight-bold">' . (empty($options['comment']) ? 'comment' : $options['comment']) . "</th></tr>\n";
        return($temp);
    } elseif ($p == 'tail') {
        $legend = '';
        if (!empty($options['color_legend'])) {
            $legend = "<h4 class=\"m-5 mb-n4\">Legend</h4><blockquote class=\"m-5\"><table class=\"my-4\">
        <tr class=\"$GLOBALS[mdbmedcolor]\"><th colspan=2 class=\"pl-2 font-weight-bold text-center\">Prime Description Color Codes</th></tr>
	<tr><td class=\"$_Composite pl-2 text-center font-weight-bold\">Composite</td><td class=\"pl-2\">Proven composite</td></tr>
	<tr><td class=\"$_Remove pl-2 text-center font-weight-bold\">Remove</td><td class=\"pl-2\">Scheduled for deletion because it is too small or proven composite</td></tr>
	<tr><td class=\"$_UnTested pl-2 text-center font-weight-bold\">UnTested</td><td class=\"pl-2\">Not yet tested</td></tr>
	<tr><td class=\"$_InProcess pl-2 text-center font-weight-bold\">InProcess</td><td class=\"pl-2\">Currently being tested</td></tr>
	<tr><td class=\"$_PRP pl-2 text-center font-weight-bold\">Probable-prime</td><td class=\"pl-2\">Shown to be a PRP,
	  awaiting further testing (see note).</td></tr>
	<tr><td class=\"$_Proven pl-2 text-center font-weight-bold\">Proven</td><td class=\"pl-2\">Proven prime</td></tr>
	<tr><td class=\"$_External pl-2 text-center font-weight-bold\">External</td><td class=\"pl-2\">Proven prime, externally verified</td></tr>
	<tr><td colspan=2 class=\"$GLOBALS[mdbmedcolor] px-2\">Note:&nbsp; Only proven primes are accepted
	  on this list.&nbsp;  These colors refer the status of this list's re-verification
	  process only.</td></tr>
        </table></blockquote>";

            $legend .= "<blockquote class=\"m-5\"><table>
	<tr class=\"$GLOBALS[mdbmedcolor]\"><th colspan=2 class=\"font-weight-bold text-center\">Rank/Id Color Codes</th></tr>
	<tr><td class=\"$_Proven text-center font-weight-bold\">yes</td><td>On the list</td></tr>
	<tr><td class=\"$_UnTested text-center font-weight-bold\">no</td><td>Not on the current list</td></tr>
	<tr><td class=\"$_InProcess text-center font-weight-bold\">(unknown)</td><td>Not yet re-ranked</td></tr>
	<tr><td colspan=2 class=\"$GLOBALS[mdbmedcolor] px-2\">Note:&nbsp; This list is (re)ranked every 30 minutes.</td></tr>
	</table></blockquote>";

    #      $legend = "<table><tr><td>$legend</td><td>$legend2</td></tr></table>\n";
        }

        $temp = empty($options['wrapper'])
        ? "<tr class=\"$GLOBALS[mdbmedcolor]\"><td>&nbsp;</td><td>&nbsp;</td>
	<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>" .
        (empty($options['no rank']) ? '<td>&nbsp;</td>' : '') . "\n</tr></table>\n"
        : "\n</tr></table>\n</td></tr></table>\n";

        return("$temp\n$legend\n");
    }

    $out = " " . lib_tr();  # Space to make HTML more readable

    if (!empty($options['color'])) {
        $color = $_UnTested;
        if ($p['prime'] == 'Composite') {
            $color = $_Composite;
        } elseif ($p['prime'] == 'InProcess') {
            $color = $_InProcess;
        } elseif ($p['prime'] == 'PRP') {
            $color = $_PRP;
        } elseif ($p['prime'] == 'Proven') {
            $color = $_Proven;
        } elseif ($p['prime'] == 'External') {
            $color = $_External;
        }
        if (preg_match('/Remove/i', $p['status'])) {
            $color = $_Remove;
        }
        $out = " <tr class=\"$color\">";  # Space to make HTML more readable
    }
  # A couple \n's tossed in below because pico was causing linewrap problems

  # The rank
    if (empty($options['no rank'])) {
        $temp = ($HTMLIdRank ? $p['id'] : ($HTMLReRank > 0 ? $HTMLReRank++
        : (empty($p['rank']) ? 'unranked' : $p['rank'])));

        $color = '';
        if (!empty($options['color'])) {
            $color = $_InProcess;
            if ($p['onlist'] == 'yes') {
                $color = $_Proven;
            } elseif ($p['onlist'] == 'no') {
                $color = $_UnTested;
            }
        }

        $prime_url = '';    # create the url for linking the rank, also used below for user comments
        if ($options['link rank'] == 'yes') {
            $prime_url = "/primes/page.php?id=$p[id]";
        } elseif (!empty($options['link rank'])) {
            $prime_url = "/primes/page.php?id=$p[id]&edit=" . $options['link rank'];
        }
        if (!empty($prime_url)) {
            $temp = "<a class=none href=\"$prime_url\">$temp</a>";
        }

        if (empty($p['e_rank'])) {
            $p['e_rank'] = 'unset';
        }
        $temp2 = (empty($p['rank']) ? 'unranked' : "$p[rank] was $p[e_rank]");
        $out .= "<td class='text-center $color' title='rank $temp2'>$temp</td>";
    }

  # The description
    if (!empty($options['description'])) {
        $temp = $options['description']($p['description']);
    } else {
        $temp = $p['description'];
    }
    if ($p['prime'] == 'Composite') {
        $temp = "<strike>$temp</strike>";
    } else {
        $temp = "<b>$temp</b>";
    }
    $out .= "<td class=\"text-right\" style=\"max-width: 670px;overflow: auto;\">$temp</td>\n";


  # The digits
    if (!empty($options['digits'])) {
        $temp = $options['digits']($p['digits'],$p['description']);
    } else {
        $temp = $p['digits'];
    }
    $temp2 = (isset($p['weight']) ? "title=\"weight $p[weight]\"" : '');
    $out .= "<td class=\"text-center\" $temp2>$temp</td>\n";
  # Can be a long field if URL linked!

  # The discoverers
    if (!empty($options['discoverer'])) {  # This option would be a function call
        $temp = $options['discoverer']($p['discoverer']);
    } else {
        $disc = (empty($p['discoverer']) ? $p['credit'] : $p['discoverer']);
        $out .= '<td class="text-center"><a class=none href="/bios/code.php?code=' . $disc .
         '">' . $disc . "</a></td>\n";
    }

  # The Date

  # original
  # preg_match("/^(\d\d\d\d).*/",$p['submitted'],$temp);
  # $out .= "<td title=\"$p[submitted]\">$temp[1]</td>\n";

  # day granularity, ISO format - Ugly!
  # preg_match("/^(\d\d\d\d-\d\d-\d\d).*/",$p['submitted'],$temp);
  # $out .= "<td title=\"$p[submitted]\">$temp[1]</td>\n";

  # Month granularity using names -
    preg_match("/^(\d\d\d\d)-(\d\d).*/", $p['submitted'], $temp);
    global $monthNames;
    $month = $monthNames[(int)($temp[2])];
    $out .= "<td title=\"$p[submitted]\">$month&nbsp;$temp[1]</td>\n";

  # The comment
    if (!empty($options['comm_func'])) {      # A comment function?
        $temp = $options['comm_func']($p['comment']);
    } else {
        $temp = $p['comment'];
    }
    if (
        preg_match('/(InProcess|Untested)/', $p['prime'])
        and !empty($options['unverified']) and $options['unverified'] == 'warn'
    ) {
        $temp .= " <font color=red>(Unverified because $p[prime])</font>";
    }
    if (!empty($p['by_user'])) {
        $temp .= (empty($temp) ? '' : ' ') .   # is a space necessary?
        "(<a href=\"$prime_url#comments\" class=none title=\"user comments\">**</a>)";
    }
    if (empty($temp)) {
        $temp = '&nbsp;';
    } else {
        if (!empty($options['comment'])) {
            $temp = $options['comment']($temp);
        }
        $temp = add_links($temp, $options);  # Adds links if 'add links' is set
    }

    $out .= '<td>' . $temp . "</td>\n";
    return($out);
}


# Now a version of modify_add_links I can call even when not modifying
# for use in show_text

$top20_match_array = array();

function len_sort($a, $b)
{
    return strlen($b['comm_match']) - strlen($a['comm_match']);
}

function add_links($text, $options)
{
    global $top20_match_array, $basic_database_connection;

  # return unless 'add links' is defined and $text is not empty
    if (empty($options['add links']) or empty($text)) {
        return($text);
    }

  ## Old method linked to glossary:
  ##   include_once("bin/modify.inc");
  ##   return modify_add_links($text,$_SERVER['PHP_SELF'],'class=none');

  # Load the comm_match strings... from the top20's 'archivable' table into
  # the global $top20_match_array.  But only do so once (not once per prime!)

    if (count($top20_match_array) == 0) {
        $query = "SELECT comm_match, not_comment, subcategory, repeatable,
        id, name, visible FROM archivable WHERE comm_match != '' AND visible != 'no'";
        $i = 0;
        foreach ($basic_database_connection->query($query) as $row) {
            $top20_match_array[$i++] = $row;
        }
    }

  # Now match the ($top20_match_array against the comments
    $extra = 'class=none title="top 20 list"';
    $url = '/top20/page.php?id=';

  # sort by length so we match Cullen after Generalized Cullen
    usort($top20_match_array, 'len_sort');

    foreach ($top20_match_array as $row) {
        $tag = $row['comm_match'];
        $tag = preg_replace('/\//', '\\/', $tag);
        $id = $row['id'];
        if (preg_match("/$tag/", $text)) {
            $text = preg_replace("/($tag)/", "<a href=\"$url$id\" $extra>$1</a>", $text);
        }
    }

    return($text);
}


function ShowText($p, $options = '')
{
    global $TextRerank, $TextIdRank ;
  # Linewrapping is a pain!

    if (empty($p)) {
        return('no prime to show.');
    }

  # wrap can be used to set the page width.  Default is 78.

    if (!empty($options['wrap']) and is_numeric($options['wrap']) and $options['wrap'] > 55) {
        $PageWidth = $options['wrap'];
    } else {
        $PageWidth = 78;
    }

    $x32 = str_repeat('-', 32);
    if ($p == 'head') {
        $TextRerank = (empty($options['renumber']) ? 0 : 1);
        $TextIdRank = (empty($options['id']) ? 0 : 1 );

        $out =  "-----  $x32 ------- ----- ---- --------------"
        . sprintf(
            "\n%5s  %20s %37s",
            ($TextIdRank ? 'id' : 'rank'),
            'description',
            " digits  who year comment"
        )
        . "\n-----  $x32 ------- ----- ---- --------------\n";
        return($out);
    } elseif ($p == 'tail') {
        return("-----  $x32 ------- ----- ---- --------------\n");
    }

    $comment = $p['comment']; # So we can modify them if desired
    if (!empty($p['by_user'])) {
        $comment .= (empty($comment) ? '' : ' ') . '(**)';
    }
    $disc = (empty($p['discoverer']) ? $p['credit'] : $p['discoverer']);

  # Start by printing rank/id (and possible a date 'letter' a= htis month, b= last...)
  # I tried to get rid of these letter but several folks balked

    $letter = ' ';
    if (!empty($options['dateletter'])) {
      # add the letter a after rank of the current month's primes...
        if (preg_match('/^(\d\d)\/\d\d\/(\d\d)$/', `date +%D`, $matches)) {
            $c_year = $matches[2];
            $c_month = $maches[1];
            if (preg_match('/^(19|20)(\d\d)(\d\d)/', $p['flags'], $matches)) {
                $ago = ($c_year - $matches[2]) * 12 + $c_month - $matches[3]; # Negative for 1999's...
                if ($ago < 6 and $ago >= 0) {
                    if ($ago == 0) {
                        $letter = 'a';
                    }
                    if ($ago == 1) {
                        $letter = 'b';
                    }
                    if ($ago == 2) {
                        $letter = 'c';
                    }
                    if ($ago == 3) {
                        $letter = 'd';
                    }
                    if ($ago == 4) {
                        $letter = 'e';
                    }
                    if ($ago == 5) {
                        $letter = 'f';
                    }
                }
            }
        } else {
            lib_die("failed to read current system date");
        }
    }

  # The option id might be set for a single headless prime (e.g., error message)
    $rank = ( ($TextIdRank or isset($options['id'])) ? $p['id'] :
    ($TextRerank > 0 ? $TextRerank++ : $p['rank']) );

    $out = sprintf("%5d%1s ", $rank, $letter);
  # Note: we have used up 7 columns of out page so far.

  # Now, lets print the description; it could be padded to the 39th column
  # So short one (at most 39-7=32 characters) can just be print, longer might
  # be wrapped.  But also keep track of where we end in $at_column so we can
  # do clever things with the rest of the prime...

    if (strlen($p['description']) > 32 and (!empty($options['wrap']))) {
        if (strlen($p['description']) <= $PageWidth - 7) {  # Long, but no wrap.
            $out .= $p['description'];
            $at_column = 7 + strlen($p['description']);
        } else {
            $temp = $p['description'];
            $width = $PageWidth - 8; # 7 used, need one for backquote
            while (preg_match("/^(.{1,$width})(.*)/", $temp, $matches)) {
                $temp = $matches[2];
                $out .= $matches[1];
                if (!empty($temp)) {
                    $out .= "\\\n       ";  # Not done, so backquote -- indent 7
                } else {
                    $at_column = 7 + strlen($matches[1]);
                }
            }
        }
    } else {  # Excellent--simple short description!
        $out .= $p['description'];
        $at_column = 7 + strlen($p['description']);
    }

  # Print the digits
  # Pad enough to get digits where you want them (usually ending in column 47)
  # but, if already too far over, perhaps we can move digits left on the next
  # line to fit in the comment?

    $digits = $p['digits'];
    if (!empty($options['wrap'])) {
        if ($at_column < 46 - strlen($digits)) {
          # No wrapping needed here--print and go
            $out .= str_repeat(' ', 47 - strlen($digits) - $at_column) . $digits;
            $at_column = 47;
        } elseif (strlen($comment) + strlen($digits) + 11 < $PageWidth) {
          # Need to wrap eventually and can fit all the rest on one line
          # lets also line up the digits if possible (to end in column 47)
            $blanks = min($PageWidth - strlen($comment) - 11, 47) - strlen($digits);
            $out .= "\n" . str_repeat(' ', $blanks) . $digits;
            $at_column = $PageWidth - strlen($comment) - 11;
        } else {
            $out .= "\n" . str_repeat(' ', 47 - strlen($digits)) . $digits;
            $at_column = 47;
        }
    } else {
        $out .= sprintf(" %7d", $digits);
    }

  # LoadPrimes.pm loads the year, but "select * from prime" does not
    if (empty($p['year']) and preg_match('/^(\d\d\d\d)/', $p['submitted'], $matches)) {
        $p['year'] = $matches[1];
    }

  # Print the discoverer and year (columns 40 through 56, blank in 57)
    if (!empty($options['add links'])) {  # Add links?
        $temp = "<a href=\"/bios/code.php?code=$disc\">$disc</a>";
    } else {
        $temp = $disc;
    }
    $temp = str_repeat(' ', 5 - strlen($disc)) . $temp;
    $out .= sprintf(" %s %4d ", $temp, $p['year']);
    $at_column += 11;

  # Finally, print the comment in columns 57 (a blank) through $PageWidth

  # If 'unverified' is set to warn, then warn on the untested primes
    if (
        preg_match('/(InProcess|Untested)/', $p['prime'])
        and !empty($options['unverified']) and $options['unverified'] == 'warn'
    ) {
        $comment .= " (Unverified because $p[prime])";
    }

  # If wrapping, prepare by removing the links as well, because wrapping
  # surely means printing on paper...

    if (!empty($options['wrap'])) {
        $comment = preg_replace('/<(url|rel|ref),.*?>/', '', $comment);
    }

    if ((!empty($options['wrap'])) and strlen($comment) > $PageWidth - $at_column) {
        $indent = 10;  # How far do we indent?
        if (strlen($comment) <= $PageWidth - $indent) {
            $out .= "\n" . str_repeat(' ', $indent) . add_links($comment, $options);
        } else {
            $width = $PageWidth - $indent;
            while (preg_match("/^\s*(.{1,$width})(\s.*|$)/", $comment, $matches)) {
                $comment = $matches[2];
                $out .= "\n" . str_repeat(' ', $indent) . add_links($matches[1], $options);
            }
            if (!empty($comment)) { # SHOULD BE IMPOSSIBLE--LONG COMMENT WITH NO BREAKS!
                $out .= "\n" . str_repeat(' ', $indent) . add_links($comment, $options);
            }
        }
        $out .= "\n";
    } else {
        $out .= add_links($comment, $options) . "\n";
    }

    return($out);
}
