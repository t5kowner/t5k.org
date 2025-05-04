package compare;

# Contains &desc_cmp(a,b) which is like cmp but for prime-strings.  It
# will calculate full digital expansions when necessary as well as use
# the 'prime_blob' table (for stored expansions and unparsable expressions)

# Also &on_list(a[,log[,eps]]) which checks if the parsable description
# 'a' is a prime on the list (e.g., primes in AP--check they are there!)
# 'log' should be the log of 'a' (or omitted) and 'eps' and error bound
# for the logs (limits how far we search).  Make it large for a very slow
# (but definitely accurate) search.  Defaults to the value in constants.
# It first searches for one with that description--then uses the $eps...

# &on_list uses &desc_cmp centrally!

use bmath;	# &desc_cmp needs this exact math to compare close numbers
use parser; 	# &desc_cmp's path to bmath...
use LoadPrimes;	# &on_list must be able to look on the list!
use constants;	# &on_list needs a default $eps


# &desc_cmp is passed two prime descriptions and tries to order them by the
# text description alone (e.g., twin primes...).  Returns the usual -1,0,1
# plus undef for unable to tell (1 if the first is > second...).  
# Uses the full digit parser and 'prime_blob' able when necessary.

sub desc_cmp {
  my $a = shift or return undef;
  my $b = shift or return undef;

  # Why would we call this if the strings are identical?
  if ($a eq $b) {
    # warn "Identical primes in database: $a (\&compare::desc_cmp)\n";
    return 0;
  }

  # check for cases like 318032361*2^107001+1 and 318032361*2^107001-1
  # (that is: something+/-r and samething+/-s; so we just compare r and s)
  # Covers twins, triplets...

  # Easy case: r and s are digits

  if ($a =~ /^(.*?)([+-]\d{1,10})$/o) {
    my $start = $1; my $rest = $2;
    if ($b =~/^(.*?)([+-]\d{1,10})$/o and $1 eq $start) {
      return $rest <=> $2;     # Cannot return 0 (otherwise $a = $b)
    }
  }

  # check for cases like 1113672351*2^98305+1 and 1113695535*2^98305+1
  # (that is: r*something and s*samething; so we just compare r and s)
  if ($a =~ /^(\d{1,10})(\*.*)$/o) {
    my $start = $1; my $rest = $2;
    if ($b =~/^(\d{1,10})(\*.*)$/o and $2 eq $rest) {
      return $start <=> $1;	# Cannot return 0 (otherwise $a = $b)
    }
  }

  # Bad news if we are here!  Must call external full digit parser...

  # If these are blobs, then get the numbers.  Full digits in blobs have spaces...
  my $p;
  if ($a =~ /^"/o and $p = &LoadPrimes::LoadBlob($a)) {
    ($a = $$p{'full_digit'}) =~ s/\s+//go;
  }
  if ($b =~ /^"/o and $p = &LoadPrimes::LoadBlob($b)) {
    ($b = $$p{'full_digit'}) =~ s/\s+//go;
  }

  my $out;
  eval "\$out = &parser::parse(\"sgn(\$a-(\$b))\")";
  warn $@ if $@;
  # PARI crashes! my $out = &parser::parse("sgn($a-($b))");
  if (defined($out)) {
    return $out if ($out == 1 or $out == -1);
    if ($out == 0) {
      # warn "Equal primes in database: \n\t$a\n\t$b\n";
      return 0;
    }
  }

  warn "\nCould not compare: ".(defined($error) ? "log difference $error " : '').
	"(so desc_cmp returning undef)\n\t$a\n\t$b\n";
  return undef;
}

# Changes here should be reflected in the PHP version on_list.inc

sub on_list {
  my $desc = shift || '';
  my $log10 = shift || 0;
  my $eps = shift || $defaults::eps;
  my @Primes;

  unless ($desc) {
    warn "ERROR: &compare::on_list was not passed a prime description.\n";
    return undef;
  }

  # First, lets see if this exact description is there
  return ${Primes[0]}{'id'} if &LoadPrimes::Load(\@Primes,
	{ where  => "description = '$desc'", select => 'id' } );
  # This may be about 5% faster than the above
  #  if ($id = &connect_db::GetRow(
  #	{ where  => "description = '$desc'", columns => 'id' } )) { return $id; }

  # Sad, oh well, lets get the log base ten of the number to know what to compare to
  unless ($log10) {
    eval "\$log10 = &parser::parse(\$desc,'log only')";
    if ($@ or $log10 eq 'NaN') {  # Oh-oh!  eval error
      warn "ERROR: &compare::on_list was passed the unparsable string \"$desc\"\n";
      return undef;
    }
    $log10 = $log10/log(10);
  }
  # print "$desc has log_10 $log10 (\$eps is $eps)\n";


  # Bound the size we expect the log base 10 to be
  my $upper_limit = $log10*(1+$eps);
  my $lower_limit = $log10/(1+$eps);
  # print "seeking primes where log10 > $lower_limit and log10 < $upper_limit\n";

  # Grab all the primes within those bounds (return undef=not on list, if none)
  return undef unless &LoadPrimes::Load(\@Primes,
	{ where  => "log10 > $lower_limit and log10 < $upper_limit", 
	  sortby => "digits DESC, digit_rank ASC",
	  select => "id, description" });

  # Look through the list
  foreach my $p (@Primes) {
    my $cmp = &desc_cmp($desc,$$p{'description'});
    # print "checking against: $$p{'description'}, cmp is $cmp\n";
    return $$p{'id'} unless $cmp; # found it if $cmp == 0
    return undef if $cmp == 1;	  # Not on list of ours is bigger than all of them
  }
  undef;
}

1;
