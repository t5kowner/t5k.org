package menu;

# Just a single file to keep the menu bar for my midi programs
# Also &SlurpFile to read files and &ReadParse so this directory is
# independent of others.

my $server = 't5k.org';
my $base  = "/programs/music/listen/code";
my $MenuPrinted = 0;

# TitleAndMenu(title).  Follow with &End

sub TitleAndMenu {
  $MenuPrinted = 1;
  my $title=shift || 'unknown';
  my $out = "Content-type: text/html\n\n";
  $out .= &Head($title);
  $out .= "<BODY BGCOLOR=\"#FFFFFF\" BACKGROUND=\"$base/assets/background.gif\">\n";
  $out .= &Title($title);
  $out .= $Menu;
  $out .= "<TD>\n";
}

sub End {
  '</td></tr>
</table>
</body>
</html>';
}

sub Punt {
  if ($MenuPrinted) {
    print '<h2>Fatal Error</h2>';
  } else {
    print &TitleAndMenu('Fatal Error');
  }
  print @_, &End;
  exit;
}

sub Hear {
  "(<a href=page.cgi/hear.txt>no sound</a>?)"
}

sub SlurpFile {
  my $file = shift or return "No file specified.";
  if (not -e $file) {
    return "File does not exist: $file.\n";
  } elsif (not -r $file) {
    return "File not readable: $file.\n";
  } elsif (not open(FILE,$file)) {
    return "Could not open: $file.\n";
  } else {       # yes--we have succesfully opened a file !
    my $out = <FILE>;               # slurp file
    if ($out =~ /^Title\s*=\s*(.*)$/) {
      $Title = $1;
      $out = '';  
    }
    $/ = undef;
    $out .= <FILE>;               # slurp file
    close(FILE);
    return $out;
  }
 "Failed to read $file.\n";
}

# That's it!

$Menu = '<TABLE CELLPADDING=10><TR VALIGN=TOP>
  <TD><b>Sounds:</b>
    <ul>
      <li><A HREF="'.$base.'/index.cgi" class=menu 
	title="MIDI information and related pages">Index</a> 
      <li><A HREF="'.$base.'/primes.cgi" class=menu 
	title="A program to make Prime-al Sounds">Primes</A>
      <li><A HREF="'.$base.'/sequence.cgi" class=menu 
	title="Plays the factorization of terms">Sequences</A>
      <li><A HREF="'.$base.'/midi.cgi" class=menu 
	title="Converts character strings to sounds">Strings</A>
    </ul>
    <IMG SRC="'.$base.'/assets/space2.gif" ALT="" HEIGHT=1 WIDTH=120>
    <BR><B>Prime Pages:</b>
    <ul>
      <li><A HREF="/" class=menu>Home</A>
      <li><A HREF="/search/" class=menu>Search</A>
      <li><a href="/primes/" class=menu>Top 5000</a>
    </ul>
  </TD>
';

sub Head {
  my $title=shift || 'unknown';
'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<HTML>
<HEAD>
  <link rel=stylesheet type="text/css" href="'.$base.'/assets/template.css">
  <title>'.$title.'</title>
</HEAD>
';
}

sub Title {
  my $title=shift || 'unknown';
'<TABLE CELLPADDING=5 WIDTH="100%">
  <TR>
    <TD WIDTH="40"></TD>
    <TD>
      <TABLE BORDER=2 CELLPADDING=10 >
        <TR>
        <TD WIDTH="10"></TD>
        <TD BGCOLOR="#FFFFFF"><B><FONT SIZE=+2>
          '.$title.'</FONT></B><BR>(another of the <A
          HREF="/">Prime Pages</A>)
        </TD>
        </TR>
      </TABLE>
    </TD>
    <td align=center>
      <a href="index.cgi"><img src="'.$base.'/assets/speakers.gif"
      alt="Midi Sounds" border=0 width=161 height=80></a>
    </td>
  </TR>
</TABLE>';
}

# Taken from cgi-lib.pl: (with much code removed, e.g., multi-part forms)
# Perl Routines to Manipulate CGI input
# S.E.Brenner@bioc.cam.ac.uk   
# $Id: cgi-lib.pl,v 2.4 1996/01/21 20:20:59 brenner Exp $
# Copyright (c) 1996 Steven E. Brenner

sub ReadParse {
  local (*in) = @_ if @_;
  
  # Get several useful env variables

  my $len = ($ENV{'CONTENT_LENGTH'} || 0);
  if ($len > 131072) {
      &Punt("cgi-lib.pl: Request to receive too much data: $len bytes\n");
  }

  my $type = ($ENV{'CONTENT_TYPE'} || '');
  if ($type eq 'application/x-www-form-urlencoded' || $type eq '' ) {
    local ($key, $val, $i);

  # Disable warnings as this code deliberately uses local and environment
  # variables which are preset to undef (i.e., not explicitly initialized)
  $perlwarn = $^W;
  $^W = 0;
  
    # Read in text
  
    my $meth = $ENV{'REQUEST_METHOD'};
    if ($meth eq 'GET') {
      $in = $ENV{'QUERY_STRING'};
    } elsif ($meth eq 'POST') {
        read(STDIN, $in, $len);
    } else {
       &Punt("cgi-lib.pl: Unknown request method: $meth\n");
    }

    @in = split(/[&;]/,$in);

    foreach $i (0 .. $#in) {
      # Convert plus to space
      $in[$i] =~ s/\+/ /g;
  
      # Split into key and value.
      ($key, $val) = split(/=/,$in[$i],2); # splits on the first =.
  
      # Convert %XX from hex numbers to alphanumeric
      $key =~ s/%(..)/pack("c",hex($1))/ge;
      $val =~ s/%(..)/pack("c",hex($1))/ge;
  
      # Associate key and value
      if (defined($in{$key})) { # \0 is the multiple separator
         $in{$key} .= "\0".$val;
      } else {
         $in{$key} = $val;
      }
    }
  $^W = $perlwarn;
    
  } elsif ($ENV{'CONTENT_TYPE'} =~ m#^multipart/form-data#) {
    # for efficiency, compile multipart code only if needed
    &Punt("This routine can not handle multipart forms, use cgi-lib.pl\n");
  } else {
    &Punt("cgi-lib.pl: Unknown Content-type: $ENV{'CONTENT_TYPE'}\n");
  }

  return scalar(@in)
}

1;
