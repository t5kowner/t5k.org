<?php

namespace Top20\Linters;

use PDO;
use Top20\Linters\BaseLinter;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

class GeneralizedFermat extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        //n^2+1 is good enough since a GFN 2+ can be rewritten as a GFN 1
        if (gmp_perfect_square(gmp_sub($prime->number->getExpansion(), 1))) {
            return [new LintResult(Category::GeneralizedFermat, true, "generalized Fermat")];
        }
        return [];
    }
}
