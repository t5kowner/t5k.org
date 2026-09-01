<?php

namespace Top20\Linters;

use PDO;
use Top20\SubmittedPrime;

class AllLinters extends BaseLinter
{
    private array $linters = [];

    public function __construct()
    {
        $this->linters = [
            new ArithmeticProgression(),
            new Compositorial(),
            new CullenWoodall(),
            new CunninghamChain(),
            new Cyclotomy(),
            new DividesSomething(),
            new ECPP(),
            new EulerIrregular(),
            new Factorial(),
            new Fibonacci(),
            new FibonacciCofactor(),
            new FibonacciPrimPart(),
            new GaussianMersenne(),
            new GeneralizedFermat(),
            new GeneralizedLucas(),
            new GeneralizedLucasPrimPart(),
            new GeneralizedRepunit(),
            new GeneralizedUnique(),
            new Irregular(),
            new KTuplet(),
            new Lehmer(),
            new LehmerPrimPart(),
            new Lucas(),
            new LucasAuriPrim(),
            new LucasCofactor(),
            new LucasPrimitive(),
            new Mersenne(),
            new MersenneCofactor(),
            new Mills(),
            new Multifactorial(),
            new NearRepdigit(),
            new Palindrome(),
            new Partitions(),
            new Primorial(),
            new Twin(),
            new Unique(),
            new Wagstaff(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function lint(PDO $db, SubmittedPrime $prime, array $others = []): array
    {
        $ret = [];
        foreach ($this->linters as $linter) {
                $ret = array_merge($ret, $linter->lint($db, $prime, $others));
        }
        return $ret;
    }
}
