#!/usr/bin/perl
# To test canonicalizer over the web
# Copyright Chris Caldwell (C) 1997-2004, not for distribution.

## use lib qw (/var/www/html/bin);
use lib qw (
  /var/www/html/primes/support/modules
  /var/www/html/primes/support/math
  /var/www/html/primes/support
  /var/www/html/primes/bin
  /var/www/html/bin
);

use my;          # my cgi routines
use Time::HiRes 'time'; 
my $time = time;

print &my::HTML_Header;

# get default HTML page (program_name.html)

undef $/;
my $file = $0.'.html';
open (FILE,$file) or &my::punt("Could not open file :\"$file\"");
my $file = <FILE>;
close(FILE);

# Do the work

use canonical;

my $out = '';
$out .= sprintf("Time used since process started: %9.4f seconds.<br>\n",$time-$^T);
$out .= sprintf("Time used since loaded parser modules: %9.4f seconds.\n",time-$time);

my $parse_string='';
if (&my::ReadParse(*In)) {
  $parse_string= $In{'string'} || '';
  $out .= "<h3>Evaluating: $parse_string</h3>\n";
  $out .="<h3>Returned: " .&canonical::adjust($parse_string,'html')."</h3>";
  $out .= "<pre>".&canonical::show_changes()."</pre>\n";
}
$out .= sprintf("(took %9.4f seconds)\n",time-$time);

$file =~ s/__output__/$out/;
$file =~ s/parse_string/$parse_string/;

$info = `grep "# Rule" < canonical.pm`;
$info =~ s/# (Rules \d+.*)/<br><br>$1<br>/go;
$info =~ s/[^n]#/<li>/go;  # Allow "n#"
$file =~ s/__info__/$info/;

print $file;
