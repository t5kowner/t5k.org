<?php

# Two exportable functions:
#
#  parser_WrapNumber($parser_string)    takes an integer (with perhaps whitespaces and \'s)
#       and wraps it, then places it in a code block.
#  parser_parse($parser_string) tries to parse the string and returns either
#       a number or an error

include_once(basic_to_base() . 'bin/parser/operators.inc');
include_once(basic_to_base() . 'bin/parser/functions.inc');

function my_gmp_strval($a)
{
  # Fix a gmp problem, sometimes there are trailing nulls!
    return preg_replace("/\D$/", '', gmp_strval($a));
}


function my_gmp_intval($a)
{
    if (gmp_cmp($a, 2147483647) > 0 or gmp_cmp($a, -2147483647) < 0) {
        print "value " . gmp_strval($a) . " too large for gmp_intval, using 2147483647.\n<br>";
        return 2147483647;
    }
    return gmp_intval($a);
}

# Notice get_atom recognizes atoms, and this might need to be changed
# (e.g., do you want integers or reals as atoms?)

# Usage
#
# parser::parse(parse_string) will parse the given string and return either
# a numeric result or NaN (and will give an error warning).  Set the global
# variable $ParserShowErrors to 0 to avoid printing errors (but why??)

$ParserShowErrors = 1;

# Outline
#
# 1. Operator definitions
# 2. Parser guts (parse, pop_atom, peek_op, get_term)
# 3. Support (Evaluators, error handlers)

# Change this math package to change the arithmetic (e.g., big int,
# or just digits...  There should be nothing here that knows the form
# so, for example, use math=>new(1) for the number 1

#################### 1. Operator definitions ######################

# Operators must have the following defined:
#
#   precedence    :  higher number evaluated first
#   evaluate      :  the name of a function to evaluate the operator
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
#   symbol_match  :  if not empty, passes the match from the
#                    symbol to the evaluator function as the
#                    first parameter  (e.g., (\w+)\( to match any
#                    function name)

global $parser_ops;

$parser_ops  = array(                                          #  binary
    'power' => array( 'symbol' => '^', 'associate' => 'right',
       'precedence' => 20, 'evaluate' => 'eval_power' ),
    'mult'  => array( 'symbol' => '*', 'associate' => 'left',
       'precedence' => 17, 'evaluate' => 'gmp_mul' ),
    'div'   => array( 'symbol' => '/', 'associate' => 'left',
       'precedence' => 17, 'evaluate' => 'gmp_div' ),
    'add'   => array( 'symbol' => '+', 'associate' => 'left',
       'precedence' => 16, 'evaluate' => 'gmp_add' ),
    'sub'   => array( 'symbol' => '-', 'associate' => 'left',
       'precedence' => 16, 'evaluate' => 'gmp_sub' ),

    '(string) concatinate'   => array( 'symbol' => '.', 'associate' => 'left',
       'precedence' => 24, 'evaluate' => 'eval_concat' ),
    '(string) repeat'   => array( 'symbol' => '_', 'associate' => 'left',
       'precedence' => 25, 'evaluate' => 'eval_repeat' ),

    'list'  => array( 'symbol' => ',', 'associate' => 'left',
       'precedence' => 4,  'evaluate' => 'eval_list' ),
                                                       # Unary
    'negation'    => array( 'symbol' => '-', 'associate' => 'prefix',
       'precedence' => 18, 'evaluate' => 'eval_neg' ),
    'factorial'   => array( 'symbol' => '!(\d+)', 'associate' => 'postfix',
       'precedence' => 21, 'evaluate' => 'eval_fact',
       'symbol_match' => 'yes' ),
    'factorial2'  => array( 'symbol' => '!', 'associate' => 'postfix',
       'precedence' => 22,  'evaluate' => 'eval_fact' ),

    'primorial'   => array( 'symbol' => '#', 'associate' => 'postfix',
       'precedence' => 22, 'evaluate' => 'eval_primorial' ),
    'parenthesis' => array( 'symbol' => '(', 'associate' => 'prefix',
       'precedence' => 3,  'evaluate' => 'eval_parenthesis',
       'match' => ')' ),
//  'brackets' => array( 'symbol' => '[', 'associate' => 'prefix',
//     'precedence' => 3,  'evaluate' => ,
//     'match' => ']' ),
                                                       # Functions
    'function'      => array( 'symbol' => '(\\w+)\\(', 'associate' => 'prefix',
       'precedence' => 3,  'evaluate' => 'FunctionEvaluator',
       'match' => ')', 'symbol_match' => 'yes' )
  );

