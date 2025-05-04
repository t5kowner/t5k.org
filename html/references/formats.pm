package formats;

# Every BibTeX type has a format hash array (e.g., %book, %article...)
# in addition to these there is %Default which defines defaults for all the 
# others and serves for all unknown types (with an error message of course)
#
# The format hash arrays have keys = the usual BibTeX fields (e.g. date
# author ('and' delimited)...) and values which are also has arrays
# with keys and values as follows:
#
#   Transform	a subroutine address to call on the entry (e.g. \&AdjustTitle
#			or \&Ignore or sub { my $item = shift; uc($item) }
#			default is \&strip (includes TeX washing)
#   PreText 	text to be prepended to this field (e.g., <i>)
#			default is ' '
#   PostText	text to be prepended to this field (e.g., <i>)
#   sequence 	an integer, the lowest are printed first...
#   squawk	a string to be printed to the error stream (e.g., contains comment)
#			(actually any unknown key, e.g., squawk will generate
#			an error message with its name and value)
#   separate    when true for a field f (e.g. abstract), stored as a seperate
#                       entry in the db (named "k::BibTeX key")  Note that both
#                       refs.cgi and ReadBibHTML must expect these!
#
# There is one more key in the format hash array called 'All_Items' which
# may specify PreText (for the whole bibentry), PostText and Transform
# (called before any fields are printed).  Its sequence number is usually 0, but
# is ignored--it will be used first (and last in the case of 
# $Format{'All_Items'}->{'PostText'})

# Note most items are formated as HTML, ready to print, but the names
# are stored <<full name::lastname>> so that we may (or may not) later
# link to last name for author search.

use FindBin qw($Bin);  # For portability when mirroring...
use lib "$Bin/../glossary/db";        # Contains Modify
# Modify removed!!!!
# my $LinksDB = '../glossary/db/links'; # For Modify

use washer;  # Does the TeX -> HTML conversion
use Carp;    # Allows better error messages
# use Modify;  # Add links to glossary--just used in 'annote'

# This hash is used in Default->All_Items->Transform to make a date flag

%Months = ( 'January' => '.01', 'February' => '.02', 'March' => '.03',
  'April' => '.04', 'May' => '.05', 'June' => '.06', 'July' => '.07',
  'August' => '.08', 'September' => '.09', 'October' => '.10', 
  'November' => '.11', 'December' => '.12', 'Sept-Oct' => '.095');
# Those after December are for exceptions in primes.bib
 
# Okay--off to work

