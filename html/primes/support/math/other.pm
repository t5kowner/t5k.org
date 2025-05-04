package other;

# Contains the other parser functions
#
#	Bern(n)		Bernoulli numbers.

sub FracBern {
  my $k = shift;
  my $h = 0;
  my $s = 1;
  my $j = $k+1;
  my $n = 1;
  while (++$n <= $k+1) {
    $j = $j*($n-$k-2)/$n;
    $h = $h+$j*$s/$n;
    $s = $s+$n^$k
  }
  $k == 0 ? 1 : $h
}

sub Bern {
  # Bern($n) = The Bernoulii numbers via Euler's formula
  # zeta(n) = abs(B_n)*(2 pi)^n/(2*n!)

  &math::is_real(my $n = shift) or return('NaN');
  return(&math::new(1,0)) if &math::is_zero($n);	# Bern(0) = 1

  # Lets convert n to a regular integer (to check its parity)
  $rn = int(&math::__real($n)+0.5);			# round n
  return 'NaN' if $rn > 2**53;				# Too big to tell even/odd
  return(&math::new(-1/2,0)) if $rn == 1;			# Bern(1) = -1/2
  return(&math::new(0,0)) if ($rn % 2);			# zero for odds > 1

  my $out = &math::div( &math::mul( &math::zeta($n),
	&math::mul(&math::new(2,0),&math::gamma(&math::inc($n)))),
        	&math::pow(&math::new(6.283185307179586476925286766559,0)
	,$n));
  # Add the sign...
  (($rn/2) % 2) ? $out : &math::neg($out);
}

# binomial coefficients
# Problem: Overflows when $a is large even if $b is very small (2 or 3) so I
# added a work around
# This does not match the Pari version in non-integer cases

sub C {
  # Currently fails for a or b = 1 (so the log is zero)
  my $a = $_[0][0] || die("Binomial coefficient C() requires two (not zero) variables");
  my $b = $_[0][1] || die("Binomial coefficient C() requires two (not one) variables");
  my $c = &math::sub($a,$b);
  if (($n = &math::__real($b)) < 50) {
    my $one = &math::new(1,0);
    $prod = $one;
    while ($n > 0) {
      $prod = &math::mul(&math::div($a,$b),$prod);
      $a = &math::sub($a,$one);
      $b = &math::sub($b,$one);
      $n--;
    }
    return $prod;
  }
  &math::div(&math::gamma(&math::inc($a)),
	&math::mul(&math::gamma(&math::inc($b)),&math::gamma(&math::inc($c))) );
}

# Partitions

sub p {
  # Let's reserve small p(n) for the nth prime.
  # Asymptotically, p(n) = exp(pi(2n/3)^.5)/(4n(3)^.5), so we have
  # ln(p(n) = (2/3)^.5pi*n^.5 - ln(n) - ln(48)/2 }
  return 'NaN' unless &math::is_real(my $n = shift);
  my $a = $$n{'ln_r'};
  {'ln_r'=>exp($a/2.0)*2.565099660323728191088072719342-$a-1.9356005054539454645320868613776,
	'theta'=>0}
}

sub X {
  # Archaic Dubner function.  Do not use!  Here for historic primes only.
  &math::is_positive(my $n = shift) or return 'NaN';
  {'ln_r'=>(exp($$n{'ln_r'})+254)*log(10)+log(1.223333444444445555556), 'theta'=>0}
}

sub Q {
  # Archaic Dubner function.  Do not use!  Here for historic primes only.
  $k = $_[0][0];
  $n = $_[0][1];
  {'ln_r'=>(8*exp($$k{'ln_r'})+exp($$n{'ln_r'}))*log(10)+&R($k)->{'ln_r'},'theta'=>0}
}

sub Y {
  # Archaic Dubner function.  Do not use!  Here for historic primes only.
  $k = $_[0][0];
  $n = $_[0][1];
  $m = $_[0][2];
  {'ln_r'=>log(4.5) + 2*(40.26707225079+$$k{'ln_r'})*exp($$n{'ln_r'}) - $$m{'ln_r'},'theta'=>0}
}

sub M {
  # Archaic Dubner function.  Do not use!  Here for historic primes only.
  $k = $_[0][0];
  $n = $_[0][1];
  {'ln_r'=>($$k{'ln_r'}+40.26707225079)*exp($$n{'ln_r'})-log(4),'theta'=>0}
}

sub J {
  my $a = int(&math::__real($_[0][0])+0.5);
  my $n = int(&math::__real($_[0][1])+0.5);
  &primes::Jacobi($a,$n);
}

sub E {
  # Equation 1.2.13 pg 9 from "Computation of Special Functions"
  # by Zhang and Jin.
  return &math::new(1) if &math::is_zero(my $n = shift);
  &math::is_positive($n) or return  'NaN';
  my $real_n = int(&math::__real($n)+0.5);
  return &math::new(0) if ($real_n % 2 != 0);	# E_odd = 0
  return &math::new(-1) if $real_n == 2;
  return &math::new(5) if $real_n == 4;
  return &math::new(-61) if $real_n == 6;
  return &math::new(1385) if $real_n == 8;
  my $m = &math::inc($n);
  my $z = &math::pow(&math::div(&math::new(2),&math::new(3.1415926535897932384626433832795)),$m);
  $z = &math::mul(&math::mul($z,&math::gamma($m)),&math::new(2));
  if ($$n{'ln_r'} < 30) {	# Aiming for 15 decimal place accuracy (ha!)
    my $neg_m = &math::neg($m);
    my $w = &math::sub( &math::new(1), &math::pow(&math::new(3),$neg_m) );
    $w = &math::add($w,&math::pow(&math::new(5),$neg_m));
    $w = &math::sub($w,&math::pow(&math::new(7),$neg_m));
    if ($$n{'ln_r'} < 15) {	# Aiming for 15 decimal place accuracy (ha!)
      $w = &math::add($w,&math::pow(&math::new(9),$neg_m));
      $w = &math::sub($w,&math::pow(&math::new(11),$neg_m));
      $w = &math::add($w,&math::pow(&math::new(13),$neg_m));
    }
    $z = &math::mul($w,$z);
  }
  {'ln_r'=>$$z{'ln_r'}, 'theta'=>(($real_n/2 % 2) ? 180 : 0)}
}

sub R {
  defined (my $n = shift()) or return 'NaN';
  &math::div( &math::sub(&math::pow(&math::new(10),$n),&math::new(1)), &math::new(9));
}


1;
