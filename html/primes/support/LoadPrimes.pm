package LoadPrimes;

#use FindBin qw($Bin);   # Where is this script stored?  Put full path into $Bin
#use lib ("$Bin/../bin",$Bin);
use lib qw (
  /var/www/html/primes/support
  /var/www/html/primes/bin
);

use connect_db;
use modify;    # To add links to glossary

# This will (hopefully) be the only program that knows the format of
# the prime list, or even where it is--which will make format changes
# easier.  Everything here is read only.
#
# Routines
#
#   &Load(\@List,\%which,$AdjustLinks)
#
#	Loads the primes into a the list \@List of hash array reference.
#	Example:
#
#            print 'Loaded ',&LoadPrimes::Load(\@Primes)," primes.\n";
#            print ${Primes[4999]}{'rank'},' should be 5000.';
#
#	The hash arrays are like this:
#
#	    $prime = {'rank', 'description', 'digits', 'discoverer', 'year',
#		'comment', 'flags', 'quoted', 'log_10', 'id' };
#
#	where log_10 is the log (base 10) of the number, add one and
#	truncate to calculate the number of digits.
#
#	The optional %which contains information about which primes to
#	load (e.g., {where -> 'comment is NULL', orderby -> 'log10'}.
#
#	If $AdjustLinks is true, then adjusts hidden links (see ...)
#
#   &ProcessPrimes(\&Function,\%which,$AdjustLinks)
#
#	Goes through the list of primes and and calls &Function for
#	each prime's hash array (see above).  Ends when the function
#	returns not true.  (Example: see the code for &Load below.)
#	Returns the number of primes processed.
#
#   &ShowHTML(\%Prime,\%Options)  Show the prime as a table row.
#       Call with 'head' (or 'tail')  as the first parameter to
#       start/stop the table (so call with 'head' first).  When calling
#       with 'head' set, we also can include the 'renumber'  to
#       renumber the ranks, 'no rank' to omit, or 'id' to list
#       id's instead of ranks; also 'comment' to rename the
#       'comment' column.    { 'description' => \&MakePretty }
#       gives a routine to use to format the description.  Can
#       also do this with the comment, discoverer and digits.  The option
#       'add links' adds links from the comments to the glossary.
#
#   &ShowText(\%Prime,\%Options)  Return number as a text string
#       Let the second parameter be { 'add links' => 1} if you
#       want comments linked to the glossary and codes linked.
#       Use 'wrap' => page_width to remove "<rel,notes/13466917/,notes>"
#       from comments and wrap the lines.
#
#   &ShowData(\%Prime,\%Options) Return the number as a
#       single line in the same format used by all.dat.
#       Call with 'head' (or 'tail')  as the first parameter to
#       to print a date comment or 'End.' respectively.  When calling
#       with 'head' set, we also can include the 'renumber'  to
#       renumber the ranks or 'id' to uses id's instead of ranks.
#
#   &MakePretty($String) changes ^ to sup (e.g., 2^(3+4) ->
#       2<sup>3+4</sup>)...
#
#   &LinkDigits($digits,$description) returns the number of digits
#      (the first parameter) linked to a file containing the
#      digits (if any).
#
#  Also
#	&LoadBlob

my $PageWidth = 78;	# When using wrap mode in &showtext(), this is the
	   		# page width.  Must be at least ???
local %UsedCodes;	# Will store a list of the codes actually used
			# keys are codes, value is number of times used (1, 2, ..)
			# Global to PopPrime

sub LinkDigits {
  # Currently only links Mersennes
  my $LinkBase1 = 'http://www.isthe.com/chongo/tech/math/prime/mersenne.html#M';
  my $digits = shift or die "LinkDigits not passed digits";
  my $description = shift or die "LinkDigits not passed prime description";
  if ($description =~ s/^2\^(\d+)-1$/$LinkBase1$1/) {
    $digits = '<a href="'.$description.'">'.$digits.'</a>';
  }
  $digits
}

