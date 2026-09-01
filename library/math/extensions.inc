<?php

namespace Math;

use GMP;

/**
 * @param GMP $num Number that might be a perfect power
 * @param ?int $base Minimal base that can represent $num as a power
 * @param ?int $power Maximal power that can represent $num as a power
 * @return bool True if $num is a perfect power, false otherwise
 */
function evalPerfectPower(GMP $num, ?int &$base, ?int &$power): bool
{
    //2^maxPow is >= to $num
    $maxPow = strlen(gmp_strval($num, 2)); //would like mpz_sizeinbase...

    for ($power = $maxPow; $power >= 2; $power--) {
        $roots = gmp_rootrem($num, $power);
        if (!gmp_cmp($roots[1], 0)) {
            $base = gmp_intval($roots[0]);
            return true;
        }
    }
    return false;
}

/**
 * @param string $num Number to check (matching description in database)
 * @return bool Whether the number is Gaussian Mersenne
 */
function isGaussianMersenne(string $num): bool
{
    //2^a +/- 2^b + 1
    if (preg_match('/^2\^(\d+)[+\-]2\^(\d+)\+1$/', $num, $matches)) {
        //2b-a=1
        if (!gmp_cmp(gmp_sub(gmp_mul(2, $matches[2]), $matches[1]), 1)) {
            return true;
        }
    }
    return false;
}

/**
 * @param int $num Number to calculate radical of
 * @return int  Radical of $num (product of all unique prime factors)
 */
function radical(int $num): int
{
    $ret = 1;
    if (!($num % 2)) {
        $ret *= 2;
        $num /= 2;
    }
    for ($i = 3; $num != 1; $i += 2) {
        if (!($num % $i)) {
            $ret *= $i;
            while (!($num % $i)) {
                $num /= $i;
            }
        }
    }
    return $ret;
}
