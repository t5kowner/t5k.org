CC = gcc
CFLAGS = -pipe -O2 -fomit-frame-pointer -ffast-math

all: nthprime piofx

nthprime: nthprime.o scan.o
	$(CC) -s -o $@ nthprime.o scan.o -lm

piofx: piofx.o piscan.o
	$(CC) -s -o $@ piofx.o piscan.o -lm
