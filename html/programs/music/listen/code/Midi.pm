package Midi;
# MIDI 0.1 beta 

# Exportable constants
#
#   &MidiInstrumentSelectBox([name][,selected]);  List all 128 
#     "instruments" in a HTML FORM's select box (as "code# name")
#     adds '0 (percussion only)' to the general midi standard.
#     Uses 'NAME=name' (default name is 'NAME=Instrument') and
#     selects 'selected' (integer 0..128) default is 104. 
#
#   $MidiCgiHeader; The proper header for MIDI/CGI output with
#     an expired date to reduce the chance of browser caching.
#
# Exportable routines
#
#   &CreateMidi(string,instrument); turns the string to a midi file
#     and might be used as follows in a cgi program:
# 
#       print &Midi::CreateMidi($In{'string'},$In{'Instrument'});
#
#   &MakeMidiFile(instrument_track[s]); where the tracks are played 
#     note lists, for example:
#
#       local $NoteList = Midi->New(
#       while ( ? ) {
#         $NoteList->AddNote( ? , ? );
#       }
#       &Midi::MakeMidiFile($NoteList->Play($In{'Instrument'})); 
#
# Object type Midi is a "NoteList" with the following methods 
# 
#   &New([string]);  Create a new NoteList [make note list from the 
#     given string].
#
#   &Last; The time of the last event in the NoteList
#
#   &Print; Prints the notelist as text for debugging
#
#   &AddNote(note[,duration[,time[OnVelocity[,OffVelocity]]]]) 
#      Add a note to the NoteList and return the time of the note  
#      off. For example, to play middle C for the default time,  
#      after the last recorded note with on velocity 120 (loud)
#      and off velocity 64 (default)
#
#        $Notes1->AddNote(60,0,0,120,64); 
#
#   &Play([instrument_number][,title]) : Convert to a MIDI string
#     [for the given instrument] [with the given track title].
#
######################  Routines #######################

sub MakeMidiFile {
  my @tracks = @_;    
  my $out = &Header(1+@tracks).&Track(&Signature());
  foreach (@tracks) {
    next unless $_;  # Allow empty play lists please!
    $out .= &Track($_);
  }
  $out;
}

# Pass a string to &song(string,instrument) and it treats each 
# character as quarter note...

sub CreateMidi {
  my $string = shift;
  my $instrument = shift || 1;
  my $Notes = Midi->New($string);
  &MakeMidiFile($Notes->Play($instrument));
}

# Return an instrument select box (you may name the form field
# and decided which instrument is selected)

sub MidiInstrumentSelectBox {
   my $name = shift || 'Instrument';
   my $which_selected = shift;
   $which_selected = 104 unless defined $which_selected;
   (my $List = $MidiInstrumentList) =~ 
	s/<option>($which_selected .*)/<option selected>$1/m;
   "<SELECT NAME=$name>$List</SELECT>";
}

################## NoteList object/methods ################
#
# Internally a NoteList is an associative array of events where
#
#   keys=\d+ (the time from the start)
#       with value = comma delimited lists of one of the following
#       forms
#
#          \d+on\d+    or    \d+off\d+
#
#       where the first number is the note number, the second is 
#       the velocity with which it is turned on or off respectively. 

# Default velocity for notes on/off (per midi standard)

$NoteVelocityOn  = 64;
$NoteVelocityOff = 64;

# Create a new NoteList (make note list from the given string
# if one is passed).

sub New {
  my $type = shift;
  my $string = shift;  # If passed a string, use it to create notes
  my $self = bless {  }, $type;
  if (defined $string) {
    my ($count,$note) = 0;
    foreach $note (split(/ */,$string)) {
      $self->AddNote(ord($note));
    }
  } 
  $self;
}

# Print the NoteList for debugging

sub Print {  
  my $self = shift;
  foreach $time (sort {$a <=> $b} keys %$self) {
    print "At $time have $self->{$time}<BR>\n";
  }
}

# When is the last event in the NoteList?

sub Last {  
  my $self = shift;
  my $last = 0;
  foreach (keys %$self) {
    $last = $_ if $_ > $last;
  }
  $last;
}

