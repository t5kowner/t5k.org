<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

use function Math\isGaussianMersenne;

class GaussianMersenne extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        if (isGaussianMersenne($prime->prime)) {
            $comment = array_filter($prime->singleComment, function ($x) {
                return preg_match('/^gaussian Mersenne norm \d+\??$/', $x);
            });
            if (sizeof($comment) < 1) {
                $comment = "Gaussian Mersenne norm";
            } else {
                $comment = ucfirst($comment[0]);
            }
            return [new LintResult(Category::GaussianMersenne, true, $comment)];
        }
        return [];
    }
}
