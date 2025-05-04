#!/usr/bin/perl -w

use primes;
use Midi;

print "The midi test:\n";
$Notes=Midi->New('aA');
$Notes->Print;
&Midi::MakeMidiFile(\%Notes);

print "A few prime tests\n";
print "\tFirst ten primes: ";

$p=-100; # Should not cause a problem
for (1..10) {
  $p = &primes::NextPrime($p);
  print $p,', ';
}
print "\n";

$n = 3;
for (1..5) {
  $n = 133*$n + 1;
  $factors = &primes::Factor($n);
  print "\t$n \t= ",&primes::DisplayFactors($factors),
 	( &primes::Prime($n) ? " prime" : " not prime"),"\n";
}

$t = 1;
$i = 0;
while (2*$t+1 > 2*$t) { 
  $i++;
  $t=$t+$t;
# print "$i, $t\n";
}
print "Bits in integer $i, max int $t;\n"; 

