<?php

namespace Top20;

use Math\Number;
use PDO;

/**
 * Represents how an archival tag should be created (static properties)
 */
final class LintResult
{
    /**
     * @var int The number of primes on this list that are considered "on the list"
     */
    private readonly int $threshold;
    /**
     * Creates a LintResult
     *
     * @param Category $category What kind of archival tag?
     * @param bool $verified Was this completely verified, or is additional work needed later? (ex: GF divisor)
     * @param string $officialComment What should be part of the official comment for this prime?
     * @param string $subcategory The subcategory for this tag (ex: length of AP)
     **/
    public function __construct(
        readonly public Category $category,
        readonly public bool $verified,
        readonly public string $officialComment,
        readonly public string $subcategory = "",
        int $threshold = -1
    ) {
        $this->threshold = ($threshold == -1) ?
            ($this->subcategory == "" ? ARCHIVABLE_COUNT : ARCHIVABLE_CLASS_COUNT) : $threshold;
    }

    public function onList(PDO $db, string $description): bool
    {
        if ($this->threshold == 0) {
            return false; //nothing qualifies from this list
        }

        if (
            !$result = $db->query(
                "SELECT prime.digits, prime.id, prime.description, prime.blob_id 
                 FROM archival_tag INNER JOIN prime ON archival_tag.prime_id=prime.id 
                 WHERE archival_tag.category_id=" . $this->category->value .
                (empty($this->subcategory) ? " " : " AND archival_tag.subcategory=\"$this->subcategory\" ") .
                "ORDER BY archival_tag.rank_now ASC 
                 LIMIT 1 OFFSET " . ($this->threshold - 1)
            )
        ) {
            return false;
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return true; //not enough to even fill out the list
        }
        $submitNum = (new Number())->fromDescription($description);
        if ($row['digits'] != $submitNum->getDigits()) {
            return $submitNum->getDigits() > $row['digits'];
        }
        //same digits - compare raw output
        $dbNum = (new Number())->fromDB($db, $row['id'], $row['description'], $row['digits'], $row['blob_id']);
        return gmp_cmp($submitNum->getExpansion(), $dbNum->getExpansion()) > 0;
    }
}
