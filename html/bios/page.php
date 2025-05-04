<?php

$t_submenu =  "Display Prover";

include("bin/basic.inc");
include("../../library/format_helpers/datestamp.inc");
$db = basic_db_connect(); # Connects or dies

# This page displays entries depending on the first of the following which is
# defined:
#
#   $id     Display that one item.
#   $username   (CURRENTLY ACCEPTS $lastname FOR username!!!!!!!!!!!!!!!!!!!!)
#
# People were causing ugly loops using wget on error messages, so now the
# paths when there are errors are hard coded (not relative).  Sad....

# Register the form variables:

$my_variables_digits       = '(id|edit)';
$my_variables_alphanumeric = '()';
$my_variables_self_tested  = '()';
$my_variables_general      = '(lastname|username|xx_expand_codes)';  # Example: Proth.exe

# xx_expand_codes is a button name, client values is only checked is unempty, but not used

security_scrub_variables($my_variables_digits, $my_variables_alphanumeric, $my_variables_self_tested, $my_variables_general);

if (isset($_REQUEST['id']) and is_numeric($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
}
if (!empty($_REQUEST['lastname'])) {
    $username = $_REQUEST['lastname'];  # REMOVE EVENTUALLY!
}
if (!empty($_REQUEST['username'])) {
    $username = $_REQUEST['username'];
}
if (!empty($_REQUEST['edit'])) {
    $edit = $_REQUEST['edit'];
}

# Begin our work

$t_text = '';   # Will hold the text (and if stays blank we know we have an error).
$t_allow_cache = 'yes';  # Set to no to stop page caching.
$for_technote = '';  # Will hold items to be added to tech note.

function EditorsLine($id, $username)
{
  # When the user adds 'edit=1' to the path, then this line is added before
  # each entry.
    $out = "<A href=\"/bios/admin/index.php?xx_TableName=person&amp;xx_edit=$id\" class=none>edit</A> |
        <A href=\"/bios/admin/index.php?xx_TableName=person&amp;xx_delete=$id\" class=none>delete</A> ";
    $out = "Editor actions (careful!) : person.id = $id  [ $out ]\n ";
    return '<div class="alert alert-danger font-weight-bold" role="alert">' . $out . "</div>\n";
}

# Originally my Previous, Next, Random buttons were '9Next' and I'd seek the next after 9
# at access time; but this meant each page had additional URI's  ?id=9, ?id=8Next, ?id=10Previous
# and search (indexing) engines hit three times as often.  So now I hard code the id's

function seek_next($surname = '', $step = 1)
{
    global $db;
  # Look for the first entry past $surname.  If $step is negative seeks previous;
  # 0 for a random entry.  Returns the id column of this entry.
  # Result might be empty (NULL) if wrapping past the end.

  # First, form the $query

    $where = '';
    if ($step > 0) {      # Find the next entry
        if (!empty($surname)) {
            $where = 'surname > :surname';
        }
        $direction = 'ORDER BY surname';
    } elseif ($step < 0) {   # Find the previous entry
        if (!empty($surname)) {
            $where = ':surname > surname';
        }
        $direction = 'ORDER BY surname DESC';
    } else {          # Find a random entry
        if (!empty($surname)) {
            $where = 'surname <> :surname';
        }
        $direction = 'ORDER BY RAND()';
    }
    $where .= (empty($where) ? '' : ' AND ') . 'PrimesTotal>0'; # Just real entries!
    $query = "SELECT id FROM person WHERE $where $direction LIMIT 1";
  # Okay, that should do it, spit out answer

    try {
        $stmt = $db->prepare($query);
        if (!empty($surname)) {
            $stmt->bindParam(':surname', $surname);
        }
        $stmt->execute();
    } catch (PDOException $ex) {
        lib_mysql_die('seek_next: invalid next query', $query);
    }
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        return($row['id']);
    } else {
      # If there is no entry this size, return NULL
        return(null);
    }
}


# Build the variable part of the query

$where = '';
if (!empty($username)) {
    $where = "person.username LIKE BINARY '$username'";
} elseif (!empty($id) and intval($id) > 0 and is_numeric($id)) {  # Get single term to display
    $where = "person.id=" . intval($id);
} else {
    $temp = (empty($id) ? '' : '<div class=error>The database id must be a small positive integer.</div><p> ');
    $t_title = 'Display a Prover-Account';
    $t_text = display_query_form($temp);
}

