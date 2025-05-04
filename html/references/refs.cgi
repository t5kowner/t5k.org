#!/usr/bin/perl -w
# A routine to serve references via BibTeX database

use FindBin qw($Bin);  # For portability when mirroring...
use lib "$Bin", "$Bin/../bin", "$Bin/../bios/db";
my $RelativeURL = $ENV{'SCRIPT_NAME'}||'./';
$RelativeURL =~ s:references/.*$::;

use my;
use SlurpFile;
use ReadBibHTML;

# Globals:

$BibTeXData = '';  # Used in ShowBibTeX to show BibTeX data
$RawDataPath = 'primes.bib';

my %DBMByAuthor;     # Used in Author to show author's pubs
$DBMByAuthorPath = 'ByAuthor';

$ScriptURL = '/references/refs.cgi';
        # To avoid virtual problems
	# somewhere I was getting refs.cgi/refs.cgi

# Get the data, what do they want?

unless (&my::ReadParse(*In)) {
  if (defined $ENV{'PATH_INFO'} and $ENV{'PATH_INFO'} =~ /^\/(\w+)$/) {
    $In{'item'} = $1;   # scrubed in the loop below
  } else {
    $In{'DefaultText'} = 1;    # Just give the basic stuff
  }
}

if (defined $In{'PersonalPage'}) {
  &ReadBibHTML::configure('IgnoreOmits','ReverseSortByDate','NoKeys')
  # Publist for my personal home page--special rules
}

# Check for bad stuff!  All variable should contain only \w* strings
# or a comma delimted list of such (or be empty)

foreach (keys %In) {
   next unless $In{$_};
   $In{$_} =~ /([\w ,]*)/;
   $In{$_} = $1;
}

# Lets greet our kind visitor

print &GenericHeader;

# Now lets act on it

print &LinkBar; # Here so it can be adjusted based on $In{}

my $context = $ENV{'REQUEST_URI'} || '(undefined in refs.cgi)';
$context .= ' from '.($ENV{'HTTP_REFERER'} || 'unspecified in ENV');
print &DefaultText if delete $In{'DefaultText'};
print &ReadBibHTML::ShowItems(delete $In{'item'},'medium',$context) if defined $In{'item'};
print &ReadBibHTML::ShowItems(delete $In{'long'},'long',$context) if defined $In{'long'};
&ShowRawItems(delete $In{'raw'}) if defined $In{'raw'};

if (defined $In{'range'}) {
  print &ReadBibHTML::ShowRange(delete $In{'range'},'medium')
} elsif (defined $In{'author'}) {
  &ShowAuthorsPubs(delete $In{'author'})
} elsif (exists $In{'authorindex'}) {
  &ShowAuthorIndex(delete $In{'authorindex'});
}

print &GenericFooter;
exit;

######  support routines

sub ShowAuthorsPubs {
  # Prints all of the authors pubs in the database
  my $Item = shift or &my::punt('No item passed to ShowAuthorsPubs');
  dbmopen(%DBMByAuthor,$DBMByAuthorPath,0644)
    or &my::punt('Could not read dbm file: ',$DBMByAuthorPath);
  my $Items = $DBMByAuthor{$Item} || '';
  dbmclose(%DBMByAuthor);
  if ($Items) {
    print "<h4>All items with author $Item (sorted by date)</h4>\n";
    $Items =~ s/\0/,/go;
    &ReadBibHTML::configure('SortByDate') unless delete $In{'PersonalPage'};
    print &ReadBibHTML::ShowItems($Items,undef,'[refs.cgi\'s ShowAuthorsPubs]');
  } else {
    print "<font color=red>No items found for author: $Item</font>";
  }
}

sub ShowAuthorIndex {
  dbmopen(%DBMByAuthor,$DBMByAuthorPath,0644)
    or &my::punt('Could not read dbm file: ',$DBMByAuthorPath);
  # First we print a jump bar
  my $out = "<center><hr width=\"90%\">Authors: [";
  foreach ((A..Z)) {
    $out .= "<a href=\"#$_\">$_</a> ";
  }
  print "$out ] <hr width=\"90%\"></center>\n";
  # now a info line
  print "<h4>Last name of authors and editors who appear in bibliographic database</h4>\n";
  # Now let's do the work
  my $Letter='';
  print "<blockquote><dl compact>";
  foreach (sort keys %DBMByAuthor) {
    ($First) = ($_ =~ /(.)/o);
    if ($First ne $Letter) {
      $Letter= $First;
      print "<dt><a name=$Letter>$Letter</a><dd>\n";
    }
    print "<a href=\"$ScriptURL?author=$_\">$_</a>, ";
  }
print "<a name=U></a><a name=X></a>";  # Just to avoid linklint errors
  print "</dl></blockquote>";
  dbmclose(%DBMByAuthor);
}

sub ShowRawItems {
  print "<h4>Item(s) in original BibTeX format</h4>\n";
  print "\n<blockquote><pre>\n";
  foreach (split(/,\s*/,shift)) { &ShowBibTeX($_) }
  print "\n</pre></blockquote>\n";
}

