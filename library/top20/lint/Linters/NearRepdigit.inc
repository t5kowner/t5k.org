<?php

namespace Top20\Linters;

use PDO;
use Top20\Linters\BaseLinter;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

class NearRepdigit extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        $count = count_chars($prime->number->getExpansion(), 1);
        if (count($count) != 2) {
            return [];
        }
        $vals = array_values($count);
        if ($vals[0] == 1 || $vals[1] == 1) {
            return [new LintResult(Category::NearRepdigit, true, "near-repdigit")];
        }
        return [];
    }
}
