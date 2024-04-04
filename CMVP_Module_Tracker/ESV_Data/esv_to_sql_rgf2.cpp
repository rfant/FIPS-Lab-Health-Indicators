#include <stdio.h>
#include <//usr/include/postgresql/libpq-fe.h>  //ubuntu

#include <iostream>
#include <fstream>
#include <string>
#include "utils.h"
#include <unistd.h>
#include <sstream>
#include <stdarg.h>  //ubuntu
#include "../dev_or_prod_rgf2.h"

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
//int date1_lt_date2 (const char *, const char *);

#include <openssl/aes.h>

AES_KEY aesKey_;

unsigned char decryptedPW[16];

//===============================================================
void strfcat(char *src, char *fmt, ...){
//this is sprintf and strcat combined.
//strfcat(dst, "Where are %d %s %c\n", 5,"green wizards",'?');
//strfcat(dst, "%d:%d:%c\n", 4,13,'s');

    //char buf[2048];


    char buf[SQL_MAX];
    va_list args;

    va_start(args, fmt);

//   vsprintf(buf, fmt, args);
    vsnprintf(buf,sizeof buf, fmt,args);

    va_end(args);

    //strcat(src, buf);
    strncat(src,buf,sizeof buf);

}//strfcat

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
int insert_sql_table (const char * esv_cert_num,const char *implementation_name,const char *standard,const char *description,const char *version,const char *noise_source,const char *reuse_status,
	const char * operational_environment,const char * cavp_certs, const char * sample_size,const char *vendor_name,const char *validation_date,const char *lab_name){

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
strfcat(sql1," INSERT INTO \"CMVP_ESV_Table\" ( \"ESV_Cert_Num\",\"Implementation_Name\", \"Standard\", \"Description\",\"Version\", \"Noise_Source\", \"Reuse_Status\",\"OE\", \"CAVP_Certs\",\"Sample_Size\", \"Vendor_Name\",\"Validation_Date\",\"Lab_Name\")");
		

strfcat(sql1," VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s, %s) ",esv_cert_num, implementation_name, standard, description, version, noise_source, reuse_status, operational_environment, cavp_certs, sample_size,vendor_name,validation_date,lab_name);

printf("alpha 3 insert: sql1=%s\n",sql1);

		sql_result = PQexec(conn, sql1); 

		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			
   			printf("\nError 173: SQL  Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}

return 0;
} //insert_sql_table

//===========================================================================================================


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
/*int date1_lt_date2 (const char *date1, const char *date2){
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

*/
//====================================================================================================
long int parse_esv_cert_from_single_html_file (char * file_name,byte* data,const long len){
//this will parse a single module from the HTML file as received from the CMVP ESV website
//It will then insert that module info  into the main ESV SQL table
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
char dummyB[VALUE_SIZE];
char dummyC[VALUE_SIZE];
char dummyD[VALUE_SIZE];
char dummyE[VALUE_SIZE];
char dummyF[VALUE_SIZE];



int myZstart,myZend;
int myZ;

const char * esv_cert_num;

const char * implementation_name;
const char * standard;
const char * description;
const char * version;
const char * noise_source;
const char * reuse_status;
const char * operational_environment;
const char * vendor_name;
const char * lab_name;
const char * cavp_certs;

const char * sample_size;
const char * validation_date;



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
myX=str_find ("Certificate Not Found", data, len, 0); 
if(myX!=0) {printf("***** Skipping Invalid ESV Certifcate Files\n"); return(-1); }


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

	for(i=0;i<VALUE_SIZE;i++) 	dummyB[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummyC[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummyD[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummyE[i]=0;
	for(i=0;i<VALUE_SIZE;i++) 	dummyF[i]=0;
	


	//sunset_flag=str_find("Sunset ",data,len,0);  //see if this file has a sunset date. Many of the older certs did not.
	//historical_flag=str_find ("Historical", data,len, 0); //see if this an "Historical" list module.
	//revoked_flag=str_find("Revoked",data,len,0); //see if it has been revoked.
	
	//----------- ESV Certificate Number ----------------------
	myX=str_find ("Entropy Certificate #", data,len, 0);  //returns file ptr postion starting a "0"
	if(myX==0) {printf("***** Warning 308a: ESV Certificate Start Tag Not found. Discarding File.(x=%d)\n",myX); return(-1); }


	myY=str_find ("</h3>", data,len, myX); 
	if(myY==0) {printf("***** Warning 308b: ESV Certificate End Tag Not found (y=%d)\n",myY); return(-1); }
	
	
	
	
	j=1;
	dummy1[0]='\'';
	for	(i=myX+21;i<myY;i++){  
		dummy1[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummy1[j]='\'';	
	for(i=j+1;i<VALUE_SIZE;i++)
		dummy1[i]=0;
	
	esv_cert_num=strip_space(dummy1); //convert my char [255] to char *
	if DEBUG printf("ESV Certificate Number: %s\n",esv_cert_num);

	//printf("alpha1: cert_num=%s\n",cert_num);
	

	//----------- Implementation Name ----------------------
	myX=str_find ("id=\"implementation-name\"", data,len, 0); //myY+1);  //returns file ptr postion 
	if(myX==0) {printf("***** Warning 341a: Implementation Name Tag Not found (x=%d)\n",myX); 	}
	
	myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
	myY=str_find ("</div>", data,len, myX+1); 
	if(myY==0) {printf("***** Error 341b: Implementation Name End Tag Not found (y=%d)\n",myY); return(-1); }

	j=1;
	dummy2[0]='\'';
	for	(i=myX+2;i<myY;i++){  
		dummy2[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummy2[j]='\'';	
	for(i=j+1;i<VALUE_SIZE;i++)
		dummy2[i]=0;

	implementation_name=strip_space(dummy2); //convert my char [255] to char *
	if DEBUG printf("Implementation Name: %s\n",implementation_name);
	

	//--------------- Standard  (sp80090-B for now) --------------
	myX=str_find ("id=\"standard\"", data,len, myY+1);  //returns file ptr postion 
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



	//--------------- Description ------------
	myX=str_find ("<span>Description", data,len, 0); //myY+1);  //returns file ptr postion 
	if(myX==0) {printf("***** Error 414a: Description Tag Not found (x=%d)\n",myX); return(-1); }
	myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
	myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
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
	
	
	description=strip_space(dummy4); //convert my char [255] to char *
	if DEBUG printf("Description: %s\n",description);
	
	//printf("alpha4\n");

	
	//---------------Version ------------------
	myX=str_find ("<span>Version</span", data,len, 0); //myY+1);  //returns file ptr postion 
	if(myX==0) {printf("***** Error 451a: Sunset Date Tag Not found (x=%d)\n",myX); return(-1); }
	myX=str_find (">", data,len, myX+1);  //returns file ptr postion 
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


	version=strip_space(dummy5); //convert my char [255] to char *
	if DEBUG printf("Version: %s\n",version);
	
	
	//printf("alpha5\n");
	
	
	
	
	// ------------ Noise Source Classification --------------

	myX=str_find ("Noise Source Classification</span", data,len, 0);
	if(myX==0) {printf("***** Error 531: Noise Source Tag Not found (x=%d)\n",myX); return(-1); }
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myY=str_find ("</div>", data,len, myX+1);
	  
	
	j=1;
	dummy6[0]='\'';
	for	(i=myX+1;i<myY;i++){  
		dummy6[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummy6[j]='\'';	
	for(i=j+1;i<VALUE_SIZE;i++)
		dummy6[i]=0;
	

	noise_source=strip_space(dummy6); //convert my char [255] to char *
	if DEBUG printf("Noise Source: %s\n",noise_source);
	


// ------------ Reuse Status --------------

	myX=str_find ("Reuse Status</span", data,len, 0);//myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0) {printf("***** Error 678: Reuse Status Tag Not found (x=%d)\n",myX); return(-1); }
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myY=str_find ("</div>", data,len, myX+1) ;
	  
	
	j=1;
	dummy7[0]='\'';
	for	(i=myX+1;i<myY;i++){  
		dummy7[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummy7[j]='\'';	
	for(i=j+1;i<VALUE_SIZE;i++)
		dummy7[i]=0;
	

	reuse_status=strip_space(dummy7); //convert my char [255] to char *
	if DEBUG printf("Reuse Status: %s\n",reuse_status);


// ------------ OE (Operational Environment)--------------

	myX=str_find ("Operating Environments</th>", data,len, 0);//myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0) {printf("***** Error 678: OE Tag Not found (x=%d)\n",myX); return(-1); }
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
 	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find (">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	


	myY=str_find ("</ul>", data,len, myX+1) -11;
	myZ=str_find (">Vendor<",data,len,myX+1);
	if(myY==0 || (myZ<myY))
	{
		printf ("********** Warning 833: OE: tags not found (x=%d, y=%d,z=%d)\n",myX,myY,myZ);
		operational_environment="NULL";

	}	
	else
	{	myX=myX-4; //rgf  need to include the first bullet.


		j=1;
		dummy8[0]='\'';
		for	(i=myX+1;i<myY;i++){  
			dummy8[j]=data[i];
			//printf("**%d: %c\n",i,data[i]);
			j++;}  
		dummy8[j]='\'';	
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy8[i]=0;
		

		operational_environment=strip_space(dummy8); //convert my char [255] to char *
	}	
	
	if DEBUG printf("OE: %s\n",operational_environment);
	


// ------------ CAVP Certs--------------  DON"T DO CAVP AT ALL. WAY TOO MESSY AND INCONSISENT FORMAT FROM US GOV WEBSITE

	myX=str_find ("Vetted Conditioning Component CAVP Certificates</th>", data,len, 0);//myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0) {printf("***** Error 678: CAVP Tag Not found (x=%d)\n",myX); return(-1); }

	//myX=str_find ("</ul>", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	//myX=str_find ("<li>", data,len, myX+1)+3;  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	
	myX=str_find ("<ul class=\"list-left15pxPadding\">", data,len, myX+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myX=str_find ("<ul class=\"list-left15pxPadding\">", data,len, myX+1) +50;  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	
	

		
	
	myY=str_find ("</ul>", data,len, myX+1) ;
	myZ=str_find (">Vendor<",data,len,myX+1);


	if(myY==0 || (myZ<myY))
	{ 
		printf("***** Warning 678: CAVP ENDING Tag Not found (x=%d)\n",myY); 
		cavp_certs="NULL";
	}
	else	
	{

		j=1;
		dummy9[0]='\'';
		for	(i=myX+1;i<myY;i++){  
			dummy9[j]=data[i];
			//printf("**%d: %c\n",i,data[i]);
			j++;}  
		dummy9[j]='\'';	
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy9[i]=0;
		

		cavp_certs=strip_space(dummy9); //convert my char [255] to char *
		//if DEBUG printf("CAVP Certs: %s\n",cavp_certs);
	}
	
	

// ------------ sample size--------------. Apparentely. Sample Size is NOT a required field in the ESV cert (e.g. E37)


	myX=str_find ("Entropy Per Sample:", data,len, 0);//myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0) 
	{
		printf("***** Warning 678: Sample Size Tag Not found (x=%d)\n",myX); 
		sample_size="NULL";
	}
	else 
	{
		//myX+= 18;

		myY=str_find ("</td>", data,len, myX+1) ;
		
		j=1;
		dummyA[0]='\'';
		for	(i=myX+1;i<myY;i++){  
			dummyA[j]=data[i];
			//printf("**%d: %c\n",i,data[i]);
			j++;}  
		dummyA[j]='\'';	
		for(i=j+1;i<VALUE_SIZE;i++)
			dummyA[i]=0;
		

		sample_size=strip_space(dummyA); //convert my char [255] to char *
	}	
		if DEBUG printf("Sample Size: %s\n",sample_size);
	



	//-------------- Vendor Name ------------

	
	myX=str_find (">Vendor<", data,len, 0); //myY+1);  //start at start of file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0) {printf("***** Error 570: Vendor name Tag Not found (x=%d)\n",myX); return(-1);}
		
	
		
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myY=str_find ("</a>", data,len, myX);


	j=1;
	dummyB[0]='\'';
	for	(i=myX+1;i<myY;i++){  
		dummyB[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummyB[j]='\'';		
	for(i=j+1;i<VALUE_SIZE;i++)
		dummyB[i]=0;
	

	vendor_name=strip_space(dummyB); //convert my char [255] to char *
	if DEBUG printf("Vendor: %s\n",vendor_name);

	//-------------- Validation Date ------------

	
	myX=str_find ("<th>Date</th>", data,len, 0); //myY+1);  //start at start of file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0) {printf("***** Error 570: Validation Date Tag Not found (x=%d)\n",myX);  return(-1); }
		
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //

	myY=str_find ("</td>", data,len, myX);


	j=1;
	dummyC[0]='\'';
	for	(i=myX+1;i<myY;i++){  
		dummyC[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummyC[j]='\'';		
	for(i=j+1;i<VALUE_SIZE;i++)
		dummyC[i]=0;
	

	validation_date=strip_space(dummyC); //convert my char [255] to char *
	if DEBUG printf("Validation Date: %s\n",validation_date);

	
	


	//-------------- Lab Name ------------

	
	myX=str_find ("<th>Lab</th>", data,len, 0); //myY+1);  //start at start of file position myY+1 (have to inc by 1 to avoid repeat)
	if(myX==0) { printf("***** Error 570: Lab Name Tag Not found (x=%d)\n",myX); return(-1); }
		
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //
	myX=str_find (">", data,len, myX+1);  //

	myY=str_find ("</td>", data,len, myX);


	j=1;
	dummyD[0]='\'';
	for	(i=myX+1;i<myY;i++){  
		dummyD[j]=data[i];
		//printf("**%d: %c\n",i,data[i]);
		j++;}  
	dummyD[j]='\'';		
	for(i=j+1;i<VALUE_SIZE;i++)
		dummyD[i]=0;
	

	lab_name=strip_space(dummyD); //convert my char [255] to char *
	if DEBUG printf("Lab: %s\n",lab_name);

	

	//----------- Insert Row Into SQL Table ----------------------------------




//double check the length of all these strings. 

	if(strlen(esv_cert_num)>VALUE_SIZE)
		{printf("warning 685a: esv_cert_num too long=%s\n",esv_cert_num);esv_cert_num="NULL";}

	if(strlen(implementation_name)>VALUE_SIZE)
	{
			
		for	(i=0;i<VALUE_SIZE-1;i++)  //truncate the string
			dummy2[i]=implementation_name[i];
			
		dummy2[VALUE_SIZE-1]='\'';   //need to add single quote ' so the SQL insert has correct syntac.
		dummy2[VALUE_SIZE]=0;	// add 0 to terminate the string
		for(i=VALUE_SIZE+1;i<VALUE_SIZE;i++)
			dummy2[i]=0;
		
		implementation_name=dummy2;
		printf("warning 685b: implementation_name truncated since too long =%s\n",implementation_name);

	}

	if(strlen(standard)>VALUE_SIZE)
		{printf("warning 685c: standard too long=%s\n",standard);standard="NULL";}

	if(strlen(description)>VALUE_SIZE)
	{
			
		for	(i=0;i<VALUE_SIZE-1;i++)  //truncate the string
			dummy2[i]=description[i];
			
		dummy2[VALUE_SIZE-1]='\'';   //need to add single quote ' so the SQL insert has correct syntac.
		dummy2[VALUE_SIZE]=0;	// add 0 to terminate the string
		for(i=VALUE_SIZE+1;i<VALUE_SIZE;i++)
			dummy2[i]=0;
		
		description=dummy2;
		printf("warning 685b: Description truncated since too long =%s\n",description);

	}

//,,sample_size,


	if(strlen(version)>VALUE_SIZE)
		{printf("warning 685d: version too long=%s\n",version);version="NULL";}

	if(strlen(noise_source)>VALUE_SIZE)
		{printf("warning 685e: noise source too long=%s\n",noise_source);noise_source="NULL";}

	if(strlen(validation_date)>VALUE_SIZE)
		{printf("warning 685f: validation date too long=%s\n",validation_date);validation_date="NULL";}

	if(strlen(reuse_status)>VALUE_SIZE)
		{printf("warning 685g: Reuse Status too long=%s\n",reuse_status);reuse_status="NULL";}

	if(strlen(operational_environment)>VALUE_SIZE)
		{printf("warning 685g2: OE too long=%s\n",operational_environment);operational_environment="NULL";}

	if(strlen(vendor_name)>VALUE_SIZE)
		{printf("warning 685h: vendor_name too long=%s\n",vendor_name);vendor_name="NULL";}

	if(strlen(lab_name)>VALUE_SIZE)
		{printf("warning 685i: lab_nam too long=%s\n",lab_name);lab_name="NULL";}
	
	//if(strlen(cavp_certs)>VALUE_SIZE)
	//	{printf("warning 685j: cavp_certs too long=%s\n",cavp_certs);cavp_certs="NULL";}
	
	if(strlen(sample_size)>VALUE_SIZE)
		{printf("warning 685k: sample size too long=%s\n",sample_size);sample_size="NULL";}
	
	

	
	
printf("\nalpha2\n");
//here's the actual table insert
 insert_sql_table (esv_cert_num,implementation_name,standard,description,version,noise_source,reuse_status,operational_environment,cavp_certs,sample_size,vendor_name,validation_date,lab_name);


return 0;

} //parse_esv cert_from_single_html_file


//============================================================
int main (int argc, char* argv[]) {


 const char *Table_Name="CMVP_ESV_Table";
	char *file_path;
	char *file_num;

	long int file_pos=0;
	
	//char sql1 [SQL_MAX];
	//PGresult *sql_result;
	data_t data;
	int i;
	int myX;
	int Postgresql_Connection_Status;
	char connbuff[200];



//printf("alpha1\n");
	

	switch (PROD) {
		case 2:  			//local VM machine
		//	AES_set_decrypt_key(userKey_, 128, &aesKey_); //rgf2
    	//	AES_decrypt(VMencryptedPW, decryptedPW,&aesKey_);
    		
    		printf("\ndecrypted p/w is:%s\n",plainTextPW); //rgf2

    		snprintf(connbuff,sizeof connbuff,"host=localhost user=postgres password=%s dbname=postgres", decryptedPW);
       		conn = PQconnectdb(connbuff);
   	   		
   	   		break;
	
		case 1: 			//intel intranet production
  		
	  		
			//AES_set_decrypt_key(userKey_, 128, &aesKey_);
    		//AES_decrypt(IntelencryptedPW, decryptedPW,&aesKey_);

			printf("\ndecrypted p/w is:%s\n",plainTextPW); //rgf2

    		snprintf(connbuff,sizeof connbuff,"host=postgres5320-lb-fm-in.dbaas.intel.com user=lhi_prod2_so password=%s dbname=lhi_prod2 ", decryptedPW);
    
    		conn = PQconnectdb(connbuff);
   	   		break;
	
		case 0: //Intel intranet pre-production
			
		 	//AES_set_decrypt_key(userKey_, 128, &aesKey_);
    		//AES_decrypt(IntelencryptedPW, decryptedPW,&aesKey_);
    		
    		//snprintf(connbuff,sizeof connbuff,"host=postgres5596-lb-fm-in.dbaas.intel.com user=lhi_pre_prod_so password=%s dbname=lhi_pre_prod ", decryptedPW);
    
   	   		//conn = PQconnectdb(connbuff);
   	   		break;
		default: 
			printf("ERROR  112: Unknown PROD=%d\n",PROD); break;
	}




	Postgresql_Connection_Status=PQstatus(conn);

	switch(Postgresql_Connection_Status) {
		case CONNECTION_OK: 			printf("alpha Connection OK\n"); break;
		case CONNECTION_BAD:			printf("Connection Bad. Possilbe invalid connection parameters\n"); break;
		case CONNECTION_STARTED: 		printf("Connection Started\n");break;
		case CONNECTION_MADE:  			printf("Connecton Made\n"); break;
		case CONNECTION_AWAITING_RESPONSE: printf("Connection Awaiting Response\n"); break;
		case CONNECTION_AUTH_OK: 		printf("Connection Auth Ok\n"); break;
		case CONNECTION_SSL_STARTUP: 	printf("Connection _SSL_Startup\n"); break;
		case CONNECTION_SETENV: 		printf("Connect Setenv\n"); break;
		case CONNECTION_CHECK_WRITABLE: printf("Connection_Check_Writable\n"); break;
		case CONNECTION_CONSUME: 		printf("Connection Consume\n"); break;
		default:
			printf("ERROR 1297: unknown connection error = %d\n",Postgresql_Connection_Status); break;
		} // switch


	if (Postgresql_Connection_Status==CONNECTION_OK) {
   

		if(argc != 2) { 
			printf("*** Error 345: Missing Input File Name.\n");
			
		} 

		// get input filename
		file_path = argv[1];
	
		//------ do file manipulation stuff here
		//printf("Opening file: '%s'\n", file_path);
		if(!read_file(file_path, &data))
				printf("*** Error 356: Error reading file '%s'.\n",file_path);
			
		

		//printf("alpha3\n");
					
		parse_esv_cert_from_single_html_file(file_path, data.rawsymbols,  data.len);
		
	//printf("alpha4\n");

    		//printf("alphaC\n");
	} //connection ok
	else
		printf("PostgreSQL connection error. connstr=%s\n",connbuff);

	PQfinish(conn);
//printf("alpha5\n");


	return(0);


} //main