$t_adjust_path = "/bios/";
// print "<li>$_SERVER[PHP_SELF]";
// print "<li>$t_adjust_path";

# Do the query
if ($where) {
    $The5000th = lib_get_column('prime.rank <= 5000 ORDER BY prime.rank DESC', 'prime', 'log(score)', $db);

    $select = "*, floor(exp(ScoreActive-$The5000th)+0.5) as ScoreNormal";

    $query = "SELECT $select FROM person WHERE $where";
  # print $query;

    try {
        $stmt = $db->query($query);
    } catch (PDOException $ex) {
        lib_mysql_die('Invalid query page.php 125, contact the admin', $query);
    }

  # Get the term
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $name = $row['name'];
        $t_title = $name;
        $username = $row['username'];
        $id = $row['id'];
        $type = $row['type'];

        $created = \Format_Helpers\formatDatestamp($row['created']);
        $modified = \Format_Helpers\formatDatestamp($row['modified']);

        if (isset($GLOBALS['edit']) and $GLOBALS['edit'] == 1) {
            $t_text .= EditorsLine($id, $row['username']) . "<P>";
        }

      ##### Flag those that submit bad names for thier accounts (usually just
      ##### incomplete) and while at it, set the local flag $NameError to a short
      ##### description of the error
        $NameError = '';
        if (!empty($row['NameError'])) {
            lib_reset_colors('brown');
            if (empty($edit)) {
                $NameError = $row['NameError'];  # Pages functions not disabled for editors
            }
            $t_text .= '<br><div class=error><p class="text-center">Submission Error</p>
	<p>This entry is not complete (this usually means that it does not have
	a complete surname and given name). Because of this error, this entry
	cannot be listed in indexes of provers, or on the lists of ranked
	provers.  The search buttons below will also be disabled until this
	error is addressed.  To correct this error, the owner of this page must
	e-mail the corrected information to the editor.</p>' .
            "<p>[Error details: $NameError]</p></div>";
        }
      #####

      # Id 560 is the 'unknown program' account and should have no primes so
      # it will not get ranked...

        if (empty($row['PrimesTotal']) && $id != 560) {
            $t_text .= "<p><div class=error>This entry has no associated primes and may be deleted anytime.
        Until this entry has primes, it will not show up in the index list.</div>\n<p>\n";
          #  Why index this if there is no primes yet?
            $t_meta['add_lines'] = '<meta name="robots" content="noindex, nofollow, noarchive">';
        }


      # Add a picture?
        $temp2 = 'User supplied image--mail editor to report abuse.';
        if (!empty($row['picture'])) {
            $temp = preg_replace('/^(\S+)\s?(.*)$/', "\"$1\" $2", $row['picture']);
            $temp = "<div class=\"col col-md-3 float-right\"><img src=$temp align=\"right\" class=\"img-fluid\" alt=\"$temp2\"></div>\n";
        # what about picture_link ?  Removed for now
        #   if (!empty($row['picture_link'])) {
        #     $temp = "<a href=\"$row[picture_link]\" class=offsite>$temp</a>\n";
        #   }
            $t_text .= $temp;
        }

      # This is a three column table: left is row title, right is text (sometimes split)
      # Need to support a button if there are wild_codes that are not expanded

        if (!empty($row['wild_codes']) and empty($_REQUEST['xx_expand_codes'])) {
            $t_text .= "<form action=\"${t_adjust_path}page.php\" method=post>
		   <input type=hidden name=id value=$id>";
        }

        $t_text .= "\n<div class=\"col-9\">\n<table class=\"table table-sm table-responsive table-hover m-3\">\n";

        $t_text .= "<tr><th class=\"font-weight-bold\">Proof-code(s):</th>\n<td colspan=\"2\">";
        if (!empty($row['codes'])) {
            $temp = empty($_REQUEST['xx_expand_codes']) ? 20 : 2000;
            $t_text .= ExpandCodes($row['codes'], $id, $temp);
        } else {
            $t_text .= "No proof-code has created for this entry yet, use the link below to create one.";
        }
        $t_text .= "</td></tr>\n";

        if (!empty($row['wild_codes'])) {
            if (empty($_REQUEST['xx_expand_codes'])) {
                $t_text .= '<tr><th class="font-weight-bold">Active wild codes:' . "</th>\n<td width='15%'>$row[wild_codes]</td>" .
                "\n<td><input type=submit name=xx_expand_codes
			value=\"Display Wild Code Matches\"></td>\n</tr>\n";
            } else {
                $temp = ExpandCodes(GetMatches($row['wild_codes']), $id, 10000);
                $t_text .= '<tr><th class="font-weight-bold">Wild code matches:' . "</th>\n<td colspan=\"2\">$temp</td></tr>";
            }
        }

        if (!empty($row['prog_prefix'])) {
            $t_text .= '<tr><th class="font-weight-bold">Code prefix:' . "</th><td colspan=\"2\">$row[prog_prefix]</td></tr>\n";
        }

        $t_text .= '<tr><th class="font-weight-bold align-middle">E-mail address:' . "</th>\n<td colspan=\"2\">";
        if ($row['hide_email'] == 'yes') {
            $t_text .= '(e-mail address unpublished)';
        } else {                              # Ah, have permission to publish
            if (empty($row['email']) or $row['email'] == 'unknown') {
                $t_text .= '(e-mail address unknown)';              # oops, does not exist
            } elseif ($row['hide_email'] == 'munge') {
                $temp = preg_replace('/(\w)/', '<font color=black>$1</font>', $row['email'], 3);
                $temp = "<table class=\"table-borderless\"><tr><td>$temp</td></tr></table>\n";
                $temp = preg_replace('/@/', '</td><td>(at)</td><td>', $temp); # ah, must munge
                $temp = preg_replace('/\./', '</td><td>(dot)</td><td>', $temp);
                $t_text .= $temp;
            } else {
                $t_text .= "<A href=\"mailto:$row[email]\">$row[email]</A>";    # Free and clear!
            }
        }
        $t_text .=  "</td></tr>\n";

        if (!empty($row['webpage'])) {
          # Sometimes folks write 'none' for their web page, or put 'coming soon'
          # so anything that does not start http:// is excluded.
            if (preg_match('/^(ftp|http):\/\//', $row['webpage'])) {
                $temp = "<a href=\"$row[webpage]\" class=offsite>$row[webpage]</A>";
            } else {
                $temp = $row['webpage'];
            }
            $t_text .= '<tr><th class="font-weight-bold">Web page:' . "</td><td colspan=\"2\">$temp</td></tr>\n";
        }

        $t_text .= "<tr><th class=\"font-weight-bold\">Username</th>\n<td>$row[username]</td>
	<td>(" . sprintf("entry created on %s", $created) . ")</td></tr>\n";
        $t_text .= '<tr><th class="font-weight-bold">Database id' . ":</th><td>$row[id]</td>
	<td>(" . sprintf("entry last modified on %s", $modified) . ")</td></tr>\n";

        if (!empty($row['program_does'])) {
            $does = preg_replace('/,/', ', ', $row['program_does']);
            if ($type == 'program') {
                $t_text .= "<tr><th style='width: 8em' class='font-weight-bold'>Program Does" .
                "</b>&nbsp;<a href=\"${t_adjust_path}help/program_does.php\">*</a>:</th>
		<td colspan=\"2\">$does</td></tr>\n";
            } else {
                $t_text .= "<tr><td>ERROR:</td>
		<td colspan=\"2\">program_does is non-empty ($does) for non-program.</td></tr>\n";
            }
        }

        if (!empty($row['PrimesTotal'])) {
          # Number of primes: total, and active (if any)

            $t_text .= "<tr><td class=\"font-weight-bold\">Active primes:</td><td colspan=\"2\">";
            if (empty($row['PrimesActive'])) {
                $t_text .= "This entry has no primes on the current list.";
            } else {
                $t_text .= "on current list" . ': ' . $row['PrimesActive'];
                if ($type == 'person') {
                    $t_text .= ' (' . sprintf("unweighted total: %d", $row['PrimesActiveRaw']) . ')';
                }
                if (empty($NameError)) {
                    $t_text .= ((empty($row['PrimesRank']) or $row['PrimesRank'] == 99999) ? ' (' . "unranked" . ')' :
                    ", <a href=\"$t_adjust_path" .
                    "top20.php?type=$type&amp;by=PrimesRank&amp;at=$row[PrimesRank]&amp;id=$id\">
		rank by number</a> " . $row['PrimesRank']);
                } else {
                    $t_text .= ', rank by number <font color=red>xxx</a>';
                }
            }
            $t_text .=  "</td></tr>\n";

            $t_text .= "<tr><th class=\"font-weight-bold\">Total primes:</th>\n\t<td colspan=\"2\">";
            $t_text .= "number ever on any list: " .
            (empty($row['PrimesTotal']) ? '0' : $row['PrimesTotal']);
            if ($row['PrimesTotal'] > 16000) {
                $for_technote .= "Note that the prime search page will not display more than 16,000 primes.<br>";
            }

            if ($type == 'person') {
                $t_text .= ' (' . sprintf("unweighted total: %d", $row['PrimesTotalRaw']) . ')';
            }
            $t_text .=  "</td></tr>\n";

          # Score for primes: total, and active (if any)
            $t_text .=  '<tr><th class="font-weight-bold">' . "Production score:</th>\n\t<td colspan=\"2\">";
            if (empty($row['ScoreActive'])) {
                $t_text .=  "no primes, so no score for current list";
            } else {
                $t_text .= sprintf("for current list %d (normalized: %d)", $row['ScoreActive'], $row['ScoreNormal']);
            }

            $t_text .= ((empty($row['ScoreTotal']) or $row['ScoreTotal'] == $row['ScoreActive']) ?
            '' : ', ' . sprintf("total %.4f", $row['ScoreTotal']));

            if (!empty($row['ScoreActive'])) {
                if (empty($NameError)) {
                    $t_text .= ((empty($row['ScoreRank']) or $row['ScoreRank'] == 99999) ? ' (unranked)' :
                    ", <a href=\"$t_adjust_path" .
                    "top20.php?type=$type&amp;by=ScoreRank&amp;at=$row[ScoreRank]&amp;id=$id\">
		rank by score</a> " . $row['ScoreRank']);
                } else {
                    $t_text .= ', rank by score <font color=red>xxx</a>';
                }
            }

            $t_text .= "</td></tr>\n";

          # Now, can we seek most recent prime and largest prime?  Entrance average too?

            include_once('bin/make_prime_pretty.inc');  # to reformat the prime for HTML
            $unprocessedPrimes = 0;
            if ($row['PrimesActiveRaw'] > 0) {
          # To this I'll append the "ORDER xxx LIMIT 1" to get the largest and latest, and
          # ... for mean entrance score
                $query_start = "SELECT prime.id, prime.description, prime.submitted, prime.submitted_precision, prime.credit, prime.digits\n";
                $query_tables = 'prime';

                if (!empty($row['codes'])) {
                    $row['codes'] = preg_replace('/ /', '', $row['codes']); # somehow had spaces get into these lists of codes
                }
                $query_core = (empty($row['codes']) ? '' :
                "\t  prime.credit IN ('" . preg_replace('/,/', "','", $row['codes']) . "')");
                if (!empty($row['wild_codes'])) {
                      $query_core = "person.id = $id AND " . (empty($row['codes']) ? '' : "\t(\n$query_core\n\tOR\n");
                      $query_core .= "\t   prime.credit RLIKE
		CONCAT(CONCAT('(',REPLACE(REPLACE(wild_codes,',','|'),'\\\\d','[0-9]')),')')";
                      $query_core .= (empty($row['codes']) ? '' : "\n\t)");
                      $query_tables = 'prime, person';
                }
              # primes.prime table has "prime is enum('Composite','Untested','InProcess','PRP','Proven','External')"
              # so to omit composites, untested and inprocess need "prime > 3" (altered below!)
                $query_sub_core = $query_core;
                $query_core = "$query_core AND prime.prime > 3";

          # First, the largest
                $query2 = $query_start . " FROM $query_tables WHERE $query_core\nORDER BY prime.log10 DESC LIMIT 1";
              # Had prime.rank, prime.log10 DESC; but this took about 80 times as long for MySQL!
                try {
                    $stmt = $db->query($query2);
                } catch (PDOException $ex) {
                    lib_mysql_die('Invalid query page.php 344, contact the admin', $query2);
                }
                if ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $temp = MakePretty($row2['description']);
                    $t_text .= '<tr><th class="font-weight-bold">' . "Largest prime:</th>
		<td colspan=\"2\"><a href=\"/primes/page.php?id=$row2[id]\" class=no_underline>$temp</a>\n" .
                    '&rlm;' . sprintf(
                        '(%s digits) via code %s on %s',
                        '&lrm;' . $row2['digits'],
                        "<A href=\"${t_adjust_path}code.php?code=$row2[credit]\">$row2[credit]</a>",
                        \Format_Helpers\formatDatestamp($row2['submitted'], $row2['submitted_precision'])
                    ) . "</td></tr>\n";
                }

        # Now the latest
                $query2 = $query_start . " FROM $query_tables WHERE $query_core\nORDER BY prime.submitted DESC LIMIT 1";
                try {
                    $stmt = $db->query($query2);
                } catch (PDOException $ex) {
                    lib_mysql_die('Invalid query page.php 361, contact the admin', $query2);
                }
                if ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $temp = MakePretty($row2['description']);
                    $t_text .= '<tr><th class="font-weight-bold">' . "Most recent:</th>
		<td colspan=\"2\"><a href=\"/primes/page.php?id=$row2[id]\" class=no_underline>$temp</a>\n" .
                    '&rlm;' . sprintf(
                        '(%s digits) via code %s on %s',
                        '&lrm;' . $row2['digits'],
                        "<A href=\"${t_adjust_path}code.php?code=$row2[credit]\">$row2[credit]</a>",
                        \Format_Helpers\formatDatestamp($row2['submitted'], $row2['submitted_precision'])
                    ) . "</td></tr>\n";
                }

        # Now the Mean Entrance Rank
                $query_start = "SELECT AVG(e_rank) AS avg, MIN(e_rank) AS min, MAX(e_rank) AS max";
                $query2 = "$query_start FROM $query_tables WHERE $query_core  AND prime.onlist<>'no'";
                try {
                    $stmt = $db->query($query2);
                } catch (PDOException $ex) {
                    lib_mysql_die('Invalid query page.php 378, contact the admin', $query2);
                }
                if ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $t_text .= '<tr><th class="font-weight-bold">' . "Entrance Rank:</th>
		<td colspan=\"2\">" . sprintf(
                        "mean %.2f (minimum %d, maximum %d)",
                        $row2['avg'],
                        $row2['min'],
                        $row2['max']
                    ) . "</td></tr>\n";
                }

            # Now count the unprocessed...
                $query2 = "SELECT COUNT(*) as number FROM $query_tables WHERE prime < 4 AND prime > 1 AND " . $query_sub_core;
                try {
                    $stmt = $db->query($query2);
                } catch (PDOException $ex) {
                    lib_mysql_die('Failed to get row from prime_blob', $query2);
                }
                if ($row2 = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($row2['number'] > 0) {
                        $t_text .= "<tr><th class=\"font-weight-bold\">Unprocessed:</th>\n<td colspan=\"2\">" .
                        sprintf('prime submissions still untested or inprocess: %s.', $row2['number']) . "</td></tr>\n";
                        $unprocessedPrimes = $row2['number'];
                    }
                }
            }

            $t_text .= "\n</table></div>\n";  # this div contains the column starts below image

            if (!empty($row['wild_codes']) and empty($_REQUEST['xx_expand_codes'])) {
                $t_text .= "</form>\n";
            }

          # Prepare credit string for database searches
          # Note that wild codes take special care.  I change the Perl \d to the mysql [[:digit:]]
          # and remove the leading ^ from the wild codes (added back in in the search engine)

            $temp = ucfirst($type);
            if (!empty($row['wild_codes'])) {
                $temp2 = preg_replace('/\\\\d/', '[[:digit:]]', $row['wild_codes']);
                $temp2 = preg_replace('/\^/', '', $temp2);  # Added in search engine
            } else {
                $temp2 = '';
            }
            $temp2 .= (($temp2 and $row['codes']) ? ',' : '') . $row['codes'];

          # Buttons to select primes--hopefully not followed by robots!
          # These should not function for those with NameErros (as punishment)
            $ButtonAddress = $NameError ? '' : '/primes/search.php';  # NameError folks don't get to search

            $t_text .= "\n<div class=\"row d-print-none\">" .  # Don't print these buttons
            "<div class=\"col col-sm-6 text-center\">
	<form method=post action=\"$ButtonAddress\">
	  <input type=hidden name=Discoverer value=\"$temp2\">
	  <input type=hidden name=Number value=\"$row[PrimesActiveRaw]\">
	  <input type=hidden name=OnList value=yes>
	  <input type=hidden name=Style value=HTML>
	  <input type=submit class=\"btn btn-primary\" name=all value=\"" . sprintf("This %s's Current Primes", $temp) . "\">
	</form></div>
	<div class=\"col col-sm-6 text-center\">
	<form method=post action=\"$ButtonAddress\">
	  <input type=hidden name=Discoverer value=\"$temp2\">
	  <input type=hidden name=Number value=\"" . ($row["PrimesTotalRaw"] + $unprocessedPrimes) . "\">
	  <input type=hidden name=OnList value=all>
	  <input type=hidden name=Style value=HTML>
	  <input type=submit name=all class=\"btn btn-primary\" value=\"" . sprintf("All of This %s's Primes", $temp) . "\">
	</form></div>\n";
            $for_technote .= "Unverified primes are omitted from counts and lists until verification completed.<br>";
        } else {
            $t_text .= "\n</table>\n";
        }

      # How to link info
        if (empty($id)) {
            $t_text .= "<div class=highlight>To link to this page use the following URL:
        <b><a href=\"https://t5k.org/bios/page.php?id=$id\" class=menu>
        https://t5k.org/bios/page.php?id=$id</a></b></div>";
        }

      # Finally, the prover-account data.

        if (!empty($row['description'])) {
            $t_text .= '<div class="purple lighten-5 p-2 mt-3 w-100"><p id="descriptive"><fieldset class="p-3"><legend class="w-auto"><b>' .
            "Descriptive Data:</b>
        (<a href=\"includes/disclaimer.php\">report abuse</a>)</legend>\n";
            include_once('bin/modify.inc');
            global $modify_ErrorMessage;
            $temp = '<p>' . $row['description'] . '</p>';
            $temp = modify_entities($temp);
            $temp = modify_adjust_html($temp);  # Balance HTML, remove "evil" code
            $t_text .= "\n<table class=user><tr><td dir=ltr>\n$temp\n</td></tr></table>\n";
    #      if (!empty($modify_ErrorMessage)) $t_text .= "<blockquote><font color=red size=-1>
    #       Error in data above: $modify_ErrorMessage</font></blockquote>\n";
            $t_text .= "</fieldset>\n</div>\n";
        }

        $t_text .= "<p id=action>\n";

      # For projects, let the person get a project code.  If they do not want this to be
      # easy, then leave person.ProjectAlsoCredits empty.

        if ($type == 'project' and !empty($row['ProjectAlsoCredits'])) {
            $t_text .= "<h4>I found a prime as a member of this group and I would like to</h4>\n<ul>\n";
            $t_text .= "<li><a href=\"${t_adjust_path}newprover.php\">Create a New Prover-Account</a>
	  If you already have a prover-account, you must use it, you may have only one.\n";
            $t_text .= "<li><a href=\"${t_adjust_path}newcode.php?project=$username\">" .
            'Create a New Proof-Code' . "</a>
	 " . 'Remember that all unused codes will be deleted after 24 hours, so do not create a proof-code until you need it.'
             . "\n</ul>\n";
        }

      ## List unused or technical data for this entry
        $t_text .= '<div class="technote mt-4 p-2 w-100">' . sprintf(
            'Surname: %s (used for alphabetizing and in codes).',
            $row['surname']
        ) . (empty($for_technote) ? '' : '<br>' . $for_technote) . "</div>\n";


      # Forms for user to edit page, change password...
        $t_text .= '<div class="d-print-none blue lighten-5 mt-5">';  # don't print this form

        $temp = ($type == 'person' ? 'I am %s and I would like to' : 'I administer %s and I would like to');

        $t_text .= '<fieldset class="border p-2 m-3">
       <legend class="w-auto h6">' . sprintf($temp, $name) . '</legend>';

        $t_text .= '<div class="row"><div class="col-12 col-md-8">' .
        "<form action=\"${t_adjust_path}edit.php\" method=post>
	   <input type=hidden name=xx_person_id value=$row[id]>
	   <input type=submit class=\"btn btn-light p-1\" name=xx_action value=\"Edit Prover-Account\"> "
         . "Edit this page" . '
	</form>
	</div>

        <div class="col-12 col-md-4">' . "
	<form action=\"${t_adjust_path}edit.php\" method=post>
	   <input type=hidden name=xx_person_id value=$row[id]>
	   <input type=submit name=xx_action class=\"btn btn-light p-1\" value=\"Change Password\">
	</form>
       </div>
     </div>\n";

        if (($type == 'person' or $type == 'other') and !empty($row['codes'])) {
            $t_text .= '<div class="row mt-3">
	   <div class="col pl-4"><form method=post action="/primes/submit.php"><p>' .
             "Submit primes using the proof-code: <input type=hidden name=xx_person_id value=$id>\n";
            $Codes = explode(",", $row['codes']);
            for ($i = 0; $i < count($Codes); $i++) {
                $Codes[$i] = preg_replace('/ /', '', $Codes[$i]);
                $t_text .= "<input type=submit class=\"btn btn-light p-1\" name=code_str value=\" $Codes[$i] \">\n";
            }
            $t_text .= "(a password will be required)</p></form></div></div>\n";
        }

        if ($type == 'person') {
            $t_text .= '
           <div class="row">
             <div class="col-12 col-md-4">
               <form action="' . $t_adjust_path . 'newcode.php" method=post>
	         <input type="hidden" name="xx_person_id" value="' . $row['id'] . '">
	         <input type="submit" class="btn btn-light p-1" value="Create a New Proof-Code">
               </form>
             </div>
             <div class="col-12 col-md-8">
	       (Reuse an old code if your are using the same programs, projects... Only create a new code if
		you are doing something differently than in the past. Duplicate codes will be merged.
	     </div>
           </div>' . "\n";
        }

        $t_text .= "</fieldset>\n</div>\n</div>";
      # end of forms
    } else {
      # People were causing giant loops using wget on errors, so no relative paths here
        header("HTTP/1.0 404 Not Found");
        $t_title = "Error: No Such Prover-Account in Database";
        $t_text = display_query_form("<div class=error>"
        . "Database query returned no results.  One possibility is that you mistyped the URL.  You might try using the links in the menu to browse the index or search this site."
        . "</div><P>");
    }

    $sort = $row['username'] ?? '';

# include_once('bin/modify.inc');
  # Perhaps has TeX like entities we need to translate!
# $t_text = modify_entities($t_text);
  # Definitely expect words to cross-link (using second parameter to
  # avoid links back to this same page!
# $t_text = modify_add_links($t_text,$sort);
}

