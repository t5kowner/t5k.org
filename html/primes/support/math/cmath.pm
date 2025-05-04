package math;

use primes;

# The idea is to place the math routines here so that we can just change
# this to change the type of math we are doing (integer, big integer,
# digits only...)

# Routines in this module
#
#   parser support functions
#
#	preprocessor(struing)	preprocessing for the parser (e.g., 2i to 2*i)
#	new(x,[y])		create a number object $n = x+iy
#	FunctionEvaluator	evaluate functions (GlobalString parser is odd)
#	pop_atom()		pops (so defines) atoms for the parser
#	show($n,[style])	displays the number object(s) $n in the request style
#
#   recognizers (not user functions!)
#
#	is_one, is_zero, is_real, is_positive
#
#   fundamental arithmetic operations (user functions + internal, that is,
#   they both accept and return number objects).
#
#	neg(z)			negation
#	conjugate(z)		find the complex conjugate
#	inc(z)			add one (used in multiplication)
#	add, sub, mul, div	four basic binary operations
#	sqrt_(z)		principle square root (externally sqrt)
#	pow(z,w)		exponentiate z^w
#	exp_(z)			e^z (externally called exp)
#	log_(z)			principal branch of the complex log (externally log)
#	N(z)			complex norm (absolute value squared)
#	gamma, log_gamma	the usual gamma function (and its complex log)
#	cyclotomic(n,x,y)	the nth cyclotomic poly of x and y.
#	zeta(z)			the Rieman zeta function
#
#   basic functions for reals only (user functions + internal)
#
#	floor_(z)		floor function or real part of z (externally floor).
#
#   internal use only
#
#	__real(z)		returns the real part of z as a real

use linear;
use other;
use List::Util qw[min max];

use constant PI 	=> 3.1415926535897932384626433832795;	# pi eh?
use constant TO_DEGREES	=> 57.295779513082320876798154814105; 	# 180/pi
use constant LOG_10 	=> 2.3025850929940456840179914546844;	# ln(10)

use constant COMPLEX_ZERO => {'ln_r' => 'NInfinity', 'theta' => 0};	# 0
use constant COMPLEX_ONE  => {'ln_r' => 0, 'theta' => 0};		# 1
use constant COMPLEX_E    => {'ln_r' => 1, 'theta' => 0};		# e
use constant COMPLEX_I	  => {'ln_r' => 0, 'theta' => 90};		# i
use constant COMPLEX_PI   => {'ln_r' => log(PI), 'theta' => 0};		# pi
use constant COMPLEX_HALF => {'ln_r' => log(1/2), 'theta' => 0};	# 1/2

# Sometimes we need a preprocessor--this routine will be called before
# the parser.  For example, we might want to modify "2+3i" to make the
# implied multiplication explicit with s/(\d+)i\b/$1*i/go

sub preprocessor {
  my $string = shift;
  $string =~ s/(\d+)i\b/$1*i/go;
  $string;
}

# This module will represent complex numbers as
#
#	{'ln_r' => ln(r), 'theta' => argument}
#
# so multiplication is easy but addition is hard.  Note the argument
# is stored in degrees!  (This is so there is no error in representing
# i or calculating -z from z--in radians there would be roundoff
# errors from pi.

# Values are all complex and NaN (not a number).  ln(r) can be NInfinity
# (for zero).  The argument must be kept between -180 and 180.

sub new {
  # &new(a,b) is a + bi, return as the number object {ln_r, theta}
  # WARNING: if b is undefined it assumes b=0 (a feature, not a bug)
  defined (my $a = shift()) or return 'NaN';
  defined (my $b = shift()) or return &new($a,0);
  my $max  = max(abs($a),abs($b));
  if ($max == 0) { return COMPLEX_ZERO; }
  my $norm = $max*sqrt(($a/$max)**2+($b/$max)**2);
  if ($norm == 0) {			# Possible?
    return COMPLEX_ZERO;
  } elsif ($a == 0 and $b == 1) {       # Let's get i correct!
    return COMPLEX_i;
  } else {
    return({'ln_r' => log($norm), 'theta' => atan2($b,$a)*TO_DEGREES});
  }
}


