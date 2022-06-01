
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
// .  So, SQL table name is: CMVP_Active_Table
//
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




CLR_SQL1_STR
//  So, SQL table name is: CMVP_Active_Table
strfcat(sql1," 	\
create or replace  function get_most_recent_date_in_both_rows ( bigint,   bigint ) returns date    \
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
		FROM \"CMVP_Active_Table\"  where (\"Row_ID\"=$1 OR 	\"Row_ID\"=$2) 	\
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
			if DEBUG printf("SUCCESS: added   'get_most_recent_date_in_both_rows ( bigint,   bigint )'\n");
		}

//-------------------------------------------------------------------------------------------------



CLR_SQL1_STR
//only works for Active_Table data.  So, SQL table name is: CMVP_Active_Table

strfcat(sql1," 	create or replace  function merge_rows_with_same_Cert_Num (bigint, bigint ) returns integer ");	
	strfcat(sql1," as $$	 ");
	strfcat(sql1," update \"CMVP_Active_Table\" set \"Sunset_Date\"= (	 ");
			strfcat(sql1," select max(\"Sunset_Date\"::date) from \"CMVP_Active_Table\" where 	 ");
			  strfcat(sql1," ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	 ");
			  strfcat(sql1," )	) 	 ");
	strfcat(sql1," where  ( \"Row_ID\"=$1 );	 ");
	strfcat(sql1," update \"CMVP_Active_Table\" set \"Validation_Date\"= (	 ");
			strfcat(sql1," select min(\"Review_Pending_Start_Date\"::date) from \"CMVP_Active_Table\" where 	 ");
			  strfcat(sql1," ( \"Row_ID\"=$1  OR \"Row_ID\"=$2	 ");
			  strfcat(sql1," )	) 	 ");
	strfcat(sql1," where  ( \"Row_ID\"=$1 );	 ");
	strfcat(sql," select 1 as merge_2_dup_rows_with_same_Cert_Num;");
strfcat(sql1," $$	 ");
strfcat(sql1," language SQL;	 ");


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

/*CLR_SQL1_STR

strfcat(sql1,"DROP FUNCTION loop_to_merge_all_dups ();");
sql_result = PQexec(conn, sql1);  //execute drop command
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK   ){  //7 is function not found. 
   			printf("\nError 55: SQL Drop Function 'loop_to_merge_all_dups();' failed:  sql1=%s\n",sql1);
			PQclear(sql_result);
			printf("Ignoring Error 55:\n");
			//return (1);
		}
	else
	{
		if DEBUG printf("SUCCESS: dropped 'loop_to_merge_all_dups();'\n");
	}
*/
CLR_SQL1_STR

//  So, SQL table name is: CMVP_Active_Table

strfcat(sql1,"\
create or replace FUNCTION loop_to_merge_all_dups ( ) RETURNS integer  	\
AS $$ DECLARE 	\
  inner_row  record; 		\
  outer_row record; 	\
BEGIN	\
FOR outer_row in (SELECT \"Row_ID\",\"Cert_Num\",\"Status\" FROM \"CMVP_Active_Table\"  order by \"Row_ID\" asc ) LOOP	\
	if  (outer_row.\"Cert_Num\" is  not null) AND (outer_row.\"Status\" is null) then	\
			\
		FOR inner_row in (SELECT \"Row_ID\",\"Cert_Num\",\"Status\" FROM \"CMVP_Active_Table\"  order by \"Row_ID\" asc ) LOOP	\
			if  (inner_row.\"Cert_Num\" is  not null) AND (inner_row.\"Status\" is null) then	\
				if  (inner_row.\"Cert_Num\"  = outer_row.\"Cert_Num\") AND (outer_row.\"Row_ID\" != inner_row.\"Row_ID\") then	\
					\
					if(select merge_rows_with_same_Cert_Num (	\
							case when (inner_row.\"Row_ID\" < outer_row.\"Row_ID\") then (inner_row.\"Row_ID\") else (outer_row.\"Row_ID\") end, /* smaller Row num */ 	\
							case when (inner_row.\"Row_ID\" < outer_row.\"Row_ID\") then (outer_row.\"Row_ID\") else (inner_row.\"Row_ID\") end  /* bigger Row num */ 	\
												        )	\
					   ) !=1 then   	/* return=1 means success */   \
						return(-2);	\
					end if;	\
					\
				end if; 	\
			end if; 	\
		end loop; 	\
	else 	\
		return (-4);	\
	end if;   \
		\
end loop;     \
	\
delete from \"CMVP_Active_Table\" where \"Status\"='DELETE';	\
	\
return(1);	\
	\
	\
END;	\
$$ LANGUAGE 'plpgsql';	\
");

printf("yankee: slq1= %s\n",sql1);

sql_result = PQexec(conn, sql1);  //do delete
	if (PQresultStatus(sql_result) != PGRES_COMMAND_OK)  {
   			printf("\nError 266: SQL Command failed: sql1=%s\n",sql1);
			PQclear(sql_result);
			return (1);}
		else
		{
			if DEBUG printf("SUCCESS: added   'loop_to_merge_all_dups ( )'\n");
		}

return(0);


} //create sql functions
