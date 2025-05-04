<?php

$t_submenu =  "Code";   # added to breadcrumbs menu

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

# This page displays info about one code--a short page linking to
# the bio entries.
#
#   $code   Display that one item
#   $edit   Add editor's functions
#   $id     Display that one item (deprecated)

# Register the form variables and detaint them:

$code = (empty($_REQUEST['code']) ? '' : htmlentities($_REQUEST['code']));
$code = preg_replace('/[^\w]/', '', $code);  # short \w string

$edit = (empty($_REQUEST['edit']) ? '' : htmlentities($_REQUEST['edit']));
$edit = preg_replace('/[^\d]/', '', $edit);  # must be an integer

$id   = (empty($_REQUEST['id'])   ? '' : htmlentities($_REQUEST['id']));
$id   = preg_replace('/[^\d]/', '', $id);  # must be an integer

# Begin our work

$t_text = '';   # Will hold the text (and if stays blank we know we have an error).
$temp = 'disclaimer';  # Just to keep the word in the pot file.  Eventually remove this line.

$t_allow_cache = 'yes'; # Probably non-longer necessary due to template update

function EditorsLine($id, $code)
{
  # When the user adds 'edit=1' to the path, then this line is added before
  # each entry.
    $out = "<A href=\"admin/index.php?xx_TableName=code&amp;xx_edit=$id\" class=none>edit</A> |
        <A href=\"admin/index.php?xx_TableName=code&amp;xx_delete=$id\" class=none>delete</A> ";
    $out = "Term #id \"$code\"  [ $out ]\n ";
    return '<div class="alert alert-danger font-weight-bold" role="alert">These entries are machine generated--so
	I see no reason to use these:' . $out . "</div>\n";
}

function seek_next($code, $step)
{
    global $db;
  # Look for the first entry past $code.  If $step is negative seeks previous;
  # 0 for a random entry.  Returns the id column of this entry.
  # Name might be empty (NULL) if wrapping past the end.

  # First, form the $query
    if ($step > 0) {      # Find the next entry
        if (empty($code)) {
            $code = '';
        }
        $where = "WHERE name > :code";
        $direction = 'ORDER BY name';
    } elseif ($step < 0) {   # Find the previous entry
        if (empty($code)) {
            $code = 'zzz';
        }
        $where = "WHERE :code > name";
        $direction = 'ORDER BY name DESC';
    } else {          # Find a random entry
        if (empty($code)) {
            $code = '';
        }
        $where = "WHERE name <> :code";
        $direction = 'ORDER BY RAND()';
    }
    $query = "SELECT name FROM code $where $direction LIMIT 1";
  # Okay, that should do it, spit out answer

    try {
        $stmt = $db->prepare($query);       # Not all are bound
        $stmt->bindParam(':code', $code);
        $stmt->execute();
    } catch (PDOException $ex) {
        lib_mysql_die('Invalid query in this page view (23), contact Chris', $query);
    }

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        return($row['name']);
    } else {
      # If there is no entry this size, return NULL
        return(null);
    }
}

# Build the variable part of the query

$where = '';
if (!isset($t_adjust_path)) {
    $t_adjust_path = '';  # Default value
}
if (!empty($code)) {
    $where = "code.name LIKE BINARY '$code'";
} elseif (intval($id) > 0) {  # Get single term to display
    $where = "code.id=" . intval($id);
} else {
    $temp = (empty($id) ? '' : '<div class="error">The database id must be a small positive integer.</div><p> ');
    $t_title = "Display a Proof-code";
    $t_text = display_query_form($temp);
    $t_adjust_path = "/bios/";  # Want no wget problems ...
}

# For the normalized score:
$The5000th = lib_get_column('prime.rank = 5000', 'prime', 'log(score)', $db);

