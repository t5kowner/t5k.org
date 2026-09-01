<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\Linters\BaseLinter;
use Top20\LintResult;
use Top20\SubmittedPrime;

class Lehmer extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        //this is very weak, but matches old logic
        //will hopefully be expanded on once the definition is ironed out more
        if (in_array("lehmer number", $prime->singleComment)) {
            return [new LintResult(Category::Lehmer, false, "Lehmer number")];
        }
        return [];
    }
}
