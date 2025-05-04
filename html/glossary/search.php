<?php

# The search page. Needs work! First register the form variables:

$searchstring = isset($_REQUEST['searchstring']) ? htmlentities($_REQUEST['searchstring']) : '';
# This is altered in a couple ways below and escaped to avoid MySQL injection before use.

include("bin/basic.inc");
$db = basic_db_connect();
# Connects or dies

$searchstring = preg_replace('/[^\w+\-<> ()~*"]/', '', $searchstring);
$safe_string =  htmlentities($searchstring);
$t_text = '<p>Enter the list of words you are looking for--separated by commas.&nbsp;  Words that show
up too often (stop words and those in more than 50% of the documents) are not indexed.&nbsp;  There
are <a href=search_note.php>advanced options</a> should you want to use them.&nbsp; You may also used
the Prime Page\'s <a href="/search/">search page</a> for more elaborate searches.</p>

<blockquote class="mx-5 my-3">
  <form method="post" action="search.php">
    <input type=text name=searchstring maxLength=256 size=55 value="' . $safe_string . '">&nbsp;&nbsp;
    <input type="submit" value="Search"> Enter Keywords<br>
    <b>Example:</b> <code>palindrome, palindromic</code>
  </form>
</blockquote>';
# Build the variable part of the query

$where = '';
if (!empty($searchstring)) {
# MySQL adds boolean search capacities in 4.0 http://www.MySQL.com/doc/en/Fulltext_Search.html
  # using these by passes the stop work limit automatically!
    $InBoolean = (preg_match('/[+-<>()~*"]/', $searchstring) ? ' IN BOOLEAN MODE' : '');
    $where = "visible='yes' AND MATCH (text,name) AGAINST (" . $db->quote($searchstring) . "$InBoolean)";
    $t_text .= "<p>Results listed in order of the relevance of the match:</p>";
}

# Do the query
$results = '';
if ($where) {
    $query = "SELECT sort, name FROM terms WHERE $where AND (class LIKE 'glossary' OR class LIKE 'both')";
    $sth = lib_mysql_query($query, $db, 'search.php (46): Invalid query in this page view');
    while ($row = $sth->fetch()) {
        $results .= "<LI><A HREF=\"page.php/" . $row['sort'] . ".html\">" . $row['name'] . "</a></LI>\n";
    }
    if (empty($results)) {
        $results = 'Sorry, no matches found.';
    }
    $t_text .= "<UL>$results</UL>";
}

if (!empty($searchstring)) {
    $t_text .= "<p>Searched for: '" . $searchstring . "'";
// using query <pre>$query</pre>";
}

# This templates uses $t_text for the text, $t_title...
$t_title = 'Search the Prime Glossary';
include("template.php");
