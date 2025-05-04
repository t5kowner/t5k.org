#include <primesieve.hpp>
#include <stdint.h>
#include <iostream>
#include <cstdlib>
int main(int, char** argv)
{
  int64_t n = -1;
  uint64_t start = std::atoll(argv[1]);
  uint64_t nth_prime = primesieve::nth_prime(n,start);
  std::cout << n << "th prime = " << nth_prime << std::endl;
  nth_prime = primesieve::nth_prime(-n,start);
  std::cout << -n << "th prime = " << nth_prime << std::endl;
  return 0;
}

// primesieve::nth_prime(int64_t n, uint64_t start);