function FunctionEvaluator($function, $args)
{
  # Note: what is passed to the function is either a single 'number'
  # or (if more than one operator) an array
    if (function_exists($function)) {
        return $function($args);
    } else {
        return my_die("undefined function $function");
    }
}

## For user defined functions, if they have one parameter, it is passed
# as a gmp integer.  If they have several, they are passed as a single
# array of gmp integers.

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

######################### 2. Parser Guts ##########################

# First a couple global variables

global $parser_string;      # global parse string use by most routines
global $parser_string_old;      # original string kept for my_die error messages
global $parser_message; # set by my_die after error to stop parsing
  # actually only stops printing of further errors right now

# parse(string) removes the whitespaces from string, initializes the
# global variables, then parses the expression to return either the
# integer answers (as a string) or undef. (Note that internally we use
# 'NaN' rather than undef for undefined terms.)

function parser_parse($parser_string_in)
{
    global $parser_string_old, $parser_string, $parser_message;
    $parser_string = $parser_string_in;
    $parser_string = preg_replace("/\s+/", '', $parser_string); # remove whitespace
    $parser_string_old = $parser_string;  # because my_die uses global $parser_string_old
    $parser_message = ''; # because we're not dead yet!
    $out = get_term(0);
  # $parser_string is empty now if we have successfully parsed.
    if (empty($parser_string)) {
        if (is_array($out)) {
            $out2 = my_gmp_strval(array_shift($out));
            foreach ($out as $n) {
                $out2 .= ', ' . my_gmp_strval($n);
            }
            return $out2;
        } else {
            return  my_gmp_strval($out);
        }
    } else {
        return my_die('syntax error');
    }
}

# peek_op(flag) peeks ahead looking for an operator.  Does not alter
# the parse string.   If flag='prefix', then seeks only prefix
# operators.  Otherwise, seeks anything *but* a prefix operator.

function peek_op($flag)
{
    global $parser_string, $parser_ops;
    $prefix = ($flag == 'prefix');
  # my ($op, $temp);
    for (reset($parser_ops); $op = key($parser_ops); next($parser_ops)) {
        $temp = ($parser_ops[$op]['associate'] == 'prefix');
        if ($prefix ? $temp : !$temp) { # the right type?
            $temp = (!empty($parser_ops[$op]['symbol_match']) ? '^' : '^\\') .
            $parser_ops[$op]['symbol'];
            if (preg_match("/$temp/", $parser_string)) {
                return($op);
            }
        }
    };
    return 0;
}


# pop_atom looks in the parse string for either an unsigned integer
# If it fails to find an atom (e.g., next is an operator), then it
# returns null.  Otherwise it removes whatever it
# finds from the parse string.

function pop_atom()
{
    global $parser_string;
    if (preg_match("/^(\d+)(.*)$/", $parser_string, $match)) {
        $parser_string = $match[2];
        return gmp_init($match[1]);
    } else {
        return null;
    }
}

# get_term(0) removes a term from the parse string and returns its
# mathematical value.  get_term(n) stops parsing and returns a
# value when it finds an operator of precedence less than n.
# Prefix operators behind a binary op must still be processed
# first  (e.g., 2*-3)--so form the only exception.

