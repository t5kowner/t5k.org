<?php

#t# Should be nothing to change for end users

# The main method in this module is References.  It takes
# a comma delimited list of strings like
#
#    URL=optional link text=optional post text=optional pretext
#
# and converts it to a list of HTML links.  See below for more
# info.
#
########### Examples of use from make.glossary ############
#
#  $temp = SplitRef_References(&db::Value($item,'related'),
#      '<p><b>Related pages</b> (outside glossary)<ul><LI>',
#      '<LI>','</UL>','','');
#
#  $temp = SplitRef_References(&db::Value($item,'references'),
#      '<p><b>Article references</b><ul><LI>','<LI>','</UL>',
#      '','Reference_Anchor');
#
############################################################

############################################################
#
# &References(string, prefix, delimiter, postfix, message,
#                           \&adjust)
#
# Expects a comma delimited string of references of the form
#
#    URL=optional link text=optional post text=optional pretext
#
# &$adjust is called (if defined) to create the URL links (default
# is to wrap it with ('<a href="' and '">').
# Returns text string starting with optional
# prefix, then each ref link separated by the optional delim and
# ends with the optional postfix.  If message and not string,
# then prints prefix.message.postfix.
# See adjust methods below.

function SplitRef_References(
    $references = '',
    $out = '',
    $delim = '',
    $postfix = '',
    $message = '',
    $adjust = 'Reference_Make_Link_Default'
) {
    if (empty($references) and !empty($message)) {  # maybe just a spacer in the list?
        $out .= $message;
    } else {
        $delim2 = '';
      # This is a comma delimited list (with some quoted commas '\,') of ...
      # (I used \0 instead of XxX but it failed--why?)
        $references = preg_replace('/\\\\,/', 'XxX', $references);    # protect quoted commas
        $references = preg_split('/\s*,\s*/', $references);
        for ($i = 0; $i < count($references); $i++) {
            $item = preg_replace('/XxX/', ',', $references[$i]);    # return quoted commas
          # ... of = delimited lists (with some quoted ='s)
            $item = preg_replace('/\\\\=/', 'XxX', $item);      # protect quotes ='s
            $terms = preg_split('/\s*=\s*/', $item);
            $URL  = preg_replace('/XxX/', '=', $terms[0]);      # return quoted equals
            if (empty($URL)) {
                print "<font color=red>Empty URL in $item</font>";
            }
            $Ref  = isset($terms[1]) ? preg_replace('/XxX/', '=', $terms[1]) : '';
            $Post = isset($terms[2]) ? preg_replace('/XxX/', '=', $terms[2]) : '';
            $Pre  = isset($terms[3]) ? preg_replace('/XxX/', '=', $terms[3]) : '';
            if (!empty($Post)) {
                $Post = ' ' . $Post;
            }
            $out .= $delim2 . $Pre . $adjust($URL, $Ref) . $Post;
            $delim2 = $delim;
        }
    }
    return $out . $postfix;
}

# Adjust methods are passed (URL[,link string]).

# Just plain old URL's I think
function Reference_Make_Link_Default($URL, $Link)
{
 # default, more below
  # The related pages shows up here, but not see_also (which uses the next)
    if (empty($Link)) {
        $Link = $URL;
    }
    if (preg_match('/^\w+$/', $URL)) {  # Spots all see_also anchors?
        $URL = "page.php/$URL.html";
    }
    return " <a href=\"$URL\">$Link</a>";
}

function Reference_Make_Glossary_Link($URL, $Link)
{
 # add 'class=glossary'
  # I think only see_also shows up here.
    if (empty($Link)) {
        $Link = $URL;
    }
  # Had trouble here--sometimes called from page.php/Fermats.html and ended up with page.php/page.php
    if (preg_match('/^\w+$/', $URL)) {  # Spots all see_also anchors?
        $URL = "/glossary/xpage/$URL.html";
    }
    return " <a href=\"$URL\" class=glossary>$Link</a>";
}

# Links to the Reference pages in brackets
function Reference_Anchor($URL, $Link)
{
  # Here the URL is either a refs.shtml anchor or a true URL
    if (preg_match('/^\w+\d+\w?$/', $URL)) {  # Spots all reference anchors?
        $URL2 = '/references/refs.cgi/' . $URL;
    }
    return '[<a href="' . $URL2 . '">' . $URL . '</a>' . (!empty($Link) ? ', ' . $Link . ']' : ']');
}