sub FunctionEvaluator {
  # Note: what is passed to the function is either a single 'number'
  # or (if more than one operator) an array

  $root = shift();
  $root =~ s/^(log|exp|floor|sqrt)/$1_/o;	# Protect function names in perl
  $function = "math::$root";
  return &$function(@_) if (defined &$function);
  $function = "linear::$root";
  return &$function(@_) if (defined &$function);
  $function = "other::$root";
  return &$function(@_) if (defined &$function);

  &parser::my_die("undefined function $root (not in 'math.pm', 'linear.pm' or 'other.pm')");
}

# &pop_atom looks in the parse GlobalString for either an unsigned real
# or ??????.
# If it fails to find an atom (e.g., next is an operator), then it
# returns 'NaN' (not a number).  Otherwise it removes whatever it
# finds from the parse GlobalString $parser::GlobalString.

sub pop_atom {
   if ($parser::GlobalString =~ /^(\d+\.?\d*)/o or $parser::GlobalString =~ /^(\d*\.\d+)/o) {
     # Found unsigned real (signs already removed as operators)
     $parser::GlobalString = $';
#print "zzz pop_atom found '$1'\n";
     return &new($1);

  } elsif ($parser::GlobalString =~ /^(\w+)($|(?=[^\(\w\d]))/o) {
    #  Should be a constant
    $parser::GlobalString = $';
    my $constant = $1;
    return COMPLEX_I   if  $constant eq 'i';
    return COMPLEX_E   if  $constant eq 'e';
    return COMPLEX_PI  if  $constant eq 'pi';
    return &new(0.57721566490153286061)  if $constant eq 'gamma';
    return 'NaN'       if  $constant eq 'NaN';
    return &parser::my_die("Unknown constant \"$constant\".");
  } else {
    'NaN';
#   &parser::my_die("Expected an atom (real number or pre-defined constant).");
  }
}

# Here just because the string processor needs something different!

sub parenthesis {
  shift()
}

# display routines

sub show {
  # A routine to return the value as a displayable string (not ended in a carriage return)
  # If the second parameter is set, then it shows both internal and external formats
  # (this is a multiline display and does not work with lists and NaN's).

  return('NaN') if ((my $z = shift()) eq 'NaN');	# same for all styles

  if (ref($z) eq 'ARRAY') {
    # Ah, an array, should be the result of the list operator: ','
    my $list = 'LIST: ';
    foreach (@$z) { $list .= show($_).', ' }
    chop($list);chop($list);
    return $list;
  }

  # The usual case --- a complex number
  my $style = shift() || 0;	# Internal and external format?

  return $$z{'ln_r'} if ($style eq 'log only'); # Just the log?
  return int(1+$$z{'ln_r'}/log(10)) if ($style eq 'digits'); # Not useful I'd bet!
  return (&__real($z) <=> 0) if ($style eq 'sign only'); # Just the sign? Ignores complex part.

  my $r = ($z->{'ln_r'} eq 'NInfinity' ? 0 : exp($z->{'ln_r'}));
  my $theta = $z->{'theta'};
  my $a = $r * cos( $theta/TO_DEGREES );
  my $b = $r * sin( $theta/TO_DEGREES );
  if ($style eq 'number object') {
    return $z;
  } elsif ($style) { # 'html', 'both' or 'internal too'
    my $out = "\tinternal (ln_radius, argument) = ($$z{ln_r}, $theta deg)\n";
    $out .= "\t(so the norm has ".($$z{ln_r}/LOG_10 + 1)." digits)\n" unless $z->{'ln_r'} eq 'NInfinity';
    $out .= "\twhich is ".(&is_real($z) ? $a : "$a + $b*i");
    $out =~ s/\n/<br>\n/gios if ($style eq 'html');
    $out =~ s/\t/<li>\n/gios if ($style eq 'html');
    $out;
  } else {
    return &is_real($z) ? $a : "$a + $b*i";
  }
}

## odd ball internal only

  sub __real {
    # Takes a number object and returns the real part (as a real)
    return 0 if &is_zero(my $z=shift());
    my $cos_theta = ($z->{'theta'} == 180 ? -1 : cos( ($z->{'theta'})/TO_DEGREES ));
    exp($$z{'ln_r'})*$cos_theta;
  }

# recognizers (return 0 or 1)

sub is_one {
  # Is the number EXACTLY one?
  my $z = shift() or return(0);
  # Last condition includes 'NaN' because in a numeric context a string == 0
  return 0 if $z->{'ln_r'} eq 'NInfinity';
  $z->{'ln_r'} == 0 and $z->{'theta'} == 0
}

sub is_zero {
  # Is the number EXACTLY zero?
  my $z = shift() or return(0);
  return(0) if $z eq 'NaN';
  $z->{'ln_r'} eq 'NInfinity'
}

sub is_real {
  # I am not sure how to allow for errors when testing real... Does it matter?
  my $z = shift() or return(0);
  return(0) if $z eq 'NaN';
  return(1) if $z->{'ln_r'} eq 'NInfinity';
  $z->{'theta'} == 0 or $z->{'theta'} == 180;
}

sub is_positive {
  # Looking for a positive real--EXACTLY
  my $z = shift() or return(0);
  return(0) if $z eq 'NaN';
# warn("$parser::GlobalString, from $parser::GlobalOldString") unless defined $z->{'ln_r'};
  return(0) if $z->{'ln_r'} eq 'NInfinity';
  $z->{'theta'} == 0
}

# Basic functions

sub neg {
  # Negate the complex number z.
  defined (my $z = shift()) or return 'NaN';
  return $z if ($z eq 'NaN' or &is_zero($z));
  my $theta = ($z->{'theta'} > 0 ? $z->{'theta'} - 180 : $z->{'theta'} + 180);
  {'ln_r' => $z->{'ln_r'}, 'theta' => $theta}
}

sub conjugate {
  # complex conjugation: just change theta to -theta (execpt for theta=180)
  defined (my $z = shift()) or return 'NaN';
  my $theta = ($z->{'theta'} != 180 ? - $z->{'theta'} : 180);
  {'ln_r' => $z->{'ln_r'}, 'theta' => $theta}
}

#sub real {
#  # Takes a number object and returns the real part (as an object)
#  return COMPLEX_ZERO if &is_zero(my $z=shift());
#  my $cos_theta = ($z->{'theta'} == 180 ? -1 : cos( ($z->{'theta'})/TO_DEGREES ));
#  my $r =
#    exp(
#$$z{'ln_r'})*$cos_theta;
#  }
#}

sub inc {
  # adds one to the parameter
  defined (my $z = shift()) or  return 'NaN';
  $z ne 'NaN' or return 'NaN';
  return COMPLEX_ONE if &is_zero($z);	#  Must not be zero for the next step

  # If very large, don't bother adding...
  return $z if ($z->{'ln_r'} > 200);

  # Find the radius, watch for underflow
  my $r = ($z->{'ln_r'} eq 'NInfinity' or $z->{'ln_r'} < -500 ? 0 : exp($z->{'ln_r'}));

  # Now evaluate $z + 1 = (1 + r cos theta) + (r sin theta) i
  # Force the sin(180 degrees) to be zero (was getting a small error... so 5-2 was
  # the complex number 3 + 2.44921270764475e-16*i)
  my $cos_theta = ($z->{'theta'} == 180 ? -1 : cos( ($z->{'theta'})/TO_DEGREES ));
  my $sin_theta = ($z->{'theta'} == 180 ?  0 : sin( ($z->{'theta'})/TO_DEGREES ));
  my $ln_r = 1 + 2*$r*$cos_theta + $r*$r;	# a^2 + b^2
  return COMPLEX_ZERO if $ln_r == 0;
  $ln_r = 0.5 * log($ln_r);	# Good, modulus not zero, lets take the log
  my $theta = atan2($r*$sin_theta, 1 + $r*$cos_theta );
  { 'ln_r' => $ln_r, 'theta' => $theta*TO_DEGREES }
}

sub add {
  defined (my $z1 = shift()) or return 'NaN';
  return &add($z1->[0],$z1->[1]) if ref($z1) eq 'ARRAY';  # Called by user!
  defined (my $z2 = shift()) or return 'NaN';
  return($z1) if &is_zero($z2) or $z1 eq 'NaN';
  return($z2) if &is_zero($z1) or $z2 eq 'NaN';
  # Put the larger in $z1
  if ($z1->{'ln_r'} < $z2->{'ln_r'}) { my $z = $z2; $z2 = $z1; $z1 = $z; }
  # Now use z1 + z2 = z1*(1 + z2/z1)
  &mul($z1,&inc(&div($z2,$z1)));
}

sub sub {
  return &sub($_[0][0],$_[0][1]) if ref($_[0]) eq 'ARRAY';  # Called by user!
  return &add(shift(),&neg(shift()));
}

sub repeat {
  # Want  4_5 = 4444 for prime curios
  my $n = shift;
  my $k = new( int( ($n->{'ln_r'})/log(10) + 1 ) );
  my $m = shift;
  &mul($n,
    &div (
      &sub( &pow(&new(10),&mul($k,$m)), &new(1)),
      &sub( &pow(&new(10),$k), &new(1) )
    )
  )
}


sub concat {
  # want 4444&555 = 4444555 for prime curios
  my $n = shift;
  my $m = shift;
  my $k = new( int( ($m->{'ln_r'})/log(10) + 1 ) );
  &add(&mul($n, &pow(&new(10),$k)),$m)
}


sub mul {
  return &mul($_[0][0],$_[0][1]) if ref($_[0]) eq 'ARRAY';  # Called by user!
  defined (my $z1 = shift()) or return 'NaN';
  defined (my $z2 = shift()) or return 'NaN';

  # Multiplying by 0 and 1 is easy
  return($z1) if (&is_zero($z1) or $z1 eq 'NaN');
  return($z2) if (&is_zero($z2) or $z2 eq 'NaN');
  # We have $z1 = 1 when evaluating power series for &gamma ...
  return($z1) if &is_one($z2);
  return($z2) if &is_one($z1);

  # Otherwise, add the angles
  my $theta = $z1->{'theta'} + $z2->{'theta'};
  $theta -= 360 if $theta > 180;	# Need argument between -180 and 180 INCLUSIVE
  $theta += 360 if $theta <= -180;	# (-180 could come from &div)

  # Add the logs
  my $ln_r = $z1->{'ln_r'} + $z2->{'ln_r'};

  {'ln_r' => $ln_r, 'theta' => $theta }
}

sub div {
  return &div($_[0][0],$_[0][1]) if ref($_[0]) eq 'ARRAY';  # Called by user!
  defined (my $z1 = shift()) or  return 'NaN';
  $z1 ne 'NaN' or return 'NaN';
  defined (my $z2 = shift()) or return 'NaN';
  return('NaN') if &is_zero($z2) or $z2 eq 'NaN';
  &mul($z1,{'ln_r'=>-$z2->{'ln_r'},'theta'=>-$z2->{'theta'}});
}

sub exp_ {
  defined (my $z = shift()) or return 'NaN';

  return COMPLEX_ONE if &is_zero($z);  	# e^0 = 1
  my $r = exp($$z{'ln_r'});
  my $x = $r * cos( $$z{'theta'}/TO_DEGREES );
  my $y = $r * sin( $$z{'theta'}/TO_DEGREES );

  # Convert the angle to degrees, then reduce the angle
  $y *= TO_DEGREES;
  $y -= int($y/360) * 360;
  $y -=360 if $y >  180;
  $y +=360 if $y <= -180;

  # We're done!
  {'ln_r' => $x, 'theta' => $y}
}

sub pow {
  # pow(z1,z2) = z1^z2.
  return &sub($_[0][0],$_[0][1]) if ref($_[0]) eq 'ARRAY';  # Called by user!
  defined (my $z1 = shift()) or return 'NaN';
  defined (my $z2 = shift()) or return 'NaN';

  # Special cases
  return 'NaN' if $z1 eq 'NaN' or $z2 eq 'NaN';
  if (&is_zero($z1)) {
    return (&is_zero($z2) ? 'NaN' : COMPLEX_ZERO);		# 0^z2 = 0
  }
  return COMPLEX_ONE if &is_one($z1) or &is_zero($z2);  	# 1^z2 = 1, z1^0 = 1

  # Real case: a positive number to a real exponent
  if (is_positive($z1) and is_positive($z2)) {
    return {'ln_r' => $$z1{'ln_r'}*exp($$z2{'ln_r'}), 'theta' => 0}
  }

  # Complex case: z1^z2 = exp(z2*log(z1))
  &exp_(&mul($z2,&log_($z1)));
}

sub sqrt_ {
  defined (my $z = shift()) or return 'NaN';
  &pow($z,&new(0.5));
}

# The good old floor function (could easily be extended to negatives)

sub floor_ {
  # Easy cases: NaN, 0, 0+b*i (the last one should not happen!)
  defined (my $z = shift) or return 'NaN';
  return $z if $z eq 'NaN';
  return COMPLEX_ZERO if (&is_zero($z) or abs($$z{'theta'}) == 90);
  # Force $z to be real (should only be used on reals anyway)
  $$z{'theta'} = (abs($$z{'theta'}) < 90 ? 0 : 180);
  # Large cases: flooring unnecessary (ln(2^53) = 36.7)
  return $z if $$z{'ln_r'} > 37;
  my $n = exp($$z{'ln_r'});
  return $z if ($n == int($n)); # Don't mess with integers
  if (abs($$z{'theta'}) == 0) {	# The 'positive' case
    return COMPLEX_ZERO unless int($n); 	# Can't take log of zero!
    {'ln_r'=>log(int($n)), 'theta'=>0}
  } else {			# The 'negative' case
    {'ln_r'=>log(int($n)+1), 'theta'=>180}
  }
}

sub log_ {
  # Principal value of the complex log
  defined (my $z = shift()) or return 'NaN';
  return 'NaN' if ($z eq 'NaN' or &is_zero($z));
  &new($$z{'ln_r'},$$z{'theta'}/TO_DEGREES);
}

sub N {
  # Calculates the norm (which is just the modulus squared)
  defined (my $z = shift()) or return 'NaN';
  $z ne 'NaN' or return 'NaN';
  {'ln_r'=>($z->{'ln_r'} eq 'NInfinity' ? $z->{'ln_r'} : 2*$z->{'ln_r'}),'theta'=>0}
}

sub gamma {
  # Usual gamma function.

  return 'NaN' unless defined(my $z = shift());
  return 'NaN' if $z eq 'NaN';

  # It is undefined at 0 and negative integers, but log_gamma will catch those
  &exp_(&log_gamma($z))
}

sub log_gamma {
  # Log of the usual gamma function approximated with a power series
  # (hopefully 12-15 decimal place accuracy)
  return 'NaN' unless defined(my $z = shift());
  return 'NaN' if $z eq 'NaN';

  # print "In log_gamma,".&show($z);

  # It is undefined at 0 and negative integers
  return 'NaN' if &is_zero($z);
  if ($$z{'theta'} == 180) {	# A negative real perhaps?
    my $x = &__real($z);
    # Darn! having trouble recognizing integers : int(real(new(-5))) = -4
    $eps = 0.0000001;  # Should be global!
    return 'NaN' if ($x-int($x)< $eps or int($x)-$x > 1-$eps);
  }

  # Now make modulus > 10 (for faster convergence of asymptotic series)
  if ($$z{'ln_r'} < 2.3) {
    if ($$z{'theta'} > -90 and $$z{'theta'} < 90) { # First or fourth quadrant
      return &sub(&log_gamma(&inc($z)), &log_($z));
    } else {
      $z = &sub($z,COMPLEX_ONE);
      return &add(&log_gamma($z), &log_($z));
    }
  }

  # Now use the asymptotic series
  # Starts: (z-0.5)*log(z) - z - log(2pi)/2
  my $out = &add( &sub( &mul(&sub($z,&new(0.5)),&log_($z)), $z), &div(&new(log(2*PI)),&new(2)));
  return $out if &__real($z) > 10**20;

  $out = &add($out, &div(COMPLEX_ONE,&mul(&new(12,0),$z)) );
  my $z_squared = &mul($z,$z);
  $z = &mul($z,$z_squared);	# $z is now $z^3
  $out = &sub($out, &div(COMPLEX_ONE,&mul(&new(360,0),$z)) );
  return $out if $$z{'ln_r'} > 10;

  $z = &mul($z,$z_squared);	# $z is now $z^5
  $out = &add($out, &div(COMPLEX_ONE,&mul(&new(1260,0),$z)) );
  $z = &mul($z,$z_squared);	# $z is now $z^7
  $out = &sub($out, &div(COMPLEX_ONE,&mul(&new(1680,0),$z)) );
  return $out if $$z{'ln_r'} > 5;

  $z = &mul($z,$z_squared);	# $z is now $z^9
  $out = &add($out, &div(COMPLEX_ONE,&mul(&new(1188,0),$z)) );
  $z = &mul($z,$z_squared);	# $z is now $z^11
  $out = &sub($out, &div(COMPLEX_ONE,&mul(&new(691/360360,0),$z)) );
  return $out if $$z{'ln_r'} > 3;

  $z = &mul($z,$z_squared);	# $z is now $z^13
  $out = &add($out, &div(COMPLEX_ONE,&mul(&new(156,0),$z)) );
  $z = &mul($z,$z_squared);	# $z is now $z^15
  &sub($out, &div(COMPLEX_ONE,&mul(&new(3617/122400,0),$z)) );
}

sub zeta {
  # Zeta function for positive arguments greater than one
  return 'NaN' unless (my $s = shift());
  return &new(-0.5,0) if &is_zero($s);

  # Add up the first few terms
  my $zeta = COMPLEX_ONE;
  my $n = 1;
  while (++$n <= 10) {
    $zeta = &add($zeta, &pow(&new($n,0),&neg($s)));
  }

  # Now use the Euler Maclaurin summation formula
  if ($$s{'ln_r'} < 10) { # (Add the correction terms?)
    my $n = &new($n,0);  # change $n to internal format
    $zeta = &add($zeta, &div(&pow($n,&inc(&neg($s))),
        &sub($s,COMPLEX_ONE)));
    $zeta = &add($zeta, &div(&pow($n,&neg($s)),&new(2,0)));
    $zeta = &add($zeta, &div(&mul(&pow($n,&neg(&inc($s))),$s),
        &new(12,0)));
    my $prod = &mul(&mul($s,&inc($s)),&add($s,&new(2,0)));
    $zeta = &sub($zeta,&div(&mul(&pow($n,&neg(&add($s,
        &new(3,0)))),$prod),&new(720,0)));
    $prod = &mul($prod,&mul(&add($s,&new(3,0)),&add($s,&new(4,0))));
    $zeta = &add($zeta,&div(&mul(&pow($n,&neg(&add($s,
        &new(5,0)))),$prod),&new(30240,0)));
    $prod = &mul($prod,&mul(&add($s,&new(5,0)),&add($s,&new(6,0))));
    $zeta = &sub($zeta,&div(&mul(&pow($n,&neg(&add($s,
        &new(7,0)))),$prod),&new(1209600,0)));
    # Error should be less than this last term
  }
  return($zeta);
}

# &cyclotomic($n, $x, $y)
# Evaluates the n-th cyclotomic polynomial (in homogeneous form) at
# ($x,$y) and returns the logarithm of the result.  (Note: if $y=1
# so ln(y)=0 we get the USUAL FORM for the cyclotomic polynomials.)

sub cyclotomic {
  &is_positive(my $n = shift) or return 'NaN';	# Could allow zero and return 1
  $n = int(&__real($n)+0.5);			# Force $n into a regular integer
  my $x = shift or return 'NaN';
  my $y = shift or return 'NaN';

  &sub($x,$y) if ($n < 2);			# N < 2 should be n=1, so return x-y
  # print "In cyclotomic: $n,".&show($x).",".&show($x)."\n";

  # Form a hash table of divisors d (keys) paired with (what will eventually
  # be) the Mobius value of d (values). Since the latter will be a multiplier
  # in a sum we can skip the ones for which this value will be zero--e.g.,
  # those divisible by a prime square

  my %divisors;
  $divisors{1} = 1;
  my $factors = &primes::Factor($n);
  # print &primes::DisplayFactors($factors);

  foreach my $p (keys %{$factors}) {
    foreach my $d (keys %divisors) {
      $divisors{$d*$p} = - $divisors{$d}
    }
  }

  # Now process the list

  my $result = COMPLEX_ONE;
  foreach my $d (keys %divisors) {
    my $u = delete $divisors{$d};
    $d = &new($n/$d,0);
    if ($u > 0) {
      $result = &mul($result, &sub(&pow($x,$d), &pow($y,$d)));
    } else {
      $result = &div($result, &sub(&pow($x,$d), &pow($y,$d)));
    }
  }
  $result
}

sub Phi {
  # Notice that the first is expected to be an integer
  my $a = int(&__real($_[0][0])+0.5);
  &cyclotomic(&new($a),$_[0][1],COMPLEX_ZERO,COMPLEX_ONE);
}

###### Wrap the expression in single quotes to use these from the command line

sub multi_factorial {
  # returns log of multi_factorial(n,k) = n!k  (k=1 for factorial, k=2 for n!! ...)

  # n should be a non-negative real
  return COMPLEX_ONE if &is_zero(my $n = shift());
  return 'NaN' unless &is_positive($n);

  # k should be a positive integer
  return 'NaN' unless &is_positive(my $k = shift());
  $k = &floor_(&add($k,COMPLEX_HALF));

  my $m = &div($n,$k);
  return &mul(&pow($k,$m),&gamma(&inc($m)))
	if (abs(&__real(&sub($m,&floor_(&add($m,COMPLEX_HALF))))) < 0.00001);
  &div( &mul( &mul( &pow($k,&floor_($m)), &gamma($m) ),$n ),
	&gamma( &sub($m,&floor_($m)) ) );
}

sub primorial {
# Approximates sizes of prime factorials. Uses the sum of the log's
# of the known primes up to 5000*k (k=1,2...) to speed up the search.
# Cannot accurately handle p too big, however, it does use
# the prime number theorem to make a guess (and prints a warning) for
# bigger n.                                                           }

  my $p = int(&__real(shift)+0.5); # recover parameter, call it p

  # If its in the table, just return it
  return &exp_(&new($primorials{$p})) if defined $primorials{$p};

  # find the closest thing to it in the table
  my $n = 0;
  my $key;
  foreach $key (keys %primorials) {
    $n = $key if ($key < $p and $key > $n);
  }
  # so now $n < $p (and it is the largest for which $n# has bee defined

  if ($p > $n + 50000) {         # Approximate if p very large!
    warn "$p too big for primorial, using rough approx (add table entry after $n?)";
    return &exp_(&new($p - sqrt($p)));
  }

  # Now find the value of p#
  my $sum = $primorials{$n};
  my $count = 1;
  while (($n = &primes::NextPrime($n)) <= $p) {
    # last if $n == 0;
    $sum += log($n);
    $primorials{$n} = $sum unless $count % 20;
  }
  &exp_(&new($primorials{$p} = $sum));
}

# initialize;
  
$primorials{0}      =      0;
$primorials{5000}   =   4911.695351706863544301349620266;
$primorials{10000}  =   9895.991379156987312668949670307;
$primorials{15000}  =  14844.791692165288718173995807007;
$primorials{20000}  =  19805.309624307867332892257521076;
$primorials{25000}  =  24814.245437041014218769503859356;
$primorials{30000}  =  29750.341743932332594949754552394;
$primorials{35000}  =  34809.363631338689168121958069908;
$primorials{40000}  =  39769.713423932771763550377232248;
$primorials{45000}  =  44799.737827001478491583716983228;
$primorials{50000}  =  49732.018402956854874944331375622;
$primorials{55000}  =  54698.749591139943996500283212089;
$primorials{60000}  =  59816.575291537671581769872731626;
$primorials{65000}  =  64631.051757884213079434560032042;
$primorials{70000}  =  69545.412324660962369028834457438;
$primorials{75000}  =  74670.660374386143289523843196319;
$primorials{80000}  =  79669.201918712429445781121223824;
$primorials{85000}  =  84649.986084131429298979454484925;
$primorials{90000}  =  89611.398626732182647620475874116;
$primorials{95000}  =  94688.414219855984484059122465805;
$primorials{115000} = 114501.445252928615118236901460457;
$primorials{135000} = 134508.499785612226228396814042810;
$primorials{145823} = 145388.150025110946668575631082929;
$primorials{170000} = 169360.003141647239570289617063636;
$primorials{200000} = 199518.9651846238525378802950148278;
$primorials{250000} = 249545.0961468758510393837389336141;
$primorials{274529} = 273949.890382729263790351327941887;
$primorials{312583} = 311716.8118531893745096770414521421;
$primorials{350377} = 349847.621485461307875874607068456;
$primorials{389171} = 388307.4112404981959312004893795679;
$primorials{427991} = 427066.8920506571464623035545547198;
$primorials{467213} = 466099.1724813621626787330477752811;
$primorials{506131} = 505383.484330015434228980012489986;
$primorials{545747} = 544900.9662496943945944272901257883;
$primorials{585493} = 584637.5304743594464324230575309466;
$primorials{625187} = 624577.3192719926761526986659496656;
$primorials{665659} = 664710.8165307829379416748493753251;
$primorials{706019} = 705025.893694106287445650955092389;
$primorials{746773} = 745512.2101167261914529443697780756;
$primorials{787207} = 786162.3893720211582416072656346636;
$primorials{827719} = 826967.0605306346491334689739053697;
$primorials{868771} = 867919.9078341801842815982293506238;
$primorials{909683} = 909014.2001655043840306303588495049;
$primorials{951161} = 950244.3063805715404226291748424902;
$primorials{993107} = 991605.7751806513630006307800516557;
$primorials{1034221} = 1033092.536171500612757138049935147;
$primorials{1076143} = 1074700.3311368437780994054005015128;
$primorials{1117579} = 1116424.301674490088573924408169455;
$primorials{1159523} = 1158260.1613874723261134892913863855;
$primorials{1200949} = 1200203.3718377848416777426677921967;
$primorials{3267113} = 3265980.7601694797801968451105459574;
# Special case for old primes on the list n#/m# ...
$primorials{1999999000} = 1999940162.77873752847421588265179651;
$primorials{2000000011} = 1999941105.10089941875198712912416626;

1;
