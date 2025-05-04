#!/usr/bin/perl -w
my $version = 1.70;
use warnings;
use strict;
use POSIX 'WNOHANG';

use File::Basename;
our $script_name = basename($0);

# Make changes in verify.ini, not here!  Use ./pfgw... on LINUX

use FindBin;		# Find curent path (verify.ini... is in the same spot)
use lib "$FindBin::Bin";# make path accessible (allows relative paths too)

use HTTP::Request::Common qw(POST);
use LWP::UserAgent::Determined;     # Once I had to install LWP::Protocol::https using CPAN for this to work
use Getopt::Std;

getopts('AB:CDdEhH:Mn:Op:PstU:X:');
our ($opt_A, $opt_B, $opt_C, $opt_D, $opt_d, $opt_E, $opt_h, $opt_H, $opt_M, $opt_n, $opt_O,
	 $opt_p, $opt_P, $opt_s, $opt_t, $opt_U, $opt_X);

# Get the configuration data from verify.ini (follows the above
# so we can overide the command line in the ini file--e.g., in
# Windows).

srand;
# variables shared with verify
our ($pfgw, $machine, $clients, $direction, $person_id, $url, $type, $llr,
	$ecpp, $pari, $password);
require('verify.ini');


# Windows presents its own problems
my $System = ($pfgw =~ /\.exe$/ ? 'Windows' : 'Linux');

$opt_h and print "Usage: verify.pl [options] where the options are
	-h	print this help and exit
	-s	execute silently (except for errors to stderr)
	-d	very verbose mode
	-t	run tests but print results to screen rather than the server
	-M	tell the server not to alter the primes' modification date

	-n k	just test k numbers (use -n 0 to test without contacting server)
	-p k	test prime with id k (no matter its present status)
	-P	force server side parsing (so client gets a complete expansion)
	-H f	use the helper file named 'f' (useful for PFGW)
	-B s	example -B '108455*2^44393+1,108455*2^44393+3' builds and uses a
		helper file with those primes (note s is comma delimited list
		of one or more primes).
	-A 	when using pfgw, force use of -t  (n+1 case)
	-C	when using pfgw, force use of -tc
	-D	when using pfgw, force use of -tp (n-1 case)
	-E	use Morain's ECPP (dies if not available, will not use ECPP
		without this flag!)  If -p is not set, will seek only PRP's
		from server.  Sets -P (server-side parsing)
	-X s	a string parameter to add to pfgw call (e.g., -X '-x72835')

	-O	omit the already running check (be careful!)
	-U s	server is at url s rather than the url in verify.ini

Note that all of these flags can be set in the verify.ini file as well.
Note person_id, machine, url and clients should be set there.\n
(Running on $System)\n";
$opt_h and exit;

# Were the variables all set in verify.ini?

# Who is running this?
$person_id or &my_die("You must set \$person_id in verify.ini\n");
# Unique id for this machine/process
$machine = $machine || rand(100000);  # Should be unique...
# Easy (ASC) or hard (DESC) ones first?
$direction = $direction || 'ASC';
# Where is the prime list's interface?
$url	= $url || 'https://t5k.org/primes/verify.php';
$url    = $opt_U ? $opt_U : $url;  # can be overridden onthe command line
# What type of prime do we request?
$type	= $type || 'any';
# What clients are available on this machine?
$clients = $clients || 'pfgw';

$opt_O or &die_if_already_running;
# Why die?  Well, LLR is a real problem on LINUX, seems to be fine though on
# windows.  This is good becuase the die_if... routine does nothing on windoows
# and only works on LINIX.

if ($opt_p) {	# after a certian prime
  if ($opt_p =~ /^\d+$/) {
    $opt_n = 1;  	# set the number of primes to test to one
    $opt_s or print "Will test only the number with id=$opt_p.\n";
  } else {
    &my_die("Bad id given with -p");
  }
}
if ($opt_E) {	# not used in years
   defined($ecpp) or &my_die("The variable \$ecpp must be defined to use -E\n");
   -e $ecpp or &my_die("Could not find the ECPP script which should be in "
	."\$ecpp='$ecpp'\n");
   $opt_s or print "Will force the use of Morain's ECPP via $ecpp\n";
   $opt_P = 1;		# Let's make sure all is parsed on the server.
   $type = 'prp';	# Let's not waste this on easy primes!
   $direction = 'ASC';	# Start small
}
$opt_s or $opt_E or print "Clients: $clients\n";
$opt_s or print "Type: $type\n";
$opt_n = ((defined $opt_n and $opt_n >= 0) ? $opt_n : 99999);
$opt_s or $opt_n < 99999 and print "Test just $opt_n number(s).\n";
$opt_X = '' unless defined $opt_X;
$opt_t and ($opt_s or print "Will not return the results to the server, ".
	"just to the screen.\n");
