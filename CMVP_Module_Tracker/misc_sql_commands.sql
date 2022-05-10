--drop function get_dup_rows ();
--DROP FUNCTION merge_rows_with_same_TID ( );
--DROP FUNCTION loop_merge_2_dup_rows_with_same_TID ( );
--drop function get_most_recent_date_in_both_rows2 ( );

----------------
-- Date calcuations

select "Vendor_Name","Module_Name","TID","Review_Pending_Start_Date","In_Review_Start_Date","Coordination_Start_Date"
from "CMVP_atsec_Only_MIP_Table" where "Coordination_Start_Date" is not null AND "Finalization_Start_Date" is null 
and (SELECT CURRENT_DATE) - "Coordination_Start_Date"  < 365

------------------------------------------------------------------------------------------------
UPDATE  "CMVP_MIP_Table" SET "Finalization_Start_Date" = case  
when subquery."Finalization_Start_Date" is null AND '7/28/2020'::date > subquery."Coordination_Start_Date"::date then '7/28/2020'::date  
when '7/28/2020'::date < subquery."Finalization_Start_Date"  then '7/28/2020'::date  
else subquery."Finalization_Start_Date"::date end  
from( 
    SELECT "CMVP_MIP_Table"."Module_Name","CMVP_MIP_Table"."TID", "CMVP_MIP_Table"."Coordination_Start_Date",  "CMVP_MIP_Table"."Finalization_Start_Date" , 
    "CMVP_MIP_Table"."Lab_Name"from "CMVP_MIP_Table"  left  JOIN "Daily_CMVP_MIP_Table" ON "CMVP_MIP_Table"."Module_Name" = "Daily_CMVP_MIP_Table"."Module_Name"  
    AND "CMVP_MIP_Table"."TID" = "Daily_CMVP_MIP_Table"."TID" where "Daily_CMVP_MIP_Table"."Module_Name" is null  
    AND "Daily_CMVP_MIP_Table"."TID" is null  
  ) as subquery  where subquery."Coordination_Start_Date" is not null  
AND "CMVP_MIP_Table"."Coordination_Start_Date" is not null  
AND "CMVP_MIP_Table"."Module_Name"=subquery."Module_Name"  AND "CMVP_MIP_Table"."TID"=subquery."TID"  

-------------------------------------------------------------------------------------------------
-- if there is a TID in atsec_Only, then add that TID to CMVP_MIP_Table. If no TID then set it to NULL

UPDATE  "CMVP_MIP_Table" SET "TID" = case  when subquery."TID" is not null  then subquery."TID"  else 'NULL' end  
from( SELECT "TID"   from "CMVP_atsec_Only_MIP_Table" where "Vendor_Name" like 'eWBM' and "Module_Name" like 'MS1201 Security Sub-system'
) as subquery
where "CMVP_MIP_Table"."TID" is null AND "CMVP_MIP_Table"."Vendor_Name" like 'eWBM' and  "CMVP_MIP_Table"."Module_Name" like 'MS1201 Security Sub-system'; 
 
 UPDATE  "CMVP_MIP_Table" SET "Lab_Name" = case  when "CMVP_MIP_Table"."TID" is not null  then 'atsec'  else 'NULL' end  
;
    
--------------------------------------------------------------------------------------------------------------------------------------------
create function get_dup_rows2 () returns table (row1 int)  --drop funtion get_dup_rows2 ()
--get first two row ID's where the rows have the same first 7 characters.  e.g. "11-1555"  and "11-1555-1543"
as $$

SELECT  longName."Row_ID"
FROM "CMVP_MIP_Table" longName INNER JOIN "CMVP_MIP_Table" shortName 
ON left(longName."TID",7) = shortName."TID" order by shortName."Row_ID" asc limit 2



$$
language SQL;

-----------------------------------------------------------------------------------------------------------------------------------------------
delete from "CMVP_Active_Table" where
"Row_ID"=
(
  select * --min(subquery."Row_ID")
    --delete 
  from (
    SELECT  longName."Row_ID" --,longName."Cert_Num"
    FROM "CMVP_Active_Table" longName INNER JOIN "CMVP_Active_Table" shortName 
    ON longName."Cert_Num" = shortName."Cert_Num" order by shortName."Row_ID" 
    asc limit 2
  )as subquery
  order by "Row_ID" asc limit 1
)

----------------------------------------------------------------------------------------------------------------------------------------------

-----------------------------------------------------

--Find duplicate rows

