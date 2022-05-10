#include <stdio.h>
//#include </usr/local/Cellar/postgresql/13.2_1/include/libpq-fe.h>
#include <//usr/include/postgresql/libpq-fe.h>  //ubuntu
#include <iostream>
#include <fstream>
#include <string>
#include "utils.h"
#include <unistd.h>
#include <stdarg.h>  //ubuntu



//global variables
PGconn *conn;
char * MasterStr;


#define VALUE_SIZE 512   //max size of value between tags
#define SQL_MAX 4096    //max size of a sql query string
//=========================================================
#define DEBUG  (1)   //set to (0) to turn off printf messages. set to (1) to turn on printf messages.

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
#define STANDARD 11

//PROTOTYPES
void strfcat(char *, char *, ...);
int str_find (const char * ,byte * ,const long , long );
int date1_lt_date2 (const char *, const char *);

#include "current_MIP_Indicator_sql.h"


//=======================================================
//Intel Specific Stuff for current_MIP_Table
int manual_update() 
{

//This procedure will be used to manually update the really wierd (and hopefully, not too frequent) situtations
// that are test escapes from the normal operations flow.
// For example, Intel changed the module name during Review_Pending, In_Review and in the middle of Coordination which messed up the CMVP MIP website too which my parsing 
// algorithm failed to catch. Hence, the need to  manually fix stuff on the rare occassion. 


char *value;
char *value1;
char *value2;
char *value3;
char *value4;
char *valueName;
char *valueTID;

PGresult *sql_result;
int         nFields;
int i,j;
char sql1 [SQL_MAX];
int k;
	




// Set the SL and Module Type in MIP_Table based on data in Active_Table.
//Basically, if there is a single module (no dups) that has the same module_name and same module_vendor in both MIP and Active tables, then they are the same module.
// So, set the SL and Module Type in MIP to what is in Active.
CLR_SQL1_STR
strfcat(sql1," UPDATE  \"CMVP_MIP_Table\" SET \"Module_Type\" = subquery.\"Module_Type\" , \"SL\"=subquery.\"SL\", \"Cert_Num\"=subquery.\"Cert_Num\" ");
strfcat(sql1," from(	SELECT \"CMVP_MIP_Table\".\"Module_Name\", \"CMVP_MIP_Table\".\"Vendor_Name\", \"CMVP_Active_Table\".\"Module_Type\",\"CMVP_Active_Table\".\"SL\",\"CMVP_Active_Table\".\"Cert_Num\"  from \"CMVP_MIP_Table\"   ");
strfcat(sql1," inner JOIN \"CMVP_Active_Table\"  ON \"CMVP_MIP_Table\".\"Module_Name\" =  \"CMVP_Active_Table\".\"Module_Name\" ");
strfcat(sql1," and \"CMVP_MIP_Table\".\"Vendor_Name\" = \"CMVP_Active_Table\".\"Vendor_Name\" and \"CMVP_Active_Table\".\"Status2\" is null ) ");
strfcat(sql1," as subquery  where \"CMVP_MIP_Table\".\"Module_Name\"=subquery.\"Module_Name\" and \"CMVP_MIP_Table\".\"Vendor_Name\"=subquery.\"Vendor_Name\" ; ");
sql_result = PQexec(conn, sql1); 
sql_result = PQexec(conn, sql1); 
if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
	printf("\nError 109: SQL Update SL and Module_Type in MIP Table Command failed: sql1=%s\n",sql1);
PQclear(sql_result);


//seriously goofy dates (dates going backwards) for Intel product with UL. Two modules with same name but only one advances to Coordination. 
//Cert #3838 is Ice Lake. THe other one (TID 1002) is Comet Lake.  I'll kill both of those rows, and manually put back both rows since having
//the same name make my algorithm want to skip both of them as "DUP-Name". Also, I'll remove all the (R) marks and single quotes.

CLR_SQL1_STR

strfcat(sql1," delete from \"CMVP_MIP_Table\" where \"Module_Name\" like 'Cryptographic Module for Intel Platform%%' and \"Clean_Lab_Name\" like 'UL' and \"Vendor_Name\" like 'Intel%%'; ");

//new & missing UL
strfcat(sql1," INSERT INTO \"CMVP_MIP_Table\"(\"Status2\",\"TID\",\"Cert_Num\",\"Module_Name\", \"Vendor_Name\", \"Lab_Name\",\"IUT_Start_Date\", \"Review_Pending_Start_Date\",\"In_Review_Start_Date\", \"Coordination_Start_Date\",\"Finalization_Start_Date\",\"Last_Updated\",\"Standard\",\"Clean_Lab_Name\",\"SL\",\"Module_Type\") ");
strfcat(sql1,"  VALUES (NULL,'3838',3838,'Cryptographic Module for Intel Platforms Security Engine Chipset (ICL)','Intel Corporation','UL VERIFICATION SERVICES INC',NULL,'10/11/2019','6/1/2020','7/28/2020','3/4/2021',(select CURRENT_DATE),'FIPS 140-2','UL',1,'Firmware-Hybrid'); ");
strfcat(sql1," INSERT INTO \"CMVP_MIP_Table\"(\"Status2\",\"TID\",\"Module_Name\", \"Vendor_Name\", \"Lab_Name\",\"IUT_Start_Date\", \"Review_Pending_Start_Date\",\"In_Review_Start_Date\", \"Coordination_Start_Date\",\"Finalization_Start_Date\",\"Last_Updated\",\"Standard\",\"Clean_Lab_Name\",\"SL\",\"Module_Type\",\"Cert_Num\",\"Y_CO_Avg\") ");
strfcat(sql1,"  VALUES ('Vanished-03/16/21. Reappear-04/07/21','1002','Cryptographic Module for Intel Platforms Security Engine Chipset (CML)','Intel Corporation','UL VERIFICATION SERVICES INC',NULL,'7/28/2019','9/21/2020','1/25/2021',NULL,(select CURRENT_DATE),'FIPS 140-2','UL',1,'Firmware-Hybrid',NULL,110); ");
//strfcat(sql1,"  VALUES (NULL,'1002','Cryptographic Module for Intel Platforms Security Engine Chipset (CML)','Intel Corporation','UL VERIFICATION SERVICES INC',NULL,'7/28/2019','9/21/2020','1/25/2021',NULL,(select CURRENT_DATE),'FIPS 140-2','UL',1,'Firmware-Hybrid',NULL,110); ");

//orig & has UL
//strfcat(sql1," INSERT INTO \"CMVP_MIP_Table\"(\"Status2\",\"TID\",\"Cert_Num\",\"Module_Name\", \"Vendor_Name\", \"Lab_Name\",\"IUT_Start_Date\", \"Review_Pending_Start_Date\",\"In_Review_Start_Date\", \"Coordination_Start_Date\",\"Finalization_Start_Date\",\"Last_Updated\",\"Standard\",\"Clean_Lab_Name\",\"SL\",\"Module_Type\") ");
//strfcat(sql1,"  VALUES (NULL,'3838',3838,'Cryptographic Module for Intel Platforms Security Engine Chipset (ICL)','Intel Corporation','UL VERIFICATION SERVICES INC',NULL,'7/28/2020','9/21/2020','1/25/2021','3/4/2021',(select CURRENT_DATE),'FIPS 140-2','UL',1,'Firmware-Hybrid'); ");
//strfcat(sql1," INSERT INTO \"CMVP_MIP_Table\"(\"Status2\",\"TID\",\"Module_Name\", \"Vendor_Name\", \"Lab_Name\",\"IUT_Start_Date\", \"Review_Pending_Start_Date\",\"In_Review_Start_Date\", \"Coordination_Start_Date\",\"Finalization_Start_Date\",\"Last_Updated\",\"Standard\",\"Clean_Lab_Name\",\"SL\",\"Module_Type\",\"Cert_Num\",\"Y_CO_Avg\") ");
//strfcat(sql1,"  VALUES (NULL,'1002','Cryptographic Module for Intel Platforms Security Engine Chipset (CML)','Intel Corporation','UL VERIFICATION SERVICES INC',NULL,'10/01/2019','5/02/2020','7/28/2020',NULL,(select CURRENT_DATE),'FIPS 140-2','UL',1,'Firmware-Hybrid',NULL,110); ");



//printf("rgf sql= %s\n",sql1);

sql_result = PQexec(conn, sql1); 

if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
	printf("\nError 130: SQL  Command failed: sql1=%s\n",sql1);
	PQclear(sql_result);
	return (1);
}




