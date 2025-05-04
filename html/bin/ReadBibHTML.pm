package ReadBibHTML;

# This is the module to read the dbm version of the BibTeX database.
# No other routines need to know the format or even where the databases
# are.  See .../references/README, as well as this directories programs
# Read, formats.pm and washer.pm (which create these DBM's) for info
# on how they are set up.
#
##################  Exportable functions  ##########################
#
# &ShowRange(string,type)
#
#    Expects a string of one or more letters (e.g., 'abc'), will
#    show all items starting with those letters.  Exceptions:  'annote'
#    and 'abstract' will show all annotations and abstracts respectively.
#    'omit' will show those with the 'omit' flag set.  And
#    'all' does just that--in long format.
#
# &ShowItems(list[,type[,note]])
#
#    Here list is a comma delimited string of BibKeys or BibKey=string
#    (where the string is a page, chapter... reference).  If you need
#    a comma in the string, use \, to quote it.  Returns a HTML string.
#
# Here type can be short (no annote, abstract), medium (the default, links
# to annote, abstract--unless they are real short, then include them)
# or long (always print both annote and abstract). The note
# if present, will be used in error messages (so could be
# sort code for the glossary page being created...).
#
# &configure(string)
#
#   Possible values are
#	'QuietError'  	: Print error to StdErr (rather than inlcuding
#			  an error message in the string returned).
#       'NoRawLink'	: will not link the item BibKey to raw BibTeX
#	'NoAuthorLinks'	: do not link authors to the refs.cgi?author=...
#       'NoKeys'        : Uses <LI>Item rather than <DT>Key<DD>Item
#	'IgnoreOmits'	: will show those marked as omit (omit field
# 			  is set in BibTeX database).
#	'SortByDate'	: How do we sort a list of references?
#       'ReverseSortByDate'
#	'SortByKey'	  (Key is default)
#
#################  Configuration constants #########################
#
# Okay, where is the data?  Both needed by &GrabItem
# Also uses href's to the reference cgi, so where does it live?

use FindBin qw($Bin);   # Where is this script stored?  Put full path into $Bin

my $ScriptURL = '/references/refs.cgi';

require AnyDBM_File;    # Works, but don't know what format!
import  AnyDBM_File;

my $RefsRoot = "$Bin/../references/";
if (not -e $RefsRoot.'primes.bib') { $RefsRoot = '/var/www/html/references/'; }

my $FilePath    = $RefsRoot.'primes.bib';
my $DBMDataPath = $RefsRoot.'References';
my $PathToLong  = $RefsRoot.'long/';

# medium prints annotes that are not too long, how long is that?

  $MediumLength = 250;

### Support modules (loded at compile time, not run time!)

use my;
use SlurpFile;

####################################################################
#
# Internal variables for this module

my %DBMData;       # Used in ShowItem to link to dbm file
                   # and as flag (not empty=data loaded)
my %DBMDate;       # Stores just the dates of the items

#Default configuration, can be altered via &configure

$QuietError = 0;   # Do return error message for missing refs...,
                   # just print warn on stderror
$NoRawLink = 0;    # If set, will not link the item BibKey to raw BibTeX
$NoAuthorLinks=0;  # Omit links to other of authors works?
$IgnoreOmits = 0;  # Do NOT ignore entries for which 'omit::BibKey' is set?
$SortBy = 'key';   # What do we sort by"
$DefaultType = 'medium'; # Default type of display
$NoKeys = 0;       # Omit item keys
$AddAnchors = 0;   # Add key as anchor (name) for each entry?

sub configure {
  my $item;
  foreach $item (@_) {
    $QuietError = 1 if $item eq 'QuietError';
    $NoRawLink = 1 if $item eq 'NoRawLink';
    $NoKeys = 1 if $item eq 'NoKeys';
    $NoAuthorLinks = 1 if $item eq 'NoAuthorLinks';
    $IgnoreOmits = 1 if $item eq 'IgnoreOmits';
    $AddAnchors = 1 if $item eq 'AddAnchors';

    $SortBy = 'key' if $item eq 'SortByKey';
    $SortBy = 'date' if $item eq 'SortByDate';
    $SortBy = 'rdate' if $item eq 'ReverseSortByDate';
  }
}

#
##### First the routines that do not know the database format.  #####

sub OpenDBM {
  my %DBM;
  dbmopen(%DBM,$DBMDataPath,0644)
    or &my::punt('ReadBibHTML.pm could not read dbm file: ',
	$DBMDataPath);
  my $key;
  foreach $key (keys %DBM) {
    if ($key =~ /;/o) {
      $key =~ /^(.*?);(.*)$/o;
      $DBMData{$1} = $DBM{$key};
      $DBMDate{$1} = $2;
    } else {
      $DBMData{$key} = $DBM{$key};
    }
  }
}