SELECT "Module_Name","Vendor_Name", count(*) FROM "CMVP_MIP_Table"
GROUP BY "Module_Name","Vendor_Name"  HAVING count(*) > 1;
------------------------------------
CREATE OR REPLACE FUNCTION myfunction(integer) 
RETURNS integer AS $$
  DECLARE
   nm ALIAS FOR $1;
    cub INTEGER;
  BEGIN
   cub:=nm;
    WHILE cub <=10000 LOOP
      cub := cub * cub * cub;
    END LOOP;
    RETURN cub;
  END;
$$ LANGUAGE 'plpgsql';

-------------------------
CREATE TABLE new_table AS SELECT * FROM existing_table

--------------------------------------
CREATE  FUNCTION add_em(integer, integer) RETURNS integer AS $$
    SELECT $1 + $2;
	select left("TID",2)::integer  FROM "CMVP_MIP_Table"  where ("Row_ID"=$1 OR 	"Row_ID"=$2) ;
$$ LANGUAGE SQL;
---




-----------------------------------------------------
--fully active atsec cert with all prelim Only/historic data

SELECT alpha."TID",bravo."Cert_Num",bravo."Sunset_Date",bravo."Validation_Date",bravo."Standard",bravo."Module_Name",bravo."Vendor_Name",bravo."Lab_Name"
from "CMVP_atsec_Only_MIP_Table" as alpha left join "CMVP_Active_Table" as bravo on
alpha."Module_Name"=bravo."Module_Name" where alpha."Vendor_Name"=bravo."Vendor_Name"

--------------------------------------
-- combon of above two sections to get both historic and active information for atsec modules for just 3subs and 5subs (>4)
SELECT "TID","Cert_Num","Module_Name","Vendor_Name","Review_Pending_Start_Date" as RP,"In_Review_Start_Date" as IR,"Coordination_Start_Date" as CO,"Finalization_Start_Date" as Final,"Validation_Date" ,"Sunset_Date",
  DATE_PART('year', "Sunset_Date"::date) - DATE_PART('year', right("Validation_Date",10)::date) 
  as Years_Cert_Life,
    (DATE_PART('year', "In_Review_Start_Date"::date) - DATE_PART('year', "Review_Pending_Start_Date"::date)) * 12 
    +(DATE_PART('month', "In_Review_Start_Date"::date) - DATE_PART('month', "Review_Pending_Start_Date"::date)) 
  as Mon_in_RP,
    (DATE_PART('year', "Coordination_Start_Date"::date) - DATE_PART('year', "In_Review_Start_Date"::date)) * 12 
    +(DATE_PART('month', "Coordination_Start_Date"::date) - DATE_PART('month', "In_Review_Start_Date"::date)) 
  as Mon_in_IR,
    (DATE_PART('year', "Finalization_Start_Date"::date) - DATE_PART('year', "Coordination_Start_Date"::date)) * 12 
    +(DATE_PART('month', "Finalization_Start_Date"::date) - DATE_PART('month', "Coordination_Start_Date"::date)) 
  as Months_in_CO,
    (DATE_PART('year', "Finalization_Start_Date"::date) - DATE_PART('year', "Review_Pending_Start_Date"::date)) * 12 
    +(DATE_PART('month', "Finalization_Start_Date"::date) - DATE_PART('month', "Review_Pending_Start_Date"::date)) 
  as Mon_Total_F_to_RP, 
    (DATE_PART('year', right("Validation_Date",10)::date) - DATE_PART('year', "Review_Pending_Start_Date"::date)) * 12 
    +(DATE_PART('month', right("Validation_Date",10)::date) - DATE_PART('month', "Review_Pending_Start_Date"::date)) 
  as Mon_Total_V_to_RP
from (/* combined atsec historical with atsec active information */
  SELECT alpha."TID",bravo."Cert_Num",bravo."Sunset_Date",bravo."Validation_Date",bravo."Standard",bravo."Module_Name",bravo."Vendor_Name",bravo."Lab_Name",alpha."Review_Pending_Start_Date","In_Review_Start_Date",
  "Coordination_Start_Date","Finalization_Start_Date"
  from "CMVP_atsec_Only_MIP_Table" as alpha left join "CMVP_Active_Table" as bravo on
  alpha."Module_Name"=bravo."Module_Name" where alpha."Vendor_Name"=bravo."Vendor_Name"
) as subquery
where 1=1
--AND subquery."Finalization_Start_Date" is not null 
AND subquery."Review_Pending_Start_Date" is not null 
AND subquery."In_Review_Start_Date" is not null 

and (DATE_PART('year', "Sunset_Date"::date) - DATE_PART('year', right("Validation_Date",10)::date)  )> 4
order by mon_in_rp desc

