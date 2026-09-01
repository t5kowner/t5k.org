<?php

namespace Top20\Linters;

use PDO;
use Top20\Linters\BaseLinter;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

class Factorial extends BaseLinter
{

    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        //I can't imagine a case where something other than <number>! makes sense, so I'm not bothering to implement it
        if (preg_match("/^\d+![+\-]1$/", $prime->prime)) {
            return [new LintResult(Category::Factorial, true, "factorial")];
        }
        return [];
    }
}