sub ShowRange {
  # Expects a string of one or more letters (e.g., 'abc'), will show all
  # items starting with those letters.  Exceptions:  'annote', 'abstract'
  # and omit will show all entries with those fields set respectively;
  # Also 'all' shows it all (even those with omit set).

  my $letters=shift or &my::punt('No data passed to ShowRange');
  my $type = shift || $DefaultType;
  my $note = "[Function ReadBibHTML.pm/ShowRange($letters)]";

  %DBMData or &OpenDBM;
  my @items; # To store matching items

  if ($letters eq 'omit') {
    $IgnoreOmits = 1;
  } elsif ($letters eq 'all') {
    $letters = 'abcdefghijklmnopqrstuvwxyz';
    $type = 'long';
    $IgnoreOmits = 1;
  }

  if ($letters !~ /(annote|abstract|omit)/o) {
    foreach (keys %DBMData) {
      next if /^abstract::/o;
      next if /^annote::/o;
      next if /^omit::/o;
      push(@items,$_) if /^[$letters]/io;
    }
    print "<h4>All items with keys beginning with the letter(s): $letters</h4>\n";
  } else {
    foreach (keys %DBMData) {
      next unless /^$letters\:\:/o;
      push(@items,$')
    }
    print "<h4>All entries with \"$letters\"s in the database</h4>\n";
    $type = 'long';
  }
  &ShowItems(join(',',@items),$type,$note);
}

sub ItemSort {
  if ($SortBy eq 'date') {
    my $a_date = ( defined $DBMDate{$a} ? $DBMDate{$a} : 0 );
    my $b_date = ( defined $DBMDate{$b} ? $DBMDate{$b} : 0 );
    return($a_date <=> $b_date || $a cmp $b)
  } elsif ($SortBy eq 'rdate') {
    my $a_date = ( defined $DBMDate{$a} ? $DBMDate{$a} : 0 );
    my $b_date = ( defined $DBMDate{$b} ? $DBMDate{$b} : 0 );
    return($b_date <=> $a_date || $b cmp $a)
  } else { # Should be 'key'
    return (uc($a) cmp uc($b))
  }
}

sub ShowItems {
  my $items=shift;  # Error checked in ShowItem
  my $type = shift || $DefaultType;
  my $note = shift || '';

  %DBMData or &OpenDBM;  # Needs to be here for refs.cgi?author=...

  my $out = ($NoKeys ? '<UL>' : '<blockquote><DL>');

  $items =~ s/\\,/\0/go;  # Protect quoted commas
  my @items = split(/,\s*/o,$items);

  foreach (sort ItemSort @items) {
    s/\0/,/go; # put the quoted commas back (e.g., calls from glossary)
    $out .= &ShowItem($_,$type,$note)
  }
  $out .= ($NoKeys ? '</UL>' : '</DL></blockquote>');
  $out . "\n";
}

# Now the routines that do know dbm format.  Neither should be exported.

sub GrabItem {
  my $Extra = 'class=bib title="other works by this author"';
  my $item=shift or &my::punt('No item passed to GrabItem');
  my $out = $DBMData{$item};
  if ($out =~ /^\0long\/(.*)/o) {  # Long entries stored in a file
    $out = &SlurpFile::SlurpFile("$PathToLong$1",'no die') ||
      "<font color=red>Error: could not read file $PathToLong$1</font>";
  }
  # remember names are stored as <<fullname::last name>>
  if ($NoAuthorLinks) {
    $out =~ s/<<(.+?)::(.+?)>>/$1/go;
  } else {
    $out =~ s/<<(.+?)::(.+?)>>/<a href="$ScriptURL?author=$2" $Extra>$1<\/a>/go;
  }
  $out;
}

sub ShowItem {
  # Should print a single item, may be called repeatedly
  my $Item = shift or &my::punt('No item passed to ShowItem');
  my $type = shift || $DefaultType;
  my $note = shift || '?? [ShowItem in ReadBibHTML.pm]';

  %DBMData or &OpenDBM;

  # This is neceassary for call from glossary...
  ($Item,my $Page,my $Comment) = ($Item =~ /([^=]+)=?([^=]*)=?(.*)/o);

  my $out = '';
  if (defined $DBMData{$Item}) {

    unless ($IgnoreOmits) {
      return('') if defined $DBMData{"omit::$Item"};
    }

    # First the item tag (just the BibKey, perhaps linked to the raw BibTeX)

    unless ($NoKeys) {
      $out = "\n<DT>";
      if ($NoRawLink) {
         $out .= $Item;
      } else {  # link to raw BibTeX
         # Must keep class on the same line due to IE 5 bug
         $out .= "<a href=\"$ScriptURL?raw=$Item\" class=bib".
            " title=\"BibTeX source\">$Item</a>";
      }
      $out .= " ($Page)" if ($Page);  # Used by glossary (I think)
    } else {
      warn "Ignored 'page' $Page (ref $Item in $note)" if $Page;
    }

    if ($AddAnchors) {
       $out .= "<a name=\"$Item\"></a>";
    }

    # Now the actual item as

    $out .= ($NoKeys ? '<LI class=BibItem>' : '<DD class=BibItem>');
    $out .= &GrabItem($Item);

    $out .= " [$Comment]" if ($Comment);
    if ($type ne 'short' and defined $DBMData{"abstract::$Item"}) {
      if ($type eq 'long' or
	  $type eq 'medium' and length($DBMData{"abstract::$Item"}) < $MediumLength
		and $DBMData{"abstract::$Item"} !~ /\0/o) {
        $out .= &GrabItem("abstract::$Item")
      } else {
	$out .= " (<a href=\"$ScriptURL?long=$Item\" class=bib
		title=\"Entry with abstract\">Abstract available</a>)";
      }
    }
    if ($type ne 'short' and defined $DBMData{"annote::$Item"}) {
      if ($type eq 'long' or
	  $type eq 'medium' and length($DBMData{"annote::$Item"}) < $MediumLength
		and $DBMData{"annote::$Item"} !~ /\0/o) {
        $out .= &GrabItem("annote::$Item");
      } else {
        $out .= " (<a href=\"$ScriptURL?long=$Item\" class=bib
		title=\"Entry with annotation\">Annotation available</a>)";
      }
    }
  } else {
    warn "Error: $note is missing reference $Item\n";
    unless ($QuietError) { # Quiet means send only to StrErr, not output
      $out = "<font color=red><dt>$Item<dd>Item not found.</font>";
    }
  }
  $out;
}

1;
