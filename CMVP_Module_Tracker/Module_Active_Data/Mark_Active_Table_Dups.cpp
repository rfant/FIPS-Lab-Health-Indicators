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
	
	
	char connbuff[200];
	
	int i;
	PGresult *sql_result;
	char sql1 [SQL_MAX];



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
			printf("ERROR  110: Unknown PROD=%d\n",PROD); break;
	}


	


    if (PQstatus(conn) == CONNECTION_OK) {


    	//NOTE: this is a stand-alone program to do these tasks. It only needs to run once a day.
    	//If I included this code in the "active_to_sql.cpp" file, then this would run once for every single file (and there are 5000 files!!)
    	//So, much faster to run it once here.

    	//mark all the rows in Active_Table which have the same Module_Name and Vendor_Name repeated on multiple rows. This is approx 1/3 of all the rows.
    	//Do this so that mapping from the CMVP_MIP_Table (which only has Module_name and Vendor_Name) to the CMVP_Active_Table will get us the "Lab_Name" for
    	//the lab field in the CMVP_MIP_Table.
    	//Also, some Module_Name and Vendor_Name dups are done by multiple labs (e.g. Samsung with atsec and GISOLVE) submiting the module
		
    	
		CLR_SQL1_STR
		
		strfcat(sql1,"update \"CMVP_Active_Table\" set \"Status2\"= 'DUP' ");
		strfcat(sql1," from ( Select \"Cert_Num\" 	from 	\"CMVP_Active_Table\" as A1 inner join "); 
		strfcat(sql1,"  (	select \"Module_Name\",\"Vendor_Name\",count(*) from \"CMVP_Active_Table\" "); 
		strfcat(sql1,"	group by \"Module_Name\",\"Vendor_Name\" 	HAVING count(*) > 1 order by \"Vendor_Name\" ");
		strfcat(sql1,"  ) as A2 on A1.\"Module_Name\"=A2.\"Module_Name\" and A1.\"Vendor_Name\"=A2.\"Vendor_Name\" ");
		strfcat(sql1,"  ) as subquery where subquery.\"Cert_Num\"=\"CMVP_Active_Table\".\"Cert_Num\" ");
		sql_result = PQexec(conn, sql1); 

		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
			printf("\nError 473: SQL  Marking Dup Module_Name and Vendor_Names in Active Table Command failed: sql1=%s\n",sql1);
		PQclear(sql_result);
			

		//Make a "clean" copy of Lab Names since some many labs have slight variations in their lab name
		CLR_SQL1_STR

		strfcat(sql1,"	update \"CMVP_Active_Table\" set \"Clean_Lab_Name\" = UPPER((string_to_array(\"Lab_Name\", ' '))[1]) where \"Clean_Lab_Name\" is null ");
		sql_result = PQexec(conn, sql1); 

		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
			printf("\nError 479: SQL Clean Lab Name in Active Table Command failed: sql1=%s\n",sql1);
		PQclear(sql_result);
		
		//Fix AEGisolve name since the parser strips out the funny european AE letter
		CLR_SQL1_STR

		strfcat(sql1,"	update \"CMVP_Active_Table\" set \"Lab_Name\"='AEGISOLVE', \"Clean_Lab_Name\"='AEGISOLVE' where \"Clean_Lab_Name\" like '%%GISOLVE%%'");
		sql_result = PQexec(conn, sql1); 

		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
			printf("\nError 122: SQL Lab Name update in Active Table Command failed: sql1=%s\n",sql1);
		PQclear(sql_result);
		


		//Make a "clean" copy of Module_Types since some labs have variations such as "Software-Hybrid", "Software  Hybrid" and "Hybrid Software". Instead, I'll lump
		//all the hybrids together
		CLR_SQL1_STR

		strfcat(sql1,"	update \"CMVP_Active_Table\" set \"Clean_Module_Type\" =  (case when \"Module_Type\" like '%%Hybrid%%' then 'Hybrid' else \"Module_Type\" end) ");
		strfcat(sql1,"	where \"Clean_Module_Type\" is null ");

		sql_result = PQexec(conn, sql1); 

		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
			printf("\nError 479: SQL Clean Lab Name in Active Table Command failed: sql1=%s\n",sql1);
		PQclear(sql_result);


		//In the CMVP_Permanent_Sunset_Table, merge all rows with the same Cert_Num, validation_date and sunset_date. 
		CLR_SQL1_STR

		strfcat(sql1," update \"CMVP_Permanent_Sunset_Table\" as t1 set \"Validation_Date\" = t2.max_validation_date ");
		strfcat(sql1," from (select \"Cert_Num\",max(\"Validation_Date\") as max_validation_date from \"CMVP_Permanent_Sunset_Table\"  ");
		strfcat(sql1," 	  group by \"Cert_Num\" order by \"Cert_Num\" desc) as t2 ");
		strfcat(sql1," where t1.\"Cert_Num\"=t2.\"Cert_Num\"; ");

		strfcat(sql1," update \"CMVP_Permanent_Sunset_Table\" as t1 set \"Sunset_Date\" = t2.max_sunset_date ");
		strfcat(sql1," from (select \"Cert_Num\",max(\"Sunset_Date\") as max_sunset_date from \"CMVP_Permanent_Sunset_Table\"  ");
		strfcat(sql1," 	  group by \"Cert_Num\" order by \"Cert_Num\" desc) as t2 ");
		strfcat(sql1," where t1.\"Cert_Num\"=t2.\"Cert_Num\"; ");

		

		strfcat(sql1," delete from \"CMVP_Permanent_Sunset_Table\" t1 using \"CMVP_Permanent_Sunset_Table\" t2  ");
		strfcat(sql1," 	 WHERE  t1.\"Row_ID\" < t2.\"Row_ID\"  AND t1.\"Cert_Num\" = t2.\"Cert_Num\"  ");
		strfcat(sql1," 	 and t1.\"Sunset_Date\"=t2.\"Sunset_Date\" and t1.\"Validation_Date\"=t2.\"Validation_Date\" ;");
		
		//printf("permanent sql=%s\n",sql1);
		sql_result = PQexec(conn, sql1); 


		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
			printf("\nError 129: SQL  Permanent Sunset Table Command failed: sql1=%s\n",sql1);
		PQclear(sql_result);

	} //connection ok
	else
		printf("PostgreSQL connection error\n");
	
	PQfinish(conn);
	printf("\n Marking all dup rows in the CMVP_Active_Table now. Plus I will create a clean_lab_name. Plus merging at sunset dates.\n");

	return(0);


} //main