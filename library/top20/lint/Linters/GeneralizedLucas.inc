<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\Linters\BaseLinter;
use Top20\LintResult;
use Top20\SubmittedPrime;

class GeneralizedLucas extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        if (preg_match('/^[UV]\((\d+),-?(\d+),(\d+)\)$/', $prime->prime, $matches)) {
            $p = $matches[1];
            $q = $matches[2];
            $n = $matches[3];

            $d = $p * $p - 4 * $q;

            if ((5 * $n > $p) && (5 * $n > sqrt(abs($d)))) {
                return [new LintResult(Category::GeneralizedLucas, true, "generalized Lucas number")];
            }
        } elseif (preg_match('/^\(2\^(\d+)\+1\)\/3$/', $prime->prime)) {
            return [new LintResult(Category::GeneralizedLucas, true, "generalized Lucas number")];
        }
        return [];
    }
}
