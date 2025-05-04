<?php

#t# ready (Nothing changed, nothing here for end users)

if (!isset($mdbltcolor)) {
    $mdbltcolor = 'info-color';
    $mdbmedcolor = 'primary-color';
    $mdbltcolor = 'primary-color-dark';
}

require_once __DIR__ . "/../../../library/constants/environment.inc";

function modify_help()
{

    print <<< HERE
   There are several ways in which we routinely modify text, they are collected
here (in the file modify.inc) to be shared with other programs:

<ul>
  <li> The first is to change TeX-like entities (e.g., \\equiv) to HTML entities
(&equiv;) or a gif.&nbsp; The changes are defined by the array \$modify_changes and made
by the function <b>modify_entities(\$text)</b>.&nbsp;  Note that <b><a
href=modify.php?entities=1>modify_show_entities()</a></b> "explains" the
concept and gives a table of changes made (plus an example).
  <br> &nbsp; &nbsp; This routine also changes the expression \image(filename)
to an HTML link to the file (adjusting paths...).&nbsp;  You may put
extra info into the image tag (after a comma) as in the following example:
<blockquote>
  \\image(icon.gif)<br>
  \\image(icon.gif, align=left valign=center)<br>
  \\imagecaption(icon.gif,caption)<br>
  \\imagecaption(icon.gif,caption,align=right)<br>
</blockquote>
(The second parameter of the image tag will be placed directly into the HTML IMG
tag, the third parameter for the imagecaption will be placed into the TABLE tag.)<P>
References to the books... on the bibliography pages is by "key" as follows:
<blockquote>
  \\cite{CP2001}<br>
  \\cite[p. 23, eq. 5]{CP2001}
</blockquote>
(This is purposely identical to LaTeX/BibTeX format; but LaTeX allows a key_list, we currently
allow only one key)<p>

  <li> The second is to add links to our glossary.&nbsp;  This is done by the routine
<B>modify_add_links(\$text)</b> and is "explained" by <b><a
href=modify.php?links=1>modify_show_links()</a></b>.<br><br>

  <li> The third is to balance/limit HTML.&nbsp;  When we allow the public (or
ourselves!) to add entries to various databases over the web, sometimes they submit
unbalanced HTML (e.g., writing &lt;i&gt;n&lt;i&gt; instead of &lt;i&gt;n&lt;/i&gt;) or
submit malicious html (meta redirects, embed javascript...).&nbsp;  So this routine must
add the missing closing tags, and remove the disallowed tags, so that entry will not mess
up all of the following entries.&nbsp; This is done by the routine
<B>modify_adjust_html(\$text)</b> and is "explained" by <b><a
href=modify.php?adjust=1>modify_show_adjustments()</a></b>.

</ul>

Click on the links above for more information (or here to <a href=..>to return
to the collection</a>)
HERE;
}

$modify_changes = array(
   array('\equiv', '≡'),
   array('\leq', '≤'),
   array('\le',  '≤'),
   array('\geq', '≥'),
   array('\ge',  '≥'),
   array('\lt', '<'),
   array('\gt', '>'),
   array('\pm', '±'),
   array('\cdot', '·'),
   array('\TM', '&trade;'),
   array('\COPY', '©'),
   array('\REG', '&reg;'),
   array('\QED','<span class="float-right" title="Q.E.D.">∎</span>'),
     array('\alpha', 'α'),
     array('\beta', 'β'),
   array('\gamma', 'γ'),
   array('\Gamma', 'Γ'),
   array('\delta', 'δ'),
   array('\epsilon', 'ε'),
   array('\lambda', 'λ'),
   array('\mu', 'μ'),
     array('\minus ','−'),
     array('\minus','−'),
   array('\nu', 'η'),
   array('\phi', '&phi;'),
   array('\pi', 'π'),
   array('\Phi', 'Φ'),
     array('\rho','ρ'),
   array('\sigma','σ'),
   array('\Sigma','Σ'),
   array('\sqrt','√'),
   array('\tau','τ'),
     array('\theta', 'θ'),
   array('\zeta', 'ζ'),
   array('\ltcolor', "$GLOBALS[mdbltcolor]"),
   array('\medcolor', "$GLOBALS[mdbmedcolor]"),
   array('\drkcolor', "$GLOBALS[mdbdrkcolor]")
);


