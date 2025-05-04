package Modify;

use DBI;
use lib '/var/www/library/constants/';
use environment;

# Exportable:
#
#  &Modify(text[,ExpectedURL])	Modifies text to link to glossary. If ExpectedURL is
#				set, will use relative URL's if possible.
#				Note <nolink>text</nolink> protects text from links
#
#  &LoadDatabase([context])	Loads the glossary info into the array @$glossary
#				context is 'glossary' (default), 'curios' or 'both'
#  &ShowEntities()			???????????????????????????
#  &ModifyEntities()			???????????????????????????
#  &ShowLinks([context])	Inherits context from &LoadDatabase (if it was called first)

# Modify(text, [ExpectedURL])  # Leave the second blank for hard links
# otherwise, if you know where it will be called from, will try to use
# relative links to the glossary

my $glossary;

sub LoadDatabase {
  # Load the Database
  my $context = shift || 'glossary';
  my $where = '';
  $where = "WHERE class != 'curios'" if $context eq 'glossary';
  $where = "WHERE class != 'glossary'" if $context eq 'curios';
  my $dsn = "DBI:mysql:glossary:localhost";
  my $dbh = DBI->connect($dsn,'primes_admin',environment::T5K_DB_PRIMES_ADMIN_PASSWORD,{mysql_enable_utf8 => 1, RaiseError => 1});
  my $query = "SELECT tag, sort, sense, class FROM links $where ORDER BY weight DESC";
  $glossary = $dbh->selectall_arrayref($query);
  $dbh->disconnect();
  return @$glossary+0;  # return the number of entries
}

sub ShowLinks {
  &LoadDatabase unless (defined($glossary) and @$glossary > 0);

  foreach (@$glossary) {
    # Grab the row selected -- MUST MATCH $query!!
    my ($tag, $sort, $sense, $class) = @$_;
    $class .= '; case-sensitive' if $sense eq 'yes';
    print "$tag ($class)\n\t$sort\n";
  }
}

sub Modify {
  my $text2modify = shift or return(''); # no text to modify?
  my $ExpectedURL = shift || '';

  # Who is calling effects the type of links.  From glossary the URL's
  # are short (and we do not want to link back to same page!). Need
  # longer URL's from other directories.
  my ($path, $self);
  if ($ExpectedURL =~ /\bglossary\/page.php\/(\w+)\.\w+$/) {
    # must be a call from the glossary directory
    $path = 'page.php?sort=';
    $self = $1;
  } else {
    $path = '/glossary/page.php?sort=';
    $self = '';
  }
  $path = '/glossary/xpage/';


  &LoadDatabase unless (defined($glossary) and @$glossary > 0);
  foreach (@$glossary) {
    # Grab the row selected -- MUST MATCH $query!!
    my ($tag, $sort, $sense, $class) = @$_;

    ### while (my $row = $sth->fetchrow_hashref) {

    $tag =~ s/\s+/\\s+/go;
# print "$tag, $sort, $sense, \n";
    next unless $text2modify =~ /$tag/i; # skip to next if no match

    next if ($sort eq $self); # Why link to self?

    my $url = $path.$sort.'.html';

    my $case = ($sense eq 'yes' ? '' : 'i');

    # I wish we could:
    #
    #    $text2modify =~ s/($tag)/<a href=$url>$1<\/a>/
    #
    # but what if the tag is in a URL or linked href?

    my $tested = '';			# Modified text lands here and is placed
					# back in $text2modify at end of loop
    my $rest = $text2modify;		# Unprocessed is here
    my $start = '';			# $start is the part before a <
					# so we look for matches in start

    while ($rest) {               	# $start is the part before a <
      ($start,$rest) = ($rest =~ /^([^<]*)(.*)$/s);
      # (?<!a\\) is for zero-width negative look behind (not proceeded by a '\')
      if ($case ?
          $start =~ s/(?<!\\)\b($tag)\b/<a href="$url" class=glossary>$1<\/a>/ :
          $start =~ s/(?<!\\)\b($tag)\b/<a href="$url" class=glossary>$1<\/a>/i )
      {
        $tested .= $start.$rest;  	# done if start matches
        $rest = '';
        ++$LinksInternal;         	# (global for home page counts)
      } else {                    	# otherwise, remove a tag
        $tested .= $start;
        if ($rest =~ /^<[aA]\s/so) {  	# hypertext reference?
           $rest =~ /^(.*?<\/[aA]>)(.*)$/s or die ("unmatched <a");
           $tested .= $1;
           $rest = $2;
        } elsif ($rest =~ /^<nolink/soi) {
	   # don't link between <nolink>...</nolink>
           $rest =~ /^(.*?<\/nolink>)(.*)$/si or die ("unmatched <nolink>");
           $tested .= $1;
           $rest = $2;
        } elsif ($rest =~ /^(<[^>]*>?)(.*)$/s) {
           $tested .= $1;
           $rest = $2;
        }
      }
    }
    $text2modify = $tested;
  }

  # Now remove the <nolink> containers
  $text2modify =~ s/<\/?nolink>//gs;
  return $text2modify;
}

