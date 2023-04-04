#include <stdio.h>
#include <//usr/include/postgresql/libpq-fe.h>  //ubuntu

#include <iostream>
#include <fstream>
#include <string>
#include "utils.h"
#include <unistd.h>
#include <sstream>
#include <stdarg.h>  //ubuntu
#include "../dev_or_prod.h"



//#include "Active_Indicator_sql.h"

//global variables
PGconn *conn;
char * MasterStr;


#define VALUE_SIZE 16384 //8192 //1024  //max size of value between tags
#define SQL_MAX 32768 //16384 //8192 //max size of a sql query string
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

//PROTOTYPES


const char * sanitize_sql_input ( char *);
const char * strip_space ( char * );
void strfcat(char *, char *, ...);
int str_find (const char * ,byte * ,const long , long );
int date1_lt_date2 (const char *, const char *);
//==============================================================

#include <openssl/aes.h>

int rgf(){
 	#include <openssl/aes.h>

AES_KEY aesKey_;
unsigned char userKey_[]="0003141592653598";
unsigned char decryptedPW[16];
int rgf(){
 	
   

unsigned char plainTextPW[] = "icDuf94Gae";
    //unsigned char in_[]="postgres";
    //unsigned char encryptedPW[16];
    unsigned char decryptedPW[16];

    //postgres
    // unsigned char out_ []={218,25,112,80,80,4,144,228,28,211,51,153,143,165,170,231};

   

   
    //AES_ctr128_encrypt

/*    AES_set_encrypt_key(userKey_, 128, &aesKey_);
    AES_encrypt(plainTextPW, encryptedPW, &aesKey_);
    	
    	fprintf(stdout,"\nencryptedPW[0]=%d;\n",encryptedPW[0]);
		fprintf(stdout,"encryptedPW[1]=%d;\n",encryptedPW[1]);
   		fprintf(stdout,"encryptedPW[2]=%d;\n",encryptedPW[2]);
   		fprintf(stdout,"encryptedPW[3]=%d;\n",encryptedPW[3]);
   		fprintf(stdout,"encryptedPW[4]=%d;\n",encryptedPW[4]);
   		fprintf(stdout,"encryptedPW[5]=%d;\n",encryptedPW[5]);
   		fprintf(stdout,"encryptedPW[6]=%d;\n",encryptedPW[6]);
   		fprintf(stdout,"encryptedPW[7]=%d;\n",encryptedPW[7]);
   		fprintf(stdout,"encryptedPW[8]=%d;\n",encryptedPW[8]);
   		fprintf(stdout,"encryptedPW[9]=%d;\n",encryptedPW[9]);
   		fprintf(stdout,"encryptedPW[10]=%d;\n",encryptedPW[10]);
   		fprintf(stdout,"encryptedPW[11]=%d;\n",encryptedPW[11]);
   		fprintf(stdout,"encryptedPW[12]=%d;\n",encryptedPW[12]);
   		fprintf(stdout,"encryptedPW[13]=%d;\n",encryptedPW[13]);
   		fprintf(stdout,"encryptedPW[14]=%d;\n",encryptedPW[14]);
   		fprintf(stdout,"encryptedPW[15]=%d;\n",encryptedPW[15]);
   

encryptedPW[0]=44;
encryptedPW[1]=155;
encryptedPW[2]=95;
encryptedPW[3]=216;
encryptedPW[4]=43;
encryptedPW[5]=67;
encryptedPW[6]=13;
encryptedPW[7]=219;
encryptedPW[8]=62;
encryptedPW[9]=86;
encryptedPW[10]=123;
encryptedPW[11]=205;
encryptedPW[12]=185;
encryptedPW[13]=227;
encryptedPW[14]=198;
encryptedPW[15]=171;*/

  unsigned char encryptedPW []={44,155,95,216,43,67,13,219,62,86,123,205,185,227,198,171};



    AES_set_decrypt_key(userKey_, 128, &aesKey_);
    AES_decrypt(encryptedPW, decryptedPW,&aesKey_);
    fprintf(stdout,"\nRecovered Original message: %s\n", decryptedPW);      
    return 0;



 return 0;
    

} //rgf









//===========================================================

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

//=================================================================================================
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
//3)  registered trademark sybmols '(R)'
//    		These symbols plays havoc with the overall algorithm. The answer is to remove all ® and (R).
//4)  tab characters

const char * return_str;
bool SpaceFound=false;
int i,j;
	
	//printf("pre strip_space: %s len=%d\n",str,strlen(str));

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
	    str[count]='\0'; //add null to end of string
	}

    //remove the single trailing space if there is one
	j=strlen(str);
	if(str[j-2]==' ')
	{
		str[j-2]=str[j-1];
	    str[j-1]='\0';//add null to end of string
    }