sub MakePretty {
  $string = shift || die "No string passed to MakePretty";
  # use <sup> for integer exponents
  $string =~ s/([^\^]+)\^(\d+)$/$1<sup>$2<\/sup>/go;
  $string =~ s/([^\^]+)\^(\d+)([\+\-\*\/\)\]])/$1<sup>$2<\/sup>$3/go;
  # change multiplication symbols
  $string =~ s/\*/\&middot;/go;
  # change +/- to &plusmn;  (ISO-Latin 1 #177)
  $string =~ s/\+\/\-/\&plusmn;/go;
  # Let the second term in binary ! be a subscript
  $string =~ s/(\d+)\!(\d+)/$1!<sub>$2<\/sub>/go;
  # Add a space after commas
  $string =~ s/,/, /go;
  # The very very long integers need to be split.
  $string =~ s/(\d+)(\d{50})/$1<br> $2/;
  $string =~ s/(\d+)(\d{50})/$1<br> $2/;
  $string;
}

# Uses global $HTMLReRank (rank for next prime if we want to renumber
# the ranks) and $MakePretty (make primes pretty).

sub ShowHTML {
  # Uses globals $HTMLIdRank, $HTMLReRank

  my $p = shift or return 'no prime to show.';
  local $options = shift || {};  # Allows an array of options to be set

  my $temp;
  if ($p eq 'head') {
    $HTMLReRank = (defined $$options{'renumber'} ? 1 : 0);
    $HTMLIdRank = (defined $$options{'id'} ? 1 : 0);
    $temp = "<table class=\"td2 mx-auto table-hover\">\n <thead>\n  <tr class=\"blue lighten-4\">\n";
    $temp .= ($HTMLIdRank ? '   <th>id</th>' : '   <th>rank</th>') unless (defined $$options{'no rank'});
    $temp .= "\n   <th>prime</th>\n   <th class=\"text-center\">digits</th>\n   <th>who</th>\n   <th>when</th>";
    $temp .= "\n   <th>".($$options{'comment'} || 'comment')."</th>\n  </tr>\n </thead>\n";
    $temp .= " <tbody>\n";
    return $temp;
  } elsif ($p eq 'tail') {
    return ' <tr class="blue lighten-4">'.
      ('<td>&nbsp;</td>'x(defined $$options{'no rank'} ? 5 : 6))."  </tr>\n </tbody>\n</table>";
  }

  my $out = "  <tr>";  # Space to make HTML more readable

  # A couple \n's tossed in below because pico was causing linewrap problems

  # The rank
  unless (exists $$options{'no rank'}) {
    $temp = ($HTMLIdRank ? $$p{'id'} : ($HTMLReRank ? $HTMLReRank++ : $$p{'rank'}));
#   Is style=rank used anywhere?  See he note below about style=...
#   $out .= "\n   <td class=\"text-center\" title=\"$$p{'rank'}\" style=\"rank\"><a href=\"/primes/page.php?id=$$p{'id'}\">$temp</a></td>";
    $out .= "\n   <td class=\"text-center\" title=\"$$p{'rank'}\"><a href=\"/primes/page.php?id=$$p{'id'}\">$temp</a></td>";
  }

  # The description
  if (exists $$options{'description'}) {
    $temp = &{$$options{'description'}}($$p{'description'});
  } else {
    $temp = $$p{'description'};
  }
  $out .= "\n   <td class=\"test-right\"><b>$temp</b></td>";

  # The digits
  if (exists $$options{'digits'}) {
    $temp = &{$$options{'digits'}}($$p{'digits'},$$p{'description'});
  } else {
    $temp = $$p{'digits'};
  }

  $out .= "\n   <td class=\"text-right\">$temp</td>";
  # Can be a long field if URL linked!

  # The discoverers
  if (defined $$options{'discoverer'}) {
    $temp = &{$$options{'discoverer'}}($$p{'discoverer'});
  } else {
    $temp = '<a href="/bios/code.php?code='.$$p{'discoverer'}."\">$$p{'discoverer'}</a>";
  }
  $out .= "\n   <td>".$temp."</td>";

  # The Date
  $out .= "\n   <td>$$p{'year'}</td>";

  # The comment
  $temp = $$p{'comment'};
  if (defined $$options{'comment'}) {
    $temp = &{$$options{'comment'}}($$p{'comment'});
  }
  if (exists $$options{'add links'}) {
    $temp = &Modify::Modify($temp) if exists $$options{'add links'};
  }
  $out .= "\n   <td>".$temp."</td>\n  </tr>\n";
}

