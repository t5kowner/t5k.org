<?php

namespace Top20\Linters;

use PDO;
use Top20\Linters\BaseLinter;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

use const Top20\ARCHIVABLE_COUNT;

class DividesSomething extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {

        $results = [];
        foreach ($prime->singleComment as $comment) {
            if (!str_starts_with($comment, "divides ")) {
                $comment = "divides " . $comment;
            }

            if (preg_match("/^divides fermat f\\(\\d+\\)$/", $comment)) {
                $results[] = new LintResult(Category::FermatDivisor, false, $this->cleanComment($comment, sizeof($results) > 0));
                continue;
            }

            if (preg_match("/^divides gf\\(\\d+,(3|5|6|10|12)\\)$/", $comment, $matches)) {
                $results[] = new LintResult(
                    Category::GFDivisor,
                    false,
                    $this->cleanComment($comment, sizeof($results) > 0),
                    "Divides GF(*,$matches[1])",
                    ARCHIVABLE_COUNT
                );
                continue;
            }

            //weird handling here seems to be because of https://t5k.org/primes/page.php?id=97516
            if (preg_match("/^divides phi\\(\\d+\\^\\d+(|\\/\\d+|\\/\\d+\\^\\d+),\\d+\\)$/", $comment)) {
                $results[] = new LintResult(Category::DividesPhi, false, $this->cleanComment($comment, sizeof($results) > 0));
            }
        }
        return $results;
    }

    private function cleanComment(string $comment, bool $isFirst): string
    {
        //first, capitalize Fermat F, GF, and Phi
        $comment = str_replace(["fermat f", "gf", "phi"], ["Fermat F", "GF", "Phi"], $comment);
        //then make sure the first starts with "divides "
        if ($isFirst) {
            return $comment;
        }
        return substr($comment, 8, strlen($comment));
    }
}