if (empty($t_text)) {
    lib_mysql_die("No such entry found!  Are the id's correct?", $query);
}

# The templates uses $t_text for the text, $t_title...
$edit = (empty($edit) ? '' : "&amp;edit=$edit");

# Let $next, $prev and $random be id's that point to the next, prev and a
# random entry.  I use to use 'next' and 'prev'...  and let the system get
# these only when pressed--but for robots a single way of reaching
# each page is best (id=...) and keeps if from thinking it found
# three different pages

$temp_menu = '';  # will hold the next/prev/rand menu

if (!empty($row['surname'])) {
    $surname = $row['surname'];
    if (!($next = seek_next($surname, +1))) {
        $next = seek_next('', +1);
    }
    if (!($prev = seek_next($surname, -1))) {
        $prev = seek_next('', -1);
    }
    $random = seek_next($surname, 0);

    if (!isset($t_adjust_path)) {
        $t_adjust_path = '';
    }
    $temp_menu = "<div class=\"col-sm-3 text-right mt-n3 mb-4 mt-sm-2 d-print-none\" role=\"group\">
    <a href=\"${t_adjust_path}page.php?id=$prev$edit\" class=\"btn btn-dark p-1\" role=\"button\">Previous</a>
    <a href=\"${t_adjust_path}page.php?id=$next$edit\" class=\"btn btn-dark p-1\" role=\"button\">Next</a>
    <a href=\"${t_adjust_path}page.php?id=$random$edit\" class=\"btn btn-dark p-1\" role=\"button\">Random</a>
    </div>";
    $t_meta['description'] = "Welcome to the Prover Database for the List of Largest " .
    "Known Primes.   These pages contain a collection of records, resources and " .
    "results all related to prime numbers.  " .
    (empty($name) ? '' : "This pages contains the prover entry '$name.'");
    if (!empty($name)) {
        $t_meta['add_keywords'] = "$name";
    }
} else {
    $t_meta['description'] = "Welcome to the Prover Database for the List of Largest
    Known Primes.   These pages contain a collection of records, resources and
    results all related to prime numbers.  This page is display a single prover-account.";
}

