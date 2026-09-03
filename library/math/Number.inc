<?php

namespace Math;

use PDO;

class Number
{
    /**
     * @var ?PDO database handle, if this is from a database record
     */
    private ?PDO $db;
    /**
     * @var ?string ID in the primes table
     */
    private ?string $id = null;
    /**
     * @var ?string description in the primes table. Can be set even if this doesn't exist in the primes table yet
     */
    private ?string $desc = null;
    /**
     * @var ?string ID in the prime_blob table
     */
    private ?string $blobId = null;
    /**
     * @var ?int Number of digits in the number. Used instead of log10 here to avoid rounding issues
     */
    private ?int $digits = null;
    /**
     * @var ?string full decimal expansion of a number
     */
    private ?string $decimalExpansion = null;
    /**
     * @var ?float log10 of the number
     */
    private ?float $log10 = null;

    public function getLog10(): float
    {
        if ($this->log10) {
            return $this->log10;
        }
        $this->populateLog10AndDigits();
        return $this->log10;
    }

    /**
     * @return int number of digits in the number
     */
    public function getDigits(): int
    {
        if ($this->digits) {
            return $this->digits;
        }
        $this->populateLog10AndDigits();
        return $this->digits;
    }

    private function populateLog10AndDigits(): void
    {
        if ($this->blobId) {
            $result = $this->db->query("SELECT digits, full_digit, log10 FROM prime_blob WHERE id=$this->blobId");
            $this->digits = $result[0];
            $this->decimalExpansion = str_replace(" ", "", $result[1]);
            $this->log10 = $result[2];
        } elseif ($this->id) {
            $result = $this->db->query("SELECT digits, log10, blob_id FROM prime WHERE id=$this->id");
            $this->digits = $result[0];
            $this->log10 = $result[1];
            if (!empty($result[2])) {
                $this->blobId = $result[2];
            }
        } else {
            $this->decimalExpansion = getDecimalExpansion($this->desc, $this->log10);
            $this->log10 /= log(10);
            $this->digits = strlen($this->decimalExpansion);
        }
    }

    /**
     * @return string Full expanded number
     */
    public function getExpansion(): string
    {
        if ($this->decimalExpansion) {
            return $this->decimalExpansion;
        }
        if ($this->blobId) {
            $result = $this->db->query("SELECT digits, full_digit, log10 FROM prime_blob WHERE id=$this->blobId");
            $this->digits = $result[0];
            $this->decimalExpansion = str_replace(" ", "", $result[1]);
            $this->log10 = $result[2];
        } elseif ($this->desc) {
            $this->decimalExpansion = getDecimalExpansion($this->desc);
            $this->digits = strlen($this->decimalExpansion);
        } else {
            $result = $this->db->query("SELECT prime.digits, prime.blob_id, prime.description, prime_blob.full_digit, prime.log10  
                FROM prime LEFT JOIN prime_blob ON prime.id=prime_blob.prime_id WHERE id=$this->id");
            $this->digits = $result[0];
            $this->desc = $result[2];
            $this->log10 = $result[4];
            if (!empty($result[1])) {
                $this->blobId = $result[1];
                $this->decimalExpansion = str_replace(" ", "", $result[3]);
            } else {
                $this->decimalExpansion = getDecimalExpansion($this->desc);
            }
        }
        return $this->decimalExpansion;
    }

    public function fromDB(PDO $db, string $id, ?string $desc, ?int $digits, ?string $blobId): Number
    {
        $this->db = $db;
        $this->id = $id;
        $this->desc = $desc;
        $this->digits = $digits;
        $this->blobId = $blobId;
        return $this;
    }


    public function fromDescription(string $desc): Number
    {
        $this->desc = $desc;
        return $this;
    }

    public function fromBlob(PDO $db, string $blobId): Number
    {
        $this->db = $db;
        $this->blobId = $blobId;
        return $this;
    }
}
