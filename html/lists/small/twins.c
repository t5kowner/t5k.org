#include "stdio.h"
#include "math.h"

int number;
int max;
int cols=8; /* How many primes to print each row */

main()
{
        printf("How many twins do you want? ");
        scanf("%d",&max);
        printf("\n");
        if (max<3) max=cols;
        max = ((max-1)/cols + 1)*cols;
        printf("The First %d Twin Primes (First of the Pair only)\n",max);
        number = 3;
        do
        {
                if (isprime(number)) {
                   max-=1;
                   if (max % cols) 
                     { printf("%9d",number); }
                   else
                     { printf("%9d \n",number); }
                }
                number+=2;
        } while (max>0) ;
}

isprime(int thenumber)
{
        int isitprime=1,loop;
        for(loop=3 ; (isitprime) && (loop*loop<(thenumber+1)) ; loop+=2)
                isitprime = (thenumber % loop);
	thenumber+=2;
        for(loop=3 ; (isitprime) && (loop*loop<(thenumber+1)) ; loop+=2)
                isitprime = (thenumber % loop);
        return(isitprime);
}