================================================================
========Demo List of SQL Queries  as of march 5, 2021 ==========
================================================================
========================================================================================================================
=========== INDICATOR SQL FUNCTIONS=====================================================


--how many active certs do all labs  have in total today?

select "Lab_Name", count(*) as Num_Certs FROM "CMVP_Active_Table" GROUP BY "Lab_Name" order by Num_Certs desc

--atsec has many names. What if we combine them, truncate the lab name to just the first word
select  UPPER((string_to_array("Lab_Name", ' '))[1]) as Lab, count(*) as Num_Certs  from "CMVP_Active_Table" Group by Lab order by Num_Certs desc;

--how many new certs issued did every lab (including atsec) receive in 2020?
select "Lab_Name", count(*) as Num_Certs 
FROM "CMVP_Active_Table" where "Sunset_Date" <='2025-12-31' and "Sunset_Date" >= '2025-01-01' GROUP BY "Lab_Name"   order by Num_Certs desc

--what are the specific new certs atsec received in 2020

select "Lab_Name" , "Cert_Num" , "Module_Name","Vendor_Name","Status", "Sunset_Date" FROM "CMVP_Active_Table" where LOWER("Lab_Name") like LOWER('%atsec%') and
"Sunset_Date" <='2025-12-31' and "Sunset_Date" >= '2025-01-01' 
order by "Sunset_Date"

==========================================================

--How many "Review Pending" atsec modules are there?
select * from "CMVP_atsec_Only_MIP_Table" 
where   "Review_Pending_Start_Date" is not null AND "In_Review_Start_Date" is  null AND "Coordination_Start_Date" is null and "Finalization_Start_Date" is null
and "Status2"  is null order by "TID"
 
 
--How many "Review Pending" non_atsec modules are there?

select "Status2","Vendor_Name","Module_Name"
from "CMVP_MIP_Table" where "Review_Pending_Start_Date" is not null AND "In_Review_Start_Date" is null AND "Coordination_Start_Date" is null and "Finalization_Start_Date" is null
and ("Status2" like '%Reappear%'  OR "Status2" is NULL OR "Status2"  like 'DUP%' ) 
order by "Vendor_Name", "Module_Name"
 ====================================================
--How many "In Review" atsec modules are there?

select * from "CMVP_atsec_Only_MIP_Table" 
where   "In_Review_Start_Date" is not null  AND "Coordination_Start_Date" is null and "Finalization_Start_Date" is null
and "Status2"  is null order by "TID"
 
--How many "In Review" non_atsec modules are there?

select "Status2","Vendor_Name","Module_Name"
from "CMVP_MIP_Table" where "In_Review_Start_Date" is not null AND "Coordination_Start_Date" is null and "Finalization_Start_Date" is null
and ("Status2" like '%Reappear%'  OR "Status2" is NULL OR "Status2"  like 'DUP%') 
order by "Vendor_Name", "Module_Name"
 =======================================================  
        
 --How many "Coordination" atsec modules are there?

select * from "CMVP_atsec_Only_MIP_Table" 
where   "Coordination_Start_Date" is not null  AND  "Finalization_Start_Date" is null
and "Status2"  is null order by "TID"

--How many "Coordination" non_atsec modules are there?

select "Status2","Vendor_Name","Module_Name"
from "CMVP_MIP_Table" where "Coordination_Start_Date" is not null AND "Finalization_Start_Date" is null 
and ("Status2" like '%Reappear%'  OR "Status2" is NULL OR "Status2" like 'DUP%') 
order by "Vendor_Name", "Module_Name"

    
========================================================================================
-- list of number of days each individual atsec modules spent in Coordination   for the date range listed
SELECT "Lab_Name","Status2","TID","Module_Name","Vendor_Name","Coordination_Start_Date","Finalization_Start_Date", 
"Finalization_Start_Date"::date -  "Coordination_Start_Date"::date as Days_Coor
  from "CMVP_atsec_Only_MIP_Table" 
where ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
and  "Finalization_Start_Date" is not null and "Coordination_Start_Date" is not null
and  "Finalization_Start_Date" <= (select CURRENT_DATE) AND "Finalization_Start_Date" >= (Select current_Date) - interval '1 years' 
and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= (Select current_Date) - interval '1 years' 
and "Coordination_Start_Date" < "Finalization_Start_Date"
order by "Module_Name" desc --Months_Total desc