sub ShowText {
  # Uses globals : $TextRerank, $TextIdRank
  # Linewrapping is a pain!

  my $p = shift or return 'no prime to show.';
  local $options = shift || {};  # Allows an array of options to be set

  # wrap can be used to set the page width.  Default is 78.
  $PageWidth = $$options{'wrap'} if (defined $$options{'wrap'} and $$options{'wrap'} > 55);

  if ($p eq 'head') {
    $TextRerank = (defined $$options{'renumber'} ? 1 : 0);
    $TextIdRank = (defined $$options{'id'} ? 1 : 0 );
    my $out =  "-----  ".('-'x31)." -------- ----- ---- --------------"
	 . sprintf("\n%5s  %-31s %s",($TextIdRank ? 'id' : 'rank'),
		'description',"digits   who   year comment")
	 . "\n-----  ".('-'x31)." -------- ----- ---- --------------\n";
    return $out;

  } elsif ($p eq 'tail') {
     return "-----  ".('-'x31)." -------- ----- ---- --------------\n";
  }

  my $comment = $$p{'comment'}||'';	# So we can modify them if desired
  my $disc = $$p{'discoverer'}||'';

  # Start by printing rank/id (and possible a date 'letter' a= htis month, b= last...)
  # I tried to get rid of these letter but several folks balked

  my $letter = ' ';
  if (exists $$options{'dateletter'}) {
    # add the letter a after rank of the current month's primes...
    if (`date +%D` =~ /^(\d\d)\/\d\d\/(\d\d)$/o) {
      my $c_year = $2; my $c_month = $1;
      if ($$p{'flags'} =~ /^(19|20)(\d\d)(\d\d)/o) {
	$ago = ($c_year - $2)*12 + $c_month-$3; # Negative for 1999's...
        if ($ago < 6 and $ago >= 0) {
	  if ($ago == 0) { $letter = 'a'};
	  if ($ago == 1) { $letter = 'b'};
	  if ($ago == 2) { $letter = 'c'};
	  if ($ago == 3) { $letter = 'd'};
	  if ($ago == 4) { $letter = 'e'};
	  if ($ago == 5) { $letter = 'f'};
        }
      }
    } else {
      die "failed to read current system date";
    }
  }

  # Might be using this text output on web server (search page...)

  if (exists $$options{'add links'}) {	# Modify?
    # largest.html, for example, sets the base on the other machine
    $disc = '<a href="/bios/code.php?code="'.$$p{'discoverer'}."\">$$p{'discoverer'}</a>";
    $comment = &Modify::Modify($comment);
  }

  # The option id might be set for a single headless prime (e.g., error message)
  my $rank = ( ($TextIdRank or $$options{'id'}) ? $$p{'id'} :
	($TextRerank ? $TextRerank++ : $$p{'rank'}) );

  my $out = sprintf "%5d%1s ", $rank, $letter;
  # Note: we have used up 7 columns of out page so far.


  # Now, lets print the description; it should be padded to the 39th column
  # So short one (at most 39-7=32 characters) can just be print, longer might
  # be wrapped.  First though, check for HTML entities

  if ($$p{'description'} =~ s/&(\w+);/$1/g) {
    if ($1 ne 'tau') { print "unrecognized text entitity \&$1; in prime with id=$$print{'id'} (near line 263)\n"; }
  }

  if (length($$p{'description'}) > 32 and (exists $$options{'wrap'})) {
    if (length($$p{'description'}) <= $PageWidth - 7) {
      $out .= $$p{'description'}."\n       ";
    } else {
      my $temp = $$p{'description'};
      my $width= $PageWidth - 8; # 7 used, need one for backquote
      while ($temp =~ /^(.{1,$width})/o) {
        $temp = $';
        $out .= $1;
        $out .= "\\" if $temp;  # Not done, so backquote
        $out .= "\n       ";
      }
    }
    $out .= ' 'x32;
  } else {
    $out .= sprintf "%-32s", $$p{'description'};
  }

  # Print the digits, discoverer and year (columns 40 through 58, blank in 59)
  $out .= sprintf "%8d %-5s %4d ", $$p{'digits'}, $disc, $$p{'year'};

  # Finally, print the comment in columns 59 (a blank) through $PageWidth

  # If wrapping, prepare by removing the links as well, because wrapping
  # surely means printing on paper...

  if (exists $$options{'wrap'}) {
    $comment =~ s/<(url|rel|ref),.*?>//go;
  }

  if ((exists $$options{'wrap'}) and length($comment) >= $PageWidth - 59) {
   my $indent = 10;  # How far do we indent?
   if (length($comment) <= $PageWidth - $indent) {
      $out .= "\n".' 'x$indent.$comment;
    } else {
      my $width= $PageWidth - $indent;
      while ($comment =~ /^(.{1,$width})(\s|$)/o) {
        $comment = $';
        $out .= "\n".' 'x$indent.$1;
      }
    }
    $out .= "\n";
  } else {
    $out .= "$comment\n";
  }

  $out;
}

