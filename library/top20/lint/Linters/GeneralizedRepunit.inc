<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

class GeneralizedRepunit extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        //we allow Generalized Repunits if they can be written as (b^n-1)/(b-1)
        //with the restrictions b!=2 (these are Mersenne), b!=10 (this is a Repunit), b>0 (negative bases are weird),
        //n>=5 (everything works with n=2, making the list pointless,
        //n=3 is testable as n(n+1)+1, making n-1 tests easy which would fill the list,
        //and n=4 is never prime since 4 isn't prime)
        if (preg_match('/^\((\d+)\^(\d+)-1\)\/(\d+)$/', $prime->prime, $matches)) {
            $b = $matches[1];
            $n = $matches[2];
            $b2 = $matches[3];
            if ($b > 3 && $n >= 5 && ($b - 1 == $b2) && $b != 10) {
                return [new LintResult(Category::GeneralizedRepunit, true, "generalized repunit")];
            }
        } elseif (preg_match('/^R\(\d+\)$/', $prime->prime)) {
            return [new LintResult(Category::Repunit, true, "repunit")];
        }

        //could be awkward edge case - check for manual comment and trust it for now
        if (
            in_array("generalized repunit", $prime->singleComment)
            || in_array("Generalized repunit", $prime->singleComment)
        ) {
            return [new LintResult(Category::GeneralizedRepunit, false, "generalized repunit")];
        }
        return [];
    }

    //this is too slow to run on submission.
    //Keeping around as a starting point for edge cases (though this is largely untested)
    public function verifyLint(SubmittedPrime $prime): array
    {
        for ($b = gmp_init(3), $bPrev = gmp_init(2);; $bPrev = $b, $b = gmp_add($b, 1)) {
            $bPow = gmp_add(gmp_mul($prime->number->getExpansion(), $bPrev), 1);
            //are we past n=5 being viable?
            if (gmp_cmp(gmp_root($bPow, 5), $b) < 1) {
                return [];
            }
            if (gmp_perfect_power($bPow)) {
                //we don't care what power this is - just that it's at least 5
                for ($n = 2; $n <= 4; $n++) {
                    $res = gmp_rootrem($bPow, $n);
                    if (!gmp_cmp($res[0], $b) && !gmp_cmp($res[1], 0)) {
                        continue 2;
                    }
                }
                //base 10 is special
                if (!gmp_cmp($b, 10)) {
                    return [new LintResult(Category::Repunit, true, "repunit")];
                }

                //we have something!
                return [new LintResult(Category::GeneralizedRepunit, true, "generalized repunit")];
            }
        }
    }
}
