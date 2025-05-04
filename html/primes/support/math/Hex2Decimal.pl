#!/usr/bin/perl -w

use Math::Pari;

$f = shift or die "Usage '$0 filename' where filename points to a file
  containing a large hexidecimal number.  Whitespace will be removed.\n";

open(FILE,$f) or die "Could not open $f.\n";

$out = PARI 0;
while (<FILE>) {
  s/[\s\n\,\\]//go;
  s/\s*(\w)/
    $x = $1;
    if ($x eq 'A') { $x = 10; }
    elsif ($x eq 'B') { $x = 11; }
    elsif ($x eq 'C') { $x = 12; }
    elsif ($x eq 'D') { $x = 13; }
    elsif ($x eq 'E') { $x = 14; }
    elsif ($x eq 'F') { $x = 15; }
    $out = 16*$out + $x;
    print '';
  /eog;
}

print $out;
