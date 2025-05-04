<?php

include("bin/basic.inc");
$dbh = basic_db_connect(); # Connects or dies

# This page displays entries depending on the first of the following which is
# defined:
#
#   $_SERVER[PATH_INFO] $_SERVER[PHP_SELF]/23.html gives $sort=23.  (Once, because of search engines
#       this was/is the main page access method!  Now them seem to index page.php?id=12...
#       just fine.
#   $sort   The sort code of the entry
#   $term_id    Display that one term.
#   $edit   Allow editor function menu
#
# People were causing ugly loops using wget on error messages, so now the
# paths when there are errors are hard coded (not relative).  Sad....

# Register the form variables:

$term_id = (!empty($_REQUEST['term_id']) and is_numeric($_REQUEST['term_id']))
                    ? $_REQUEST['term_id'] : '';
$edit = !empty($_REQUEST['edit'])   ? $_REQUEST['edit'] : '';
# Two ways to pass the 'sort' information...

$sort = (!empty($_REQUEST['sort']) and preg_match('/(\w+)/', $_REQUEST['sort'], $temp))
  ? $temp[1] : '';

if (!empty($_SERVER['PATH_INFO']) and $_SERVER['PATH_INFO'] != "" and empty($sort)) {
    if (preg_match("/^\/(.*)\.html?$/", $_SERVER['PATH_INFO'], $matches)) {
        $sort = $matches[1];  # matches[0] is whole match
      #print "<li>$sort";
      # Need this next one for includes to work right
        preg_match("/(.*)\/$sort\.html$/", $_SERVER['PHP_SELF'], $matches);
        $t_adjust_path = '../'; # since we are not in home directory
    }
} else {
    $t_adjust_path = '';
}

if (preg_match("/xpage\//", $_SERVER['REQUEST_URI'])) {
    $t_adjust_path .= '../';
}

##print("<li>$t_adjust_path<li>$_SERVER[PHP_SELF]<li>$_SERVER[REQUEST_URI]");
#### $t_adjust_path = "/glossary/";


# Begin our work

$t_text = '';   # Will hold the text (and if stays blank we know
        # we have an error).
$invisible = 0; # We will cound invisible items...

// A special case (that should be moved for security): change the visibility
// of an entry.

if (!empty($term_id) and !empty($set_visible)) {
    if ($set_visible == 'yes') {
        set_visible('terms', $term_id);
    } else {
        set_visible('terms', $term_id, 'no');
    }
}

// Given a table name, sets the 'visible' field of the record with id=$id
// Sets it to yes, unless $not is not empty

function set_visible($table_name, $id, $not = 0)
{
    empty($not) ? $not = 'yes' : $not = 'no';
    $query = "UPDATE $table_name SET visible='$not' WHERE id=$id;";
    $sth = lib_mysql_query($query, $GLOBALS['dbh'], "set_visible($table_name,$id,$not) failed");
    $sth->rowCount();
}

function EditorsLine($id, $sort, $visible, $class)
{
  # When the user adds 'edit=1' to the path, then this line is added before
  # each entry.
    $out = "<b><a href=\"/glossary/admin/index.php?xx_TableName=terms&amp;xx_edit=$id\" class=\"none\">edit</a></b> |
        <a href=\"/glossary/admin/index.php?xx_TableName=terms&amp;xx_delete=$id\" class=\"none\">delete</a> ";

    $out = "Term #$id  [ $out ]\n ";

    if ($visible == 'no') {
        $out .= "<B>NOT VISIBLE</B> ";
    }
    $out .= " &nbsp; &nbsp; (Used in what? $class) ";
    $out .= "&nbsp; Links [ <a href=\"/glossary/admin/index.php?xx_TableName=links&amp;" .
    "ind_search1=$sort&amp;ind_search2=sort\" class=none>edit</a> ] &nbsp;
	or see <A href=\"/glossary/admin/index.php?xx_TableName=terms&amp;ind_visible=no\" class=none>all
		<b>non-visible terms</b></A>\n";
    return "<div class=\"alert alert-danger\"> $out </div>\n";
}

