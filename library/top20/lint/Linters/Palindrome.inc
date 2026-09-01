<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\Linters\BaseLinter;
use Top20\LintResult;
use Top20\SubmittedPrime;

class Palindrome extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        if (strrev($prime->number->getExpansion()) == $prime->number->getExpansion()) {
            //technically a palindrome, but we don't want to include repunits here really since they have another list
            if (str_replace("1", "", $prime->number->getExpansion()) !== "") {
                return [new LintResult(Category::Palindrome, true, "palindrome")];
            }
        }
        return [];
    }
}
