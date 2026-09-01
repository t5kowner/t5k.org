<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\Linters\BaseLinter;
use Top20\LintResult;
use Top20\SubmittedPrime;

class GeneralizedLucasPrimPart extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        if (preg_match('/^prim[UV]\(\d+,-?\d+,-?\d+\)$/', $prime->prime)) {
            return [new LintResult(Category::GeneralizedLucasPrim, false, "generalized Lucas primitive part")];
        }
        return [];
    }
}
