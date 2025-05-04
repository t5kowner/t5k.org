<?php

# Uses arithmetic on quadratic integers
include_once(basic_to_base() . 'bin/parser/quad.inc');

function linear_Linear($arg_list)
{
  # Get the variables--assuming small enough to fit in integers!
    $f0 = ($arg_list[0] == 0 ? gmp_init($arg_list[0]) : $arg_list[0]);
    $f1 = ($arg_list[1] == 0 ? gmp_init($arg_list[1]) : $arg_list[1]);
    $f2 = ($arg_list[2] == 0 ? gmp_init($arg_list[2]) : $arg_list[2]);
    $f3 = ($arg_list[3] == 0 ? gmp_init($arg_list[3]) : $arg_list[3]);
    $n  = $arg_list[4];
  # print "($f0,$f1,$f2,$f3,$n)";

  # Solve for c1, c2 where f(n+1) = c1*f(n) + c2*f(n-1)
    $d = gmp_sub(gmp_mul($f1, $f1), gmp_mul($f2, $f0));
    if (gmp_cmp($d, 0) == 0) {
        echo "Can not solve for coefficients in linear recurrence (discriminant is 0)";
        return 'NaN';
    }
    $c1 = gmp_div(gmp_sub(gmp_mul($f2, $f1), gmp_mul($f3, $f0)), $d);
    $c2 = gmp_div(gmp_sub(gmp_mul($f1, $f3), gmp_mul($f2, $f2)), $d);
  # Should have an is integer check!
  # echo "c1 = ",gmp_strval($c1),", c2 = ",gmp_strval($c2),"<br>";

  # Now let r1 and r2 be the roots of this quadratic
    $disc = gmp_add(gmp_mul($c1, $c1), gmp_mul(4, $c2));
  # echo " disc = ",gmp_strval($disc),'<br>';
    $r1 = quad_reduce(array($c1,1,2));
    $r2 = quad_reduce(array($c1,-1,2));
  # echo " roots r1, r2 = ",quad_strval($r1),', ',quad_strval($r2),'<br>';

    if (gmp_cmp($disc, 0) != 0) {
      # answer = ($a1*$r1^$n + $a2*$r2^$n)/sqrt($disc)
        $a1 = quad_sub(array($f1,0,1), quad_mul(array($f0,0,1), $r2, $disc));
      # echo " a1 = ",quad_strval($a1),'<br>';
        $a2 = quad_sub(array(0,$f0,1), $a1); # $f0*sqrt($disc) - $a1
      # echo " a2 = ",quad_strval($a2),'<br>';
        $result = quad_mul($a1, quad_pow($r1, $n, $disc), $disc);
        $result = quad_add($result, quad_mul($a2, quad_pow($r2, $n, $disc), $disc));
        if (gmp_cmp($result[0], 0) != 0 or $result[2] != 1) {
            echo "Error!!!" . quad_strval($result);
        }
        return $result[1];
    } else {
        echo "Can not handle double roots yet (discriminant = ",gmp_strval($disc),")";
        return 'NaN';
    }
}

function linear_primLinear($arg_list)
{
  # Get the variables--assuming small enough to fit in integers!
    $f0 = $arg_list[0];
    $f1 = $arg_list[1];
    $f2 = $arg_list[2];
    $f3 = $arg_list[3];
    $n = $arg_list[4];
  # print "($f0,$f1,$f2,$f3,$n)";

  # grab the factorization of $n
    $factors = factor(my_gmp_intval($n));

  # use it to build an array of divisor => mobious for divisor (zeros omitted)
    $divisors = array('1' => '1');
    foreach ($factors as $p => $a) {
  # print "factor $p ^ $a";
        $p2 = my_gmp_intval($p);
        foreach ($divisors as $q => $u) {
            $divisors[$p2 * $q] = - $divisors[$q];
        }
    }

    $numerator = 1;
    $denominator = 1;
    foreach ($divisors as $q => $u) {
  # print "<li>$q => $u, ".gmp_strval($n);
        if ($u == 1) {
            $numerator = gmp_mul(
                $numerator,
                linear_Linear(array($f0,$f1,$f2,$f3,gmp_div($n, $q)))
            );
        }
        if ($u == -1) {
            $denominator = gmp_mul(
                $denominator,
                linear_Linear(array($f0,$f1,$f2,$f3,gmp_div($n, $q)))
            );
        }
    }

    return gmp_div($numerator, $denominator);
}
