package parser;

# Usage
#
# &parser::parse(parse_string,return_style) will parse the given string.
# What it returns will depend on the optional "return_style" which will
# be passed to "&math::show". "return_style" might be something like
# '' (for number object), 'log only', 'sign only', 'html', 'internal too'...

# Set the global variable $GlobalShowParserErrors to 0 to avoid printing errors.

$GlobalShowParserErrors = 1;
sub GlobalShowParserErrors { $GlobalShowParserErrors = shift() || 1; }

# Outline
#
# 1. Operator definitions
# 2. Parser guts (parse, peek_op, get_term)
# 3. Support (error handlers)

# Note that the math routines must be loaded first in a module named math.
# It must also include &pop_atom (modifies $GlobalString which is a global
# for this module).

# Change this math package to change the arithmetic (e.g., big int,
# or just digits...  There should be nothing here that knows the form
# so, for example, use math=>new(1) for the number 1.  (Internally the
# package must be named math, but externally, the file can be whatever).

#################### 1. Operator definitions ######################

# Operators must have the following defined:
#
#   precedence    :  higher number evaluated first
#   evaluate      :  a function to evaluate the operator
#   symbol        :  how the operator is represented (may use the
#                    same symbol for both a prefix and not-prefix op
#                    (e.g., -)
#   associate     :  is the function 'left' or 'right' associative
#                    (binary); or 'prefix' or 'postfix' (unary)
#
# You might also define
#
#   match         :  for a symbol to enclose the operand (e.g., the
#                    operator '(' is matched by ')'.
#
#   symbol_match  :  if defined, passes the match from the
#                    symbol to the evaluator function as the
#                    first parameter  (e.g., (\w+)\( to match any
#                    function name)

%operator = (                                          #  binary
    'repeat' => { 'symbol' => '_',  'associate' => 'left',
	'precedence' => 25, 'evaluate' => \&math::repeat },
    'concat' => { 'symbol' => '&',  'associate' => 'left',
	'precedence' => 24, 'evaluate' => \&math::concat },

    'power' => { 'symbol' => '^', 'associate' => 'right',
       'precedence' => 20, 'evaluate' => \&math::pow },
    'mult'  => { 'symbol' => '*', 'associate' => 'left',
       'precedence' => 17, 'evaluate' => \&math::mul },
    'div'   => { 'symbol' => '/', 'associate' => 'left',
       'precedence' => 17, 'evaluate' => \&math::div },
    'add'   => { 'symbol' => '+', 'associate' => 'left',
       'precedence' => 16, 'evaluate' => \&math::add },
    'sub'   => { 'symbol' => '-', 'associate' => 'left',
       'precedence' => 16, 'evaluate' => \&math::sub },

    'list'  => { 'symbol' => ',', 'associate' => 'left',
       'precedence' => 4,  'evaluate' => sub { my $a = shift;
       ref($a) eq 'ARRAY' ? [@$a,shift()] : [$a,shift()] } },

                                                       # Unary
    'negation'    => { 'symbol' => '-', 'associate' => 'prefix',
       'precedence' => 18, 'evaluate' => \&math::neg },
    'positive'    => { 'symbol' => '+', 'associate' => 'prefix',
       'precedence' => 18, 'evaluate' => sub { shift(); } },

    # There are two types of factorials: 20!!! = 2!3=2!(2+1) afactorial must be
    # matched before factorial -- so we sort by keys below in peek_op.  Should
    # there later be more such order necessities, then we need to rewrite this
    # building the order into
    'afactorial'  => { 'symbol' => '!(?=[\d(])', 'associate' => 'left',
       'symbol_match' => 'yes',
       'precedence' => 21,  'evaluate' => \&math::multi_factorial },
    'factorial'   => { 'symbol' => '(!+)', 'associate' => 'postfix',
       'precedence' => 22, 'evaluate' => sub {
	  my $k = &math::new(length(shift()));
	  my $n = shift();
          &math::multi_factorial($n,$k) },
       'symbol_match' => 'yes' },

    'primorial'   => { 'symbol' => '#', 'associate' => 'postfix',
       'precedence' => 22, 'evaluate' => \&math::primorial },
    'parenthesis' => { 'symbol' => '(', 'associate' => 'prefix',
       'precedence' => 3,  'evaluate' => \&math::parenthesis,
       'match' => ')' },
    'brackets' => { 'symbol' => '[', 'associate' => 'prefix',
       'precedence' => 3,  'evaluate' => \&math::floor_,
       'match' => ']' },
                                                       # Functions
    'function'      => { 'symbol' => '(\\w+)\\(', 'associate' => 'prefix',
       'precedence' => 3,  'evaluate' => \&math::FunctionEvaluator,
       'match' => ')', 'symbol_match' => 'yes' }
);

######################### 2. Parser Guts ##########################

# First a couple global variables

local $GlobalString;	# global parse GlobalString use by most routines
local $GlobalOldString;	# original GlobalString kept for my_die error messages
local $GlobalDied;	# set to 1 by &my_die after error to stop parsing

