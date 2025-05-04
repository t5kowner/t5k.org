<?php

require_once 'bin/basic.inc';
$db = basic_db_connect();

# This page displays entries depending on the first of the following which is
# defined:
#
#   $id     Display that one item.
#   $sort   Again display that one item (for compatibility with old system)

# Register the form variables

$id   =  empty($_REQUEST['id'])   ? '' : $_REQUEST['id'];
$id   = preg_replace('/[^\d].*$/', '', $id);
$sort =  empty($_REQUEST['sort']) ? '' : $_REQUEST['sort'];
$sort = preg_replace('/[^\w]/', '', $sort);

$t_text  = '';
$t_title = 'The Top Twenty';
$t_adjust_path = "/top20/";

# An icon linked to the top of the page:
$up = '<a class=up href="#top"><img src="/top20/includes/gifs/up3.gif"' .
        '  width=14 height=14 ALT="(up)"></a>';

# Build the variable part of the query

$done = 0;
# If $id or $sort is defined--that is the page we want!
$where = 'visible != "no" AND purpose LIKE "%top20%" AND ';
if (isset($id) and is_numeric($id)) {  # Get single term to display
    $where .= "id=$id";
} elseif (!empty($sort)) {
    $where .= "sort LIKE BINARY " . $db->quote($sort);
} else {
    $t_text = "No query specified.";
    $done = 1;
}
if (!$done) {
    $query = "SELECT * FROM archivable WHERE $where";
    if ($sth = lib_mysql_query($query, $db, 'page.php: Invalid query')) {
        if (!($row = $sth->fetch(PDO::FETCH_ASSOC))) {
            $t_text = "<span class=error>\n" .
            'Error: There is no such entry.' . "</span><br><br>\n";
            $done = 1;
        }
    } else {
        lib_mysql_die('Invalid query in this page view (1)', $query);
        $done = 1;
    }
}
if ($done) {
    include("template.php");
    exit;
}

# We have the term--let's display it!

$t_add_tab = "id=$id";

$The5000th = lib_get_column('prime.rank = 5000', 'prime', 'digits', $db);

# Let's print something on the top of the page.  Is this form archivable?

$temp = "<p>This page is about one of those forms.</p>";
if ($row['type'] == 'tolerated') {
    $temp = "<p>This page though is not about an archivable form, but rather about a form
    which is <b>tolerated</b> on the current list, and the primes with this comment
    only appear on the list if the prime there for some other reason.";
}

$t_text .= "<p id=top>The Prime Pages keeps a list of the 5000 largest
   known primes, plus a few each of certain selected archivable forms and
   classes. These forms are defined in this collection's home page.</p>" . $temp;

# Prepare the description

$description = $row['description2'];

if (empty($description)) {
    $t_text .= '<span class=error>The entry for this page is not yet written. Do you want to write it?</span><br><br>';
} else {
    $t_text .= "<h3 id=notes class=pt-2>$up Definitions and Notes</h3>\n$description\n";
}

# Display the records:

$t_text .= "<h3 id=records class=pt-2>$up Record Primes of this Type</h3>\n";

require_once "bin/ShowPrimes.inc";
$options['link rank'] = 'yes';
$options['description'] = 'MakePretty';
$options['comm_func'] = 'MakePretty';
$options['renumber'] = 1;
$options['wrapper'] = 'table';
$count = 0;

# This next block of code nearly duplicates that for the weighted records below
# combine as a subroutine?

# Which primes match?  The column 'match_' decided; if empty then we want
# these primes with the same category id as this one.
if (empty($row['match_'])) {
    $row['match_'] = "category_id = $row[id]";
}

# Get all the subcategories we want to show and adjust number of primes accordingly
$max = 5;
if (empty($row['visible_subcat'])) {
    $max = 20;
}

