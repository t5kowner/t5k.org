<?php

namespace Top20;

use Math\Number;
use PDO;
use Top20\Linters\AllLinters;

/**
 * @param PDO $db Database handle
 * @param SubmittedPrime $prime prime to lint
 * @param array<SubmittedPrime> $others Other primes to test $prime against (ex: twin prime submission)
 * @return array<LintResult> archival tag info
 */
function lintAll(PDO $db, SubmittedPrime $prime, array $others = []): array
{
    return (new AllLinters())->lint($db, $prime, $others);
}


/**
 * @param PDO $db Database handle
 * @param Number $number Number to look for in database
 * @param array<SubmittedPrime> $others numbers not in database that should be treated as if they are in the database
 * @return bool True if number is found, false otherwise
 */
function doesNumberExist(PDO $db, Number $number, array $others): bool
{
    //check others first as that's most likely + avoids extra database checks
    foreach ($others as $other) {
        if ($other->number->getExpansion() === $number->getExpansion()) {
            return true;
        }
    }

    //only check for numbers with the correct amount of digits in the database
    $result = $db->query("SELECT id, description, digits, blob_id FROM prime WHERE digits = " . $number->getDigits());
    while ($row = $result->fetch()) {
        $otherNum = new Number();
        if (!$row["blob_id"]) {
            $row["blob_id"] = null; //there's some zeros in the DB
        }
        $otherNum->fromDB($db, $row["id"], $row["description"], +$row["digits"], $row["blob_id"]);
        if ($otherNum->getExpansion() === $number->getExpansion()) {
            return true;
        }
    }
    return false;
}
