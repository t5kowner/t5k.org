package primes;

# Routines
#
#  &NextPrime(x)  =  the next prime after x (any real or undef)
#  &Prime(x) = 1 if x is prime  (x any integer)
#  &Factor(n) returns an associative array of the prime divisors of n
#  $DisplayFactors(%factors) formats output of above into a nice string

# $DisplayFactors(\%factors) formats the output of &Factor into
# a "nice" string for printing using HTML

sub DisplayFactors {
  my $factors = shift;
  my $out  = '';
  my $mult = '';   # (will contain) multiplication symbol
  foreach (sort {$a <=> $b} keys %{$factors}) {
    $out .= $mult.$_;
    $$factors{$_} == 1 or $out .= '<sup>'.$$factors{$_}.'</sup>'; 
    $mult = '<sup>.</sup>';
  }
  $out;
}

# &Factor(integer n) returns a pointer to an associative array of the 
# form $divisor{prime p}=power to which p divides n.  This
# is trial division and only works for small numbers.

sub Factor {
  my $n = shift or die "Factor: zero or no argument!";
  my $out = { };  # reference to a hash

  if ($n <= 1) {    # exceptional cases: one, negative
     $n = -$n if $n < 0;
     return $out if $n < 2;
  }

  my ($i,$p);
  for $i (0..$NumberOfPrimes-1) {   # Use the prime table
    $p = $Primes[$i];
    while ($n % $p == 0) {
      $$out{$p} = ($$out{$p}||0) + 1;
      $n /= $p;
      return $out if ($n == 1);  # done?
    }
    if ($p*$p > $n) {  # n is now prime
      $$out{$n} = 1; 
      return $out;  # done.
    }
  }

  my $sqrt = int(sqrt($n));
  while ($p < 100000) {          # Now lets wheel factor
    $p += 2;
    while ($n % $p == 0) {
      (exists $$out{$p}) ? $$out{$p} +=1 : $$out{$p} = 1;  
      $n /= $p;
      return $out if ($n == 1);  # done?
      $sqrt = int(sqrt($n));
    }
    if ($p > $sqrt) {  # n is now prime
      $$out{$n} = 1;  
      return $out; 
    }
  }
  unless (&prp($n)) {
    warn "factor gave up on $n -- calling it prime but it is composite";
  }
  $$out{$n} = 1;
  $out;
}

$NumberOfPrimes = 168;

@Primes = ( 2,  3,  5,  7, 11, 13, 17, 19, 23, 29, 
           31, 37, 41, 43, 47, 53, 59, 61, 67, 71,
           73, 79, 83, 89, 97,101,103,107,109,113,
          127,131,137,139,149,151,157,163,167,173,
          179,181,191,193,197,199,211,223,227,229,
          233,239,241,251,257,263,269,271,277,281,
          283,293,307,311,313,317,331,337,347,349,
          353,359,367,373,379,383,389,397,401,409,
          419,421,431,433,439,443,449,457,461,463,
          467,479,487,491,499,503,509,521,523,541,
	  547,557,563,569,571,577,587,593,599,601,
	  607,613,617,619,631,641,643,647,653,659,
	  661,673,677,683,691,701,709,719,727,733,
	  739,743,751,757,761,769,773,787,797,809,
	  811,821,823,827,829,839,853,857,859,863,
	  877,881,883,887,907,911,919,929,937,941,
	  947,953,967,971,977,983,991,997
);

# NextPrime(x)  = the next prime after x (any real or undef) 

sub NextPrime {
  local $n = ($_[0] or 0);
  if ($n < 2) {
    return 2;
  } elsif ($n == 2) {
    return 3;
  } elsif (($n & 1) == 0) {
    return &NextPrime($n-1);
  } elsif ($n < $Primes[$NumberOfPrimes-1]) {
    local $i = 0;
    while ($n >= $Primes[$i]) { $i++; }
    return $Primes[$i];
  } else {
    while (not &Prime($n+=2)) { };
    return $n;
  }
}

# PrevPrime(x)  = the largest prime less than x (any real or undef)                  
    
sub PrevPrime {
  local $n = ($_[0] or 0);
  if ($n <= 2) {
    return undef;
  } elsif ($n == 3) {
    return 2;
  } elsif (($n & 1) == 0) {
    return &PrevPrime($n+1);
  } elsif ($n <= $Primes[$NumberOfPrimes-1]) {
    local $i = 0;
    while ($n > $Primes[$i]) { $i++; } 
    return $Primes[$i-1];
  } else {
    while (not &Prime($n-=2)) { };   
    return $n;
  }
}

#  &Prime(x) = 1 if x is prime  (x any integer)

sub Prime {
  local $n = ($_[0] or 0);
  if ($n == 2 || $n == 3) { # recognize 2, 3
     return 1;
  } elsif (($n & 1) == 0) { # even now means composite
     return 0; 
  } elsif ($n > 4) { # odd integer >=5 
     my $i = 0;  # already checked 2 
     my $sqrt = int(sqrt($n));
     while (++$i < $NumberOfPrimes) {
       if ($n % $Primes[$i]) {
         return(1) if ($Primes[$i] > $sqrt); # divided far enough to show primality
       } else {
         return (($n==$Primes[$i]) ? 1 : 0); # found a factor (prime iff factor = $n)
       }
     }
     $p = $Primes[$NumberOfPrimes-1];
     while ($p <= $sqrt) {          # Now lets wheel factor
       $p += 2;
       return(0) if ($n % $p == 0);
     }
     return 1;	# Now proven prime, we wheel factored past to the sqrt.
  }
  0;  # 0 and negatives arrive here 
}

sub prp {
  $warned_once or warn "using unwritten prp";
  $warned_once = 1;
  1;
}

# This is not right perhaps?
# sub Jacobi {
  # Jacobi($a,$n) where $a is an integer and $n a positive odd integer.
#  my $a = shift;
#  return(1) if $a == 0; 
#
# my $n = shift; # Must be odd
# return(1) if ($n == 1);
#
# die("negative base $n in Jacobi symbol") if ($n < 0);
# die("even base $n in Jacobi symbol") if ($n % 2 == 0);
#
# my $j = 1;
# while ($a != 0) {
#   while ($a % 2 == 0) {
#     $a = $a/2;
#     $j = -$j  if ($n % 8  == 3  or  $n % 8 == 5);
#   }
#   my $t = $a; $a = $n; $n = $t;
#   $j = -$j if ($a % 4 == 3 and $n % 4 == 3);
#   $a = $a % $n;
# }
#  if ($n == 1) { return($j) } else { return(0) }
#}

1;