$opt_M and ($opt_s or print "Will tell the server not to alter primes' "
	."modification dates.\n");

# Have we been passed an existing helper file (possible prime factors of n+/-1)
# via $opt_H ? If so and it is short, then add it to the notes. In either case,
# prepare $opt_H to be used in pfgw's comand line.

my $AddToNotes = '';

if ($opt_H) {  		# Helper file
  (-e $opt_H) or &my_die("File '$opt_H' does not exist.");
  (-r $opt_H) or &my_die("File '$opt_H' exists but is not readable.");
  $opt_s or print "Using the helper file '$opt_H'.\n";

  # If helper file is small enough, add to notes ('notes' is the certificate
  # stored in verify table entry on the server).

  my $old = $/;		$/ = undef;
  open(FILE,$opt_H) or &my_die($!);
  my $file = <FILE>;	$/ = $old;
  close FILE;
  my $temp = $file;
  $temp =~ s/(\d{35})(\d{20,})(\d{35})/$1.'...('.(length($2)+70).
	' digits)...'.$3/egs;
  $AddToNotes .= "\n\nHelper File:\n".$temp if length($temp) < 4096;
  $opt_H = " -h$opt_H ";  # prepare for use in PFGW
} else {
  $opt_H = '';  # Will use this below in the PFGW command line
}

# Perhaps instead we were asked to build a helper file from $opt_B
# which contains a comma delimited list of parsable expressions.

our $temp_helper = undef; # Global to &my_exit (which removes this file)

if ($opt_B) {
  $opt_s or print "Building a helper file from \"$opt_B\"\n";
  &my_die("Can not yet use $opt_B and $opt_H") if $opt_H;
  $AddToNotes .= "Helper file contains: \"$opt_B\"\n";

  # What shall we call the temp helper?
  $temp_helper = 'helper_file_'.($opt_p ? 'id_'.$opt_p : 'rand_'.rand(100000));

  foreach my $n (split(/\s*,\s*/,$opt_B)) {
    $opt_d and print "\t$n\n";
    `./parse2file.pl $temp_helper "$n"`;
    &my_die('parse2file.pl failed: '.$?) if $?; # Die if call returned an error
  }
  $opt_H = " -h$temp_helper ";  # prepare for use in PFGW
}

$opt_s or $opt_P and print "Forcing the server to send a decimal expansion.\n";


#############################################
##### 		Main loop		#####
#############################################


my $errors_left = 3;  # was 10 until 5/2021
my $result; 	# used in and after the main loop
my $override_clients = 0;
my $id;
# Most errors cause death anyway... but let's avoid an infinite error loop!

my $parse = 0; # Gets set to prime_id on errors and used in redo for this loop
	# for the case where we need to request the full digit expansion when,
	# for example, the pfgw parser fails

