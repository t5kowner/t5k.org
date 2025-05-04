<?php

# The search page. Needs work!
# Currently will not match surname, but MySQL squawks when I add it to the full text index.

$t_submenu =  "Search";

# Begin our work

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies
$t_allow_cache = 'yes';

# Register the form variables:

$searchstring = isset($_POST['searchstring']) ? $_POST['searchstring'] : '';
$searchstring = preg_replace("/<\/?script/i", '', $searchstring);
$searchstring = preg_replace('/[^\w+\-<>()~*"]/', '', $searchstring);

### if (get_magic_quotes_gpc()==1) $searchstring = stripslashes($searchstring);
$safe_string = preg_replace('/"/', '&quot;', $searchstring);
$safe_string =  htmlentities($searchstring);  # added 8/2018
# print "<li>[".$searchstring."]";
# print "<li>[".$safe_string."]";


# xgettext:no-php-format
$t_text = '<p>Enter the list of words you are looking for--separated by commas. Words that show up too often
	(stop words and those in more than 50% of the documents) are not indexed.  There are
	<a href="search_note.php">advanced options</a> should you want to use them. You may also use the
	PrimePage\'s <a href="/search/">search page</a> for more elaborate searches.</p>';

$t_text .= "<blockquote>
  <form method=\"post\" action=\"$_SERVER[PHP_SELF]\">
    <input type=text name=searchstring maxLength=256 size=55 value=\"$safe_string\">&nbsp;&nbsp;
    <input type=\"submit\" class=\"btn btn-primary p-2\" value=\"Search\"><br>
    <b>Example:</b> <code class=\"blue-text\">palindrome, palindromic</code>
  </form>
</blockquote>";

# Build the variable part of the query

$where = '';
if (!empty($searchstring)) {
  # MySQL adds boolean search capacities in 4.0  http://www.mysql.com/doc/en/Fulltext_Search.html
  # using these by passes the stop work limit automatically!
    $InBoolean = (preg_match('/[+-<>()~*"]/', $searchstring) ? ' IN BOOLEAN MODE' : '');
    $where = "PrimesTotal>=0 AND (MATCH (description,name,surname) AGAINST (" .
    $db->quote($searchstring) . "$InBoolean) OR username=" . $db->quote($searchstring) . ')';

    $t_text .= "Results listed in order of the relevance of the match:";
}

# Do the query
if ($where) {
    $query = "SELECT id as sort, name FROM person WHERE $where";
    $sth = lib_mysql_query($query, $db, 'Invalid query in this page view');

    $results = '';
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $results .= "<LI><A HREF=\"page.php?id=$row[sort]\" dir=ltr>$row[name]</a></LI>\n";
    }

    if (empty($results)) {
        $results = 'Sorry, no matches found.';
    }

    $t_text .= "<UL>$results</UL>";
}

if (!empty($searchstring)) {
    $t_text .= '<p>' . sprintf("Searched for: '%s'.", $searchstring);
} else {
    $t_text .= "\n" . '<p class="mt-4">' . "Prover-Account Database Searches:</p>
<blockquote>
  <form method=post action='index.php'>
    <input type=submit class=\"btn btn-primary p-2\" value=\"All Programs\" name=type> &nbsp; &nbsp; &nbsp;
    <input type=submit class=\"btn btn-primary p-2\" value=\"All Projects\" name=type>
  </form>

  <form method=post action='index.php'>
    <input type=submit class=\"btn btn-primary p-2\" value=\"Those Modified\"> " . sprintf(
        "in the last %s days",
        '<input type=text size=2 name=changed value=7>'
    ) . "
  </form>
</blockquote><p class=\"mt-4\">Related Searches:</p>
<blockquote class=\"mb-3\">
  <form method=post action='code.php'>
    <input type=submit class=\"btn btn-primary p-2\" value=\"Search for proof-code:\">
    <INPUT type=text name=code size=16>\n\t (code name such as g123, p46, L13)
  </form>
</blockquote>
";
}

# This templates uses $t_text for the text, $t_title...
$t_title = 'Prover-Account Database Searches';
include("template.php");