# Assemble the page by writing the top of the text

if (empty($type)) {
    $type = 'person';  # col-prnt-12 is defined in my css to make it print full width
}
  # using row flex reverse so get the next/prev/rand to slide on top on small screens but be scond on large
  $t_text = '<div class="row flex-row-reverse">' . $temp_menu . '<div class="col-sm-9 col-print-12">' .
    "<img src=\"/bios/includes/gifs/$type.gif\" alt=\"$type\" class=\"img-fluid float-left mr-2\"><p>"
    . "A titan, as defined by Samuel Yates, is anyone who has found a
	<a href=\"/glossary/page.php?sort=TitanicPrime\" class=\"glossary\">titanic prime</a>. 
	This page provides data on those that have found these primes.
	The data below only reflects on the primes currently on the list.
	(Many of the terms that are used here are explained on
	<a href=\"${t_adjust_path}help/bio_page_help.php\">another page</a>.)</p></div>
        </div>\n$t_text";


# Done--dump into the template

include("template.php");

# support routines

function display_query_form($text = '')
{
    global $t_adjust_path;
    return $text . "\n<p>This page displays a single prover-account. To do so, you must specify which using just one of the following.</p>
    <blockquote>
    <form method=post action=\"${t_adjust_path}page.php\"><table cellspacing=3 cellpadding=3>
      <tr><td bgcolor=\"$GLOBALS[medcolor]\">Database id</td>
        <td><input type=text size=4 maxlength=6 name=id value=\"" .
    (isset($id) ? $id : '') . "\"></td>
        <td>(" . "a small positive integer" . ")</td></tr>
      <tr><td bgcolor=\"$GLOBALS[medcolor]\">Username</td>
        <td><input type=text size=16 maxlength=16 name=username value=\"" .
    (isset($username) ? $username : '') . "\"></td>
        <td>(a prover-account username)</td></tr>
      </table>
      <input type=submit class=\"btn btn-primary p-2 m-4\" value=\"Search for this one Entry\">
    </form>
    </blockquote>\n";
}

function GetMatches($pattern)
{
    global $db;
    $query = "SELECT name FROM code WHERE
	CONVERT(name USING utf8mb4) REGEXP
	CONVERT(CONCAT('(',REPLACE(REPLACE('$pattern',',','|'),'\\d','[0-9]'),')') USING utf8mb4) COLLATE utf8mb4_unicode_520_ci
	ORDER BY LENGTH(name), name";
    $sth = lib_mysql_query($query, $db, 'Error page.php 600 (contact the admin): ');
    $out = '';
    $delim = '';
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $out .=  $delim . $row['name'];
        $delim = ',';
    }
    return $out;
}

function ExpandCodes($list, $id, $max = 20)
{
    global $t_adjust_path;
    $Codes = explode(",", $list);

    if (count($Codes) < $max) {
        return list_them(0, count($Codes), $Codes);
    }

    $out  = "<form action=\"${t_adjust_path}page.php\" method=post>\n";
    $out .= list_them(0, 5, $Codes);
    $out .= "\n... <input type=hidden name=id value=$id>
	 	<input type=submit name=xx_expand_codes value=\"" .
      sprintf('Display the other %d' . "\">\n", count($Codes) - 10) . ' ... ';
    $out .= list_them(count($Codes) - 5, count($Codes), $Codes) . "\n</form>\n";

    return $out;
}

// Just needed for the proceeding function

function list_them($start, $count, $Codes)
{
    global $t_adjust_path;
    $delim = '';
    $out = '';
    for ($i = $start; $i < $count; $i++) {
        $Codes[$i] = preg_replace('/ /', '', $Codes[$i]);
        $out .= "$delim<a href=\"${t_adjust_path}code.php?code=$Codes[$i]\">$Codes[$i]</a>";
        $delim = ', ';
    }
    return $out;
}
