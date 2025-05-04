<?php

# Register the form variables:
$searchstring = isset($_REQUEST['searchstring']) ? htmlentities($_REQUEST['searchstring']) : '';
# This is altered in a couple ways below and escaped to avoid mysql injection before use.

# Begin our work

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

$t_submenu = 'Search';

$display_string = preg_replace('/"/', '&quot;', $searchstring);
$display_string =  htmlentities($searchstring);

$t_text = "<p>This page can be used to search the text and titles of the entries
   in the Top 20 pages. Enter the list of words you are looking for. Words that
   show up in the database too often (like 'primes') are not indexed. Currently
   the words are just or'd and match full words only. This may change eventually,
   but for now you may use the Prime Page's <a href=\"/search/\">search page</a>
   for links to more elaborate searches.";

# Note that the example below is not to be translated, the entries are in English only.
$t_text .= '
<blockquote>
  <form method="post" action="search.php">
    <input type="text" name="searchstring" size="20" value=\'' . $display_string . '\'>&nbsp;&nbsp;
    <input type="submit" value="Search"><br>
    <b>Example:</b> <code class="text-danger">palindrome, palindromic</code>
  </form>
</blockquote>';

# Build the variable part of the query
$where = '';
if (!empty($searchstring)) {
    $where = "visible='yes' AND MATCH (name,description,weight_text) AGAINST (? IN BOOLEAN MODE)";
    $t_text .= "Results listed in order of the relevance of the match:";
}

# Do the query
if ($where) {
    $query = "SELECT id, name FROM archivable WHERE $where";
    try {
        $sth = $db->prepare($query);
        $sth->bindParam(1, $searchstring);
        $sth->execute();
    } catch (PDOException $e) {
        lib_mysql_die($query, 'Invalid query in this page view');
    }

    $results = '';
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $results .= "<LI><A HREF=\"page.php?id=" . $row['id'] . "\">" . $row['name'] . "</a></LI>\n";
    }

    if (empty($results)) {
        $results = 'Sorry, no matches found.';
    }

    $t_text .= "<UL>$results</UL>";
}

if (!empty($searchstring)) {
    $t_text .= "<p>Searched for: '$searchstring'.</p>";
}

# This templates uses $t_text for the text, $t_title...
$t_title = 'Search the Top Twenty Lists';
$t_subtitle = "Another of the Prime Pages' resources";
include("template.php");
