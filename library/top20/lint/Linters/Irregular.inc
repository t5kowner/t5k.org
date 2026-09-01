<?php

namespace Top20\Linters;

use PDO;
use Top20\Linters\BaseLinter;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

class Irregular extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        //if the number looks like an irregular prime, we can stop there
        if (preg_match("/^-?\\d+\\*Bern\\(\\d+\\)\\/[\\/0-9]*$/", $prime->prime)) {
            return [new LintResult(Category::Irregular, true, "irregular")];
        }

        //we could have something more awkward - such as a blob that doesn't fit the submission field
        //could be valid, but more difficult to verify
        //for now, require the submitter to have a comment, and we'll verify later
        if (in_array("irregular", $prime->singleComment)) {
            return [new LintResult(Category::Irregular, false, "irregular")];
        }
        return [];
    }
}
