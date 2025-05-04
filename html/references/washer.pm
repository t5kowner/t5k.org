package washer;

use Carp;      # Allows better error messages
use entities;  # Translate TeX entities.
# uses &main::my_warn to provide smarter warnings too

# Must not be relative (e.g., when imbedded into a glossary entry)
$ScriptURL = '/references/refs.cgi';

# John Schommer's Tex Washer

sub wash {

  #We first initialize variables.  Many of these variables have been
  #sloppily added as their need arose.  No attempt at efficiency here.

	$italoff = "\<\/i\>";
	$icollar = "\}";
	$ecollar = "\}";
	$ticollar = "\}";
	$boldoff = "\<\/b\>";
	$bcollar = "\}";
	$bfcollar = "\}";

  #We now begin washing the file of all substitutable TeX-code,
  #one line at a time.

  $line = shift;
  unless ($line) {
    &main::my_warn('empty string passed to washer::wash in washer.pm SHOULD NEVER HAPPEN');
    # croak('How did this happen?');
    return '';
  }
  $line =~ s/\n/ /go;

  $line =~ s/\\\{/#QuotedOpenCurly/o;   # Change these to avoid parsing problems
  $line =~ s/\\\}/#QuotedCloseCurly/o;

  # Allow, but ignore, \v{ } accenting
  $line =~ s/\\v\s*\{(\w)\}/$1/gos;

  # Wash the math

  &double_dollars;
  &single_dollars;

  &italics;
  &textit;
  &emphasis;
  &boldface;
  &textbf;

  # note; hardcoded use of cgi-name!!!!  Second \cite case can not use \\cite\{(.*?), ...
  $line =~ s/\\cite\[(.*?)\]\s*\{(.*?)\}/[<a href="$ScriptURL?item=$2">$2<\/a>, $1]/gos;
  $line =~ s/\\cite\{([^},]+),\s*(.*?)\}/[<a href="$ScriptURL?item=$1">$1<\/a>, <a href=$ScriptURL?item=$2>$2<\/a>]/gos;
  $line =~ s/\\cite\{(.*?)\}/[<a href="$ScriptURL?item=$1">$1<\/a>]/gos;
  $line =~ s/\\url\s*\{(.*?)\}/<a href="$1">$1<\/a>/gos;

  if ($line =~ s/\\href\s*\{(.+?)\}\s*\{(.+?)\}/<a href="$1">$2<\/a>/gos) {
    &main::my_warn('virtual url used') if $1 !~ /^(http\:|\/|ftp\:|https\:)/o;
  }
  &main::my_warn('bad citation') if $line =~ /\\(cite|url|href)/o;

  $line = &entities::TextEntities($line);

  $line =~ s/``/"/g;   # Why are these here?
  $line =~ s/''/"/g;

  $line =~ s/.\\ /. /go;   # TeX's ".\ " is used to avoid a second space being added


  $line =~ s/\#QuotedOpenCurly/&#123;/o;
  $line =~ s/\#QuotedCloseCurly/&#125;/o;

  $line;
}

#Washes double dollars from the text.
sub double_dollars {
  $line =~ s/\$\$(.+?)\$\$/&MathQuote($1,1)/geos;
  $line =~ s/\\begin\{displaystyle\}(.*?)\\end\{displaystyle\}/&MathQuote($1,1)/geos;
}

#Washes single dollars from the text.

sub single_dollars {
  $line =~ s/\$(\d+)\$/$1/gos; # Leave single numbers alone
  $line =~ s/\$(.+?)\$/&MathQuote($1)/geos;
}

sub MathQuote {
  my $string = shift;
  my $string_was = $string;
  my $quote = shift || 0;

  $string =~ s/</&lt;/go; # Mistaken for HTML, must be before &MathEntities
  $string =~ s/>/&gt;/go; # Mistaken for HTML

  $string = &MathFunctions($string);  # Convert \pmod ...
  $string = &entities::MathEntities($string);  # Convert \pm ...

  $string = &superscripts($string);  # In here avoids changing URL's ...
  $string = &subscripts($string);

  $string = '<i>'.$string.'</i>';    # Math is basically italisized
  $string =~ s/([0-9()\[\]]+)/<\/i>$1<i>/go;  # But not integers... (not '.' otherwise...)
  $string =~ s#<i>(.*?)</i>#
     my $a = $1;
     if ($a =~ /<i>/) {
        &main::my_warn("MathQuote removed nested open-italic in\n\t$string_was\n");
        $a =~ s/<i>//g;
     }
     $a =~ s/(<\/?sup>|<\/?sub>)/<\/i>$1<i>/g;
     "<i>$a</i>";
  #eg;

  # Now clean up extra i's
  $string =~ s/<\/i>(\s*)<i>/$1/go;
  $string =~ s/<i>(\s*|<\/?sup>|<\/?sub>)<\/i>/$1/go;

  # Double dollars are quoted
  $quote ? '<blockquote>'.$string.'</blockquote>' : $string;
}