# Do the query
if ($where) {
    $select = 'code.*, floor(exp(log(code.ScoreActive)-:The5000th)+0.5) AS ScoreNormal';
    $query = "SELECT $select FROM code WHERE $where";
    try {
        $stmt = $db->prepare($query);   # Not all are bound
        $stmt->bindParam(':The5000th', $The5000th);
        $stmt->execute();
    } catch (PDOException $ex) {
        lib_mysql_die('Invalid query in this page view (17), contact Chris', $query);
    }

  # Get the term
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $name = $row['name'];
        $t_title = "Proof-code: $name";
        $display_text  = $row['display_text'];
        $display_short = $row['display_short'];
        $display_html  = $row['display_html'];

        if (isset($GLOBALS['edit']) and $GLOBALS['edit'] == 1) {
            $t_text .= EditorsLine($row['id'], $row['name']) . "<P>";
        }

      # This is a three column table, left is row title, right is text (sometimes split)
        $t_text .= add_data('header', '', 'class="' . $GLOBALS['mdbltcolor'] . ' p-4"');
        $temp = (empty($row['comment']) ? '' : ' &nbsp; (See the descriptive data below.)');
        $t_text .= add_data('<strong>Code name</strong> (<a href="help/page.php#code_name">*</a>)', $name . $temp);
        $t_text .= add_data(
            '<strong>Persons</strong> (<a href="help/page.php#persons">*</a>)',
            "$row[persons] (counting humans only)"
        );
        $t_text .= add_data(
            '<strong>Projects</strong> (<a href="help/page.php#persons">*</a>)',
            "$row[projects] (counting projects only)"
        );
        $t_text .= add_data('<strong>Display (HTML)</strong>', $display_html);
        $t_text .= add_data(
            '<strong>Number of primes</strong>',
            'total ' . $row['PrimesTotal'],
            "on current list" . ' ' . $row['PrimesActive']
        );
        $t_text .= add_data('<strong>Unverified Primes</strong>', "$row[NonPrime] (" .
        "prime table entries marked 'Composite','Untested', or 'InProcess'");

        if ($row['ScoreTotal'] > 0) {
            $t_text .= add_data('<strong>Score for Primes</strong>
	(<a href="help/page.php#score">*</a>)', 'total ' . sprintf('%.4f', log($row['ScoreTotal'])) .
            ($row['PrimesActive'] == 0 ? '' : ', on current list ' .
            sprintf('%.4f', log($row['ScoreActive'])) .
            ($row['ScoreNormal'] > 0 ? ' (' . "normalized score $row[ScoreNormal])" : '') ));
        }

        if ($row['PrimesActive'] > 0) {
            $select = 'AVG(e_rank) AS avg, MIN(e_rank) AS min, MAX(e_rank) AS max';
            $query = 'SELECT ' . $select . ' FROM prime WHERE prime.credit = :name AND onlist<>\'no\'';
            try {
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->execute();
            } catch (PDOException $ex) {
                lib_mysql_die('Invalid query in this page view (14), contact Chris', $query);
            }

            if ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $temp = '<strong>Entrance Rank</strong> (<a href="help/page.php#e_rank">*</a>)';
                $t_text .= add_data($temp, sprintf("mean %.2f (minimum %d, maximum %d)", $row2['avg'], $row2['min'], $row2['max']));
            }
        }

        $t_text .= add_data('footer', '');

        if (empty($row['PrimesTotal'])) {
            $t_text .= "<div class=error>\n" .
            "This entry has no associated primes and may be deleted anytime.\n " .
            "It may take up to one hour for the system to adjust all entries for newly verified primes.\n</div>\n<p>\n";
        } else {
            $t_text .= "\n<P>
        <table><tr>
        <td><form method=post action=\"../primes/search.php\">
        <input type=hidden name=Discoverer value=\"$name\">
        <input type=hidden name=Number value=\"$row[PrimesActive]\">
        <input type=hidden name=OnList value=yes>
	<input type=hidden name=Style value=HTML>
        <input type=\"submit\" class=\"btn btn-primary p-2 mb-4\" name=all value=\"This Code's Current Primes\">
        </form></td>
        <td><form method=post action=\"../primes/search.php\">
        <input type=hidden name=Discoverer value=\"$name\">
        <input type=hidden name=Number value=\"$row[PrimesTotal]\">
        <input type=hidden name=OnList value=all>
	<input type=hidden name=Style value=HTML>
        <input type=\"submit\" class=\"btn btn-primary p-2 mb-4\" name=all value=\"All of This Code's Primes\">
        </form></td>";
            if ($row['NonPrime'] > 0) {
                $t_text .= "\n
        <td><form method=post action=\"../primes/search.php?Advanced=1\">
        <input type=hidden name=Discoverer value=\"$name\">
        <input type=hidden name=Number value=\"$row[NonPrime]\">
        <input type=hidden name=\"PrimeStatus[]\" value=1>
        <input type=hidden name=\"PrimeStatus[]\" value=2>
        <input type=hidden name=\"PrimeStatus[]\" value=3>
	<input type=hidden name=Style value=HTML>
        <input type=\"submit\" class=\"btn btn-primary p-2 mb-4\" name=all value=\"All of This Code's Non-Primes\">
        </form></td>";
            }
            $t_text .= "\n</tr></table>\n";
        }

      # The submit primes buttons (for the list of humans only)

        $temp = '';
  ##    $query2 = "SELECT username FROM person WHERE codes REGEXP :name AND type='person'";
        $query2 = "SELECT username FROM person WHERE codes REGEXP '\\\\b$name\\\\b' AND type='person'";
        try {
            $stmt = $db->prepare($query2);       # Not all are bound
    ##    $stmt->bindValue(':name', "'\\\\b$name\\\\b'");
            $stmt->execute();
        } catch (PDOException $ex) {
            lib_mysql_die('Invalid query in this page view (code.php2 210) ' . $ex->getMessage(), $query2);
        }
      ### echo "SELECT username FROM person WHERE codes REGEXP '\\\\b$name\\\\b' AND type='person';";

        $temp .= "<form method=post action=\"../primes/submit.php\">\n Submit primes using this code as:" .
        "\n<input type=hidden name=code_str value=\"$name\">";
        $submit_buttons = '';

        while ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
            ### echo "<li>$row2[username]";
            $submit_buttons .= "<input type=submit name=username class=\"btn btn-secondary p-1\" value=\"$row2[username]\">\n";
        }
        $temp .= "$submit_buttons (A password will be required before submission is completed.)
	</form>\n";

      # The text comment

        if (!empty($row['comment'])) {
            include_once('bin/modify.inc');
            global $modify_ErrorMessage;
            $t_text .= '<div class="purple lighten-5 p-2 mt-3 w-100"><p id="descriptive"><fieldset class="p-3"><legend class="w-auto"><b>' . "Descriptive Data:</b>
	(<a href=\"includes/disclaimer.php\">report abuse</a>)</legend>\n";
            $temp = modify_adjust_html($row['comment']);  # Balance HTML, remove "evil" code
            $t_text .= "<table class=user><tr><td dir=ltr>\n" . modify_entities($temp) . "\n</td></tr>\n";
          #if (!empty($modify_ErrorMessage)) $t_text .= "<tr><td><font color=red size=-1>
          #          HTML errors in data above: $modify_ErrorMessage</font></td></tr>\n";
            $t_text .= "</table>\n</fieldset>\n</div>";
        }

      # edit comment buttons

        if (!empty($row['comment'])) {
            $temp = "<form method=post action=\"code_edit.php\">
       <input type=hidden name=code_str value=\"$name\">Edit the descriptive data above as: $submit_buttons</form>\n";
        } else {
            $temp .= "<form method=post action=\"code_edit.php\">Add descriptive data explaining this prover-code as:
        <input type=hidden name=code_str value=\"$name\">$submit_buttons</form>\n";
        }

        if (!empty($temp)) {
            $t_text .= '<fieldset class="border p-2 mt-5"><legend class="w-auto">I am a member of this code and I would like to:</legend>' .
            $temp . "\n</fieldset>\n";
        }

      # Additional information

        $t_text .= "\n<div class=\"technote my-5 p-3\">\n";

      # First gather any prover program information into $prog_info

        if (!empty($row['program_id'])) {
            if (!empty($row['comment']) and preg_match('/^x\d+$/', $name) and $row['program_id'] = 560) {
              # In theory the comments on x-codes explain the program
                $prog_info = '(see the descriptive data above)';
            } else {
                $query2 = 'SELECT username, id, PrimesTotal, PrimesActive, ScoreTotal, ScoreActive FROM person WHERE id = :id';
                try {
                    $stmt = $db->prepare($query2);       # Not all are bound
                    $stmt->bindParam(':id', $row['program_id']);
                    $stmt->execute();
                } catch (PDOException $ex) {
                    lib_mysql_die('Invalid query in this page view (16), contact Chris', $query2);
                }
                if ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $prog_info = sprintf("<a href=\"/bios/page.php?id=%s\" class=none>%s</a> &nbsp;", $row2['id'], $row2['username']);
              # If the program has primes we can say more.  Note the scores are summed for each code,
              # then the log taken before it is stored in the person score.
                    if ($row['PrimesActive'] > 0 and $row2['PrimesActive'] > 0 and $row2['ScoreActive'] > 0) {
                            $prog_info .= sprintf(
                                "The primes from this code accounts for %.3f%% of the (active) primes and %.3f%% of the (active) score for this program.",
                                $row['PrimesActive'] * 100 / $row2['PrimesActive'],
                                $row['ScoreActive'] * 100 / exp($row2['ScoreActive'])
                            );
                    }
                } else {
                    $prog_info = '<div class=error>program_id is wrong--warn the admin!</div>';
                }
            }
        } else {
            $prog_info = 'unknown';
        }

        $t_text .= '<p class="font-weight-bold lead">Below is additional information about this entry.</p>';

        $t_text .= add_data('header', '', 'class="technote"');
        $t_text .= add_data("Display (text)", $display_text);
        $t_text .= add_data("Display (short)", $display_short);
        $t_text .= add_data("Database id", "$row[id] (do not use this database id, it is subject to change)");
        $t_text .= add_data("Proof program", $prog_info);
      // from UNIX TimeStamp CDT, to GMT, to locale dependent date
        $t_text .= add_data("Entry last modified", $row['modified']);
      // $t_text .= add_data("Entry created", $row['created']);  Wait--no created entry in the table!
        $t_text .= add_data('footer', '');

        $t_text .= "\n</div>\n";
    } else {
      # People were causing giant loops using wget on errors, so no relative paths here
        $t_title = "Error: No Such Proof-code in Database";
        $t_text = display_query_form('<div class="alert alert-danger" role="alert">Database query returned no results.
	One possibility is that you mistyped the URL.  You might try using the links in the menu to browse the index
	or search this site. If you think there is an error in the system, go to the home page and e-mail the
	technical editor</div>');
    }

  # How to link info (provide if they did not use the code)
    if (empty($_REQUEST['code']) and !empty($name)) {
        $t_text .= '<div class=highlight>To link to this page use the following URL:' .
        "<B><a href=\"https://t5k.org/bios/code.php?code=$name\" class=menu>
	https://t5k.org/bios/code.php?code=$name</a></B></div>";
    }
}

