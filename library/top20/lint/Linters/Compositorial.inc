<?php

namespace Top20\Linters;

use Top20\Linters\BaseLinter;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

use const Top20\ARCHIVABLE_TOLERATED_COUNT;

class Compositorial extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(\PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        if (preg_match('/^(\d+)!\/(\d+)#+[+\-]1$/', $prime->prime, $matches)) {
            $stop = gmp_init($matches[1]);
            $start = gmp_init($matches[2]);
            $n = gmp_add($start, 1);
            $verified = true;
            while (gmp_cmp($n, $stop) <= 0) {
                $isPrime = gmp_prob_prime($n);
                if ($isPrime == 2) {
                    return [];
                } elseif ($isPrime == 1) {
                    $verified = false;
                }
                $n = gmp_add($n, 1);
            }
            return [new LintResult(
                Category::Compositorial,
                $verified,
                "compositorial",
                "",
                ARCHIVABLE_TOLERATED_COUNT
            )];
        }
        return [];
    }
}