%Default = (
  'All_Items' => {
        'PreText'  => '',
        'sequence' => 0,
     	'Transform' => sub { my $item = shift; 
                # Adjust dates: combine month into year, and also form a
                # new entry dateflag to use when sorting 
 
		my $dateflag = '';
                if (exists $$item{'year'}) {
                  ($dateflag) = ($$item{'year'} =~ /(\d+).*?$/o);
                } elsif (defined $main::name) { 
                  # If no year, try to get one from the item name
                  ($dateflag) = ($main::name =~ /(\d+).*?$/o);
		}
                unless ($dateflag) {
                  $dateflag = '0000';
                  &main::my_warn('No clue as to the date (using 0000)');
                }

		# Note the assumption all two digit dates are in the 1900's
  		$dateflag = "19$dateflag" if length($dateflag)==2;

     		if (exists $$item{'month'}) {  
		  # Month abbrv already translated when read in
		  defined $Months{$$item{'month'}} or 
			&main::my_warn('Unknown month:'.$$item{'month'});
                  $dateflag += $Months{$$item{'month'}}||0; 
        	  $$item{'year'} = (delete $$item{'month'}).
                    (exists $$item{'year'} ? " $$item{'year'}" : '');
     		}
  
                $$item{'dateflag'} = $dateflag;

		# Next for Books with editors, not authors--assume <B> is on!
      		if (exists $$item{'editor'} and not $$item{'author'}) {
		  $$item{'author'} = $$item{'editor'}.'</b> editor'.
                    (((delete $$item{'editor'}) =~ / and /i) ? 's' : '').'<b>';
		}
	}
    },
  'omit' => { # Used to ignore entries by default, e.g.,
              # my non-prime papers.  Can be over-ridden in &ReadBibHTML
	'separate' => 1     # Store as a seperate entry in db--ref.cgi must know!
	},  
  'author' => { 'required' => 1,
	'Transform' => \&BoldNameList,
        'PostText' => ',',
        'sequence' => 1,
    },
  'mrnumber' => { # Link to MR Reviews 
        'Transform' => sub { my $num = shift;
	   $num =~ s/ \\?\#/:/o;
	   # MatSciNet sometimes lists old and new forms; these start with the new MR\d+
	   $num =~ s/ .*$// if $num =~ /^MR/;
	   $num =~ s/ \(.*\)$// if $num =~ /^\d* \(/;
	   # Blanks in the number cause malformed URL's (effect Link Checker)
	   (my $link = $num) =~ s/ /%20/go;
  	   my $temp = ($num =~ /^MR/ ? '' : 'MR '); # Why repeat 'MR' if it is in the number?
	   return "<b><a href=\"http://www.ams.org/mathscinet-getitem?mr=$link\">$temp$num</a></b>"
	},
	'PreText' => ' ',
        'sequence' => 95
  },
  'issn' => { 'Transform' => \&Ignore },  # Might be useful someday

  'mrclass' 	=> { 'Transform' => \&Ignore },  # So can paste from MatSciNet
  'fjournal'	=> { 'Transform' => \&Ignore },  # "
  'coden' 	=> { 'Transform' => \&Ignore },  # "
  'mrreviewer' 	=> { 'Transform' => \&Ignore },  # "
  'doi'         => { 'Transform' => \&Ignore },  # "

  'title' => { 'required' => 1,
        'Transform' => \&AdjustTitle,
	'PreText' => ' "',
	'PostText' => ',"',
        'sequence' => 5,
    },
  'journal' => {   # Mainly used in ARTICLE, but also UNPUBLISHED
	'PreText' => ' <I>',
	'PostText' => '</I>,',
        'sequence' => 6,
    },
  'type' => {   # *THESIS only?
        'PostText' => ',',
        'sequence' => 11,
    },
  'volume' => { 
        'PreText' => ' <B>',
        'PostText' => '</B>',
        'sequence' => 15,
    },
  'number' => { 
        'PreText' => ':',
        'sequence' => 16,
    },
  'school' => {   # *THESIS only?
        'PostText' => ',',
        'sequence' => 17,
    },
  'howpublished' => {   # Misc and Booklet only?
	'sequence' => 18, 
    },
  'address' => { 
        'PostText' => ',',
        'sequence' => 19,
    },
  'year' => { 
        'PreText'  => ' (',
        'PostText' => ')',        
        'sequence' => 20,
    },
  'pages' => {  
	'sequence' => 24, 
   	'PostText' => '.&nbsp; '},

  # Citation proper ends--now comments...

  'note' => {
        'PreText'  => ' ',
        'PostText' => '.&nbsp; ',
        'sequence' => 25,
    },
  'comment' => {        # Remove?
        'PreText'  => ' <font color=red>(',
        'PostText' => ')</font>&nbsp; ',
        'squawk'   => ' contains comment ',
        'sequence' => 26,
    },
  'annote' => {
	'Transform' => sub { my $item = shift; 
#	    $item = &Modify::Modify(&strip($item),$LinksDB); # Add links  to glossary
	    $item = &strip($item);
	},
        'PreText'  => ' <span style="color: #000033;">[',
        'PostText' => ']</span>',
        'sequence' => 27,
	'separate' => 1     # Store as a seperate entry in db? ref.cgi must know!
    },
  'key' => { 'Transform' => \&Ignore },
  'pp_keywords' => { 'Transform' => \&Ignore },
  'crossref' => {
	'squawk' => 'using crossreference--has an odd BibTeX meaning!',
        'PreText' => ' See also ',
        'PostText' => '.&nbsp;',
        'Transform' => sub { my $out = shift || ''; 
           $out =~ s/^\s*//o;
           $out =~ s/\s*$//o;
           my @refs = split('\s*,\s*',$out);
           $out = '';
           foreach (@refs) { $out .= "<a href=\"#$_\">$_</a>, " }
           chop($out);chop($out);
           $out;
        }
    },
  'url' => { 
        'Transform' => sub { my $url = shift || ''; 
           "<a href=\"$url\">$url</a>"; 
        },
        'PreText' => ' (',
        'PostText' => ')',
        'sequence' => 30,
    },
  'abstract' => {
 	'PreText' => " <blockquote><b>Abstract:</b>\n",
 	'PostText' => "\n</blockquote>\n",
        'sequence' => 90,
	'separate' => 1     # Store as a seperate entry in db? ref.cgi must know!
   },
);