function modify_entities($text)
{

    global $modify_changes;
    reset($modify_changes);
# If no backslashes, then nothing to change
    if (empty($text) or !preg_match('/\\\\/', $text)) {
        return $text;
    }

  # Images might have been inserted with the \image( ) tag
    $text = preg_replace(
        '/\\\\image\(([^\),]+)\,?([^\)]*)\)/',
        '<img src="' . basic_upload_where('client') . "$1\" $2>",
        $text
    );
  # Images might have been inserted with the \imagecaption( ) tag
    $text = preg_replace(
        '/\\\\imagecaption\(([^\),]+)\,?([^\),]+)\,?([^\)]*)\)/',
        "<table cellpadding=0 cellspacing=0 $3 align=right>
	<tr><td><img src=\"" . basic_upload_where('client') . "$1\" $3></td></tr>
	<tr><td align=center><font size=-3>$2</font></td></tr>\n</table>\n",
        $text
    );

  # References might have been inserted with the \cite{ } tag
  # Note LaTeX/BibTeX allow a key_list here!
    $text = preg_replace(
        '/\\\\cite\{([^\},]+)\}/',
        "[<a href=\"/references/refs.cgi/$1\">$1</a>]",
        $text
    );
    $text = preg_replace(
        '/\\\\cite\[([^\]]*)\]\{([^\},]+)\}/',
        "[<a href=\"/references/refs.cgi/$2\">$2</a> $1]",
        $text
    );

# 2021 update:   while ( list($key, $value) = each($modify_changes) ) {
    foreach ($modify_changes as $key => $value) {
        $match = '/' . preg_quote($value[0]) . '\b/';
      # print "<li>$match";
        $replace = $value[1];
        $text = preg_replace($match, $replace, $text);
    }
    return($text);
}

function modify_show_entities()
{
    global $bgcolor, $ltcolor, $medcolor;
    global $modify_changes;
#  include("lib.inc");  included by basics.inc

    $out = "<p>The goal of modify_entities() is to allow us to use non-text items
  (Greek letters, math symbols) in our text.  I have chosen to do this using
  TEX-like codes so that is someday we translate these to a book, we can
  use TEX to type set it more easily.  We probably ought to use the $ $ TEX math
  delimiters, and <i>eventually I plan to</i>.  But not right now.</p>
  <p>Since this routine is
  very easily altered we provide a table (below) of what is currently supported.
  We will add new symbols as needed (but is should stay backwards compatible).
  I hate using gifs for things like &equiv; but the HTML entities are not
  universally supported (yet).</p>
  <p>I added three at the bottom to all users to set <code>class=\"\\ltcolor\"</code>
  to color table entries ... using the collections color scheme.  Stupid?</p>";

    $out .= "<blockquote><table>\n";
    $out .= "<tr bgcolor=\"$medcolor\"><th width=60>use</th></th>
	<th width=60>to get</th><th>actual code</th></tr>\n";
    while (list($key, $value) = each($modify_changes)) {
        $match = htmlentities($value[0]);
        $becomes = $value[1];
        $replace = htmlentities($value[1]);
        $out .= lib_tr() . "<td align=center>$match</td><td align=center>$becomes</td>
	<td>$replace</td></tr>\n";
    }
    $out .= "<tr bgcolor=\"$medcolor\"><th width=60>&nbsp;</th></th>
	<th width=60>&nbsp;</th><th>&nbsp;</th></tr>\n";
    $out .= "</table></blockquote>\n";

    $example = "\pi(\pi) \leq 2\pi \pm \pi but not \pie \\equiv \pi (as \pie is not defined).
	Some Greek: \lambda \zeta \\theta \mu. ";
    $out .= "Example <blockquote>" . htmlentities($example);
    $out .= "</blockquote>becomes<blockquote>" . modify_entities($example) . "</blockquote>\n";
    return ($out);
}

// Now add links to the glossary

function modify_show_links()
{
    print "The routines in this file link text to the glossary entries.
    Below you can see what entries are linked to by what pattern.  Mouse
    over the entry to see the title, or click the entry to see it in
    the glossary.<P>
    Sometimes we wish to avoid a link.  Encasing text as follows will
    keep it from being linked. <blockquote>
    &lt;nolink>no text in here will be linked&lt;/nolink></blockquote>
    <P>\n";

  # Note, may be called from other databases (e.g., Curios!) so need to override defaults
    $db = basic_db_connect('localhost', 'primes_', T5K_DB_PRIMES_PASSWORD, 'glossary'); // Connects or dies
    $query = "SELECT tag, sort, entry, sense, weight, class FROM links ORDER BY weight DESC";
    $sth = lib_mysql_query($query, $db, 'Invalid query, contact Chris');

    global $medcolor;
    print "<table width=90% cellspacing=3>" .
    "<tr bgcolor=\"$medcolor\"><th width=200 align=right>target (sort)</th>
	<th>pattern by (tag)</th><th>class</th></tr>\n";
    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
        print lib_tr();
        print "\n  " . '<td width=200 align=right><a href="/glossary/xpage/' . $row['sort'] .
        '.html" title="' . $row['entry'] . '">' . $row['sort'] . "</td>\n";
        $tag = preg_replace('/\s+/', '\\s+', $row['tag']);
        print '  <td>' . $tag . "</td>\n";
        print "<td title=\"weight=$row[weight]\" align=center>$row[class]</td>\n</tr>\n";
    }
    print "</table>";
}