while ($errors_left > 0 and $opt_n-- > 0) {

  # First, request a number to process
  my %data = &get_number($type,$override_clients || $clients,$parse);
  my $time = time;

  # Grab the prime's description.  It is 'none' if there are no avaliable
  # numbers to test.
  my $desc = $data{'description'} ||
	&my_die("The server failed to respond with a prime's description.");
  my $digits = $data{'digits'} || '(not sent)';
  my $prime_id = $data{'prime_id'};
  if ($desc eq 'none') {
    $opt_s or print "No more primes currently available.\n";
    &my_exit($opt_s ? '' : "Done.\n");  # Want silent exit for $opt_s
  }
  $opt_s or print "Testing $desc ($digits digits, id $prime_id)\n";

  # Need an id to report a response (obviously unnecessary if $opt_t is set!)
  # Keep the old id if we overrode clients to prevent a new id from being made
  if (!$override_clients) {
    $id = $data{'id'}||$prime_id or $opt_t or
	&my_die("The server failed to respond with a verify entries id.");
  }
  # Now we need to decide how to prove (client parameters...)

  # We do this in three code blocks.  The first (below) creates the command (for
  # the various programs); the next executes it; the last reads the results
  # (again different for each program because we must interpret thier output).

  my $command = '';
  my $client = '';

  if ($opt_E) { 				# No choice, must ECPP
    # This is not ECPP itself, but a script to filter input/output...
    $client = 'ECPP_Morain';
    $command = "$ecpp $id $desc";

  } elsif ($clients =~ /\bllr\b/ and not $opt_H and (-e $llr)
	and $desc =~ /^(\d+\*|)2\^\d+\-1$/) {    # Here $1 contains the *
    # This is not llr itself, but a client to make it command line
    ### NOTE: LLR can now handle +/-, and multiplier is limited to 40 bits
    $client = 'llr';
    my $temp = ($1 ? '' : '1*');
    $command = "$llr $temp$desc";

  } elsif ($clients =~ /\bpfgw\b/) {		# pfgw is the main choice
    # These are not tested!  What should be allowed on a command line?
    my $line_buffer_max = 30000;
    $line_buffer_max = 300 if $System eq 'Windows';  # Darn Windows box ;-(

    # make sure the program exists
    (-e $pfgw) or &my_die("\$pfgw in verify.ini must point to pfgw.\n");

    # Translate terms that differ
    $desc =~ s/(\b[VU]\([^,]+,[^,]+,[^)]+\))/lucas$1/g;
    if ($desc =~ s/\bp(\(\d+\))$/numpart$1/) {
    # if ($desc =~ s/^p(\(\d+\))$/numpart$1/) {
      # This substitution will generate a pfgw error, but p(n) is the nth
      # prime in pfgw.  The error will force server-side parsing.
      sleep 1800;	# hopefully enough time for the blob to be created
      # on the server.
    }

    # set flags and command
    $client = 'pfgw';
    # Be nice to Windows machines
    my $flags = ($System eq 'Windows' ? "-n -V -f " : '-V -f ');
    # Will the prime fit on the command line?  If not, put it in a file.
    my $where = "-q\"$desc\"";	# '$desc' fails on Windows XP
    if (length($desc) > $line_buffer_max) {
      my $file = 'p_'.$prime_id.'.txt';
      open(FILE,">$file") or &my_die($!); 	# Open to overwrite
      print FILE $desc;
      close(FILE);
      $opt_d and print "Prime was written to the file '$file'.\n";
      $where = "$file";
    }
    if ($opt_C) {
      # On some primes we need to force two sided testing
      $opt_d and $opt_C and
	print "[-C was used so forcing use of -tc by pfgw]\n";
      $command = "$pfgw $flags -tc $opt_H $opt_X $where";
    } elsif ($desc =~ /\+1$/o or $desc =~ /^Phi\([^,]+,[^)]+\)$/o or $opt_A) {
      $opt_d and $opt_A and
	print "[-A was used so forcing use of -t by pfgw]\n";
      $command = "$pfgw $flags -t $opt_H $opt_X $where";
    } elsif ($desc =~ /\-1$/o or $opt_D) {
      $opt_d and $opt_D and
	print "[-D was used so forcing use of -tp by pfgw]\n";
      $command = "$pfgw $flags -tp $opt_H $opt_X $where";
    } else {  # Slow two-sided test
      $command = "$pfgw $flags -tc $opt_H $opt_X $where";
    }
    #  $flags = '-a1 -tc' if ($type eq 'n^2-1' or $type eq 'any');
    #  $flags = '-a1 -t -hhelper' if ($type eq 'special');

  } elsif ($clients =~ /pari\b/o) {

    # This is not pari itself, but a pari client which knows the grammar
    (-e $pari) or
	&my_die("\$pari in verify.ini must point to the pari client.\n");
    $client = 'pari';
    $command = "$pari '$desc'";

  } else {
    &my_die("Unknown client $client\n");
  }

  #######################
  ### Now do the work ###
  #######################

  $command .= " >command_output 2>&1";
  $opt_d and print "\nNow executing\n\t$command\n";
  my $pid = fork();
  if (!$pid) {
	  exec($command);
	  exit;
  }
  my $waited = 0;
  my $notes = "";
  $opt_d and print "Now waiting for $pid\n";
  while (waitpid($pid, WNOHANG) == 0) {
	  sleep(5);
	  $waited += 5;
	  if($waited % 900 == 0) {
		  (undef, undef ,$notes) = `cat command_output` =~ /(.|\n|\r)*(\n|\r)(.+)/;
		  $opt_d and print "Returning status update:\n$notes\n";
		  &return_result($id,"status",$notes);
	  }
  }
  $notes = `cat command_output`;
  $opt_d and print "Found:\n\n$notes\n";

  ########################
  ### Check the result ###
  ########################

  $result = 'error';	# Assume the worst
  if ($client eq 'pfgw') {
    $result = 'prime' if $notes =~ /is prime/o;
    $result = 'composite' if $notes =~ /is composite/o;
    $result = 'composite' if $notes =~ /factors: /o;
    $result = 'composite' if $notes =~ /small number, factored/o;
    $result = 'prp' if $notes =~ /is PRP!/o;
    $result = 'prp' if $notes =~ /is \d+\-PRP!/o;
    $result = 'prp' if $notes =~ /is Lucas PRP!/o;
    $result = 'prp' if $notes =~ /Fermat and Lucas PRP!/o;
    $result = 'failed' if $notes =~ /Evaluator failed/o;

    # If failed the first time, we might try letting the server parse it for us
    if (!$parse and ($result eq 'error' or $result eq 'failed')
	and $desc !~ /^\d+$/) {
      $parse = $data{'prime_id'} ||
	&my_die("Server failed to set 'prime_id' so can not redo");
	$override_clients = 'null'; #prevents another verify record from being made on the server
	redo;
    }

  } elsif ($client eq 'llr') {
    $result = 'prime' if $notes =~ /is prime!/o;
    $result = 'prp' if $notes =~ /is a probable prime/o;
    $result = 'composite' if $notes =~ /is not prime/o;
    $result = 'composite' if $notes =~ /has a small factor/o;

  } elsif ($client eq 'ECPP_Morain') {
    $result = 'prime' if $notes =~ /This number is prime/o;
    $result = 'composite' if $notes =~ /This number is composite/o;

  } elsif ($client eq 'pari') {
    $result = 'prime' if $notes =~ /prime/o;
    $result = 'prp' if $notes =~ /prp/o;
    if ($notes =~ /composite/o) {
       if ($type eq 'any composite') {
	$result = 'verified composite';
      } else {
	$result = 'composite';
      }
    }

  } elsif ($client eq 'pari2prp') {
    $result = 'prp' if $notes =~ /is a probable-prime\./o;
    $result = 'composite' if $notes =~ /is composite\./o;
  }

  # Unset so it does not bother the next number!
  $parse = 0;
  $override_clients = 0;

  if ($result eq 'error') {
      warn "ERROR READING PROGRAM RESPONSE!\n\"$notes\"\n".
	"Prime was $desc; verify id $id\n";
      $errors_left--;
  }
  if ($result eq 'failed') {
      warn "PROGRAM FAILED!\n\"$notes\"\n".
	"Prime was $desc; verify id $id\n";
      $errors_left--;
  }

  $time = time - $time;
  my $units = 'seconds';
  if ($time > 90) {
    $time = $time/60; $units = 'minutes';
    if ($time > 90) {
      $time = $time/60; $units = 'hours';
      if ($time > 36) {
        $time = $time/24; $units = 'days';
      }
    }
  }
  $opt_s or printf "%s (%0.2f %s)\n", $result, $time, $units;
  $notes .= sprintf "[Elapsed time: %0.2f %s]\n", $time, $units;

  # Clean up the notes

  if ($client eq 'pfgw' or $client eq 'pari2pfgw') {
    # (PWFG prints "description number/number" as progress note)
    my $match = quotemeta($desc);
    # N+1: 2245*2^227935-1 27500/227948 mro=0.0001831054688
    $notes =~ s/(F|PRP|N\+1|N\-1)?:? .*? \d+\/\d+\s*(mro=\d+\.\d+e?-?\d*|mro=0)?[\b\r\f]+//gm;
    $notes =~ s/(\d{10})\d+(\d{10})/$1...$2/sg;
    $notes =~ s/[\s\b\r\f]*\n[\s\b\r\f]*/\n/sg;
    $notes =~ s/\r+\d+\/\d+(?=[\r\f])/\r/gs;
  } elsif ($client eq 'pari') {
    $notes = "Pari's isprime() reports this number is $notes.";
  } elsif ($client eq 'pari2prp') {
    # Should be just fine
  } elsif ($client eq 'llr') {
    # 115029915*2^122892-1, iteration : 110000 / 122892 [89.50%].  Time per iteration : 0.422 ms.
    # 29412122415*2^333333-1, iteration : 80000 / 333333 [24.00%]. Time per iteration : 1.566 ms.
    $notes =~ s/\033\[7m//g;  # Used to reverse video in output of LLR.
    $notes =~ s/\033\[0m//g;
    $notes =~ s/\S+,\s*iteration\s*:\s*\d+\s+\/\s+\d+\s+\[\d*\.\d*\%\]\.\s+Time per iteration\s+:[ \d\.\w]+\r//gm;
    $notes =~ s/[\s\b\r\f]*\n[\s\b\r\f]*/\n/sgo;
  }

  # Get the results back to the server
  $notes .= $AddToNotes;  # Add a short helper file or anything?
  $notes = "Command: $command\n$notes" if (length($command) < 128);
  &return_result($id,$result,$notes);
}

# Don't want window to close on Windows machines (so we will die,
# not just print done and exit)

if ($errors_left > 0) {
  &my_die("\nDone.\n");
} else {
  &my_die("\nToo many errors! result=$result\n");
}

# &my_exit might do a little cleanup while exiting

&my_exit($opt_s ? '' : "Done.\n");  # Want silent exit for $opt_s



####################################################################
####			Support Routines			####
####################################################################

# &get_number(type,client,reparse) contacts the sever and seeks a number to
# check. Notice that 'type' and 'client' should match the fields required by
# $url (read it).  E.g., &get_number('n-1','pfgw').

# It returns a hash array of the key-value pairs (as described on $url)
# If reparse is defined, it should be a primes' database id; in
# that case we (re)request that very prime and ask that the
# server parse it first!

sub get_number {
# Uses globals $url, $person_id, $machine, $passsword and $direction from
# user configuration section (as well as globals $opt_d, $opt_s).

  my $type = shift || 'n-1';
  my $client = shift || 'pfgw';
  my $reparse = shift || '';

  # Fill in the form

  my $FormValues = [type => $type, person => $person_id, machine => $machine,
	client => $client, password => $password];
  # This means just test
  push(@$FormValues, silent => 'silent') if $opt_t;
  # modification date unchanged
  push(@$FormValues, unmodified => 'yes') if $opt_M;
  # Force parse because of command line?
  push(@$FormValues, parse => 1) if $opt_P;
  # Reparse flag set, redo last prime
  if ($reparse) {
     # with server-side parsing
     push(@$FormValues, prime_id => $reparse);
     push(@$FormValues, parse => 1) unless $opt_P;
  } elsif ($opt_p) {
     # -p used to force a prime id
     push(@$FormValues, prime_id => $opt_p);
  } else {
     # Ah, just grab the next prime
     push(@$FormValues, direction=>$direction);
  }
  my $req = POST $url, $FormValues;

  # Contact the server

  my $ua = LWP::UserAgent::Determined->new(
	ssl_opts => { verify_hostname => 0 },
  );
  $ua->timing("30,60,90");   # wait time between the retries
  $ua->agent("Prime Page Verifier $version");
  $opt_s or print "Contacting $url\n";

  my $result =  $ua->request($req)->as_string;
  (my $head, my $body) = ($result =~ /^(.*?)\n\n(.*)$/so);

  # Process the result from the server

  &my_die("Error (header did not include OK):\n\n$head\n\nurl = $url\n")
	unless ($head =~ /^HTTP\/\d\.\d 200 OK/);
  $opt_d and print "Success! Head is\n\n$head\n\n";

  # We expect at least a description and unless the description is 'none',
  # a numerical id

  if ($body =~ /^description (.*)(<br>|)$/mo) {
    &my_die("Error! Server response missing id:\n\n$body")
	unless ($1 eq 'none' or $body =~ /^id \d+<br>$/mo);
  } else {
    warn("Error! Server response missing prime description:\n\n$body");
    &my_die('Too many errors, limit reached in &get_number') if $errors_left-- < 0;
    $opt_d and print "Will retry in three minutes\n\n";
    sleep 180;
    return &get_number($type,$clients,$parse);
  }

  # Got a response; decode it into %data

  my %data; 	# Holds the data sent by the server
  foreach (split('<br>\n',$body)) {
    next unless /^(\w+) (.*)/;  # Ignore comments and improperly formated lines
    $opt_d and print "Server says: $1 is $2\n";
    $data{$1} = $2;
  }

  # Store into log

  # timestamp for log
  my $datestring = localtime();
  open(FILE,'>>verify.log') or &my_die($!);
  print FILE "On $datestring received: $body\n";
  close(FILE);

  %data;
}



# &return_results($id,$results,$notes) attempts to send the info back
# to the server.  Dies (with error message) or returns 1.

sub return_result {
  # If $opt_t is set, then we are not returning a result!
  my $id = shift or $opt_t or &my_die('Must specify id in &return_result');
  my $result = shift or &my_die('Must specify result in &return_result');
  my $notes = shift;

  # Store into log (just in case network fails...)  Note $opt_t is test only,
  # so do not return results to server

  open(FILE,'>>verify.log') or warn($!); 	# Don't die unless necessary!
  print FILE "returning\nid ".($opt_t ? '-t set, not reurning results' : $id).
	"\nresult $result\nnotes $notes\n";
  close(FILE);

  if ($opt_t) {  # test only!  So print the results and return
    print "returning\nresult $result\nnotes:\n$notes\n";
    return 1;
  }

  # Now contact the server

  my $ua = LWP::UserAgent::Determined->new(
	ssl_opts => { verify_hostname => 0 },
  );
  $ua->timing("30,60,90");   # wait time between the retries
  $ua->agent("Prime Page Verifier $version");
  $opt_d and print "Contacting $url\n";

  # Clean this up!
  my $FormValues = [ id => $id, result => $result, password => $password ];
  # Change the prime modification date?
  push(@$FormValues, unmodified => 'yes') if $opt_M;
  # Any notes to pass?
  push(@$FormValues, notes  => $notes) if (defined($notes) and $notes);

  my ($req, $head, $body, $webResult);
  $req = POST $url, $FormValues;
  $webResult =  $ua->request($req)->as_string;
  ($head, $body) = ($webResult =~ /^(.*?)\n\n(.*)$/so);

  # Sometimes fails, e.g., when our nameserver goes down, when PHP times out
  unless ($body =~ /result recorded/) {
    &my_die('Too many errors, limit reached in &return_result')
	if $errors_left-- < 0;
    $opt_d and print "Server responded:\n\n$webResult\n\nWill retry in five ".
	"minutes\n\n";
    sleep 300;
    return &return_result($id,$result,$notes);
  }

  $opt_s or print "Server notified about prime id $id\n";
  $opt_d and print "Server responded:\n\n$webResult\n";

  # 'Remove' log (because nothing is in progress)
  # Humm, not sure I want to remove it yet, lets just keep overwriting for now
  open(FILE,'>verify.log') or &my_die($!);
  # open(FILE,'>>verify.log') or &my_die($!);
  print FILE "\nstored\nid $id\nresult $result\n";
  close(FILE);

  1;
}

# Is it already running?

sub die_if_already_running {
  return if $System eq 'Windows';  # Don't know how to check on windows
  my $who = `whoami`;  chop $who;
  my $process = `ps -u $who | grep "$script_name"`;
  # $$ is the curent process id (It will show up here of course!)
  $process =~ s/$$.*?\n//sg;
  # chop of any trailing \n's
  $process =~ s/\n$//sg;
  if ($process !~ /^\s*$/) {
    exit if $opt_s;
    &my_die("Appears to be already running\n\"$process\"\n");
  }
}

# &my_die() is a replacement for die because on the Windows XP box die closes
# the window and error messages are gone.  On 95 and 98 I can use
# '(right click)->program->keep window open' but I can not find this on XP!

sub my_die {
  print @_;  # Prints the error string
  if ($System eq 'Windows') {  # Darn, a Windows box ;-(
    print "\nPress CTR-Z and ENTER to exit: ";
    die (<STDIN>);
  } else {
    exit;
  }
}

# &my_exit is for the same purpose--clean up; but don't close the window

sub my_exit {
  if ($temp_helper) {  # Did the -B option leave a temporary helper file behind
      unlink $temp_helper or
	warn "Failed to remove temp helper file $temp_helper";
  }
  &my_die(@_);
}
