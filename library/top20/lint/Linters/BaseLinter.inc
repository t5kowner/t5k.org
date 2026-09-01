<?php

namespace Top20\Linters;

use PDO;
use Top20\LintResult;
use Top20\SubmittedPrime;

abstract class BaseLinter
{
    /**
     * Determines if/how a submission qualifies for a particular archival tag
     *
     *
     * @param PDO $db Database handle
     * @param SubmittedPrime $prime Prime to lint
     * @param array<SubmittedPrime> $others Other primes in this submission (ex: to check for existence of twin)
     * @return array<LintResult> Info for archival tag if qualified, otherwise null
     **/
    abstract public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array;
}