sub MathFunctions {  # Only a couple for now
  my $string = shift;
  my $out = $string;
  if ($out =~ /\\pmod\s*\{(.+?)\}/os) {
    $out = "$` (mod $1) $'";
  }
  if ($out =~ /\\sqrt\s*\{(.+?)\}/os) {
    $out = "$` sqrt($1) $'";
  }
  $out;
}

# Can we manage to get the next term? Should be a word or bracketed expression.
# Brackets will be removed.

sub PopTerm {
  (my $first) = ($line =~ /^(\S)/o); # Grab a token (somethin sans space)
  my $out = '';
  if ($first eq '{') {
    $line = $';
    $line =~ /(.*?)([\{\}])/ or die "Expected }, found \"$line\"";
    $line = $';
    $out .= ($2 eq '}') ? $1 : $1.&PopTerm;
  } elsif ($first =~ /\w/o) {
    $line =~ /(\w+)/;
    $out = $1;
    $line = $';
  }
  $out;
}


sub subscripts {
  my $line = shift;
  $line =~ s/\_(\w+)\b/<sub>$1<\/sub>/gos;
  $line =~ s/\_\{([^\{\}]*)\}/<sub>$1<\/sub>/go;
  $line;
}

sub superscripts {
  my $line = shift;
  $line =~ s/\^(\w+)\b/<sup>$1<\/sup>/gos;
  $line =~ s/\^\{([^\{\}]*)\}/<sup>$1<\/sup>/go;
  $line;
}


#Formats italics
sub italics {
	$count = 0;
	$italoffcount = 0;
	@words = split(/\{\\it /,$line);
	$words[0] =~ s/\}/$icollar/;
	while (++$count < @words ) {
		$words[$count] =~ s/\}/$italoff/;
		if ($words[$count] =~ /$italoff/) {
			++$italoffcount;
		}
	}
	if (@words > $italoffcount + 1) {
		$icollar = $italoff;
	}else{
		$icollar = "\}";
	}
	$line = join ("\<i\>",@words);
}

#Also formats italics
sub emphasis {

	$count = 0;
	$italoffcount = 0;
	@words = split(/\{\\em /,$line);
	$words[0] =~ s/\}/$ecollar/;
	while (++$count < @words ) {
		$words[$count] =~ s/\}/$italoff/;
		if ($words[$count] =~ /$italoff/) {
			++$italoffcount;
		}
	}
	if (@words > $italoffcount + 1) {
		$ecollar = $italoff;
	}else{
		$ecollar = "\}";
	}
	$line = join ("\<i\>",@words);
}


#Yet more italics formatting.
sub textit {
	$count = 0;
	$italoffcount = 0;
	@words = split(/\\textit\{/,$line);
	$words[0] =~ s/\}/$ticollar/;
	while (++$count < @words ) {
		$words[$count] =~ s/\}/$italoff/;
		if ($words[$count] =~ /$italoff/) {
			++$italoffcount;
		}
	}
	if (@words > $italoffcount + 1) {
		$ticollar = $italoff;
	}else{
		$ticollar = "\}";
	}
	$line = join ("\<i\>",@words);
}

#Formats boldface
sub boldface {
	$count = 0;
	$boldoffcount = 0;
	@words = split(/\{\\bf /,$line);
	$words[0] =~ s/\}/$bcollar/;
	while (++$count < @words ) {
		$words[$count] =~ s/\}/$boldoff/;
		if ($words[$count] =~ /$boldoff/) {
			++$boldoffcount;
		}
	}
	if (@words > $boldoffcount + 1) {
		$bcollar = $boldoff;
	}else{
		$bcollar = "\}";
	}
	$line = join ("\<b\>",@words);
}


#And more boldface formatting.
sub textbf {
	$count = 0;
	$boldoffcount = 0;
	@words = split(/\\textbf\{/,$line);
	$words[0] =~ s/\}/$bfcollar/;
	while (++$count < @words ) {
		$words[$count] =~ s/\}/$boldoff/;
		if ($words[$count] =~ /$boldoff/) {
			++$boldoffcount;
		}
	}
	if (@words > $boldoffcount + 1) {
		$bfcollar = $boldoff;
	}else{
		$bfcollar = "\}";
	}
	$line = join ("\<b\>",@words);
}

1;
