<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\Linters\BaseLinter;
use Top20\LintResult;
use Top20\SubmittedPrime;

class LehmerPrimPart extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        // TODO add actual validation after https://github.com/t5kowner/t5k.org/discussions/54 is decided...
        if (in_array("lehmer primitive part", $prime->singleComment)) {
            return [new LintResult(Category::LehmerPrimitive, false, "Lehmer primitive part")];
        }
        return [];
    }
}