%ARTICLES = (
  'series' => {
	'PreText' => ' series ',
	'PostText' => ',',
        'sequence' => 7
    },
  'journal' => { 
 	'required' => 1,
        'PreText' => ' <I>',
        'PostText' => '</I>,',
        'sequence' => 6
    }
);

%BOOKS = (  
  'title' => { 
	'required' => 1,
        'Transform' => \&AdjustTitle,
	'PreText' => ' <I>',
	'PostText' => '</I>,',
        'sequence' => 5,
    },
  'chapter' => { 
	'sequence' => 4,  # For INBOOK only ?
	'PreText' => '"',
	'PostText' => '" in ',
    },
  'booktitle' => { 
	'sequence' => 6,  # For INCOLLECTION (and INPROCEEDINGS?) only, do not adjust caps
	'Transform' => sub { my $name = shift;   # remove {} protecting caps, as not altered
           $name =~ s/\{(\w+)\}/$1/;
           $name;
        },
	'PreText' => ' In "',
	'PostText' => ',"',
    },
  'organization' => { 
	'sequence' => 6.5,  # For INPROCEEDINGS only, do not adjust caps
	'PostText' => ',',
    },
  'editor' => { 
        'sequence' => 7,  # For INCOLLECTION only ??
	'Transform' => sub { my $names = shift;
           my $out = &NameList($names).' editor';
           $out .= 's' if $names =~ / and /i;
           $out;
        },
	'PreText' => ' ',
	'PostText' => ',',
    },
  'edition' => { 
        'PostText' => ' edition,',
        'sequence' => 10,
    },
  'series' => { 
        'sequence' => 14,
    },
  'volume' => { 
        'PreText' => ' Vol, ',
        'PostText' => ',',
        'sequence' => 15,
    },
  'publisher' => { 
        'PostText' => ', ',
        'sequence' => 17,
    },
  'pages' => {  
	'sequence' => 20, 
   	'PreText' => ' pp. ',
   	'PostText' => ', '
    },
  'year' => { 'required' => 1,
        'sequence' => 19,
        'PostText' => '.&nbsp; ',
    },
  'isbn' => {
	'PostText' => '. ',
 	'PreText' => ' ISBN ',
        'sequence' => 21,
    },
);

%TECHREPORTS = (
  'number' => {       # Techreport only ? No--also in article
     	'PreText' => ' technical report ',
   	'sequence' => 7,
    },
  'institution' => {  # Same place the school would be in thesis
        'PostText' => ',',
        'sequence' => 17,
    },
);

%PHDTHESISS = (
  'title' => { 'required' => 0,
        'Transform' => \&AdjustTitle,
	'PreText' => ' "',
	'PostText' => '," Ph.D. thesis, ',
        'sequence' => 5,
    },  
);

%MASTERSTHESISS = (
  'title' => { 'required' => 0,
        'Transform' => \&AdjustTitle,
	'PreText' => ' "',
	'PostText' => '," Master\'s thesis, ',
        'sequence' => 5,
    },  
);

