#include <stdio.h>
#include <//usr/include/postgresql/libpq-fe.h>  //ubuntu

#include <iostream>
#include <fstream>
#include <string>
#include "utils.h"
#include <unistd.h>
#include <sstream>
#include <stdarg.h>  //ubuntu
#include <time.h>
//#include "../dev_or_prod_rgf2.h"

#include <openssl/aes.h>

//#include "Active_Indicator_sql.h"

//global variables
PGconn *conn;
char * MasterStr;





//============================================================
int main (int argc, char* argv[]) {
#include "../dev_or_prod_rgf2.h"

	const char *Table_Name="CMVP_Active_Table";
	char *file_path;
	char *file_num;

	long int file_pos=0;
	
	//char sql1 [SQL_MAX];
	//PGresult *sql_result;
	data_t data;
	int i;
	int myX;

	


//AES_KEY aesKey_;


//use this AES encrypt to figure out what the hardcoded encryptedPW should be after defining the plainTextPW. Once you know it, hardcode the encrypted p/w in the dev_or_prod_rgf2.h file
// AES_set_encrypt_key(userKey_, 128, &aesKey_);
// AES_encrypt(encryptedPW, plainTextPW, &userKey_);
// fprintf(stdout,"\nPlainText: %x %x %x %x %x %x %x %x %x %x %x %x %x %x %x %x \n", encryptedPW[0],encryptedPW[1],encryptedPW[2],encryptedPW[3],encryptedPW[4],encryptedPW[5],encryptedPW[6],encryptedPW[7],encryptedPW[8],encryptedPW[9],encryptedPW[10],encryptedPW[11],encryptedPW[12],encryptedPW[13],encryptedPW[14],encryptedPW[15]);     
 

//AES_set_decrypt_key(userKey_, 128, &aesKey_);
AES_decrypt(IntelencryptedPW, decryptedPW,&aesKey_);

//printf("\nPlainText: %s\n", decryptedPW);     
return 0;


} //main