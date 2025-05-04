package my;

# These functions prepend a HTML header *if* necessary (see variable
# $HeaderPrinted).

# &HTML_Header           returns Content-type: text/html\n\n
# &HTML_Title(title)     returns head and title
# &HTML_End              returns </body>...
# &HTML_print            prints (with print-but checks for header first)
# &message(title,text)   prints html page with given title, text AND EXITS!
# &punt(message)         prints message as an error page AND EXITS!

# Subroutines which must occur before HTTP header (so before the above)

# &SetCookie		place cookie (in header)

# Other subroutines

# &AddToHead(stuff)     adds the stuff to the HTML header (must be bfore printing it!)
# &GetCookies		reads the cookies (from ENV)
# &Expiry(months)       creates cookie expiration date "months" months from now

# &SetNoCache;		encourages browser not to cache, (must occur before
#                	HTML header)  

# &Location_Header(URL);              returns header to redirect to given URL
# &ReadParse([*In]) 
# &SendMail(to,subject,message,from,cc)  sends mail (only the 'to' is required)
# &ScriptName                         returns current script name as 
#       a URL, e.g., "action=".&my::ScriptName() within <FORM>
# &ShowHash(\%hash[,html])            return the hash as a text list,
#       or if the second variable exists, as a HTML <DL> list
#       e.g.,  &my::ShowHash(\%Item);   
# &Encrypt(string)                    encrypts a short string
# &Compare(string,encrypted_string)   compares to see if they match
#
###########################

$HeaderPrinted = 0;  # Global, true if header printed
$HeadStuff = '';     # Extra stuff to add to header

@DoW = ('Sun','Mon','Tues','Wednes','Thurs','Fri','Satur');
@MoY = ('Jan','Feb','Mar','Apr','May','Jun',
            'Jul','Aug','Sep','Oct','Nov','Dec');

sub SetNoCache {
  # Can not force no-cache, but can request it with a couple methods
  &punt('Attempt to add no-cache header after header printed') if $HeaderPrinted;
  print "Pragma: no-cache\nExpires: Tue, 26-Oct-1965 12:00:00\n"
}

sub Expiry {
  # &Expiry(months) gives the date in a format ready for cookies
  # months can be negative (dates past) 0 (now) positive and/or fractional
  my $time = (shift || 0)*2628000 + $^T;
  my($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime($time);
  $year += 1900; # Y2K okay
  sprintf("%sday, %02d-%s-%4d %02d:%02d:%02d GMT\n",
    $DoW[$wday], $mday, $MoY[$mon], $year, $hour, $min, $sec)
}

sub AddToHead {  # Untested
  &punt('Attempt to add to header after header printed') if $HeaderPrinted;
  $HeadStuff .= shift || '';
}

sub SetCookie {
  return;

  # Must have name and value, must be sent before MIME header
  # Cookies returned to sites that match domain and are a subdirectory of path
  my($name, $value, $months, $path, $domain, $secure) = @_;
  my $expiration = &Expiry($months) if $months;

  &punt('Attempt to set cookie after header printed') if $HeaderPrinted;
  &punt('Missing cookie name or value') unless ($name and $value);

  print "Set-Cookie: $name=$value";
  print "; expires=$expiration" if $expiration;  # defaults to end of session
  print "; path=$path" if $path;  # defaults to URL of routine creating
  print "; domain=$domain" if $domain;  # defaults to full domain of routine
  print "; secure" if $secure;  # defaults to false
  print "; \n";
}

sub GetCookies {
  # Returns a hash array of cookies
  my(%cookies,$key,$value);
  foreach (split (/; /,$ENV{'HTTP_COOKIE'}||'')) {
    ($key, $value) = split (/=/,$_);
    $cookies{$key} = $value;
  } 
  return %cookies;
} 

sub Location_Header {
  "Location: $_[0]\n\n"
}

sub HTML_Header {
  return '' if $HeaderPrinted;
  $HeaderPrinted = 1;
  "Content-type: text/html\n\n"
}

sub HTML_print {
  print &HTML_Header,@_
}

# ReadParse
# Reads in GET or POST data, converts it to unescaped text, and puts
# key/value pairs in %in, using '\0' to separate multiple selections
# Returns TRUE if there was input, FALSE if there was no input 
# UNDEF may be used in the future to indicate some failure.
# If a variable-glob parameter (e.g., *cgi_input) is passed to ReadParse,
# information is stored there, rather than in $in, @in, and %in.

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
      &punt("cgi-lib.pl: Request to receive too much data: $len bytes\n");
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
       &punt("cgi-lib.pl: Unknown request method: $meth\n");
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
    &punt("This routine can not handle multipart forms, use cgi-lib.pl\n");
  } else {
    &punt("cgi-lib.pl: Unknown Content-type: $ENV{'CONTENT_TYPE'}\n");
  }

  return scalar(@in)
}

