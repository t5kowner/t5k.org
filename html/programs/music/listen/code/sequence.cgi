#!/usr/bin/perl -w

require "/var/www/html/programs/music/listen/code/my.pm";

my $MaxTerm = 2**63;

require "/var/www/html/programs/music/listen/code/primes.pm";
require "/var/www/html/programs/music/listen/code/Midi.pm";
require "/var/www/html/programs/music/listen/code/menu.pm";

if (&my::ReadParse(*In)) {  # Process Input

   ($A)  =  (($In{'A'} || 0) =~ /^(\d+)/o);
   ($B)  =  (($In{'B'} || 0) =~ /^(\d+)/o);
   ($C)  =  (($In{'C'} || 0) =~ /^(\d+)/o);
   ($X0) = (($In{'X0'} || 0) =~ /^(\d+)/o);
   ($X1) = (($In{'X1'} || 0) =~ /^(\d+)/o);

   unless (($NumTerms) = (($In{'NumTerms'}||10)=~/^(\d+)/o) and ($NumTerms<500)) {
     &menu::Punt("<P>You must enter a positive integer less than 500 for the
        number of terms.  (This is to keep our server from being overloaded.)");
     exit;
     print Midi::MidiCgiHeader;  # Just want it to stop complaining this is used once
   }

   if ($In{'OmitLow'} and $In{'OmitMiddle'}  and $In{'OmitHigh'} ) {
     &menu::Punt("You have omitted everything--nothing to play!");
   }

   if ($In{'Debug'}) {
      print &menu::TitleAndMenu('Sequence-al Sounds: Debug');
      print "<div class=highlight>(A,B,C) = ($A,$B,$C), (X0,X1) = ($X0,$X1)</div><P>\n";
      print "<div class=highlight>The output is <a href=\"/programs/music/listen/code/sequence.midi\">here</a></div>";
   }

   $String = &CreateMusic;

   if ($In{'Debug'}) {
      $file="sequence.midi";
      open(OUT,">$file") or &menu::Punt("Fatal Error: could not open $file for write");
      print OUT $String or &menu::Punt("Fatal Error: could not extend $file");
      close(OUT) or &menu::Punt("Fatal Error: could not close $file");
      chmod 0755, $file;   # Always fails?
      print &menu::End;
   } else {
      print $Midi::MidiCgiHeader,$String;
      exit;
   }
} else {  # Print Form
   print &menu::TitleAndMenu('Sequence-al Sounds');
   &PrintForm;
   print &menu::End;
}

exit;

############################## SEQUENCE ##################################
#

sub AddTerm {
  my $term = shift || return;
  my $factors = &primes::Factor($term);

  if ($term > $MaxTerm) {  # Are the terms too big??
    if (defined $In{'Debug'}) {
      print "$term is too large too factor";
    }
    return;
  }

  # Okay, not too big--lets factor.

  if (defined $In{'Debug'}) {
     print "</ul>$term = ",&primes::DisplayFactors($factors),"<UL>\n";
  }

  my $note;
  my $list;
  foreach $prime (sort {$a <=> $b} keys %{$factors}) {
    if ($$factors{$prime} < 4) {
      $velocity = 32*$$factors{$prime};
    } else {
      $velocity = 127;
    }
    if ($prime < ($In{'NumLow'} || 15)) {
      $list = 'low';
      $note = ($In{'OffsetLow'}||40) + $prime;
      $NoteList1 -> AddNote($note,190,$time,$velocity);
    } elsif ($prime < ($In{'NumMiddle'} || 60)) {
      $list = 'middle';
      $velocity = $velocity + 63 if $velocity < 65;
      $note = ($In{'OffsetMiddle'}||30) + $prime;
      $NoteList2 -> AddNote($note,190,$time,$velocity);
    } else {
      $list = 'high';
      $velocity = $velocity + 63 if $velocity < 65;
      $note = ($In{'OffsetHigh'}||41) + ($prime % ($In{'BaseHigh'}||41));
      $NoteList3 -> AddNote($note,190,$time,$velocity);
    }
    if (defined $In{'Debug'}) {
      print "<LI>$prime is $list, velocity $velocity, note is $note</li>";
    }

  }
}

# &CreateMusic;

sub CreateMusic {
   $NoteList1 = Midi->New();
   $NoteList2 = Midi->New();
   $NoteList3 = Midi->New();

   if (defined $In{'Debug'}) {
      print "<h2>terms in the sequence</h2>
         Max for a low primes is $In{'NumLow'}<br>
         Max for a middle primes is $In{'NumMiddle'}<br>
         <P>
      ";
   }

   $time=0;
   &AddTerm(my $Term0=$X0);
   $time=194;
   &AddTerm(my $Term1=$X1);
   for (3..$NumTerms) {
     $Temp  = $Term1;
     $Term1 = $A*$Term1 + $B*$Term0 + $C;
     $Term0 = $Temp;
     $time += 194;
     &AddTerm($Term1);
   }
   &Midi::MakeMidiFile(
     (defined $In{'OmitLow'}    ? '' : $NoteList1->Play($In{'InstLow'},'small primes')),
     (defined $In{'OmitMiddle'} ? '' : $NoteList2->Play($In{'InstMiddle'},'middle primes')),
     (defined $In{'OmitHigh'}   ? '' : $NoteList3->Play($In{'InstHigh'},'large primes'))
   );
}

############################## SUPPORT ###################################
#
#
sub PrintForm {
  print "<form action=https://$ENV{SERVER_NAME}$ENV{SCRIPT_NAME}/$^T.midi
   method=post>

<h2>First define the sequence</h2>

The terms of the sequence are defined recursively by
<blockquote>
  <i>x</i><sub><i>n</i>+1</sub> =
        A<i>x</i><sub><i>n</i></sub>+B<i>x</i><sub><i>n</i>-1</sub>+C
</blockquote>
with starting values <i>x</i><sub>0</sub> and <i>x</i><sub>1</sub>.

<blockquote><table border=1 cellpadding=5>
  <tr class=highlight>
    <th>coefficients</th><th>inital values</th>
  </tr>
  <tr>
    <td>
	A <INPUT TYPE=TEXT NAME=A VALUE=1 SIZE=3> &nbsp;
	B <INPUT TYPE=TEXT NAME=B VALUE=0 SIZE=3> &nbsp;
	C <INPUT TYPE=TEXT NAME=C VALUE=1 SIZE=3>
    </td>
    <td>
	<i>x</i><sub>0</sub> <INPUT TYPE=TEXT NAME=X0 VALUE=0 SIZE=7> &nbsp;
	<i>x</i><sub>1</sub> <INPUT TYPE=TEXT NAME=X1 VALUE=1 SIZE=7>
    </td>
  </tr>
</table></blockquote>

<h2>Now determine the score</h2>

<blockquote><table border=1 cellpadding=5>
  <tr class=highlight>
    <th>range</th><th>maximum</th><th>offset</th><th>instrument</th><th>omit?</th>
  </tr>
  <tr align=center>
    <td align=right>low</td>
    <td align=center><Input type=text name=NumLow value=15 size=3></td>
    <td><Input type=text name=OffsetLow value=40 size=3></td>
    <td>",&Midi::MidiInstrumentSelectBox('InstLow',0),"</td>
    <td><input type=checkbox name=OmitLow></td>
  </tr>
  <tr align=center>
    <td align=right>middle</td>
    <td><Input type=text name=NumMiddle value=60 size=3></td>
    <td><Input type=text name=OffsetMiddle value=40 size=3></td>
    <td>",&Midi::MidiInstrumentSelectBox('InstMiddle',1),"</td>
    <td><input type=checkbox name=OmitMiddle></td>
  </tr>
  <tr align=center>
    <td align=right>high</td>
    <td><Input type=text name=BaseHigh value=41 size=3><br>modulus</td>
    <td><Input type=text name=OffsetHigh value=40 size=3></td>
    <td>",&Midi::MidiInstrumentSelectBox('InstHigh',104),"</td>
    <td><input type=checkbox name=OmitHigh></td>
  </tr>
</table></blockquote>

<P>How many terms? <INPUT TYPE=TEXT NAME=NumTerms VALUE=30 SIZE=3>
  <input type=submit value=\"LISTEN TO THESE TERMS\"> ".&menu::Hear."<br><br>
  <font color=grey><INPUT TYPE=CHECKBOX NAME=Debug> Show debug screen?</font>
	(Use with Microsoft Explorer if it does not play)
</form>
";

}
