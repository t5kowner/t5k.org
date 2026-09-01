<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

class Fibonacci extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        //a number is a fibonacci number iff 5x^2+4 or 5x^2-4 is a perfect square
        $mult = gmp_mul(gmp_mul($prime->number->getExpansion(), $prime->number->getExpansion()), 5);
        if (gmp_perfect_square(gmp_sub($mult, 4)) || gmp_perfect_square(gmp_add($mult, 4))) {
            return [new LintResult(Category::Fibonacci, true, "Fibonacci")];
        }
        return [];
    }
}
