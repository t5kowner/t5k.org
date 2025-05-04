#!/usr/bin/perl -w

# Finds the length of the mersenne and perfect number (exponent passed 
# as addtional path info--otherwise uses the default.

local $Exponent= 3021377;
if (defined $ENV{"PATH_INFO"} and ($ENV{"PATH_INFO"} =~ m#^/?(\d+)/?$#)) {
  $Exponent = $1;
} 
local $Digits = int(1 + $Exponent*log(2)/log(10));

use lib  "/bin", "/var/www/html/bin";

require "my.pm";
$NumberURL =  "/notes/$Exponent/";
$PerfectURL = "https://t5k.org/mersenne/index.html#theorems";

if (&my::ReadParse(*In)) {
  ($Size) = ($In{'Size'} =~ /^(\d+)/io);
  $Points = $Digits*$Size;
  &my::message("How long is the prime 2<sup>$Exponent</sup>-1?",&MenuBar,"<center>
   <table border=2 cellpadding=2>
   <tr><th colspan=3>If you use a $Size point font
    then the <a href=$NumberURL>prime</a> is...</th></tr>
    <tr><td>&nbsp;</td><td align=center>without commas</td>
    <td align=center>with commas</td></tr>
    <tr><td>(US)</td><td>",
    &USLength($Points),"</td><td>", &USLength($Points*4/3),"</td>
    </tr><tr><td>(metric)</td><td>",
    &MetricLength($Points),"</td><td>",&MetricLength($Points*4/3),"</td></tr>
    <tr><th colspan=3>...and the 
    <a href=$PerfectURL>associated perfect number</a> is...</th></tr>
    <tr><td>(US)</td><td>",
    &USLength(2*$Points),"</td><td>", &USLength($Points*8/3),"</td>
    </tr><tr><td>(metric)</td><td>",
    &MetricLength(2*$Points),"</td><td>",&MetricLength($Points*8/3),
    "</td></tr></table>");
} else {
  &my::message("How long is the prime 2<sup>$Exponent</sup>-1?",&Form);
}

sub USLength {
  local $Inches = int($_[0]/72);
  my $Feet   = int($Inches/12); $Inches = $Inches % 12;
  my $Miles  = int($Feet/5280); $Feet = $Feet % 5280;
  my $out = ($Miles ? "$Miles mile" : '');
  $out .= 's' if $Miles > 1;
  $out .= ' and ' if ($Miles and not $Inches);
  $out .= ($Feet ? " $Feet feet " : '');
  $out .= ($Inches ? " and $Inches inch" : '');
  $out .= 'es' if $Inches > 1;
  $out;
}

sub MetricLength {
  my $Meters = int($_[0]/72/39.37);
  "$Meters meters\n";
}

sub Form {
  my $out = &MenuBar."If we were to print all of the $Digits decimal digits of the <a
href=$NumberURL>prime</a> 2<sup>$Exponent</sup>-1 in a single line of type, how long would it be? 
Obviously it depends on the size of the font.  You select the font size and I'll tell you how long.  10
and 12 point fonts are standard size fonts in books and articles. 
<center>
<form method=post> 
<select name=Size>"; 
  foreach ((1..9)) {
    $out .= "<option>$_\n";
  }
  $out .= "<option selected>10\n";
  foreach ((12,14,16,18,20,24,28,32,40,48,60,72,96,144,288)) {
    $out .= "<option>$_\n";
  }
  $out .= "</select> &nbsp; &nbsp; <input type=submit value=\"How Long?\"></form>";
}

sub MenuBar {
  "<center><b>[</b> <a href=https://t5k.org/>Home Page</a> ||
  <a href=https://t5k.org/largest.html>Largest</a> |
  <a href=https://t5k.org/mersenne/index.html>Mersenne</a> |
  <a href=https://t5k.org/notes/faq/>FAQ</a> |
  <a href=https://t5k.org/notes/proofs/>Proofs</a> |
  <a href=https://t5k.org/lists/>Lists</a> |
  <a href=https://t5k.org/prove/>Proving</a> <b>]</b>
  </center><P>"
}
