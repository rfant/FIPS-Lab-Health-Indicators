create function get_most_recent_date_in_both_rows ( bigint,   bigint ) returns date    
as $$	
select  (case	 
	when m0::date>=m1::date AND m0::date>=m2::date AND m0::date>=m3::date then m0 	
	when m1::date>=m2::date AND m1::date>=m3::date AND m1::date>=m0::date then m1	
	when m2::date>=m3::date AND m2::date>=m0::date AND m2::date>=m1::date then m2	
	when m3::date>=m0::date AND m3::date>=m1::date AND m3::date>=m2::date then m3  end ) 	
	as max_date		
		from 	
		( SELECT  case when max("IUT_Start_Date") is null then '1/1/1901' else max("IUT_Start_Date") end as m0,	
		 		  case when max("Review_Pending_Start_Date") is null then '1/1/1901' else max("Review_Pending_Start_Date") end as m1,	
		 		  case when max("In_Review_Start_Date") is null then '1/1/1901' else max("In_Review_Start_Date") end as m2,	
		 		  case when max("Coordination_Start_Date") is null then '1/1/1901' else max("Coordination_Start_Date") end as m3 	
		FROM "CMVP_MIP_Table"  where ("Row_ID"=$1 OR 	"Row_ID"=$2) 	
	) as subquery1 		
	
$$	
language SQL;	
-----------------------------------------------------------------------------------

--assumes Module_Name and Vendor_Name are the same
--
create function merge_rows_with_no_TID (bigint, bigint ) returns integer 	
	as $$	
	
	update "CMVP_MIP_Table" set "IUT_Start_Date"= (	
			select min("IUT_Start_Date"::date) from "CMVP_MIP_Table" where 	
			  ( "Row_ID"=$1  OR "Row_ID"=$2	
			  )	) 	
	where  ( "Row_ID"=$1 );	
	
	update "CMVP_MIP_Table" set "Review_Pending_Start_Date"= (	
			select min("Review_Pending_Start_Date"::date) from "CMVP_MIP_Table" where 	
			  ( "Row_ID"=$1  OR "Row_ID"=$2	
			  )	) 	
	where  ( "Row_ID"=$1 );	
		
	update "CMVP_MIP_Table" set "In_Review_Start_Date"= (	
			select min("In_Review_Start_Date"::date) from "CMVP_MIP_Table" where 	
			  ( "Row_ID"=$1  OR "Row_ID"=$2	
			  )	) 	
	where  ( "Row_ID"=$1 );	
	
	update "CMVP_MIP_Table" set "Coordination_Start_Date"= (	
			select min("Coordination_Start_Date"::date) from "CMVP_MIP_Table" where 	
			  ( "Row_ID"=$1  OR "Row_ID"=$2	
			  )	) 	
	where  ( "Row_ID"=$1 );	
		
	update "CMVP_MIP_Table" set "Finalization_Start_Date"= (	
			select min("Finalization_Start_Date"::date) from "CMVP_MIP_Table" where 	
			  ( "Row_ID"=$1  OR "Row_ID"=$2	
			  )	) 	
	where  ( "Row_ID"=$1 );	
		
	update "CMVP_MIP_Table" set "Last_Updated"= (	
			select max("Last_Updated"::date) from "CMVP_MIP_Table" where 	
			  ( "Row_ID"=$1  OR "Row_ID"=$2	
			  )	) 	
	where  ( "Row_ID"=$1 );	
			
	update "CMVP_MIP_Table" set "Status"= 'DELETE' where   "Row_ID"=$2  ;	
	update "CMVP_MIP_Table" set "Status"= 'MERGED' where   "Row_ID"=$1  ;	
	
	select 1 as merge_2_dup_rows_with_same_TID2;	
	
$$	
language SQL;
-----------------------------------------------------------------------------------

CREATE FUNCTION loop_to_merge_all_dups_with_no_TID ( ) RETURNS integer  	
AS $$ DECLARE 	
  inner_row  record; 		
  outer_row record; 	
BEGIN	
FOR outer_row in (SELECT "Row_ID","Module_Name","Vendor_Name","Status" FROM "CMVP_MIP_Table"  order by "Row_ID" asc ) LOOP	
	if  (outer_row."Status" is null) then	
			
		FOR inner_row in (SELECT "Row_ID","Module_Name","Vendor_Name","Status" FROM "CMVP_MIP_Table"  order by "Row_ID" asc ) LOOP	
			if   (inner_row."Status" is null) then	
				if  (inner_row."Module_Name"  = outer_row."Module_Name") 
				AND (inner_row."Vendor_Name"=outer_row."Vendor_Name") 
				AND (outer_row."Row_ID" != inner_row."Row_ID") then	
					
					if(select merge_rows_with_no_TID (	
							case when (inner_row."Row_ID" < outer_row."Row_ID") then (inner_row."Row_ID") else (outer_row."Row_ID") end,	--min
							case when (inner_row."Row_ID" < outer_row."Row_ID") then (outer_row."Row_ID") else (inner_row."Row_ID") end 	--max
												        )	
					   ) !=1 then   	--return=1 means success
						return(-2);	--something failed
					end if;	
					
				end if; 	
			end if; 	
		end loop; 	
	else 	
		return (-4);	
	end if;   
		
end loop;     
	
delete from "CMVP_MIP_Table" where "Status"='DELETE';	
update "CMVP_MIP_Table" set "Status"= null ;	
return(1);	
	
	
END;	
$$ LANGUAGE 'plpgsql';
