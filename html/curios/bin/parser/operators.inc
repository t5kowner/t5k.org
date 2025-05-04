<?php

# Defines the operators used by the parser

# Note:  eval_primorial resets the time limit to 90 (three times the default)
# adjust this if you want to do something more globally

function eval_primorial($a)
{
    if (gmp_cmp($a, 2)  < 0) {
        return(1);
    }
    if (gmp_cmp($a, 2) == 0) {
        return(2);
    }
    $out = 2;
    $i = 1;
    set_time_limit(90);
    while (gmp_cmp($i + 2, $a) < 1) {
        $i = $i + 2;
        if (gmp_prob_prime($i) == 0) {
            continue;
        }
        $out = gmp_mul($out, $i);
    }
    return($out);
}

function eval_power($a, $b)
{
  # Darn fool php folks made the second variable for gmp_pow a regular integer!
    return gmp_pow($a, my_gmp_intval($b));
}

function eval_neg($a, $b)
{
  # Parser passes two parameters, this annoys gmp_neg
    return gmp_neg($a);
}

function eval_parenthesis($a)
{
    return $a;
}

function eval_fact($a, $b = '', $c = '')
{
    if (empty($b) or $b == 'NaN') {
        $n = $a;  # Simple factorial
        $step = 1;
    } else {
        $n = $b;
        $step = $a;
    }
    $result = 1;
    while (gmp_cmp($n, 1) > 0) {
        $result = gmp_mul($result, $n);
        $n = gmp_sub($n, $step);
    }

    return $result;
}

function eval_concat($a, $b)
{
  // String concatination of gmp integers: 2_3.0_3.5_3 = 2220333
    return gmp_init(my_gmp_strval($a) . gmp_strval($b));
}

function eval_repeat($a, $b)
{
  // String repetition of gmp integers: 2_3.0_3.5_3 = 2220333
    return gmp_init(str_repeat(my_gmp_strval($a), my_gmp_intval($b)));
}

# All this does is build the list into an array

function eval_list($a, $b)
{
    if (is_array($a)) {
        array_push($a, $b);
        return $a;
    } else {
        return array($a,$b);
    }
}