# AddNote(note[,duration[,time[,velocity_on[,velocity_off]]]]) 
# Add a note to the NoteList and return the time of the note off.
# Velocity on/off are bytes with 64 as the default (via GM standard)

sub AddNote {  
  my $self = shift;
  defined (my $note = shift) or die "AddNote given no note!";
  my $duration = shift || 380;
  my $time = shift || $self->Last+4;
  my $VelocityOn = shift || $NoteVelocityOn;
  my $VelocityOff = shift || $NoteVelocityOff;

  # turn the note on
  if (defined $self->{$time}) {
     $self->{$time} .= ','.$note.'on'.$VelocityOn;
  } else {
     $self->{$time} = $note.'on'.$VelocityOn;
  }

  # turn the note off

  $time = $time+$duration;
  if (defined $self->{$time}) {
     $self->{$time} .= ','.$note.'off'.$VelocityOff;
  } else {
     $self->{$time} = $note.'off'.$VelocityOff;
  }
  $time;  # return ending time
}

# &Play([instrument_number]) : Convert to a MIDI string
#    [for the given instrument]

$channel = 1; # how do I get each on a different channel?
  # Should belong to the Midi object.  For now, just incremented
  # in &Play

sub Play {
  my $self = shift;
  my $instrument = shift;
  my $title = shift;

  # First get the $instrument and choose a channel number using the 
  # general midi standard with the addition of "0 percussion only"
  # which (by the standard) is the sole use of channel 10.  
  # Note that the instruments can be given by their numbers, 
  # or as the output from the &MidiInstrumentSelectBox (e.g.,
  # numbers in the text).

#  my $channel = ($Midi::channel == 9 and $instrument != 0 ? 
#	$Midi::channel = 11 : $Midi::channel++);  Does not work!

  my $channel = $Midi::channel++;

  if (defined $instrument and $instrument =~ /(\d+)/o) {  # extract the number
    $instrument = $1;
    if ($instrument == 0) { # recognize percussion only
      $channel = 10;
      $instrument = 1; 
    }
  } else {
    $instrument = 1;
  }

  my $out = '';  # Will store the output

  # 03 : Optional - imbed track name (should this be in &Track?)
  if (defined $title) {
    $out .= chr(0).chr(0xFF).chr(0x03).chr(length($title)).$title;
  }

  # 04 : Optional - imbed instrument name

  $out .= chr(0).chr(0xFF).chr(0x04).chr(length($instrument))
    .$instrument;

  # C? : Declare instrument (often called "program change")

  $out .= chr(0).chr(0xC0+$channel-1).&Byte($instrument-1,1);

  # Now convert NoteList to the midi format (three bytes:
  # (delta-t, 0x90+channel for on, 0x80+channel for off, 
  # then velocity).

  my ($time,$note,$action,$velocity);
  my $LastTime=0;
  foreach $time (sort {$a <=> $b} keys %$self) {
    foreach (split(/,/,$self->{$time})) {
      (($note,$action,$velocity) = /(\d+)([^\d]+)(\d+)/o) 
        or die "bad note format: $_";
      if ($action eq 'on') {
        $out .= WriteVariableLength($time-$LastTime). 
           chr(0x90+$channel-1).chr($note).chr($velocity);
      } elsif ($action eq 'off') {
        $out .= WriteVariableLength($time-$LastTime).
           chr(0x80+$channel-1).chr($note).chr($velocity);
      } else {
        warn "Unknow action in $_";
      }
      $LastTime = $time; # to calculate delta times.  The first
        # event in each comma delimited list must have the time 
        # from the previous list, but all the others happen 
        # simultaneously, so we reset the $LastTime here to get
        # delta times of zero.
    }
  }
  $out;
}

######################## Support Routines ####################
#
# &Header(tracks) returns the header string for format 1 (mult. simultaneous
# tracks) with the given number of tracks.

