<?php

// Enough?    $Description = preg_replace('/[^\w\d\s*+\-!(){}[\]?$]/','',$Description);
// Same for comment also usercommentmatch

// PROBLEM!!  'ORDER BY rank' is the way to go here, but what about unranked primes?  Currently using log10.

// Search through the database for primes.
//
// The actual text of the search page is the files search.php
// The routines that format the output are in bin/ShowPrimes.inc
//
// Variable(s) that control the output (untainted in the main program):
//
//  Style       Set to 'HTML', 'Text' (default) or 'NoBorder' (text without template/menus...)
//  Advanced    If set, then displays the advanced form (1 or unset)
//  OrderBy     Sort order (currently (Date|Rank))
//
// All of the other variable (see 'untaint') control the search:
//
//  MinRank, MaxRank, MinDigits, MaxDigits, MinAge, MaxAge, Discoverer,
//  Description, Comment, Number, OnList, MinERank, MaxERank
//
// Search variables are cleaned up and the query is formed in the procedure 'Untaint'

include_once("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

$Criteria = '';     # Global to UnTaint; will be filled with an text list of what we searched for
$ToRefine = '';     # Global to UnTaint; filled with search fields should we need to repeat search
            # (a couple other values added just below)
$time = microtime(1);   # For total page time
$Max_Primes = 500000;   # Absolute limit on number of primes this page can find (MUST BE ADDED TO search.txt)
ini_set('memory_limit', '512M');

$t_text = '';       # Build output for template here
$t_meta['add_lines'] = '<style> code {color: red} dt {font-weight: bold} </style>';
$t_submenu = 'prime search';

$style = (isset($_REQUEST['Style']) ? $_REQUEST['Style'] : 'Text');
if (!preg_match('/(HTML|text|NoBorder)/', $style)) {
    $style = 'Text';  // untaint
}

$query = UnTaint(); # Untaint data, for $Query;

###### echo "<pre>$query</pre>";

# $advanced = (isset($_REQUEST['Advanced']) ? '&amp;Advanced='.$_REQUEST['Advanced'] : '');
$advanced = (isset($_REQUEST['Advanced']) ? '&amp;Advanced=1' : '');

# Add a menu to change from the default output style (to prime list)
$temp = '<form method=post class="my-3"><strong>Search Output Style:</strong>
	<INPUT type=radio CHECKED value=HTML name=Style> HTML
	<INPUT type=radio value=Text name=Style> Text
	<INPUT type=radio value=NoBorder name=Style> Text (printable)
	<input type=submit value="Display Again" class="btn btn-primary py-2">
	<input type=submit name=refine value="Refine Search" class="btn btn-primary py-2">' . $ToRefine . '</form>';
$temp = preg_replace("/(value=$style)/", "$1 checked", $temp);
if ($style != 'NoBorder') {
    $t_text .= $temp;
}

if ($query and empty($_REQUEST['refine'])) {
    $t_title = "Database Search Output";
    $options['unverified'] = 'warn';  # If shown, warn about unverified primes

    include_once('bin/ShowPrimes.inc');
    if ($style == 'HTML') {
        $options['style'] = 'HTML';
        $options['link rank'] = 'yes';
        $options['add links'] = 'yes';
        $options['description'] = 'MakePretty';
    } else { # 'Text' or 'NoBorder'
        $options['style'] = 'Text';
        $options['wrap'] = 78;
        $t_text .= "<pre>\n";   # Protect the text from HTML mangling
    }
    $t_text .= ShowPrime('head', $options);

    $query_time = microtime(1);
    $stmt = $db->query($query);
    $results = $stmt->fetchALL(PDO::FETCH_ASSOC);
    $query_time = microtime(1) - $query_time;
    $Criteria .= "Query required " . round($query_time, 4) . " seconds.&nbsp;";

  # Get the rows
    $count = 0;
    foreach ($results as $row) {
        preg_match("/^\d\d(\d\d).*/", $row['submitted'], $temp);
        $t_text .= ShowPrime($row, $options);
        $count++;
    }

    $t_text .= ShowPrime('tail', $options);    # Prints ending info, my name...
    if ($style != 'HTML') {
        $t_text .= "\n</pre>";
    }
    $t_text .= PrintTail($style);
} else {
    $t_title = "Database Search Query";
    $out = file_get_contents(empty($advanced) ? 'search.txt' : 'adv_search.txt');
  # if we are refining the search, need to put old values into input fields:
    foreach ($_POST as $key => $val) {
        if (!preg_match('/^\w+$/', $key)) {
            continue; # all our key names are one word
        }
      # Radio (note: assumes order for input fields: type=radio name=xxxxx value=xxxxxx [checked]
      # need to add 'checked' if value matches, otherwise remove checked
        if (preg_match("/type=radio name=$key/", $out)) {
            $out = preg_replace("/(type=radio name=$key value=\w+)\s+checked/i", "$1", $out);
            $out = preg_replace("/(type=radio name=$key value=$val)/i", "$1 checked", $out);
        } elseif (preg_match("/type=checkbox name=\"$key\[\]\"/", $out)) {
            $out = preg_replace("/(type=checkbox name=\"$key\[\]\" value=\w+)\s+checked/i", "$1", $out);
            foreach (explode(',', $val) as $key2 => $val2) {
                $out = preg_replace("/(type=checkbox name=\"$key\[\]\" value=$val2)/i", "$1 checked", $out);
            }
        } else {  # this leaves only the non-radio fields
            $out = preg_replace("/(name=$key)(\s+value=\w+)?/", "$1 value=\"$val\"", $out);
        }
    }
  # Would like to be able to pass on parameters via the URI such as edit=1...; Nah, removed
  #  $temp = (empty($_SERVER["QUERY_STRING"]) ? '' : '?'.$_SERVER["QUERY_STRING"]);
  #  $out = preg_replace('/(action="search\.php)(.*?)(">)/',"\1$temp\3",$out);
  # Okay, we're done
    $t_text = $out;
}

# Alright, let's output!
if ($style == 'NoBorder') {
    print "<B>The Largest Known Primes' database search output:</b><P>\n$t_text";
} else {
    include("template.php");
}

exit;

########################## DoSearch ###########################

function hidden($field, $value)
{
    return '<input type=hidden name=' . $field . ' value="' . $value . "\">\n";
}

function UnTaint()
{
  # Untaints $_REQUEST['var'] into $var.  Checks $Number.
# Returns and appropriate database query (empty if no variables are set)
    global $Criteria, $ToRefine, $db;

    $Where = '';

    #  Load and check rank limits

    if (isset($_REQUEST['MinRank']) and preg_match('/(\d+)/', $_REQUEST['MinRank'], $Matches)) {
        $Criteria .= "\nMinimum rank =$Matches[0].&nbsp; ";
        $Where .= (empty($Where) ? '' : ' AND ') . "prime.rank >= $Matches[0]";
        $ToRefine .= hidden('MinRank', $Matches[0]);
    }
    if (isset($_REQUEST['MaxRank']) and preg_match('/(\d+)/', $_REQUEST['MaxRank'], $Matches)) {
        $Criteria .= "\nMaximum rank =$Matches[0].&nbsp; ";
        $Where .= (empty($Where) ? '' : ' AND ') . "prime.rank <= $Matches[0]";
        $ToRefine .= hidden('MaxRank', $Matches[0]);
    }

    #  Load and check entrance rank limits

    if (isset($_REQUEST['MinERank']) and preg_match('/(\d+)/', $_REQUEST['MinERank'], $Matches)) {
        $Criteria .= "\nMinimum entrance rank =$Matches[0].&nbsp; ";
        $Where .= (empty($Where) ? '' : ' AND ') . "prime.e_rank >= $Matches[0]";
        $ToRefine .= hidden('MinERank', $Matches[0]);
    }
    if (isset($_REQUEST['MaxERank']) and preg_match('/(\d+)/', $_REQUEST['MaxERank'], $Matches)) {
        $Criteria .= "\nMaximum entrance rank =$Matches[0].&nbsp; ";
        $Where .= (empty($Where) ? '' : ' AND ') . "prime.e_rank <= $Matches[0]";
        $ToRefine .= hidden('MaxERank', $Matches[0]);
    }

    #  Load and check digit limits

    if (isset($_REQUEST['MinDigits']) and preg_match('/(\d+)/', $_REQUEST['MinDigits'], $Matches)) {
        $Criteria .= "\nMinimum digits =$Matches[0].&nbsp; ";
        $Where .= (empty($Where) ? '' : ' AND ') . "prime.digits >= $Matches[0]";
        $ToRefine .= hidden('MinDigits', $Matches[0]);
    }
    if (isset($_REQUEST['MaxDigits']) and preg_match('/(\d+)/', $_REQUEST['MaxDigits'], $Matches)) {
        $Criteria .= "\nMaximum digits =$Matches[0].&nbsp; ";
        $Where .= (empty($Where) ? '' : ' AND ') . "prime.digits <= $Matches[0]";
        $ToRefine .= hidden('MaxDigits', $Matches[0]);
    }

    # Discoverer allows 'OR'=',', '?' and '*'; removes whitespace
    # The discoverer may have: \w \d \ + ? ^ and \d\w plus seach characters space ?$[].
    # Also allows [character classes] which might be used for things like p[1-9]

    $Discoverer = isset($_REQUEST['Discoverer']) ? $_REQUEST['Discoverer'] : '';
    # print "<li>Code $Discoverer";
    $Discoverer = preg_replace('/[^^,?*:\d\w[\]\\\\ + ]/', '', $Discoverer);
    $ToRefine .= hidden('Discoverer', $Discoverer);
    # print "<li>Code $Discoverer";
    if (!empty($Discoverer)) {
        $Discoverer =  preg_replace('/\s+OR\s+/i', '|', $Discoverer);   # change ' OR ' to |
        $Discoverer =  preg_replace('/\s*,\s*/', '|', $Discoverer); # change ',' to |
        $DiscovererWas = $Discoverer;
        $Discoverer =  preg_replace('/([?])/', '?', $Discoverer);   # quote ? (to \?)
        $Discoverer =  preg_replace('/([\*])/', '.*', $Discoverer);     # change * to .*
      # For speed, if the regexp contains no metacharacters, just use IN instead.
        if ($DiscovererWas != $Discoverer or preg_match('/\[/', $Discoverer)) {
            $Where .= (empty($Where) ? '' : ' AND ') . "prime.credit REGEXP BINARY '^($Discoverer)$'";
            $Criteria .= "\nDiscoverer = ^($Discoverer)\$.&nbsp; ";
        } else {
            $Discoverer = preg_replace('/\|/', '\',\'', $Discoverer); # return to 'a','b','b' style
            $Where .= (empty($Where) ? '' : ' AND ') . "prime.credit IN ('$Discoverer')";
            $Criteria .= "\nDiscoverer = '$Discoverer'.&nbsp; ";
        }
    }

    # The description  May have: ()^*/!+-#, and \d\w plus seach characters space ?$[].:%{}

    $Description = isset($_REQUEST['Description']) ? $_REQUEST['Description'] : '';
    $Description = preg_replace('/[^^.,:%\w\d\s*+\-!(){}[\]#?$\/]/', '', $Description);
    # The above dissallows <script> used for code injecton, but saddly, also word boundaries [[:<:]], [[:>:]]
    if (!empty($Description)) {
        $ToRefine .= hidden('Description', $Description);
    }
    # print "<li>Desc $Description";

    # Descriptions may begin with the word NOT

    $NotDescription = false;
    if (preg_match('/^NOT\s+(.*)$/', $Description, $Matches)) {
        $Description = $Matches[1];
        $NotDescription = true;
    }
    # print "<li>Desc $Description";

    # quote * + - ( ) and internal ^ (but not $)
    $Description = preg_replace('/\*/', '\*', $Description);
    $Description = preg_replace('/\+/', '\+', $Description);
    $Description = preg_replace('/\-/', '\-', $Description);
    $Description = preg_replace('/\(/', '\(', $Description);
    $Description = preg_replace('/\)/', '\)', $Description);
    $Description = preg_replace('/(.)\^/', '\1\^', $Description);
    # print "<li>Desc $Description";
    $Description = preg_replace('/\s+$/', '', $Description);  # Remove trailing spaces

    # change ' OR ' to | and % to .*
    if (preg_match('/\s+OR\s+/i', $Description)) {
        $Description =  '(' . preg_replace('/\s+OR\s+/i', '|', $Description) . ')';
    }
    $Description = preg_replace('/%/', '.*', $Description);

    if ($Description) {
        $Description = $db->quote($Description);
        $Where .= (empty($Where) ? '' : ' AND ') . "prime.description " .
        ($NotDescription ? 'NOT ' : '') . "REGEXP $Description";
        $Criteria .= $NotDescription ?
        "\nDescription must not match: $Description.&nbsp; " :
        "\nDescription must match: $Description.&nbsp; ";
    }

    #  Load comment.   Same as description

    $Comment = ((isset($_REQUEST['Comment']) and $_REQUEST['Comment']) ? $_REQUEST['Comment'] : '');
    # print "<li>Comm $Comment";
    $Comment = preg_replace('/[^^.,:*%\w\d\s*+\-!(){}[\]?$]/', '', $Comment);
    # print "<li>Comm $Comment";
    if (!empty($Comment)) {
        $ToRefine .= hidden('Comment', $Comment);
    }

    $NotComment = false;
    if (preg_match('/^NOT\s+(.*)$/', $Comment, $Matches)) {
        $Comment = $Matches[1];
        $NotComment = true;
    }

    # quote * + - ( ) and internal ^ (but not $)
    $Comment = preg_replace('/\*/', '\*', $Comment);
    $Comment = preg_replace('/\+/', '\+', $Comment);
    $Comment = preg_replace('/\-/', '\-', $Comment);
    $Comment = preg_replace('/\(/', '\(', $Comment);
    $Comment = preg_replace('/\)/', '\)', $Comment);
    $Comment = preg_replace('/(.)\^/', '\1\^', $Comment);
    $Comment = preg_replace('/\s+$/', '', $Comment);  # Remove trailing spaces

    # change ' OR ' to | and % to .*
    if (preg_match('/\s+OR\s+/i', $Comment)) {
        $Comment =  '(' . preg_replace('/\s+OR\s+/i', '|', $Comment) . ')';
    }
    $Comment = preg_replace('/%/', '.*', $Comment);

    if ($Comment) {
        $Comment = $db->quote($Comment);
        $Where .= (empty($Where) ? '' : ' AND ') . "prime.comment " .
        ($NotComment ? 'NOT ' : '') . "REGEXP $Comment";
        $Criteria .= $NotComment ?
        "\nComment does not include $Comment.&nbsp; " :
        "\nComment includes $Comment.&nbsp; ";
    }

    #  Check Age limits

    if (isset($_REQUEST['MinAge']) and preg_match('/(\d+)/', $_REQUEST['MinAge'], $Matches)) {
        $Criteria .= "\nMinimum age =$Matches[1] days.&nbsp; ";
        $Where .= (empty($Where) ? '' : ' AND ') . "TO_DAYS(prime.submitted) <= TO_DAYS(NOW())-$Matches[1]";
        $ToRefine .= hidden('MinAge', $Matches[1]);
    }
    if (isset($_REQUEST['MaxAge']) and preg_match('/(\d+)/', $_REQUEST['MaxAge'], $Matches)) {
        $Criteria .= "\nMaximum age =$Matches[1] days.&nbsp; ";
        $Where .= (empty($Where) ? '' : ' AND ') . "TO_DAYS(prime.submitted) >= TO_DAYS(NOW())-$Matches[1]";
        $ToRefine .= hidden('MaxAge', $Matches[1]);
    }

    # No variables set?  If so, exit
    if (empty($Where) and empty($_REQUEST['Number'])) {
        return('');
    }

    # Number of primes to get

    $Number = 20;
    if (isset($_REQUEST['Number']) and preg_match('/(\d+)/', $_REQUEST['Number'], $Matches)) {
        $Number = $Matches[1];
        $ToRefine .= hidden('Number', $Matches[1]);
    }
    if ($Number > $GLOBALS['Max_Primes']) {
        $Number = $GLOBALS['Max_Primes'];
    }
    $Criteria .= "\nNumber of primes to find $Number.&nbsp; ";


    # Prime Verification Status (prime.prime)

    $temp = '';
    if (isset($_REQUEST['PrimeStatus'])) {
        foreach ($_REQUEST['PrimeStatus'] as $key => $val) {
            $temp .= (empty($temp) ? '' : ',') . (string)(int)$val; # just ditch non-numerics
        }
    }
    $temp = preg_replace('/[^\d,]/', '', $temp);
    if (!empty($temp)) {
        if ($temp == '4,5,6') {
            $Where .= (empty($Where) ? '' : ' AND ') . "prime > 3";
        } else {
            $Where .= (empty($Where) ? '' : ' AND ') . "prime IN ($temp)";
        }
        $ToRefine .= hidden('PrimeStatus', $temp);
    }

    # Type ($OnList = 'yes', 'verified' or 'all')  !!! note $temp carries over from previous !!!
    $OnList = isset($_REQUEST['OnList']) ? $_REQUEST['OnList'] : '';
    if (!preg_match('/^(|yes|verified|all)$/', $OnList)) {
        $OnList = '';
    }

    if (!empty($temp)) {
        $Where .= (empty($Where) ? '' : ' AND ') . "list!='deleted'";
    } elseif (empty($OnList) or $OnList == 'yes') {
        $Where .= (empty($Where) ? '' : "\n\tAND ") . "onlist > 1 AND prime > 3 AND list='Top 5000'"; # All but 'no'
    } elseif ($OnList == 'verified') {
        $Where .= (empty($Where) ? '' : "\n\tAND ") . "prime > 3 AND list='Top 5000'";
    } else { # if ($OnList == 'all') {
        $Where .= (empty($Where) ? '' : "\n\tAND ") . "prime > 1 AND list!='deleted'";
    }
    if (preg_match('/(yes|verified|all)/', $OnList)) {
        $ToRefine .= hidden('OnList', $OnList);
    }

    # Check for user contributed comments

    if (
        isset($_REQUEST['UserComment']) and
        preg_match('/^(yes|NULL)$/', $_REQUEST['UserComment'], $Matches)
    ) { # Should be yes, NULL or either
        $temp = ($Matches[1] == 'yes' ? "='yes'" : ' is NULL');
        $Where .= (empty($Where) ? '' : ' AND ') . "comment.visible$temp";
        $Criteria .= "\nMust " . ($Matches[1] == 'yes' ? '' : 'not') . " have user comments.&nbsp; ";
        $ToRefine .= hidden('UserComment', $Matches[1]);

      #  Check Age limits on user comments

        if (preg_match('/(\d+)/', $_REQUEST['UserMinAge'], $Matches)) {
            $Criteria .= "\nUser comment minimum age =$Matches[1] days.&nbsp; ";
            $Where .= (empty($Where) ? '' : ' AND ') . "TO_DAYS(comment.modified) <= TO_DAYS(NOW())-$Matches[1]";
            $ToRefine .= hidden('UserMinAge', $Matches[1]);
        }
        if (preg_match('/(\d+)/', $_REQUEST['UserMaxAge'], $Matches)) {
            $Criteria .= "\nUser comment maximum age =$Matches[1] days.&nbsp; ";
            $Where .= (empty($Where) ? '' : ' AND ') . "TO_DAYS(comment.modified) >= TO_DAYS(NOW())-$Matches[1]";
            $ToRefine .= hidden('UserMaxAge', $Matches[1]);
        }

      #  Search comment text



        $UserCommentMatch = ($_REQUEST['UserCommentMatch'] ? $_REQUEST['UserCommentMatch'] : '');
        $UserCommentMatch = preg_replace('/[^\w\d\s*+\-!(){}[\]?$]/', '', $UserCommentMatch);
        if (!empty($UserCommentMatch)) {
            $ToRefine .= hidden('UserCommentMatch', $UserCommentMatch);
        }

        if ($NotUserCommentMatch = preg_match('/^NOT\s+(.*)$/', $UserCommentMatch, $Matches)) {
            $UserCommentMatch = $Matches[1];
        }

      # quote * + - ( ) and internal ^ (but not $)
        $UserCommentMatch = preg_replace('/\*/', '\*', $UserCommentMatch);
        $UserCommentMatch = preg_replace('/\+/', '\+', $UserCommentMatch);
        $UserCommentMatch = preg_replace('/\-/', '\-', $UserCommentMatch);
        $UserCommentMatch = preg_replace('/\(/', '\(', $UserCommentMatch);
        $UserCommentMatch = preg_replace('/\)/', '\)', $UserCommentMatch);
        $UserCommentMatch = preg_replace('/(.)\^/', '\1\^', $UserCommentMatch);

      # change ' OR ' to | and % to .*
        if (preg_match('/\s+OR\s+/i', $UserCommentMatch)) {
            $UserCommentMatch =  '(' . preg_replace('/\s+OR\s+/i', '|', $UserCommentMatch) . ')';
        }
        $UserCommentMatch = preg_replace('/%/', '.*', $UserCommentMatch);

        if ($UserCommentMatch) {
            $UserCommentMatch = $db->quote($UserCommentMatch);
            $Where .= (empty($Where) ? '' : ' AND ') . "comment.text " .
            ($NotUserCommentMatch ? 'NOT ' : '') . "REGEXP $UserCommentMatch";
            $Criteria .= $NotUserCommentMatch ?
            "\nUser comment does not include $UserCommentMatch.&nbsp; " :
            "\nUser comment includes $UserCommentMatch.&nbsp; ";
        }
    }

    # Form the query

    if (!empty($Where)) {
        $Where = "WHERE $Where";
    }

    # PROBLEM!!  'ORDER BY prime.rank' is the way to go here, but what about unranked?

    $OrderBy = (isset($_REQUEST['OrderBy']) ? $_REQUEST['OrderBy'] : 'Rank');
    if (!preg_match('/(Date|Rank)/', $OrderBy)) {
        $OrderBy = 'Rank';  // untaint
    }
    $ToRefine .= hidden('OrderBy', $OrderBy);
    $Order = ($OrderBy == 'Date' ? 'prime.submitted DESC' : 'prime.log10 DESC');
    return("/* search.php */ SELECT prime.rank, prime.id, prime, onlist, submitted, description, digits,
	credit, comment, COUNT(IF(comment.visible='yes','yes',NULL)) AS by_user
	FROM prime LEFT JOIN comment ON prime.id = comment.prime_id
	$Where
	GROUP BY prime.id ORDER BY $Order, prime.rank LIMIT $Number");
}

 # PrintTail just prints the info on the bottom of a successful search saying time used....

function PrintTail($style = 'HTML')
{
    global $ToRefine;

    $used = "This search used " . round(microtime(1) - $GLOBALS['time'], 4) . " second(s) to find $GLOBALS[count] primes
              matching the selection criteria: $GLOBALS[Criteria] " .
         (file_exists("/var/www/html/TESTSITE")
             ? "Query is <pre>    $GLOBALS[query]</pre>" : '');

    if ($style != 'NoBorder') {
        $used = '<div class="technote my-3 p-2">' . $used . "</div>\n";
    }
    return $used;
}
