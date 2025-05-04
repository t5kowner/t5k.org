package math;

# Warning
# Warning

# uses an external call to pari to evaluate E(n) and p(n) because
# but bernpol and numbpart are too new for MATH::PARI to make it
# available.

# Warning
# Warning




# Math::PariInit is like Math::Pari, but allows us to set the memory
# allocation and number of primes.  Phi(6685,-10) fails with the default
# allocation.  List the functions you want loaded into Perl:

use Math::PariInit qw(stack=500000000 :int :DEFAULT
  Pi I Euler log exp lngamma floor norm
  polcoeff polcyclo subst moebius divisors eulerphi
  lift Mod real nextprime);

# zeta and gamma not imported to avoid name conflicts

#### To test to see if integer: (for idiot proofing in future)
#### $out = &Math::Pari::type($in);
#### if ($out eq 't_INT') {


# The idea is to place the math routines here so that we can just change
# this to change the type of math we are doing (integer, big integer,
# digits only ...)

# Routines in this module
#
#   parser support functions
#
#	preprocessor(string)	preprocessing for the parser (e.g., 2i to 2*i)
#	new(x)			create a number object $n
#	FunctionEvaluator	evaluate functions (GlobalString parser is odd)
#	pop_atom()		pops (so defines) atoms for the parser
#	show($n,[style])	displays the number object(s) $n in the request style
#
#   fundamental arithmetic operations (user functions + internal, that is,
#   they both accept and return number objects).
#
#	neg(z)			negation
#	sgn(z)			sign of z (-1, 0 or 1)  NaN if not real
#	C(n,m)			binomial coefficient, both integers, 0 <= m <= n
#  	conjugate(z)		find the complex conjugate
#	inc(z)			add one (used in multiplication)
#	add, sub, mul, div	four basic binary operations
#	pow(z,w)		exponentiate z^w
#	exp_(z)			e^z (externally called exp)
#	log_(z)			principal branch of the complex log (externally log)
#	N(z)			complex norm (absolute value squared)
# 	gamma, log_gamma	the usual gamma function (and its complex log)
#  	cyclotomic(n,x,y)	the nth cyclotomic poly of x and y.
#	Phi(n,x)		the nth cyclotomic polynomial evaluated at x
#	zeta(z)			the Rieman zeta function
#
#   basic functions for reals only (user functions + internal)
#
#	floor_(z)		floor function or real part of z (externally floor).
#
#   Terms in linear sequences
#
#   These can be called with one variable (n) or three (p,q,n):
#
#       U, primU                Fibonacci numbers (& primitive parts)
#       V, primV                Lucas numbers (& primitive parts)
#
#	A(n), primA(n)		Aruifuilean factorizations all defined
#	B(n), primB(n)		only if n is 5 modulo 10.
#
# 	Linear(f0,f1,f2,f3,n)	The nth term of the linear sequence starting fo,f1,f2,f3...
# 	S(n), W(n)		Linear(1,1,3,7,n) and Linear(0,1,2,5,n) respectively
#				but also U(2,-1,n) and V(2,-1,n) respectively



require linear;
use other;

# Sometimes we need a preprocessor--this routine will be called before
# the parser.  For example, we might want to modify "2+3i" to make the
# implied multiplication explicit with s/(\d+)i\b/$1*i/go

