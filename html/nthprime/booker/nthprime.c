#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <math.h>
#include <netinet/in.h>

double scan(int,int);
char *suffix[]={"th","st","nd","rd","th","th","th","th","th","th"};

int myscan(char *s,char *fmt,double *result) {
	char buf[80],*p=s,*q=buf;
	long long x;

	if (!strcmp(s,"n=random")) {
		srandom(time(NULL));
		x = floor((double)random()*1000000.0/2147483648.0);
		*result = floor((double)random()*1000000.0/2147483648.0)+x*1000000.0;
		return 1;
	}

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
	int i,x;
	short t;
	double n,s;
	char *query;
	FILE *fp;

	printf("Content-type: text/html\n\n");
	printf("<html>\n");
	printf("<head><title>Nth Prime Results</title></head>\n");
	printf("<body>\n");
	if ( !(query = getenv("QUERY_STRING")) ||
	     myscan(query,"n=%lf",&n) != 1 ||
			 n < 1.0 || n > 1.0e12) {
		printf("Invalid Entry.  Your number must be between"
			" 1 and 10^12, inclusive.\n");
	} else {
		i = (int)(n-100.0*floor(n/100.0));
		printf("The ");
		myprint(n);
		printf("%s prime is ",i/10 == 1 ? "th" : suffix[i % 10]);
		if (n < 2.0)
			printf("2.\n");
	  else {
			fp = fopen("data","r");
	  	for (i=0,s=0;s<n;s+=(double)x,i++)
	  		fread(&x,1,sizeof(x),fp), x = ntohl(x);
	  	s -= (double)x, i--;
	  	if (!i) {
	  		fseek(fp,4*1250,SEEK_SET);
	  		for (;i<3 && s<n;s+=(double)x,i++)
	  			fread(&x,1,sizeof(x),fp), x = ntohl(x);
	  	} else {
	  		fseek(fp,i*2504+4*1250+2,SEEK_SET);
	  		fread(&x,1,sizeof(x),fp), x = ntohl(x);
	  		i *= 1250;
	  	}
	  	for (;s<n;s+=(double)x,i++) {
	  		fread(&t,1,sizeof(t),fp), t = ntohs(t);
	  		x += (int)t;
	  	}
	  	s -= (double)x, i--;
			myprint(scan(i,(int)(n-s)));
			printf(".\n");
		}
	}
	printf("<hr><a href="
	"\"http://www.math.Princeton.EDU/~arbooker/nthprime.html\">"
	"Return to the Nth Prime Page</a></body></html>\n");
	return 0;
}
