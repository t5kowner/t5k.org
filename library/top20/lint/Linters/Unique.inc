<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\Linters\BaseLinter;
use Top20\LintResult;
use Top20\SubmittedPrime;

use function Math\radical;

class Unique extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        //repunits are unique
        if (preg_match('/^R\(\d+\)$/', $prime->prime)) {
            return [new LintResult(Category::Unique, true, "unique")];
        }

        //Phi(a,b) requires more work
        if (preg_match('/^Phi\((\d+),(.+)\)$/', $prime->prime, $matches)) {
            $a = $matches[1];
            $b = $matches[2];
            $isNegativeExp = $b[0] === "-";
            if ($isNegativeExp) {
                $b = substr($b, 1);
            }
            $exp = strlen($b) - 1;
            if (preg_match('/^10\^(\d+)$/', $b, $matches)) {
                $exp = $matches[1];
            } elseif (!preg_match('/^10+$/', $b)) {
                return [];
            }
            if ($isNegativeExp && preg_match('/[02468]$/', $a)) {
                return [];
            }
            if ($isNegativeExp) {
                $a *= 2;
            }
            if (!($a % radical($exp))) {
                return [new LintResult(Category::Unique, true, "unique")];
            }
        }
        return [];
    }
}
