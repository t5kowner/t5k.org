#!/usr/bin/perl -w

require "/var/www/html/programs/music/listen/code/menu.pm";
require "/var/www/html/programs/music/listen/code/my.pm";
require "/var/www/html/programs/music/listen/code/Midi.pm";

if (&my::ReadParse(*In)) {              # Process Input
   $Notes1 = Midi->New();
   $Notes2 = '';
   for (split(//,uc($In{'string'})||' ')) {
     $Notes1->AddNote(ord($_));
     $Notes2 .= ord($_).', ';
   }
   my $String = &Midi::MakeMidiFile($Notes1->Play($In{'Instrument'}));
   chop $Notes2;
   chop $Notes2;

   if ($In{'Debug'}) {
     ($number,$name) = ($In{'Instrument'} =~ /^(\d+)\s+(.*)$/o);
     print &menu::TitleAndMenu('Primal Sounds: String Debug');
     print "<b>String:</b> \"$In{'string'}\"<br> 
       <b>ASCII Values:</b> $Notes2<br>
       <b>Instrument:</b> $name ($number)<br>\n";
   }

   if ($In{'Debug'}) {
     $file="strings.midi";
     open(OUT,">$file") or &menu::Punt("Fatal Error: could not open $file for write");
     print OUT $String;
     close(OUT);
     # chmod 0755, $file;
     print "<P><div class=highlight>The output is <a href=\"/programs/music/listen/code/strings.midi\">here</a><div>";
     print &menu::End;
   } else {
     $unused = '';
     $unused = $Midi::MidiCgiHeader;  # avoiding a used only once header
     print $Midi::MidiCgiHeader,$String;
   }
} else {                            # Print Form
   &PrintForm;
}
exit;

sub PrintForm {
   print &menu::TitleAndMenu('String to MIDI converter');
   print "<form action=https://$ENV{SERVER_NAME}$ENV{SCRIPT_NAME}/$^T.midi
      method=post>
      <input type=text name=\"string\" value=\"place string here\">
      <input type=submit value=\"Convert to Sound\"> ".
       &Midi::MidiInstrumentSelectBox."<br><br>
<font color=grey><INPUT TYPE=CHECKBOX NAME=Debug> Show debug screen?</font> (Use with Microsoft Explorer if it does
not play)
    </form><br>
   This program takes the 
   string you input, converts it to upper case, and then interprets 
   each character as a midi quarter note  (ASCII value=MIDI value).  
   The result is placed in a <a href=index.cgi>MIDI file</a> for your 
   listening pleasure.  (Note that spaces sound like very low notes.)<P>".
   &menu::Hear.&menu::End;

}