//Make a "clean" copy of Module_TYpes and Lab Names since some many labs  have slight variations in their lab name
CLR_SQL1_STR
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Clean_Lab_Name\" = UPPER((string_to_array(\"Lab_Name\", ' '))[1]) where \"Clean_Lab_Name\" is null ;");
//printf("clean_lab_update sql=%s\n",sql1);
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Clean_Module_Type\" =  (case when \"Module_Type\" like '%%Hybrid%%' then 'Hybrid' else \"Module_Type\" end) ");
strfcat(sql1,"	where \"Clean_Module_Type\" is null ");
//printf("clean_module_type update sql = %s\n",sql1);
sql_result = PQexec(conn, sql1); 
sql_result = PQexec(conn, sql1); 
if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
	printf("\nError 80: SQL Clean Lab Name in MIP Table Command failed: sql1=%s\n",sql1);
PQclear(sql_result);


//zero out sql1 command string. Not sure why this is necessary. But will fail if I don't
CLR_SQL1_STR
//Pulling Lab and Vendor data from CMVP website shows that:
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Lab_Name\" = 'ATSEC' where \"Vendor_Name\" like '%%Apple Inc%%'; ");//Apple only uses Atsec (35 times out of 37)
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Lab_Name\" = 'ATSEC' where \"Vendor_Name\" like '%%SUSE%%'; ");//SUSE has only ever used Atsec

//Name changes after module was submitted to CMVP: Platforms added to name
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"TID\" = '1000',\"Module_Type\"='Firmware-Hybrid',\"SL\"=1, \"In_Review_Start_Date\"='2021-07-25' where \"Module_Name\" like 'Intel Converged Security and Manageability Engine (CSME) Crypto Module for Tiger Point PCH, Mule Creek Canyon PCH, and Rocket Lake PCH' and \"Vendor_Name\" like '%%Intel Corp%%'; ");
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"TID\" = '1000' where \"Module_Name\" like 'Cryptographic Module for Intel Converged Security and Manageability Engine (CSME) for Intel Tiger Point PCH' and \"Vendor_Name\" like '%%Intel Corp%%'; ");

//space added to module name??!!?
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"TID\" = '1001',\"Module_Type\"='Firmware-Hybrid',\"SL\"=1 where \"Module_Name\" like 'Cryptographic Module for Intel Converged Security and Manageability Engine (CSME)' and \"Vendor_Name\" like '%%Intel Corp%%'; ");
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"TID\" = '1001' where \"Module_Name\" like 'Cryptographic Module for Intel Converged Security and Manageability Engine(CSME)' and \"Vendor_Name\" like '%%Intel Corp%%'; ");


//Manually adding Lab_Name because I know who it is
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Lab_Name\" = 'ATSEC' where \"TID\" like '1000'; ");
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Lab_Name\" = 'ATSEC' where \"TID\" like '1001'; ");

//Cryptographic Module for Intel® Platforms' Security Engine Chipset
//strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Finalization_Start_Date\"=  '2021-10-04', \"Coordination_Start_Date\"='2021-08-04', \"Lab_Name\" = 'ATSEC' where \"Vendor_Name\" like '%%eWBM%%' and \"Module_Name\" like '%%MS1201 Security Sub-system%%'; ");

//missing html snapshot of time from 6/21 to 10/21. But I know this finalization date is correct based on CMVP_Active_Table. 
//I know (because I was the main tester) that eWBM, Kyocera and Intel OCS.
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Finalization_Start_Date\" = '2021-09-02', \"Coordination_Start_Date\"='2021-07-01' where \"Module_Name\" like 'Intel Offload and Crypto Subsystem (OCS)' and \"Vendor_Name\" like '%%Intel Corp%%'; ");
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Finalization_Start_Date\"=  '2021-08-10', \"Coordination_Start_Date\"='2021-07-10', \"Lab_Name\" = 'ATSEC' where \"Vendor_Name\" like '%%Kyocera Document Solutions%%' and \"Module_Name\" like '%%MFP Cryptographic Module(A)%%'; ");
strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Finalization_Start_Date\"=  '2021-10-04', \"Coordination_Start_Date\"='2021-08-04', \"Lab_Name\" = 'ATSEC' where \"Vendor_Name\" like '%%eWBM%%' and \"Module_Name\" like '%%MS1201 Security Sub-system%%'; ");


sql_result = PQexec(conn, sql1); 

if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
	printf("\nError 1020: SQL  Command failed: sql1=%s\n",sql1);
	PQclear(sql_result);
	return (1);
}


//Fix AEGisolve name since the parser strips out the funny european AE letter (as a SQL injection protection) and makes it Gisolve
CLR_SQL1_STR

strfcat(sql1,"	update \"CMVP_MIP_Table\" set \"Lab_Name\"='AEGISOLVE', \"Clean_Lab_Name\"='AEGISOLVE' where \"Clean_Lab_Name\" like '%%GISOLVE%%'");
sql_result = PQexec(conn, sql1); 
if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
	printf("\nError 182: SQL Lab Name update in MIP Table Command failed: sql1=%s\n",sql1);
PQclear(sql_result);

//---------------------------------------------------------------------------
//Mark the modules that are Intel "FIPS Certifiable". These are Intel products that have been sold to customer but customer does the FIPS certification.
//Manually done right now, but will need to add function to indicator to allow admins to update
//Google and Acumen  
CLR_SQL1_STR

strfcat(sql1," update \"CMVP_MIP_Table\" set \"Module_Type\"='Hardware', \"SL\"=1, \"Lab_Name\"='ACUMEN', \"Clean_Lab_Name\"='ACUMEN', \"Status3\"= ");
strfcat(sql1," case 	when \"Status3\" is null  then '.Intel_Certifiable.' when \"Status3\" not like '%%.Intel_Certifiable.%%' then '.Intel_Certifiable.' || \"Status3\" else \"Status3\" end ");
strfcat(sql1," where \"Module_Name\" like '%%Inline Crypto Engine (ICE)%%' AND \"Vendor_Name\" like '%%Google%%'; ");

strfcat(sql1," update \"CMVP_MIP_Table\" set \"Module_Type\"='Hybrid', \"SL\"=1, \"Lab_Name\"='ACUMEN', \"Clean_Lab_Name\"='ACUMEN', \"Status3\"= ");
strfcat(sql1," case 	when \"Status3\" is null  then '.Intel_Certifiable.' when \"Status3\" not like '%%.Intel_Certifiable.%%' then '.Intel_Certifiable.' || \"Status3\" else \"Status3\" end ");
strfcat(sql1," where \"Module_Name\" like '%%Integrated Management Complex (IMC) and B227 True Random Number Generator (TRNG) Firmware-Hybrid Cryptographic Module%%' AND \"Vendor_Name\" like '%%Google%%'; ");

strfcat(sql1," update \"CMVP_MIP_Table\" set \"Module_Type\"='Hybrid', \"SL\"=1, \"Lab_Name\"='ACUMEN', \"Clean_Lab_Name\"='ACUMEN', \"Status3\"= ");
strfcat(sql1," case 	when \"Status3\" is null  then '.Intel_Certifiable.' when \"Status3\" not like '%%.Intel_Certifiable.%%' then '.Intel_Certifiable.' || \"Status3\" else \"Status3\" end ");
strfcat(sql1," where \"Module_Name\" like '%%Look-aside Cryptography and Compression Engine (LCE)%%' AND \"Vendor_Name\" like '%%Google%%'; ");

