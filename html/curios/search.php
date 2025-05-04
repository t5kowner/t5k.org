<?php

# The search page. Needs work!

include("bin/basic.inc");
$db = basic_db_connect(); # Connects or dies

# register form variable:

$searchstring  = (isset($_REQUEST['searchstring']) ? $_REQUEST['searchstring'] : '');
$searchstring = stripslashes($searchstring);
$searchstring = preg_replace('/[^\w+\-<> ()~*"]/', '', $searchstring);
$safe_string =  htmlentities($searchstring);

# Default text (a search form)

$t_text = '<p>Enter the list of words you are looking for--separated by commas.&nbsp;
  Words that show up too often (stop words and those in more than 50% of the documents)
  are not indexed.&nbsp;  There are <a href=search_note.php>advanced options</a> should
  you want to use them.&nbsp; You may also used the Prime Page\'s <a
  href="/search/">search page</a> for more elaborate searches.</p>

  <form method="post" action="' . $_SERVER['PHP_SELF'] . '" class="m-3">
    <input type=text name=searchstring maxLength=256 size=55 value="' . $safe_string . '">&nbsp;&nbsp;
    <input type="submit" value="Search" class="' . $mdbltcolor . '"> Enter Keywords<br>
    <b>Example:</b> <span class="deep-orange-text">palindrome, palindromic</span>
  </form>

<p>You may also just enter the number you want curios about:</p>

  <form method=post action="page.php" class="m-3">
     <input type=text size=30 name=short value="">
    <input type="submit" value="Search" class="' . $mdbltcolor . '"> Enter Number<br>
    <b>Example:</b> <span class="deep-orange-text">23</span>
  </form>

<p>Finally, we have a <a href="ByNumber.php">list of contributors\' names</a>--many
linked to the curios they have found.</p> ';

# Build the variable part of the query

$where = '';
if (!empty($searchstring)) {
  # using these by passes the stop word limit automatically!
    $InBoolean = (preg_match('/[+-<>()~*"]/', $searchstring) ? ' IN BOOLEAN MODE' : '');
    $where = "visible='yes' AND MATCH (text) AGAINST (:searchstring$InBoolean)";
    $t_text .= "<p>Results listed in order of the relevance of the match:</p>";
}

# Do the query
if ($where) {
    $query = "SELECT numbers.short, curios.id
	FROM curios, numbers
	WHERE $where AND numbers.id = curios.number_id";
    $sth = $db->prepare($query);
    $sth->bindValue(':searchstring', $searchstring);
    $sth->execute();

    $results = '';
    while ($row = $sth->fetch()) {
        $results .= "<LI><A HREF=\"page.php?curio_id=" . $row['id'] .
        "\">" . $row['short'] . "</a></LI>\n";
    }

    if (empty($results)) {
        $results = 'Sorry, no matches found.';
    }

    $t_text .= "<UL>$results</UL>";
}

if (!empty($searchstring)) {
    $t_text .= "<p>Searched for: '" . $safe_string . "'";
}

# This templates uses $t_text for the text, $t_title...
$t_title = 'Search our Curios!';

include("template.php");
