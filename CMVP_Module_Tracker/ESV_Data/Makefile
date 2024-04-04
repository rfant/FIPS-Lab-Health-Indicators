CXX = g++

#CXX =clang
#CXX=gcc

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
#LIB = -lbz2 -lpthread  
#ubuntu
LIB = -lssl -lcrypto 

INC= 
#/usr/local/include

######
# Main operations
######

all:    ESV_ALL 

#clean:

#	rm -f active_to_sql.o

ESV_ALL: esv_to_sql.o Mark_esv_Table_Dups.o delete_data_esv_table.o setesvErrorFlag.o clearesvErrorFlag.o sql_with_pw_enc.o
esv_to_sql.o: esv_to_sql.cpp
	$(CXX) $(CXXFLAGS) $(INC) esv_to_sql.cpp -lpq -v -o esv_to_sql $(LIB)
	
#mark each row in the ESV Table where both Module_Name and Vendor_Name are repeated elsewhere. This is appoximately 1/3 of the total number of rows.
#do this so that the algorithm doesn't get confused about which module the CMVP_MIP_Table is referring to.
Mark_esv_Table_Dups.o: Mark_esv_Table_Dups.cpp
	$(CXX) $(CXXFLAGS) $(INC) Mark_esv_Table_Dups.cpp -lpq -v -o Mark_esv_Table_Dups $(LIB)

setesvErrorFlag.o: setesvErrorFlag.cpp
	$(CXX) $(CXXFLAGS) $(INC) setesvErrorFlag.cpp -lpq -v -o setesvErrorFlag $(LIB)

clearesvErrorFlag.o: clearesvErrorFlag.cpp
	$(CXX) $(CXXFLAGS) $(INC) clearesvErrorFlag.cpp -lpq -v -o clearesvErrorFlag $(LIB)


delete_data_esv_table.o: delete_data_esv_table.cpp
	$(CXX) $(CXXFLAGS) $(INC) delete_data_esv_table.cpp -lpq -v -o delete_data_esv_table $(LIB)

sql_with_pw_enc.o: sql_with_pw_enc.cpp
	$(CXX) $(CXXFLAGS) $(INC) sql_with_pw_enc.cpp -lpq -v -o sql_with_pw_enc $(LIB)
	

