<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\Linters\BaseLinter;
use Top20\LintResult;
use Top20\SubmittedPrime;

class MersenneCofactor extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        if (preg_match('/^\(2\^\d+-1\)(\/\d+)+$/', $prime->prime)) {
            return [new LintResult(Category::MersenneCofactor, false, "Mersenne cofactor")];
        }
        //The most likely failure is a Mersenne with a ton of divisors that uses a blob - trust it for now
        if (in_array("mersenne cofactor", $prime->singleComment)) {
            return [new LintResult(Category::MersenneCofactor, false, "Mersenne cofactor")];
        }
        return [];
    }
}
