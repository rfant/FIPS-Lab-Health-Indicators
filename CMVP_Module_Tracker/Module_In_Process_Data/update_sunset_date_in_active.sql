UPDATE  "CMVP_Active_Table" SET "Sunset_Date" = TO_DATE(subquery."Sunset_Date" ,'MM/DD/YYYY')
from(	
	SELECT "CMVP_Permanent_Sunset_Table"."Sunset_Date", "CMVP_Permanent_Sunset_Table"."Cert_Num"from "CMVP_Permanent_Sunset_Table"   
inner JOIN "CMVP_Active_Table"  ON "CMVP_Permanent_Sunset_Table"."Cert_Num" =  "CMVP_Active_Table"."Cert_Num" 
	and "CMVP_Permanent_Sunset_Table"."Sunset_Date" not like 	'%1901-01-01%'
) 
 as subquery  where  "CMVP_Active_Table"."Cert_Num"=subquery."Cert_Num" 
 and "CMVP_Active_Table"."Sunset_Date" = '1901-01-01'
 