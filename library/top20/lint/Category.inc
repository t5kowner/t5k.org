<?php

namespace Top20;

const ARCHIVABLE_COUNT = 20;
const ARCHIVABLE_CLASS_COUNT = 5;
const ARCHIVABLE_TOLERATED_COUNT = 0;

enum Category: int
{
    case Twin = 1;
    case SophieGermain = 2;
    case Mersenne = 4;
    case Primorial = 5;
    case Cullen = 6;
    case Woodall = 7;
    case FermatDivisor = 8;
    case GF6 = 9;
    case GF10 = 10;
    case GF12 = 11;
    case GeneralizedFermat = 12;
    case APConsecutive = 13;
    case ArithmeticProgression = 14;
    case NearRepdigit = 15;
    case GeneralizedRepunit = 16;
    case GFDivisor = 18;
    case CunninghamChain = 19;
    case CunninghamChain2 = 20;
    case LucasAuriPrim = 21;
    case GeneralizedLucas = 23;
    case LucasPrimitive = 24;
    case EulerIrregular = 25;
    case Irregular = 26;
    case ECPP = 27;
    case GF3 = 28;
    case GF5 = 29;
    case Factorial = 30;
    case SophieGermain2 = 31; //not linted, will be removed once the PHP implementation handles all linting
    case DividesPhi = 37;
    case Fibonacci = 39;
    case FibonacciPrimPart = 40;
    case GaussianMersenne = 41;
    case GeneralizedCullen = 42;
    case GeneralizedLucasPrim = 43;
    case GeneralizedUnique = 44;
    case GeneralizedWoodall = 45;
    case Lehmer = 47;
    case Lucas = 48;
    case MersenneCofactor = 49;
    case Palindrome = 53;
    case Partitions = 54;
    case Quadruplet = 55;
    case Quintuplet = 56;
    case Repunit = 57;
    case Triplet = 61;
    case Unique = 62;
    case FibonacciCofactor = 64;
    case LucasCofactor = 65;
    case Wagstaff = 67;
    case LehmerPrimitive = 68;
    case Sextuplet = 70;
    case Septuplet = 71;

    case Largest = 3; //weird case; shouldn't be on any tags
    case LeastnPlus = 17; //unused - doubtful it will ever be used
    case NSW = 22;//5 primes from 2001 and tolerated. Not implementing in PHP
    case SpecialCases = 32; //3 weird "free-text" cases. Not implementing in PHP
    case ARPT_CL = 33; //nothing more recent than 2001. Not implementing in PHP
    case Binomial = 34; //nothing more recent than 2005. Not implementing in PHP
    case Compositorial = 35;
    case Cyclotomy = 36;
    case Elliptic = 38;//nothing in the 2000s+. Not implementing in PHP
    case Illegal = 46;//should frankly be completely wiped. Not implementing in PHP
    case MillsPrime = 50;
    case Multifactorial = 51;
    case nCarmichaelFactor = 52;//nothing since early 2000s. Not implementing in PHP
    case Strobogrammatic = 58;//nothing in the 2000s+. Not implementing in PHP
    case Tetradic = 59;//nothing since 2010. Not implementing in PHP
    case Triadic = 60;//nothing in decades. Not implementing in PHP
    case GapPrimes = 63;//nothing in decades. Not implementing in PHP
    case Proth = 66;//never fully implemented. Can do later if desired
    case ktuplet = 69; //not used on the Perl side
}
