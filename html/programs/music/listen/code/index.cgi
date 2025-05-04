#!/usr/bin/perl -w

# Call as .../page.cgi/filename.  Reads $FileArea.filename and prints it
# using the standard menu::TitleAndMenu.  The first line can be
# Title = ... to give a title to the page.

# use menu;
require "/var/www/html/programs/music/listen/code/menu.pm";

my $File = $ENV{'PATH_INFO'} || '/index.txt';
my $FileArea = '/var/www/html/programs/music/listen/code/assets';
$menu::Title = "MIDI's, Primes, and Sequences";
my $Out = &menu::SlurpFile($FileArea.$File);

print &menu::TitleAndMenu($menu::Title),$Out,&menu::End;

