<?php

# Need to be able to handle $a + $b*sqrt($d) with $d^2 = $dsquare an
# integer.  Problem: $a, $b could be half-integers.  Solution used here:
# these quadratic integers $a, $b are represented as
#
#   array($a,$b,$denom)
#
# where $a and $b are gmp_integers and $denom is 1 or 2 (regular integer).

# uses is_gmp_even($a)

function quad_reduce($a)
{
  # echo "Reducing ".quad_strval($a),"<br>";
  # The denominator might be 1, 2, or 4 (after a multiply), lets reduce if possible
    if ($a[2] <= 1) {
        return $a; # Already reduced (or an error)
    }
    if (is_gmp_even($a[0]) and is_gmp_even($a[1])) { # numbers even
        $a[0] = gmp_div($a[0], 2);
        $a[1] = gmp_div($a[1], 2);
        $a[2] = $a[2] / 2;
      # echo "Repeat ";
        return quad_reduce($a);
    } else {
        return $a; # Already reduced
    }
}

function quad_add($a, $b)
{
  # echo "Adding: ",quad_strval($a),'+',quad_strval($b),'<br>';

    if ($a[2] == $b[2]) { # Same denominators
        $c0 = gmp_add($a[0], $b[0]);
        $c1 = gmp_add($a[1], $b[1]);
        $denom = $a[2];
    } elseif ($a[2] == 1) {
        $c0 = gmp_add(gmp_mul(2, $a[0]), $b[0]);
        $c1 = gmp_add(gmp_mul(2, $a[1]), $b[1]);
        $denom = 2;
    } else {
        $c0 = gmp_add($a[0], gmp_mul(2, $b[0]));
        $c1 = gmp_add($a[1], gmp_mul(2, $b[1]));
        $denom = 2;
    }
    return quad_reduce(array($c0,$c1,$denom));
}

function quad_neg($a)
{
    return array(gmp_neg($a[0]),gmp_neg($a[1]),$a[2]);
}

function quad_sub($a, $b)
{
  # echo "Subtracting: ",quad_strval($a),'-',quad_strval($b).'<br>';
    return quad_add($a, quad_neg($b));
}

function quad_strval($a)
{
  # Display the quad number
    $denom = ($a[2] == 1 ? '' : "/$a[2]");
    return "(" . gmp_strval($a[0]) . $denom . "," . gmp_strval($a[1]) . $denom . ")";
}

function quad_mul($a, $b, $d_square)
{
  # echo "Multiplying: ",quad_strval($a),'*',quad_strval($b).'<br>';
    return quad_reduce(array (
    gmp_add(
        gmp_mul($a[0], $b[0]),
        gmp_mul(gmp_mul($a[1], $b[1]), $d_square)
    ),
    gmp_add(gmp_mul($a[0], $b[1]), gmp_mul($a[1], $b[0])),
    $a[2] * $b[2]
    ));
}

function quad_pow($a, $exp, $d_square)
{
  # $a = quad number, $exp is a gmp integer
  # echo "Power: ",quad_strval($a),'^',gmp_strval($exp).'<br>';
    $exp = gmp_strval($exp);
    if ($exp == 0) {
        return array(1,0,1);
    }
    if ($exp == 1) {
        return $a;
    }

  // Right to left binary exponentiation
    $t = array(1,0,1);
    $f = $a;

    while ($exp > 1) {
        if (($exp & 1) == 1) {  // if exponent is odd
            $t = quad_mul($t, $f, $d_square);
        }
        $exp = intval($exp / 2);
        $f = quad_mul($f, $f, $d_square);
    };
    $t = quad_mul($t, $f, $d_square);
    return $t;
}
