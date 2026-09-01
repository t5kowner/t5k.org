<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

class FibonacciCofactor extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        if (preg_match('/^U\((\d+)\)(\/\d+)+$/', $prime->prime, $matches)) {
            //if not prime index, skip
            //if probably prime, mark verified=false for further work later
            $isPrime = gmp_prob_prime($matches[1]);
            if ($isPrime == 0) {
                return [];
            }
            return [new LintResult(Category::FibonacciCofactor, ($isPrime == 2), "Fibonacci cofactor")];
        }
        //The most likely failure is a blob with a ton of divisors - trust it for now
        if (in_array("fibonacci cofactor", $prime->singleComment)) {
            return [new LintResult(Category::FibonacciCofactor, false, "Fibonacci cofactor")];
        }
        return [];
    }
}
