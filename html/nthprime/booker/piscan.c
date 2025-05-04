#include <stdlib.h>
#include <math.h>

#define BASE    379904
#define SCRATCH (30030*16)
#define SEGSIZE (30030*640)

static int base[2*BASE];
#define indx (base+BASE)
static char scratch[SCRATCH];
#define small_sieve ((char *)base+1024)

int piscan(int segment,int top) {
	register int i,j,k,l,m,r=0;
	double start = (double)segment*SEGSIZE;

	if ((top = (top-1)>>1) < 0)
		return 0;
	l = ((int)sqrt(start+SEGSIZE) - 1) | 1;
	m = l>>1;
	for (i=3;i*i<=l;i+=2) {
		if (small_sieve[i>>1]) continue;
		for (j=i*3>>1;j<=m;j+=i)
			small_sieve[j] = 1;
	}
	for (j=3>>1,k=0;j<=m;j++)
		if (!small_sieve[j]) base[k++] = 2*j+1;
	if (!segment)
		for (i=0;i<k;i++)
			indx[i] = 3*base[i]>>1;
	else
		for (i=0;i<k;i++) {
			indx[i] = (int)(ceil(start/base[i])*base[i]-start);
			if (!(indx[i] & 1))
				indx[i] += base[i];
			indx[i] >>= 1;
		}

	for (l=SCRATCH;l<=SEGSIZE/2;l+=SCRATCH) {
		m = 0x01010101;
		for (i=0;i<SCRATCH;i+=32) {
			*(int *)&scratch[i   ] = m; *(int *)&scratch[i+4 ] = m;
			*(int *)&scratch[i+8 ] = m; *(int *)&scratch[i+12] = m;
			*(int *)&scratch[i+16] = m; *(int *)&scratch[i+20] = m;
			*(int *)&scratch[i+24] = m; *(int *)&scratch[i+28] = m;
		}

		for (i=k-1;i>=0;i--)
			if ( (j = indx[i]-l) < 0) {
				m = base[i];
				do {
					scratch[j+SCRATCH]=0; if ((j += m) >= 0) break;
					scratch[j+SCRATCH]=0; if ((j += m) >= 0) break;
					scratch[j+SCRATCH]=0; if ((j += m) >= 0) break;
					scratch[j+SCRATCH]=0; if ((j += m) >= 0) break;
					scratch[j+SCRATCH]=0; if ((j += m) >= 0) break;
					scratch[j+SCRATCH]=0; if ((j += m) >= 0) break;
					scratch[j+SCRATCH]=0; if ((j += m) >= 0) break;
					scratch[j+SCRATCH]=0;
				} while ( (j += m) < 0);
				indx[i] = j+l;
			}
	
		for (j=-SCRATCH;j<0;j++) {
			r += (int)scratch[j+SCRATCH];
			if (--top < 0)
				return r;
		}
	}
}