$subcats = lib_mysql_query("SELECT DISTINCT subcategory FROM archival_tag 
    WHERE category_id = $row[id] AND subcategory REGEXP '$row[visible_subcat]'", $db);

$subcat = $subcats->fetch(PDO::FETCH_ASSOC);

# awkward do-while because the largest primes (and archival tags without a subcategory I think?) confuse things
do {
    if ($subcat) {
        $subcategory = $subcat["subcategory"];
    }
    $where = empty($row['visible_subcat']) ? '' : "subcategory = '$subcategory' AND ";
    # The table of largest known primes does not need a join
    if (preg_match('/^NO_JOIN(.*)$/', $row['match_'], $temp)) {
        $query = "SELECT prime.* FROM prime
	    WHERE prime.onlist > 1 and $temp[1]
	    ORDER BY prime.rank, prime.digit_rank LIMIT $max";
    } else {
        $query = "SELECT prime.* FROM prime,archival_tag
   	    WHERE prime.id=prime_id AND $where prime.onlist > 1 AND $row[match_] " .
            ((isset($row['type']) and $row['type']) == 'tolerated' ? '' : "AND archival_tag.onlist='yes'") .
            " GROUP BY prime.id ORDER BY prime.rank, prime.digit_rank LIMIT $max";
    }

    $sth = lib_mysql_query($query, $db);
    if (empty($sth)) {  #Query failed!
        http_response_code(404);
        $t_text = "<P><span class=error>Database query returned no
	    results.  One possibility is that you mistyped the URL.  You might try
	    using the links in the menu to browse the index or search this site.</span><br><br>";
        include("template.php");
        exit;
    }

    $t_text .= "\n<blockquote>\n";
    if (!empty($row["subcat_header"])) {
        $t_text .= '<span class="font-weight-bold">' . eval($row["subcat_header"]) . '</span>';
    }
    $t_text .= ShowHTML('head', $options);

    while ($prime = $sth->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['top20_modify']) and !empty($prime['comment'])) {
            # This archival entry wants to modify the comments (e.g., AP's)
            eval("$row[top20_modify]");
        }
        $t_text .= ShowHTML($prime, $options);
        $count++;
    }
    $t_text .= ShowHTML('tail', $options);
    $t_text .= "\n</blockquote>\n";
    if (empty($row['subcat_header'])) {
        break;
    }
} while ($subcat = $subcats->fetch(PDO::FETCH_ASSOC));

# Display the weighted records:

if (!empty($row['weight_text'])) {
    $t_text .= "<h3 id=weighted class=pt-2>$up\nWeighted Record Primes of this Type</h3>\n" .
      $row['weight_text'] . "\n<P>";

  # Which primes match?  The column 'match_' decided; if empty then we want
  # these primes with the same category id as this one.
    if (empty($row['match_'])) {
        $row['match_'] = "category_id = $row[id]";
    }

    $query = "SELECT prime.*, archival_tag.weight FROM prime,archival_tag
	WHERE prime.id=prime_id and prime.onlist > 1 and $row[match_]
	ORDER BY archival_tag.weight DESC, prime.log10 DESC LIMIT 20";
    $sth = lib_mysql_query($query, $db);
    $t_text .= "\n<blockquote>\n";
    $t_text .= ShowHTML('head', $options);
    while ($prime = $sth->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['top20_modify']) and !empty($prime['comment'])) {
          # This archival entry wants to modify the comments (e.g., AP's)
            eval("$row[top20_modify]");
        }
        $t_text .= ShowHTML($prime, $options);
        $count++;
    }
    $t_text .= ShowHTML('tail', $options);
    $t_text .= "\n</blockquote>\n";
}

# Add any 'related' links

$related = $row['related'];
if (!empty($related)) {
    require_once "bin/SplitRef.inc";
    $related = SplitRef_References($related, '<ul><LI>', '<LI>', '</UL>', '');
}
if (!empty($related)) {
    $t_text .= "<h3 id=related class=pt-2>$up\nRelated Pages</h3>\n$related\n";
}


# Add any references.  refs_tr should exist, but might not if entry
# just created  or updated as this field is filled by a separate routine

if (!empty($row['refs_tr'])) {
    $refs = $row['refs_tr'];
} else {
    $refs = $row['refs'];
    if (!empty($refs)) {
        require_once "bin/SplitRef.inc";
        $refs = SplitRef_References(
            $refs,
            '<ul><LI>',
            '<LI>',
            '</UL>',
            '',
            'Reference_Anchor'
        );
    }
}
if (!empty($refs)) {
    $t_text .= "<h3 id=reference class=pt-2>$up\nReferences</h3>\n$refs\n";
}

# The templates uses $t_text for the text, $t_title...
$t_title = $row['name'];
$t_submenu = $t_title;

$t_meta['description']  = "...";
$t_meta['add_keywords'] = $row['name'];

include("template.php");
