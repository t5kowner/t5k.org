<?php $xx_string = $_REQUEST['xx_string']; ?>

<h3>Parser test</h3>

This is a parser based on <a href="http://www.php.net/manual/">PHP</a>'s <a
href="http://www.php.net/manual/en/ref.gmp.php>gmp"
integer</a> module. (Info on <a href=../admin/php_info.php>local PHP</a>
version.)<P>

<form method=post>
  <textarea rows=15 cols=80 name=xx_string><?php echo $xx_string; ?></textarea>
  <br><input type=submit name=evaluate value="Evaluate"> &nbsp;
  <input type=submit name=explain value="or Show legal operations">
</form>

<?php

# Register the form variables:

$xx_string  = (isset($_REQUEST['xx_string']) ? $_REQUEST['xx_string'] : '');
$evaluate  = (isset($_REQUEST['evaluate']) ? $_REQUEST['evaluate'] : '');
$explain  = (isset($_REQUEST['explain']) ? $_REQUEST['explain'] : '');
# $  = (isset($_REQUEST['']) ? $_REQUEST[''] : '');

include('../basic.inc');
include(basic_to_base() . "bin/parser/parser.inc");


if ($explain) {
    ShowAllOps();
} else {
    print "<P>" . parser_WrapNumber(parser_parse($xx_string));
}

######################################################

function ShowAllOps()
{
    global $parser_ops, $parser_fun;
    echo "This is an integer only parser (they may be positive, negative or zero).  
	<h4>First the operators:</h4>  
	Binary operators are either right or left associative.  Unary
	operators are either prefix or postfix.  The higher precedences are evaluated first.
	A few operators (like functions and parenthesis) have both pre and post-fix components.
	(called start and end below.)  GRE = match symbol using a general regular
	expressions (and the match is passed to the operator evaluator).

	<P><table align=center border=1 borderColor=\"$GLOBALS[drkcolor]\"><tr><td valign=middle>
        <table>
        <tr bgcolor=\"$GLOBALS[medcolor]\"><th>operation</th><th colspan=3>symbol</th>
        <th colspan=3>evaluation</th></tr>
        <tr bgcolor=\"$GLOBALS[medcolor]\"><th>name</th><th>start</th>
        <th>end</th><th><font size=-2>GRE</font></th>
	<th>precedence</th><th>associate</th><th>evaluated by</th></tr>";

    for ($precedence = 30; $precedence > 0; $precedence--) { # Okay, this is stupid!
        for (reset($parser_ops); $op = key($parser_ops); next($parser_ops)) {
            if ($parser_ops[$op]['precedence'] == $precedence) {
                echo lib_tr() .
                "<th align=right>$op</th>" .
                '<td align=center>' . $parser_ops[$op]['symbol'] . '</td>' .
                '<td>' . (!empty($parser_ops[$op]['match']) ? $parser_ops[$op]['match'] : '') . '</td>' .
                '<td>' . (!empty($parser_ops[$op]['symbol_match']) ? $parser_ops[$op]['symbol_match'] : '') . '</td>' .
                '<td align=center>' . $parser_ops[$op]['precedence'] . '</td>' .
                '<td>' . $parser_ops[$op]['associate'] . '</td>' .
                '<td>' . $parser_ops[$op]['evaluate'] . '</td>' .
                "</tr>\n";
            }
        }
    }
    echo "</table></table>\n";
    echo "<P>Warning: 3_4.0_4.5_4 = 333305555 because these operate on gmp_integers,
	so 0_4 = 0000 is reduced to 0.\n";

    echo "<h4>Built in functions:</h4>
	<P><table align=center border=1 borderColor=\"$GLOBALS[drkcolor]\">
	<tr><td valign=middle>
        <table cellpadding=5>
        <tr bgcolor=\"$GLOBALS[medcolor]\"><th>function</th><th>explanation</th></tr>";

    for (reset($parser_fun); $op = key($parser_fun); next($parser_fun)) {
        echo lib_tr() . "<td align=right width=20%>$op</td><td>" . $parser_fun[$op] . "</td></tr>\n";
    }
    echo "</table></table>\n";
}

?>