strfcat(sql1," update \"CMVP_MIP_Table\" set \"Module_Type\"='Hardware', \"SL\"=1, \"Lab_Name\"='ACUMEN', \"Clean_Lab_Name\"='ACUMEN', \"Status3\"= ");
strfcat(sql1," case 	when \"Status3\" is null  then '.Intel_Certifiable.' when \"Status3\" not like '%%.Intel_Certifiable.%%' then '.Intel_Certifiable.' || \"Status3\" else \"Status3\" end ");
strfcat(sql1," where \"Module_Name\" like '%%Non-Volatile Memory express (NVMe) Data Path Security Cluster (DPSC) Module%%' AND \"Vendor_Name\" like '%%Google%%'; ");


sql_result = PQexec(conn, sql1); 
if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  
	printf("\nError 208: SQL Lab update in MIP Table Command failed: sql1=%s\n",sql1);
PQclear(sql_result);




//--------------------------------------------------------------------------------		
// Merge all the duplicate rows with same TID
CLR_SQL1_STR
if DEBUG printf("\nloop_to_merge_all_dups_with_same_TID\n");
strfcat(sql1,"select loop_to_merge_all_dups_with_same_TID (); ");
sql_result = PQexec(conn, sql1);  

if (PQresultStatus(sql_result) != PGRES_TUPLES_OK) 
	{//check status
		printf("bravo7\n");
	   	printf("\nError 110: SQL Merge Function Command failed: sql1=%s\n",sql1);
		return(1);
	} //check status

printf("bravo8: loop_to_merge_all_dups_with_same_TID\n");
return(1);


} //manual_update


//====================================================
int date1_lt_date2 (const char *date1, const char *date2){
//date1 less than date2
//Quick and dirty date compare.
//Assumes date format is 'mm/dd/yyyy'
//   NOTE: the single quote must be included on both sides of the date
//returns 1 (true), if date1 < date2. i.e. date1 is earlier than date2. 
//return 0 (false) otherwise (i.e. date2 >= date1),  
// NOTE: think of NULL = infinity. Then
//	 NULL  < mm/dd/yyyy  returns 0 (false)
//   mm/dd/yyyy < NULL returns 1 (true)  



//printf("zulu1:DATE1=%s  DATE2=%s  \n",date1,date2);
    int day1,month1,year1;
    int day2,month2,year2;

    if(date1=="NULL" && date2!="NULL") 
    	return(0);

    if(date1!="NULL" && date2=="NULL")
    	return(1);

    if(date1=="NULL" && date2=="NULL")
    	return(0);

    sscanf(date1,"'%d/%d/%d'",&month1,&day1,&year1); //reads the numbers in a mm/dd/yyyy format
    sscanf(date2,"'%d/%d/%d'",&month2,&day2,&year2); //from the string

    //printf("zulu2 Date1: month1=%d.  day1=%d. year1=%d\n",month1,day1,year1);
	//printf("zulu2 Date2: month2=%d.  day2=%d. year2=%d\n",month2,day2,year2);

    if(year1<year2)
    	return(1);
    else if (year1>year2)
    	return(0);   
    else
    {//  year1=year2

    	if(month1<month2)
    		return(1);
    	else if (month1>month2)
    		return(0);
    	else
    	{//  month1=month2

    		if(day1<day2)
    			return(1);
    		else if (day1>day2)
    			return(0);
    		else
    			return(0);  //hitting this means date1=date2

    	}
    }
 	printf("ERROR 185: should never get here\n");

    return (0);


	
}  //date1 lt date2


//=============================================================================================
int get_value_from_sql (){

PGresult *sql_result;
char *PQresultErrorMessage(const PGresult *res);
char * error_string;
int         nFields;
int i,j;
int my_rows;
char sql1 [SQL_MAX];
int k;

	CLR_SQL1_STR

	strfcat(sql1,"select * from \"CMVP_MIP_Table\"   where \"Vendor_Name\" = 'Western Digital Corporation'  order by \"Row_ID\" asc limit 10;  "); //Assumes table is already created. Clean up old rows in temp_table
	sql_result = PQexec(conn, sql1);  
	
	k=PQresultStatus(sql_result);


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
	}


	 // first, print out the table collumn attribute names

	nFields = PQnfields(sql_result);

	for (i = 0; i < nFields; i++)
        printf("%-15s", PQfname(sql_result, i));

    printf("\n\n");

    // next, print out the rows of data

    for (i = 0; i < PQntuples(sql_result); i++)
    {
 	    for (j = 0; j < nFields; j++)
	        printf("%-15s", PQgetvalue(sql_result, i, j));
        printf("\n");
    }
	 
	 PQclear(sql_result);
    
	 return(0);

}//get value from sql

//===================================================================================================
char* deblank(char* input)                                                  // deblank accepts a char[] argument and returns a char[] 
{
	int i,j,end_value,input_length,remove_char_count;

    char *output=input;
    input_length=strlen(input);

    remove_char_count=0;
    for (int i = 0, j = 0; i<input_length; i++,j++)                        // Evaluate each character in the input 
    {
        if (input[i]!=-1)   
        {                                               // If the character is not a neg 1 
            output[j]=input[i];  						// Copy that character to the output char[] 
            //remove_char_count++;  
        }                                             
        else
           { j--;
           remove_char_count++;
           }                                       // If it is a neg 1 then do not increment the output index (j), the next non-space will be entered at the current index 
    }
	//end_value=j;
	//output[j]='\'';
	
	//printf(" input_length=%d. remove_char_cnt=%d. input_length-remove_char_cnt=%d\n",input_length,strlen(input),remove_char_count,input_length-remove_char_count);

	for (i=(input_length - remove_char_count);i<input_length;i++)
		{	//printf("alpha %d: %c\n",i,output[i]);
			output[i]=0;
		}


	//for(i=0;i<input_length;i++)
	//	printf("%d: %c\n",i,output[i]);

    return output;                                                          // Return output char[]. Should have no neg 1's
}
//===================================================================================================
const char * sanitize_sql_input ( char * str){
// This will prevent sql injection attacks (or simple bad value strings from the CMVP website)
// This will "escape" any single quote ’  '  `  by repeating it
//

const char * return_str;

char * temp_str;
bool SpaceFound=false;
int i,j,k;


	
	//printf("\npre_sanitize: %s\n",str);

    int count = 0; 
    j=0;
    count=0;
    j=strlen(str);
    char virus;
    bool found_one=false;
    if (j>VALUE_SIZE)
    	{printf("ERROR 666: string is too long\n");
		 return(0);
		 }
 

	

	bool Not_Done=true;

    str[count++]='\'';
    i=1;
    while (Not_Done)
    
     {
     	if( str[i]!=0x27 && str[i]!=0x60 && str[i]!=0xb4)
     	{
     		str[count++]=str[i];
     		i++;
     	}
     	else
     	{
     		found_one=true;
     		//printf("Virus=%d\n",str[i]);
     		str[count++]=str[i];
     		//move every element down by one
     		for(k=j-1; k>count;k--)
     			str[k]=str[k-1];
			str[count++]=str[i];
    		
    		i++;
    		i++;
    		j++;  //increasing my total length by 1 (Since I'm repeating the char), so j++;


		}
     	Not_Done=(i <=j-2);

     }//for
     
     str[count++]='\'';
     str[count++]='\0';

//if (found_one){
	//printf("  String After Sanity: %s\n",str);//dummyF);
 //}

//printf("pos_sanitize: %s\n",str);     

return_str=str;

return(return_str);
 
   
} //sanitize_sql_input




//=============================================================================================================

