<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

use function Math\evalPerfectPower;

class CullenWoodall extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        if (!preg_match("/^(\\d+)\\*(\\d+)\\^(\\d+)([+-])1$/", $prime->prime, $matches)) {
            return [];
        }
        $base = $this->getCWBase($matches[1], $matches[2], $matches[3]);
        if (!$base) {
            return [];
        }
        $generalized = $base != 2;
        $woodall = $matches[4] == "-";
        return [new LintResult(
            ($generalized ?
                ($woodall ? Category::GeneralizedWoodall : Category::GeneralizedCullen) :
                ($woodall ? Category::Woodall : Category::Cullen)),
            true,
            ($generalized ? "generalized " : "") . ($woodall ? "Woodall" : "Cullen"),
        )];
    }

    /** Given k*b^n, attempt to determine if it can be rewritten as n*b^n with n+2>b
     * @param string $kIn k of k*b^n
     * @param string $bIn b of k*b^n
     * @param string $nIn n of k*b^n
     * @return string|false The b of the new expression if successful, false otherwise
     */
    private function getCWBase(string $kIn, string $bIn, string $nIn): string|false
    {
        $origK = gmp_init($kIn);
        $origB = gmp_init($bIn);
        $origN = gmp_init($nIn);
        //it's possible for a number to be written as n*b^n for multiple combinations of numbers
        //a combination with larger b is less likely to meet the n+2>b criteria, so start by minimizing b
        //(b^x)^n = b^(xn)
        if (evalPerfectPower($origB, $outputBase, $outputPower)) {
            $origN = gmp_mul($origN, $outputPower);
            $origB = $outputBase;
        }

        //minimize K too initially
        for ($res = gmp_div_qr($origK, $origB); !gmp_cmp($res[1], 0); $origK = $res[0], $origN = gmp_add($origN, 1), $res = gmp_div_qr($origK, $origB)) ;

        $attemptB = $origB;
        $attemptK = $origK;
        $attemptN = $origN;
        $lastDivisor = 1;
        while (true) {
            //we're only going to increase K from here
            //if it's already too big, stop
            if (gmp_cmp($attemptK, $attemptN) > 0) {
                return false;
            }

            //we're also only increasing B, while simultaneously decreasing N
            //if n+2>b isn't true now, it won't be true ever - give up
            if (gmp_cmp(gmp_add($attemptN, 2), $attemptB) < 1) {
                return false;
            }

            //increase K by multiplying by B until it's greater than or equal to N
            while (gmp_cmp($attemptK, $attemptN) < 0) {
                $attemptK = gmp_mul($attemptK, $attemptB);
                $attemptN = gmp_sub($attemptN, 1);
            }
            //if equal and n+2>b, we're done! return b
            if (!gmp_cmp($attemptK, $attemptN) && gmp_cmp(gmp_add($attemptN, 2), $attemptB) > 0) {
                return gmp_strval($attemptB);
            }

            //find another B...
            $lastDivisor++;
            $res = gmp_div_qr($origN, $lastDivisor);
            $attemptK = gmp_mul($origK, gmp_pow($origB, gmp_intval($res[1])));
            $attemptN = $res[0];
            $attemptB = gmp_pow($origB, $lastDivisor);
        }
    }
}