%INCOLLECTIONS = (
  'title' => { 'required' => 1,
        'Transform' => \&AdjustTitle,
	'PreText' => ' <I>',
	'PostText' => '</I>.&nbsp;',
        'sequence' => 5,
    },
);

#####  Add the defaults to each type...  ######

foreach (keys %Default) {
  $BOOKS{$_} = $Default{$_}    unless $BOOKS{$_};
  $BOOKLETS{$_} = $BOOKS{$_}  unless $BOOKLETSS{$_};
  $ARTICLES{$_} = $Default{$_} unless $ARTICLES{$_};
  $TECHREPORTS{$_} = $Default{$_} unless $TECHREPORTS{$_};
  $PHDTHESISS{$_} = $Default{$_} unless $PHDTHESISS{$_};
  $MASTERSTHESISS{$_} = $Default{$_} unless $MASTERSTHESISS{$_};
}

### (needs work on item ordering)

foreach (keys %BOOKS) {
  $INCOLLECTIONS{$_} = $BOOKS{$_} unless $INCOLLECTIONS{$_};
}

%INPROCEEDINGSS = %INCOLLECTIONS; 
%INBOOKS        = %INPROCEEDINGSS;
%MISCS          = %Default;
%UNPUBLISHEDS   = %MISCS;

# Avoid error messages:

%UNPUBLISHEDS = %UNPUBLISHEDS;
%INBOOKS = %INBOOKS;

#####  Routines #######

sub Ignore {
  return '';
}

sub strip {
  my $string = shift;

  $string =~ s/\s+/ /gos;  # remove multiple white space, makes one line

  if (defined $string) {
    return $string if ($string =~ /^\d+$/o);
    $string = &washer::wash($string);
    if ($string =~ s/\{(\w*?)\}/$1/os) {
      &main::my_warn("Assumed \"{$1}\" protecting caps only");
    }
    $string;
  } else {
    croak('Undefined string!!!');
    '';
  }
}

# Not yet written!

sub AdjustTitle {
  my $title = shift or croak "No title passed to &AdjustTitle";
  my $oldtitle = $title;
  $title = &LowercaseTitle($title);  #  Adjust case of title--first
  # unless ($title eq $oldtitle) {
  #   print $main::name,': ',$title,"\n";
  # }
  $title = &washer::wash($title);    # Must be second 
  &main::my_warn('Non-removed { }\'s after &AdjustTitle') 
    if $title =~ /(\{|\})/o; 
  $title;
}