# Now the hard part!

function modify_add_links($text2modify, $BaseURL = '', $extra = '', $class = '')
{
  # Extra is extra HTML to imbued in the <a> tag, e.g., 'class=none' to avoid
  # the link's background color being changed by the .css file
  # Adding 'curio' for $class will all matches to the curio definitions in the
  # glossary

    if (empty($text2modify)) {
        return '';
    }
    if (!preg_match('/class\s*=/', $extra)) {
        $extra = 'class=glossary';
    }

  # Who is calling effect the type of links.  From glossary the URL's
  # are short (and we do not want to link back to same page!). Need
  # longer URL's from other directories.
  # print "<li>$_SERVER[PHP_SELF]";
  # if (preg_match("/\bglossary\/page.php\/(\w+)\.\w+/",$_SERVER['PHP_SELF'],$matches)) {
  #  # must be a call from the glossary directory
  #  $path = '../page.php/';
  # } else if (preg_match("/\bglossary\/page.php/",$_SERVER['PHP_SELF'])) {
  #  # must be a call from the glossary directory ending in ?sort-... or
  #  # ?term_id=... which is not in PHP_SELF
  #  $path = 'page.php/';
  # } else {
  #   $path = '/glossary/page.php/';
  # }
    $path = '/glossary/xpage/';


  # Note, may be called from other databases (e.g., Curios!) so need to override defaults
    $db = new PDO('mysql:host=localhost;dbname=glossary;charset=utf8', 'primes_', T5K_DB_PRIMES_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $query = 'SELECT tag, sort, entry, sense, weight FROM links ';
    if ($class != 'curio') {
        $query .= "WHERE class!='curio' ";
    }
    $query .= 'ORDER BY weight DESC';
    try {
        $stmt = $db->query($query);
    } catch (PDOException $ex) {
        lib_mysql_die('modify.inc: Invalid query', $query);
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['sort'] == $BaseURL) {
            continue; # Why link to self?
        }

        $url = $path . $row['sort'] . '.html';
        $tag = $row['tag'];
        $tag = preg_replace('/\s+/', '\s+', $tag);
        $case = ($row['sense'] == 'yes' ? '' : 'i');

      # Skip this $tag if it is not in the text
      # print "<li>testing /$tag/$case &nbsp; (from $row[sort])";
        if (preg_match("/$tag/$case", $text2modify)) {
        # I wish we could:
        #
        #    $text2modify =~ s/($tag)/<a href=$url $extra title='glossary'>$1<\/a>/
        #
        # but what if the tag is in a URL or linked href or <nolink> container?

            $tested = '';       # Modified text lands here (and is placed
                # back in $text2modify at end of loop
            $rest = $text2modify;   # Unprocessed is here
            $start = '';        # $start is the part before a <
                # so we look for matches in start

            while ($rest) {
                preg_match("/^([^<]*)(.*)$/s", $rest, $matches);
                $start = $matches[1];
                $rest = $matches[2];

                # print "\n<li><font color=green>$tested</font>
                # <font color=red>$start</font>
                # <font color=blue>$rest</font>\n";

                if (preg_match("/\b($tag)\b/$case", $start)) {
                    $start = preg_replace("/\b($tag)\b/$case", "<a href=\"$url\"
		$extra title='glossary'>$1</a>", $start);
                    $tested .= $start . $rest;    # done if start matches
                    $rest = '';
                } else {              # otherwise, remove a tag
                    $tested .= $start;
                    if (preg_match("/^<[aA]\s/s", $rest)) {  # hypertext reference?
                        preg_match("/^(.*?<\/[aA]>)(.*)$/s", $rest, $matches) or die("unmatched <a");
                        $tested .= $matches[1];
                        $rest = $matches[2];
                    } elseif (preg_match("/^<nolink/si", $rest)) {
               # don't link between <nolink>...</nolink>
                        preg_match("/^(.*?<\/nolink>)(.*)$/si", $rest, $matches)
                        or die("unmatched <nolink>");
                        $tested .= $matches[1];
                        $rest = $matches[2];
                    } elseif (preg_match("/^(<[^>]*>?)(.*)$/s", $rest, $matches)) {
                        $tested .= $matches[1];
                        $rest = $matches[2];
                    }
                }
            }
            $text2modify = $tested;
        }
    }

  # Finally remove the <nolink> containers
    $text2modify = preg_replace("/<\/?nolink>/", '', $text2modify);
    return $text2modify;
}


// modify_adjust_html($text) removes any tags not in modify_GoodTags and balances
// all open tags except those listed in $modify_DoNotBallance.  Changes
// are noted (appended to) the global $modify_ErrorMessage (which will be empty
// if everything was fine).  '<!--#include' is also removed (hardcoded as
// the only non-'<\w+' tag below))

$modify_DoNotBallance = ' P IMG LI DT DD BR HR ';
    # Tags that do not need to be closed to balance HTML text.
    # (must be space delimited with space on end)

$modify_GoodTags = $modify_DoNotBallance .
    " A ABBR ACRONYM ADDRESS B BIG BLOCKQUOTE " .
    " CODE CENTER CITE DD DL DT EM FONT " .
    " H2 H3 H4 HR I KBD NOBR OL PRE PLAINTEXT " .
    " SMALL SUP SUB STRONG TT U UL VAR " .
    " TABLE THEAD TBODY TFOOT TD TR TH CAPTION ";

// What about AREA IMG MAP COL

$modify_RemoveThese = "javascript:|VBScript|onClick|onDblClick|onKeyDown" .
    "|onKeyPress|onKeyUp|onMouseDown|onMouseMove|onMouseOut|onMouseOver" .
    "|onMouseUp|onMouseWheel|style|onerror|AREA|MAP|meta|script|iframe" .
    "|form|object|embed|link|head|applet|body|bgsound|basefont" .
    "|frame|frameset|ilayer|style|title|xml|name|base";

// Replace these with a white list!!!!!!!!!!

function modify_adjust_html($text = '')
{
# Removes 'nontext,' balances <tag> with </tag>

    if (empty($text)) {
        return ''; # Done?
    }

    global $modify_GoodTags;      # What tags are not bad?
    global $modify_ErrorMessage;  # Error message to return?
    global $modify_RemoveThese;

  # Now balance HTML by adding any forgotten closing tags (e.g., the
  # person used <sup> but not </sup>).  Not this destroys the copy of
  # $text as it goes.  See $modify_DoNotBallance.

    $out = $text; # Will return $Out, which will eventually be the
        # modified original plus appended closing tags

    $append = '';
    global $modify_DoNotBallance; # What tags do not need to be closed
    $modify_ErrorMessage = '';
    while (preg_match("/[^<]*<\/?(\w+|!--#include)[^>]*>(.*)/si", $text, $matches)) {
        $tag = $matches[1];
        $text = $matches[2];
        if (!preg_match("/ $tag /i", $modify_GoodTags)) {    # Next remove bad tags
            $modify_ErrorMessage .=
            "The HTML mark-up tag \"$tag\" is not allowed on these pages.  ";
          # Remove the tag and its closing form (if any)
            $out = preg_replace("/<$tag\b[^>]*>/i", '', $out);
            $out = preg_replace("/<\/$tag\b[^>]*>/i", '', $out);
        }
    }

    $out .= $append;
    if (preg_match("/<[^>]*\b($modify_RemoveThese)\b/i", $out, $matches)) {
        $out = preg_replace("/(<[^>]*)\b($modify_RemoveThese)\b/i", '\1Removed', $out);
        $modify_ErrorMessage .= "Abusable keyword $matches[1] removed. ";
    }
  # Call Tidy
    $out = tidy_html_fragment($out);
    if (!empty($GLOBALS['tidy_error_buffer'])) {
        $modify_ErrorMessage .=
        "HTML_Tidy reports: " . htmlentities($GLOBALS['tidy_error_buffer']);
    }
    return $out;
}

# tidy_html_fragment will either return a cleaned-up valid HTML fragment
# or will return FALSE.  The Global string tidy_error_buffer will contain
# any warnings/errors.

function tidy_html_fragment($html)
{
    global $tidy_error_buffer;

  # Wrap the fragment so it looks like an HTML page (necessary to no confuse
  # users with error messages about DocTypes...
    $DocType = '<!DOCTYPE html PUBLIC \"-//W3C//DTD HTML 3.2//EN\">';
    $html_pre = $DocType . "<html>\n<head>\n<title>temp</title>\n" .
    '<meta http-equiv="content-type" content="text/html; charset=UTF-8">' .
    "\n</head>\n<body>\n";
    $html_post = "\n</body>\n</html>";
    $html = $html_pre . $html . $html_post;

  # Now let's do it
    $config = array('indent' => true,
        'doctype' => 'loose',
        'drop-proprietary-attributes' => true,  # Remove unrecognized tags...
        'drop-empty-paras' => true,             # Why have empty paragraphs?
        'show-body-only' => true,               # Contents of the body only
    'char-encoding' => 'utf8',
        'wrap' => 60,
    );
  # 'quote-ampersand' => FALSE,
  # 'fix-uri' => FALSE,
  # other possible options of use: clean, enclose-text, escape-cdata,
  # hide-comments, quote-marks

    $php_version = phpversion();
    $php_version = preg_replace('/^(\d+).*/', '$1', $php_version);

    if ($php_version > 4) {
        $tidy = tidy_parse_string($html, $config, 'utf8');
        $tidy->CleanRepair();

      # Set the global variable to contain the error buffer $tidy->diagnose();
      # Add more info to the error buffer (even when no errors)
        $tidy_error_buffer = tidy_get_error_buffer($tidy);
    } else {
        foreach ($config as $key => $value) {
            tidy_setopt($key, $value);
        }
        $tidy = tidy_parse_string($html);
        tidy_clean_repair();
        $tidy = tidy_get_output();

      # Set the global variable to contain the error buffer $tidy->diagnose();
      # Add more info to the error buffer (even when no errors)
        $tidy_error_buffer = tidy_get_error_buffer();
    }

  # Returns 0 if no error/warning was raised, 1 for warnings or accessibility
  # errors, or 2 for errors.
    if (
        ($php_version > 4 and $tidy->getStatus() > 1) or
        ($php_version == 4 and tidy_get_status() > 1)
    ) {
        $tidy_error_buffer = "Errors to serious to allow display of HTML code. " .
        $tidy_error_buffer;
        return false;
    }
    return $tidy;
}


function modify_show_adjustments($text = '')
{
    global $modify_GoodTags;      # What tags are allowed?
    global $modify_DoNotBallance; # What tags do not need to be closed
    global $modify_ErrorMessage;  # Error message to return?
    global $modify_RemoveThese;   # Strings just removed

    $remove = preg_replace('/\|/', ' ', $modify_RemoveThese);

    if (empty($text)) {
        $text = 'This is <i>unbalanced<i> html text';
    }

    $out = "<h2>I. Modification Procedures</H2>
	First, this routine (<code>modify_adjust_html</code>)
	removes any tags not listed in the variable
	<code>\$modify_GoodTags</code>which currently contains:
	<blockquote><code><b>$modify_GoodTags</b></code></blockquote>
	This is to stop malicious code from being stored in the database.

	<P>Second, in order to not let HTML errors render a page unreadable,
	we then close all HTML tags that were left open--with the exception of
	those listed in the variable <code>\$modify_DoNotBallance</code> which
	currently contains:
	<blockquote><code><b>$modify_DoNotBallance</b></code></blockquote>

	Finally, we also remove all strings below (case-insensitive) to avoid
	embedded Java-Scripts that could be evil <blockquote>
	<code><b>$remove</b></code></blockquote>

	<div class=technote>Technical note: This routine notes any changes in
	the global variable <code>\$modify_ErrorMessage</code>.</div>


	<h2>II. Testing the routine</h2>
	<a name=test_modify></a>You may test it out by placing text here:
	<form method=post action=\"$_SERVER[PHP_SELF]?adjust=1\"><blockquote>
	<input type=hidden value=1 name=adjust>
	<textarea name=text rows=3 cols=70>$text</textarea>
	<BR><input type=submit value=\"submit this text\">
	</blockquote></form>";

    $text = modify_adjust_html($text);

    $out .= "The above text returns <blockquote>$text</blockquote> The actual HTML
	is <blockquote><pre>" . htmlentities($text) . "</pre></blockquote> and the
	error messages (if any) are: <blockquote>
	<font color=red>$modify_ErrorMessage</font></blockquote>";

    return $out;
}