-- list of number of days each individual atsec modules spent In Review   for the date range listed
SELECT "Lab_Name","Status2","TID","Module_Name","Vendor_Name","In_Review_Start_Date","Coordination_Start_Date", 
 "Coordination_Start_Date"::date - "In_Review_Start_Date"::date as Days_In_Review
  from "CMVP_atsec_Only_MIP_Table" 
where ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
and  "Coordination_Start_Date" is not null and "In_Review_Start_Date" is not null
and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= (Select current_Date) - interval '1 years' 
and  "In_Review_Start_Date" <= (select CURRENT_DATE) AND "In_Review_Start_Date" >= (Select current_Date) - interval '1 years' 
and "In_Review_Start_Date" < "Coordination_Start_Date"
order by "Module_Name" desc --Months_Total desc


-- list of number of days each individual atsec modules spend Review Pending   for the date range listed
SELECT "Lab_Name","Status2","TID","Module_Name","Vendor_Name","Review_Pending_Start_Date","In_Review_Start_Date", 
 "In_Review_Start_Date"::date -  "Review_Pending_Start_Date"::date as Days_Review_Pend
  from "CMVP_atsec_Only_MIP_Table" 
where ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
and  "In_Review_Start_Date" is not null and "Review_Pending_Start_Date" is not null
and  "In_Review_Start_Date" <= (select CURRENT_DATE) AND "In_Review_Start_Date" >= (Select current_Date) - interval '1 years' 
and  "Review_Pending_Start_Date" <= (select CURRENT_DATE) AND "Review_Pending_Start_Date" >= (Select current_Date) - interval '1 years' 
and "Review_Pending_Start_Date" < "In_Review_Start_Date"
order by "Module_Name" desc --Months_Total desc


-- Comprehensive average of days for all atsec modules in each state for the date range listed
WITH sumsRF AS 
(  
SELECT "Lab_Name",
(select round(cast(avg( "In_Review_Start_Date"::date -  "Review_Pending_Start_Date"::date ) as numeric),2) as Avg_Days_Review_Pending from "CMVP_atsec_Only_MIP_Table" 
    where ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
    and  "In_Review_Start_Date" is not null and "Review_Pending_Start_Date" is not null
    and  "In_Review_Start_Date" <= (select CURRENT_DATE) AND "In_Review_Start_Date" >= (Select current_Date) - interval '1 years'
    and  "Review_Pending_Start_Date" <= (select CURRENT_DATE) AND "Review_Pending_Start_Date" >= (Select current_Date) - interval '1 years'
    and "Review_Pending_Start_Date" < "In_Review_Start_Date" ),
(select round(cast(avg( "Coordination_Start_Date"::date -  "In_Review_Start_Date"::date ) as numeric),2) as Avg_Days_In_Review from "CMVP_atsec_Only_MIP_Table" 
    where ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
    and  "Coordination_Start_Date" is not null and "In_Review_Start_Date" is not null
    and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= (Select current_Date) - interval '1 years' 
    and  "In_Review_Start_Date" <= (select CURRENT_DATE) AND "In_Review_Start_Date" >= (Select current_Date) - interval '1 years' 
    and "In_Review_Start_Date" < "Coordination_Start_Date" ),       
(select round(cast(avg( "Finalization_Start_Date"::date -  "Coordination_Start_Date"::date ) as numeric),2)as Avg_Days_Coor from "CMVP_atsec_Only_MIP_Table"  
   where ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
    and  "Finalization_Start_Date" is not null and "Coordination_Start_Date" is not null
    and  "Finalization_Start_Date" <= (select CURRENT_DATE) AND "Finalization_Start_Date" >= (Select current_Date) - interval '1 years' 
    and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= (Select current_Date) - interval '1 years' 
    and "Coordination_Start_Date" < "Finalization_Start_Date")
from "CMVP_atsec_Only_MIP_Table"  where ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
group by "Lab_Name"
) 
SELECT 
   "Lab_Name",Avg_Days_Review_Pending , Avg_Days_In_Review, Avg_Days_Coor,
   Avg_Days_Review_Pending + Avg_Days_In_Review +avg_days_coor    AS Total_Days
FROM 
   sumsRF;
 

 --List the numbers of days that each module actually took to get from Review_Pending_Start_Date to Coordination_Start_Date
SELECT "Lab_Name","Status2","TID","Module_Name","Vendor_Name","Review_Pending_Start_Date","Coordination_Start_Date", 
 "Coordination_Start_Date"::date -  "Review_Pending_Start_Date"::date as Days_RP_To_CO
  from "CMVP_atsec_Only_MIP_Table" 
where ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
and  "Coordination_Start_Date" is not null and "Review_Pending_Start_Date" is not null
and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= (Select current_Date) - interval '1 years' 
and  "Review_Pending_Start_Date" <= (select CURRENT_DATE) AND "Review_Pending_Start_Date" >= (Select current_Date) - interval '1 years' 
and "Review_Pending_Start_Date" < "Coordination_Start_Date" and "In_Review_Start_Date" is not null
order by  Days_RP_To_CO desc


 --- What is the averagee amount time for all atsec  module to go from Review_Pending_Start_Date to Coordination_Start_Date
 select round(cast(avg( "Coordination_Start_Date"::date -  "Review_Pending_Start_Date"::date ) as numeric),2) as Avg_Days_RP_to_CO from "CMVP_atsec_Only_MIP_Table" 
    where ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
    and  "Coordination_Start_Date" is not null and "Review_Pending_Start_Date" is not null
    and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= (Select current_Date) - interval '1 years'
    and  "Review_Pending_Start_Date" <= (select CURRENT_DATE) AND "Review_Pending_Start_Date" >= (Select current_Date) - interval '1 years'
    and "Review_Pending_Start_Date" < "Coordination_Start_Date" and "In_Review_Start_Date" is not null


create or replace  function get_RP_CO_avg_from_past (date,date) returns INTERVAL as $$
 --$1 is the newer date.  $2 is the older date
 select (round(cast(avg( "Coordination_Start_Date"::date -  "Review_Pending_Start_Date"::date ) as numeric),2)::integer || ' days')::interval from "CMVP_atsec_Only_MIP_Table" 
    where ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
    and  "Coordination_Start_Date" is not null and "Review_Pending_Start_Date" is not null
    and  "Coordination_Start_Date" <= $1 AND "Coordination_Start_Date" >= $2
    and  "Review_Pending_Start_Date" <= $1 AND "Review_Pending_Start_Date" >= $2
    and "Review_Pending_Start_Date" < "Coordination_Start_Date" and "In_Review_Start_Date" is not null
$$ language sql;

--here's how to invoke the function
select get_RP_CO_avg_from_past ( (select CURRENT_DATE)::date,(select CURRENT_DATE - interval '1 year')::date)


-- what is the atsec Only forecast until Coordination Starts? (check how accurate my predications are!) 
select "Status2", "TID","Module_Name","Vendor_Name","Review_Pending_Start_Date" as RP, "Coordination_Start_Date" as Act_CO  ,
"Review_Pending_Start_Date" + INTERVAL '216 days' as Pred_CO ,
abs(("Review_Pending_Start_Date" + INTERVAL '216 days')::date -"Coordination_Start_Date") as Delta_Pred_Act
from "CMVP_atsec_Only_MIP_Table" where "Review_Pending_Start_Date" is not null and "Coordination_Start_Date" is not null 
and "Coordination_Start_Date" <=(Select CURRENT_DATE) and "Coordination_Start_Date" >= (Select current_Date) - interval '1 years'
and "Review_Pending_Start_Date" <=(Select CURRENT_DATE) and "Review_Pending_Start_Date" >= (Select current_Date) - interval '1 years' 
and "Status2" not like 'Vanished%' and "Review_Pending_Start_Date" < "Coordination_Start_Date"
and ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
order by delta_pred_act


--replace 216 with a function call from today to today-1 year
-- what is the atsec Only forecast until Coordination Starts? (check how accurate my predications are!) 
select "Status2", "TID","Module_Name","Vendor_Name","Review_Pending_Start_Date" as RP, "Coordination_Start_Date" as Act_CO  ,
"Review_Pending_Start_Date" + get_RP_CO_avg_from_past ( (select CURRENT_DATE)::date,(select CURRENT_DATE - interval '1 year')::date) as Pred_CO ,
abs(("Review_Pending_Start_Date" + get_RP_CO_avg_from_past ( (select CURRENT_DATE)::date,(select CURRENT_DATE - interval '1 year')::date))::date -"Coordination_Start_Date") as Delta_Pred_Act
from "CMVP_atsec_Only_MIP_Table" where "Review_Pending_Start_Date" is not null and "Coordination_Start_Date" is not null 
and "Coordination_Start_Date" <=(Select CURRENT_DATE) and "Coordination_Start_Date" >= (Select current_Date) - interval '1 years'
and "Review_Pending_Start_Date" <=(Select CURRENT_DATE) and "Review_Pending_Start_Date" >= (Select current_Date) - interval '1 years' 
and "Status2" not like 'Vanished%' and "Review_Pending_Start_Date" < "Coordination_Start_Date"
and ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
order by delta_pred_act

