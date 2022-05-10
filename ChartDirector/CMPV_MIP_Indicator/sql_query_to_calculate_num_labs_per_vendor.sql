select "Vendor_Name",count(distinct "Lab_Name") as num_labs, count(distinct "Module_Name") as num_mods
from "CMVP_Active_Table" --where "SL"=2 and "Module_Type" like '%Hardware%'
group by "Vendor_Name"
having 
count(distinct "Lab_Name") <=2
order by "Vendor_Name"