function get_term($precedence)
{
    global $parser_string, $parser_ops;
#  $right = '';
  # $symbol;
  # $evaluator;

  # Pop a term and peek ahead for an operator

    $left = pop_atom();
    $op = peek_op(is_null($left) ? 'prefix' : 'not prefix');

  # Return if the operator precedence lower, otherwise evaluate
  # and then loop.

    while ($op) {
        if ($parser_ops[$op]['precedence'] < $precedence) {
          # Usual precedence rules do not apply to prefix operators
            if ($parser_ops[$op]['associate'] != 'prefix') {
                return $left;
            }
        }

       # pop the operator (before we just peeked) first we get the symbol to match
        $symbol = (!empty($parser_ops[$op]['symbol_match']) ? '^' : '^\\') . $parser_ops[$op]['symbol'];
       # now match this symbol (which may contain a wild card)
        if (! preg_match("/^$symbol(.*)$/", $parser_string, $matches)) {
            return my_die("could not find $symbol in \"$parser_string\" ($op)");
        }
       # Did the operator symbol contain a wildcard?
        if (!empty($parser_ops[$op]['symbol_match'])) {
            $parser_string = $matches[2];
            $match = $matches[1];
        } else {
            $parser_string = $matches[1];
            $match = null;
        }

        if ($parser_ops[$op]['associate'] == 'prefix') {
            $left = get_term($parser_ops[$op]['precedence']);

            if ($parser_ops[$op]['match']) {
                $temp = '^\\' . $parser_ops[$op]['match'];
                if (preg_match("/$temp(.*)$/", $parser_string, $matches)) {
                       $parser_string = $matches[1]; # Found match (e.g., closing parenthesis)-remove it
                } else {
                    return my_die('Expected ', $parser_ops[$op]['match']);
                }
            }
        } elseif ($parser_ops[$op]['associate'] == 'postfix') {
        } elseif ($parser_ops[$op]['associate'] == 'left') {
            $right = get_term($parser_ops[$op]['precedence'] + 1);
        } elseif ($parser_ops[$op]['associate'] == 'right') {
            $right = get_term($parser_ops[$op]['precedence']);
        } else {
            return my_die("Is associate assigned to $op?");
        }

        $evaluator = $parser_ops[$op]['evaluate'];
        if (!empty($evaluator) and function_exists($evaluator)) {
            if (!empty($match)) {
                 $left = $evaluator($match, $left, (is_null($right) ? "NaN" : $right));
            } else {
                  $left = $evaluator($left, (is_null($right) ? "NaN" : $right));
            }
                $op = peek_op('not prefix');
        } else {
            return my_die("$op undefined");
        }
    };
    return $left;
}

########################## 3. Support #############################

# my_die(msg) places msg on the error stream and undefs the parse
# string (which should stop all parsing!)

# changed warn to print here for web use.

function my_die($msg)
{
    global $parser_string, $parser_string_old, $parser_message;
    if (empty($parser_message)) {
        $parser_message = "<font color=red><b>Parser error: $msg\n<blockquote>" . (!empty($parser_string) ?
        substr($parser_string_old, 0, strlen($parser_string_old) - strlen($parser_string)) : $parser_string_old) .
        " << here >> $parser_string\n</blockquotes></b></font>";
        $parser_string = '';  # Stops parsing?
        print $parser_message;
    }
    return 'NaN'; # Return this on errors
}

function parser_WrapNumber($parser_string = 'empty', $chunk = 10, $chunks_per_line = 6)
{
  # Assumes that the string (not a gmp value) is a number.  It removes white spaces, then
  # adds them every $chunk digits, and adds <br>'s every ,$chunks_per_line.
  # Note this number may have a negative sign, a decimal point and
  # and may end in ...  These effect how we choose to align!

    $parser_string = preg_replace("/\s/", '', $parser_string);  # Remove any whitespace

  # Okay, we have an integer, lets pad enough so the last line comes out even.

  # Add <br>'s for the lines
    $length = $chunk * $chunks_per_line;
    $leading1 = strlen($parser_string) % $length;
    $leading = strlen($parser_string) % $chunk;
    $spacer = '';

    if ($leading1 > 0) {
        $spacer = $length - $leading1;
        $spacer = str_repeat('&nbsp;', $spacer + $spacer / $chunk);
  #   $spacer = str_repeat(' ',$spacer + $spacer/$chunk);
        $parser_string = preg_replace("/^([\d\-\.]{" . $leading1 . "})/", "\\1 <br>\n", $parser_string);
      # Leading character might be a negative sign!
    }
    $parser_string = preg_replace("/([\d\.]{" . $length . "})/", "\\1 <br>\n", $parser_string);

    if ($leading > 0) {
        $parser_string = preg_replace("/^([\d\-\.]{" . $leading . "})/", "\\1 ", $parser_string);
    }
    $parser_string = preg_replace("/([\d\.]{" . $chunk . "})/", "\\1 ", $parser_string);

    return "<span class=\"text-monospace\">$spacer$parser_string</span>";
}