sub ShowData {
  # Uses globals $DataRerank, $DataIdRank

  # Return the prime formatted correctly for the data file.
  # The 'head' has one option: rerank (to rerank the numbers)
  my $p = shift or die 'no prime to show.';
  local $options = shift || {};  # Allows an array of options to be set

  if ($p eq 'head') {
    $DataRerank = (defined $$options{'rerank'} ? 1 : 0);	# set global flag
    $DataIdRank = (defined $$options{'id'} ? 1 : 0);		# set global flag
    return "# Written on ".`date`;
  } elsif ($p eq 'tail') {
    return "# End.\n";
  }

  # Adjust rank if necessary
  my $rank = ($DataIdRank ? $$p{'id'} : ($DataRerank ? $DataRerank++ : $$p{'rank'}));
  (my $year = $$p{'year'}) =~ s/\d\d(\d\d)/$1/o;		# Need a two-digit year
  my $desc = ($$p{'quoted'} ? "\"$$p{'description'}\"" : $$p{'description'});	# quoted?
  my $comm = ($$p{'comment'} ? $$p{'comment'}.' ' : '');
  my $digits = $$p{'log_10'}+1;
  if (int($digits) > $$p{'digits'}) { $digits -= 0.000001; }
  my $flags = ($$p{flags} ? "#$$p{flags}" : '');
  # Now lets print it out!
  return "$rank $desc $digits $$p{'discoverer'} $year $comm$flags\n";
}