sub preprocessor {
  my $z = shift;
  $z =~ s/(\d+)i\b/$1*i/go;  # Allow implicit mutiplication for i (2+3i)
  # 10326*2000009987#/1999999973#+1
  $z =~ s/(^|[(,+\-*])(\d+)#\/(\d+)#([),+\-*\/]|$)/$1primorial($2,$3)$4/go;
  $z;
}

sub new {
  defined (my $a = shift()) or return 'NaN';
  return PARI($a);
}

sub FunctionEvaluator {
  # Note: what is passed to the function is either a single 'number'
  # or (if more than one operator) an array

  $root = shift();
  $root =~ s/^(log|exp|floor)$/$1_/o;	# Protect function names in perl
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
     $a = $1;
     # print "Atom: $a, ref is ",ref($a),"\n";
     return PARI($a);
  } elsif ($parser::GlobalString =~ /^(\w+)($|(?=[^\(\w\d]))/o) {
    #  Should be a constant
    $parser::GlobalString = $';
    my $constant = $1;
    return I   if  $constant eq 'i';
    return exp(1)   if  $constant eq 'e';
    return Pi  if  $constant eq 'pi';
    return Euler  if $constant eq 'gamma';
    return 'NaN'       if  $constant eq 'NaN';
    return &parser::my_die("Unknown constant \"$constant\".");
  } else {
    'NaN';  # Needed in parser to know to seek a prefix operator
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

  my $z = shift();
  return('NaN') if (not(ref($z)) and $z eq 'NaN');	# same for all styles

  if (ref($z) eq 'ARRAY') {
    # Ah, an array, should be the result of the list operator: ','
    my $list = 'LIST: ';
    foreach (@$z) { $list .= show($_).', ' }
    chop($list); chop($list);
    return $list;
  }

  # The usual case
  my $style = shift() || '';
  if ($style eq 'html') {
    my $out = &Math::Pari::pari2pv($z);
    my $leading = (length($out) % 10);
    $out =~ s/(\d{$leading})/$1 /o if $leading;
    $out =~ s/(\d{10})/$1 /go;
    $out;
  } elsif ($style eq 'backquote') {
    my $out = &Math::Pari::pari2pv($z);
    $out =~ s/(\d{78})/$1\\\n/go;
    $out;
  } elsif ($style eq 'digits') {
    length(&Math::Pari::pari2pv($z));
  } elsif ($style eq 'log only') {
    &Math::Pari::pari2num(log($z));
  } else {
    $z
  }
}

sub neg {
  # Negate the complex number z.
  -shift()
}

sub sgn {
  # The sign of a real number
  my $z = shift();
  return 'NaN' if $z != real($z);
  &Math::Pari::sign($z);
}

sub conjugate {
  # complex conjugation: just change theta to -theta (execpt for theta=180)
  &Math::Pari::conj(shift())
}

sub inc {
  1+shift()
}

sub repeat {
  my $x = shift();
  my $y = shift();
  my $l = length($x);
  $x*(10**($l*$y)-1)/(10**$l-1);
}

sub concat {
  my $x = shift();
  my $y = shift();
  $x.$y;
}

sub add {
  return &add($_[0][0],$_[0][1]) if ref($_[0]) eq 'ARRAY';  # Called by user!
  shift()+shift()
}

sub sub {
  return &sub($_[0][0],$_[0][1]) if ref($_[0]) eq 'ARRAY';  # Called by user!
  shift()-shift()
}

sub mul {
  return &mul($_[0][0],$_[0][1]) if ref($_[0]) eq 'ARRAY';  # Called by user!
  shift()*shift()
}

sub div {
  return &div($_[0][0],$_[0][1]) if ref($_[0]) eq 'ARRAY';  # Called by user!
  shift()/shift()
}

sub exp_ {
  exp(shift())
}

sub pow {
  return &pow($_[0][0],$_[0][1]) if ref($_[0]) eq 'ARRAY';  # Called by user!
  shift()**shift()
}

#sub sqrt_ {
#  defined (my $z = shift()) or return 'NaN';
#  &pow($z,&new(0.5));
#}

sub floor_ {
  floor(shift())
}

sub log_ {
  # Principal value of the complex log
  log(shift())
}

sub N {
  # Calculates the norm (which is just the modulus squared)
  norm(shift())
}

sub gamma {
  &Math::Pari::gamma(shift())
}

sub log_gamma {
  lngamma(shift())
}

sub zeta {
  &Math::Pari::zeta(shift())
}

sub cyclotomic {
  my $n = floor($_[0][0]);
  my $x = $_[0][1];
  my $y = $_[0][2];
  &Phi([$n,$x/$y])*$y**&eulerphi($n);
}


sub Phi {
  # Notice that the first is expected to be an integer
  my $n = $_[0][0];
  my $x = $_[0][1];
  my $z = PARI 'x';
  &subst(&polcyclo($n,$z),$z,$x);
}

sub U {
  # Either U(n) = U(1,-1,n) (the usual Fibonacci numbers) or the Lucas sequence
  # U(p,q,n) generated by p and q.  (Was called lucasU(p,q,n).)

  my ($n, $p, $q);  # Either way, U(n) = (x^n - y^n)/(x-y) (so fails if disc = 0)
		    # if x, y are the roots of x^2 = p*x - q (but we don't use this)
  if (ref($_[0]) eq 'ARRAY') {  # Three variable case
    $p = $_[0][0];
    $q = $_[0][1];
    $n = floor($_[0][2]);
  } else {
    $p =  1;
    $q = -1;
    $n = floor(shift());
  }
  my $x = PARI 'x';
  2*&polcoeff(&lift(&Mod(($p+$x)/2,$x*$x+4*$q-$p*$p)**$n),1)
}


sub V {
  my ($n, $p, $q);
  if (ref($_[0]) eq 'ARRAY') {  # Three variable case
    $p = $_[0][0];
    $q = $_[0][1];
    $n = $_[0][2];
  } else {
    $p =  1;
    $q = -1;
    $n = shift();
  }
  my $x = PARI 'x';
  2*&polcoeff(&lift(&Mod(($p+$x)/2,$x*$x+4*$q-$p*$p)**$n),0)
}

sub primU {
# Evaluates the primitive part of the n-th (n = exp(ln_n)) Fibonacci
# number and returns its natural log (assumes n > 1).  See:
  my ($n, $p, $q);
  if (ref($_[0]) eq 'ARRAY') {  # Three variable case
    $p = $_[0][0];
    $q = $_[0][1];
    $n = $_[0][2];
  } else {
    $p =  1;
    $q = -1;
    $n = shift();
  }

  my $prod = 1;
  foreach (@{&divisors($n)}) {
    $prod *= &U([$p,$q,$n/$_])**&moebius($_);
  }
  $prod;
}

sub primV {
# For the Lucas numbers we use primV(n) = primU(2n)
  if (ref($_[0]) eq 'ARRAY') {
    my $p = $_[0][0];
    my $q = $_[0][1];
    my $n = $_[0][2];
    &primU([$p,$q,2*$n]);
  } else {
    &primU(2*shift());
  }
}

sub Linear {
  # If a two-term linear recurrence exists with those first four initial
  # values, pfgw could solve for the recurrence coefficients, solve the
  # quadratics, and then calculate Linear(), so
  #
  #   Linear(0,1,1,2,n) is U(n)
  #   Linear(0,1,p,p^2-q,n) is U(p,q,n)
  #   Linear(2,1,3,4,n) is V(n)
  #   Linear(2,1,p-2q,p^2-2pq-q,n) is V(p,q,n)
  #   Linear(1,7,41,239,n) is the NSW numbers
  #
  # Roughly we have Linear(f0, f1, f2, f3, n) = fn

  # Get the variables first
  my $f0 = $_[0][0];
  my $f1 = $_[0][1];
  my $f2 = $_[0][2];
  my $f3 = $_[0][3];
  my $n  = floor($_[0][4]);

  # Solve for c1, c2 where f(n+1) = c1*f(n) + c2*f(n-1)
  my $d = $f1*$f1-$f2*$f0;
  if (not(ref($d)) and $d eq 0) {
    # This means that f_n = r^n * f_0 where r = f_1/f_0 (check that f3 = r*f2)
    # then make sure r is an integer (Linear(27,9,3,1,n)), then solve
    warn "Not possible solve for coefficients in linear recurrence Linear($f0,$f1,$f2,$f3,n)!";
    return 'NaN';
  }
  my $c1 = ($f1*$f2-$f3*$f0)/$d;
  my $c2 = ($f1*$f3-$f2*$f2)/$d;

  #           n
  #  ( c1 c2 )  ( f1 )         ( f_n+1 )
  #  (  1  0 )  ( f0 )    is   (  f_n  )

  my $m = PARI "[$c1,$c2;1,0]^$n*[$f1;$f0]";
  PARI "$m\[2,1]"
}

sub S {
  Linear([1,1,3,7,shift()]);
}

sub W {
  Linear([0,1,2,5,shift()]);
}

sub A {  # n == 5 (mod 10)
  # A($n), B($n) calculate the non-primitive form of A( ) and B( )
  # So here we MUST have $n == 5 (mod 10)
  my $n = shift;
  my $U = &U($n/5);
  5*$U*$U-5*$U+1;
}

sub B {  # n == 5 (mod 10)
  # A($n), B($n) calculate the non-primitive form of A( ) and B( )
  # So here we MUST have $n == 5 (mod 10)
  my $n = shift;
  my $U = &U($n/5);
  5*$U*$U+5*$U+1;
}


sub primA {
# Evaluates the primitive part of the Lucas Aurifeullian factorization. The
# argument MUST be 5 modulo 10 for these to be defined! See equation 2.10 in
#
#       Tables of Fibonacci and Lucas Factorizations
#       J. Brillhart, P. Montgomery, R. Silverman
#       Math. Comp 50:181, January 1988, 251-260.
#
# Note that his e_d = 1 (d == 1 or 4 mod 5) and e_d = 0 otherwise.

  defined (my $n = shift()) or return 'NaN';

  unless ($n % 10 == 5) {
    warn("The argument in A, B, primA and primB must be 5 modulo 10.  You gave $n\n");
    return 'NaN';
  }

  my $result = 1;
  foreach my $d (@{&divisors($n)}) {
    next if $d % 5 == 0;
    if ($d % 5 == 1 or $d % 5 == 4) {
      $result *= &A($n/$d)**&moebius($d);
    } else {
      $result *= &B($n/$d)**&moebius($d);
    }
  }
  $result;
}

sub primB {
 my $n = shift;
 &primV($n)/&primA($n);
}

sub Bern {
  # Bern($n) = The Bernoulii numbers
  &Math::Pari::bernfrac(floor(shift()));
}

# binomial coefficients

# Note Pari defines binomial(n,k) to be n*(n-1)*...*(n-k+1)/k! so k should be an integer
# (hence my use of floor).  When k is negative binomial(n,k) returns zero.

sub C {
  $n = $_[0][0];
  $k = $_[0][1];
  &Math::Pari::binomial($n,floor($k));
}

##  Can all be replaced by the following one line when I can get Math::Pari compiled
##  with a recent version of pari
##  return &Math::Pari::numbpart(floor(shift()));

# Partitions

$partitions{0} = 1;
$partitions{1} = 1;
$partitions{2} = 2;
$partitions{3} = 3;
$partitions{4} = 5;
$partitions{5} = 7;  # 8
$partitions{6} = 11; # 14
$partitions{7} = 15; # 25

sub p {
  # Asymptotically, p(n) = exp(pi(2n/3)^.5)/(4n(3)^.5), but...
  #  return &Math::Pari::numbpart(floor(shift()));

  my $n = shift;
  return $partitions{$n} if exists($partitions{$n});
  return 0 if $n <= 0;

  my $out = `echo "default(colors,\"no\"); default(lines,0); numbpart($n)" | gp -q`;
  chop $out;
  PARI $out;

#  my $sum = 0;
#  my $k = 1;
#  while (2*$n > $k*(3*$k-1)) {
#    $sum += &p($n - $k*(3*$k-1)/2) + &p($n - $k*(3*$k+1)/2);
#    $k++;
#  }
#  $partitions{$n} = $sum;
}

sub X {
  # Archaic Dubner function.  Do not use!  Here for historic primes only.
  my $n = shift;
  my $s = '122333344444444'.('5'x16).('6'x32).('7'x64).('8'x128).('9'x$n);
  PARI $s
}

sub Q {
  # Archaic Dubner function.  Do not use!  Here for historic primes only.
  $k = $_[0][0];
  $n = $_[0][1];
  (10**(8*$k) + 2*10**(7*$k) + 3*10**(6*$k) + 4*10**(5*$k) + 5*10**(4*$k)
	+ 6*10**(3*$k) + 7*10**(2*$k) + 8*10**($k) + 9) * &R($k) * 10**$n + 1;
}

sub Y {
  # Archaic Dubner function.  Do not use!  Here for historic primes only.
  # When 6M+1, 12M+1 and Y all prime, the product is a Carmichael
  my $k = $_[0][0];
  my $n = $_[0][1];
  my $s = $_[0][2];
  18*&M([$k,$n])*(4*&M([$k,$n])+1)/$s+1
}

sub M {
  # Archaic Dubner function.  Do not use!  Here for historic primes only.
  # When 6M+1, 12N+1 and Y all prime, the product is a Carmichael
  my $k = $_[0][0];
  my $n = $_[0][1];
  (($k*&primorial(47)/2-1)**$n)/4
}

sub E {
  # Signed Euler numbers (sometimes signs and zeros are omitted)
  my $n = shift;
  return 0 if $n < 0;	# No negative terms
  return 0 if $n % 2;	# Odd terms are zero
  my $out = `echo "default(colors,\"no\"); default(lines,0); eulerfrac($n)" | gp -q`;
  chop $out;
  # print "[$out]";
  # $out =~ s/\x1B\[([0-9]{1,2}(;[0-9]{1,2})?)?[m|K]//g;	# remove any color coding
  PARI $out
}

sub R {
  defined (my $n = shift()) or return 'NaN';
  (10**$n-1)/9
}

###### Wrap the expression in single quotes to use these from the command line

sub multi_factorial {
  # returns log of multi_factorial(k,n) = n!k  (k=1 for factorial, k=2 for n!! ...)

  # n should be a non-negative real
  my $n = real(shift());
  return 1 if $n eq 0;
  return 'NaN' unless $n > 0;

  # k should be a positive integer
  my $k = floor(real(shift()));
  return 'NaN' unless $k > 0;

  my $prod = $n;
  while ($n > $k) {
    $n -= $k;
    $prod *= $n;
  }
  $prod;
}

sub primorial {
  # primorial(n) is n#, primorial(a,b) is a#/b#

  my ($p, $q);
  if (ref($_[0]) eq 'ARRAY') {  # Two variable case
    $p = $_[0][0];
    $q = $_[0][1];
  } else {
    $p = shift;
    $q = 1;
  }

  my $prod = 1;
  my $prime = nextprime($q+1);  # Pari's nextprime is the next prime >= x (not > x)
  while ($prime <= $p) {
    $prod *= $prime;
    $prime = nextprime($prime+1);
  }
  $prod;
}

# initialize;

1;
