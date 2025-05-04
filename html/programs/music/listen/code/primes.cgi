#!/usr/bin/perl -w

require "/var/www/html/programs/music/listen/code/primes.pm";
require "/var/www/html/programs/music/listen/code/Midi.pm";
require "/var/www/html/programs/music/listen/code/menu.pm";

# Limits

$MaxStart = 10000000;      # largest start value
$MaxTerms = 500;           # longest sequence of notes
# Let's do the work

if (&menu::ReadParse(*In)) {  # Process Input

   $CorrectionFactor = ($In{'Half'} ? 1 : 12/7);
   unless (($Base) = ($In{'Base'} =~ /^(\d+)/o) and $Base > 0 and 
       $CorrectionFactor * $Base <= 128) {
     &menu::Punt("<P>I am sorry, but you must enter a positive integer less  
       than 75 (128 if you are using half notes) for the modulus (base).
       This is because MIDI only supports 75 whole notes (127 notes
       including half notes).  <b>Note:</b> not all of these notes are
       implemented by all sound cards.
       For many instruments the lowest (and highest) notes may all be
       mapped to the same frequency!  <b>Moral:</b> smaller moduli
       (bases) should work better."); 
     exit;
   }
   unless (($NumTerms) = ($In{'NumTerms'}=~/^(\d+)/o) and 
       ($NumTerms <= $MaxTerms)) {
     &menu::Punt("<P>You must enter a positive integer (less than or equal
       to $MaxTerms) for the number of primes/notes.  Sorry, but this limit is
       necessary to keep our server from being overloaded."); 
     exit;
   }
   unless (($Start) = ($In{'Start'} =~ /^(\d+)/o) and ($NumTerms <= $MaxStart)) {
      &menu::Punt("<P>You must enter a positive integer (at most than $MaxStart)
        for the starting point of the sequence.  (This is to keep our CPU 
        from being overloaded.)");
     exit;
   }

   if ($In{'Debug'}) {
     ($number,$name) = ($In{'Instrument'} =~ /^(\d+)\s+(.*)$/o);
     print &menu::TitleAndMenu('Primal Sounds: Debug');
     print "<table border=1 cellpadding=5><tr><td><b>Modulus</b> 
       $Base</td><td><b>Terms</b> 
       $NumTerms</td></tr><tr><td><b>Starting at (or after)</b> $Start</td>
       <td><b>Instrument:</b> $name ($number)</td></tr>\n<tr><td>";
     print '<B>',($In{'UseGaps'} ? 'Using' : 'Not using'),
       "</B> the prime gaps.</td><td>\n"; 
     print '<B>',($In{'Half'} ? 'Using' : 'Not using'),
       "</B> half notes.</td></tr>\n"; 
   }
   $String = &CreateMusic($Base,$NumTerms,$Start);

   if ($In{'Debug'}) {
     $file="primes.midi";
     open(OUT,">$file") or &menu::Punt("Fatal Error: could not open $file for write");
     print OUT $String;
     close(OUT);
     # chmod 0755, $file;
     print "<div class=highlight>The output is <a href=\"/programs/music/listen/code/primes.midi\">here</a></div>";
     print &menu::End;
   } else {
     $unused = '';
     $unused = $Midi::MidiCgiHeader; # avoiding a used only once header
     print $Midi::MidiCgiHeader,$String;
   }

} else {  # Print Form
  print &menu::TitleAndMenu('Primal Sounds');
  &PrintForm;
  print &menu::End;
}

exit;

############################## SEQUENCE ##################################
#
#
# &CreateMusic(modulus, number_of_terms, starting_where)

sub CreateMusic {
   local $Base  = $_[0];
   local $Terms = $_[1];
   local $Term  = $_[2] || 1;
   local $NoteList = Midi->New();
   local $Prime = $Term;

   # Two octaves worth of whole-notes
   local @Filter = (0,2,4,5,7,9,11,12,14,16,17,19,21,23); 

   #  Center on middle A (A3) if possible.
   local $Offset = 0;  # Where in filter to start
   local $Note;
   if ($In{'Half'}) {      
     $BaseNote = ($Base < 114 ? int(57-$Base/2) : 0);
   } else {
     $BaseNote = ($Base < 68 ? int(57-12*$Base/14) : 2);
     while (($Offset = &In($BaseNote % 12,@Filter)) == -1) { $BaseNote++ };
   }
   if ($In{'Debug'}) {
     print "<tr><td><b>Basenote</b> $BaseNote</td><td><b>Offset</b> 
       $Offset</td></tr></table>\n<P>The primes (notes) are:\n<blockquote>\n"; 
   }

   # Put the first prime in $Term
   if (&primes::Prime($Prime)) { 
     $Term = $Prime;
   } else {
     $Term = &primes::NextPrime($Prime);
   }

   # Play the primes
   for (1..$Terms) {
     $Prime = $Term;  # We will play $Prime, but look ahead
     $Term = &primes::NextPrime($Term); # to measure the gap

     # What note do we play?
     $Note = $Prime % $Base;
     if (not $In{'Half'}) {
       $Note = 12*int(0.01+$Note/7) + $Filter[($Note%7)+$Offset]
                   - $Filter[$Offset]; 
     }
     $Note = $BaseNote + $Note;

     if ($In{'UseGaps'}) {
         $Gap = int(($Term-$Prime)/(log($Prime)+1)*380);
         $NoteList->AddNote($Note,$Gap);
       } else {
         $NoteList->AddNote($Note);
       }
      if ($In{'Debug'}) {
        print "$Prime ($Note",($In{'UseGaps'} ? ",$Gap" : ''),') ';
      }
   }

   if ($In{'Debug'}) {
     local $Average = ($NoteList->Last)/$Terms-4; # 4 used as a gap
     print "\n</blockquote>\n";
     $In{'UseGaps'} and print "<b>Average gap</b> $Average<P>\n";
   }
 
   &Midi::MakeMidiFile($NoteList->Play($In{'Instrument'}));
}

############################## SUPPORT ###################################
#
#
# &Position($Item,@List) = position (starting at 0) or -1

sub In {
  my $Item  = shift or return(-1);
  my $where = 0;
  foreach (@_) {
    return($where) if ($Item eq $_);
    $where++;
  }
  return(-1);
}

sub PrintForm {
  print "<form action=primes.cgi/$^T.midi
   method=post>
   Modulo what base?   <INPUT TYPE=TEXT NAME=Base VALUE=6 SIZE=3>
   <input type=submit value=\"LISTEN TO THESE PRIMES\"> ".&menu::Hear."<BR>
   How many primes ?   <INPUT TYPE=TEXT NAME=NumTerms VALUE=30 SIZE=3>&nbsp;&nbsp;
   Starting at (or after)? <input type=text name=Start value=2 SIZE=9><BR>
   Which instrument?   ",&Midi::MidiInstrumentSelectBox,"<BR><BR>
   <input type=checkbox name=Half> Allow half-notes?<BR>
   <INPUT TYPE=CHECKBOX NAME=UseGaps> Use the prime gaps to determine ",
   "the note lengths?<br><font color=grey>
   <INPUT TYPE=CHECKBOX NAME=Debug> Show debug screen.</font> (Use with Microsoft Explorer if it does not play)
   </FORM>

   <H3>What is this?</H3>
   This program takes the sequence of primes {2,3,5,7,11,13,17,19,...} and
   forms the sequence of remainders modulo the small base (modulus) you give.
   For example, modulo 4 this sequence is {2,3,1,3,3,1,1,3,...}.  It then
   plays this sequence as if the numbers were notes (near middle C)
   on the \"instrument\" of your choice.  

   <P><a href=page.cgi/primes.txt class=highlight>More Information</a> 
   (e.g., what if I can't hear it?)
";


}