sub ShowBibTeX {
  my $Item = shift or &my::punt('No item passed to ShowBibTeX');
  $BibTeXData or
    $BibTeXData = &SlurpFile::SlurpFile($RawDataPath)
     or &my::punt('Could not read BibTeX file: ',$RawDataPath);
  my $out;
  ($out) = ($BibTeXData =~ /(@\w+\{$Item,\s+.*?\n\})/si);                 # Items
  ($out) = ($BibTeXData =~ /\n(@\w+\{\s*$Item\s*=.*?\})/si) unless $out;  # Strings
  $out =~ s/</&lt;/go if $out; # Protect HTML
  print $out||"<font color=red>$Item not found</font>","\n";
}

sub DefaultText {
   my $out = &SlurpFile::SlurpFile('default.page')
      || "Fatal error: missing default.page";
   $out . "<blockquote><pre>". &SlurpFile::SlurpFile('count.txt')."</pre></blockquote>";
}

sub GenericHeader {
  my $title = shift || 'Reference Database';
  my $menu = &SlurpFile::SlurpFile("../includes/menubar.txt")
        ||  "Menu error";
  &my::HTML_Header.'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"  "http://www.w3.org/TR/html4/loose.dtd">
<HTML><HEAD>
<link rev="made" href="mailto:admin@t5k.org">
<LINK REL="SHORTCUT ICON" href="/favicon.ico">
<link type="text/css" href="/includes/primepages.css" rel="stylesheet">
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<TITLE>'.$title.'</TITLE>
</HEAD>
<BODY BACKGROUND="'.$RelativeURL.'gifs/2blue.gif">

<TABLE CELLPADDING=5>
 <TR>
  <TD WIDTH="40"></TD>
  <TD>
   <TABLE BORDER=2 CELLPADDING=10 >
    <TR>
     <TD WIDTH="10"></TD>
     <TD BGCOLOR="#FFFFFF"><B><FONT SIZE="+2">'.$title.'</FONT></B>
       <BR>(references for the <A HREF="'.$RelativeURL.'index.html">Prime Pages</A>)
     </TD>
    </TR>
   </TABLE>
  </TD>
  <td align=right>
   <a href="/index.html"><img src="'.$RelativeURL.'assets/title1.gif" width=378
   height=70 alt="The Prime Pages" border=0 align=right></a>
  </td>
 </TR>
</TABLE>

<P>
<TABLE CELLPADDING=2 WIDTH="100%"><TR VALIGN=TOP><TD width=80>'.$menu.'</TD>
 <TD width=560>
   This is the <a href="'.$RelativeURL.'index.html">Prime Pages</a>\'
   interface to our BibTeX database.&nbsp;  Rather than being an exhaustive database,
   it just lists the references we cite on these pages.&nbsp;  Please <a
   href="mailto:admin@t5k.org">let me know</a> of any errors you notice.'
}

sub LinkBar {
  my $out = '<blockquote>References: [ ';

  $out .= (exists $In{'DefaultText'} ?
     '<b>Home</b> | '
	: "<A href=\"$ScriptURL\">Home</a> | ");


  $out .= (exists $In{'authorindex'} ?
     '<b>Author index</b> | '
	: "<A href=\"$ScriptURL?authorindex\">Author index</a> | ");

  $out .= (exists $In{'keyindex'} ?
     '<b>Key index</b> | '
	: "<A href=\"$ScriptURL?keyindex\">Key index</a> | ");

  $out .= ' Search ]</blockquote>';

  if (exists $In{'keyindex'}) {
    delete $In{'keyindex'};
    $out .= '<div align=center><hr>
	Article keys: [ <A href="refs.cgi?range=a">A</A> <A
	href="refs.cgi?range=b">B</A> <A href="refs.cgi?range=c">C</A> <A
	href="refs.cgi?range=d">D</A> <A href="refs.cgi?range=e">E</A> <A
	href="refs.cgi?range=f">F</A> <A href="refs.cgi?range=g">G</A> <A
	href="refs.cgi?range=h">H</A> <A href="refs.cgi?range=i">I</A> <A
	href="refs.cgi?range=j">J</A> <A href="refs.cgi?range=k">K</A> <A
	href="refs.cgi?range=l">L</A> <A href="refs.cgi?range=m">M</A> <A
	href="refs.cgi?range=no">NO</A> <A href="refs.cgi?range=pq">PQ</A> <A
	href="refs.cgi?range=r">R</A> <A href="refs.cgi?range=s">S</A> <A
	href="refs.cgi?range=tuv">TUV</A> <A href="refs.cgi?range=w">W</A> <A
	href="refs.cgi?range=xyz">XYZ</A> ]<hr></div>

	<p>The articles in this database are cited usings keys in an
	author-date format.&nbsp;  For example, an article by Caldwell in
	1994 would be Caldwell94 (or perhaps Caldwell94a or Caldwell94b... if he
	had several that year).&nbsp;  For multiple authors, an article by
	Dubner, Caldwell and Keller in 1984 would be DCK84.';
  }
  $out;

}

sub GenericFooter {
  my $sig = &SlurpFile::SlurpFile("../includes/signature.txt") ||
                  "Signature line error";
  &my::HTML_End("</td></tr></table>\n$sig");
}
