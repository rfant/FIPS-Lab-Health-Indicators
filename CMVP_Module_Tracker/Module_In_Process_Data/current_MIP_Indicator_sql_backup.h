
//#ifndef SQL_MAX
//#define SQL_MAX 4096
//#endif

//#ifndef DEBUG
//#define DEBUG  (1)   //set to (0) to turn off printf messages.
//#endif

#ifndef CLR_SQL1_STR
#define CLR_SQL1_STR for(i=0;i<SQL_MAX;i++) sql1[i]=0; 
#endif
//--------------------------
int create_sql_function(){
//this include file contains all the sql functions needed for the CMVP MIP Indicator.
//
//This will first "drop" and then "create" each function to make sure the latest version
//of the function source code is always used.
//
// The top level function is:   loop_to_merge_all_dups (which will invoke the other two sql functions)
// will be used later by the main mip_to_sql.cpp file to merge all the duplicate TID/Module_Name rows.
// Doing this in sql makes the code run 10x faster than if written in C++.
//
//NOTE: syntax note:    \   is used to continue the line so the sql statement is basically one long continuous line.
//                          but that's not very human readable, so \  is used to break the line up
//                    
//	
//Input: none
//output: 0=> Success,  1=> failure

PGresult *sql_result;
int         nFields;
int i,j;
char sql1 [SQL_MAX];
int k;



CLR_SQL1_STR

//Here are the drop functions
strfcat(sql1,"drop function get_most_recent_date_in_both_rows (bigint, bigint );");
sql_result = PQexec(conn, sql1);  //execute drop command
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK ){  //7 is function not found. 
   			//printf("\nError 35: SQL Drop Function 'get_most_recent_date_in_both_rows (bigint,bigint);'failed:  sql1=%s\n",sql1);
			PQclear(sql_result);
			//return (1);
			//printf("Ignoring Error 35\n");
		}
	else
	{
		if DEBUG printf("SUCCESS: dropped 'get_most_recent_date_in_both_rows (bigint, bigint );' \n");
	}



