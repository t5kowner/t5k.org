<?php

namespace Top20\Linters;

use Math\Number;
use PDO;
use Top20\Linters\BaseLinter;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

use function Top20\doesNumberExist;

use const Top20\ARCHIVABLE_CLASS_COUNT;
use const Top20\ARCHIVABLE_COUNT;

class CunninghamChain extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        $found = false;
        foreach ($prime->singleComment as $comment) {
            if (
                str_starts_with($comment, "cunningham chain (") ||
                str_starts_with($comment, "cunningham chain 2nd kind (") ||
                str_starts_with($comment, "sophie germain (")
            ) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return [];
        }
        foreach (["+", "-"] as $sign) {
            $prev = $this->getChainLengthBackward($db, $prime, $others, $sign);
            $next = $this->getChainLengthForward($db, $prime, $others, $sign);
            $total = $next + $prev + 1;
            if ($total < 2) {
                continue;
            }
            $category = $sign == "+" ? Category::CunninghamChain : Category::CunninghamChain2;
            $threshold = ARCHIVABLE_CLASS_COUNT;
            $comment = $prev == 0 ? "(p)" : "(" . (2 ** $prev) . "p" . $sign . (2 ** $prev - 1) . ")";
            if ($sign == "+" && $total == 2) {
                //weird carve out for Sophie Germain
                $category = Category::SophieGermain;
                $threshold = ARCHIVABLE_COUNT;
            }
            switch ($category) {
                case Category::SophieGermain:
                    $comment = "Sophie Germain " . $comment;
                    break;
                case Category::CunninghamChain:
                    $comment = "Cunningham chain " . $comment;
                    break;
                case Category::CunninghamChain2:
                    $comment = "Cunningham chain 2nd kind " . $comment;
                    break;
                default: //unreachable
            }
            return [new LintResult($category, true, $comment, $comment, $threshold)];
        }
        return [];
    }

    /**
     * Determines how long a cunningham chain goes "forward" (increasing multiplier)
     * @param PDO $db database handle
     * @param SubmittedPrime $prime Prime to look forward from
     * @param array $others Other primes that might be part of the chain
     * @param string $sign "+" for cunningham chains of 1st kind, "-" for second kind
     * @return int Number of primes that continue this chain
     */
    private function getChainLengthForward(PDO $db, SubmittedPrime $prime, array $others, string $sign): int
    {
        $ret = 0;
        $mult = 2;
        $add = 1;
        $sign = $sign == "+" ? "-" : "+";
        while (
            doesNumberExist(
                $db,
                (new Number())->fromDescription("(" . $prime->number->getExpansion() . $sign . $add . ")/" . $mult),
                $others
            )
        ) {
            $ret++;
            $mult *= 2;
            $add = $mult - 1;
        }
        return $ret;
    }

    /**
     * Determines how long a cunningham chain goes "backward" (decreasing multiplier)
     * @param PDO $db database handle
     * @param SubmittedPrime $prime Prime to look backward from
     * @param array $others Other primes that might be part of the chain
     * @param string $sign "+" for cunningham chains of 1st kind, "-" for second kind
     * @return int Number of primes that continue this chain
     */
    private function getChainLengthBackward(PDO $db, SubmittedPrime $prime, array $others, string $sign): int
    {
        $ret = 0;
        $mult = 2;
        $add = 1;
        while (
            doesNumberExist(
                $db,
                (new Number())->fromDescription($mult . "*(" . $prime->number->getExpansion() . ")" . $sign . $add),
                $others
            )
        ) {
            $ret++;
            $mult *= 2;
            $add = $mult - 1;
        }
        return $ret;
    }
}