const char * strip_space ( char * str){
//This will remove:
//1)  leading and trail spaces. 
//2)  registered trademark symbols '®'
//3)  registered trademark sybmols '(R)' and (TM)
//    		These symbols plays havoc with the overall algorithm. The answer is to remove all ® and (R).
//4)  tab characters

const char * return_str;
bool SpaceFound=false;
int i,j;
	
	//printf("pre-strip: %s\n",str);

    int count = 0; 
    count=0;
   
    for (i = 0; str[i]; i++) 
        if (str[i] != 0x9 && str[i]!=0xA && str[i]!=0xD ) 
        	if(str[i]!=' ' || str[i+1]!=' ')
        	   	str[count++] = str[i]; // here count is incremented 
    str[count] = '\0'; 
	
    //remove the single leading space if there is one(remeber, all consecutive spaces have already been converted to a single space above)
    if(str[1]==' ')
    {
	    count=1;
	    for(i=2;str[i];i++)
	    	str[count++]=str[i];
	    str[count]='\0';
	}

    //remove the single trailing space if there is one
	j=strlen(str);
	if(str[j-2]==' ')
	{
		str[j-2]=str[j-1];
	    str[j-1]='\0';
    }

//replace '®', from string with -1 instead. NOTE: ®  is actually two ascii characters.
	for (i=1;i<strlen(str);i++)
	//	if(str[i]>0x7f || str[i]<0)
		if(str[i]>0x92 || str[i]<0)  //include AE for aegisovle
			str[i]=-1;  //replace any funny ASCII with a negative 1.

//replace '(R)' from string with -1 instead
	for (i=1;i<strlen(str);i++)
		if(str[i]=='(' && str[i+1]=='R' && str[i+2]==')' )
			{ str[i]=-1; str[i+1]=-1; str[i+2]=-1; }

//replace '(TM)' from string with -1 instead
	for (i=1;i<strlen(str);i++)
		if(str[i]=='(' && str[i+1]=='T' && str[i+2]=='M' && str[i+3]==')' )
			{ str[i]=-1; str[i+1]=-1; str[i+2]=-1; str[i+3]=-1;}

	//now remove all the negative 1's
	str=deblank(str);

	return_str=sanitize_sql_input(str);

	//printf("post-strip: %s\n",return_str);
	

	return(return_str);
 


} //strip space
//===================================================================================================



int str_find (const char * str1,byte * str2,const long len, long pos){

//This finds the first occurence of str1 inside str2 starting at pos
//Input: str1  : sub string I'm looking for 
//       str2  : super string in which I'm looking for str1
//		len    : strlen(str2)
//       pos   : starting pos
//		
//Output:  position of first occurence. 0 means str1 is not in str2
long i,j;
bool found_it=true;
long str1_len=0;

	str1_len=strlen(str1); 

	for (i=pos;i<=len-str1_len;i++) {
			j=0;
			found_it=true;
			for (j=0;j<str1_len;j++){
				if(str1[j]!=str2[i+j]){
					found_it=false;
					break;
				} // if j
			} //for j	
			if(found_it)
				break;
	} //for i 

	if(found_it)
		return i;
	else
		return 0;

} //str find




//=============================================================================================================

int insert_sql_table(const char * tid_value, const char * module_name,const char * vendor_name, const char * lab_name,const char * iut_value,
					const char * review_pending, const char * in_review, const char * coordination, const char * finalization,
					const char * standard_value){
//This will take the incoming current_row and update the SQL table with its data (where appropriate)
//Input:  all the columns names for the main sql table
//output: return (1) for error. return (0) for success. 


char *value;
char *value1;
char *value2;
char *value3;
char *value4;
char *valueName;
char *valueTID;

PGresult *sql_result;
int         nFields;
int i,j;
char sql1 [SQL_MAX];
int k;
	

		//zero out sql1 command string. Not sure why this is necessary. But will fail if I don't
		CLR_SQL1_STR

	   	//build my sql  command string
		strfcat(sql1,"INSERT INTO \"CMVP_MIP_Table\"(\"TID\",\"Module_Name\", \"Vendor_Name\", \"Lab_Name\",\"IUT_Start_Date\", \"Review_Pending_Start_Date\",");
		strfcat(sql1,"\"In_Review_Start_Date\", \"Coordination_Start_Date\",\"Finalization_Start_Date\",\"Last_Updated\",\"Standard\") ");
		//strfcat(sql1,"VALUES (%s,replace(%s,'(R)',''),replace(%s,'(R)',''),%s,%s,%s,%s,%s,%s,(select CURRENT_DATE),%s)",tid_value,module_name,vendor_name,lab_name,iut_value,review_pending,in_review, coordination, finalization,standard_value);
		strfcat(sql1,"VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,(select CURRENT_DATE),%s)",tid_value,module_name,vendor_name,lab_name,iut_value,review_pending,in_review, coordination, finalization,standard_value);
		if DEBUG
			printf("\nInserting row with today's date: sql1 =%s\n",sql1);

		sql_result = PQexec(conn, sql1); 

		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			printf("\nError 173: SQL  Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}
		

return 0;
} //insert_sql_table





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
//printf("alpha:size(src)=%d. size(buf)=%d\n",strlen(src),strlen(buf));
    strcat(src, buf);
}//strfcat