sub Header {    # Return "header chunk"
  $tracks = ($_[0] or 2);
  my $out;
  $out  = 'MThd';               # Required header string
  $out .= &Byte(6,4);           # Chunk length--always 6
  $out .= &Byte(1,2);           # Format 1, separate tracks
  $out .= &Byte($tracks,2);     # Number of tracks
  $out .= &Byte(384,2);         # division of a quarter note indicated by
          # delta-t's in stream.  If certian negative numbers, then indicates
          # clock rather than metric time--see MIDI format pages.                     
  return $out;
}

# &Signature(n,d,c,b,t) returns a signature track (time sig and tempo only)
# Time signature is for informational purposes only, does not alter sound output.
# tempo does though!

sub Signature {
  # Add appropriate meta events.  All meta events start "dt FF nn ll"
  # where dt is a delta time, 0xFF marks these as meta events, nn is the
  # number of the event, ll is the length of what follows.  
  # The following (except perhaps 51) are optional.
  my $out = '';

  # 02: Copyright notice (first event, first track)  
  $out .= chr(0).chr(0xFF).chr(0x02).chr(28).
    'PrimePages (C) 1997-2025';

  # 03: Sequence (track 1 of type 1) or track (otherwise) name
  $out .= chr(0).chr(0xFF).chr(0x03).chr(12).
    'Prime Sounds';

  # 04: Time signature is FF 58 04 nn dd cc bb (defaults to 4/4)
  my $n = &Byte($_[0]||4, 1);  # Time sig is $n/2^-$d, so   
  my $d = &Byte($_[1]||2, 1);  #    6/8 is (6,3).
  my $c = &Byte($_[2]||96, 1); # MIDI Clocks per Metronome tick
  my $b = &Byte($_[3]||8, 1);  # 32nd notes per MIDI quarter note
  $out .= chr(0).chr(0xFF).chr(0x58).chr(0x04).$n.$d.$c.$b;

  # 51 : Tempo signature is  FF 51 03 tttttt  microseconds per 
  # quarter note (or so says the docs)
  my $t = &Byte($_[4] or 500000, 3);
  $out .= chr(0).chr(0xFF).chr(0x51).chr(0x03).$t
}

# &Track(string) writes string as a track (adds MTrk, length and end track)

sub Track {
# Could imbed a track name with meta event 03
  my $string = $_[0];
  my $length = &Byte(length($string)+4,4);
  return ('MTrk'.$length.$string.chr(0).chr(0xff).chr(0x2f).chr(0));
  # tracks must end with the required meta event 0x00ff2f00 !
}
  
# &Byte(number,length) converts the number into a byte string of the given 
# number of bytes (=two hex digits).  default=2. 
# surely this can be done with sprintf !

sub Byte {
  my $number = $_[0] || 0;
  my $length = $_[1] || 2;
  my $out = '';
  my $i = 0;
  while ($i++ < $length) {
    $out = chr($number % 256).$out;
    $number = int($number/256);
  }
  warn("midi::Byte size error") if $number;
  $out;
}

# Midi stores delta-times in an odd way (using 7 bits per byte, first bit
# is 1 for all but the last byte). WriteVariableLengh(value) returns these 
# numbers crrectly formated.

sub WriteVariableLength {
  my $value = $_[0] || 0;
  my $out = chr($value & 0x7f); 
  while (($value >>= 7) > 0) {
     $out = chr(($value & 0x7f) | 0x80).$out;
  }
  $out;
}

################## Constants #######################

$MidiCgiHeader = '';   # stops -w from whining about appearing once
$MidiCgiHeader = "Content-type: audio/x-midi\n".
## $MidiCgiHeader = "Content-type: audio/midi\n".
"Expires: Sunday, 01-Jan-95 00:00:00 GMT\n\n"; # To (try to) stop caching
## "\n";