$ent{'\\equiv'}	= '≡';
$ent{'\\leq'}	= '≤';
$ent{'\\le'}	= '≤';
$ent{'\\geq'}	= '≥';
$ent{'\\ge'}	= '≥';
$ent{'\\lt'}	= '&lt;';
$ent{'\\gt'}	= '&gt;';
$ent{'\\pm'}	= '±';
$ent{'\\cdot'}	= '·';
$ent{'\\TM'}	= '&trade;';
$ent{'\\COPY'}= '©';
$ent{'\\REG'}	= '&reg;';

$ent{'\\QED'}  = '<span class="float-right" title="Q.E.D.">∎</span>';
$ent{'\\alpha'}='α';
$ent{'\\beta'} ='β';
$ent{'\\gamma'}='γ';
$ent{'\\Gamma'}='Γ';

$ent{'\\delta'}  ='δ';
$ent{'\\epsilon'}='ε';
$ent{'\\lambda'} ='λ';
$ent{'\\mu'}     ='μ';
$ent{'\\minus '} ='−';
$ent{'\\minus'}  ='−';
$ent{'\\nu'}     ='η';
$ent{'\\phi'}    ='&phi;';
$ent{'\\pi'}     ='π';
$ent{'\\Phi'}    ='Φ';
$ent{'\\rho'}    ='ρ';

$ent{'\\sigma'}  = 'σ';
$ent{'\\Sigma'}  = 'Σ';
$ent{'\\sqrt'}   = '√';
$ent{'\\tau'}    = 'τ';
$ent{'\\theta'}  = 'θ';
$ent{'\\zeta'}   = 'ζ';

$ent{'\\ltcolor'} = 'ltcolor';
$ent{'\\medcolor'} = 'medcolor';
$ent{'\\drkcolor'} = 'drkcolor';

sub ShowEntities {
  my $line = "------\t".('-'x60)."\n";
  print "\nEntites to be translated:\n\n${line}from\tto\n$line";
  foreach (sort keys %ent) {
    print "$_ \t$ent{$_}\n";
  }
  print "$line\n";
}

sub ModifyEntities {
  my $text = shift || return '';
  my $mathjax = shift || 'no';
  my $upload_where = '?????';

  # If no backslashes, then nothing to change
  if ($text !~ /\\/s) { return $text; }

  # Images might have been inserted with the \image( ) tag
  $text =~ s/\\image\(([^\),]+)\,?([^\)]*)\)/<img src="$upload_where$1" $2>/sg;
  # Images might have been inserted with the \imagecaption( ) tag
  $text =~ s#\\imagecaption\(([^\),]+)\,?([^\),]+)\,?([^\)]*)\)#
        <table cellpadding=0 cellspacing=0 $3 align=right>
        <tr><td><img src="$upload_where$1" $3></td></tr>
        <tr><td align=center><font size=-3>$2</font></td></tr>\n</table>\n#sg;

  # References might have been inserted with the \cite{ } tag
  # Note LaTex/BibTeX allow a key_list here!

  $text =~ s#\\cite\{([^\},]+)\}#[<a href="/references/refs.cgi/$1">$1</a>]#gs;
  $text =~ s#\\cite\[([^\]]*)\]\{([^\},]+)\}#[<a href="/references/refs.cgi/$2">$2</a> $1]#gs;

  if ($mathjax eq 'no') {
    # If mathjax is yes, I assume TeX entities are handled via mathjax
    foreach (keys %ent) {
      $value = $ent{$_};
      s#\\#\\\\#;	# Protect the backquotes on the entities
      $text =~ s/(?<!\\)$_\b/$value/g;
    }
  }

  $text;
}

1;
