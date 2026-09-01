<?php

namespace Top20\Linters;

use Math\Number;
use PDO;
use Top20\Category;
use Top20\LintResult;
use Top20\SubmittedPrime;

use function Top20\doesNumberExist;

class KTuplet extends BaseLinter
{
    private const NAMES = [
        3 => "triplet",
        4 => "quadruplet",
        5 => "quintuplet",
        6 => "sextuplet",
        7 => "septuplet",
    ];

    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        foreach ($prime->singleComment as $comment) {
            $k = 0;
            $n = 0;
            foreach (self::NAMES as $index => $name) {
                if (preg_match('/^' . $name . ' \((\d+)\)$/', $comment, $matches)) {
                    $k = $index;
                    $n = $matches[1];
                    break;
                }
            }
            if (preg_match('/^(\d+)-tuplet \((\d+)\)$/', $comment, $matches)) {
                $k = $matches[1];
                $n = $matches[2];
            }
            if ($k >= 3 && $n <= $k) {
                $d = $this->getDiameter($k);
                $minIndex = $n;
                $maxIndex = $n;
                $minDiff = 0;
                $maxDiff = 0;
                for ($diff = 2; $diff <= $d; $diff += 2) {
                    if (
                        $minIndex > 1 && doesNumberExist(
                            $db,
                            (new Number())->fromDescription("(" . $prime->prime . ")-" . $diff),
                            $others
                        )
                    ) {
                        $minIndex--;
                        $minDiff = $diff;
                    }
                    if (
                        $maxIndex < $k && doesNumberExist(
                            $db,
                            (new Number())->fromDescription("(" . $prime->prime . ")+" . $diff),
                            $others
                        )
                    ) {
                        $maxIndex++;
                        $maxDiff = $diff;
                    }
                }
                if ($minIndex == 1 && $maxIndex == $k && ($minDiff + $maxDiff == $d)) {
                    $comment = $k . "-tuplet";
                    if (isset(self::NAMES[$k])) {
                        $comment = self::NAMES[$k];
                    }
                    $comment .= " ($n)";
                    return [new LintResult($this->getCategory($k), true, $comment, $comment)];
                }
            }
        }
        return [];
    }

    private function getCategory(int $k): Category
    {
        return match ($k) {
            3 => Category::Triplet,
            4 => Category::Quadruplet,
            5 => Category::Quintuplet,
            6 => Category::Sextuplet,
            7 => Category::Septuplet,
            default => Category::ktuplet,
        };
    }

    private function getDiameter(int $ktupleSize): int
    {
        //get primes <= $ktupleSize
        $primesToCheck = [];
        for ($i = 2; $i <= $ktupleSize; $i++) {
            $isPrime = true;
            foreach ($primesToCheck as $p) {
                if ($i % $p === 0) {
                    $isPrime = false;
                    break;
                }
            }
            if ($isPrime) {
                $primesToCheck[] = $i;
            }
        }

        //search for minimal diameter
        for ($size = 2 * $ktupleSize; true; $size += 2) {
            //initial values
            $vals = array_fill(0, $ktupleSize, 0);
            $vals[0] = 0;
            $vals[$ktupleSize - 1] = $size;
            //indices for middle positions
            $indices = range(1, $ktupleSize - 2);
            if ($this->searchCombination($indices, 0, $vals, $size, $primesToCheck, $ktupleSize)) {
                return $size;
            }
        }
    }
    private function isValidPattern(array $vals, array $primesToCheck): bool
    {
        foreach ($primesToCheck as $prime) {
            $modsSeen = [];
            foreach ($vals as $val) {
                $modsSeen[$val % $prime] = 1;
                if (count($modsSeen) >= $prime) {
                    return false;
                }
            }
        }
        return true;
    }

    private function searchCombination(
        array $indices,
        int $pos,
        array &$vals,
        int $size,
        array $primesToCheck,
        int $ktupleSize
    ): bool {
        if ($pos === count($indices)) {
            return $this->isValidPattern($vals, $primesToCheck);
        }
        $idx = $indices[$pos];
        $prev = $vals[$idx - 1];
        $min = $prev + 2;
        $remaining = count($indices) - $pos - 1;
        $max = $size - 2 * ($remaining + 1);
        for ($v = $min; $v <= $max; $v += 2) {
            $vals[$idx] = $v;
            //ugly recursion, but only up to ~$ktupleSize deep, so whatever
            //might be worth rethinking someday though
            if ($this->searchCombination($indices, $pos + 1, $vals, $size, $primesToCheck, $ktupleSize)) {
                return true;
            }
        }
        return false;
    }
}
