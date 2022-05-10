select "Module_Name" , "SL","Module_Type" from "CMVP_MIP_Table" 


--sanity check
select t2."Status2",t1."Module_Name", t2."Cert_Num", t1."Vendor_Name",t2."Clean_Lab_Name",t2."SL" as act_sl,t1."SL" as mip_sl,
	t2."Module_Type" as act_mt,t1."Module_Type" as mip_mt 
 	from "CMVP_MIP_Table" as t1 inner join "CMVP_Active_Table" as t2 on  t1."Module_Name"=t2."Module_Name"
	and t1."Vendor_Name"=t2."Vendor_Name"  
	where t2."Status2" is null
	order by "Module_Name"


--new 		
UPDATE  "CMVP_MIP_Table" SET "Module_Type" = subquery."Module_Type" , "SL"=subquery."SL"
from(	SELECT "CMVP_MIP_Table"."Module_Name", "CMVP_MIP_Table"."Vendor_Name", "CMVP_Active_Table"."Module_Type","CMVP_Active_Table"."SL" from "CMVP_MIP_Table"   
inner JOIN "CMVP_Active_Table"  ON "CMVP_MIP_Table"."Module_Name" =  "CMVP_Active_Table"."Module_Name" 
and "CMVP_MIP_Table"."Vendor_Name" = "CMVP_Active_Table"."Vendor_Name" and "CMVP_Active_Table"."Status2" is null ) 
as subquery  where "CMVP_MIP_Table"."Module_Name"=subquery."Module_Name" 
and "CMVP_MIP_Table"."Vendor_Name"=subquery."Vendor_Name" 


--old 		
UPDATE  "CMVP_MIP_Table" SET "Lab_Name" = subquery."Lab_Name" 
from(	SELECT "CMVP_MIP_Table"."Module_Name", "CMVP_MIP_Table"."Vendor_Name", "CMVP_Active_Table"."Lab_Name" from "CMVP_MIP_Table"   
inner JOIN "CMVP_Active_Table"  ON "CMVP_MIP_Table"."Module_Name" =  "CMVP_Active_Table"."Module_Name" 
and "CMVP_MIP_Table"."Vendor_Name" = "CMVP_Active_Table"."Vendor_Name" ) 
as subquery  where  "CMVP_MIP_Table"."Module_Name"=subquery."Module_Name" and "CMVP_MIP_Table"."Vendor_Name"=subquery."Vendor_Name" 




