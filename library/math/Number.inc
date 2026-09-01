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
     * @return int number of digits in the number
     */
    public function getDigits(): int
    {
        if ($this->digits) {
            return $this->digits;
        }
        if ($this->blobId) {
            $result = $this->db->query("SELECT digits, full_digit FROM prime_blob WHERE id=$this->blobId");
            $this->digits = $result[0];
            $this->decimalExpansion = str_replace(" ", "", $result[1]);
        } elseif ($this->id) {
            $result = $this->db->query("SELECT digits, blob_id FROM prime WHERE id=$this->id");
            $this->digits = $result[0];
            if (!empty($result[1])) {
                $this->blobId = $result[1];
            }
        } else {
            $this->decimalExpansion = getDecimalExpansion($this->desc);
            $this->digits = strlen($this->decimalExpansion);
        }
        return $this->digits;
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
            $result = $this->db->query("SELECT digits, full_digit FROM prime_blob WHERE id=$this->blobId");
            $this->digits = $result[0];
            $this->decimalExpansion = str_replace(" ", "", $result[1]);
        } elseif ($this->desc) {
            $this->decimalExpansion = getDecimalExpansion($this->desc);
            $this->digits = strlen($this->decimalExpansion);
        } else {
            $result = $this->db->query("SELECT prime.digits, prime.blob_id, prime.description, prime_blob.full_digit 
                FROM prime LEFT JOIN prime_blob ON prime.id=prime_blob.prime_id WHERE id=$this->id");
            $this->digits = $result[0];
            $this->desc = $result[2];
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
