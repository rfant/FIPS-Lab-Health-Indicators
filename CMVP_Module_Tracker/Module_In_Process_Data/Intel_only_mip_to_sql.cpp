#include <stdio.h>
//#include </usr/local/Cellar/postgresql/13.2_1/include/libpq-fe.h>
#include <//usr/include/postgresql/libpq-fe.h>  //ubuntu
#include <iostream>
#include <fstream>
#include <string>
#include "utils.h"
#include <unistd.h>

//global variables
PGconn *conn;
char * MasterStr;


#define VALUE_SIZE 512   //max size of value between tags
#define SQL_MAX 4096   //4096 //max size of a sql query string
//=========================================================
#define DEBUG  (1)   //set to (0) to turn off printf messages.

#define CLR_SQL1_STR for(i=0;i<SQL_MAX;i++) sql1[i]=0; 
#define CLR_NOTES_STR for(i=0;i<SQL_MAX;i++) notes[i]=0; 
//zero out sql command strings. Not sure why this is necessary. But will fail if I don't

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




void strfcat(char *, char *, ...);
int check_dates_match(char * ,const char * );
int str_find (const char * ,byte * ,const long , long );

#include "atsec_Only_MIP_Indicator_sql.h"


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
//3)  registered trademark sybmols '(R)'
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
		if(str[i]>0x7f || str[i]<0)
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

//=============================


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


//===================================================================================================

int check_dates_match(char * file_name,const char * last_update_date)
{
//output: 0-> error
//        1 -> ok
//Simple test ot make sure that the date embedded in the file name is the same date as is used inside the file
//This is only used for the atsec only data because a person manually named the file, and so errors/typos happen
//So, only "parse_modules_from_single_email" should ever invoke this.

//Also, make sure that only hypens ('-') are used on the date part and not ('_') since they will order differently 
// in sort.
	
#define Plus_Or_Minus 3

	
	int pos=10;
	char  temp_date[VALUE_SIZE];
	char  * temp_value;
	char  * temp_alpha;
	int i;
	
	char dateFromName[pos+1]; // yyyy-mm-dd.  pos+1 to add 1 for the null terminator
	char * convertedDateFromName; //mm/dd/yyyy
	
	//printf("charlie:  file_name=%s\n",file_name);

	strncpy(dateFromName, file_name +71, pos); //71 is to define the full path /Users/fant/CMVP_Module_Tracker/Module_In_Process_Data/atsec_only_data/2017-01-01_ATT00026.htm
	dateFromName[pos] = '\0'; // place the null terminator
	
	
	temp_value=dateFromName;

	if(temp_value[7]=='_' || temp_value[4]=='_')
		{printf("error 244: '_' underscore present in date.\n"); return(0); }


	//NOTE: this brute force algorithm is stupid and painful. BUT, I have a memory leak
	// that I can't track down. So, this is the only consitent algorithm
	// that works.

	//TODO: implement this in SQL instead using date conversion function there. Much cleaner. . .
	//yyyy-mm-dd  converts to 'mm/dd/yyyy'


	temp_date[0]='\''; 				//single quote
	temp_date[1]=' ';            	// space
	temp_date[2]=temp_value[5];  	//1st m
	temp_date[3]=temp_value[6];  	//2nd m
	temp_date[4]='/'; 				// slash
	temp_date[5]=temp_value[8];		//1st d
	temp_date[6]=temp_value[9];		//2nd d
	temp_date[7]='/';				// slash
	temp_date[8]=temp_value[0];		// y
	temp_date[9]=temp_value[1];		// y
	temp_date[10]=temp_value[2];	// y
	temp_date[11]=temp_value[3];	// y
	temp_date[12]='\'';				// single quote

	if (temp_date[2]=='0' && temp_date[5]=='0')
		{
		temp_date[0]='\''; 				//single quote
		temp_date[1]=' ';            	// space
		temp_date[2]=temp_value[6];  	//2nd m  (1st m is '0')
		temp_date[3]='/'; 				// slash  	
		temp_date[4]=temp_value[9]; 	// 2nd d (1st d is '0')
		temp_date[5]='/';				//	slash
		temp_date[6]=temp_value[0];		//	y
		temp_date[7]=temp_value[1];  	//	y 
		temp_date[8]=temp_value[2];		// 	y
		temp_date[9]=temp_value[3];		// y
		temp_date[10]='\'';				// single quote
		temp_date[11]=0;
						
		}	

	if (temp_date[2]=='1' && temp_date[5]=='0')
		{
		
		temp_date[0]='\''; 				// single quote
		temp_date[1]=' ';            	// space
		temp_date[2]=temp_value[5];  	// 1st m  
		temp_date[3]=temp_value[6]; 	// 2nd m
		temp_date[4]='/';				// slash
		temp_date[5]=temp_value[9];		// 2nd d (1st d is '0')
		temp_date[6]='/';				// slash
		temp_date[7]=temp_value[0];  	// y 
		temp_date[8]=temp_value[1];		// y
		temp_date[9]=temp_value[2];		// y
		temp_date[10]=temp_value[3];	// y
		temp_date[11]='\'';
		temp_date[12]=0;
		
		}	

	if (temp_date[2]=='0' && temp_date[5]!='0')
		{
		
		temp_date[0]='\''; 				// single quote
		temp_date[1]=' ';            	// space
		temp_date[2]=temp_value[6];  	// 2nd m  
		temp_date[3]='/'; 				// slash
		temp_date[4]=temp_value[8];		// 1st d
		temp_date[5]=temp_value[9];		// 2nd d 
		temp_date[6]='/';				// slash
		temp_date[7]=temp_value[0];  	// y 
		temp_date[8]=temp_value[1];		// y
		temp_date[9]=temp_value[2];		// y
		temp_date[10]=temp_value[3];	// y
		temp_date[11]='\'';
		temp_date[12]=0;
		

		}	


	for(i=strlen(temp_value)+3;i<VALUE_SIZE;i++) //include two single-quotes and a space
		temp_date[i]=0;
	

	convertedDateFromName=temp_date;  //dummy1

	printf("Date_Check: last_update_date=%s. dateFromName=%s\n\n",last_update_date, strip_space(convertedDateFromName));

	if(strcmp(last_update_date,strip_space(convertedDateFromName))==0)
		return 1; //success
	else
		return 0; //fail




	
} //check_dates_match


