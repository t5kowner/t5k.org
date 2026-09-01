<?php

namespace Top20\Linters;

use PDO;
use Top20\Linters\BaseLinter;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

class FibonacciPrimPart extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        // I couldn't find an easy/quick way to make this foolproof,
        // so we'll trust people submit it in the most reasonable form for now
        if (preg_match('/^primU\(\d+\)$/', $prime->prime)) {
            return [new LintResult(Category::FibonacciPrimPart, true, "Fibonacci primitive part")];
        }
        return [];
    }
}
