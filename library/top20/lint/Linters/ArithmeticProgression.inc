<?php

namespace Top20\Linters;

use Math\Number;
use PDO;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

use function Top20\doesNumberExist;

class ArithmeticProgression extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {

        /** @var LintResult[] $ret */
        $ret = [];

        foreach ($prime->singleComment as $comment) {
            //handling here is a little awkward - both CPAPs and APs share the same comment
            //a prime can have AP, CPAP, both, or neither tags
            //so for now, we will return all appropriate tags, but might have the same comment to avoid confusion
            if (
                preg_match(
                    '/^(consecutive primes )?arithmetic progression \((\d+),d=(.*)\)$/',
                    $comment,
                    $matches
                )
            ) {
                $isConsecutive = !empty($matches[1]);
                $index = $matches[2];
                $distance = $matches[3];
                if ($distance == 2) {
                    break; //twins aren't included in CPAPs
                }

                //check that all entries before this one are on the list
                for ($i = 1; $i < $index; $i++) {
                    if (
                        !doesNumberExist($db, (new Number())->fromDescription(
                            "(" . $prime->prime . ")-" . ($index - $i) . "*(" . $distance . ")"
                        ), $others)
                    ) {
                        continue 2;
                    }
                }

                //see how far this AP goes
                $apLength = $index;
                while (
                    doesNumberExist($db, (new Number())->fromDescription(
                        "(" . $prime->prime . ")+" . ($apLength + 1 - $index) . "*(" . $distance . ")",
                    ), $others)
                ) {
                    $apLength++;
                }

                if ($apLength > 1 && $isConsecutive) {
                    $ret[] = new LintResult(
                        Category::APConsecutive,
                        false, //we haven't verified all numbers in between are composite
                        "consecutive primes arithmetic progression ($index,d=$distance)",
                        "$index/$apLength"
                    );
                }
                if ($apLength > 2) {
                    $ret[] = new LintResult(
                        Category::ArithmeticProgression,
                        true,
                        ($isConsecutive ? "consecutive primes " : "") . "arithmetic progression ($index,d=$distance)",
                        "$index/$apLength"
                    );
                }
            }
        }

        return $ret;
    }
}