# The templates uses $t_text for the text, $t_title...
$edit = (empty($edit) ? '' : "&edit=$edit");

if (!($next = seek_next($code, +1))) {
    $next = seek_next('', +1);
}
if (!($prev = seek_next($code, -1))) {
    $prev = seek_next('', -1);
}
  $random = seek_next($code, 0);

  $temp_menu = "<div class=\"col-sm-3 text-right mt-n3 mb-4 mt-sm-2 d-print-none\" role=\"group\">
    <a href=\"${t_adjust_path}code.php?code=$prev$edit\"  class=\"btn btn-dark p-1\" role=\"button\">Previous</a>
    <a href=\"${t_adjust_path}code.php?code=$next$edit\"  class=\"btn btn-dark p-1\" role=\"button\">Next</a>
    <a href=\"${t_adjust_path}code.php?code=$random$edit\" class=\"btn btn-dark p-1\" role=\"button\">Random</a>
    </div>";

#  $t_meta['description'] = "???";
  $add2_keywords = $code;


if (empty($t_text)) {
    lib_mysql_die("No such entry found!  Are the id's correct?", $query);
} else {
    if (empty($type)) {
        $type = 'code';
    }


    $t_text = '<div class="row flex-row-reverse">' . $temp_menu . '<div class="col-sm-9 col-print-12"><p>
	<p>Samuel Yates began, and this site continues, a database of the largest known primes.  Primes in that
	database are assigned a proof-code to show who should be credited with the discovery as well as what
	programs and projects they used. (Discoverers have one prover-entry, but may have many proof-codes
	because they use a variety of programs...)</p>
	<p>' . ( empty($code) ? '' : ' ' . sprintf("This page provides data on %s, one of those codes.", $code) ) . "</p></div>
        </div>\n$t_text";
}


