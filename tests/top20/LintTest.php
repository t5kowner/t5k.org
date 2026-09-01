<?php

namespace tests;

require_once dirname(__FILE__) . "/../../library/top20/lint/lint.inc";

use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Top20\Category;
use Top20\SubmittedPrime;

use function Top20\lintAll;

class LintTest extends TestCase
{
    public static function provider(): array
    {
        return [
            ["3*2^1234-1 twin (p)", ["3*2^1234+1"], Category::Twin, false, 1],
            ["3*2^1234+1 twin (p+2)", ["3*2^1234-1"], Category::Twin, false, 1],
            ["3*2^1234-1 twin (p)", [], Category::Twin, true, 0],
            ["3*2^1234-1 twin (p)", ["3*2^1234+5"], Category::Twin, true, 0],

            ["3*2^1234-1", ["3*2^1235-1"], Category::SophieGermain, false, 1],
            ["3*2^1235-1", ["3*2^1234-1"], Category::SophieGermain, false, 1],
            ["3*2^1234-1", [], Category::SophieGermain, true, 0],
            ["3*2^1234-1", ["3*2^1234+5"], Category::SophieGermain, true, 0],

            ["2^1234-1", [], Category::Mersenne, false, 1],
            ["2^1234-3", [], Category::Mersenne, true, 0],

            ["1234#+1", [], Category::Primorial, false, 1],
            ["1234#-1", [], Category::Primorial, false, 1],
            ["1234#+3", [], Category::Primorial, true, 0],
            ["1234#-3", [], Category::Primorial, true, 0],
            ["2*1234#-1", [], Category::Primorial, true, 0],

            ["12345*2^12345+1", [], Category::Cullen, false, 1],
            ["12345*3^12345+1", [], Category::GeneralizedCullen, false, 1],
            ["669*2^128454+1", [], Category::GeneralizedCullen, false, 1],
            ["20*29^20+1", [], Category::GeneralizedCullen, true, 0],  //doesn't meet n+2>b rule

            ["12345*2^12345-1", [], Category::Woodall, false, 1],
            ["12345*3^12345-1", [], Category::GeneralizedWoodall, false, 1],
            ["669*2^128454-1", [], Category::GeneralizedWoodall, false, 1],
            ["20*29^20-1", [], Category::GeneralizedWoodall, true, 0],

            ["3*2^1234+1 Divides Fermat F(1234)", [], Category::FermatDivisor, false, 1],
            ["3*2^1234+1 Divides Fermat F(1234), GF(1234,12)", [], Category::FermatDivisor, false, 2],
            ["3*2^1234+1 Divides Fermat F(1234), divides GF(1234,12)", [], Category::FermatDivisor, false, 2],
            ["3*2^1234+1 Divides Fermat F(1234), GF(1234,12)", [], Category::GFDivisor, false, 2],
            ["3*2^1234+1 Divides GF(1234,6)", [], Category::GFDivisor, false, 1],
            ["3*2^1234+1 Divides GF(1234,7)", [], Category::GFDivisor, true, 0],  //don't track base 7
            ["3*2^1234+1 Divides Phi(695^94625/5^4,2)", [], Category::DividesPhi, false, 1],
            ["3*2^1234+1 Divides Phi(59^608685,2)", [], Category::DividesPhi, false, 1],

            //see https://github.com/t5kowner/t5k.org/issues/21 for some of these
            ["1234^(2^5)+1", [], Category::GeneralizedFermat, false, 1],
            ["1234!^2+1", [], Category::GeneralizedFermat, false, 1],
            ["968*288^235591+1", [], Category::GeneralizedFermat, false, 1],

            ["3*2^1234+1 Consecutive primes arithmetic progression (1,d=4)",
                ["3*2^1234+5"], Category::APConsecutive, false, 1],
            ["3*2^1234+1 Consecutive primes arithmetic progression (1,d=4)", [], Category::APConsecutive, true, 0],
            ["3*2^1234+1 Consecutive primes arithmetic progression (1,d=6)",
                ["3*2^1234+5"], Category::APConsecutive, true, 0],
            ["3*2^1234+5 Consecutive primes arithmetic progression (2,d=4)",
                ["3*2^1234+1"], Category::APConsecutive, false, 1],

            ["3*2^1234+1 Arithmetic progression (1,d=4)", ["3*2^1234+5"], Category::ArithmeticProgression, true, 0],
            ["3*2^1234+1 Arithmetic progression (1,d=4)",
                ["3*2^1234+5", "3*2^1234+9"], Category::ArithmeticProgression, false, 1],
            ["3*2^1234+1 Arithmetic progression (1,d=6)",
                ["3*2^1234+5", "3*2^1234+9"], Category::ArithmeticProgression, true, 0],
            ["3*2^1234+5 Arithmetic progression (2,d=4)",
                ["3*2^1234+1", "3*2^1234+9"], Category::ArithmeticProgression, false, 1],
            ["3*2^1234+9 Arithmetic progression (3,d=4)",
                ["3*2^1234+1", "3*2^1234+5"], Category::ArithmeticProgression, false, 1],

            ["9*10^1234-1", [], Category::NearRepdigit, false, 1],
            ["92*10^1234-1", [], Category::NearRepdigit, false, 1],
            ["(10^1235)^2-2", [], Category::NearRepdigit, false, 1],

            ["(1234^4-1)/1233", [], Category::GeneralizedRepunit, true, 0],  //we require n>=5
            ["(1234^1234-1)/1233", [], Category::GeneralizedRepunit, false, 1],

            ["5*2^1234-1 Cunningham chain (2p+1)", ["5*2^1233-1"], Category::CunninghamChain, true, 1], //Sophie Germain
            ["5*2^1234-1 Cunningham chain (p)", ["5*2^1236-1", "5*2^1235-1"], Category::CunninghamChain, false, 1],
            ["5*2^1234-1 Cunningham chain (4p+3)", ["5*2^1233-1", "5*2^1232-1"], Category::CunninghamChain, false, 1],
            ["5*2^1234-1 Cunningham chain (2p+1)", ["5*2^1233-1", "5*2^1235-1"], Category::CunninghamChain, false, 1],

            ["5*2^1234+1 Cunningham chain 2nd kind (2p-1)", ["5*2^1233+1"], Category::CunninghamChain2, false, 1],
            ["5*2^1234+1 Cunningham chain 2nd kind (p)",
                ["5*2^1236+1", "5*2^1235+1"], Category::CunninghamChain2, false, 1],
            ["5*2^1234+1 Cunningham chain 2nd kind (4p-3)",
                ["5*2^1233+1", "5*2^1232+1"], Category::CunninghamChain2, false, 1],
            ["5*2^1234+1 Cunningham chain 2nd kind (2p-1)",
                ["5*2^1233+1", "5*2^1235+1"], Category::CunninghamChain2, false, 1],

            ["primA(1325)", [], Category::LucasAuriPrim, false, 1],
            ["primB(1325)", [], Category::LucasAuriPrim, false, 1],

            ["U(5574,-1,2591)", [], Category::GeneralizedLucas, false, 1],
            ["U(5574,-1,2)", [], Category::GeneralizedLucas, true, 0], //n too small

            ["primV(1325)", [], Category::LucasPrimitive, false, 1],

            ["primV(1325,-1,123)", [], Category::GeneralizedLucasPrim, false, 1],

            ["-E(510)", [], Category::EulerIrregular, false, 1],
            ["-E(886)/68689", [], Category::EulerIrregular, false, 1],
            ["2*3^4+1 Euler irregular", [], Category::EulerIrregular, false, 1],

            ["6*Bern(674)/337", [], Category::Irregular, false, 1],
            ["2*3^4+1 irregular", [], Category::Irregular, false, 1],

            ["2*3^4+1 ECPP", [], Category::ECPP, false, 1],

            ["123!+1", [], Category::Factorial, false, 1],
            ["123!-1", [], Category::Factorial, false, 1],

            ["U(123)", [], Category::Fibonacci, false, 1],

            ["primU(1325)", [], Category::FibonacciPrimPart, false, 1],

            ["2^123-2^62+1", [], Category::GaussianMersenne, false, 2],
            ["2^123+2^62+1", [], Category::GaussianMersenne, false, 2],

            ["primU(5,-1,7)", [], Category::GeneralizedLucasPrim, false, 1],

            ["5^4+5^2+1", [], Category::GeneralizedUnique, false, 1],
            ["Phi(5,5)", [], Category::GeneralizedUnique, false, 1],
            ["Phi(10,5)", [], Category::GeneralizedUnique, false, 1],

            ["2*3^4+1 Lehmer number", [], Category::Lehmer, false, 1],

            ["V(123)", [], Category::Lucas, false, 1],

            ["(2^23-1)/47", [], Category::MersenneCofactor, false, 1],
            ["2*3^123-1 Mersenne cofactor", [], Category::MersenneCofactor, false, 1],

            ["12345678987654321", [], Category::Palindrome, false, 1],

            ["p(132)", [], Category::Partitions, false, 1],

            ["3*2^1234+1 triplet (1)", ["3*2^1234+3", "3*2^1234+7"], Category::Triplet, false, 1],
            ["3*2^1234+1 3-tuplet (1)", ["3*2^1234+3", "3*2^1234+7"], Category::Triplet, false, 1],
            ["3*2^1234+1 triplet (2)", ["3*2^1234+3", "3*2^1234-3"], Category::Triplet, false, 1],
            ["3*2^1234+1 triplet (3)", ["3*2^1234-1", "3*2^1234-5"], Category::Triplet, false, 1],
            ["3*2^1234+1 triplet (1)", ["3*2^1234+3"], Category::Triplet, true, 0],
            ["3*2^1234+1 triplet (1)", ["3*2^1234+3", "3*2^1234+9"], Category::Triplet, true, 0], //spread too much

            ["3*2^1234+1 quadruplet (1)", ["3*2^1234+3", "3*2^1234+7", "3*2^1234+9"], Category::Quadruplet, false, 1],
            ["3*2^1234+1 quadruplet (2)", ["3*2^1234+5", "3*2^1234-1", "3*2^1234+7"], Category::Quadruplet, false, 1],
            ["3*2^1234+1 quadruplet (3)", ["3*2^1234-3", "3*2^1234-5", "3*2^1234+3"], Category::Quadruplet, false, 1],
            ["3*2^1234+1 quadruplet (4)", ["3*2^1234-5", "3*2^1234-7", "3*2^1234-1"], Category::Quadruplet, false, 1],
            ["3*2^1234+1 quadruplet (1)", ["3*2^1234+3"], Category::Quadruplet, true, 0],
            ["3*2^1234+1 quadruplet (1)", ["3*2^1234+3", "3*2^1234+7", "3*2^1234+11"], Category::Quadruplet, true, 0],

            ["R(123)", [], Category::Repunit, false, 2],

            ["R(123)", [], Category::Unique, false, 2],
            ["Phi(15,-10^125)", [], Category::Unique, false, 1],

            ["U(113)/677", [], Category::FibonacciCofactor, false, 1],
            ["2*3^123-1 Fibonacci cofactor", [], Category::FibonacciCofactor, false, 1],

            ["V(101)/809", [], Category::LucasCofactor, false, 1],
            ["2*3^123-1 Lucas cofactor", [], Category::LucasCofactor, false, 1],

            ["(2^199+1)/3", [], Category::Wagstaff, false, 2],

            ["2*3^4+1 Lehmer primitive part", [], Category::LehmerPrimitive, false, 1],

            ["123!/123#+1", [], Category::Compositorial, false, 1],
            ["123!/122#+1", [], Category::Compositorial, false, 1],
            ["123!/12#+1", [], Category::Compositorial, true, 0],

            ["10!2+1", [], Category::Multifactorial, false, 1],
            ["2+10!2+1", [], Category::Multifactorial, true, 0],
            ["(2^12+13)!(2^11)-1", [], Category::Multifactorial, false, 1],
        ];
    }
    #[DataProvider('provider')]
    public function testAll(string $prime, array $others, Category $expected, bool $invert, int $total)
    {
        $pdo = $this->createMock(PDO::class);
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->any())->method("fetch")->willReturn(false);
        $pdo->expects($this->any())->method("query")->willReturn($stmt);
        $primeOthers = [];
        foreach ($others as $other) {
            $primeOthers[] = new SubmittedPrime($pdo, $other, "");
        }
        preg_match('/(\S+)\s*(.*)/', $prime, $matches);
        $desc = $matches[1];
        $comment = $matches[2];
        $results = lintAll($pdo, new SubmittedPrime($pdo, $desc, $comment), $primeOthers);
        $this->assertCount($total, $results);
        foreach ($results as $result) {
            if ($result->category == $expected) {
                $this->assertFalse($invert);
                return;
            }
        }
        $this->assertTrue($invert);
    }
}
