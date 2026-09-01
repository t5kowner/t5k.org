<?php

namespace Top20\Linters;

use PDO;
use Top20\Linters\BaseLinter;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

class LucasPrimitive extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        // I couldn't find an easy/quick way to make this foolproof,
        // so we'll trust people submit it in the most reasonable form for now
        if (preg_match('/^primV\(\d+\)$/', $prime->prime)) {
            return [new LintResult(Category::LucasPrimitive, true, "Lucas primitive part")];
        }
        return [];
    }
}
