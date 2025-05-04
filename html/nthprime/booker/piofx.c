#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <math.h>
#include <netinet/in.h>

int piscan(int,int);

int myscan(char *s,char *fmt,double *result) {
	char buf[80],*p=s,*q=buf;

	for (p=s,q=buf;*p && q<buf+79;p++)
		if (!strncasecmp(p,"%2C",3))
			p += 2;
		else if (*p != ',')
			*q++ = *p;
	*q = '\0';

	return sscanf(buf,fmt,result);
}

void myprint(double x) {
	if (x < 1000.0)
		printf("%.0f",x);
	else {
		myprint(floor(x/1000.0));
		printf(",%03.0f",x-1000.0*floor(x/1000.0));
	}
}

int main(int argc,char *argv[]) {
	int i,x,r;
	short t;
	double n,s,d;
	char *query;
	FILE *fp;

	printf("Content-type: text/html\n\n");
	printf("<html>\n");
	printf("<head><title>Nth Prime Results</title></head>\n");
	printf("<body>\n");
	if ( !(query = getenv("QUERY_STRING")) ||
	     myscan(query,"x=%lf",&n) != 1 ||
			 n < 1.0 || n > 3.0e13) {
		printf("Invalid Entry.  Your number must be between"
			" 1 and 3*10^13, inclusive.\n");
	} else {
		if (n < 2.0)
			printf("There are 0 primes less than or equal to 1.\n");
		else if (n < 3.0)
			printf("There is 1 prime less than or equal to 2.\n");
	  else {
			fp = fopen("data","r");
			r = (int)(n/(30030*640));
			s = 0.0;
			for (i=0;i<=r;s+=(double)x,i+=1250)
				fread(&x,1,sizeof(x),fp), x = ntohl(x);
			s -= (double)x, i -= 1250;
	  	if (!i) {
	  		fseek(fp,4*1250,SEEK_SET);
	  		for (;i<3 && i<=r;s+=(double)x,i++)
	  			fread(&x,1,sizeof(x),fp), x = ntohl(x);
	  	} else {
	  		fseek(fp,r/1250*2504+4*1250+2,SEEK_SET);
	  		fread(&x,1,sizeof(x),fp), x = ntohl(x);
	  	}
	  	for (;i<=r;s+=(double)x,i++) {
	  		fread(&t,1,sizeof(t),fp), t = ntohs(t);
	  		x += (int)t;
	  	}
	  	s -= (double)x, i--;
			x = piscan(i,(int)(n-(double)i*(640*30030)));
			s += (double)x;
			printf("There are ");
			myprint(s);
			printf(" primes less than or equal to ");
			myprint(n);
			printf(".\n");
		}
	}
	printf("<hr><a href="
	"\"http://www.math.Princeton.EDU/~arbooker/nthprime.html\">"
	"Return to the Nth Prime Page</a></body></html>\n");
	return 0;
}