sub ShowLaTex {
  # Uses globals $DataRerank, $DataIdRank, $ShowLatexTail
  # WARNING: ASSUMES APPROPRIATE FILE HEADER AND MULTIPLE PACKAGES (fullpage, array, ragged2e, longtable...)
  # Return the prime formatted correctly for the data file.
  # The 'head' has one option: rerank (to rerank the numbers)
  my $p = shift or die 'no prime to show.';
  local $options = shift || {};  # Allows an array of options to be set

  if ($p eq 'head') {
    $DataRerank = (defined $$options{'rerank'} ? 1 : 0);	# set global flag
    $DataIdRank = (defined $$options{'id'} ? 1 : 0);		# set global flag
    $ShowLatexTail = '';	# Build a gloab list fo primes that are too long here
    return '\setlength{\tabcolsep}{3pt}'."\n".'\renewcommand{\arraystretch}{1.05}'."\n".
	'\begin{longtable}{r>{\RaggedRight}p{2.5in}rrc>{\RaggedRight}p{2 in}}'."\n".'\hline'."\n".
	'rank & description & digits & who & year & comment \\\\'."\n".'\hline'."\n".'\noalign{\vskip 3pt}'."\n".'\endhead'.
	"\n".'\hline'."\n".'\endfoot'."\n";
  } elsif ($p eq 'tail') {
    $ShowLatexTail = '\setlength{\tabcolsep}{6pt}'."\n\\section{The Long Primes}\n\nThese are the
	primes that were too long to fit above.\n\n\n\\raggedright\n$ShowLatexTail"
	if ($ShowLatexTail);
    return "\\end{longtable}\n $ShowLatexTail\n";
  }

  # Adjust rank if necessary
  my $rank = ($DataIdRank ? $$p{'id'} : ($DataRerank ? $DataRerank++ : $$p{'rank'}));
  (my $year = $$p{'year'}) =~ s/\d\d(\d\d)/$1/o;		# Need a two-digit year

  # The description must be handled carefully
  my $desc = $$p{'description'};
  if ($$p{'quoted'}) {
     $desc =  "\"$desc\"";
     if ($desc =~ s/&(\w+);/\\$1/g) {
       if ($1 ne 'tau') { print "unrecognized HTML entitity \&$1; in prime with id=$$print{'id'} (near line 380)\n"; }
     }
  }
  my $length = length($desc);

  # # Protect LaTex specials # $ % & ~ _ ^ \ { }
  $desc =~ s/\^(\d+)/^{$1}/go;
  if ($desc =~ /([\$\%\&~_])/) { print "warning: unprotected $1 in description of primes with id $$prime{'id'}: $desc\n"; }
  $desc =~ s/(#)/\\$1/go;

  # Pretty print
  $desc =~ s/\*/ \\cdot /go;
  # Let the second term in binary ! be a subscript
  $desc =~ s/(\d+)\!(\d+)/$1!_{$2}/go;

  # The very very long integers need to be split by adding '\backslash \\' to the LaTex file.
  $desc =~ s/(\d+)(\d{65})/$1\\backslash \\\\$2/go;

  # hyperlink{label}{text} AND \hypertarget{label}{target}

  if ($length > 40) {
    $ShowLatexTail .= "Prime with rank \\hypertarget{p$rank}{$rank} ($$p{'digits'} digits by $$p{'discoverer'})\n";
    if ($desc =~/^"?(\d+)(...\(\d+ other digits\)...)(\d+)"?$/o) {
      $ShowLatexTail .= "See \\href{https:\/\/t5k.org\/primes\/page.php?id=$$p{'id'}}{on-line version}
	for the rest of the digits\n"."\\begin{quote}\n``\$$1\$$2\$$3\$''\n\\end{quote}\n";
    } elsif ($desc =~/\\\\/) {
      $ShowLatexTail .= "\\begin{multline*}\n$desc\n\\end{multline*}\n";
    } else {
      $ShowLatexTail .=  "\\begin{quote}\n\$$desc\$\n\\end{quote}\n"
    }
    $desc = "[ Long prime \\hyperlink{p$rank}{$rank} ]";
  } else {
    $desc = "\$$desc\$";
  }

  # Protect LaTex specials in discoverer # $ % & ~ _ ^ \ { }
  my $disc = $$p{'discoverer'};

  my $comm = ($$p{'comment'} ? $$p{'comment'}.' ' : '');
  # Protect LaTex specials # $ % & ~ \ { } (not ^, _ which are only in equations)
  $comm =~ s/(#|\$|&|~|%|\{|\})/\\$1/go;

  # Now my <url,,>... comments, these might have math ^ in them!
  $comm =~ s/<url,(.*?),(.*?)>/
	my $url = $1; my $text = $2;
	$text =~ s#(\^)#\\$1\{\}#go if ($text =~ m#(^|\s)(.*?\^.*?)(\s|$)#o);
	"\\href{$url}{$text}"
  /gioe;
  $comm =~ s/<rel,(.*?),(.*?)>/(\\href{https:\/\/t5k.org\/$1}{$2})/gio;
  $comm =~ s/<ref,(.*?)>/(\\href{https:\/\/t5k.org\/references\/refs.cgi\/$1}{$1})/gio;

  # Are there math terms in the comment?
  $comm =~ s/(\S*\d+\S*)/
    my $temp = $1;
    ($temp =~ m#href#o or $temp =~ m#\[\w{2,4}\]#o) ? $temp : '$'.$temp.'$';
  /ego;
  $comm =~ s/\^(\d+)/^{$1}/go;
  $comm =~ s/\*/ \\cdot /go;

  # Now lets print it out!
  return "$rank & $desc &  $$p{'digits'} & $disc & $year & $comm \\\\\n";
}

sub ProcessPrimes {
  local $Function = shift || 
    die 'ProcessPrimes not given function reference';

  my $which = shift || {};
  my $fields  = ($$which{'fields'}  ? "$$which{fields}"     : '*');
  my $where   = ($$which{'where'}   ? "WHERE $$which{where}"      : '');
  my $list    = ($$which{'list'}    ? $$which{'list'}  : 'Top 5000');
  $where .= ($where ? ' AND ' : 'WHERE ')."list='$list'"; 

  my $table   = ($$which{'table'}   ? 'deleted' : 'prime');
  my $orderby = ($$which{'orderby'} ? "ORDER BY $$which{orderby}" : "ORDER BY $table.rank");
  my $limit   = ($$which{'limit'}   ? "LIMIT $$which{limit}"      : '');
# my $having  = ($$which{'having'}  ? "HAVING $$which{having}"    : '');

  $AdjustLinks = (shift() ? 1 : 0 );  # Global!
  local $Count = 0; # Gloabal to count primes
  local $Line = 0;  # Global used in &PopPrime to state location of errors

  # open database
  my $dbh = &connect_db::connect; #xxx
  my $query = "SELECT $fields, submitted+0 as date_stamp FROM $table $where $orderby $limit";
####### print "LoadPrimes.pm line 468:".$query."\n\n";
  $sth = $dbh->prepare($query) || die $sth->errstr;

  $sth->execute() or die $sth->errstr;  # Wow!  Got them all!
  # warn "Database loaded, now processing\n"; 
  while (my $p = $sth->fetchrow_hashref) {
    $_ = &PopPrime($p);
    ++$Count;
    # print &ShowText($_);
    &{$Function}($_) or return $Count;
  }
  $Count;
}

sub Load {
  $Out = shift || die 'LoadPrimes passed no array reference.'; # Global to subroutine
  my $which = shift || undef;
  my $AdjustLinks = (shift() ? 1 : 0 );
  &ProcessPrimes (sub { push @$Out,$_; },$which,$AdjustLinks);
}

#### Internal ####

# These should be the only routines that knows the format of the prime
# list!  The first reformats (when desired) the hidden links imbedded
# with angle brackets within the desc and comment.  Format for these
# links:
#
#    <ref,reference (from refs.cgi),optional additional unlinked text>
#    <rel,relative reference (from prime pages root),optional link text>
#    <url,url (rel or absolute,optional link text>
#
# Uses global $AdjustLinks to decide if to adjust (or just delete these)

sub AdjustHiddenLinks {       #  Either adjusts or deletes hidden links
  my $Text = shift || return '';
  $Text =~ /</o || return $Text;   # Return unless < in comment 
  if ($AdjustLinks) {
    $Text =~ s#(.*?)<ref,([^,]*),?(.*?)>([^<]*)#
	"$1\[<a href=\"/references/refs.cgi\/$2\">$2</a>" .
		($3 ? ", $3" : '')."\]$4"#goie;
    $Text =~ s#(.*?)<rel,/?([^,]*),?(.*?)>([^<]*)#
	"$1\(<a href=\"/$2\">".($3||$2)."</a>\)$4"#goie;
    $Text =~ s#(.*?)<url,([^,]*),?(.*?)>([^<]*)#
	"$1\(<a href=\"$2\">".($3||$2)."</a>\)$4"#goie;
  } else {
    $Text =~ s/(.*?)<.*?>([^<]*)/$1$2/go;
  }
  $Text;
}

sub PopPrime {   # grab one prime from the list
  my $p = shift;

  if (defined($$p{'submitted'})) { 
    # Submitted may be omitted from the fields in %which when called
    ($Year) = ($$p{'submitted'} =~ /^(\d\d\d\d)/o);
  }
  my $Flags  = $$p{'date_stamp'};
  $Flags =~ s/\.0+$//;	# Remove decimal point and trailing zeros
  my $Quoted = ($$p{'description'} =~ /^"(.*)"\s*\[?[\]]*\]?\s*$/o);
  $$p{'description'} = $1 if $Quoted;
  my $comment = $$p{'comment'}||'';
  # Update global list of prover-codes for printing in tail (move?)
  if (defined($$p{'credit'})) {
    # Credit may be omitted from the fields in %which when called
    if (defined $UsedCodes{$$p{'credit'}}) {
       $UsedCodes{$$p{'credit'}}++;
    } else {
       $UsedCodes{$$p{'credit'}} = 1;
       # print $$p{'credit'};
    }
  }
  return {'rank'=>$$p{'rank'}||99999, 'description'=>$$p{'description'},
     'digits'=>$$p{'digits'}, 'credit'=>$$p{'credit'}, 'submitted'=>$$p{'submitted'},
     'discoverer'=>$$p{'credit'}, 'year'=>$Year, 'comment'=>$comment, 'blob_id'=>$$p{'blob_id'}||0,
     'flags'=>$Flags, 'quoted' => $Quoted, 'log_10' => $$p{'log10'},
     # This is for database access by other programs
     'id'=>$$p{'id'}};
}

# &LoadBlob($description,$id)  (Need one of the two, perferably the second!)

sub LoadBlob {
  my $desc = shift;
  my $id = shift;
  my $dbh = &connect_db::connect();	# Must be before the call to quote!

  my $query;
  if ($id) {
    $query = "SELECT * FROM prime_blob WHERE id='$id'";
  } elsif (defined($desc) and $desc) {
    $desc = $dbh->quote($desc);
    $query = "SELECT * FROM prime_blob WHERE description LIKE $desc LIMIT 1";
  } else {
    die "\&LoadBlob(\$desc,\$id) must be passed either a description or an id to match.\n";
  }

  my $sth = $dbh->prepare($query) || die $sth->errstr;
  $sth->execute() || die $sth->errstr;
  my $p = $sth->fetchrow_hashref;
  $sth->finish();

  unless (defined($p)) {
    warn "No prime blob matching $desc found.\n";
    return undef;
  }

  return {'text'=>$$p{'text'},'description'=>$$p{'description'},
     'digits'=>$$p{'digits'}, 'log_10' => $$p{'log10'},
     'full_digit' => $$p{'full_digit'}, 'person_id' => $$p{'person_id'},
     # This is for database access by other programs
     'id'=>$$p{'id'}};
}


# Initialize

$AdjustLinks = 0;

1;
