<?php

namespace Top20\Linters;

use PDO;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

use function Math\isGaussianMersenne;

class GeneralizedUnique extends BaseLinter
{
    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        //note: this logic isn't foolproof, but it's impossible to be foolproof when the definition is so awkward
        //see issue #12

        //first, is it Phi(a,b)?
        if (preg_match('/^Phi\((.*)\)$/', $prime->prime, $matches)) {
            //determine a and b, keeping matching parens in mind
            $parenLevel = 0;
            $buffer = "";
            $a = "";
            foreach (str_split($matches[1]) as $char) {
                switch ($char) {
                    case '(':
                        $parenLevel++;
                        $buffer .= $char;
                        break;
                    case ')':
                        $parenLevel--;
                        if ($parenLevel < 0) {
                            return [];
                        }
                        $buffer .= $char;
                        break;
                    case ',':
                        if ($buffer == "") {
                            //we don't accept null for any argument, ever
                            return [];
                        }
                        if ($parenLevel > 0) {
                            $buffer .= $char;
                        } elseif ($a == "") {
                            $a = $buffer;
                            $buffer = "";
                        } else {
                            //there's no 3rd argument to Phi
                            return [];
                        }
                        break;
                    default:
                        $buffer .= $char;
                }
            }
            if ($a == "" || $buffer == "") {
                return [];
            }

            //we have $a and $b - is $buffer ($b) a power of 10? If so, it's unique instead of generalized unique
            if (preg_match('/^-?(10+|10\^\d+)$/', $buffer)) {
                return [];
            }
            return [new LintResult(Category::GeneralizedUnique, true, "generalized Unique")];
        }
        //other option - Gaussian Mersenne
        if (isGaussianMersenne($prime->prime)) {
            return [new LintResult(Category::GeneralizedUnique, true, "generalized Unique")];
        }
        //other option - it's the expanded form of Phi(3,n), n^2+n+1
        if (preg_match('/^(.*)\^(\d+)[+\-]\1\^(\d+)\+1$/', $prime->prime, $matches)) {
            if (!gmp_cmp($matches[2], gmp_mul($matches[3], 2))) {
                return [new LintResult(Category::GeneralizedUnique, true, "generalized Unique")];
            }
        }
        return [];
    }
}
