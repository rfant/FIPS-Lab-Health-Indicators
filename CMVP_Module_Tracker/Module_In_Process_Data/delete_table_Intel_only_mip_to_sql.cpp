#include <stdio.h>
#include </usr/local/Cellar/postgresql/13.2_1/include/libpq-fe.h>
#include <iostream>
#include <fstream>
#include <string>
#include "utils.h"
#include <unistd.h>

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
/*#define TID 1
#define MODULE_NAME 2
#define VENDOR_NAME 3
#define IUT 4
#define REVIEW_PENDING 5
#define IN_REVIEW 6
#define COORDINATION 7
#define FINALIZATION 8
#define LAB_NAME 9
#define STATUS 10
*/
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
    vsprintf(buf, fmt, args);
    va_end(args);

    strcat(src, buf);
}//strfcat

//============================================================
//int main (int argc, char* argv[]) {
int main (){
	
	const char *Table_Name="CMVP_atsec_Only_MIP_Table";
	int k;
	
	int i;
	PGresult *sql_result;
	char sql1 [SQL_MAX];


	conn = PQconnectdb("host=postgres.aus.atsec user=richard password==uwXg9Jo'5Ua dbname=fantDatabase ");
    if (PQstatus(conn) == CONNECTION_OK) {

	  	//zero out sql1 command string. Not sure why this is necessary. But will fail if I don't
		CLR_SQL1_STR

	  	strfcat(sql1,"delete from \"CMVP_atsec_Only_MIP_Table\"; ");
		sql_result = PQexec(conn, sql1); 

		//if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
			
		

		k=PQresultStatus(sql_result);
	
		if (k != PGRES_TUPLES_OK and k!= PGRES_COMMAND_OK) 
		{//check status
		   printf("\ndelete_table_atsec_only_mip_to_sql.cpp Error 473: SQL  Command failed: sql1=%s\n",sql1);
		  
		  switch (k) {
			case PGRES_EMPTY_QUERY: printf(" PGRES_EMPTY_QUERY:The string sent to the server was empty.\n"); break;
			case PGRES_COMMAND_OK: printf("PGRES_COMMAND_OK:Successful completion of a command returning no data. \n"); break;
			case PGRES_TUPLES_OK: printf("PGRES_TUPLES_OK:Successful completion of a command returning data (such as a SELECT or SHOW). \n"); break;
			case PGRES_COPY_OUT: printf(" PGRES_COPY_OUT:Copy Out (from server) data transfer started.\n"); break;
			case PGRES_COPY_IN: printf(" PGRES_COPY_IN:Copy In (to server) data transfer started.\n"); break;
			case PGRES_BAD_RESPONSE: printf("PGRES_BAD_RESPONSE:The server's response was not understood. \n"); PQclear(sql_result); return(1);break;
			case PGRES_NONFATAL_ERROR: printf("PGRES_NONFATAL_ERROR:A nonfatal error (a notice or warning) occurred. \n"); PQclear(sql_result); return(1);break;
			case PGRES_FATAL_ERROR: printf("PGRES_FATAL_ERROR: A fatal error occurred.\n"); PQclear(sql_result); return(1);break;
			default: printf("Unknown PQresultStatus=%s\n",k); break;
			} //switch
		
		} //check status
			PQclear(sql_result);
	} //connection ok
	PQfinish(conn);


	return(0);


} //main