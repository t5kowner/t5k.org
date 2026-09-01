<?php

namespace Top20\Linters;

use PDO;
use Top20\Linters\BaseLinter;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

use const Top20\ARCHIVABLE_TOLERATED_COUNT;

class Multifactorial extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        //https://t5k.org/primes/page.php?id=67139 makes this awkward
        //ultimately, we want to see <something>!<something>...+/-1
        //where <something> is either a number or anything contained within parenthesis

        if (!preg_match('/[+\-]1$/', $prime->prime)) {
            return [];
        }
        //it ends in +/-1. Remove it now to simplify the rest
        $val = substr($prime->prime, 0, strlen($prime->prime) - 2);

        $pieces = explode("!", $val);
        for ($i = 1; $i < sizeof($pieces); $i++) {
            //everything up to the $i-th piece should be in the same set of parens (or just a number)
            if ($this->piecesOK($pieces, 0, $i - 1) && $this->piecesOK($pieces, $i, sizeof($pieces) - 1)) {
                return [new LintResult(
                    Category::Multifactorial,
                    true,
                    "multifactorial",
                    "",
                    ARCHIVABLE_TOLERATED_COUNT
                )];
            }
        }
        return [];
    }

    private function piecesOK(array $pieces, int $startLoc, int $endLoc): bool
    {
        $str = implode("!", array_slice($pieces, $startLoc, $endLoc - $startLoc + 1));
        if (preg_match('/^[0123456789]+$/', $str)) {
            return true;
        }
        if (!str_starts_with($str, "(") || !str_ends_with($str, ")")) {
            return false;
        }
        $cnt = count_chars($str);
        return $cnt[ord("(")] == $cnt[ord(")")];
    }
}
