<?php

// These routines expand and modify links, ... for the glossary list
// Assumes the database is already opened.  Called from meta_row entires in database!

if (empty($GLOBALS['expand_included'])) {
    $GLOBALS['expand_included'] = 1;
} else {
    return ' ';  # Already included (hard to tell if included in calls via edit.inc in meta_row)
}

// expand_create_links($string,$sort)
//  Parse the string (comma delimited list of GRE's)
//  creating links back to $sort; all old links removed
//  and a nice form of each for the index is created.
//  Returns '' if succeeds, else an error message.
//
// expand_remove_links_to($sort)
//  Removes all the old links to this 'sort' entry (called by the above)
//
// expand_see_also($see_also)
//  Does what it says!  Expands the comma delimited list of sort codes to a
//  clickable HTML string (add slashes if storing in MySQL!)
//
// expand_see_also($see_also)
//  Does what it says!  Expands the comma deliminted list of sort codes to a
//  clickable HTML string

function expand_see_also($see_also)
{
    $out = '';
    $delim = '';
    $error = '';
    $db = basic_db_connect();

  # Split on commas and process
    $list = preg_split('/\s*,\s*/', $see_also);
    for ($i = 0; $i < count($list); $i++) {
        $sort = $list[$i];
      # Find this in the database to get its name
        $query = "SELECT name, id FROM terms WHERE sort='$sort'";
        $sth = lib_mysql_query($query, $db, "Query failed in expand_see_also (expand.inc 40)", $query);
        if ($row = $sth->fetch()) {
            $out .= $delim . "<a href=\"/glossary/page.php/$sort.html\" class=glossary title=glossary>" . $row['name'] . '</a>';
            $delim = ', ';
        } else {
            $error .= "  <br>No entry matching '$sort'";
        }
    }

    echo "<font color=red><b>$error</b></font>";
    echo "From \"$see_also\" created \"$out\"";

    return($out);
}


// expand_remove_links_to($sort)
//  Removes all the old links to this 'sort' entry (called by the above)

function expand_remove_links_to($sort)
{
    $db = basic_db_admin_connect();
  # All of the old links to this particular 'sort' should be removed

    try {
        $sth = $db->prepare("DELETE FROM links WHERE sort=:sort");
        $sth->bindValue(':sort', $sort);
        $sth->execute();
        return $sth->rowCount();
    } catch (PDOException $ex) {
        lib_warn('expand_remove_links_to failed to delete links (expand.inc 69). ' . $ex->getMessage());
        return(0);
    }
}

// expand_create_links($string,$sort,$class)
// Parse the string (comma delimited list of GRE's) creating links back to $sort;
// all old links removed and a nice form of each for the index is created.  These
// changes are all entered into the links table.  Returns '' if succeeds, else an
// error message.  Note: $class is glossary, curio or both; copied from term's value.

function expand_create_links($match, $sort, $class)
{
  # Remove the old links (we are replacing them!)
    expand_remove_links_to($sort);

    echo "<h4>$match</h4>\n<ul>";

  # Prepare sort for MySQL (it might have spaces...)
    $sort = '\'' . AddSlashes($sort) . '\'';

  # Remove extra (multiple) whitespace, linefeeds, tabs...
    $match = preg_replace('/\s+/', ' ', $match);

  # Protect quoted commas (are there ever any?)
    $match = preg_replace('/\\\,/', "\0", $match);

  # Now split on commas and process
    $list = preg_split('/\s*,\s*/', $match);
    for ($i = 0; $i < count($list); $i++) {
      # put the quoted commas back
        $tag = preg_replace('/\0/', ',', $list[$i]);
        $entry = $tag;

        echo "<dt>From \"$tag\" : ";

      # Choices '(a|b)' and '(a|b|c)' become 'a'
        $entry = preg_replace('/\(([^|)]+)\|[^|\)]+\)/', '$1', $entry);
        $entry = preg_replace('/\(([^|)]+)\|[^|)]+\|[^|)]+\)/', '$1', $entry);

      # Functions have '(?=\()'  (Why do I need \\\\\ not \\\ ?)
        $entry = preg_replace('/\(\?=\\\\\(\)/', '( )', $entry);

      # Parenthesis may be quoted '\(' or '\(?' (leave '(' behind in either case)
        $entry = preg_replace('/\\\\([()])\??/', '$1', $entry);

      # The selection boxes '[Gg]' should be replaced by the first choice
        $entry = preg_replace('/\[(.)[^\]]\]/', '$1', $entry);

      # Remove optional last letters like in 'primes?'
#    $entry = preg_replace('/(\w)\?/','',$entry);
        $entry = preg_replace('/.\?/', '', $entry);

      # Those beginning with '++' (which must be removed) are case sensitive
        if (preg_match('/^\+\+(.*)/', $tag, $matches)) {
            $sense = 'yes';
            $tag = $matches[1];
            $entry = preg_replace('/^\+\+(.*)$/', '$1', $entry);
        } else {
            $sense = 'no';
        }

        echo "<dd>Entry \"$entry\" <dd>tag \"$tag\" <dd>case sensitive \"$sense\"";

        $tag = '\'' . AddSlashes($tag) . '\'';
        $entry = '\'' . AddSlashes($entry) . '\'';

        $repeat = 'no';     # Currently no repeats
        $weight = strlen($tag); # The longer ones will be matched first

      # Form the $query, do the work.
        $query = "INSERT links (sort, tag, entry, sense, repeat_, weight, class)
        VALUES ($sort, $tag, $entry, '$sense', '$repeat', $weight, '$class')";

        echo "<dd>weight $weight <dd>sort \"$sort\"";
        echo "<pre>$query</pre>";

        $sth = lib_mysql_query($query, basic_db_admin_connect(), "expand_create_links failed");
        if ($sth->rowCount() == 0) {
            lib_mysql_die("expand_create_links  failed? query succeded, but no
        rows were effected!", $query);
        }
    }
    return '';
}