sub ShowHash {
  my $Hash = shift;
  my $HTML = shift || 0;
  my $dt = "\n".($HTML ? '<DT>' : "\n");
  my $dd = "\n".($HTML ? '<DD>' : "\t");
  my $out = ($HTML ? '<DL>' : '');
  foreach (keys %$Hash) {
     $out .= $dt.$_.$dd.$$Hash{$_};
  }
  ($HTML ?  $out.'</DL>' : $out)
}

sub Encrypt {  
  $salt = substr($^T,7,2);  # Use seconds to choose a random method
  crypt($_[0],$salt)
}

sub Compare {
  $_[0] or warn "You did not specify the plain text string";
  $_[1] or warn "You did not specify the encrypted string";
  my $Salt = substr($_[1],0,2);
  crypt($_[0],$Salt) eq $_[1]
}

sub ScriptName {
  my $port='';
  if (exists $ENV{'SERVER_PORT'} and $ENV{'SERVER_PORT'} != 80) {
    $port = $ENV{'SERVER_PORT'};  # nonstandard port
  }
  'http://'.($ENV{'SERVER_NAME'} or 'unknown SERVER_NAME').$port.
      ($ENV{'SCRIPT_NAME'} or 'unknown SCRIPT_NAME');
}

# &SendMail(to[,subject[,message[,from[,cc]]]])

sub SendMail {  
  open(FILE,"|/usr/lib/sendmail -oi -t") 
    or &Punt("Fatal error: could not open mail!");
  (print FILE "From: $_[3]\n") if defined $_[3];

  print FILE 'To: ',$_[0],"\n";
  (print FILE "CC: $_[4]\n") if defined $_[4]; 
  print FILE 'Subject: ',($_[1]||'No Subject'),"\n\n",
      ($_[2]||'No message text.');
  close(FILE)
    or &punt("Fatal error: could not send mail! Error:",$?>>8," Signal: ",$?&255)
}

sub HTML_Title {
  my $title = shift || 'no title';
  my $out = &HTML_Header . "\n<HTML>\n<HEAD>\n<TITLE>$title</TITLE>\n$HeadStuff</HEAD>\n" . 
    "<BODY BGCOLOR=\"#CCFFFF\">\n<H1 ALIGN=CENTER>".$title."</H1>\n";
  foreach (@_) { $out .= "$_\n"; }
  $out
}

# list all the parameters passed and end the file

sub HTML_End {
  my $out = &HTML_Header;
  foreach (@_) { $out .= "$_\n"; }
  $out."\n</BODY>\n</HTML>"
}

sub punt {  # Print message @_[0] on a page with title Fatal Error
  message('Fatal Error',@_,
    '<p>Contact <a href=mailto:admin@t5k.org>the admin</a> if you need help.')
}

sub message {  # Print message @_[1...] on a page with title $_[0]
  my $out = &HTML_Title(shift || 'Message') . &HTML_End(@_);
  print $out;
  exit  # Tis what we usually do after printing a page (db.cgi requires it)
}

1;
