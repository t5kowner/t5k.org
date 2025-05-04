package canonical;

# use FindBin qw($Bin);   # Where is this script stored?  Put full path into $Bin
# use lib ($Bin,"$Bin/..","$Bin/../math","$Bin/../../bin");
use lib qw (
  /var/www/html/primes/support/modules
  /var/www/html/primes/support/math
  /var/www/html/primes/support
  /var/www/html/primes/bin
);

use primes;
use parser;
use bmath;
Math::Pari::setprecision(116); #primes b^n with large b need greater precision when checking if it's a power
use Regexp::Common qw(balanced);
        # From http://search.cpan.org/author/ABIGAIL/Regexp-Common/

#The obvious parallel is to require
#5*n > max(abs(p),abs(sqrt(d))), 
#in prim/lucasU/V(p,q,n),
#with discriminant d=p^2-4*q.
#
#2) Redundancy: My favourite example of this is
#
#primU(lucasV(p,q,k),q^k,n) = primU(p,q,k*n)

# Real primes submitted to the list
#
#	5984*256^5984-1
#	6706*(19020*(9030*(284*(164*(58*(6*(4*(4*(3)^3+1)^3+1)^3+1)^3+1)^3+1)^3+1)^3+1)^3+1)^3+1
#	Phi(2^6*3^5,3^3*163)
# 	Phi(18,1246!) = 1246!^6-1246!^3+1
#	3315!*(3315-1)!-1
#	(2420*(27^2420))+1
#
# These often have to be rewritten to compare them with others...
# so this module contains the routine
#
#	&canonical::adjust($string)
#
# which attempts to return a canonicalized version of $string.
# 
#	&canonical::show_changes()
#
# will then return a string explaining what changes have been made. 
# &change is for internal use (to build the string for the above routine)
# and &canonical::test just prints an example.

# Note that the lines starting '\s*#\sRule' will show up on the test
# web page.  That way I can write the documentation while writing here.

# &adjust(description) attempts to adjust the string description to a canonical form.

sub test {
  my $a = '4+4+5---+-2* ((3)*5) * 
	64^100 *6-7 +3*2+3
	- Phi(2^6*3^5,3^3*163)
	+(58*(6*(4*(4*(3)^3+1)^3+1)^3+1)^3+1)^3+1';
  my $b = '45*2^602-Phi(15552,4401)+(58*(6*5180117^3+1)^3+1)^3+16';
  print "\nWas : $a\n\nNow : ".&adjust($a)."\nGoal: $b\n";
}

# $adjust($string) adjust string accoring to some built in rules *SO*
# &change($rule,$from,$to) records the change from $from to $to based on
# the given rule in the global $change variable (to be read by &changes)
# and updates globals $number_changs and $number_loops.
# If $rule is 'reset', then the global variables are reset.  
# If $rule is 'new loop', then the code is recycling so incerment $number_loops.
# If $to = undef, then $from is a message (which is added to $changes).  
# If rule is not 'reset' or 'new loop'; then returns $to (effecting the
# change to $to because of context of call)

sub change {
  my $rule = shift;
  if ($rule eq 'reset') {
    $changes = '';
    $number_changes = 0;
    $number_loops = 0;
  } elsif ($rule eq 'new loop') {
    $number_loops++;
  } else {
    $number_changes++;
    my $from = shift;
    $changes .= sprintf("[%u.%2u] Rule %s: ",$number_loops,$number_changes,$rule);
    return ($changes .= $from."\n") unless defined (my $to = shift);
    $changes .= "replacing '$from' with '$to'.\n";
    $to;
  }
}

# After canonical is used, &show_changes does just that!

sub show_changes {
  if ($changes) {
    ('-'x70)."\n[pass.change] Rule: action\n".('-'x70)."\n$changes".('-'x70)."\n";
  } else {
    "No changes were made.\n";
  }
}

