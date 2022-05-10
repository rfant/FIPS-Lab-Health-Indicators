select "Clean_Lab_Name",
TRUNC(AVG(abs("Finalization_Start_Date" -"In_Review_Start_Date")) )
as DaysInMip,
trunc(avg(abs("Finalization_Start_Date" - "Coordination_Start_Date"))) as DaysInCO
from "CMVP_MIP_Table" where "Review_Pending_Start_Date" between '2020-08-01' and '2022-01-30' 
and ("Status2" like '%Promoted%' OR "Status2" like '%Reappear%' OR "Status2" is null) 
and 	("In_Review_Start_Date" is not null AND  "Finalization_Start_Date" is not null)
Group by "Clean_Lab_Name" order by daysinmip 