//====================================================================================================
long int parse_modules_from_single_cmvp_file (char * file_name,byte* data,const long len){
//this will parse a single module from the HTML _cmvp_file after it is pulled from the CMVP MIP Status website
//It will then update that module info  in the main SQL table
//intput: "data" is a byte stream where each character of the HTML file is a single index
//        len   is the total number of bytes in the HTML file
//		  
//output: returns 1 on error. returns 0 on success




const char * last_update_date;
const char * tid_value;
const char * module_name;
const char * vendor_name;
const char * lab_name;   
const char * iut_value;
const char * review_pending;
const char * in_review;
const char * coordination;
const char * finalization;
const char * standard_value;  //will either be "FIPS 140-2"  or  "FIPS 140-3"
const char * status_value;  




PGresult *sql_result;
char sql1 [SQL_MAX];

int dim_value;
int i,j;
int myX,myY,prevX=0;
int myY_a; // used for new CMVP url linke embedded in vendor name </a>
char dummy1[VALUE_SIZE];
char dummy2[VALUE_SIZE];
char dummy3[VALUE_SIZE];
char dummy4[VALUE_SIZE];
char dummy5[VALUE_SIZE];
char dummy6[VALUE_SIZE];
char dummy7[VALUE_SIZE];
char dummyTID[VALUE_SIZE];
char dummyIUT[VALUE_SIZE];
char dummySTANDARD[VALUE_SIZE];
char dummySTATUS[VALUE_SIZE];
int num_of_modules=1;
int termination_value;
int file_calc;

int Standard_Column_Exists;  // "Standard" (FIPS 140-2 or FIPS 140-3) was introduced on 9/25/2020. Before that, it was always FIPS 140-2.Need to account for that.
int Status_Column_Exists;    // "Status" with a single value (Review Pending, In Review, Coordination, Finalization) was introduced on 10/28/2020. Before that, a matrix display was used to show status.

	for(i=0;i<VALUE_SIZE;i++) 	dummy1[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy2[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy3[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy4[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy5[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy6[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy7[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummyTID[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummyIUT[i]=0;	
	for(i=0;i<VALUE_SIZE;i++) 	dummySTANDARD[i]=0;	
	for(i=0;i<VALUE_SIZE;i++) 	dummySTATUS[i]=0;	

	//CMVP changes their HTML format. The existance of these keywords will dictate how the parser works.
	termination_value=str_find ("tfoot id=\"MIPFooter\"", data,len, 0);	//this is where the interesting part of the  HTML file ends.
	Standard_Column_Exists=str_find(">Standard<",data,len,0);// "Standard" (FIPS 140-2 or FIPS 140-3) was introduced on 9/25/2020. Before that, it was always FIPS 140-2.Need to account for that.
	Status_Column_Exists=str_find(">Status<",data,len,0); // "Status" with a single value (Review Pending, In Review, Coordination, Finalization) was introduced on 10/28/2020. Before that, a matrix display was used to show status.

	
	CLR_SQL1_STR
	//setup temporary sql table. 
	strfcat(sql1,"delete from \"Daily_CMVP_MIP_Table\""); //Assumes table is already created. Clean out old rows (modules from the HTML file) 
	printf("\ndelete Daily sql1=%s\n",sql1);
	sql_result = PQexec(conn, sql1);  //do delete
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			printf("\nError 618a: SQL  Command failed: sql1=%s\n",sql1);
   			switch (PQresultStatus(sql_result)) {
				case PGRES_EMPTY_QUERY: printf(" PGRES_EMPTY_QUERY:The string sent to the server was empty.\n"); break;
				case PGRES_COMMAND_OK: printf("PGRES_COMMAND_OK:Successful completion of a command returning no data. \n"); break;
				case PGRES_TUPLES_OK: printf("PGRES_TUPLES_OK:Successful completion of a command returning data (such as a SELECT or SHOW). \n"); break;
				case PGRES_COPY_OUT: printf(" PGRES_COPY_OUT:Copy Out (from server) data transfer started.\n"); break;
				case PGRES_COPY_IN: printf(" PGRES_COPY_IN:Copy In (to server) data transfer started.\n"); break;
				case PGRES_BAD_RESPONSE: printf("PGRES_BAD_RESPONSE:The server's response was not understood. \n"); PQclear(sql_result); return(1);break;
				case PGRES_NONFATAL_ERROR: printf("PGRES_NONFATAL_ERROR:A nonfatal error (a notice or warning) occurred. \n"); PQclear(sql_result); return(1);break;
				case PGRES_FATAL_ERROR: printf("PGRES_FATAL_ERROR: A fatal error occurred.\n"); PQclear(sql_result); return(1);break;
				default: printf("Unknown PQresultStatus=%s\n",PQresultStatus(sql_result)); break;
			} //switch	
			PQclear(sql_result);
			return (1);}
		//printf("\ndelete PQ_RET=%d\n",PQresultStatus(sql_result));	
		
		
	//------------- Get Last Update Date ------------------------

	myX=str_find ("Last Updated:", data,len, 0);  //returns file ptr postion of first "<"
	myY=str_find ("</p>", data,len, myX+1); 

	
	if((myX==0 || myY==0)) {
		printf("***** Error 142a: Last Update Tag Not found (x=%d.y=%d)\n",myX,myY);
		return 1;
	}
	else{

		j=1;
		dummy1[0]='\''; 
		for	(i=myX+13;i<myY;i++){  //The literal 26 is to skip "<h1>CMVP Status Report for"
			dummy1[j]=data[i];
			j++;}  //I only want the subset (mm/dd/yyyy) from the file
		dummy1[j]='\''; 
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy1[i]=0;
		last_update_date=strip_space(dummy1); //convert my char [255] to char *
	} //else
	

	printf("Last Update: %s.  \n",last_update_date);	

	
		
//Big Loop for all modules within this single file
while (myX >0 && myX >= prevX && myX<termination_value) {

	if DEBUG printf("***** Module %d   *******************\n",num_of_modules); 
	num_of_modules++;

	
	//----------- Get Module Name ---------------------------------------

	myX=str_find ("<td>", data,len,myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	
	myY=str_find ("</td>", data,len, myX+1);  
	
	if( myX>termination_value) {
		printf("Done with file.\n");
		break;
	}

	if(myX==0){
		printf("Exiting A: Can't find tag for Module Name\n");
		break;
	}

	
	if(myX<prevX ) 
		printf("Exiting B: myX=%d (-1 means I can't find tag), prevX=%d\n",myX,prevX);
	else
		prevX=myX;

	if(myX==0 || myY==0){
		printf("***** Error 142b: Module Name Tag Not found (x=%d.y=%d)\n",myX,myY);break;	}
	else
	{
		j=1;
		dummy2[0]='\'';
		for	(i=myX+4;i<myY;i++){  
			dummy2[j]=data[i];
			j++;}  
		dummy2[j]='\'';	
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy2[i]=0;
		
		module_name=strip_space(dummy2); //convert my char [255] to char *
	} //else
	if DEBUG printf("Module Name: %s\n",module_name);

	
	//--------------- Get Vendor Name -----------------------------------

	myX=str_find ("<td>", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myY=str_find ("</td>", data,len, myX+1); 
	myY_a=str_find ("<a ", data,len, myX+1);  

	//CMPV started put "contact information" as a URL for each vendor name starting 04/21/2021. I have to strip that </a> stuff out.
	if(myY_a>0 && myY_a < myY)
		{//printf("myY_a=%d. myY=%d.\n",myY_a,myY);
		myY=myY_a;
		}

	if(myX==0){
		printf("Exiting A: Can't find tag for Vendor Name\n"); 	break; 	}
	if(myX<prevX) 
		printf("Exiting C: myX=%d (-1 means I can't find tag), prevX=%d\n",myX,prevX);
	else
		prevX=myX;

	if(myX==0 || myY==0){
		printf("***** Error 142c: Vendor Name Tag Not found (x=%d.y=%d)\n",myX,myY);
		break;
	}
	else{
		j=1;
		dummy3[0]='\'';
		for	(i=myX+4;i<myY;i++){  
			dummy3[j]=data[i];
			j++;}  
		dummy3[j]='\'';
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy3[i]=0;
			
		vendor_name=strip_space(dummy3); //convert my char [255] to char *
	} //else
	if DEBUG printf("Vendor Name: %s\n",vendor_name);
	//------------------------Get TID and lab_name -----------------------------
		


	lab_name="NULL";
	tid_value="NULL";  // No TID value yet since this is a Module In Process.


	if DEBUG  printf("TID: %s",tid_value);
	
	//--------------- Get the STANDARD (140-2 or 140-3) ----------------------------------

	// The column header field ">Standard<" was introduced by CMVP on 9/25/2020. So any file before that will not
	// have that field.  Any file after 9/25/2020 will have that field. 
	
	if (Standard_Column_Exists)
	{

		//this file does have ">Standard<" field  so the Standard could be 
		//FIPS 140-2  or   FIPS 140-3.  Therefore, capture the value in the Standard field.

		myX=str_find ("<td>", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
		myY=str_find ("</td>", data,len, myX+1);  
		if(myX==0){
			printf("Exiting A: Can't find tag for Standard Value (140-2 or 140-3)\n");	break;	}
		if(myX<prevX) 
			printf("Exiting C: myX=%d (-1 means I can't find 'Standard' tag), prevX=%d\n",myX,prevX);
		else
			prevX=myX;

		if(myX==0 || myY==0){
			printf("***** Error 630a: Standard Value (140-2 or 140-3) Tag Not found (x=%d.y=%d)\n",myX,myY);
			break;
			}
		
		j=1;
		dummySTANDARD[0]='\'';
		for	(i=myX+4;i<myY;i++){  
			dummySTANDARD[j]=data[i];
			j++;}  
		dummySTANDARD[j]='\'';
		for(i=j+1;i<VALUE_SIZE;i++)
			dummySTANDARD[i]=0;
			
		//if DEBUG
		//	printf(">Standard< Tag Exists? YES. 140-2 or -3 since: %s is post-140-3",last_update_date);
	}
	else
	{  
		//this file does NOT have ">Standard<" field. So I know the standard must be "FIPS 140-2" (because 140-3 was only introduced 
		//after 9/25/2020). Therefore set the Standard to "FIPS 140-2" and then skip to next field.
		
		dummySTANDARD[0]='\'';
		dummySTANDARD[1]='F';
		dummySTANDARD[2]='I';
		dummySTANDARD[3]='P';
		dummySTANDARD[4]='S';
		dummySTANDARD[5]=' ';
		dummySTANDARD[6]='1';
		dummySTANDARD[7]='4';
		dummySTANDARD[8]='0';
		dummySTANDARD[9]='-';
		dummySTANDARD[10]='2';
		dummySTANDARD[11]='\'';

		//if DEBUG
		//	printf(">Standard< Tag Exists? NO. So, only 140-2 since: %s is pre-140-3",last_update_date);

	}  //doesNOT have ">Standard<" field.
	
	standard_value=strip_space(dummySTANDARD); //convert my char [255] to char *. Remove leading & trailing spaces.

	if DEBUG printf("\nStandard: %s\n",standard_value);


	//--------- Get the IUT which also uses <td> </td> -------------------

	iut_value="NULL";  

	if DEBUG  printf("IUT: %s\n",iut_value);



	//-------------- Status ------------------------------------
	// "Status" with a single value (Review Pending, In Review, Coordination, Finalization) was introduced on 10/28/2020. 
	//  Before that, a matrix display was used to show status using the highlight attribute of HTML.
	//  I'll have to determine which format is used in this file. The "else" clause below has the code looking for the
	//  "highlight" html attribute to determine the status.

	if(Status_Column_Exists)
	{

		//if DEBUG
		//	printf(">Status< Tag Exists? YES. Use value associated with tag. \n");

		//initilizate everyting to null
		review_pending="NULL";
		in_review="NULL";
		coordination="NULL";
		finalization="NULL";

		
		// get the value associated with "Status"
		myX=str_find (">", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
		myY=str_find ("</td>", data,len, myX+1);  
		if(myX==0){
			printf("Exiting A: Can't find tag for Status\n"); break; 	}
		if(myX<prevX) 
			printf("Exiting C: myX=%d (-1 means I can't find 'Status' tag), prevX=%d\n",myX,prevX);
		else
			prevX=myX;
		if(myX==0 || myY==0){
			printf("***** Error 630a: Status Value Tag Not found (x=%d.y=%d)\n",myX,myY); 	break; 	}
				
		j=1;
		dummySTATUS[0]='\'';
		for	(i=myX+4;i<myY;i++){  
			dummySTATUS[j]=data[i];
			j++;}  
		dummySTATUS[j]='\'';
		for(i=j+1;i<VALUE_SIZE;i++)
			dummySTATUS[i]=0;
			
		status_value=dummySTATUS; //convert my char [255] to char *

		
		if(strstr(status_value,"Review Pending")!=0) 
				review_pending=last_update_date; 
		else if (strstr(status_value,"In Review")!=0) 
				in_review=last_update_date;
		else if (strstr(status_value,"Coordination")!=0) 
				coordination=last_update_date;
		else if (strstr(status_value,"Finalization")!=0) 
				finalization=last_update_date;	
		else
			printf ("ERROR 708: Unknown Status Value=%s\n",status_value);

	
	}
	else
	{   //use the highlight attribute to determine status
			//if DEBUG 
			//	printf(">Status< Tag Exists? NO. Use'highlight' HTML attribute. Status_Column_Exist=%d \n",Status_Column_Exists);

			//-------------- Review Pending ------------------------------
			myX=str_find ("<td", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
			myY=str_find ("</td>", data,len, myX+1);  
			if(myX==0){
				printf("Exiting A: Can't find tag for Review_Pending\n");
				break;	}
			if(myX<prevX) 
				printf("Exiting E: myX=%d (-1 means I can't find tag), prevX=%d\n",myX,prevX);	
			else
				prevX=myX;

			if(myX==0 || myY==0) { 
				printf("***** End Of File (or Missing Tag) (x=%d.y=%d)\n",myX,myY);	break;	}
			else{
				j=0;
				for	(i=myX+4;i<myY;i++){  
					dummy4[j]=data[i];
					j++;}  
					for(i=j+1;i<VALUE_SIZE;i++)
					dummy4[i]=0;
				review_pending=dummy4; //convert my char [255] to char *
			} //else

			//Each module in this file will only have a single CMVP state with keyword "highlighted".
			//The "highlight" state is the current state. All other states are NULL.
			//If a state is "highlighted", then I'll replace its NULL value with the file's date. 
			if(strstr(review_pending,"highlight")!=0) 
				review_pending=last_update_date; 
			else
				review_pending="NULL";
		
			//-------------- In Review ------------------------------

			myX=str_find ("<td", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
			myY=str_find ("</td>", data,len, myY+1);  
			if(myX==0){
				printf("Exiting A: Can't find tag for In Review\n");break; }
			if(myX<prevX) 
				printf("Exiting F: myX=%d (-1 means I can't find tag), prevX=%d\n",myX,prevX); 	
			else
				prevX=myX;
			if(myX==0 || myY==0){
				printf("***** Error 142e: In Review Tag Not found (x=%d.y=%d)\n",myX,myY);	break; 	}
			else{
				j=0;
				for	(i=myX+4;i<myY;i++){  
					dummy5[j]=data[i];
					j++;}  
				for(i=j+1;i<VALUE_SIZE;i++)
						dummy5[i]=0;
				in_review=dummy5; //convert my char [255] to char *
			} //else

			//Each module in this file will only have a single CMVP state with keyword "highlighted".
			//The "highlight" state is the current state. All other states are NULL.
			//If a state is "highlighted", then I'll replace its NULL value with this file's date. 
			if(strstr(in_review,"highlight")!=0)
				in_review=last_update_date;
			else
				in_review="NULL";
					
			//-------------- Coordination ------------------------------

			myX=str_find ("<td", data,len, myY+1);  ///start at last file position myY+1 (have to inc by 1 to avoid repeat)
			myY=str_find ("</td>", data,len, myY+1);  
			if(myX==0){
				printf("Exiting A: Can't find tag for Coordination\n");	break;	}
			if(myX<prevX) 
				printf("Exiting G: myX=%d (-1 means I can't find tag), prevX=%d\n",myX,prevX); 
			else
				prevX=myX;

			if(myX==0 || myY==0){
				printf("***** Error 142f: Coordination Tag Not found (x=%d.y=%d)\n",myX,myY); break;}
			else{
				j=0;
				for	(i=myX+4;i<myY;i++){  
					dummy6[j]=data[i];
					j++;}  
				for(i=j+1;i<VALUE_SIZE;i++)
					dummy6[i]=0;

				coordination=dummy6; //convert my char [255] to char *
			} //else

			//Each module in this file will only have a single CMVP state with keyword "highlighted".
			//The "highlight" state is the current state. All other states are NULL.
			//If a state is "highlighted", then I'll replace its NULL value with this file's date. 
			if(strstr(coordination,"highlight")!=0)
				coordination=last_update_date;
			else
				coordination="NULL";
			
			//-------------- Finalization ------------------------------

			myX=str_find ("<td", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
			myY=str_find ("</td>", data,len, myY+1);  
			if(myX==0){
				printf("Exiting A: Can't find tag for Finalization\n");	break;	}
			if(myX<prevX) 
				printf("Exiting H: myX=%d (-1 means I can't find tag), prevX=%d\n",myX,prevX);
			else
				prevX=myX;

			if(myX==0|| myY==0){
				printf("***** Error 142g: Finalization Tag Not found (x=%d.y=%d)\n",myX,myY);break; 		}
			else{
				j=0;
				for	(i=myX+4;i<myY;i++){  
					dummy7[j]=data[i];
					j++;}  
				for(i=j+1;i<VALUE_SIZE;i++)
					dummy7[i]=0;

				finalization=dummy7; //convert my char [255] to char *
			} //else
			
			//Each module in this file will only have a single CMVP state with keyword "highlighted".
			//The "highlight" state is the current state. All other states are NULL.
			//If a state is "highlighted", then I'll replace its NULL value with this file's date. 
			if(strstr(finalization,"highlight")!=0)
				finalization=last_update_date;
			else
				finalization="NULL";
			
	
	}   //Big else statement: using the highlight attribute to determine status.

	if DEBUG printf("Review Pending: %s\n",review_pending);
	if DEBUG printf("In Review: %s\n",in_review);
	if DEBUG printf("Coordination: %s\n",coordination);
	if DEBUG printf("Finalization: %s\n",finalization);
	
	//----------- Insert Row Into SQL Table ----------------------------------
	if(myX>0 && myX>=prevX && myX<termination_value)
	{  //Make sure I have a valid row before inserting and copying	
		
		insert_sql_table(tid_value,module_name,vendor_name,lab_name,iut_value,review_pending,in_review,coordination,finalization,standard_value);
		
		
		//Make a copy of this row in a temp table called "Daily". This table is used to see if a module VANISHES from the MIP list.
		//This table will ONLY contain all the modules that are in this current HTML file.
		CLR_SQL1_STR
	
		strfcat(sql1,"INSERT INTO \"Daily_CMVP_MIP_Table\" (\"TID\",\"Module_Name\", \"Vendor_Name\",\"Lab_Name\", \"IUT_Start_Date\", ");
		strfcat(sql1,"\"Review_Pending_Start_Date\",\"In_Review_Start_Date\", \"Coordination_Start_Date\", \"Finalization_Start_Date\",\"Standard\" ) ");
		strfcat(sql1,"VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",tid_value,module_name,vendor_name,lab_name,iut_value,review_pending,in_review, coordination, finalization,standard_value);
		printf("\ninsert into Daily: sql1=%s\n",sql1);

		sql_result = PQexec(conn, sql1);  //execute the copy to "Daily" table
		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
				printf("\nError 982: SQL  Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}
		
		
		
			


	//--------------------------------------------
	//=================================================================
	//Figure out Finalization Date:  If module doesn't have a finalization date, AND appears in the ACTIVE table, then use the
	//validation date from the ACTIVE table as the finalization date in the CMVP_MIP_Table.	

		CLR_SQL1_STR

		strfcat(sql1," UPDATE  \"CMVP_MIP_Table\" SET \"Finalization_Start_Date\" = case  when subquery.\"Validation_Date\" is not null  then TO_DATE(right(subquery.\"Validation_Date\",10),'MM/DD/YYYY')  else null end  ");
		strfcat(sql1," from( SELECT \"Validation_Date\"   from \"CMVP_Active_Table\" where \"Vendor_Name\" like %s and \"Module_Name\" like %s ",vendor_name,module_name);
		strfcat(sql1," and \"CMVP_Active_Table\".\"Status\" like 'Active')as subquery ");
		strfcat(sql1," where \"CMVP_MIP_Table\".\"Finalization_Start_Date\" is null AND \"CMVP_MIP_Table\".\"Vendor_Name\" like %s and  \"CMVP_MIP_Table\".\"Module_Name\" like %s ", vendor_name,module_name); 
		strfcat(sql1," and ( TO_DATE(right(subquery.\"Validation_Date\",10),'MM/DD/YYYY') >= \"CMVP_MIP_Table\".\"Coordination_Start_Date\") ");
		printf("\nGet Final Date using ACTIVE sql1=%s\n",sql1);

		sql_result = PQexec(conn, sql1);  //execute the Finalization Date update 
		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
					printf("\nError 1089: SQL Command failed: sql1=%s\n",sql1);
					PQclear(sql_result);
				    return (1);
				}
		//printf("\nPQ_RETb=%d\n",PQresultStatus(sql_result));
		


	}//Make sure I have a valid row before inserting and copying
	else
		printf("error 965: invalid row. myX=%d\n",myX);

} // Big Loop for all modules


//All done parsing this single HTML file. Now I just need to clean up the Tables:  
// 0) Find all duplicates (same Vendor_Name and Module_Name) from this file that are in Daily_CMVP_MIP_Table. PLUS, mark them as DUPS in the  CMVP_MIP_Table, no matter their state.
// 1) mark all Vanished Modules.
// 2) merge duplicates that are only in CMVP_MIP_Table. This is basically merging the muliple HTML files together. Not the dup entries in a single HTML file.
// 3) santiy check the CMVP going backwards in their FSM (i.e. moving from finalization back to coordination (e.g. Module_Name=Aruba IAP-303H))
	

//==================
//Find all duplicates in Daily_CMVP_MIP_Table and mark their counterparts in CMVP_MIP_Table as ""DUP_IN_FILE" since they are in the same HTML file twice (i.e. Daily parsing)
		//This is necessary because some vendors use identical module names for modules that are on the CMVP MIP website simultaneously.  I can't
		//tell programatically which module is which since they have the same Module_Name and Vendor_Name. I considered keeping only the first instance
		// and discarded the 2nd instance. But there's no guarantee they modules would always appear in the same order on the MIP website. Not doing this
		//action could really skew all the Start_Dates and mess up the duration calculations for the different MIP states.
		

		
CLR_SQL1_STR

strfcat(sql1," update \"CMVP_MIP_Table\" set \"Status2\" = 'DUP_IN_FILE' from  ");
strfcat(sql1," (	SELECT \"Module_Name\", \"Vendor_Name\", COUNT( \"Module_Name\" ) as \"cnt\" ");
strfcat(sql1," 	FROM \"Daily_CMVP_MIP_Table\" group by \"Module_Name\",\"Vendor_Name\" HAVING   COUNT( \"Module_Name\" )> 1)  ");
strfcat(sql1," as subquery where subquery.\"cnt\" > 1 and \"CMVP_MIP_Table\".\"Module_Name\"=subquery.\"Module_Name\" " );
strfcat(sql1," and \"CMVP_MIP_Table\".\"Vendor_Name\"=subquery.\"Vendor_Name\"  ");

printf("\nMark DUP_IN_FILE =%s\n",sql1);

sql_result = PQexec(conn, sql1);  //execute 
if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
			printf("\nError 1055: SQL Command failed: sql1=%s\n",sql1);
		PQclear(sql_result);
		return (1);}
//===================
// Vanishing Modules: sometimes a module  will just disappear from one HTML file to the next and 
// no update on it is ever seen again on the MIP table. This could be because the vendor cancelled it, or it got merged with another module, it was renamed, it transitioned
// to the Active state, or something else.  This next section will mark any disappearing modules by setting its status to 'VANISHED'
//NOTE1: the expectation is that ALL "normal" modules will eventually VANISH. Since upon going ACTIVE, they will disappear from the Daily_CMVP_MIP_Table.
//NOTE2: a module can VANISH, and then Reappear on the MIP list. The elapsed time between the two states could be days/weeks/months. 
//		The merge_all_dup function below addresses that by creating a state called "Vanished-mm/dd/yyyy. Reappear-mm/dd/yyyy" where the VANISH and Reapper dates are included.

//See if this module has dropped off the MIP list.
CLR_SQL1_STR

strfcat(sql1,"UPDATE  \"CMVP_MIP_Table\" SET \"Status2\" = 'Vanished-'%s''  ", last_update_date);
strfcat(sql1," from( SELECT \"CMVP_MIP_Table\".\"Module_Name\", \"CMVP_MIP_Table\".\"Vendor_Name\" from \"CMVP_MIP_Table\"  " );
strfcat(sql1," left  outer JOIN \"Daily_CMVP_MIP_Table\"  ON \"CMVP_MIP_Table\".\"Module_Name\" =  \"Daily_CMVP_MIP_Table\".\"Module_Name\" ");
strfcat(sql1," and \"Daily_CMVP_MIP_Table\".\"Vendor_Name\" = \"CMVP_MIP_Table\".\"Vendor_Name\"  ");
strfcat(sql1," where  \"Daily_CMVP_MIP_Table\".\"Module_Name\" is null  ) as subquery  where  \"CMVP_MIP_Table\".\"Module_Name\"=subquery.\"Module_Name\" "); 
strfcat(sql1," AND \"CMVP_MIP_Table\".\"Vendor_Name\"=subquery.\"Vendor_Name\" ");
strfcat(sql1," and \"CMVP_MIP_Table\".\"Status2\" is null  ;" );

printf("\nRadar Drop Test 1 =%s\n",sql1);

sql_result = PQexec(conn, sql1);  //execute 
if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
			printf("\nError 1136: SQL Command failed: sql1=%s\n",sql1);
		PQclear(sql_result);
		return (1);}

//If the module dropped off the MIP list, is it because it transitioned to the Active Table?  If so, mark it as 'Promoted'. 
CLR_SQL1_STR

strfcat(sql1," UPDATE  \"CMVP_MIP_Table\" SET \"Status2\" = 'Promoted-'%s''  ",last_update_date);
strfcat(sql1," from(	SELECT \"CMVP_MIP_Table\".\"Module_Name\", \"CMVP_MIP_Table\".\"Vendor_Name\", \"CMVP_Active_Table\".\"Validation_Date\", \"CMVP_Active_Table\".\"Status2\" from \"CMVP_MIP_Table\"   ");
strfcat(sql1," inner JOIN \"CMVP_Active_Table\"  ON \"CMVP_MIP_Table\".\"Module_Name\" =  \"CMVP_Active_Table\".\"Module_Name\" ");
strfcat(sql1," and \"CMVP_MIP_Table\".\"Vendor_Name\" = \"CMVP_Active_Table\".\"Vendor_Name\" ) ");
strfcat(sql1," as subquery  where  \"CMVP_MIP_Table\".\"Module_Name\"=subquery.\"Module_Name\" and \"CMVP_MIP_Table\".\"Vendor_Name\"=subquery.\"Vendor_Name\" ");
strfcat(sql1," and \"CMVP_MIP_Table\".\"Status2\" like 'Vanished%%'  ");
strfcat(sql1," and (   ( TO_DATE(right(subquery.\"Validation_Date\",10),'MM/DD/YYYY') >= \"CMVP_MIP_Table\".\"Finalization_Start_Date\") ");
strfcat(sql1," OR ( TO_DATE(right(subquery.\"Validation_Date\",10),'MM/DD/YYYY') >= \"CMVP_MIP_Table\".\"Coordination_Start_Date\")   )");
strfcat(sql1," AND subquery.\"Status2\" is null ");
strfcat(sql1," and (\"CMVP_MIP_Table\".\"Coordination_Start_Date\" is not null OR \"CMVP_MIP_Table\".\"Finalization_Start_Date\" is not null); ");
printf("\nRadar Drop Test 2 =%s\n",sql1);

sql_result = PQexec(conn, sql1);  //execute  
if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
			printf("\nError 1103: SQL Command failed: sql1=%s\n",sql1);
		PQclear(sql_result);
		return (1);}


//Update the lab_name based on the Module_Name and Vendor_Name listed in the Active Table (if they exist yet)

CLR_SQL1_STR

strfcat(sql1," UPDATE  \"CMVP_MIP_Table\" SET \"Lab_Name\" = subquery.\"Lab_Name\" ");
strfcat(sql1," from(	SELECT \"CMVP_MIP_Table\".\"Module_Name\", \"CMVP_MIP_Table\".\"Vendor_Name\", \"CMVP_Active_Table\".\"Lab_Name\" from \"CMVP_MIP_Table\"   ");
strfcat(sql1," inner JOIN \"CMVP_Active_Table\"  ON \"CMVP_MIP_Table\".\"Module_Name\" =  \"CMVP_Active_Table\".\"Module_Name\" ");
strfcat(sql1," and \"CMVP_MIP_Table\".\"Vendor_Name\" = \"CMVP_Active_Table\".\"Vendor_Name\" ) ");
strfcat(sql1," as subquery  where  \"CMVP_MIP_Table\".\"Module_Name\"=subquery.\"Module_Name\" and \"CMVP_MIP_Table\".\"Vendor_Name\"=subquery.\"Vendor_Name\" ");
//strfcat(sql1," AND subquery.\"Status2\" not like 'DUP' ");
printf("Update_Lab_Name =%s\n",sql1);

sql_result = PQexec(conn, sql1);  //execute  
if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
			printf("\nError 1126: SQL Command failed: sql1=%s\n",sql1);
		PQclear(sql_result);
		return (1);}


//--------------------------------------------	
//Merge all the duplicate rows in CMVP_MIP_Table.  There will always be duplicate rows because multiple HTML files will repeat the same module
//over and over for the duration of the module's time in MIP.
CLR_SQL1_STR

if DEBUG printf("loop_merge_all_dups_with_no_TID");
strfcat(sql1,"select loop_to_merge_all_dups_with_no_TID ( ); "); //this sql function is defined inside the current_mip_indicator_sql.h

sql_result = PQexec(conn, sql1);  
if (PQresultStatus(sql_result) != PGRES_TUPLES_OK) 
{//check status
   printf("\nError 1042: SQL Merge Function Command failed: sql1=%s\n",sql1);
   switch (PQresultStatus(sql_result)) {
			case PGRES_EMPTY_QUERY: printf(" PGRES_EMPTY_QUERY:The string sent to the server was empty.\n"); break;
			case PGRES_COMMAND_OK: printf("PGRES_COMMAND_OK:Successful completion of a command returning no data. \n"); break;
			case PGRES_TUPLES_OK: printf("PGRES_TUPLES_OK:Successful completion of a command returning data (such as a SELECT or SHOW). \n"); break;
			case PGRES_COPY_OUT: printf(" PGRES_COPY_OUT:Copy Out (from server) data transfer started.\n"); break;
			case PGRES_COPY_IN: printf(" PGRES_COPY_IN:Copy In (to server) data transfer started.\n"); break;
			case PGRES_BAD_RESPONSE: printf("PGRES_BAD_RESPONSE:The server's response was not understood. \n"); PQclear(sql_result); return(1);break;
			case PGRES_NONFATAL_ERROR: printf("PGRES_NONFATAL_ERROR:A nonfatal error (a notice or warning) occurred. \n"); PQclear(sql_result); return(1);break;
			case PGRES_FATAL_ERROR: printf("PGRES_FATAL_ERROR: A fatal error occurred.\n"); PQclear(sql_result); return(1);break;
			default: printf("Unknown PQresultStatus=%s\n",PQresultStatus(sql_result)); break;
	} //switch	
	PQclear(sql_result);	

   return(-1);

} //check status


//===================================================================
// Santiy check the CMVP going backwards in their FSM. CMVP can move from finalization back to coordination (e.g. Module_Name='%Aruba IAP-303H%',    or ADVA% )
//This breaks the algorithm, so I need to check for it. If I see something weird, like out of order dates, then I'll mark it as 'GOOFY'.

CLR_SQL1_STR

strfcat(sql1," UPDATE  \"CMVP_MIP_Table\"  SET \"Status2\" = \"Status2\" || '. Goofy_Dates-'%s'. ' ", last_update_date );
strfcat(sql1," where \"Status2\" not like '%%Goofy_Dates%%'  AND ");
strfcat(sql1," ( \"Review_Pending_Start_Date\" > \"In_Review_Start_Date\" OR \"Review_Pending_Start_Date\" > \"Coordination_Start_Date\" ");
strfcat(sql1," OR \"Review_Pending_Start_Date\" > \"Finalization_Start_Date\" OR  \"In_Review_Start_Date\" > \"Coordination_Start_Date\" OR ");
strfcat(sql1," \"In_Review_Start_Date\" > \"Finalization_Start_Date\" OR  \"Coordination_Start_Date\" > \"Finalization_Start_Date\" )");

printf("\nGoofy Test =%s\n",sql1);

sql_result = PQexec(conn, sql1);  //execute  
if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
			printf("\nError 1103: SQL Command failed: sql1=%s\n",sql1);
		PQclear(sql_result);
		return (1);}



return 0;

} //parse_modules_from_single_cmvp_file
//===================================================================================================================



//=============================================================================================================

int main (int argc, char* argv[]) {

	const char *Table_Name="CMVP_MIP_Table";
	char *file_path;
	long int file_pos=0;
	
	//char *last_updated;
	data_t data;
	int i;


	
	conn = PQconnectdb("host=localhost user=postgres password=postgres dbname=postgres ");
    if (PQstatus(conn) == CONNECTION_OK) {
    	printf("SUCCESSFUL postgres connection\n");

	  if(create_sql_function()!=0) {
	  		printf("Error 911: SQL functions not created\n");
	  		return(1);
	  	}//


		if(argc != 2) { 
			printf("*** Error 345: Missing Input File Name.\n");
			
		} 

		// get input filename
		file_path = argv[1];


		//------ do file stuff here
		printf("\n\n********************************************************************************\n\n");
		printf("Opening file: '%s'\n", file_path);
		if(!read_file(file_path, &data))
				printf("*** Error 356: Error reading file '%s'.\n",file_path);
			
		

			
		
		parse_modules_from_single_cmvp_file(file_path,data.rawsymbols,  data.len);
		
		manual_update();  //This is catch all for those situations like where Inside Secure was bought by Verimatirx who was bought by Rambus, all within a 12 month period
						  //Even though this is overkill, I'll update these table entries every single time this tool is run just in case the table gets deleted
		                  // during some debug experiment. It won't hurt or slow down the adding of new modules.
		
    	printf("exiting\n");

	} //connection ok
	PQfinish(conn);


	return(0);


} //main