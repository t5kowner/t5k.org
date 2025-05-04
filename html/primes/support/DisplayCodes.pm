package DisplayCodes;

# This package allows us to print codes.  Used by list.print to print the codes
# after printing lists of primes

# Exportable routines:
#
# &DisplayCodes( );

# open database
use DBI;
use connect_db;
my $dbh = &connect_db::connect();

sub DisplayCodes {
  my $options = shift;
  my $match = $$options{'match'} || '';
  my $not_match = $$options{'not_match'} || '';
  my $these = $$options{'list'} || 0;
  my $style = $$options{'style'} || 'text';

  # Return a string which prints all of the codes unless restricted as follows
  #
  #	1. match the string $$options{'match'} (if it exists)
  #	2. do not match the string $$options{'not_match'} (if it exists)
  #	3. are included as a key (with non-empty value) in $$options{'list'}
  #	   (if it exists)
  #
  # these will be displayed as $$options{'style'} suggests: text (default), html
  # or short (only).
  #
  # Example:
  #
  # 	$those = {'g211' => 1, 'D' => 10};
  #	print &DisplayCodes({match=>'g2', list=>$those});
  #
  # will print only g211.

  $temp = ($style eq 'LaTex' ? 'text' : $style);
  my $query = "SELECT name, display_$temp as display FROM code ORDER BY name";
  $sth = $dbh->prepare($query) || die $sth->errstr;
  $sth->execute() || die $sth->errstr;

  # We put them in a hash array %Codes so we can sort them (x2 before x11...)

  my %Codes;
  my $name;
  while ($x = $sth->fetchrow_hashref) {
    $name = $$x{'name'};
    next if ($match and $name !~ /$match/o);
    next if ($not_match and $name =~ /$not_match/o);
    next if ($these and not $$these{$name});
    $Codes{$name} = $$x{display};
  }

  my $out;
  if ($style eq 'html') {
     $out = "<h4>KEY TO PROOF-CODES (primality provers):<h4>\n\n";
  } elsif ($style eq 'LaTex') {
     $out = "\\section{Table of Proof-Codes}
Key to Proof-Codes (primality provers):
\\begin{longtable}{rl}
\\hline
code & description \\\\
\\hline
\\endhead
\\hline
\\endfoot
";
  } else {
     $out = "KEY TO PROOF-CODES (primality provers):\n\n";
  }

  foreach $name (sort CodeSort keys %Codes) {
    if ($style eq 'html') {
      $out .= "<a href=\"/bios/codes.php?code=$name\">$name</a> $Codes{$name}<br>\n";
    } elsif ($style eq 'LaTex') {
      my $codes = $Codes{$name};
      # Protect LaTex specials # $ % & ~ _ ^ \ { }  (similar line is in LoarPrimes.pm)
      if ($codes =~ /([\$\%\&~\\{}])/) { print "warning: unprotected $1 in code $name = $codes\n"; }
      $codes =~ s/_/\\_/g;
      $out .= "$name \& $codes \\\\\n";
    } else {
      $out .= $name.' 'x(6-length($name))."$Codes{$name}\n";
    }
  }

  if ($style eq 'LaTex') { $out .= "\\end{longtable}\n"; }

  $out;

}


sub CodeSort {
  my ($letter1, $letter2, $number1, $number2);
  if ( ($letter1, $number1) = ($a =~ /^(\w)(\d+)$/o)  and
    ($letter2, $number2) = ($b =~ /^(\w)(\d+)$/o)) {
    (uc($letter1) cmp uc($letter2)) or ($number1 <=> $number2);
  } else {
    uc($a) cmp uc($b);
  }
}

1;
