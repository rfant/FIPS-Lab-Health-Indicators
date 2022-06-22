#include <stdio.h>
//#include </usr/local/Cellar/postgresql/13.2_1/include/libpq-fe.h>
#include <//usr/include/postgresql/libpq-fe.h>  //ubuntu
#include <iostream>
#include <fstream>
#include <string>
#include "utils.h"
#include <unistd.h>
#include <stdarg.h>  //ubuntu
#include "../dev_or_prod.h"

//global variables
PGconn *conn;
char * MasterStr;


#define VALUE_SIZE 512   //max size of value between tags
#define SQL_MAX 4096    //max size of a sql query string
//=========================================================
#define DEBUG  (1)   //set to (0) to turn off printf messages.

#define CLR_SQL1_STR for(i=0;i<SQL_MAX;i++) sql1[i]=0; 
//zero out sql1 command string. Not sure why this is necessary. But will fail if I don't

//bunch of enumerated values
#define TID 1
#define MODULE_NAME 2
#define VENDOR_NAME 3
#define IUT 4
#define REVIEW_PENDING 5
#define IN_REVIEW 6
#define COORDINATION 7
#define FINALIZATION 8
#define LAB_NAME 9
#define STATUS 10


#include <openssl/aes.h>

AES_KEY aesKey_;

unsigned char decryptedPW[16];


//PROTOTYPES


void strfcat(char *, char *, ...);

//#include "MIP_Indicator_sql.h"


//===================================================================================================
void strfcat(char *src, char *fmt, ...){
//this is sprintf and strcat combined.
//strfcat(dst, "Where are %d %s %c\n", 5,"green wizards",'?');
//strfcat(dst, "%d:%d:%c\n", 4,13,'s');

    //char buf[2048];


    char buf[SQL_MAX];
    va_list args;

    va_start(args, fmt);
    
   //vsprintf(buf, fmt, args);
    vsnprintf(buf,sizeof buf, fmt,args);


    va_end(args);

    strcat(src, buf);
}//strfcat

//============================================================
//int main (int argc, char* argv[]) {
int main (){
	
	
	
	int i;
	PGresult *sql_result;
	char sql1 [SQL_MAX];
char connbuff[200];

	
	
	switch (PROD) {
		case 2:  			//local VM machine
			AES_set_decrypt_key(userKey_, 128, &aesKey_);
    		AES_decrypt(VMencryptedPW, decryptedPW,&aesKey_);
    		
    		snprintf(connbuff,sizeof connbuff,"host=localhost user=postgres password=%s dbname=postgres", decryptedPW);
       		conn = PQconnectdb(connbuff);
   	   		
   	   		break;
	
		case 1: 			//intel intranet production
  		
	  		
			AES_set_decrypt_key(userKey_, 128, &aesKey_);
    		AES_decrypt(IntelencryptedPW, decryptedPW,&aesKey_);

			
    		snprintf(connbuff,sizeof connbuff,"host=postgres5320-lb-fm-in.dbaas.intel.com user=lhi_prod2_so password=%s dbname=lhi_prod2 ", decryptedPW);
    
    		conn = PQconnectdb(connbuff);
   	   		break;
	
		case 0: //Intel intranet pre-production
			
		 	AES_set_decrypt_key(userKey_, 128, &aesKey_);
    		AES_decrypt(IntelencryptedPW, decryptedPW,&aesKey_);
    		
    		snprintf(connbuff,sizeof connbuff,"host=postgres5596-lb-fm-in.dbaas.intel.com user=lhi_pre_prod_so password=%s dbname=lhi_pre_prod ", decryptedPW);
    
   	   		conn = PQconnectdb(connbuff);
   	   		break;
		default: 
			printf("ERROR  112: Unknown PROD=%d\n",PROD); break;
	}



    if (PQstatus(conn) == CONNECTION_OK) {


    	//This will delete all the entries in the Active Table. This will really only be used to faciliate debugging. Otherwise,
    	//I'll have to  go into the PGAdmin4 tool and manually delete the rows each time when I want a clean, fresh run.
    
		CLR_SQL1_STR

	

		strfcat(sql1," delete from \"CMVP_Active_Table\"; ");

	
		sql_result = PQexec(conn, sql1); 


		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
			printf("\nError 93: Delete Data in the Active Table failed: sql1=%s\n",sql1);
		PQclear(sql_result);

	} //connection ok
	else
		printf("PostgreSQL connection error\n");
	
	PQfinish(conn);
	printf("\n Deleted all the row data in the CMVP_Active_Table.\n");

	return(0);


} //main