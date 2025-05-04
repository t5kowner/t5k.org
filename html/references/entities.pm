package entities;

# TeX has text entities such as \~a (a-tilde) and math entites such as \pi,
# all beginning with a \.  These routines translate these.

# uses &main::my_warn to prove smarter warnings too


# Shall I make the value here a hash array and allow match, flags about spacing... ?
# Note, below is @MathSame=those that are the same in TeX (as \xxxx) and 
# extended HTML (as &xxxx;)

%MathEntities = (
# binary operators (can remove space from around these)
  'cdot'  => '&middot;',
  'cdots' => '<sup>...</sup>',
  'ldots' => '...',
  'dots'  => '...',
  'approx'=> '<u>~</u>',        # Hummmm ?????????
  'geq'   => '<u>&gt;</u>',
  'leq'   => '<u>&lt;</u>',
  ';'     => '&nbsp;',		# \; is a thick space
  ','	  => '&nbsp;',		# \, is a thin space
  '['     => '[',
  ']'     => ']',
  '*'     => '*',
  '#'     => '#',
  'in'    => '&isin;', 
  'nmid'  => '<img src="/gifs/NotDivide.gif" alt="does not divide">',
  'pm'    => '&plusmn;',
  'mp'    => '<img src="/gifs/mp.gif" alt="-/+">',
  'lfloor'=> '&lfloor;',  # SHOULD BE REPLACE BY GIF -- IO entites do not dispaly most browsers
  'rfloor'=> '&rfloor;',

# PROBLEM!!! The digit unitalization is screwing up ' width=11 height=12>', FIX so we can add these

# names (space around these may be significant)
  'gcd'   => 'gcd',
  'ln'    => 'ln',
  'li'    => 'li',	# THIS IS NON-STANDARD !!!
  'log'   => 'log ',	# what about spacing?  e.g., \log\log
  'bmod'  => 'mod',
  'mod'   => 'mod',
  'exp'   => 'exp',

#  '{' => '&#173;',  
#  '}' => '&#175;',
);

@MathSame = qw(Gamma Delta Theta Lambda Xi Pi Sigma Upsilon Phi Psi Omega
    alpha beta gamma delta epsilon zeta eta theta iota kappa lambda mu nu xi 
    omicron pi rho sigma tau phi chi psi omega prod sum int cap cup sim 
    equiv gt lt times ge le);

foreach (@MathSame) {
  if (defined $MathEntities{$_}) {
    die("$_ already defined in MathEntities.");
  } else {
    $MathEntities{$_} = '&'.$_.';';
  }
}

%MathMissing = ();  # As unknown entities found, sqwuak once, and add to this hash array

sub MathEntities {
  my $string = shift;
  # Note: \w includes _, but TeX uses it as a delimiter.
  # Also may be end of string so need *
  ($op) = ($string =~ /\\(.+?)[\W_\d]/o) or
    ($op) = ($string =~ /\\(.+?)$/o)
      or return $string;
  if (defined $MathEntities{$op}) {
    $string =~ s/\\$op([\W_\d])/<\/i>$MathEntities{$op}<i>$1/g;
    $string =~ s/\\$op$/<\/i>$MathEntities{$op}<i>/g;
    $string = &MathEntities($string);
  } else {
    &main::my_warn("undefined math entity $op") unless $MathMissing{$op}++;
    $string = "$`/$op".&MathEntities($');
  }
  $string;
}


%TextEntities = (
  '`A' => '&Agrave;',  '`a' => '&agrave;',
  '`E' => '&Egrave;',  '`e' => '&egrave;',
  '`I' => '&Igrave;',  '`i' => '&igrave;',
  '`O' => '&Ograve;',  '`o' => '&ograve;',
  '`U' => '&Ugrave;',  '`u' => '&ugrave;',

  '\'A' => '&Aacute;',  '\'a' => '&aacute;',
  '\'E' => '&Eacute;',  '\'e' => '&eacute;',
  '\'I' => '&Iacute;',  '\'i' => '&iacute;',
  '\'O' => '&Oacute;',  '\'o' => '&oacute;',
  '\'U' => '&Uacute;',  '\'u' => '&uacute;',
  '\'Y' => '&Yacute;',  '\'y' => '&yacute;',
  '\'n' => 'n', # because &nacute; is not a standard HTML entity
  
  '~A' => '&Atilde;',  '~a' => '&atilde;',
  '~O' => '&Otilde;',  '~o' => '&otilde;',
  '~N' => '&Ntilde;',  '~n' => '&ntilde;',

  '^A' => '&Acirc;',  '^a' => '&acirc;',
  '^E' => '&Ecirc;',  '^e' => '&ecirc;',
  '^I' => '&Icirc;',  '^i' => '&icirc;',
  '^O' => '&Ocirc;',  '^o' => '&ocirc;',
  '^U' => '&Ucirc;',  '^u' => '&ucirc;',

  '"A' => '&Auml;',  '"a' => '&auml;',
  '"E' => '&Euml;',  '"e' => '&euml;',
  '"I' => '&Iuml;',  '"i' => '&iuml;',
  '"O' => '&Ouml;',  '"o' => '&ouml;',
  '"U' => '&Uuml;',  '"u' => '&uuml;',
                     '"y' => '&yuml;', 

  'vc' => '&ccaron;',   'vC' => '&Ccaron;',
  'vd' => '&dcaron;',   'vD' => '&Dcaron;',
  've' => '&ecaron;',   'vE' => '&Ecaron;',
  'vl' => '&lcaron;',   'vL' => '&Lcaron;',
  'vn' => '&ncaron;',   'vN' => '&Ncaron;',
  'vr' => '&#x00159;',  'vR' => '&#x00158;',
  'vs' => '&scaron;',   'vS' => '&Scaron;',
  'vt' => '&tcaron;',   'vT' => '&Tcaron;',
  'vz' => '&#x17e;',   'vZ' => '&#x17d;',

  'Ho' => '&ouml;',

  ' '  => ' ',
  '#'  => '#',
  'ss' => '&szlig;',
  'dots' => '...',
  'ldots' => '...',
  'aa' => '&aring;',
  'ae' => '&aelig;',

  'i' => '&inodot;',  

#  '{' => '&#173;',  
#  '}' => '&#175;',
);

my %TextMissing;  # To store missing entities--sqauwk once...

sub TextEntities {
  my $string = shift;
  # Note: \w includes _, but TeX uses it as a delimiter.
  # Also may be end of string so need *

  # while ($string =~ /\{\\(.+?)\}/mo or $string =~ /\\(["'`^~]\w)/o) { 
  # caused infinite loop

  $string =~ s/\\H\{(\w)\}/{\\"$1}/g;  # Preprocess Hungarian umlauts to regular umlauts

  while ($string =~ /\{\\(.+?)\}/mo or $string =~ /\\(\w)/o) {
    my $op = $1;
    my $match_op = quotemeta($op);  # For example ^u must be \^u
    if (defined $TextEntities{$op}) {
      $string =~ s/\{\\$match_op\}/$TextEntities{$op}/gm;
    } else {
      &main::my_warn("undefined text entity $op") unless $TextMissing{$op}++;
      $string =~ s/\{\\$match_op\}/$op/gm
        or $string =~ s/\\$match_op/$op/gm
          or &main::my_warn("match to $op not found!");
    }
  }
  $string;
}


1;

