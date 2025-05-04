package math;

# The idea is to place the math routines here so that we can just change
# this to change the type of math we are doing (integer, big integer,
# digits only...)

# Sometimes we need a preprocessor--this routine will be called before
# the parser.  For example, we might want to modify "2+3i" to make the
# implied multiplication explicit with s/(\d+)i\b/$1*i/go

sub preprocessor {
  my $string = shift;
  $string;
}

# This module will represent complex numbers as strings

# Values are all complex and NaN (not a number).  ln(r) can be NInfinity
# (for zero).  The argument must be kept between -180 and 180.

sub new {
  # Passed as a + bi, return as (ln_r, theta)
  defined (my $a = shift()) and defined (my $b = shift()) or return 'NaN';
  return 0 if ($a eq '0' and $b eq '0');
  return "$b*i" if $a eq '0';
  return $a if $b eq '0';
  "$a+$b*i";
}

sub FunctionEvaluator {
  # Note: what is passed to the function is either a single 'number'
  # or (if more than one operator) an array
  $function = shift();

  "$function(".&math::show(@_).')';
}

# &pop_atom looks in the parse GlobalString for either an unsigned real
# or ??????.
# If it fails to find an atom (e.g., next is an operator), then it
# returns 'NaN' (not a number).  Otherwise it removes whatever it
# finds from the parse GlobalString $parser::GlobalString

sub pop_atom {
   if ($parser::GlobalString =~ /^(\d+)(\.\d+)?/o) {  # Found unsigned real
     $parser::GlobalString = $';
     return &math::new($1+($2||0),0);   # $2 is usually undefined
   } elsif ($parser::GlobalString =~ /^(\w+)($|(?=[^\(\w\d]))/o) {
     $parser::GlobalString = $';
     return $1;			# Perhaps i or e or pi ???
   } else {
     return 'NaN';
   }
};

sub parenthesis {
  "(".shift().")";
}

# display routines

sub show {
  # A routine to return the value as a displayable string (not ended in a carriage return)
  # If the second parameter is set, then it shows both internal and external formats
  # (this is a multiline display and does not work with lists and NaN's).

  if (ref($z=shift) eq 'ARRAY') {
    # Ah, an array, should be the result of the list operator: ','
    my $list = '';
    foreach (@$z) { $list .= show($_).',' }
    chop($list);
    return $list;
  }

  # The usual case --- a single string
  $z;
}

# recognizers (return 0 or 1)

sub one {
  # Is the number EXACTLY one?
  my $z = shift() or return(0);
  # Last condition includes 'NaN' because in a numeric context a string == 0
  $z eq '1';
}

sub is_zero {
  # Is the number EXACTLY zero?
  my $z = shift() or return(0);
  return(0) if $z eq 'NaN';
  $z eq '0';
}

sub real {
  # I am not sure how to allow for errors when testing real... Does it matter?
  my $z = shift() or return(0);
  return(0) if $z eq 'NaN';
  return(1) unless $z=~/i/o;
  0;
}

sub is_positive {
  # Looking for a positive real--EXACTLY
  die "???";
}

# Basic functions

sub neg {
  # negate
  '-'.shift();
}

sub inc {
  # adds one to the parameter
  die "???";
}

sub repeat {
  defined (my $z1 = shift()) or return 'NaN';
  defined (my $z2 = shift()) or return 'NaN';
  $z1 x $z2;
}

sub concat {
  defined (my $z1 = shift()) or return 'NaN';
  defined (my $z2 = shift()) or return 'NaN';
  $z1.$z2;
}

sub add {
  defined (my $z1 = shift()) or return 'NaN';
  defined (my $z2 = shift()) or return 'NaN';
  "$z1+$z2";
}

sub sub {
  defined (my $z1 = shift()) or return 'NaN';
  defined (my $z2 = shift()) or return 'NaN';
  "$z1-$z2";
}

sub mul {
  defined (my $z1 = shift()) or return 'NaN';
  defined (my $z2 = shift()) or return 'NaN';
  "$z1*$z2";
}

sub div {
  defined (my $z1 = shift()) or return 'NaN';
  defined (my $z2 = shift()) or return 'NaN';
  "$z1/$z2";
}

sub pow {
  defined (my $z1 = shift()) or return 'NaN';
  defined (my $z2 = shift()) or return 'NaN';
  "$z1^$z2";
}

sub N {
  # Calculates the norm (which is just the modulus squared)
  defined (my $z1 = shift()) or return 'NaN';
  "N($z1)";
}

###### Wrap the expression in single quotes to use these from the command line

sub multi_factorial {
  defined (my $z1 = shift()) or return 'NaN';
  my $z2 = shift() || '';
  $z2 eq 'NaN' ? "$z1!" : "$z1!$z2";
}

# Missing primorial?

sub primorial {
  # primorial(n) is n#, primorial(a,b) is a#/b#

  my ($p, $q);
  if (ref($_[0]) eq 'ARRAY') {  # Two variable case
    "$p#/$q#";
  } else {
    $p = shift;
    "$p#";
  }
}


1;
