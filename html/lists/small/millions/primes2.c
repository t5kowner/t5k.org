#include "stdio.h"
#include "math.h"

int number;
int max;
int cols=1; /* How many primes to print each row */

main()
{
       printf("How many primes do you want? ");
       scanf("%d",&max);
       printf("\n");
        if (max<3) max=cols;
        max = ((max-1)/cols + 1)*cols;
        printf("                    The First %d Primes (from t5k.org)\n\n",max);
        printf("               The Thirty-first 1,000,000 Primes (from t5k.org)\n\n");
        printf("%10d",2);
        max-=1;
        number = 1;  
        do
        {
                if (isprime(number)) {
                   max-=1;
                   if (max % cols) 
                     { printf("%10d",number); }
                   else
                     { printf("%10d \n",number); }
                }
                number+=2;
        } while (max>0) ;
}

isprime(int thenumber)
{
        int isitprime=1,loop;
        for(loop=3 ; (isitprime) && (loop*loop<(thenumber+1)) ; loop+=2)
                isitprime = (thenumber % loop);
        return(isitprime);
}
