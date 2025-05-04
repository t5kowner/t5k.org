#!/usr/bin/perl

# Uses my parser built on pari to evaluate the number, then
# appends it to a file. Yes-- I want to append to build helper 
# files for pfgw...

use lib "/var/www/html/primes/support/math";
use bmath;
use parser;

my $file  = shift or die "No file name given";
my $input = shift or die "No number passed to parse";

my $out = &parser::parse($input);

open(FILE,">>$file") or die $!;
print FILE "$out\n" or die $!;
close FILE or die $!;