--hard code the date-range
-- what is the atsec Only forecast until Coordination Starts? (check how accurate my predications are!) 
select "Status2", "TID","Module_Name","Vendor_Name","Review_Pending_Start_Date" as RP, "Coordination_Start_Date" as Act_CO  ,
"Review_Pending_Start_Date" + get_RP_CO_avg_from_past ('3/10/21','3/10/20') as Pred_CO ,
abs(("Review_Pending_Start_Date" + get_RP_CO_avg_from_past ( '3/10/21','3/10/20'))::date -"Coordination_Start_Date") as Delta_Pred_Act
from "CMVP_atsec_Only_MIP_Table" where "Review_Pending_Start_Date" is not null and "Coordination_Start_Date" is not null 
and "Coordination_Start_Date" <='3/10/21' and "Coordination_Start_Date" >= '3/10/20'
and "Review_Pending_Start_Date" <='3/10/21' and "Review_Pending_Start_Date" >= '3/10/20'
and "Status2" not like 'Vanished%' and "Review_Pending_Start_Date" < "Coordination_Start_Date"
and ("Status2" like '%Promoted%'   OR "Status2" like '%Reappear%' OR "Status2" is null) 
order by delta_pred_act
=======================================================================================
-- list of number of days non-atsec modules spent in Coordination for the date range listed

SELECT bravo."Lab_Name", bravo."Cert_Num", bravo."Module_Name",bravo."Vendor_Name",  "Coordination_Start_Date","Finalization_Start_Date",
 "Finalization_Start_Date"::date  -  "Coordination_Start_Date"::date as Days_Coor
  from "CMVP_MIP_Table" as alpha left join "CMVP_Active_Table" as bravo on
  alpha."Module_Name"=bravo."Module_Name" and alpha."Vendor_Name"=bravo."Vendor_Name" 
  where bravo."Status2" is null and (alpha."Status2" not like 'DUP%' or alpha."Status2" is null)
  and bravo."Lab_Name" not like 'atsec%' AND bravo."Lab_Name" not like 'Atsec%' AND bravo."Lab_Name" not like 'ATSEC%'
  and  "Finalization_Start_Date" is not null and "Coordination_Start_Date" is not null
and  "Finalization_Start_Date" <= (select CURRENT_DATE) AND "Finalization_Start_Date" >= '2020-03-05'
and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= '2020-03-05'
and "Coordination_Start_Date" < "Finalization_Start_Date"
  order by bravo."Lab_Name"

-- average number of days non-atsec labs spent in Coordination for the date range listed by each individual lab
SELECT bravo."Lab_Name", round(cast(avg("Finalization_Start_Date"::date - "Coordination_Start_Date"::date ) as numeric),2)
as Avg_Days_Coor 
  from "CMVP_MIP_Table" as alpha left join "CMVP_Active_Table" as bravo on
  alpha."Module_Name"=bravo."Module_Name" and alpha."Vendor_Name"=bravo."Vendor_Name" 
  where bravo."Status2" is null and (alpha."Status2" not like 'DUP%' or alpha."Status2" is null)
  and bravo."Lab_Name" not like 'atsec%' AND bravo."Lab_Name" not like 'Atsec%' AND bravo."Lab_Name" not like 'ATSEC%'
  and  "Finalization_Start_Date" is not null and "Coordination_Start_Date" is not null
and  "Finalization_Start_Date" <= (select CURRENT_DATE) AND "Finalization_Start_Date" >= '2020-03-05'
and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= '2020-03-05'
and "Coordination_Start_Date" < "Finalization_Start_Date"
group by bravo."Lab_Name"
  
  
------------------------------------------------------------------------------------------------------------------
-- list of number of days each individual non-atsec modules spent In Review   for the date range listed
SELECT bravo."Lab_Name", bravo."Module_Name",bravo."Vendor_Name",  "In_Review_Start_Date","Coordination_Start_Date",
 "Coordination_Start_Date"::date -  "In_Review_Start_Date"::date as Days_In_Review
  from "CMVP_MIP_Table" as alpha left join "CMVP_Active_Table" as bravo on
  alpha."Module_Name"=bravo."Module_Name" and alpha."Vendor_Name"=bravo."Vendor_Name" 
  where bravo."Status2" is null and (alpha."Status2" not like 'DUP%' or alpha."Status2" is null)
  and bravo."Lab_Name" not like 'atsec%' AND bravo."Lab_Name" not like 'Atsec%' AND bravo."Lab_Name" not like 'ATSEC%'
  and  "Coordination_Start_Date" is not null and "In_Review_Start_Date" is not null