# &parse(string,return_style) removes the whitespaces from string,
# initializes the global variables, then parses the expression.
# What it returns will depend on the optional "return_style" which will
# be passed to "&math::show". "return_style" might be something like
# '' (for number object), 'log only', 'internal too'...

sub parse {
  $GlobalDied = 0; # because we're not dead yet!

  defined($GlobalString = shift) or return &my_die('No string passed to parse');
  $GlobalString =~ s/\s+//go; # remove whitespace
  $GlobalString = &math::preprocessor($GlobalString);	# Call the preprocessor

  $GlobalOldString = $GlobalString;  # because &my_die uses global $GlobalOldString
  my $out = &get_term(0);
  # $GlobalString is empty now if we have successfully parsed.
  return 'NaN' if $GlobalDied;
  length($GlobalString) ? &my_die('syntax error (missing operator?)') : &math::show($out,shift());
}

# &peek_op(flag) peeks ahead looking for an operator.  Does not alter the parse
# GlobalString. If flag='prefix', then seeks only prefix operators.  Otherwise,
# seeks anything *but* a prefix operator.

sub peek_op {
  my ($prefix) = ($_[0] eq 'prefix');
  my ($op, $temp);
  foreach $op (sort keys %operator) {
    $temp = ($operator{$op}{'associate'} eq 'prefix');
    next unless ($prefix ? $temp : !$temp);  # the right type?
    $temp = ($operator{$op}{'symbol_match'} ? '^' : '^\\').$operator{$op}{'symbol'};
    return($op) if ($GlobalString||'') =~ /$temp/;
  };
  return 0;
}

# &get_term(0) removes a term from the parse GlobalString and returns its
# mathematical value. &get_term(n) stops parsing and returns a value when it
# finds an operator of precedence less than n. Prefix operators behind a binary
# op must still be processed first  (e.g., 2*-3)--so form the only exception.

sub get_term {
  my $precedence = $_[0];

  my $right = '';
  my $symbol;
  my $evaluator;

  # Pop a term and peek ahead for an operator

  my $left = &math::pop_atom;
  my $op = &peek_op((not(ref($left)) and $left eq 'NaN') ? 'prefix' : 'not prefix');

  # Return if the operator precedence lower, otherwise evaluate and then loop.

  while ( $op ) {
     if ($operator{$op}{'precedence'} < $precedence ) {
        # Usual precedence rules do not apply to prefix operators
        return ($left) unless $operator{$op}{'associate'} eq 'prefix';
     }

     # pop the operator (before we just peeked)
     $symbol = ($operator{$op}{'symbol_match'} ? '^' : '^\\').$operator{$op}{'symbol'};

     $GlobalString =~ /$symbol/ or return &my_die("could not find $GlobalString ($op)");
     $GlobalString = $';
     # Did the operator symbol contain a wildcard?
     my $match = ($operator{$op}{'symbol_match'} ? $1 : undef );

     # print "$left $op $right ($GlobalString)\n";  # not too useful

     if ( $operator{$op}{'associate'} eq 'prefix' ) {
        $left = &get_term($operator{$op}{'precedence'});

        if ($operator{$op}{'match'}) {
           my $temp = '^\\'.$operator{$op}{'match'};
           if (($GlobalString||'') =~ /$temp/) {
             $GlobalString = $'; # Found match (e.g., closing paren.)-remove it
           } else {
              return &my_die('Expected ',$operator{$op}{'match'});
           }
         }

     } elsif ($operator{$op}{'associate'} eq 'postfix') {
     } elsif ($operator{$op}{'associate'} eq 'left') {
        $right = &get_term($operator{$op}{'precedence'}+1);
     } elsif ($operator{$op}{'associate'} eq 'right') {
        $right = &get_term($operator{$op}{'precedence'});
     } else {
        return &my_die("Is associate assigned to $op?");
     }
     if (not(ref($right)) and $right eq 'NaN') {
       return &my_die("expected term after operator $op");
     }
     if (defined ($evaluator = $operator{$op}{'evaluate'})) {
       if (!defined(&$evaluator)) {
         return &my_die("internal error--evaluator for $op undefined!");
       }
       if (defined $match) {
         $left = &$evaluator($match,$left,$right);
       } else {
         $left = &$evaluator($left,$right);
       }
       $op = &peek_op('not prefix');

     } else {
       return &my_die("$op undefined");
     }
  };
  return $left;
}

########################## 3. Support #############################

# &my_die(msg) places msg on the error stream and undefs the parse
# GlobalString (which should stop all parsing!)

# changed warn to print here for web use.

sub my_die {
  unless ($GlobalDied == 1 or not $GlobalShowParserErrors) {
    if (defined $ENV{'GATEWAY_INTERFACE'}) {	# This will be set if run as CGI
      print "Parser error: @_<br>\n";
    } else {					# otherwise call from command line
      print "Parser error: @_\n";
    }
    if (defined($GlobalString) and defined($GlobalOldString)) {
      print(($GlobalString ?
	substr($GlobalOldString,$[,-length($GlobalString)):$GlobalOldString),
	" << here >> $GlobalString\n");
    }
    undef $GlobalString;
  }
  $GlobalDied = 1;
  'NaN'; # Return this on errors so we can "return &my_die(...)"
}


1;
