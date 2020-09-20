all: kp

kp:
	clang++ -g -o kp -I/usr/local/include -L/usr/local/lib -lfcgi++ -lfcgi -lsodium -lqrencode -lpng kp.cc

clean:
	rm -f kp
	rm -f *.core