# &adjust(string) tries to put $string into a canonical form and stores the
# list of changes it made in $changes.

sub adjust {
  $desc = shift or return '';

  # Let's track changes
  &change('reset');	# Resets the variable that stores a list of changes

  # A few local variables
  my ($left, $right, $a, $b, $n, $op, $op1, $out);
  my ($a_ops, $a_left, $a_right, $a_term, $begin_desc);
  
  # For error checking
  $begin_desc = $desc;

  # Some rules need to be applied repeatedly (they are in the loop below).
  # These need to me applied just one.

  # Rules 1: The rules that are applied only once (at the beginning):


  # Rule 1a: Do not alter quoted expressions
  if ($desc =~ /^"/o) {
    &change('1a',"Expression quoted--so no changes can be made.");
    return $desc;
  }
  
  if ($desc =~ /^prime_blob_\d+$/) {
    return $desc; #don't touch prime_blobs
  }

  # Rule 1b: Remove all white space
  &change('1b',"removed white space ($n characters)") if ($n = ($desc =~ s/\s//go));

  # Rule 1c: Exponent bases should be minimal ("256^10" should be "2^80")

  $a_left  = '[(,+\\-*\\/#^!]';	# Those with equal or lower precidence
  $a_right = '[),+\\-*\\/]';	# Omit postfix operators and ^ (right associative)

  $desc =~ s/(^|$a_left)(\d+)\^(\d+)($a_right|$)/
      $left = $1; $a = $2; $b = $3; $right = $4; $out = $&;
      if (($exp = &is_power($a, $n)) > 1) {
		  $newExp = &parser::parse($b.'*'.$exp);
		if (&is_int($a) and &is_int($newExp) and &is_int($n)) {
		  $out = &change('1c',$out,"$left$n^".($newExp).$right);
		}
      }
      $out;
  /eog;

  ### Main loop 
  # Some things might take mulitple passes: "2*((3)*5)*2^10" should be "15*2^11"

  my $prev_desc = '';

  while ($prev_desc ne $desc) {
    $prev_desc = $desc;			# Loop until we find nothing to change
    &change('new loop');		# Increment loop counter
    last if $number_loops > 100;	# Should never happen--indicates programming error!

    # Rules 2: Arithmetical simplification:
    # Rule 2a: No double negatives or unary addition ("^+3+-+2" is "3-2")
    $desc =~ s/([+\-])([+\-])/
      &change('2a',$&,$1 eq $2 ? '+' : '-');
    /goe;

    # Rule 2b: Simplify addition/subtraction ("(27-2)" or "(25-2," should be "(25)" or "(25,")
    # Later we will decide if the parenthesis are unnecessary

    $a_ops   = '[+\\-]';
    $a_left  = '[(,+\\-]';
    $a_right = '[),+\\-]';

    # Recall ?= gives a zero width positive lookahead assertion, so next match
    # can restart with this operator
    $desc =~ s/(^|$a_left)(\d+)($a_ops)(\d+)(?=$a_right|$)/
      $left = $1; $a = $2; $op = $3; $b = $4; $out = $&;
      if ($left eq '-') { $a = -$a; }
      $n = &parser::parse($a.$op.$b);
      if (&is_int($a) and &is_int($b) and &is_int($n)) {
		if ($left eq '-') { $a = '-'.$a; $n = '-'.$n; }
		$out = &change('2b',$&,"$left$n");
      }
      $out;
    /eog;
    # Rule 2c: Simplify mult,exp,div ("+27*2-" or "(27*2)" should be "+54-" or "(54)")
    # respectively. Later we will decide if the parenthesis are unnecessary
    # (function behave differently...)  We must not change /4/2/2 to /4/1.
    
    $a_ops   = '[*^\\/]';
    $a_left  = '[(+\\-,*]';
    $a_right = '[)+\\-,*\\/]';
	
	# note that we technically could "simplify" something like 2^200000, but that's unreasonable
	# if a "simplification" replaces something with 4 or more times the characters,
	# or if it's increasing in length by 14+ characters, let's not
	# 14 is semi-arbitrary at time of writing. Chosen to make sure no existing primes require a blob to be canonicalized (id 96904 was the problem).
	# it could be changed in the future if some blobs are made or we decide it's OK to have non-canonicalized numbers in the list due to this
	
    $desc =~ s/(^|$a_left)(\d+)($a_ops)(\d+)(?=$a_right|$)/
      $left = $1; $a = $2; $op = $3; $b = $4; $out = $&;
      unless ( $op eq '^' and length($b)>2 ) {
		  $n = &parser::parse($a.$op.$b);
		  if ( &is_int($a) and &is_int($b) and &is_int($n) and length($n) <= 4*length("$a$op$b") and (length($n) - length("$a$op$b")) < 14) {
			$out = &change('2c',$&,"$left$n");
		  }
	  }
      $out;
    /eog;

    # Rule 2xx: 10^n-10^(n-1) should be 9*10^(n-1) (Generalize?)
    
    $a_left  = '[(+,]';
    $a_right = '[)+\\-,]';

    $desc =~ s/(^|$a_left)10\^(\d+)\-10\^(\d+)($a_right|$)/
      $left = $1; $a = $2; $b = $3; $right= $4; $out = $&;
      $n = &parser::parse($a.'-'.$b);
      if ( &is_int($n) and $n == 1) {
	$out = &change('2xx',$&,"${left}9*10^$b$right");
      }
      $out;
    /eog;


    # Special case 3^4^5 since ^ is right associative...
    $a_right = '[)+\\-,*\\/]';

    $desc =~ s/(\^)(\d+)\^(\d+)($a_right|$)/
      $left = $1; $a = $2; $b = $3; $right = $4;  $out = $&;
      $n = &parser::parse($a.'^'.$b);
      if (&is_int($a) and &is_int($b) and &is_int($n)) {
        $out = &change('2c',$&,"$left$n$right");
      }
      $out;
    /eo;

    # Rule 2d: Combine factors into bases (40*2^10 should be 5*2^12. Also 2*2^8 is 2^9)

    $a_left  = '[(+\\-,*]';
    $a_right = '[)+\\-,*\\/]';

    $desc =~ s/(^|$a_left)(\d+)\*(\d+)\^(\d+)($a_right|$)/
      $left = $1; $a = $2; $n = $3; $b = $4; $right = $5; $out = $&;
      if (&is_int($a) and &is_int($b) and &is_int($n) and $a != 0) {  # Make sure not too big!
        my $new_a = $a; my $new_b = $b; my $new_n = $n;
	while (&is_int(&parser::parse($new_a.'\/'.$n))) { $new_a = &parser::parse($new_a.'\/'.$n); $new_b++; } 
        if ($new_a eq '1') {
  	  $out = &change('2d',$&,"$left$new_n^$new_b$right");
        } elsif ($a ne $new_a) {
  	  $out = &change('2d',$&,"$left$new_a*$new_n^$new_b$right");
        }
      }
      $out;
    /eog;

    # Rule 2e: Multiplication and division by 1 is unnecessary

    $a_left   = '[(+\\-,*/]';
    $a_right  = '[)+\\-,*/]';

    $desc =~ s/(^|$a_left)1\*/&change('2e',$&,"$1")/eog;
    $desc =~ s/[\/*]1($a_right|$)/&change('2e',$&,"$1")/eog;

    # Rule 2f: Multipliers belong in front ("2^100*3" should be "3*2^100")
    # (the original term on the left should be a non-integer)

    $a_ops  = '[\d^*\\/#!]';		# digits and operators of equal or higher precedence
    $a_left  = '[(+\\-,*]';
    $a_right = '[)+\\-,*\\/]';

    # The second choice for a allows an arithmetical sub-expression (23*(10^2130-1)/999999*10)
    $desc =~ s/(^|$a_left)($a_ops+|$a_ops*\([^)]*\)$a_ops*)\*(\d+)($a_right|$)/
      $left = $1; $a = $2; $b = $3; $right = $4; my $out = $&;
      if ($a =~ m#[\^(]#o) {	
        # Don't bother unless there is an ^, otherwise big*big*big creates 
	# an infinite loop.
	$out = &change('2f',$out,"$left$b*$a$right");
      }
      $out;
    /eog;

    # Rule 2g: Addends belong behind ("3+2^100*3" should be "2^100*3+3")  
    # (the original term on the right should be a non-integer).

    $a_left  = '[(+,\\-]';	# - needs special coding
    $a_term  = '[\d^*]';	# digits and operators of higher precedence
    $a_right = '[)+\\-,]'; 

    $desc =~ s/(^|$a_left)(\d+)([+-])($a_term+|$a_term*$RE{balanced}{-parens=>'()'}$a_term*)($a_right|$)/
      $left = $1; $a = $2; $op =$3; $b = $4; $right = $5; my $out = $&;
      if ($left eq '-') { $left = '+'; $a = "-$a"; }
      unless ($b =~ m#^\d+$#o) {
        if ($op eq '+' ) {
	  $out = &change('2g',$&,"$left$b$op$a$right");
        } elsif ($op eq '-' ) {
	  $out = &change('2g',$&,"$left-$b+$a$right");
        }
      }
      $out;
    /eog;


    # Rule 2h: In a product of integers, the smallest belongs in front (5*2 is 2*5)

    $a_left  = '[(+,\\-\[]';
    $a_right = '[)+\\-,\]]';

    $desc =~ s/(^|$a_left)(\d+)\*(\d+)($a_right|$)/
	$2 > $3 ? &change('2h',$&,"$1$3*$2$4") : $&;
    /eog;

    # Rule 2i: 0*?? = 0

    $a_left  = '[(+,\\-]';	# - needs special coding
    $a_term  = '[\d^*]';	# digits and operators of higher precedence
    $a_right = '[)+\\-,]';

    $desc =~ s/(^|$a_left)0+[*\/]($a_term+)($a_right|$)/
      $left = $1; $term = $2; $right = $3;
      &change('2i',$&,"${left}0$right");
    /eog;

    # Rule 2j: The expression 10^n/2 should be replaced by 5*10^(n-1) (should be generalized to other bases!)

    $a_left  = '[(+,\\-\[*/]';
    $a_right = '[)+\\-,\]*/]';

    $desc =~ s/(^|$a_left)10\^(\d+)\/2($a_right|$)/
       $left = $1; $exp = $2; $right = $3;
       &change('2j',$&,"${left}5*10^".($exp-1).$right);
    /eog;

	# Rule 2k: (x^y)^z => (x^(y)*(z))
	# might be overkill with parens, but they're stripped out later
	$desc =~ s/($RE{balanced}{-parens=>'()'})\^($RE{balanced}{-parens=>'()'}|\d+)/
		$out = $&;
		$wholeBase = $1;
		$secondExp = $3;
		if( $wholeBase =~ m#^\(($RE{balanced}{-parens=>'()'}|\d+)\^($RE{balanced}{-parens=>'()'}|\d+)\)$# ) {
			$out = &change('2k',$out,"($1^(($3)*($secondExp)))");
		}
		$out;
	/eog;

    # Rules 3: Phi rules:

    $desc =~ s/Phi$RE{balanced}{-parens=>'()'}/
      $out = $&;

      # Rule 3a: Phi(a^n,b) should be Phi(a,(b)^a^(n-1))  (3a is explicit power, 3A implicit)

      if ($out =~ m#^Phi\((\d+)\^(\d+),(.*)\)$#) {	# power explicit
        $out = &change('3a',$out,"Phi($1,($3)^$1^".($2-1).")") if $2 > 1; 
      }
      elsif ($out =~ m#^Phi\((\d+),(.*)\)$#) {		# power implicit
        $a = $1; $b = $2;
        if (($exp = &is_power($a, $n)) > 1) {
	  if (&is_int($a) and &is_int($n)) {
	    $out = &change('3A',$out,"Phi($n,($b)^$n^".($exp-1).")");
  	  }
        }
      }
      $out;
    /eog;


    # Rule 3b: Phi(a*p^n,b) should be Phi(ap,b^p^(n-1))
	# TODO note that factoring here won't work for all numbers - see primes.pm
    $desc =~ s/Phi\((\d+),([^)]+)\)/
      $a = $1; $b = $2; $out = $&;
      if (&is_int($a)) {
	my $factors = &primes::Factor($a);
	my $exponent = 1;
        foreach (keys %{$factors}) {
	  if ($$factors{$_} > 1) {
	    $a \/= $_**($$factors{$_}-1);
	    $exponent *= $_**($$factors{$_}-1);
	  }
	}
	$out = &change('3b',$&,"Phi($a,($b)^$exponent)") if $exponent > 1
      }
      $out;
    /eog;

    # Rule 3c: Phi(2a,b) with a odd, should be Phi(a,-b) (currently: no parenthesis in b)
    # Question: If b has parenthesis, could this make it more complicated??  Otherwise, just
    # delete "$b !~ m#\(# and " to allow parenthesis in b.

    $desc =~ s/Phi$RE{balanced}{-parens=>'()'}/
      $out = $&;
      if ($out =~ m#^Phi\((\d+),(.*)\)$#) {
        $a = $1; $b = $2; 
       if ($b !~ m#\(# and &is_int($a) and $a =~ \/[02468]$\/ and (($a = &parser::parse($a.'\/2') =~ \/[13579]$\/) and $a ne 1)) {  
          $out = &change('3c',$&,"Phi($a,-($b))");
        }
      }
      $out;  
    /eo;

    # Rule 3d: Phi(1,b) and Phi(2,b), should be b-1 and b+1 respectively
    $desc =~ s/Phi$RE{balanced}{-parens=>'()'}/
      $out = $&;
      if ($out =~ m#^Phi\(([12]),(.*)\)$#) {
	$a = $1; $b = $2;  # Note that we must add parenthesis to $b
	$out = &change('3d',$&,($a eq 1 ? "(($b)-1)" : "(($b)+1)"));
      }
      $out;
    /e;
    
    # Rule 3e: Phi(3,b) should be b^2+b+1
    $desc =~ s/Phi$RE{balanced}{-parens=>'()'}/
      $out = $&;
      if ($out =~ m#^Phi\(3,(.*)\)$#) {
	$b = $1;
	$out = &change('3e',$&,'(('.$b.')^2+('.$b.')+1)');
      }
      $out;
    /e;

    # Rules 4: Remove unnecessay parenthesis: (Difficult! Rewrite?)
    # Unnecessary parenthesis?  Functions need them... nothing else does

    # Rule 4ab: Remove unnecessay parenthesis ("2*(3*4)" should be "2*3*4")

    $a_ops    = '[\d^*!#]'; 
    $a_left   = '[()+\\-,*!]';	# Equal precedence if $op commutiative 
	# no / on left if op is *
    $a_right  = '[()+\\-,*\\/!]';	# Lower if not
    
    $desc =~ s/(^|$a_left)\(($a_ops*)\)($a_right|$)/
      &change('4a',$&,"$1$2$3");
    /eog;

    $desc =~ s/(^|[^\w])\((\d+[#!]*)\)/
      &change('4b',$&,"$1$2");
    /eog;

    # Rule 4c: The entire expression should not be incased in parenthesis

    $desc =~ s/^($RE{balanced}{-parens=>'()'})$/
      $1 =~ m#^\((.*)\)$# and &change('4c',$&,"$1");
      $1;
    /eog;

    # Rule 4d: Double parenthesis are never useful "((5))" should be "(5)" or ?

    $desc =~ s/$RE{balanced}{-parens=>'()'}/
      $out =$&;
      if ($out =~ m#^\((\(.*\))\)$#) {
        &change('4d',$&,"$1");
      }
      $out;
    /eog;
  
    # Rule 4e: "+(a+/-b)+/-" does not need the parenthesis

    $a_ops    = '[\d^*!#]'; 
    $b_ops    = '[\d^*!#+]'; 

    $desc =~ s/(^|[+,])\(($a_ops+)([+\-])($a_ops+)\)([+\-,]|$)/
       &change('4e1',$&,"$1$2$3$4$5");
    /eog;
    
    $desc =~ s/(^|[+,])\((($b_ops|$RE{balanced}{-parens=>'()'})+)\)([+\-,]|$)/
      &change('4e2',$&,"$1$2$5");
    /eog;

	# Rule 4f: "+(-...)+/-" becomes "-...+/-"
	$desc =~ s/(\+|^)($RE{balanced}{-parens=>'()'})([+-]|$)/
		$in = $&;
		$begin = $1;
		$parens = $2;
		$end = $4;
		
		$parens =~ s#^\((.*)\)$#$1#;
		&change('4f',$in,"$begin$parens$end");
		$in="$begin$parens$end";
		$in;
	/eog;
	
	# Rule 4g: "(-...w/o+/-)^even" becomes "(...)^even"
	$desc =~ s/($RE{balanced}{-parens=>'()'})\^(\d*|$RE{balanced}{-parens=>'()'})/
		$in = $&;
		$base = $1;
		$exp = &parser::parse($3);
		$end = $in;
		if($exp =~ m#[02468]$# and $base =~ m#^\(-($RE{balanced}{-parens=>'()'})*[^+-]*($RE{balanced}{-parens=>'()'})*\)$#){
			$end = "(".substr($in,2);
			&change('4g',$in,$end);
		}
		$end;
	/eog;


    $desc =~ s/xxx(.?)\((.+)$/
      $depth = 1; $left = $1||''; $term = ''; $a = $2; $out = $&; $ops = '';
      while ($depth > 0 and $a =~ m#^([^()]*)([()])(.*)$#o) {
	$new = $1.$2;
        $term .= $1.$2;
        $a = $3;
	if ($depth == 1) {
	  $new =~ s#[\w()]##og;
	  $ops .= $new;
        }
        $depth += ($2 eq '(' ? 1 : -1);
      }
      chop($term);  		# remove the last ) which ballanced the first
      if ($depth == 0) {	# Might be unballanced...
        $a =~ m#^(.)#o; 
        $right = $1||'';
        print "compare $left with $ops with $right in term \"$term\"\n";
        if (&right_prec($left) <= &left_prec($ops) and &right_prec($ops) <= &left_prec($right)) {
	  $out = &change('dw',$out,"$left$term$a");
	}
      }
      $out;
    /eog;

sub right_prec { 
  my $op = shift or return 0;
  return 25 if $op =~ /\w/o;  # Function--high precidence
  return 24 if $op =~ /[!]/o;
  return 22 if $op =~ /\^/o;
  return 18 if $op =~ /[\/]/o;
  return 17 if $op =~ /[*]/o;
  return 16 if $op =~ /[\-]/o;
  return 15 if $op =~ /[+]/o;
  return 3 if $op =~ /[(,]/o;
  warn "Unknown right precidence \"$op\"\n";
}
sub left_prec { 
  my $op = shift or return 0;
  return 24 if $op =~ /[!#]/o;
  return 23 if $op =~ /\^/o;
  return 18 if $op =~ /[\/]/o;
  return 17 if $op =~ /[*]/o;
  return 16 if $op =~ /[\-]/o;
  return 15 if $op =~ /[+]/o;
  return 3 if $op =~ /[(,]/o;
  warn "Unknown right precidence \"$op\"\n";
}

    # Rule 4e: (-23)^100, (-23)^101 should be 23^100 and -23^101 respectively.

    # (-5)^3 = -5^3
    $a_left  = '[(+\\-,*]';
    $a_right = '[)+\\-,*\\/]';	#     $a_right = '[^\\^]'; fails -- why?
    
    $desc =~ s/xxx(^|$a_left)\(\-(\d+[!#]*)\)\^(\d*)(\d)($a_right|$)/
      &change('4e',$&,($4 % 2) ? "$1-$2^$3$4$5" : "$1$2^$3$4$5");
    /eog;


    # Rules 5: Replace deprecated functions:
 
    # Rule 5a: W(n) deprecated, change to U(2,-1,n)

    $desc =~ s/\bW\(([^()]+)\)/
        &change('5a',$&,"U(2,-1,$1)")
    /eo;

    # Rule 5b: S(n) deprecated, change to V(2,-1,n)/2

    $desc =~ s/\bS\(([^()]+)\)/
        &change('5b',$&,"(V(2,-1,$1)\/2)")
    /eo;

    # Rules 6: Limited distributive laws: (careful not to increase the number of terms...)
    # Rule 6a: (a^n+b)*a^m is a^(n+m)+b*a^m

    $a_left  = '[(+\\-,*]';
    $a_right = '[)+\\-,*\\/]';	#     $a_right = '[^\\^]'; fails -- why?

    $desc =~ s/(^|$a_left)\((\d+)\^(\d+)([+\-])(\d+)\)\*\2\^(\d+)($a_right|$)/
      $left = $1; $a = $2; $n = $3; $sign = $4; $b = $5; $m=$6; $right = $7; $out = $&;
      $sum = &parser::parse($n.'+'.$m);
      if (&is_int($n) and &is_int($m) and &is_int($sum)) {
	$out = &change('6a',$&, "$left($a^".($sum)."$sign$b*$a^$m)$right" );
      }
      $out;
    /eo;      

   # Same, but multiplier on the left

    $desc =~ s/(^|$a_left)(\d+)\^(\d+)\*\(\2\^(\d+)([+\-])(\d+)\)($a_right|$)/
      $left = $1; $a = $2; $m=$3; $n = $4; $sign = $5; $b = $6; $right = $7; $out = $&;
      $sum = &parser::parse($n.'+'.$m);
      if (&is_int($n) and &is_int($m) and &is_int($sum)) {
	$out = &change('6a',$&, "$left($a^".($sum)."$sign$b*$a^$m)$right" );
      }
      $out;
    /eo;      

    $desc =~ s/(^|$a_left)(\d+)\*\(\2\^(\d+)([+\-])(\d+)\)($a_right|$)/
      $left = $1; $a = $2; $m = 1; $n = $3; $sign = $4; $b = $5; $right = $6; $out = $&;
      $sum = &parser::parse($n.'+'.$m);
      if (&is_int($n) and &is_int($m) and &is_int($sum)) {
	$out = &change('6a',$&, "$left($a^".($sum)."$sign$b*$a^$m)$right" );
      }
      $out;
    /eo;      

    # Rules 7: Clean up submitter's sillyness:

    # Rule 7a: Simplify n!/n to (n-1)! (547!/547-1 is 546!-1)

    $a_left  = '[(+\\-,*]';
    $a_right = '[)+\\-,*\\/]';	#     $a_right = '[^\\^]'; fails -- why?

    $desc =~ s/(^|$a_left)(\d+)!\/\2($a_right|$)/
	&change('7a',$&,$1.(&parser::parse($2.'-1'))."!$3");
    /eog;

    # Rule 7b: 9*R(n) is 10^n-1

    $desc =~ s/(^|$a_left)9\*R\((\d+)\)($a_right|$)/
	&change('7b',$&,"$1(10^$2-1)$3");
    /eog;


    # Rule 7c: Use a prime n in "n#"

    $desc =~ s/(\d+)#/
	&primes::Prime($1) ? $& : &change('7c',$&,&primes::PrevPrime($1).'#')
    /eog;

    # Rules 8: Update notation to match the new parser:
    # Rule 8a: When n is composite, U(n) should be primU(n) (same for V)
 
    $desc =~ s/\b([UV])\((\d+)\)/
        &primes::Prime($2) ? $& : &change('8a',$&,"prim$1($2)")
    /eo;

    # Rule 8b: Always old A(n), B(n) is primA(n) and primB(n)
 
    $desc =~ s/\b([AB])\(/
        &change('8b',$&,"prim$1(")
    /eo;

    # Rule 8c: Old lucasU, lucasV is now just U, V

    $desc =~ s/\blucas([UV])\(/
        &change('8c',$&,"$1(")
    /eo;

    # Rules 9: Use more compact notation (or more efficient)
    # Rule 9a: n!/(m!*(n-m)!) = C(n,m)  (Binomial coefficients)

    $a_left  = '[(+\\-,*]';
    $a_right = '[)+\\-,*\\/]';	#     $a_right = '[^\\^]'; fails -- why?

    $desc =~ s/(^|$a_left)(\d+)\!\/\((\d+)\!\*(\d+)\!\)($a_right|$)/
      $left = $1; $n = $2; $r = $3; $s = $4; $right = $5; $out = $&;
      if (&is_int($n) and &is_int($r) and &is_int($s) and ($n == &parser::parse($r.'+'.$s))) {
	$r = $s if $s > $r;
	$out = &change('9a',$&, "${left}C($n,$r)$right" );
      }
      $out;
    /eo;

    # Rule 9b: n!/m!^2 = C(n,m) when 2m = n (Binomial coefficients)

    $a_left  = '[(+\\-,*]';
    $a_right = '[)+\\-,*\\/]';	#     $a_right = '[^\\^]'; fails -- why?

    $desc =~ s/(^|$a_left)(\d+)\!\/(\d+)\!\^2($a_right|$)/
      $left = $1; $n = $2; $r = $3; $right = $4; $out = $&;
      if (&is_int($n) and &is_int($r) and ($n == &parser::parse($r.'*2'))) {
	$out = &change('9b',$&, "${left}C($n,$r)$right" );
      }
      $out;
    /eo;

  }

  # Rules 10: The rules that are applied only once (at the end):

  # Rule 10a: -0 is just 0

  $desc =~ s/^-0$/
    &change('10a',$&,0)
    /eo;

  # Rule 10b: suppress leading '+' sign

  $desc =~ s/^\+/
    &change('10b',$&,'')
    /eo;

  if(&parser::parse($begin_desc) != &parser::parse($desc)){
	  print "Canonicalization or parser error! Started with ".$begin_desc." but ended with ".$desc;
	  die
  }

  return $desc;
}

########### Numerical Support ###############

sub is_int {
  my $n = shift || '';
  return 0 unless ($n =~ /^\-?\d+$/o);
  1;
}

sub is_power {
   # If this a perfect power on a positive integer greater than one? 
   # If so, return the exponent; else return 1.  (O for non-integers)
   return 0 if ((my $n = shift) < 2);	# Not power of an integer greater than one
   return 0 if not &is_int($n);
   my $exp = 1;	# Let's try some exponents
   while ($exp++) {
    my $root = &parser::parse('['.$n.'^(1/'.$exp.')+0.5]');
    $_[0] = $root;
    return $exp*is_power($root) if $n == &parser::parse($root.'^'.$exp);
    return 1 if $root < 2;  # seeking positive integer greater than one!
  }
}

1;
