<?php

# Routine to search prime database for primes of a specific form
# (e.g., (\d+)*$base^(\d+)+1).  The form to match belongs in the variable
# names 'match', limits on $n in the expression belong in 'max_n'
# and 'min_n';

$t_submenu = 'proth search';

$time = time(); # Used in ShowTail() to give elapsed time of search

if (empty($_REQUEST['base'])) {  # If no form input,
    $t_title = 'Search database';
    $t_text = ShowForm();
} else {
  # We got input!  Process it.
    UnTaint();    # Untaint %In{var} into $var
    $found = 0;   # Matches found

    if ($query) {
        $t_title = "Database Search Output";
        include_once("bin/basic.inc");
        $db = basic_db_connect(); # Connects or dies

        include_once('bin/ShowPrimes.inc');
        $t_text = '<pre>' . ShowText('head');

        $sth = lib_mysql_query(
            $query,
            $db,
            'Invalid query in this page view'
        );

      # Get the rows
        $count = 0;
        $options['wrap'] = 78;
        while ($row = $sth->fetch()) {
            preg_match('/(\d+)\*(\d+)\^(\d+)[+-]1/', $row['description'], $temp);
            $k = $temp[1];
            $n = $temp[3];
            if (!empty($extra_tests)) {
              # print("\$ok=($extra_tests);");
                eval("\$ok=($extra_tests);");
              # print "[$k,$ok]";
                if (empty($ok)) {
                    continue;
                }
            }
            $t_text .= ShowText($row, $options);
            if (++$count >= $number) {
                break;
            }
        }
        $t_text .= ShowText('tail') . '</pre>';      # Prints ending info, my name...
        $t_text .= ShowTail();
    }
}
include('template.php');
exit;



function ShowForm()
{
    return <<< HERE
<p>At the <A href="/index.html">PrimePages</A> we keep a database of the
5,000 largest known primes (plus the <A href="/top20/index.php">top
twenty</A> of certain selected types). A commonly requested search is to find
all of the primes of the form <I>k<SUP>.</SUP>b<SUP>n</SUP></I>+/-1 with
restrictions on <I>k</I> and <I>n</I>. This page is designed to make that easy.</p>
<CENTER class="mt-4">
 <FORM method=post>
  <TABLE cellPadding=3 border=1>
    <CAPTION>Search Database of the Largest Known Primes<BR>for primes of the form:
	<I>k<SUP>.</SUP>b<SUP>n</SUP></I>+/-1</CAPTION>
   <TBODY>
    <TR>
     <TD>Base <I>b</I></TD>
     <TD align=right>base <INPUT type=numeric size=10 value=2 name=base></TD></TR>
    <TR>
     <TD>Limit on multiplier <I>k</I><BR>(leave blank for no limit)</TD>
     <TD align=right>minimum <INPUT type=numeric size=10 name=min_k><BR>maximum
	<INPUT type=numeric size=10 name=max_k></TD></TR>
    <TR>
     <TD>Limit on exponent <I>n</I><BR>(leave blank for no limit)</TD>
     <TD align=right>minimum <INPUT type=numeric size=10 name=min_n><BR>maximum
	<INPUT type=numeric size=10 name=max_n></TD></TR>
    <TR>
     <TD>Which sign(s)?</TD>
     <TD align=right>plus <INPUT type=checkbox CHECKED name=plus> &nbsp; &nbsp; minus
	<INPUT type=checkbox name=minus></TD></TR>
    <TR>
     <TD>Find at most</TD>
     <TD align=right>number <INPUT type=numeric size=10 value=20 name=number> </TD>
    <TR>
     <TD align=middle><INPUT type=submit value="Start Search" name=search  class="btn btn-primary py-2"></TD>
     <TD align=middle>(<A href="/search/index.html">other
	searches</A>)</TD></TR></TBODY></TABLE></FORM></CENTER>
HERE;
}


function UnTaint()
{
  # Untaints data
    global $criteria, $query, $extra_tests, $number;

  # ?? $match = (delete($In[$key]) =~ /(.+)/o ? $1 : $Match);

  # Number of primes to find:
    $number = (preg_match('/(\d+)/', $_REQUEST['number'], $temp) ? $temp[1] : 20);
    $criteria = "Find $number primes ";

  # base for these primes
    $base = (preg_match('/(\d+)/', $_REQUEST['base'], $temp) ? $temp[1] : 2);
    $criteria .= "of the form: k*$base^n";

  # sign(s) to seek
    if (!empty($_REQUEST['plus'])) {
        $sign = (empty($_REQUEST['minus']) ? '\\\\+' : '[\\\\+\\\\-]');
        $criteria .= (empty($_REQUEST['minus']) ? '+' : '+/-');
    } else {
        $sign = (empty($_REQUEST['minus']) ? '\\\\+' : '\\\\-');
        $criteria .= (empty($_REQUEST['minus']) ? '+' : '-');
    }
    $criteria .= "1 ";
    $query = "SELECT * FROM prime WHERE " .
    "description REGEXP '^[[:digit:]]+\\\\*$base\\\\^[[:digit:]]+${sign}1'
	AND onlist = 'yes' ORDER BY prime.rank";

  # min or max limits on the variables?
    $extra_criteria = '';
    $extra_tests = '';
    if (isset($_REQUEST['min_k']) and preg_match('/(\d+)/', $_REQUEST['min_k'], $temp)) {
        $extra_criteria .= "k >= $_REQUEST[min_k]";
        $extra_tests .= "\$k >= $_REQUEST[min_k]";
    }
    if (isset($_REQUEST['max_k']) and preg_match('/(\d+)/', $_REQUEST['max_k'], $temp)) {
        $extra_criteria .= (empty($extra_criteria) ? '' : ', ') . "k <= $_REQUEST[max_k]";
        $extra_tests    .= (empty($extra_tests) ? '' : ' && ') . "\$k <= $_REQUEST[max_k]";
    }
    if (isset($_REQUEST['min_n']) and preg_match('/(\d+)/', $_REQUEST['min_n'], $temp)) {
        $extra_criteria .= (empty($extra_criteria) ? '' : ', ') . "n >= $_REQUEST[min_n]";
        $extra_tests    .= (empty($extra_tests) ? '' : ' && ') . "\$n >= $_REQUEST[min_n]";
    }
    if (isset($_REQUEST['max_n']) and preg_match('/(\d+)/', $_REQUEST['max_n'], $temp)) {
        $extra_criteria .= (empty($extra_criteria) ? '' : ', ') . "n <= $_REQUEST[max_n]";
        $extra_tests    .= (empty($extra_tests) ? '' : ' && ') . "\$n <= $_REQUEST[max_n]";
    }
    if (!empty($extra_criteria)) {
        $criteria .= "where $extra_criteria";
    }
}


function ShowTail()
{
    $out = "<table width=540>
          <tr bgcolor=\"#A9A9A9\">
            <td><FONT SIZE=-1>
              Used " . (time() - $GLOBALS['time']) . " second(s) to find $GLOBALS[count] primes
              matching the selection criteria: $GLOBALS[criteria].  Query is $GLOBALS[query].
            </FONT></td>
          </tr>
        </table>\n";
    return $out;
}