function seek_next($name, $step)
{
    global $dbh;
  # Look for the first entry past $name.  If $step is negative seeks previous;
  # 0 for a random entry.  Returns the sort column of this entry.
  # Name might be empty (NULL) if wrapping past the end.

  # First, form the $query
    if ($step > 0) {      # Find the next entry
        if (empty($name)) {
            $name = '';
        }
        $where = "WHERE class='glossary' AND name > :name";
        $direction = 'ORDER BY name';
    } elseif ($step < 0) {   # Find the previous entry
        if (empty($name)) {
            $name = 'zzz';
        }
        $where = "WHERE class='glossary' AND :name > name";
        $direction = 'ORDER BY name DESC';
    } else {          # Find a random entry
        if (empty($name)) {
            $name = '';
        }
        $where = "WHERE class='glossary' AND name <> :name";
        $direction = 'ORDER BY RAND()';
    }
    $where .= " AND visible='yes'";
    $query = "SELECT sort FROM terms $where $direction LIMIT 1";
# print "<li>step: $step; query: $query;";

    try {
        $sth = $dbh->prepare($query);
        $sth->bindParam(':name', $name, PDO::PARAM_STR);
        $sth->execute();
    } catch (Exception $ex) {
        lib_mysql_die('Invalid query in seek_next (118)', $query);
    }
  # Okay, that should do it, spit out answer
    if ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        return($row['sort']);
    } else {
      # If there is no entry this size, return NULL
        return(null);
    }
}

# If $next or $prev are defined, then their value is the'name' of
# the entry that was showing when the user pressed previous or next.
# So now get the previous or next term accordingly.

# print "<li>'$sort' :: '$prev' -- '$next'</li>";
if (empty($sort)) {  # Don't bother if $sort already defined
    if (!empty($next)) {  # empty(0) returns true for 0
        if (!($sort = seek_next($next, +1))) {
            $sort = seek_next(null, +1);
        }
    } elseif (!empty($prev)) {
        if (!($sort = seek_next($prev, -1))) {
            $sort = seek_next(null, -1);
        }
    } elseif (!empty($random)) {
        if (!($sort = seek_next($random, 0))) {
            $sort = seek_next(null, 0);
        }
    }
}

# Build the variable part of the query

$where = '';
if (!empty($sort)) {
    $where = "sort LIKE " . $dbh->quote($sort);
} elseif (is_numeric($term_id)) {  # Get single term to display
    $where = "terms.id=$term_id";
} else {
    $name = "Unknown error";
    $t_text = "This should not happen.  Did you specify the term via
	term_id, sort, next, prev or via the PATH_INFO?  (Currently
	term_id is not numeric and $sort is empty.)";
    $t_adjust_path = "/glossary/";  # Want no wget problems ...
}