$MidiInstrumentList = "<option>0 (percussion only)
<option>1 Acoustic Grand\n<option>2 Bright Acoustic\n<option>3 Electric Grand
<option>4 Honky-Tonk\n<option>5 Electric Piano 1\n<option>7 Harpsichord
<option>6 Electric Piano 2\n<option>8 Clav\n<option>9 Celesta
<option>10 Glockenspiel\n<option>11 Music Box\n<option>12 Vibraphone
<option>13 Marimba\n<option>14 Xylophone\n<option>15 Tubular Bells
<option>16 Dulcimer\n<option>17 Drawbar Organ\n<option>18 Percussive Organ
<option>19 Rock Organ\n<option>20 Church Organ\n<option>21 Reed Organ
<option>22 Accordion\n<option>23 Harmonica\n<option>24 Tango Accordion
<option>25 Acoustic Guitar(nylon)\n<option>26 Acoustic Guitar(steel)\n
<option>27 Electric Guitar(jazz)\n<option>28 Electric Guitar(clean)\n
<option>29 Electric Guitar(muted)\n<option>30 Overdriven Guitar\n
<option>31 Distortion Guitar\n<option>32 Guitar Harmonics\n
<option>33 Acoustic Bass\n<option>34 Electric Bass(finger)\n
<option>35 Electric Bass(pick)\n<option>36 Fretless Bass\n<option>37 Slap Bass 1\n
<option>38 Slap Bass 2\n<option>39 Synth Bass 1\n<option>41 Violin\n
<option>40 Synth Bass 2\n<option>42 Viola\n<option>43 Cello\n
<option>44 Contrabass\n<option>45 Tremolo Strings\n
<option>46 Pizzicato Strings\n<option>47 Orchestral Strings\n
<option>48 Timpani\n<option>49 String Ensemble 1\n<option>50 String Ensemble 2\n
<option>51 SynthStrings 1\n<option>53 Choir Aahs\n<option>52 SynthStrings 2\n
<option>54 Voice Oohs\n<option>55 Synth Voice\n<option>56 Orchestra Hit\n
<option>57 Trumpet\n<option>58 Trombone\n<option>59 Tuba\n
<option>60 Muted Trumpet\n<option>61 French Horn\n
<option>62 Brass Section\n<option>63 SynthBrass 1\n<option>64 SynthBrass 2Oohs\n
<option>65 Soprano Sax\n<option>66 Alto Sax\n<option>67 Tenor Sax\n
<option>68 Baritone Sax\n<option>69 Oboe\n<option>70 English Horn\n
<option>71 Bassoon\n<option>72 Clarinet\n<option>73 Piccolo\n
<option>74 Flute\n<option>75 Recorder\n<option>76 Pan Flute\n
<option>77 Blown Bottle\n<option>78 Skakuhachi\n<option>79 Whistle\n
<option>80 Ocarina\n<option>81 Lead 1 (square)\n 
<option>82 Lead 2 (sawtooth)\n<option>83 Lead 3 (calliope)\n
<option>84 Lead 4 (chiff)\n<option>85 Lead 7 (fifths)\n
<option>86 Lead 8 (bass+lead)\n<option>89 Pad 1 (new age)\n
<option>90 Pad 2 (warm)\n<option>91 Pad 3 (polysynth)\n
<option>92 Pad 4 (choir)\n<option>93 Pad 5 (bowed)\n
<option>94 Pad 6 (metallic)\n<option>95 Pad 7 (halo)\n
<option>96 Pad 8 (sweep)\n<option>97 FX 1 (rain)\n
<option>98 FX 2 (soundtrack)\n<option>99 FX 3 (crystal)\n
<option>100 FX 4 (atmosphere)\n<option>101 FX 5 (brightness)\n
<option>102 FX 6 (goblins)\n<option>103 FX 7 (echoes)\n
<option>104 FX 8 (sci-fi)\n<option>105 Sitar\n
<option>106 Banjo\n<option>107 Shamisen\n<option>108 Koto\n
<option>109 Kalimba\n<option>110 Bagpipe\n<option>111 Fiddle\n
<option>112 Shanai\n<option>113 Tinkle Bell\n<option>114 Agogo\n
<option>115 Steel Drums\n<option>116 Woodblock\n<option>117 Taiko Drum\n
<option>118 Melodic Tom\n<option>119 Synth Drum\n<option>120 Reverse Cymbal\n
<option>121 Guitar Fret Noise\n<option>122 Breath Noise\n<option>123 Seashore\n
<option>124 Bird Tweet\n<option>125 Telephone Ring\n<option>126 Helicopter\n
<option>127 Applause\n<option>128 Gunshot";

1;