include("template.php");

function display_query_form($text = '')
{
    global $id, $code, $edit;
    $out =  $text . "<p>This page displays a single proof-code. To do so, you must specify which using just one of the following.</p>
    <blockquote><form method=post action=\"code.php\"><table class=\"table-sm blue lighten-5\">
      <tr><td class=\"$GLOBALS[mdbltcolor] pl-4 text-right\" ><b>proof-code</b></td>
        <td> &nbsp; <input type=text size=6 maxlength=6 name=code value=\"$code\"></td>
        <td>";
    $out .= "(code name such as g123, p46, L13)</td></tr>
      <tr><td class=\"$GLOBALS[mdbltcolor] pl-4 text-right\"><b>Database id</b></td>
        <td> &nbsp; <input type=text size=6 maxlength=6 name=id value=\"$id\"></td>
        <td>";
    $out .= "(a small positive integer)</td></tr>
      </table>
      <input type=submit class=\"btn btn-primary my-3\" value=\"Search for this one Entry.\">
    </form></blockquote>\n";
    return $out;
}

function add_data($key, $value, $flag = '')
{
    global $medcolor, $drkcolor;
    if ($key == 'header' and $value == '') {
        return "<blockquote $flag>\n  <table>\n";
    }
    if ($key == 'footer' and $value == '') {
        return "</table>\n</blockquote>\n";
    }
    $out = "<tr><td>$key:</td><td>$value</td></tr>\n";
    return $out;
}