sub LowercaseTitle {
  my $title = shift or 
    &main::my_warn('no title passed to &LowercaseTitle') and return('');
  $title =~ s/\s+/ /gos;  # make one line, so matches don't need s below
  my $FirstCharAdded = 0;
  my $out = '';
  my ($before,$delimeter);
  while ($title) {  # $title is what is left to process each loop
    ($before,$delimeter,$title) = ($title =~ /^([^\{\$]*)([\{\$]*)(.*)$/os);
    if ($before) {
      $before =~ s/(.*)/\L$1/os;  # Lowercase first part
      unless ($FirstCharAdded) {
        $before =~ s/(.)(.*)/\U$1\L$2/os;
      }
      $out .= $before;
    }
    if ($delimeter) {  # protect environments
      # Is the delimeter { used to protect capitalization?  If so, remove it.
      $remove = ($delimeter eq '{' and $title =~ /^[\w\-\.,']*\}/o);
      if ($delimeter eq '{$') {      # Protecting caps in the math
        $out .= '$'
      } elsif ($delimeter eq '${') { # Opps, not a true delimeter
        $delimeter = '$';
        $title = '{'.$title; # put the { back
        $out .= '$';
      } else {
        $out .= $delimeter unless $remove;
      }

      if ($delimeter eq '{$') {
        $delimeter = '\$}';
      } else {
        $delimeter =~ s/\{/\}/go;
        $delimeter =~ s/\$/\\\$/go;
      }

      $title =~ /^(.*?)$delimeter/ or 
	  &main::my_warn("Expected $delimeter in title");
      $title = $';
      $out .= $1;

      if ($delimeter eq '\$}' or $delimeter eq '\$') {
        $out .= '$' 
      } else {
        $out .= $delimeter unless $remove;
      }
    }
    $FirstCharAdded = 1;
    # print " is: ".($out||'?').'|'.($title||'?')."\n";
  }
  $out;
}


### Do we really need two functions here?

local $PubList; # To store null delimited list of items for each last name
# local $AuthorList; # For each item, list the first author's last name

sub BoldNameList {
  my $names = &strip(shift) or &main::my_warn("empty name list");
  &NameList($names,'<B>','</B>');
}

sub NameList {
  my $names = &strip(shift) or &main::my_warn("empty name list");
  $names =~ s/\{([\w .,&;\-]+)\}/$1/go;  # Remove "protect capitalization" brackets

  my $pre_format = shift || '';
  my $post_format = shift || '';

  $names =~ s/\.\-/. /go;     # Not sure why sometimes have J.-J. 
  my @names = split(/ and /,$names);

  my $name;
  my @newnames; # build a new list with links to author search
  foreach (@names) {   # Work on the name list
    &main::my_warn('item unnamed') unless $main::name;
    $name = &LastName($_);
    if (exists $PubList{$name}) {
      $PubList{$name} .= "\0".$main::name if ($PubList{$name} !~ /\b$main::name\b/);
      # Avoids listing articles twice (happens if duplicate last names for article)
    } else {
      $PubList{$name} = $main::name;
    }
    @newnames = (@newnames,"<<$_".'::'."$name>>");
  }
  @names = @newnames;

  my $last = $pre_format.pop(@names).$post_format;
  return $last unless @names;
# $pre_format.join("$post_format, $pre_format",@names)."$post_format and $last";
  $pre_format.join("$post_format, $pre_format",@names)."$post_format and $last";
}

sub LastName {
  my $name = shift or die "no name";
  my $was = $name;

  # Remove character formatting
  $name =~ s/\&(\w+?)(acute|tilde|circ|uml|ring);/$1/g;
  # Problem K{\vr}{\'i}{\vz}ek  ==> K&#x00159;i&#x17e;ek
  $name =~ s/\&#x00159;/r/g;
  $name =~ s/\&#x17e;/z/g;
  $name =~ s/\&inodot;/i/g;

  # Remove vons' and initials
  $name =~ s/\w+\.,?//go;                  # Initials
  $name =~ s/\b(de|te|van|der|von|Del|Mme)\b//go;  # von's

  # Left over commas at end (commas in middle may make authors 
  # incorrectly delimited via ',' rather than 'and'.  And this
  # comma may be in or out of a cury bracket
  # example: J. L. Selfridge and {Wagstaff, Jr.}, S. S. and Chen, Jing Run
  $name =~ s/,\s*\}?\s*$//go;
  $name =~ s/,\s*\}?\s*$//o;     # Why does twice work
           # but just the first of these fails on BSW89?

  # Editor flag --  fix this?
  $name =~ s/<\/b> editors?<b>//o;

  # Leftover spaces, braces?
  $name =~ s/^\s*\{?\s*//o;
  $name =~ s/\s*\}?\s*$//o;

  # Should be only the last name left, if more or less than one,
  # squawk and take the last.

  (my $lastname) = ($name =~ /([A-Z][\w\-]+)(,|$)/o); 
  if (not $lastname) {
    $lastname = 'UNKNOWN' unless $lastname;
    &main::my_warn("Name error: '$name,' using $lastname (was \"$was\")\n");
  } elsif ($` and $name ne 'Lord Cherwell' and $name ne 'la Vallee Poussin') {
    &main::my_warn("Name warning: '$name,' using $lastname (was \"$was\")\n");
  }
  $lastname;
}

1;