# Do the query
if ($where) {
    $query = "SELECT text, visible, see_also, related, refs, refs_tr, name,
	id, sort, class FROM terms WHERE $where";
    $sth = lib_mysql_query($query, $dbh, 'Invalid query in page.php (169), contact Chris');

  # Get the term
    if ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        $name = $row['name'];  # Depending on which search was used, not all of these are set
        $id = $row['id'];
        $sort = $row['sort'];
      # if ($row['class'] == 'curio') $t_css = '/curios/includes/template.css';
        if (!empty($GLOBALS['edit'])) {
            $t_text .= EditorsLine($id, $row['sort'], $row['visible'], $row['class']) . "<P>";
        }
        if ($row['visible'] == 'yes' or $GLOBALS['edit'] == 1) {
            $text = $row['text'];
            include_once("bin/modify.inc");
          # Perhaps has TeX like entities we need to translate!
            $text = modify_entities($text);
          # Definitely expect words to cross-link (using second parameter to
          # avoid links back to this same page!)
            $text = modify_add_links($text, $sort);

            $submitter = (empty($row['submitter']) ? '' :
            "&nbsp; [<a href=\"/glossary/ByOne.php?submitter=$row[submitter]\">$row[submitter]</a>]");

            $t_text .= $text . "$submitter<P>\n";
        } else {
            $invisible++;
        }
    } else {
      # People were causing giant loops using wget on errors, so no relative paths here
        header('Location: https://t5k.org/glossary/search.php?searchstring=' . $sort) ;
        lib_mysql_die('Query returned no results<blockquote>
	One possibility is that you mistyped the URL.  You might try using
	<a href="/glossary/index.php">the index</a> or just
	<a href="/glossary/search.php">search
	this site</a>.  If you think there is an error in the system,
	go to <a href="/glossary/home.php">home page</a> and e-mail the technical editor.
	</blockquote>', $query);
    }

    $sort = $row['sort'];

  # Are there non-visible entries?
    if (!empty($invisible)) {
        $t_text .= '<DIV style="FONT-SIZE: smaller">(There ' .
        ($invisible == 1 ? 'is one entry' : "are $invisible entries") .
        " for this term that " . ($invisible == 1 ? 'has' : 'have') .
        " not yet been approved by an editor.)<P></DIV>";
    }

  # Add any 'see_also' links
    if (!empty($row['see_also'])) {
        include_once("bin/SplitRef.inc");
        $t_text .= SplitRef_References(
            $row['see_also'],
            '<p><b>See Also:</b> ',
            ', ',
            '',
            '',
            'Reference_Make_Glossary_Link'
        );
    }

  # Add any 'related' links
    $related = $row['related'];
    if (!empty($related)) {
        include_once("bin/SplitRef.inc");
        $t_text .= SplitRef_References(
            $related,
            '<p><b>Related pages</b> (outside of this work)<ul><LI>',
            '<LI>',
            '</UL>',
            ''
        );
    }

  # Add any references.  refs_tr should exist, but might not if entry
  # just created  or updated as this field is filled by a separate routine
    if (!empty($row['refs_tr'])) {
        $t_text .= '<p><b>References:</b>' . $row['refs_tr'];
    } else {
        $refs = $row['refs'];
        if (!empty($refs)) {
            include_once("bin/SplitRef.inc");
            $t_text .= SplitRef_References(
                $refs,
                '<p><b>References:</b><ul><LI>',
                '<LI>',
                '</UL>',
                '',
                'Reference_Anchor'
            );
        }
    }
}

if (empty($t_text)) {
    lib_mysql_die("No such entry found!  Are the id's correct?", $query);
}

# The templates uses $t_text for the text, $t_title...
$t_title = $name;
$edit = (empty($edit) ? '' : "?edit=$edit");

# Let $next, $prev and $random be id's that point tothe next, prev and a
# random entry.  I use to use 'next' and 'prev'...  and let the system get
# these only when pressed--but for robots a single way of reaching
# each page is best (id=...) and keeps if from thinking it found
# three different pages

$next = seek_next($name, +1);
$prev = seek_next($name, -1);
if (!empty($name)) {
    if (!($next = seek_next($name, +1))) {
        $next = seek_next('', +1);
    }
    if (!($prev = seek_next($name, -1))) {
        $prev = seek_next('', -1);
    }
    $random = seek_next($name, 0);

    $t_submenu = "
    <a href=\"${t_adjust_path}xpage/$prev.html$edit\" class=\"text-white d-print-none\">&bull; Previous</a> &nbsp; &nbsp; &nbsp;
    <a href=\"${t_adjust_path}xpage/$next.html$edit\" class=\"text-white d-print-none\">&bull; Next</a> &nbsp; &nbsp; &nbsp;
    <a href=\"${t_adjust_path}xpage/$random.html$edit\" class=\"text-white d-print-none\">&bull; Random</a>";
}

$t_meta['description'] = "Welcome to the Prime Glossary: a collection
   of definitions, information and facts all related to prime
   numbers.  This pages contains the entry titled '$name.'
   Come explore a new prime term today!";
$t_meta['add_keywords'] = $name;

include("template.php");
