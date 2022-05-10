CXX = g++

#CXX =clang
#original
#CXXFLAGS = -std=c++11 -fopenmp -O2 -msse2 -ffloat-store -march=native

#modified for mac OS with no debug flag
#CXXFLAGS = -std=c++11 -Xpreprocessor -fopenmp  -O2 -msse2 -ffloat-store -march=native -lomp

#mac OS with debug flag
#CXXFLAGS = -g -std=c++11 -Xpreprocessor -fopenmp  -O2 -msse2 -ffloat-store -march=native -lomp
#CXXFLAGS = -g -std=c++11 -Xpreprocessor -fopenmp  -O2 -msse2  -march=native -lomp -w
#ubuntu
CXXFLAGS = -g -std=c++11 -Xpreprocessor -fopenmp  -O2 -msse2  -march=native  -w
#CXX = clang++-8
#CXXFLAGS = -Wno-padded -Wno-disabled-macro-expansion -Wno-gnu-statement-expression -Wno-bad-function-cast -fopenmp -O1 -fsanitize=address -fsanitize=undefined -fdenormal-fp-math=ieee -msse2 -march=native
#static analysis in clang using
#scan-build-8 --use-c++=/usr/bin/clang++-8 make
#LIB = -lbz2 -lpthread -ldivsufsort
LIB = 
INC= 
#/usr/local/include

######
# Main operations
######

all:    MIP_ALL 

#clean:

#	rm -f mip_to_sql.o

MIP_ALL: 	  current_mip_to_sql.o 
			

current_mip_to_sql.o: current_mip_to_sql.cpp
	$(CXX) $(CXXFLAGS) $(INC) current_mip_to_sql.cpp -lpq -v -o current_mip_to_sql $(LIB)