CLR_SQL1_STR
//.   SQL table name is: CMVP_MIP_Table
strfcat(sql1," 	\
create or replace function get_most_recent_date_in_both_rows ( bigint,   bigint ) returns date    \
as $$	\
select  (case	\ 
	when m0::date>=m1::date AND m0::date>=m2::date AND m0::date>=m3::date then m0 	\
	when m1::date>=m2::date AND m1::date>=m3::date AND m1::date>=m0::date then m1	\
	when m2::date>=m3::date AND m2::date>=m0::date AND m2::date>=m1::date then m2	\
	when m3::date>=m0::date AND m3::date>=m1::date AND m3::date>=m2::date then m3  end ) 	\
	as max_date		\
	from 	\
		( SELECT  case when max(\"IUT_Start_Date\") is null then '1/1/1901' else max(\"IUT_Start_Date\") end as m0,	\
		 		  case when max(\"Review_Pending_Start_Date\") is null then '1/1/1901' else max(\"Review_Pending_Start_Date\") end as m1,	\
		 		  case when max(\"In_Review_Start_Date\") is null then '1/1/1901' else max(\"In_Review_Start_Date\") end as m2,	\
		 		  case when max(\"Coordination_Start_Date\") is null then '1/1/1901' else max(\"Coordination_Start_Date\") end as m3 	\
		FROM \"CMVP_MIP_Table\"  where (\"Row_ID\"=$1 OR 	\"Row_ID\"=$2) 	\
	) as subquery1 		\
	\
$$	\
language SQL;	\
");


sql_result = PQexec(conn, sql1);  //execute the create function command now
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			printf("\nError 88: SQL Create Function failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);	}
		else
		{
			if DEBUG printf("\nSUCCESS: added   'get_most_recent_date_in_both_rows ( bigint,   bigint )'\n");
		}

//-------------------------------------------------------------------------------------------------
CLR_SQL1_STR

strfcat(sql1,"DROP FUNCTION merge_rows_with_no_TID (bigint, bigint);");
sql_result = PQexec(conn, sql1);  //execute drop command
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK   ){  //7 is function not found. 
  			//printf("\nError 44: SQL Drop Function merge_rows_with_no_TID failed:  sql1=%s\n",sql1);
			PQclear(sql_result);
			//printf("Ignoring Error 44\n");
		}
	else
	{
		if DEBUG printf("\nSUCCESS: dropped 'merge_rows_with_no_TID (bigint, bigint);'\n");
	}



CLR_SQL1_STR
//only works for current MIP data.  So, SQL table name is: CMVP_MIP_Table

strfcat(sql1," 	\
	create or replace function merge_rows_with_no_TID (bigint, bigint ) returns integer 	\
	as $$	\
		\
	update \"CMVP_MIP_Table\" set \"Status2\" = case when \"Status2\" like '%%Goofy%%' then \"Status2\" \
	when \"Status2\" like '%%Reappear%%' then \"Status2\" \
	when  ( get_most_recent_date_in_both_rows($1 ,$2) >= replace(\"Status2\",'Vanished-','')::DATE )then 'Vanished-' || replace(\"Status2\",'Vanished-','')::DATE || '. Reappear-' || get_most_recent_date_in_both_rows($1 ,$2) \
	else \"Status2\" end  \
	where  ( \"Row_ID\"=$1 ) AND \"Status2\" like 'Vanished%%'; \
	\
	update \"CMVP_MIP_Table\" set \"IUT_Start_Date\"= (	\
			select min(\"IUT_Start_Date\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
	\
	update \"CMVP_MIP_Table\" set \"Review_Pending_Start_Date\"= (	\
			select min(\"Review_Pending_Start_Date\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
		\
	update \"CMVP_MIP_Table\" set \"In_Review_Start_Date\"= (	\
			select min(\"In_Review_Start_Date\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
	\
	update \"CMVP_MIP_Table\" set \"Coordination_Start_Date\"= (	\
			select min(\"Coordination_Start_Date\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
		\
	update \"CMVP_MIP_Table\" set \"Finalization_Start_Date\"= (	\
			select min(\"Finalization_Start_Date\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
		\
	update \"CMVP_MIP_Table\" set \"Last_Updated\"= (	\
			select max(\"Last_Updated\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
			\
	\
	\
	\
	update \"CMVP_MIP_Table\" set \"Status\"= 'DELETE' where   \"Row_ID\"=$2  ;	\
	update \"CMVP_MIP_Table\" set \"Status\"= 'MERGED' where   \"Row_ID\"=$1  ;	\
	\
	select 1 as merge_2_dup_rows_with_no_TID2;	\
	\
$$	\
language SQL;	\
");
// USED TO BE "language SQL" directly above but didn't work with begin/end statements

sql_result = PQexec(conn, sql1);  //execute the create function command now
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			printf("\nError 106: SQL Create Function merge_rows_with_no_TID failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);	}
		else
		{
			if DEBUG printf("\nSUCCESS: added   'merge_rows_with_no_TID (bigint, bigint )'\n");
		}
//-------------------------------------------------------------------------------------------------



//---------------------------------------------------------------------------------------------------
		CLR_SQL1_STR

strfcat(sql1," 	\
	CREATE OR REPLACE FUNCTION merge_rows_with_same_TID (bigint, bigint ) returns integer 	\
	as $$	\
	update \"CMVP_MIP_Table\" set \"Module_Name\"= 	\
		(	\
			select \"Module_Name\" from \"CMVP_MIP_Table\" where \"TID\"= 	\
			   (case when (	(select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$1)<(select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$2) )	\
						then  (select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$2)	\
						else (select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$1)	\
						end  )	\
					AND 	\
						( 		\
						\"IUT_Start_Date\"=get_most_recent_date_in_both_rows ($1,$2)  OR	\
						\"Review_Pending_Start_Date\"=get_most_recent_date_in_both_rows ($1,$2) OR 	\
						\"In_Review_Start_Date\"=get_most_recent_date_in_both_rows ($1,$2) OR	\
						\"Coordination_Start_Date\"=(get_most_recent_date_in_both_rows ($1,$2)	\
						) 	\
		       ) limit 1	\
		)	\
	where   \"Row_ID\"=$1;	\
	update \"CMVP_MIP_Table\" set \"Vendor_Name\"= 	\
		(	\
			select \"Vendor_Name\" from \"CMVP_MIP_Table\" where \"TID\"= 	\
			   (case when (	(select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$1)<(select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$2) )	\
						then  (select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$2)	\
						else (select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$1)	\
						end  )	\
					AND 	\
						( 		\
						\"IUT_Start_Date\"=get_most_recent_date_in_both_rows ($1,$2)  OR	\
						\"Review_Pending_Start_Date\"=get_most_recent_date_in_both_rows ($1,$2) OR 	\
						\"In_Review_Start_Date\"=get_most_recent_date_in_both_rows ($1,$2) OR	\
						\"Coordination_Start_Date\"=(get_most_recent_date_in_both_rows ($1,$2)	\
						) 	\
		       ) limit 1	\
		)	\
	where   \"Row_ID\"=$1;	\
		\
	\
	update \"CMVP_MIP_Table\" set \"IUT_Start_Date\"= (	\
			select min(\"IUT_Start_Date\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
	\
	update \"CMVP_MIP_Table\" set \"Review_Pending_Start_Date\"= (	\
			select min(\"Review_Pending_Start_Date\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
		\
	update \"CMVP_MIP_Table\" set \"In_Review_Start_Date\"= (	\
			select min(\"In_Review_Start_Date\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
	\
	update \"CMVP_MIP_Table\" set \"Coordination_Start_Date\"= (	\
			select min(\"Coordination_Start_Date\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
		\
	update \"CMVP_MIP_Table\" set \"Finalization_Start_Date\"= (	\
			select min(\"Finalization_Start_Date\"::date) from \"CMVP_MIP_Table\" where 	\
			  ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	\
			  )	) 	\
	where  ( \"Row_ID\"=$1 );	\
		\
	\
	\
	update \"CMVP_MIP_Table\" set \"TID\"= 	\
		(	\
			select \"TID\" from \"CMVP_MIP_Table\" where \"TID\"= 	\
			   (case when (	(select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$1)<(select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$2) )	\
						then  (select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$2)	\
						else (select \"TID\"  from \"CMVP_MIP_Table\" where \"Row_ID\"=$1)	\
						end  )	\
					AND 	\
						( 		\
						\"IUT_Start_Date\"=get_most_recent_date_in_both_rows ($1,$2)  OR	\
						\"Review_Pending_Start_Date\"=get_most_recent_date_in_both_rows ($1,$2) OR 	\
						\"In_Review_Start_Date\"=get_most_recent_date_in_both_rows ($1,$2) OR	\
						\"Coordination_Start_Date\"=(get_most_recent_date_in_both_rows ($1,$2)	\
						) 	\
		       ) limit 1	\
		)	\
	where   \"Row_ID\"=$1;	\
	\
	\
	\
	update \"CMVP_MIP_Table\" set \"Status\"= 'DELETE' where   \"Row_ID\"=$2  ;	\
		\
	\
	select 1 as merge_2_dup_rows_with_same_TID;	\
	\
$$	\
language SQL;	\
");

if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179A: sql1 is too long. Increase SQL MAX size");
sql_result = PQexec(conn, sql1);  //execute the create function command now
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			printf("\nError 106: SQL Create Function failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);	}
		else
		{
			if DEBUG printf("SUCCESS: added   'merge_rows_with_same_TID (bigint, bigint )'\n");
		}


//---------------------------------------------------------------------------------------------------

CLR_SQL1_STR


strfcat(sql1,"\
CREATE OR REPLACE FUNCTION loop_to_merge_all_dups_with_same_TID ( ) RETURNS integer  	\
AS $$ DECLARE 	\
  inner_row  record; 		\
  outer_row record; 	\
BEGIN	\
FOR outer_row in (SELECT \"Row_ID\",\"TID\",\"Status\" FROM \"CMVP_MIP_Table\"  order by \"Row_ID\" asc ) LOOP	\
	if  (outer_row.\"TID\" is  not null) AND (outer_row.\"Status\" is null) then	\
			\
		FOR inner_row in (SELECT \"Row_ID\",\"TID\",\"Status\" FROM \"CMVP_MIP_Table\"  order by \"Row_ID\" asc ) LOOP	\
			if  (inner_row.\"TID\" is  not null) AND (inner_row.\"Status\" is null) then	\
				if  (inner_row.\"TID\"  = outer_row.\"TID\") AND (outer_row.\"Row_ID\" != inner_row.\"Row_ID\") then	\
					\
					if(select merge_rows_with_same_TID (	\
							case when (inner_row.\"Row_ID\" < outer_row.\"Row_ID\") then (inner_row.\"Row_ID\") else (outer_row.\"Row_ID\") end, /* smaller Row num */ 	\
							case when (inner_row.\"Row_ID\" < outer_row.\"Row_ID\") then (outer_row.\"Row_ID\") else (inner_row.\"Row_ID\") end  /* bigger Row num */ 	\
												        )	\
					   ) !=1 then   	/* return=1 means success */   \
						return(-2);	 /*merge_rows_with_same_TID failed */ \
					end if;	\
					\
				end if; 	\
			end if; 	\
		end loop; /* for inner_row */	\
	/*else */ 	/* outer_row is not null, but inner_row is null */  \
		/*return (-4); */ 	/* outer_row is not null, but inner_row is null */  \
	end if;  /*if out_row is not null */  \  
		\
end loop;  /*for outer_row*/   \
	\
delete from \"CMVP_MIP_Table\" where \"Status\"='DELETE';	\
	\
return(1);	\
	\
	\
END;	\
$$ LANGUAGE 'plpgsql';	\
");

printf("yankee: slq1= \n%s\n",sql1);

if(strlen(sql1) > SQL_MAX)
		printf("BIG eror 179B: sql1 is too long. Increase SQL MAX size");
sql_result = PQexec(conn, sql1);  //do delete
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			printf("\nError 266: SQL Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}
		else
		{
			if DEBUG printf("SUCCESS: added   'loop_to_merge_all_dups_with_same_TID ( )'\n");
		}

return(0);



//------------------------------------------------------------------------------------------------------
CLR_SQL1_STR

strfcat(sql1,"DROP FUNCTION loop_to_merge_all_dups_with_no_TID();");
sql_result = PQexec(conn, sql1);  //execute drop command
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK   ){  //7 is function not found. 
   		printf("\nError 55: SQL Drop Function loop_to_merge_all_dups_with_no_TID failed:  sql1=%s\n",sql1);
			PQclear(sql_result);
			printf("Ignoring Error 55:\n");
			//return (1);
		}
	else
	{
		if DEBUG printf("\nSUCCESS: dropped 'loop_to_merge_all_dups_with_no_TID();'\n");
	}

CLR_SQL1_STR

//only works for  lab-specific only data.  So, SQL table name is: CMVP_MIP_Table

strfcat(sql1,"\
CREATE or REPLACE FUNCTION loop_to_merge_all_dups_with_no_TID ( ) RETURNS integer  	\
AS $$ DECLARE 	\
  inner_row  record; 		\
  outer_row record; 	\
BEGIN	\
FOR outer_row in (SELECT \"Row_ID\",\"Module_Name\",\"Vendor_Name\",\"Status\" FROM \"CMVP_MIP_Table\"  order by \"Row_ID\" asc ) LOOP	\
	if  (outer_row.\"Status\" is null) then	\
			\
		FOR inner_row in (SELECT \"Row_ID\",\"Module_Name\",\"Vendor_Name\",\"Status\" FROM \"CMVP_MIP_Table\"  order by \"Row_ID\" asc ) LOOP	\
			if   (inner_row.\"Status\" is null) then		\
				if  (inner_row.\"Module_Name\"  = outer_row.\"Module_Name\") 	\
				AND (inner_row.\"Vendor_Name\"=outer_row.\"Vendor_Name\") 	\
				AND (outer_row.\"Row_ID\" != inner_row.\"Row_ID\") then	\
				 		\
					\
					if(select merge_rows_with_no_TID (		\
							case when (inner_row.\"Row_ID\" < outer_row.\"Row_ID\") then (inner_row.\"Row_ID\") else (outer_row.\"Row_ID\") end, /* smaller row num */		\
							case when (inner_row.\"Row_ID\" < outer_row.\"Row_ID\") then (outer_row.\"Row_ID\") else (inner_row.\"Row_ID\") end  /* bigger row num */		\
												        )		\
					   ) !=1 then   	/* return=1 means successful */ 	\
						return(-2);		\
					end if;		\
						\
				end if; 	\	
			end if; 		\
		end loop; 		\
	else 		\
		return (-4);		\
	end if;   	\
			\
end loop;    	\ 
		\
delete from \"CMVP_MIP_Table\" where \"Status\"='DELETE';		\
update \"CMVP_MIP_Table\" set \"Status\"= null ;		\
return(1);		\
END;	\
$$ LANGUAGE 'plpgsql';	\
");

//printf("zulu: sql1= %s", sql1);


sql_result = PQexec(conn, sql1);  //do delete
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			printf("\nError 266: SQL Command loop_to_merge_all_dups_with_no_TID failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}
		else
		{
			if DEBUG printf("\nSUCCESS: added   'loop_to_merge_all_dups_with_no_TID( )'\n");
		}

return(0);


} //create sql functions
