UPDATE  "CMVP_MIP_Table" SET "Finalization_Start_Date" = case  
	when subquery."Finalization_Start_Date" is null AND '7/28/2020'::date > subquery."Coordination_Start_Date"::date then '7/28/2020'::date  
	when '7/28/2020'::date < subquery."Finalization_Start_Date"  then '7/28/2020'::date  
	else subquery."Finalization_Start_Date"::date end  
from(	
	SELECT "CMVP_MIP_Table"."Module_Name","CMVP_MIP_Table"."TID", "CMVP_MIP_Table"."Coordination_Start_Date",  "CMVP_MIP_Table"."Finalization_Start_Date" ,
	 "CMVP_MIP_Table"."Lab_Name"from "CMVP_MIP_Table"  left  JOIN "Daily_CMVP_MIP_Table" ON "CMVP_MIP_Table"."Module_Name" = "Daily_CMVP_MIP_Table"."Module_Name"  
	 AND "CMVP_MIP_Table"."TID" = "Daily_CMVP_MIP_Table"."TID" where "Daily_CMVP_MIP_Table"."Module_Name" is null  AND "Daily_CMVP_MIP_Table"."TID" is null 	
	 ) 
as subquery  
where subquery."Coordination_Start_Date" is not null  
AND "CMVP_MIP_Table"."Coordination_Start_Date" is not null  
AND "CMVP_MIP_Table"."Module_Name"=subquery."Module_Name"  
AND "CMVP_MIP_Table"."TID"=subquery."TID" 