//replace '®', from string with -1 instead. NOTE: ®  is actually two ascii characters.
	for (i=1;i<strlen(str);i++)
	//	if(str[i]>0x7f || str[i]<0)
		if(str[i]>0x92 || str[i]<0)
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

	//printf("post strip_space: %s len=%d\n",return_str,strlen(return_str));
	

	return(return_str);
 


} //strip space
//==========================================================================================================================
int insert_sql_table(const char * cert_num, const char * module_name,const char * standard, const char * status,const char * sunset,
					const char * validation, const char * security_level, const char * module_type, 
					const char * vendor_name, const char * lab_name, const char * fips_algorithms){
//This will take the incoming current_row and insert it into the Active SQL table with its data (where appropriate)
//Input:  all the columns names for the main sql table
//output: return (1) for error. return (0) for success. 


char *value;
char *value1;
char *value2;
char *value3;
char *value4;

//printf("delta1. Post_Entry cert_num=%s\n",cert_num);

PGresult *sql_result;
int         nFields;
int i,j;
char sql1 [SQL_MAX];
int k;
	


//zero out sql1 command string. Not sure why this is necessary. But will fail if I don't
CLR_SQL1_STR
	

	   	//build my sql  command string
strfcat(sql1," INSERT INTO \"CMVP_Active_Table\" ( \"Cert_Num\",\"Module_Name\", \"Standard\", \"Status\",\"Sunset_Date\", \"Validation_Date\", \"SL\",\"Module_Type\", \"Vendor_Name\",\"Lab_Name\", \"Last_Updated\",\"FIPS_Algorithms\")");
		
//the CMVP_Active_Table can use '®' in module_names and vendor_names. But CMVP_atsec_Only_MIP_Table uses '(R)'
//so, I'll replace both symbols (which mean the same thing) with a 'blank'


strfcat(sql1," VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s, (select CURRENT_DATE),%s) ",cert_num, module_name, standard, status, sunset, validation,  security_level, module_type, vendor_name,  lab_name,fips_algorithms);

//printf("alpha 1 insert: sql1=%s\n",sql1);

		sql_result = PQexec(conn, sql1); 

		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			
   			printf("\nError 173: SQL  Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}

return 0;
} //insert_sql_table

//===========================================================================================================

//===================================================================================================
void strfcat(char *src, char *fmt, ...){
//this is sprintf and strcat combined.
//strfcat(dst, "Where are %d %s %c\n", 5,"green wizards",'?');
//strfcat(dst, "%d:%d:%c\n", 4,13,'s');
//printf("charlie1\n");
    //char buf[2048];
    char buf[SQL_MAX];
//printf("charlie2\n");

    va_list args;

    va_start(args, fmt);
//printf("charlie3. buf=%d, fmt=%d, args=%d\n",strlen(buf),strlen(fmt),args);

    vsprintf(buf, fmt, args);
//printf("charlie4\n");

    va_end(args);

//printf("charlie5\n");

    strcat(src, buf);
//printf("charlie6\n");

} //strfcat
//===========================================================================================
const char * convert_date_format( char * inputDate){

//This will convert date format of 'm/d/y' to instead be 'mm/dd/yyyy'
//If date1 is already in that format, then it will just return date1
//I will also bullet proof this function by only allowing numbers or / as characters.
// to avoid sql injection attacks.
//input: a date in the format of m/d/yyyy or mm/dd/yyyy or m/dd/yyyy or mm/d/yyyy

 char  * return_str ;
char  * tempDate ;
char dummyTemp[12];
int i;

//printf("inputDate=%s. strlen=%d \n",inputDate,strlen(inputDate));

//kind of clumsy, but works. Ennumerate all possible combinations and generate new values.

if(inputDate[2]=='/' && inputDate[5]=='/')  // eg  '2/11/2021'
	{ 	
		dummyTemp[0]='\'';  //need single quote to make SQL happy
		dummyTemp[1]='0'; 
		dummyTemp[2]=inputDate[1];
		dummyTemp[3]='/';
		dummyTemp[4]=inputDate[3];
		dummyTemp[5]=inputDate[4];
		dummyTemp[6]='/';
		dummyTemp[7]=inputDate[6];
		dummyTemp[8]=inputDate[7];
		dummyTemp[9]=inputDate[8];
		dummyTemp[10]=inputDate[9];
		dummyTemp[11]='\'';  //need closing quote for SQL
		dummyTemp[12]='\0';//add null to end of string
	}
else if (inputDate[2]=='/' && inputDate[4]=='/')  // eg '2/7/2021'
{
		dummyTemp[0]='\'';  //need single quote to make SQL happy
		dummyTemp[1]='0'; 
		dummyTemp[2]=inputDate[1];
		dummyTemp[3]='/';
		dummyTemp[4]='0';
		dummyTemp[5]=inputDate[3];
		dummyTemp[6]='/';
		dummyTemp[7]=inputDate[5];
		dummyTemp[8]=inputDate[6];
		dummyTemp[9]=inputDate[7];
		dummyTemp[10]=inputDate[8];
		dummyTemp[11]='\'';  //need closing quote for SQL
		dummyTemp[12]='\0';//add null to end of string
}
else if (inputDate[3]=='/' && inputDate[5]=='/')  // eg '11/3/2021'
{
		dummyTemp[0]='\'';  //need single quote to make SQL happy
		dummyTemp[1]=inputDate[1]; 
		dummyTemp[2]=inputDate[2];
		dummyTemp[3]='/';
		dummyTemp[4]='0';
		dummyTemp[5]=inputDate[3];
		dummyTemp[6]='/';
		dummyTemp[7]=inputDate[6];
		dummyTemp[8]=inputDate[7];
		dummyTemp[9]=inputDate[8];
		dummyTemp[10]=inputDate[9];
		dummyTemp[11]='\'';  //need closing quote for SQL
		dummyTemp[12]='\0';//add null to end of string
}
else
	printf("no hit\n");

tempDate=dummyTemp;

//mitigate some attack surfaces here.

//check my input date lengths. 
if(strlen(inputDate)>12 || strlen(inputDate)<8)
	{printf("error 430: formated date is wrong size. strlen=%d: %s\n",strlen(inputDate),inputDate);
	 return_str ="'ERROR 369: date wrong size'";
	 return return_str;
	}

//make sure only numbers and / are used
for(i=0;i<strlen(inputDate);i++)  
{	
	//printf("%d=%c\n",i,inputDate[i]);
	if ((inputDate[i] >57 || inputDate[i] <47) && inputDate[i]!=39) // ascii values of 0-9, / and '
	{	
		printf("error 437: illegal character in date: %s\n",inputDate);
		return_str="'ERROR 437: invalid chars'";
		return return_str;
	}  //if
} //for



if(strlen(inputDate)==12)  //
	 return_str= inputDate;
else
	return_str= tempDate;

printf("convert_date_format: dateIn=%s len=%d. dateOut=%s len=%d\n",inputDate,strlen(inputDate),return_str,strlen(return_str));
return return_str;

}// convert date_format 
//===============================================================================================
int date1_lt_date2 (const char *date1, const char *date2){
//date1 less than date2
//Quick and dirty date compare.
//Assumes date format is 'mm/dd/yyyy'
//returns 1 (true), if date1 < date2. i.e. date1 is earlier than date2. 
//return 0 (false) otherwise (i.e. date2 >= date1),  
// NOTE: think of NULL = infinity. Then
//	 NULL  < mm/dd/yyyy  returns 0 (false)
//   mm/dd/yyyy < NULL returns 1 (true)  

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
 	printf("ERROR 309: should never get here\n");

    return (0);


	
}  //date1 lt date2


//====================================================================================================
long int parse_modules_from_single_html_file (char * file_name,byte* data,const long len){
//this will parse a single module from the HTML email file as received from the CMVP website
//It will then insert that module info  into the main Active SQL table
//intput: "data" is a byte stream where each character of the HTML file is a single index
//        len   is the total number of bytes in the HTML file
//		  
//output: returns 1 on error. returns 0 on success


PGresult *sql_result;
char sql1 [SQL_MAX];

int dim_value;
int i_algo;
int data_fips_count; //number of FIPS algorithms for this cert_num
int i,j,k;
int myX,myY,prevX=0;
int temp_myX, temp_myY, end_of_lab_sectionX;

int myCert;
char dummy1[VALUE_SIZE];
char dummy2[VALUE_SIZE];
char dummy3[VALUE_SIZE];
char dummy4[VALUE_SIZE];
char dummy5[VALUE_SIZE];
char dummy6[VALUE_SIZE];
char tempDate[VALUE_SIZE];

char dummy7[VALUE_SIZE];
char dummy7a[VALUE_SIZE];
char dummy8[VALUE_SIZE];
char dummy9[VALUE_SIZE];
char dummyA[VALUE_SIZE];
char dummyA1[VALUE_SIZE];

int myZstart,myZend;

const char * cert_num;
const char * module_name;
const char * standard;
const char * status;
const char * sunset;
const char * validation;
const char * security_level;
const char * module_type;
const char * vendor_name;
const char * lab_name;
const char * fips_algorithms;
const char * dummy_value_fips_algorithms;
const char * tempDateHoldingSpot;


bool secret_module_name=false;
bool secret_vendor_name=false;

//int num_of_modules=1;
int termination_value;
int historical_flag;
int revoked_flag;
int sunset_flag;
//int file_calc;

//make sure this HTML file has an actual certificate number and isn't a dummy html file.
// This should happen at the BASH shell level script, but different flavors of Linux (ubuntu, suse, rhel, etc), hanlde that differently.
// so, I'm doing that check here as well.
myX=str_find ("Page Not Found", data, len, 0); 
if(myX!=0) {printf("***** Skipping Invalid Certifcate Files\n"); return(-1); }


	for(i=0;i<VALUE_SIZE;i++) 	dummy1[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy2[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy3[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy4[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy5[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy6[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	tempDate[i]=0;	

	for(i=0;i<VALUE_SIZE;i++) 	dummy7[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy7a[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy8[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummy9[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummyA[i]=0;	
	for(i=0;i<VALUE_SIZE;i++) 	dummyA1[i]=0;
	

	//sunset_flag=str_find("Sunset ",data,len,0);  //see if this file has a sunset date. Many of the older certs did not.
	historical_flag=str_find ("Historical", data,len, 0); //see if this an "Historical" list module.
	revoked_flag=str_find("Revoked",data,len,0); //see if it has been revoked.
	
	//----------- Certificate Number ----------------------
	myX=str_find ("Certificate #", data,len, 0);  //returns file ptr postion 
	if(myX==0) {printf("***** Warning 308a: Certificate Tag Not found. Discarding File.(x=%d)\n",myX); return(-1); }
	myY=str_find ("<asp:Label", data,len, myX); 
	if(myY==0) {printf("***** Warning 308b: Certificate Tag Not found (y=%d)\n",myY); return(-1); }
	
	
	
	
	j=1;
	dummy1[0]='\'';
	for	(i=myX+13;i<myY;i++){  
		dummy1[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummy1[j]='\'';	
	for(i=j+1;i<VALUE_SIZE;i++)
		dummy1[i]=0;
	
	cert_num=strip_space(dummy1); //convert my char [255] to char *
	if DEBUG printf("Certificate Number: %s\n",cert_num);

	//printf("alpha1: cert_num=%s\n",cert_num);
	

	//----------- Get Module Name ----------------------
	myX=str_find ("module-name", data,len, 0); //myY+1);  //returns file ptr postion 
	if(myX==0) {
		printf("***** Warning 341a: Module Name Tag Not found (x=%d)\n",myX); 
		secret_module_name=true; //return(-1); 
		}
	
	if (secret_module_name){
		strfcat(dummy2,"'Undisclosed Name %s'",cert_num); //" -%s",cert_num);
		
		//module_name="Unknown %s"
		}
	else
		{
		myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
		myY=str_find ("</div>", data,len, myX+1); 
		if(myY==0) {printf("***** Error 341b: Module Name Tag Not found (y=%d)\n",myY); return(-1); }

		j=1;
		dummy2[0]='\'';
		for	(i=myX+2;i<myY;i++){  
			dummy2[j]=data[i];
			//printf("**%d: %c\n",i,data[i]);
			j++;}  
		dummy2[j]='\'';	
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy2[i]=0;
	} // unknown module name

	module_name=strip_space(dummy2); //convert my char [255] to char *
	if DEBUG printf("Module Name: %s\n",module_name);
	

	//--------------- Standard  140-2  or 140-3 --------------
	myX=str_find ("module-standard", data,len, myY+1);  //returns file ptr postion 
	if(myX==0) {printf("***** Error 377a: Standard Tag Not found (x=%d)\n",myX); return(-1); }
	myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
	
	myY=str_find ("</div>", data,len, myX); 
	
	
	j=1;
	dummy3[0]='\'';
	for	(i=myX+2;i<myY;i++){  
		dummy3[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummy3[j]='\'';	
	for(i=j+1;i<VALUE_SIZE;i++)
		dummy3[i]=0;
	

	standard=strip_space(dummy3); //convert my char [255] to char *
	if DEBUG printf("Standard: %s\n",standard);



	//--------------- Status ------------
	myX=str_find ("Status", data,len, 0); //myY+1);  //returns file ptr postion 
	if(myX==0) {printf("***** Error 414a: Status Tag Not found (x=%d)\n",myX); return(-1); }
	myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
	myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
	
	myY=str_find ("</div>", data,len, myX+1); 
	
	
	j=1;
	dummy4[0]='\'';
	for	(i=myX+1;i<myY;i++){  
		dummy4[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummy4[j]='\'';	
	for(i=j+1;i<VALUE_SIZE;i++)
		dummy4[i]=0;
	
	
	status=strip_space(dummy4); //convert my char [255] to char *
	if DEBUG printf("Status: %s\n",status);
	
	//printf("alpha4\n");

	
	//---------------Sunset_Date ------------------
	if(historical_flag || revoked_flag)
	{
		// all historic and revoked Sunset_Dates are changed to NULL when the module is retired. But, Null really complicates the logic so instead use '1901' for NULL
		sunset="'1901-01-01'::date";   
		//sunset="NULL";   

	}
	else
	{	//not historical
		myX=str_find ("Sunset ", data,len, 0); //myY+1);  //returns file ptr postion 
		if(myX==0) {printf("***** Error 451a: Sunset Date Tag Not found (x=%d)\n",myX); return(-1); }
		myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
		myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
		myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
		

		myY=str_find ("</div>", data,len, myX); 
	
		j=1;
		dummy5[0]='\'';
		for	(i=myX+1;i<myY;i++){  
			dummy5[j]=data[i];
			//printf("**%d: %c\n",i,data[i]);
			j++;}  
		dummy5[j]='\'';	
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy5[i]=0;
	
	
		sunset=strip_space(dummy5); //convert my char [255] to char *
		if DEBUG printf("Sunset: %s\n",sunset);
		
	
	}  //else if not Historical
	//printf("alpha5\n");
	
	
	//-------------- Validation Dates  OR  Validation History ----------------------
	//New Tag format. January 2022. CMVP changed their HTML formats from Validation Dates to Validation History.
	
	myX=str_find ("Validation History", data,len, 0); //myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0 && historical_flag==0) {printf("***** Error 490a: Validation History Tag Not found (x=%d)\n",myX); return(-1); }

	myX=str_find ("<tbody>", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myY=str_find ("</tbody>", data,len, myX+1); //end at the last html tag "</tbody>"
	
	
	myZstart=str_find("text-nowrap",data,len,myX+1);  //these two positions will bracket the date I want
	myZend=str_find("</td>",data,len,myZstart+1);
	
//printf("alpha6\n");
	j=0;


	dummy6[j++]='\'';  //start with a single quote to keep SQL happy

		
	//rf for (k=0;k<1;k++){	
	while (myZstart != 0 && j<1000) 
	{ //while loop


		myZstart=myZstart + 13;  //ADD 13 TO skip the HTML tags    text-nowrap">
	
		if (myZstart < myY && myZend<myY )
		{ //if myZstart < myY
		
			if(j>1)
				dummy6[j++]=';';  //put a semicolon between multiple dates

			for	(i=myZstart;i<myZend;i++)
			{ //for loop myZstart

				//if necessary, insert extra '0' to convert the date format from m/d/yyyy to mm/dd/yyyy
				if(i==myZstart+0 && data[myZstart+1]=='/')
					{ dummy6[j++]='0'; }
				if(i==myZstart+2 && data[myZstart+3]=='/')
					{ dummy6[j++]='0';}
				if(i==myZstart+3 && data[myZstart+4]=='/' && data[myZstart+2]=='/')
					{ dummy6[j++]='0';}	
				dummy6[j]=data[i];
				j++;
			}  //for loop myZstart  
			
					
			// move down to the next date (if there is one) which is delineated by "text-nowrap" & "</td>"
			myZstart=str_find("text-nowrap",data,len,myZstart+1);
			//if(myZstart==0)
			//	printf("********************* ERROR 800: myZstart=0\n");

			myZend=str_find("</td>",data,len,myZstart+1);

		} //if myZstart < myY
	} //while loop	

	//printf("alpha9\n");
	dummy6[j]='\'';	 //end with a single quote to keep SQL happy
	for(i=j+1;i<VALUE_SIZE;i++)  //zero out rest of dummy6
				dummy6[i]=0;


	validation=strip_space(dummy6); //convert my char [255] to char *
	

	if DEBUG printf("Validation: %s\n",validation);
	
	
	// ------------ Overall Security Level --------------

	myX=str_find ("Overall Level", data,len, 0);
	if(myX==0) {printf("***** Error 531: Overall Security Level Tag Not found (x=%d)\n",myX); return(-1); }
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myY=str_find ("</div>", data,len, myX+1);
	  
	
	j=1;
	dummy7[0]='\'';
	for	(i=myX+1;i<myY;i++){  
		dummy7[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummy7[j]='\'';	
	for(i=j+1;i<VALUE_SIZE;i++)
		dummy7[i]=0;
	

	security_level=strip_space(dummy7); //convert my char [255] to char *
	if DEBUG printf("Security Level: %s\n",security_level);
	

	// <span>Module Type</span>
	// ------------ Module TYpe --------------

	myX=str_find ("Module Type", data,len, 0);//myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0) {printf("***** Error 678: Module Type Tag Not found (x=%d)\n",myX); return(-1); }
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myY=str_find ("</div>", data,len, myX+1) +1;
	  

	j=1;
	dummy7a[0]='\'';
	for	(i=myX+1;i<myY;i++){  
		dummy7a[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummy7[j]='\'';	
	for(i=j+1;i<VALUE_SIZE;i++)
		dummy7a[i]=0;

	module_type=strip_space(dummy7a); //convert my char [255] to char *
	if DEBUG printf("Module Type: %s\n",module_type);


	//-------------- Vendor Name ------------

	int s,r;
	myX=str_find ("Vendor</h4>", data,len, 0); //myY+1);  //start at start of file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0) {
		printf("***** Error 570: Vendor name Tag Not found (x=%d)\n",myX); 
		secret_vendor_name=true;//return(-1); 
		}
	
	//if(secret_vendor_name){
	if(secret_vendor_name){
		strfcat(dummy8,"'Undisclosed Vendor %s'",cert_num);//, cert_num);

	} 
	else 
	{ //unkown vendor

		
		s=str_find ("href=", data,len, myX+1);
		r=str_find(">",data,len,myX+1);
		r=str_find(">",data,len,r+1);
		r=str_find(">",data,len,r+1);
		r=str_find(">",data,len,r+1);

		if (r<s)
		{  //there is not a http: associated with this vendor
			printf("No http associate with this vendor\n");
			myX=str_find (">", data,len, myX+1);  //
			myX=str_find (">", data,len, myX+1);  //
			myX=str_find (">", data,len, myX+1);  //
			myY=str_find ("<b", data,len, myX);

		}
		else //these is an HTTP
		{
		//	printf("yes http\n");
			myX=str_find (">", data,len, myX+1);  //
			myX=str_find (">", data,len, myX+1);  //
			myX=str_find (">", data,len, myX+1);  //
			myX=str_find (">", data,len, myX+1);  //
			myY=str_find ("</a>", data,len, myX);

		}

		j=1;
		dummy8[0]='\'';
		for	(i=myX+1;i<myY;i++){  
			dummy8[j]=data[i];
			//printf("**%d: %c\n",i,data[i]);
			j++;}  
		dummy8[j]='\'';		
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy8[i]=0;
	
	} //unknown vendor
	

	if(strlen(dummy8)==1){
		strfcat(dummy8,"'Undisclosed Vendor %s'",cert_num);//, cert_num);

	} 
	
	vendor_name=strip_space(dummy8); //convert my char [255] to char *
	if DEBUG printf("Vendor: %s\n",vendor_name);

	

	//if DEBUG printf("Validation_4: %s\n",validation);

//------------- FIPS Alogithms ------------
	
	myX=str_find ("data-fips-count=\"", data,len, 0);  //start at top of the file
	if(myX==0) {
		//printf("***** Warning 790: FIPS Algorithms Tag Not found (x=%d, cert_num=%s) \n",myX,cert_num);  
		fips_algorithms="NULL";
	}
	else
	{  //else there is a data-fips-count tag which means there are FIPS algorithms associated with this cert_num	
		
		
		myY=str_find ("\"", data,len, myX+17);  //end at </tbody>
		j=1;
		dummyA[0]='\'';
		for	(i=myX+17;i<myY;i++){  
			dummyA[j]=data[i];
			//printf("**%d: %c\n",i,data[i]);
			j++;}  
		dummyA[j]='\'';		
		for(i=j+1;i<VALUE_SIZE;i++)
			dummyA[i]=0;
		
		dummy_value_fips_algorithms=strip_space(dummyA); //convert my char [] to char *
	
		//data-fips-count is the number of FIPS algorithms associated with this cert_num
		sscanf(dummy_value_fips_algorithms, "'%d'", &data_fips_count);  		
		
		myX=str_find ("<td class=\"text-nowrap\">", data,len, 0);  //start at top of the file and begin scanning for algorithms which will start with -nowrwap tag
		if(myX==0) {printf("***** Warning 859: text-nowrap Tag Not found (x=%d, cert_num=%s) \n",myX,cert_num); dummy_value_fips_algorithms="NULL"; }
		
			

		for(i=0;i<VALUE_SIZE;i++) 	dummyA1[i]=0;
		dummyA1[0]='\'';  //put a single quote in for the SQL insert to work. add closing quote at end.
			
		for(i_algo=0;i_algo<data_fips_count;i_algo++)
		{ //get the FIPS algorithms one by one
			myY=str_find("</td>",data,len,myX);
			//printf("amyX=%d, amyY=%d\n",myX,myY);
			
			for(i=0;i<VALUE_SIZE;i++) 	dummyA[i]=0;

			j=0;
			for	(i=myX+24;i<myY;i++){  
				dummyA[j]=data[i];
				//printf("**%d: %c\n",i,data[i]);
				j++;}  
			dummyA[j]=';';	
			for(i=j+1;i<VALUE_SIZE;i++)
				dummyA[i]=0;
						
			strfcat(dummyA1,"%s",dummyA);
			

			myX=str_find ("<td class=\"text-nowrap\">", data,len, myX+24);  //start from last spot &  begin scanning for algorithms which will start with -nowrwap tag
			//if(myX==0) {printf("***** Warning 886: text-nowrap Tag Not found (x=%d, cert_num=%s) \n",myX,cert_num); dummy_value_fips_algorithms="NULL"; }

		}  //get the FIPS algorithms one by one
		
		//add closing quote to string since SQL insert will requie it.
		for(i=0;i<VALUE_SIZE;i++)
			if(dummyA1[i]==0) 
				{ dummyA1[i]='\''; break; }

		fips_algorithms=dummyA1;

		
		
	} // /else there is a data-fips-count tag which means there are FIPS algorithms associated with this cert_num	
	
	if DEBUG printf("FIPS_Algorithms: %s\n",fips_algorithms);



	//if DEBUG printf("Validation_5: %s\n",validation);

	//------------- Lab Name -------------
	//Old header.
	//myX=str_find ("Lab</h4>", data,len, 0); //myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)


	//New header. Changed in January 2022 when CMVP changed their format of the HTML file for "Lab" name
	myX=str_find ("Lab</th>", data,len, 0); //myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0) {printf("***** Error 637a: Lab name Tag Not found (x=%d)\n",myX); return(-1); }

	//end_of_lab_section=str_find("</tbody>",data,len,myX);
	//if(end_of_lab_sectionX==0) {printf("***** Error 637b: Lab end-of-section Tag Not found (x=%d)\n",end_of_lab_sectionX); return(-1); }

	myX=str_find("<td>",data,len,myX+1);  //get the start of the lab/validation_date block
	myX=str_find("<td>",data,len,myX+1) ;  //get the start of the lab/validation_date block
	
	temp_myX=myX;
	//loop until I get the last (i.e. most recent) Lab Name
	while(temp_myX!=0){  
		temp_myX=str_find("<td>",data,len,temp_myX+1);  //get the next lab name starting tab until I run out HTML file
	//	temp_myX=str_find("<td>",data,len,temp_myX+1);  //get the next lab name starting tab until I run out HTML file
		if(temp_myX!=0  && data[temp_myX+4]!='<' && data[temp_myX+4]!='U' && data[temp_myX+5]!='p'&& data[temp_myX+6]!='d' && data[temp_myX+7]!='a' && data[temp_myX+8]!='t' && data[temp_myX+9]!='e')
			myX=temp_myX;
	}

	myX=myX+3;
	myY=str_find("</td>",data,len,myX); //get the end of the lab/validation_date block

	  
	j=1;
	dummy9[0]='\'';
	for	(i=myX+1;i<myY;i++){  
		dummy9[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummy9[j]='\'';		
	for(i=j+1;i<VALUE_SIZE;i++)
		dummy9[i]=0;


	lab_name=strip_space(dummy9); //convert my char [255] to char *
	//printf("LAB_NAME: cert_num=%s, myX=%d, myY=%d\n",cert_num,myX,myY);
	if DEBUG printf("Lab: %s\n",lab_name);



	//if DEBUG printf("Validation_6: %s\n",validation);

	//----------- Insert Row Into SQL Table ----------------------------------




//double check the length of all these strings. 

	if(strlen(cert_num)>VALUE_SIZE)
		{printf("warning 685a: cert_num too long=%s\n",cert_num);cert_num="NULL";}

	if(strlen(module_name)>VALUE_SIZE)
	{
			
		for	(i=0;i<VALUE_SIZE-1;i++)  //truncate the string
			dummy2[i]=module_name[i];
			
		dummy2[VALUE_SIZE-1]='\'';   //need to add single quote ' so the SQL insert has correct syntac.
		dummy2[VALUE_SIZE]=0;	// add 0 to terminate the string
		for(i=VALUE_SIZE+1;i<VALUE_SIZE;i++)
			dummy2[i]=0;
		
		module_name=dummy2;
		printf("warning 685b: module_name truncated since too long =%s\n",module_name);

	}

	if(strlen(standard)>VALUE_SIZE)
		{printf("warning 685c: standard too long=%s\n",standard);standard="NULL";}

	if(strlen(status)>VALUE_SIZE)
		{printf("warning 685d: status too long=%s\n",status);status="NULL";}

	if(strlen(sunset)>VALUE_SIZE)
		{printf("warning 685e: sunset too long=%s\n",sunset);sunset="NULL";}

	if(strlen(validation)>VALUE_SIZE)
		{printf("warning 685f: validation too long=%s\n",validation);validation="NULL";}

	if(strlen(security_level)>VALUE_SIZE)
		{printf("warning 685g: security_level too long=%s\n",security_level);security_level="NULL";}

	if(strlen(module_type)>VALUE_SIZE)
		{printf("warning 685g2: security_level too long=%s\n",module_type);module_type="NULL";}

	if(strlen(vendor_name)>VALUE_SIZE)
		{printf("warning 685h: vendor_name too long=%s\n",vendor_name);vendor_name="NULL";}

	if(strlen(lab_name)>VALUE_SIZE)
		{printf("warning 685i: lab_nam too long=%s\n",lab_name);lab_name="NULL";}
	
	if(strlen(fips_algorithms)>VALUE_SIZE)
		{printf("warning 685j: fips_algorithms too long=%s\n",fips_algorithms);fips_algorithms="NULL";}
	

	

	//if DEBUG printf("Validation_7: %s\n",validation);
	

	//here's the actual table insert
 	insert_sql_table(cert_num,module_name,standard,status,sunset,validation,security_level,module_type,vendor_name,lab_name,fips_algorithms);

 		
	//int k; //sql result message


	
	

	
///Next, four step process: Need to merge the Sunset_date and Validation_Date  on modules with same Cert_Num. Then I can update the Status based on the Sunset_Date.
//       Then I can delete all duplicate rows.
	

//----------------		
CLR_SQL1_STR

strfcat(sql1," update \"CMVP_Active_Table\" as t1 set \"Sunset_Date\" =    t2.max_sunset_date::date ");
strfcat(sql1," from  (select \"Cert_Num\", max(\"Sunset_Date\") as max_sunset_date from \"CMVP_Active_Table\" group by \"Cert_Num\") as t2 ");
strfcat(sql1," where t1.\"Cert_Num\"=t2.\"Cert_Num\"; ");

strfcat(sql1," update \"CMVP_Active_Table\" as t1 set \"Validation_Date\" = t2.max_validation_date ");
strfcat(sql1," from (select \"Cert_Num\",max(\"Validation_Date\") as max_validation_date from \"CMVP_Active_Table\"  ");
strfcat(sql1," 	  group by \"Cert_Num\" order by \"Cert_Num\" desc) as t2 ");
strfcat(sql1," where t1.\"Cert_Num\"=t2.\"Cert_Num\"; ");

strfcat(sql1," update \"CMVP_Active_Table\" set \"Status\" = case  ");
strfcat(sql1," 	 when \"Status\"='Revoked' then 'Revoked'  ");
strfcat(sql1," 	 when (select CURRENT_DATE)::date  <= \"Sunset_Date\"::date   then 'Active' else 'Historical'  end;  ");

strfcat(sql1," delete from \"CMVP_Active_Table\" t1 using \"CMVP_Active_Table\" t2  ");
strfcat(sql1," 	 WHERE  t1.\"Row_ID\" < t2.\"Row_ID\"  AND t1.\"Cert_Num\" = t2.\"Cert_Num\"  ");
strfcat(sql1," 	 and t1.\"Sunset_Date\"=t2.\"Sunset_Date\" and t1.\"Validation_Date\"=t2.\"Validation_Date\" ");
strfcat(sql1," 	 and t1.\"Status\"=t2.\"Status\"; ");

//printf("foxtrot sql1=%s\n",sql1);

sql_result = PQexec(conn, sql1);  
k=PQresultStatus(sql_result);

if (k != PGRES_TUPLES_OK and k!= PGRES_COMMAND_OK) 
{//check status
   //printf("SQL Result Value=%d\n. SQL1=%s",k,sql1);
  
  switch (k) {
	case PGRES_EMPTY_QUERY: printf(" PGRES_EMPTY_QUERY:The string sent to the server was empty.\n"); break;
	case PGRES_COMMAND_OK: printf("PGRES_COMMAND_OK:Successful completion of a command returning no data. \n"); break;
	case PGRES_TUPLES_OK: printf("PGRES_TUPLES_OK:Successful completion of a command returning data (such as a SELECT or SHOW). \n"); break;
	case PGRES_COPY_OUT: printf(" PGRES_COPY_OUT:Copy Out (from server) data transfer started.\n"); break;
	case PGRES_COPY_IN: printf(" PGRES_COPY_IN:Copy In (to server) data transfer started.\n"); break;
	case PGRES_BAD_RESPONSE: printf("PGRES_BAD_RESPONSE:The server's response was not understood. \n"); PQclear(sql_result); return(1);break;
	case PGRES_NONFATAL_ERROR: printf("PGRES_NONFATAL_ERROR:A nonfatal error (a notice or warning) occurred. \n"); PQclear(sql_result); return(1);break;
	case PGRES_FATAL_ERROR: printf("PGRES_FATAL_ERROR: A fatal error 1071 occurred.\nSQL= %s\n",sql1); PQclear(sql_result); return(1);break;
	default: printf("Unknown PQresultStatus=%s\n",k); break;
	}

	 printf("\nError 576: SQL deleting dup rows failed: sql1=%s\n",sql1);
   return(-1);
} //check status

// Final 5th step: make a copy of the sunset_date in the CMVP_Sunset_Table since CMVP will erase that date from their website when a module goes Historic.
CLR_SQL1_STR

strfcat(sql1," INSERT INTO \"CMVP_Permanent_Sunset_Table\" ( \"Cert_Num\",\"Sunset_Date\", \"Validation_Date\") ");
strfcat(sql1," VALUES (%s,%s,%s )",cert_num,  sunset, validation  );

//printf(" permanent sunset sql=%s\n",sql1);

sql_result = PQexec(conn, sql1);  
k=PQresultStatus(sql_result);

if (k != PGRES_TUPLES_OK and k!= PGRES_COMMAND_OK) 
{//check status
   //printf("SQL Result Value=%d\n. SQL1=%s",k,sql1);
   switch (k) {
	case PGRES_EMPTY_QUERY: printf(" PGRES_EMPTY_QUERY:The string sent to the server was empty.\n"); break;
	case PGRES_COMMAND_OK: printf("PGRES_COMMAND_OK:Successful completion of a command returning no data. \n"); break;
	case PGRES_TUPLES_OK: printf("PGRES_TUPLES_OK:Successful completion of a command returning data (such as a SELECT or SHOW). \n"); break;
	case PGRES_COPY_OUT: printf(" PGRES_COPY_OUT:Copy Out (from server) data transfer started.\n"); break;
	case PGRES_COPY_IN: printf(" PGRES_COPY_IN:Copy In (to server) data transfer started.\n"); break;
	case PGRES_BAD_RESPONSE: printf("PGRES_BAD_RESPONSE:The server's response was not understood. \n"); PQclear(sql_result); return(1);break;
	case PGRES_NONFATAL_ERROR: printf("PGRES_NONFATAL_ERROR:A nonfatal error (a notice or warning) occurred. \n"); PQclear(sql_result); return(1);break;
	case PGRES_FATAL_ERROR: printf("PGRES_FATAL_ERROR: A fatal error 1101 occurred.\nSQL = %s\n",sql1); PQclear(sql_result); return(1);break;
	default: printf("Unknown PQresultStatus=%s\n",k); break;
	}

	 printf("\nError 921: SQL deleting dup rows failed: sql1=%s\n",sql1);
   return(-1);
} //check status

return 0;

} //parse_modules_from_single_html_file


//============================================================
int main (int argc, char* argv[]) {

	const char *Table_Name="CMVP_Active_Table";
	char *file_path;
	char *file_num;

	long int file_pos=0;
	
	//char sql1 [SQL_MAX];
	//PGresult *sql_result;
	data_t data;
	int i;
	int myX;

	

//rgf();

//return(0);

//printf("alpha1\n");

	
	switch (PROD) {
		case 2:  			//local VM machine
			conn = PQconnectdb("host=localhost user=postgres password=postgres dbname=postgres ");
			break;
		case 1: 			//intel intranet production
	  		conn = PQconnectdb("host=postgres5456-lb-fm-in.dbaas.intel.com user=lhi_prod_so password=icDuf94Gae dbname=lhi_prod  port=5433 ");
			break;
		case 0: //Intel intranet pre-production
			conn = PQconnectdb("host=postgres5596-lb-fm-in.dbaas.intel.com user=lhi_pre_prod_so password=icDuf94Gae dbname=lhi_pre_prod  ");
		 	break;
		default: 
			printf("ERROR Unknown PROD=%d\n",PROD); break;
	}

	


	
    if (PQstatus(conn) == CONNECTION_OK) {

//printf("alpha2\n");
	      	

		if(argc != 2) { 
			printf("*** Error 345: Missing Input File Name.\n");
			
		} 

		// get input filename
		file_path = argv[1];
	
		//------ do file stuff here
	//	printf("\n\n********************************************************************************\n\n");
		//printf("Opening file: '%s'\n", file_path);
		if(!read_file(file_path, &data))
				printf("*** Error 356: Error reading file '%s'.\n",file_path);
			
		

	//	printf("alpha3\n");
					
		parse_modules_from_single_html_file(file_path, data.rawsymbols,  data.len);
		
//	printf("alpha4\n");

    		//printf("alphaC\n");
	} //connection ok
	else
		printf("PostgreSQL connection error\n");

	PQfinish(conn);
//printf("alpha5\n");


	return(0);


} //main