PROGS = digest api api_test

all: $(PROGS)

CC = g++ -g
//CC = g++

digest: digest.h digest.cpp
	$(CC) digest.cpp -o digest

api: digest.h api.h api.cpp
	$(CC) api.cpp -o api

api_test: digest.h api.h api_test.cpp
	$(CC) api_test.cpp -o api_test

clean:
	rm $(PROGS)