//=============================================================================================================

int insert_sql_table(const char * tid_value, const char * module_name,const char * vendor_name, const char * lab_name,const char * iut_value,
					const char * review_pending, const char * in_review, const char * coordination, const char * finalization,
					const char * standard_value,const char * last_update_date){
//This will take the incoming current_row and insert it into the SQL table with its data (where appropriate)
//Input:  all the columns names for the main sql table
//output: return (1) for error. return (0) for success. 


char *value;
char *value1;
char *value2;
char *value3;
char *value4;
char *valueName;
char *valueTID;
char notes [SQL_MAX];

PGresult *sql_result;
int         nFields;
int i,j;
char sql1 [SQL_MAX];
int k;


CLR_NOTES_STR
	
strfcat(notes,"'created '%s';'",last_update_date);

//zero out sql1 command string. Not sure why this is necessary. But will fail if I don't
CLR_SQL1_STR


	

   	//build my sql  command string
//	strfcat(sql1,"INSERT INTO \"CMVP_atsec_Only_MIP_Table\"(\"TID\",\"Module_Name\", \"Vendor_Name\", \"Lab_Name\",\"IUT_Start_Date\", \"Review_Pending_Start_Date\",");
//	strfcat(sql1,"\"In_Review_Start_Date\", \"Coordination_Start_Date\",\"Finalization_Start_Date\",\"Last_Updated\",\"Standard\" ) ");
//	strfcat(sql1,"VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,(select CURRENT_DATE),%s)",tid_value,module_name,vendor_name,lab_name,iut_value,review_pending,in_review, coordination, finalization, standard_value);


	//build my sql  command string
	strfcat(sql1,"INSERT INTO \"CMVP_atsec_Only_MIP_Table\"(\"TID\",\"Module_Name\", \"Vendor_Name\", \"Lab_Name\",\"IUT_Start_Date\", \"Review_Pending_Start_Date\",");
	strfcat(sql1,"\"In_Review_Start_Date\", \"Coordination_Start_Date\",\"Finalization_Start_Date\",\"Last_Updated\",\"Standard\", \"Notes\" ) ");
	strfcat(sql1,"VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",tid_value,module_name,vendor_name,lab_name,iut_value,review_pending,in_review, coordination, finalization,last_update_date, standard_value, notes);
		
	printf("\nAlpha insert into table: sql1 =%s\n",sql1);


	if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179N: sql1 is too long. Increase SQL MAX size");
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

printf("hotel 1 enter\n");

    char buf[SQL_MAX];
    va_list args;

    va_start(args, fmt);
    vsprintf(buf, fmt, args);
    va_end(args);

    strcat(src, buf);

    printf("hotel 1 exit\n");
}//strfcat