and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= '2020-03-05'
and  "In_Review_Start_Date" <= (select CURRENT_DATE) AND "In_Review_Start_Date" >= '2020-03-05'
and "In_Review_Start_Date" < "Coordination_Start_Date"
  order by bravo."Lab_Name"
 
-- average number of days non-atsec labs spent In Review for the date range listed by each individual lab
SELECT bravo."Lab_Name", round(cast(avg("Coordination_Start_Date"::date - "In_Review_Start_Date"::date ) as numeric),2)
as Avg_Days_In_Review 
  from "CMVP_MIP_Table" as alpha left join "CMVP_Active_Table" as bravo on
  alpha."Module_Name"=bravo."Module_Name" and alpha."Vendor_Name"=bravo."Vendor_Name" 
  where bravo."Status2" is null and (alpha."Status2" not like 'DUP%' or alpha."Status2" is null)
  and bravo."Lab_Name" not like 'atsec%' AND bravo."Lab_Name" not like 'Atsec%' AND bravo."Lab_Name" not like 'ATSEC%'
  and  "Coordination_Start_Date" is not null and "In_Review_Start_Date" is not null
and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= '2020-03-05'
and  "In_Review_Start_Date" <= (select CURRENT_DATE) AND "In_Review_Start_Date" >= '2020-03-05'
and "In_Review_Start_Date" < "Coordination_Start_Date"
group by bravo."Lab_Name"
    
-----------------------------------------------------------------------------------------
-- list of number of days each individual non-atsec modules spend Review_Pending   for the date range listed
SELECT bravo."Lab_Name", bravo."Standard",bravo."Module_Name",bravo."Vendor_Name",  "Review_Pending_Start_Date","In_Review_Start_Date",
"In_Review_Start_Date"::date -  "Review_Pending_Start_Date"::date as Days_Review_Pending, "Module_Type","SL"
  from "CMVP_MIP_Table" as alpha left join "CMVP_Active_Table" as bravo on
  alpha."Module_Name"=bravo."Module_Name" and alpha."Vendor_Name"=bravo."Vendor_Name" 
  where bravo."Status2" is null and (alpha."Status2" not like 'DUP%' or alpha."Status2" is null)
  and bravo."Lab_Name" not like 'atsec%' AND bravo."Lab_Name" not like 'Atsec%' AND bravo."Lab_Name" not like 'ATSEC%'
  and  "In_Review_Start_Date" is not null and "Review_Pending_Start_Date" is not null
and  "In_Review_Start_Date" <= (select CURRENT_DATE) AND "In_Review_Start_Date" >= '2020-03-05'
and  "Review_Pending_Start_Date" <= (select CURRENT_DATE) AND "Review_Pending_Start_Date" >= '2020-03-05'
and "Review_Pending_Start_Date" < "In_Review_Start_Date"
  order by bravo."Lab_Name",bravo."Vendor_Name" 


 -- average number of days non-atsec labs spent  Review Pending for the date range listed by each individual lab
SELECT bravo."Lab_Name", round(cast(avg("In_Review_Start_Date"::date - "Review_Pending_Start_Date"::date ) as numeric),2)
as Avg_Days_Review_Pending 
  from "CMVP_MIP_Table" as alpha left join "CMVP_Active_Table" as bravo on
  alpha."Module_Name"=bravo."Module_Name" and alpha."Vendor_Name"=bravo."Vendor_Name" 
  where bravo."Status2" is null and (alpha."Status2" not like 'DUP%' or alpha."Status2" is null)
  and bravo."Lab_Name" not like 'atsec%' AND bravo."Lab_Name" not like 'Atsec%' AND bravo."Lab_Name" not like 'ATSEC%'
  and  "In_Review_Start_Date" is not null and "Review_Pending_Start_Date" is not null
and  "In_Review_Start_Date" <= (select CURRENT_DATE) AND "In_Review_Start_Date" >= '2020-03-05'
and  "Review_Pending_Start_Date" <= (select CURRENT_DATE) AND "Review_Pending_Start_Date" >= '2020-03-05'
and "Review_Pending_Start_Date" < "In_Review_Start_Date"
group by bravo."Lab_Name"
    

=======================================================================================





-- Comprehensive average of days for all non-atsec modules in each state for the date range listed
;WITH sums AS 
(  
SELECT Charlie."Lab_Name", 
( SELECT  round(cast(avg("In_Review_Start_Date"::date - "Review_Pending_Start_Date"::date ) as numeric),2) as Avg_Days_Review_Pending 
  from "CMVP_MIP_Table" as alpha1 
  where ((alpha1."Status2" not like '%Goofy%'  and alpha1."Status2" not like '%Duplicate%' ) or alpha1."Status2" is null) 
  and alpha1."Lab_Name" not like 'atsec%' AND alpha1."Lab_Name" not like 'Atsec%' AND alpha1."Lab_Name" not like 'ATSEC%'
  and  "In_Review_Start_Date" is not null and "Review_Pending_Start_Date" is not null
  and  "In_Review_Start_Date" <= (select CURRENT_DATE) AND "In_Review_Start_Date" >= '2020-03-05'
  and  "Review_Pending_Start_Date" <= (select CURRENT_DATE) AND "Review_Pending_Start_Date" >= '2020-03-05'
  and "Review_Pending_Start_Date" < "In_Review_Start_Date" and alpha1."Lab_Name" = Charlie."Lab_Name"
),
(SELECT  round(cast(avg("Coordination_Start_Date"::date - "In_Review_Start_Date"::date ) as numeric),2)   as Avg_Days_In_Review 
    from "CMVP_MIP_Table" as alpha2     where ((alpha2."Status2" not like '%Goofy%'  and alpha2."Status2" not like '%Duplicate%' ) or alpha2."Status2" is null) 
   and alpha2."Lab_Name" not like 'atsec%' AND alpha2."Lab_Name" not like 'Atsec%' AND alpha2."Lab_Name" not like 'ATSEC%'
   and  "Coordination_Start_Date" is not null and "In_Review_Start_Date" is not null
  and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= '2020-03-05'
  and  "In_Review_Start_Date" <= (select CURRENT_DATE) AND "In_Review_Start_Date" >= '2020-03-05'
  and "In_Review_Start_Date" < "Coordination_Start_Date" and alpha2."Lab_Name" = charlie."Lab_Name"

),
(SELECT  round(cast(avg("Finalization_Start_Date"::date - "Coordination_Start_Date"::date ) as numeric),2)    as Avg_Days_Coor 
      from "CMVP_MIP_Table" as alpha3 
     where ((alpha3."Status2" not like '%Goofy%'  and alpha3."Status2" not like '%Duplicate%' ) or alpha3."Status2" is null)  
      and alpha3."Lab_Name" not like 'atsec%' AND alpha3."Lab_Name" not like 'Atsec%' AND alpha3."Lab_Name" not like 'ATSEC%'
      and  "Finalization_Start_Date" is not null and "Coordination_Start_Date" is not null
    and  "Finalization_Start_Date" <= (select CURRENT_DATE) AND "Finalization_Start_Date" >= '2020-03-05'
    and  "Coordination_Start_Date" <= (select CURRENT_DATE) AND "Coordination_Start_Date" >= '2020-03-05'
    and "Coordination_Start_Date" < "Finalization_Start_Date" and alpha3."Lab_Name"=charlie."Lab_Name"
)
from "CMVP_MIP_Table" as charlie
where ((charlie."Status2" not like '%Goofy%'  and charlie."Status2" not like '%Duplicate%' ) or charlie."Status2" is null)  
 and charlie."Lab_Name" not like 'atsec%' AND charlie."Lab_Name" not like 'Atsec%' AND charlie."Lab_Name" not like 'ATSEC%'
 group by charlie."Lab_Name" order by charlie."Lab_Name"
) 
SELECT 
   "Lab_Name",Avg_Days_Review_Pending , Avg_Days_In_Review, Avg_Days_Coor,
   Avg_Days_Review_Pending + Avg_Days_In_Review +  Avg_Days_Coor    AS GrandTotal 
FROM 
   sums


======== temp junk. list all labs even if they have 0 zero certs for group by
SELECT t1."Lab_Name"  , (case when t2.Num_Certs > 0 then t2.Num_Certs else 0 end) as Num_Certs 
FROM  "CMVP_Active_Table" as t1 LEFT JOIN 
    ( select  "Lab_Name", count(*) as Num_Certs from "CMVP_Active_Table" where "Status"  like '%Revoked%' 
  Group by "Lab_Name" ) AS t2
ON t1."Lab_Name" = t2."Lab_Name" and t1."Status" like '%Revoked%' 
group by t1."Lab_Name",t2.Num_Certs
order by num_certs 

=

==============


update "CMVP_atsec_Only_MIP_Table" as t1 set "Module_Type" =  t2."Module_Type" , "SL" =  t2."SL" 
from (select "Cert_Num","Module_Type","SL" from  "CMVP_Active_Table") as t2
where t1."Cert_Num"=t2."Cert_Num"



        
      