//====================================================================================================
long int parse_modules_from_single_email (char * file_name,byte* data,const long len){
//this will parse a single module from the HTML email file as received from the CMVP Weekly Status Report
//It will then update that module info  in the main SQL table
//intput: "data" is a byte stream where each character of the HTML file is a single index
//        len   is the total number of bytes in the HTML file
//		  
//output: returns 1 on error. returns 0 on success




const char * last_update_date;
const char * tid_value;
const char * module_name;
const char * vendor_name;
const char * lab_name="'atsec'";  //Note: this field is NOT populated from the file. But since only atsec gets the email . . .
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


	termination_value=str_find ("</table>", data,len, 0);	//this is where the email file ends.
	//termination_value=str_find ("Submissions Pending Your Action", data,len, 0);	//this is where the email file ends.

	Standard_Column_Exists=str_find(">Standard<",data,len,0);// "Standard" (FIPS 140-2 or FIPS 140-3) was introduced on 9/25/2020. Before that, it was always FIPS 140-2.Need to account for that.
	Status_Column_Exists=str_find(">Status<",data,len,0); // "Status" with a single value (Review Pending, In Review, Coordination, Finalization) was introduced on 10/28/2020. Before that, a matrix display was used to show status.

	CLR_SQL1_STR
	//setup temporary sql table. 
	strfcat(sql1,"delete from \"Daily_CMVP_MIP_Table\";"); //Assumes table is already created. Clean up old rows in temp_table
	
	//printf("sql1 bravo=%s\n",sql1);
	//printf("\nsql4 =%s\n",sql1);

	if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179O: sql1 is too long. Increase SQL MAX size");
	sql_result = PQexec(conn, sql1);  //do delete
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			printf("\nError 618: SQL  Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}
	

		
	//------------- Get Last Update Date ------------------------

	myX=str_find ("<h1>CMVP Status Report for ", data,len, 0);  //returns file ptr postion of first "<"
	myY=str_find ("</h1>", data,len, 0); 
	

	if(myX<prevX) 
		return 1;
	else
		prevX=myX;

	if((myX==0 || myY==0)) {
		printf("***** Error 142a: Last Update Tag Not found (x=%d.y=%d)\n",myX,myY);
		return 1;
	}
	else{

		j=1;
		dummy1[0]='\''; 
		for	(i=myX+26;i<myY;i++){  //The literal 26 is to skip "<h1>CMVP Status Report for"
			dummy1[j]=data[i];
			//printf("**%d: %c\n",i,data[i]);
			j++;}  //I only want the subset (mm/dd/yyyy) from the file
		dummy1[j]='\''; 
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy1[i]=0;


		last_update_date=strip_space(dummy1); //convert my char [255] to char *
	} //else
	

	
	printf("Last Update: %s.  \n",last_update_date);	

	//make sure that the date embedded in the file name is the same as the date in the file. This
	//is important since the sort order is critical to the parsing algorithm.
	if (check_dates_match( file_name,last_update_date))
		printf("File Dates match\n");
	else
	{
		printf("Error 776: Date in FileName: %s doesn't match Last Update:%s  inside the file. Ignoring this file. \n",file_name,last_update_date);
		return (-1);
	}
		
//Big Loop for all modules within this single email
while (myX >0 && myX >= prevX && myX<termination_value) {

	

	if DEBUG printf("***** Module %d   *******************\n",num_of_modules); 
	//printf("*****Module %d   ******************* myX=%d. Term_value=%d\n",num_of_modules,myX,termination_value);


	num_of_modules++;

	//--------- Get the TID which also uses <td> </td> -------------------
	myX=str_find ("<td", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myY=str_find ("</td>", data,len, myY+1);  


	if( myX>termination_value) {
		printf("Done with file.\n");
		break;
	}
	if(myX<prevX) {
		printf("Exiting A: myX=%d, prevX=%d\n",myX,prevX);
		break;
		}
	else
		prevX=myX;

	if(myX==0 || myY==0){
		printf("***** Error 142bb: TIDe Tag Not found (x=%d.y=%d)\n",myX,myY);
		break;
	}
	else{
		
		j=1;
		dummyTID[0]='\'';
		//for	(i=myX+19;i<myY;i++){  //get the whole TID.
		for	(i=myX+19;i<(myX+19 + 7);i++){  //just get the first 7 characters of TID
			dummyTID[j]=data[i];
			//printf("**%d: %c\n",i,data[i]);
			j++;}  
		dummyTID[j]='\'';	
		for(i=j+1;i<VALUE_SIZE;i++)
			dummyTID[i]=0;
		
		tid_value=strip_space(dummyTID); //convert my char [255] to char *
	} //else
	
	if DEBUG printf("TID: %s\n",tid_value);
	
	//----------- Get Module Name ----------------------

	myX=str_find ("<td", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myY=str_find ("</td>", data,len, myY+1);  
	if(myX<prevX) {
		printf("Exiting B: myX=%d, prevX=%d\n",myX,prevX);
		break;
		}
	else
		prevX=myX;

	if(myX==0 || myY==0){
		printf("***** Error 142b: Module Name Tag Not found (x=%d.y=%d)\n",myX,myY);
		break;
	}
	else{
		j=1;
		dummy2[0]='\'';
		for	(i=myX+4;i<myY;i++){  
			dummy2[j]=data[i];
			//printf("**%d: %c\n",i,data[i]);
			j++;}  
		dummy2[j]='\'';	
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy2[i]=0;
		
		module_name=strip_space(dummy2); //convert my char [255] to char *
	} //else
	if DEBUG printf("Module Name: %s\n",module_name);
	

	//--------------- Get Vendor Name -----------------------------------

	myX=str_find ("<td", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
	myY=str_find ("</td>", data,len, myY+1);  
	if(myX<prevX) {
		printf("Exiting C: myX=%d, prevX=%d\n",myX,prevX);
		break;
		}
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
			//printf("** %d: %c\n",i,data[i]);
			j++;}  
		dummy3[j]='\'';
		for(i=j+1;i<VALUE_SIZE;i++)
			dummy3[i]=0;
			
		vendor_name=strip_space(dummy3); //convert my char [255] to char *
	} //else
	if DEBUG printf("Vendor Name: %s",vendor_name);
	
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
			printf("Exiting C: myX=%d (0 means I can't find 'Standard' tag), prevX=%d\n",myX,prevX);
		else
			prevX=myX;

		if(myX==0 || myY==0){
			printf("***** Error 630a: Standard Value (140-2 or 140-3) Tag Not found (x=%d.y=%d)\n",myX,myY);
			break;
			}
		
		j=6; //1;   //manually add prefix "FIPS" since the CMVP uses "FIPS" for their website but doesn't use the prefix for the weekly email. Very inconsistent.

		dummySTANDARD[0]='\'';
		dummySTANDARD[1]='F';
		dummySTANDARD[2]='I';
		dummySTANDARD[3]='P';
		dummySTANDARD[4]='S';
		dummySTANDARD[5]=' ';
		
		
		for	(i=myX+4;i<myY;i++){  
			dummySTANDARD[j]=data[i];
			j++;}  
		dummySTANDARD[j]='\'';
		for(i=j+1;i<VALUE_SIZE;i++)
			dummySTANDARD[i]=0;
			
		
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

		

	}  //doesNOT have ">Standard<" field.
	
	standard_value=strip_space(dummySTANDARD); //convert my char [255] to char *. Remove leading & trailing spaces.

	if DEBUG printf("\nStandard: %s\n",standard_value);


	//-------------- Status ------------------------------------
	// "Status" with a single value (Review Pending, In Review, Coordination, Finalization) was introduced on 2/22/2021. 
	//  Before that, a matrix display was used to show status using the highlight attribute of HTML.
	//  I'll have to determine which format is used in this file. The "else" clause below has the code looking for the
	//  "highlight" html attribute to determine the status.

	if(Status_Column_Exists)
	{

		//if DEBUG
		//	printf(">Status< Tag Exists? YES. Use value associated with tag. \n");

		//initilizate everything to null
		iut_value="NULL";
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
		else if (strstr(status_value,"Implementation Under Test")!=0) 
				iut_value=last_update_date;	
		
		else
			printf ("ERROR 708: Unknown Status Value=%s\n",status_value);

	
	}
	else
	{   //use the highlight attribute to determine status
		//--------- Get the IUT which also uses <td> </td> -------------------

		myX=str_find ("<td", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
		myY=str_find ("</td>", data,len, myY+1);  
		if(myX<prevX) {
			printf("Exiting D: myX=%d, prevX=%d\n",myX,prevX);
			break;
			}
		else
			prevX=myX;
		if(myX==0|| myY==0) {
			printf("*****  Missing IUT Tag) (x=%d.y=%d)\n",myX,myY);
			break;
		}
		else{
			j=0;
			for	(i=myX+4;i<myY;i++){  
				dummyIUT[j]=data[i];
				//printf("**%d: %c\n",i,data[i]);
				j++;}  
				for(i=j;i<VALUE_SIZE;i++)
					dummyIUT[i]=0;

			
			iut_value=dummyIUT; //convert my char [255] to char *
		} //else
			
		if(strstr(iut_value,"highlight")!=0) 
		{
			iut_value=last_update_date; 
			
		}
		else
			iut_value="NULL";  

		//if DEBUG  printf("IUT: %s\n",iut_value);

		//-------------- Review Pending ------------------------------
		myX=str_find ("<td", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
		myY=str_find ("</td>", data,len, myY+1);  
		if(myX<prevX) {
			printf("Exiting E: myX=%d, prevX=%d\n",myX,prevX);
			break;
			}
		else
			prevX=myX;

		if(myX==0 || myY==0) {
			printf("***** End Of Email (or Missing Tag) (x=%d.y=%d)\n",myX,myY);
			break;
		}
		else{
			j=0;
			for	(i=myX+4;i<myY;i++){  
				dummy4[j]=data[i];
				//printf("**%d: %c\n",i,data[i]);
				j++;}  
				for(i=j+1;i<VALUE_SIZE;i++)
				dummy4[i]=0;

			
			review_pending=dummy4; //convert my char [255] to char *
		} //else

		//Each module in this email will only have a single CMVP state with keyword "highlighted".
		//The "highlight" state is the current state. All other states are NULL.
		//If a state is "highlighted", then I'll replace its NULL value with email's date. 
		if(strstr(review_pending,"highlight")!=0) 
		{
			review_pending=last_update_date; 
			
		}
		else
			review_pending="NULL";  

		//if DEBUG  printf("Review Pending: %s\n",review_pending);
		

		//-------------- In Review ------------------------------

		myX=str_find ("<td", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
		myY=str_find ("</td>", data,len, myY+1);  
		if(myX<prevX) {
			printf("Exiting F: myX=%d, prevX=%d\n",myX,prevX);
			break;
			}
		else
			prevX=myX;
		if(myX==0 || myY==0){
			printf("***** Error 142e: In Review Tag Not found (x=%d.y=%d)\n",myX,myY);
			break;
		}
		else{
			j=0;
			for	(i=myX+4;i<myY;i++){  
				dummy5[j]=data[i];
				//printf("**%d: %c\n",i,data[i]);
				j++;}  
			for(i=j+1;i<VALUE_SIZE;i++)
					dummy5[i]=0;
			
			in_review=dummy5; //convert my char [255] to char *
		} //else

		//Each module in this email will only have a single CMVP state with keyword "highlighted".
		//The "highlight" state is the current state. All other states are NULL.
		//If a state is "highlighted", then I'll replace its NULL value with email's date. 
		if(strstr(in_review,"highlight")!=0){
			in_review=last_update_date;
			//printf("%s entered in_review on %s\n",module_name,last_update_date);
		}
		else
			in_review="NULL";
		
		//if DEBUG  printf("In Review: %s\n",in_review);
		
		

		//-------------- Coordination ------------------------------

		myX=str_find ("<td", data,len, myY+1);  ///start at last file position myY+1 (have to inc by 1 to avoid repeat)
		myY=str_find ("</td>", data,len, myY+1);  
		if(myX<prevX) {
			printf("Exiting G: myX=%d, prevX=%d\n",myX,prevX);
			break;
			}
		else
			prevX=myX;

		if(myX==0 || myY==0){
			printf("***** Error 142f: Coordination Tag Not found (x=%d.y=%d)\n",myX,myY);
			break;
		}
		else{
			j=0;
			for	(i=myX+4;i<myY;i++){  
				dummy6[j]=data[i];
				//printf("**%d: %c\n",i,data[i]);
				j++;}  
			for(i=j+1;i<VALUE_SIZE;i++)
				dummy6[i]=0;

			coordination=dummy6; //convert my char [255] to char *
		} //else

		//Each module in this email will only have a single CMVP state with keyword "highlighted".
		//The "highlight" state is the current state. All other states are NULL.
		//If a state is "highlighted", then I'll replace its NULL value with email's date. 
		if(strstr(coordination,"highlight")!=0){
			coordination=last_update_date;
			//printf("%s entered coordination on %s\n",module_name,last_update_date);
		}
		else
			coordination="NULL";
		
		//if DEBUG  printf("Coordination: %s\n",coordination);
		

		//-------------- Finalization ------------------------------

		myX=str_find ("<td", data,len, myY+1);  //start at last file position myY+1 (have to inc by 1 to avoid repeat)
		myY=str_find ("</td>", data,len, myY+1);  
		if(myX<prevX) {
			printf("Exiting H: myX=%d, prevX=%d\n",myX,prevX);
			break;
			}
		else
			prevX=myX;

		if(myX==0 || myY==0){
			printf("***** Error 142g: Finalization Tag Not found (x=%d.y=%d)\n",myX,myY);
			break;
		}
		else{
			j=0;
			for	(i=myX+4;i<myY;i++){  
				dummy7[j]=data[i];
				//printf("**%d: %c\n",i,data[i]);
				j++;}  
			for(i=j+1;i<VALUE_SIZE;i++)
				dummy7[i]=0;

			finalization=dummy7; //convert my char [255] to char *
		} //else
		
		//Each module in this email will only have a single CMVP state with keyword "highlighted".
		//The "highlight" state is the current state. All other states are NULL.
		//If a state is "highlighted", then I'll replace its NULL value with email's date. 
		if(strstr(finalization,"highlight")!=0){
			finalization=last_update_date;
			//printf("%s entered finalization on %s\n",module_name,last_update_date);
		}
		else
			finalization="NULL";
		//if DEBUG printf("Finalization: %s\n",finalization);
		

	}   //Big else statement: using the highlight attribute to determine status.

	if DEBUG printf("IUT: %s\n",iut_value);
	if DEBUG printf("Review Pending: %s\n",review_pending);
	if DEBUG printf("In Review: %s\n",in_review);
	if DEBUG printf("Coordination: %s\n",coordination);
	if DEBUG printf("Finalization: %s\n",finalization);	
	
	//----------- Insert Row Into SQL Table ----------------------------------

	if(myX>0 && myX>=prevX && myX<termination_value)
	{  //Make sure I have a valid row before inserting and copying	
		
		

	 	//insert into the SQL table  just this single module from the email. 
		//insert_sql_table(tid_value,module_name,vendor_name,lab_name,iut_value,review_pending,in_review,coordination,finalization,standard_value);
		insert_sql_table(tid_value,module_name,vendor_name,lab_name,iut_value,review_pending,in_review,coordination,finalization,standard_value,last_update_date);


		
		//Make a copy of this row in a  table called "Daily". This table is used for vanishing modules 
		CLR_SQL1_STR
		strfcat(sql1,"INSERT INTO \"Daily_CMVP_MIP_Table\" (\"Row_ID\",\"TID\",\"Module_Name\", \"Vendor_Name\",\"Lab_Name\", \"IUT_Start_Date\", ");
		strfcat(sql1,"\"Review_Pending_Start_Date\",\"In_Review_Start_Date\", \"Coordination_Start_Date\", \"Finalization_Start_Date\") ");
		strfcat(sql1,"SELECT \"Row_ID\",\"TID\",\"Module_Name\", \"Vendor_Name\",\"Lab_Name\", \"IUT_Start_Date\", \"Review_Pending_Start_Date\",");
		strfcat(sql1,"\"In_Review_Start_Date\", \"Coordination_Start_Date\", \"Finalization_Start_Date\" FROM \"CMVP_atsec_Only_MIP_Table\" ");
		strfcat(sql1,"where \"Module_Name\"=%s AND \"TID\"=%s",module_name,tid_value);
		
		
		//printf("\nsql5 =%s\n",sql1);
		
		if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179P: sql1 is too long. Increase SQL MAX size");

		sql_result = PQexec(conn, sql1);  //execute the copy to "Daily" table
		if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			printf("\nError 982: SQL  Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}


	} // make sure I still have a valid row before inserting and copying
	else
		printf("error 1027: invalid row. myX=%d\n",myX);


} // Big Loop for all modules

	
//************************
//All done parsing the email. Now I just need to clean up:  
// 1) mark Vanished Modules,
// 2) merge duplicates, 
// 3) calculate finalization date,  
// 4) remove dead rows.

	

//===================
// Vanishing Modules: sometimes a module with a TID (atsec modules only)  will just disappear from one HTML file to the next and 
// no update on it is ever seen again on the MIP table. This could be because the vendor cancelled it, or it got merged with another module, it transitioned
// to the Active state, or something else.  This next section will mark any disappearing modules by setting its status2 to 'Vanished'

	//See if this module has dropped off the MIP radar.
	CLR_SQL1_STR
	strfcat(sql1,"UPDATE  \"CMVP_atsec_Only_MIP_Table\" SET \"Status2\" = 'Vanished-'%s''  ", last_update_date);
	strfcat(sql1," from( SELECT \"CMVP_atsec_Only_MIP_Table\".\"TID\" from \"CMVP_atsec_Only_MIP_Table\"  " );
	strfcat(sql1," left  outer JOIN \"Daily_CMVP_MIP_Table\"  ON \"CMVP_atsec_Only_MIP_Table\".\"TID\" =  \"Daily_CMVP_MIP_Table\".\"TID\" ");
	strfcat(sql1," where  \"Daily_CMVP_MIP_Table\".\"TID\" is null  ) as subquery  where  \"CMVP_atsec_Only_MIP_Table\".\"TID\"=subquery.\"TID\" "); 
	strfcat(sql1," and \"CMVP_atsec_Only_MIP_Table\".\"Status2\" is null  ;" );

	// update the "Notes" field regarding Vanish if appropriate
	strfcat(sql1,"UPDATE \"CMVP_atsec_Only_MIP_Table\" set \"Notes\" =  case ");
	strfcat(sql1," when \"Notes\" is null AND \"Status2\" like '%%Vanished%%' then \"Status2\" || ';' ");
	strfcat(sql1," when \"Notes\" is not null AND \"Status2\" like '%%Vanished%%' AND \"Notes\" not like '%%Vanished%%'  AND \"Notes\" not like '%%Promoted%%' ");
	strfcat(sql1," then \"Notes\" || \"Status2\" || ';'  else \"Notes\" end ; " );



	printf("\nRadar Drop Test sql5 =%s\n",sql1);

	if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179Q: sql1 is too long. Increase SQL MAX size");
	sql_result = PQexec(conn, sql1);  //execute 
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
 			printf("\nError 1136: SQL Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}

	//-----------------
	//If the module dropped off the atsec radar, is it because it transitioned to the Active Table?  If so, mark it as 'Promoted'
	CLR_SQL1_STR
	strfcat(sql1," UPDATE  \"CMVP_atsec_Only_MIP_Table\" SET \"Status2\" = 'Promoted-'%s''  ",last_update_date);
	strfcat(sql1," from(	SELECT \"CMVP_atsec_Only_MIP_Table\".\"TID\", \"CMVP_Active_Table\".\"Validation_Date\" from \"CMVP_atsec_Only_MIP_Table\"   ");
	strfcat(sql1," inner JOIN \"CMVP_Active_Table\"  ON \"CMVP_atsec_Only_MIP_Table\".\"Module_Name\" =  \"CMVP_Active_Table\".\"Module_Name\" ");
	strfcat(sql1," and \"CMVP_atsec_Only_MIP_Table\".\"Vendor_Name\" = \"CMVP_Active_Table\".\"Vendor_Name\") ");
 	strfcat(sql1," as subquery  where  \"CMVP_atsec_Only_MIP_Table\".\"TID\"=subquery.\"TID\" and \"CMVP_atsec_Only_MIP_Table\".\"Status2\" like 'Vanished%%'  ");
 	strfcat(sql1," and (\"CMVP_atsec_Only_MIP_Table\".\"Coordination_Start_Date\" is not null OR \"CMVP_atsec_Only_MIP_Table\".\"Finalization_Start_Date\" is not null) ");
 	
 	//Simple sanity check here. Can't use Finalization_Start_Date because the atsec_only data is refreshed just once a week while the Active table is updated daily.
 	//so there can be times where a module is promoted to Active but it's Finalization date in the atsec_lnly table is later (greater than) the valiation date in the Active.
 	//But the coordination_start_date should always be less than the validation date if this module truly got promoted to the Active table.
 	//Since a module can be "renewed" multiple times in the Active table, I only want to grab the module that is freshest.       
 	strfcat(sql1," and ( TO_DATE(right(subquery.\"Validation_Date\",10),'MM/DD/YYYY') >= \"CMVP_atsec_Only_MIP_Table\".\"Coordination_Start_Date\"); ");


 	// update the "Notes" field regarding Promoted if appropriate
	strfcat(sql1,"UPDATE \"CMVP_atsec_Only_MIP_Table\" set \"Notes\" =  case ");
	strfcat(sql1," when \"Notes\" is null AND \"Status2\" like '%%Promoted%%' then \"Status2\" || ';' ");
	strfcat(sql1," when \"Notes\" is not null AND \"Status2\" like '%%Promoted%%' AND \"Notes\" not like '%%Promoted%%'  ");
	strfcat(sql1," then \"Notes\" || \"Status2\" || ';'  else \"Notes\" end ; " );



 	printf("\nsql6 =%s\n",sql1);

 	if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179R: sql1 is too long. Increase SQL MAX size");
	sql_result = PQexec(conn, sql1);  //execute  
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
 			printf("\nError 1103: SQL Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}

	//----------------
	//Add cert_num to atsec_only_table if module has been promoted to Active. 
	CLR_SQL1_STR

	strfcat(sql1," UPDATE  \"CMVP_atsec_Only_MIP_Table\" as t3  SET  "); 
	strfcat(sql1," \"Cert_Num\" = (case when t3.\"Cert_Num\"  is null  then subquery.\"Cert_Num\" else  t3.\"Cert_Num\"  end)   ");
	//strfcat(sql1," ,\"TID\" = (case when t3.\"Cert_Num\" is null then t3.\"TID\" || '-' || subquery.\"Cert_Num\" else t3.\"TID\" end) ");
	strfcat(sql1," from(SELECT t1.\"TID\", t2.\"Validation_Date\",t2.\"Cert_Num\" ,t2.\"Status2\",t1.\"Status2\",t1.\"Coordination_Start_Date\",t1.\"Finalization_Start_Date\" from  ");
	strfcat(sql1," 	\"CMVP_atsec_Only_MIP_Table\"  t1 inner JOIN \"CMVP_Active_Table\" t2 ON t1.\"Module_Name\" =  t2.\"Module_Name\"  and 		 ");
	strfcat(sql1," 	t1.\"Vendor_Name\" = t2.\"Vendor_Name\" order by t1.\"TID\" ) as subquery   ");
	strfcat(sql1," where  t3.\"TID\"=subquery.\"TID\" and t3.\"Status2\" like 'Promoted-'%s''  and   ",last_update_date);
	strfcat(sql1," (t3.\"Coordination_Start_Date\" is not null OR t3.\"Finalization_Start_Date\" is not null)  ");
	strfcat(sql1," and ( TO_DATE(right(subquery.\"Validation_Date\",10),'MM/DD/YYYY') >= t3.\"Coordination_Start_Date\")  ");
 
	printf("\nsql7 =%s\n",sql1);

	if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179S: sql1 is too long. Increase SQL MAX size");
	sql_result = PQexec(conn, sql1);  //execute  
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
 			printf("\nError 1157: SQL Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}

	//----------------		
	//Merge all the duplicate rows
	CLR_SQL1_STR
	if DEBUG printf("loop_merge_all_dups");
	strfcat(sql1,"select loop_to_merge_all_dups (); ");
	sql_result = PQexec(conn, sql1);  

	if (PQresultStatus(sql_result) != PGRES_TUPLES_OK) 
	{//check status
	   	printf("\nError 1042: SQL Merge Function Command failed: sql1=%s\n",sql1);
		return(-1);
	} //check status


	//Add detals about Module_Type and SL if they are available. 
	CLR_SQL1_STR
	strfcat(sql1," Update \"CMVP_atsec_Only_MIP_Table\" as t1 set \"Module_Type\" =  t2.\"Module_Type\" , \"SL\" =  t2.\"SL\"  ");
 	strfcat(sql1," from (select \"Cert_Num\",\"Module_Type\",\"SL\" from  \"CMVP_Active_Table\") as t2 where t1.\"Cert_Num\"=t2.\"Cert_Num\" and t1.\"Module_Type\" is null ; ");
	
 	// update the "Notes" field regarding "Module_Type" and "SL" if appropriate
	strfcat(sql1," UPDATE \"CMVP_atsec_Only_MIP_Table\" set \"Notes\" =  case ");
	strfcat(sql1," when \"Notes\" is null AND \"Module_Type\" is not null then 'Added Module_Type and SL-'%s';' ",last_update_date);
	strfcat(sql1," when \"Notes\" is not null AND \"Notes\" not like '%%Module_Type%%' AND \"Module_Type\" is not null ");
	strfcat(sql1," then \"Notes\" || 'Added Module_Type and SL-'%s';' else \"Notes\" end;", last_update_date);



 	if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179k: sql1 is too long. Increase SQL MAX size");
	sql_result = PQexec(conn, sql1);  
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK) 
	{//check status
	   	printf("\nError 1184: SQL update Module_Type & SL Command failed: sql1=%s\n",sql1);
		return(-1);
	} //check status



	
 	CLR_SQL1_STR

	//strfcat(sql1," update \"CMVP_atsec_Only_MIP_Table\" set \"Finalization_Start_Date\" = ");
	//strfcat(sql1," case when \"Status2\" like '%%Promoted%%' then replace(\"Status2\",'Promoted-','')::date else null end ");
	//strfcat(sql1," where \"Finalization_Start_Date\" is null ");

	strfcat(sql1," update \"CMVP_atsec_Only_MIP_Table\" set ");
	strfcat(sql1," \"Finalization_Start_Date\" = case when \"Status2\" like '%%Promoted%%' then replace(\"Status2\",'Promoted-','')::date else null end ");
	strfcat(sql1," ,\"Notes\" = case when \"Status2\" like '%%Promoted%%' AND \"Notes\" not like '%%Update Final%%' ");
	strfcat(sql1,"      then \"Notes\" || 'Update Final-' || replace(\"Status2\",'Promoted-','')::date || ';' else \"Notes\" end ");
	strfcat(sql1," where \"Finalization_Start_Date\" is null ");
	

	if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179L: sql1 is too long. Increase SQL MAX size");
	sql_result = PQexec(conn, sql1);  
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK) 
	{//check status
	   	printf("\nError 1197: SQL update Finalization Command failed: sql1=%s\n",sql1);
		return(-1);
	} //check status

if (Status_Column_Exists)
{
	// Before the ">Status<" column header was implemented by CMVP (10/28/2020), the only way to determine
	// if a module had entered Finalization was to see the module name disappear from the list after that
	// module had entered Coordination. By comparing the date when the module started Coordination
	// and the date when the module disappeared from the list, the Finalization date could be calculated.
	// You would never actual see a module in Finalization using the CMVP matrix ('highlighted'.)
	// This was a really bad and awkward algorithm that was necessitated by CMVP's clumsy website.  
	// But now (after 10/28/2020), CMVP actually shows a module in Finalization by using the ">Status<" keyword
	// along with "Finaliation".  However, for legacy purposes, I need to keep the old method as well. The old method
	// is implemented in the else clause below. That code can be deprecated around 10/2025 when all the modules that
	// could be using it are on the historical list.

} //do nothing
else
{
	//Calculate Finalization_Start_Date using this algorithm:
	//	Step 1) Delete all rows in a temporary daily_table. Insert into daily_table all rows from this single email [done above]		
	// 	Step 2) if main sql table has module_name with a date for column "Coordination_Start_Date", 
	//               AND its "Finalization_Start_Date" is NULL AND the same module_name and TID is NOT in the temporary daily_table 
	//               then set the value in "Finalization_Start_Date" of main sql table with: 
	//					(todays_date if NULL) OR  ( min(today_date, value that's already there)). 
	//               else do nothing & exit

	
	//Finalization Calculation
	CLR_SQL1_STR
	strfcat(sql1,"UPDATE  \"CMVP_atsec_Only_MIP_Table\" SET \"Finalization_Start_Date\" = case ");
	strfcat(sql1," when subquery.\"Finalization_Start_Date\" is null AND %s::date > subquery.\"Coordination_Start_Date\"::date then %s::date ",last_update_date,last_update_date);
	strfcat(sql1," when %s::date < subquery.\"Finalization_Start_Date\" AND %s::date > subquery.\"Coordination_Start_Date\"::date then %s::date ",last_update_date,last_update_date,last_update_date);
	strfcat(sql1," else subquery.\"Finalization_Start_Date\"::date end ");
	strfcat(sql1," from(	SELECT \"CMVP_atsec_Only_MIP_Table\".\"Module_Name\",\"CMVP_atsec_Only_MIP_Table\".\"TID\", \"CMVP_atsec_Only_MIP_Table\".\"Coordination_Start_Date\", ");
	strfcat(sql1," \"CMVP_atsec_Only_MIP_Table\".\"Finalization_Start_Date\" , \"CMVP_atsec_Only_MIP_Table\".\"Lab_Name\"from \"CMVP_atsec_Only_MIP_Table\" ");
	strfcat(sql1," left  JOIN \"Daily_CMVP_MIP_Table\" ON \"CMVP_atsec_Only_MIP_Table\".\"Module_Name\" = \"Daily_CMVP_MIP_Table\".\"Module_Name\" ");
	strfcat(sql1," AND \"CMVP_atsec_Only_MIP_Table\".\"TID\" = \"Daily_CMVP_MIP_Table\".\"TID\" where \"Daily_CMVP_MIP_Table\".\"Module_Name\" is null ");
	strfcat(sql1," AND \"Daily_CMVP_MIP_Table\".\"TID\" is null 	) as subquery ");
	strfcat(sql1," where subquery.\"Coordination_Start_Date\" is not null ");
	strfcat(sql1," AND \"CMVP_atsec_Only_MIP_Table\".\"Coordination_Start_Date\" is not null " ); 
	strfcat(sql1," AND \"CMVP_atsec_Only_MIP_Table\".\"Module_Name\"=subquery.\"Module_Name\" ");
	strfcat(sql1," AND \"CMVP_atsec_Only_MIP_Table\".\"TID\"=subquery.\"TID\"  ");

	//printf("\nFinalization sql7 =%s\n",sql1);

	if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179M: sql is too long. Increase SQL MAX size");
	sql_result = PQexec(conn, sql1);  //execute the Finalization Date update 
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
 			printf("\nError 1021: SQL Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}

	
}  //finalization date calculation





return 0;

} //parse_modules_from_single_email

//=================================================================================================


//===================================================================================================================
int manual_update() 
{

//This procedure will be used to manually update the really wierd (and hopefully, not too frequent) situtations
// that are test escapes from the normal operations flow.
// For example, Inside Secure was bought by Verimatrix who was bought by Rambus all within a 12 month period. 
// Only consist things  were the TID, Module Name and the fact that atsec was the lab (so, we knew what was going on).  
// The vendor name changed during Review_Pending, In_Review and in the middle of Coordination which messed up the CMVP MIP website too which my parsing 
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
	
//Update Module_Type and SL from Active Table



//zero out sql1 command string. Not sure why this is necessary. But will fail if I don't
//CLR_SQL1_STR
	

//Update Module_Type and SL from Active Table
//strfcat(sql1,"  \"CMVP_atsec_Only_MIP_Table\" as t1 set \"Module_Type\" =  t2.\"Module_Type\" , \"SL\" =  t2.\"SL\" ");
//strfcat(sql1," from (select \"Cert_Num\",\"Module_Type\",\"SL\" from  \"CMVP_Active_Table\") as t2 where t1.\"Cert_Num\"=t2.\"Cert_Num\" ");
	

	
		//Here, Inside Secure was bought by VerimatrIx who was bought by Rambus all within a 12 month period. Only consist thing is TID and Module Name
		//strfcat(sql1,"	update \"CMVP_atsec_Only_MIP_Table\" set \"TID\" = '11-1624-6301' where \"Module_Name\" like 'VaultIP' and \"Vendor_Name\" like 'Verimatrix'; ");
		//strfcat(sql1," update \"CMVP_atsec_Only_MIP_Table\" set \"Vendor_Name\" = 'Rambus Inc.' where \"TID\" like '11-1624-6301' and \"Vendor_Name\" like 'Verimatrix'; ");


		//sql_result = PQexec(conn, sql1); 

		//if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   		//	printf("\nError 1073: SQL  Command failed: sql1=%s\n",sql1);
		//	PQclear(sql_result);
		//	return (1);}
		
		//insert the SQL table with just this single module from the email. 
		//insert_sql_table("'11-1615-5866'","'Honeywell Crypto Engine Core'","'Honeywell International Inc.'","'atsec'","null","'2/24/2019'","'3/3/2019'","null","'3/13/2019'");
		                 //tid_value     module_name                     vendor_name                           lab   iut.	 RP,         IR,             C,  F
		
		//insert_sql_table("'11-1616-5867'","'Honeywell Pseudo Random Number Generator'","'Honeywell International Inc.'","'atsec'","null","'2/24/2019'","'3/3/2019'","null", "'3/13/2019'");
		                 //tid_value     module_name                                 vendor_name                     lab   iut.	RP,        IR,        C,  F

		//insert_sql_table("'11-1617-5868'","'Honeywell Inline Crypto Engine (SDCC)'","'Honeywell International Inc.'","'atsec'","null","'2/24/2019'","'3/3/2019'","null","'3/13/2019'");
		                 //tid_value     module_name                                 vendor_name                     lab   iut.	RP,        IR,        C,  F

			
return 0;
} //manual_update
//=========================================================


//============================================================
int main (int argc, char* argv[]) {

	const char *Table_Name="CMVP_atsec_Only_MIP_Table";
	char *file_path;
	long int file_pos=0;
	
	//char *last_updated;
	data_t data;
	
	int i;
	

	int k;

	
	conn = PQconnectdb("host=localhost user=postgres password=postgres dbname=postgres ");

    if (PQstatus(conn) == CONNECTION_OK) {

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
		printf("Opening a  file: '%s'\n", file_path);
		if(!read_file(file_path, &data))
				printf("*** Error 356: Error reading file '%s'.\n",file_path);
			
		parse_modules_from_single_email(file_path,data.rawsymbols,  data.len);
		
		//manual_update();  	

		
			
    		
	} //connection ok
	PQfinish(conn);


	return(0);


} //main