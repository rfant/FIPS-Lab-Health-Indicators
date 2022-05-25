<?php

//this php file defines whether the URL is for production or development for all the PHP files.
//Change the URL value in the below file for it to reflect in all the URL's that are used for the indicators
include './cmvp_define_url_prod_vs_develop.php'; 
include './cmvp_define_which_database.php';
//==========================================================



define        ("red",0x00FF0000);
define      ("green",0x0000FF00);
define       ("blue",0x000000FF);
define ("light_blue",0x00eeeeff);
define  ("deep_blue",0x000000cc);
define      ("white",0x00FFFFFF);
define      ("black",0x00000000);
define      ("grey1",0x00dcdcdc);
define      ("grey2",0x00f6f6f6);

#=======================================================
//variables passed into this program from the calling program
$startDate=$_REQUEST["startDate"];
$endDate=$_REQUEST["endDate"];

$forecast_Y_rp=isset($_REQUEST["forecast_Y_rp"])? $_REQUEST["forecast_Y_rp"] : 0;
$forecast_Y_ir=isset($_REQUEST["forecast_Y_ir"])?  $_REQUEST["forecast_Y_ir"] : 0;
$forecast_Y_co=isset($_REQUEST["forecast_Y_co"])? $_REQUEST["forecast_Y_co"]: 0;
$months_to_look_back=isset($_REQUEST["months_to_look_back"])? $_REQUEST["months_to_look_back"] :24;

$today2 =  (new DateTime)->format('Y-m-d');
$todaysDate = date('Y-m-d', strtotime($today2));
 


$xLabel= isset($_REQUEST["xLabel"])? $_REQUEST["xLabel"] : "unknown";
$TID_Index=isset($_REQUEST["x"]) ? $_REQUEST["x"] : -1;  // Negative One means to Show All TIDs

//$value= $_REQUEST["value"];

$OrderBy = isset($_REQUEST['OrderBy']) ? $_REQUEST['OrderBy'] : '1' ;
$Direction = isset($_REQUEST['Direction']) ? $_REQUEST['Direction'] : 'asc' ;

$in_TopButtons=isset($_REQUEST["in_TopButtons"]) ? $_REQUEST["in_TopButtons"] : 5;


//toggle the direction each time.
if($Direction=='asc')
	$Direction='desc';
else
	$Direction='asc';

//1 means selected. 0 means not selected
 $in_IntelOnlyButton=isset($_REQUEST["in_IntelOnlyButton"]) ? $_REQUEST["in_IntelOnlyButton"] : 0;
 $in_ModuleTypeButton=isset($_REQUEST["in_ModuleTypeButton"]) ? $_REQUEST["in_ModuleTypeButton"] : 0;
 $in_SecurityLevelButton=isset($_REQUEST["in_SecurityLevelButton"]) ? $_REQUEST["in_SecurityLevelButton"] : 0;
 
//echo "IntelOnly=".$in_IntelOnlyButton." MT=".$in_ModuleTypeButton." SL=".$in_SecurityLevelButton."<BR>";




//echo "startDate=".$startDate." ";
//echo "endDate=".$endDate."<br></br> ";

#===========================================================================

#connect to postgreSQL database and get my chart data

$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

switch ($PROD) {
    case 2:  //postgresql database on Ubuntu VM machine 
     	$encryptedPW="xtw2D3obQa8=";
  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
			$connStr = "host=localhost  dbname=postgres user=postgres password=".$decryptedPW." connect_timeout=5 options='--application_name=$appName'";
			echo "pgsql=ubutun VM";
        break;
    case 1: //postgresql database on intel interanet production
			$encryptedPW="39ABDntQEJtweA==";
  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
			//$connStr = "host=postgres5456-lb-fm-in.dbaas.intel.com  dbname=lhi_prod user=lhi_prod_so password=".$decryptedPW." port=5433 connect_timeout=5 options='--application_name=$appName'";
			$connStr = "host=postgres5320-lb-fm-in.dbaas.intel.com  dbname=lhi_prod2 user=lhi_prod2_so password=".$decryptedPW."  connect_timeout=5 options='--application_name=$appName'";
			
			echo "pgsql=intel prod";
        break;
    case 0:   //postgresql database on intel intranet pre-production
    	$encryptedPW="39ABDntQEJtweA==";
  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
			$connStr = "host=postgres5596-lb-fm-in.dbaas.intel.com  dbname=lhi_pre_prod user=lhi_pre_prod_so password=".$decryptedPW." connect_timeout=5 options='--application_name=$appName'";
			echo "pgsql=intel pre-prod";
        break;
    default:
    	echo "ERROR: unknown PROD value";

	}

//echo "PROD= $PROD"." ConnStr= ".$connStr;

//=====================================================





$User=get_current_user();
$conn = pg_connect($connStr);
$hit_counter= " INSERT INTO \"CMVP_Hit_Counter\" ( \"URL\", \"Timestamp\",\"Date\", \"Application\",\"User\") values('".$URL_str."',(select (current_time(0) - INTERVAL '5 HOURS')),'". $today2."',
'cmvp_show_details_mip_forecast_stackedbar.php','".$User."')";
//$result = pg_query($conn, $hit_counter);



$temp_sql_string=" and 1=1 ";

	
	$sql_Str="

	(select \"TID\", \"Review_Pending_Start_Date\" as StartDate, rp_time_remaining, ".$forecast_Y_ir." as ir_time_remaining, ".$forecast_Y_co." as co_time_remaining
		from (
 	select \"TID\", \"Review_Pending_Start_Date\", 
 	abs((select (current_date)::date - (\"Review_Pending_Start_Date\" + INTERVAL '".$forecast_Y_rp." days')::date ))as rp_time_remaining 
 	from \"CMVP_MIP_Table\" where \"Review_Pending_Start_Date\" between (select CURRENT_DATE) - INTERVAL '".$months_to_look_back." months' and (select current_date) and (\"Status2\" is null) and \"In_Review_Start_Date\" is null and \"Coordination_Start_Date\" is null and \"Finalization_Start_Date\" is null ) as subquery order 
 		by \"Review_Pending_Start_Date\" )
	UNION
	(select \"TID\", \"In_Review_Start_Date\" as StartDate , 0 as rp_time_remaining, ir_time_remaining, ".$forecast_Y_co." as co_time_remaining 
	from ( select \"TID\", \"Review_Pending_Start_Date\",\"In_Review_Start_Date\", 
  	abs((select (current_date)::date - (\"In_Review_Start_Date\" + INTERVAL '".$forecast_Y_ir." days')::date ))as ir_time_remaining 
  	from \"CMVP_MIP_Table\" where \"Review_Pending_Start_Date\" between (select CURRENT_DATE) - INTERVAL '".$months_to_look_back." months' and (select current_date) and (\"Status2\" is null) and \"In_Review_Start_Date\" is not null and \"Coordination_Start_Date\" is null and \"Finalization_Start_Date\" is null ) as subquery order 
  		by \"Review_Pending_Start_Date\" )
	union
	(
	select \"TID\", \"Coordination_Start_Date\" as StartDate, 0 as rp_time_remaining, 0 as ir_time_remaining, co_time_remaining
	from ( 
		select \"TID\",\"Review_Pending_Start_Date\",\"Coordination_Start_Date\", 
		abs(( select (current_date)::date - (\"Coordination_Start_Date\" + INTERVAL '".$forecast_Y_co." days')::date ))as co_time_remaining
	 from \"CMVP_MIP_Table\" where \"Review_Pending_Start_Date\" between (select CURRENT_DATE) - INTERVAL '".$months_to_look_back." months' and (select current_date) and (\"Status2\" is null) and \"Coordination_Start_Date\" is not null and \"Finalization_Start_Date\" is null
	) as subquery  order by \"Review_Pending_Start_Date\"
	)
	order by StartDate, \"TID\"
	";
		
	



//echo "bravo sql=".$sql_Str."<br></br>";

//do the query the first time to get the array of all the TID's
$result = pg_query($conn, $sql_Str);
$arr = pg_fetch_all($result);
if($arr==null)
	{   echo "ERROR 372e:  SQL Query returned nothing<br></br>";
		$num_mod=0;
	}
else
	$num_mod=sizeof($arr);



$TID_Array=array();
$i=0;
foreach($arr as $row)
{	
	$TID_Array[]=$row['TID'];        //get the specific TID that I clicked on.
	
}

if($TID_Index==-1)
	$TID_String= "%";
else
	$TID_String=$TID_Array[$TID_Index];



$sql_Str2="
		
		
		select \"TID\",\"Module_Name\",\"Vendor_Name\",\"Lab_Name\",\"Review_Pending_Start_Date\", (case
		when \"In_Review_Start_Date\" is null then (select current_date)::date - \"Review_Pending_Start_Date\"::date else \"In_Review_Start_Date\"::date - \"Review_Pending_Start_Date\"::date end )as rpDays, 
		(case when \"In_Review_Start_Date\" is null then  ((\"Review_Pending_Start_Date\" + INTERVAL '".$forecast_Y_rp." days')::date  -(select (current_date))) else 0 end)as rp_time_remaining ,
	 	
		(case when \"In_Review_Start_Date\" is null then (\"Review_Pending_Start_Date\" + INTERVAL '".$forecast_Y_rp." days')::date   else \"In_Review_Start_Date\" end),
		(case when \"Coordination_Start_Date\" is null then (select current_date)::date - \"In_Review_Start_Date\"::date else \"Coordination_Start_Date\"::date - \"In_Review_Start_Date\"::date end) as irDays,
		(case when \"Coordination_Start_Date\" is null then  ((\"In_Review_Start_Date\" + INTERVAL '".$forecast_Y_ir." days')::date - (select (current_date))) else 0 end )as ir_time_remaining ,
	 	
		
		 (case when \"Coordination_Start_Date\" is null and \"In_Review_Start_Date\" is not null then (\"In_Review_Start_Date\" + INTERVAL '".$forecast_Y_ir." days')::date  
		 when \"Coordination_Start_Date\" is null and \"In_Review_Start_Date\" is null then (\"Review_Pending_Start_Date\" + INTERVAL '".($forecast_Y_ir + $forecast_Y_rp)." days')::date  else \"Coordination_Start_Date\" end), 
		 (case when \"Finalization_Start_Date\" is null then (select current_date)::date - \"Coordination_Start_Date\"::date else 
		 \"Finalization_Start_Date\"::date-\"Coordination_Start_Date\"::date end) as coDays,
		(case when \"Finalization_Start_Date\" is null then  ((\"Coordination_Start_Date\" + INTERVAL '".$forecast_Y_co." days')::date - (select(current_date))) else 0 end)as co_time_remaining, 
	 	
		
		(case 
				when \"Finalization_Start_Date\" is null and \"In_Review_Start_Date\" is null and \"Coordination_Start_Date\" is null then  
					(\"Review_Pending_Start_Date\" + INTERVAL '".($forecast_Y_rp + $forecast_Y_ir + $forecast_Y_co)." days')::date
		      	when \"Finalization_Start_Date\" is null and \"Coordination_Start_Date\" is null and \"In_Review_Start_Date\" is not null then
		      		(\"In_Review_Start_Date\" + INTERVAL '".($forecast_Y_ir + $forecast_Y_co)." days')::date
		      	when \"Finalization_Start_Date\" is null and \"Coordination_Start_Date\" is not null and \"In_Review_Start_Date\" is not null then
		      		(\"Coordination_Start_Date\" +  INTERVAL '".$forecast_Y_co." days')::date
		      	else
		      		\"Finalization_Start_Date\"

		 end), 
		 (case when \"Finalization_Start_Date\" is null then 0 else 
		 (select current_date)::date-\"Finalization_Start_Date\"::date end) as fiDays,

		(case when \"Finalization_Start_Date\" is not null then \"Finalization_Start_Date\"::date - \"Review_Pending_Start_Date\"::date else  
		(select current_date)::date - \"Review_Pending_Start_Date\"::date 	end)	as totalDays,

		\"Standard\", \"Module_Type\",\"SL\" 

		from \"CMVP_MIP_Table\"  where \"Review_Pending_Start_Date\" between  (select CURRENT_DATE) - INTERVAL '".$months_to_look_back." months' and (select current_date)  
			and \"TID\" like '".$TID_String."'
			and (\"Status2\" like '%Promoted%' OR \"Status2\" like '%Reappear%' OR \"Status2\" is null) 
			and \"Finalization_Start_Date\" is null AND (
			(\"In_Review_Start_Date\" is null AND \"Coordination_Start_Date\" is null) 
			OR (\"In_Review_Start_Date\" is  null and \"Coordination_Start_Date\" is not null)
			OR (\"In_Review_Start_Date\" is not null AND \"Coordination_Start_Date\" is null ) 
			
			OR (\"In_Review_Start_Date\" is  not null and \"Coordination_Start_Date\" is not null)
			) 
	
		order by ".$OrderBy." ".$Direction." ;	 

		
";

//echo "alpha str2=".$sql_Str2;
//


$result = pg_query($conn, $sql_Str2);
$arr = pg_fetch_all($result);
if($arr==null)
	{   echo "<br></br>ERROR 372f:  SQL Query returned nothing.<br></br> Sql_Str2=".$sql_Str2."<br></br>";
		$num_mod=0;
	}
else
	$num_mod=sizeof($arr);

if($TID_Index==-1)
	echo "Number of Modules:". $num_mod;

foreach($arr as $row)
	$FI_SD_Array_Color[]=$row['fidays'];  //get this to set font color toll gray if this null. else color as black

foreach($arr as $row)
	$IR_SD_Array_Color[]=$row['irdays'];  //get this to set font color toll gray if this null. else color as black

foreach($arr as $row)
	$CO_SD_Array_Color[]=$row['codays'];  //get this to set font color toll gray if this null. else color as black


foreach($arr as $row)
	$RP_DaysLeft_Array_Color[]=$row['rp_time_remaining'];  //get this to set font color toll red if this is greater than forecast.  else color as black

foreach($arr as $row)
	$IR_DaysLeft_Array_Color[]=$row['ir_time_remaining'];  //get this to set font color toll red if this is greater than forecast.  else color as black

foreach($arr as $row)
	$CO_DaysLeft_Array_Color[]=$row['co_time_remaining'];  //get this to set font color toll red if this is greater than forecast.  else color as black

/*
foreach($arr as $row)
	$RP_Start_Date[]=$row['Review_Pending_Start_Date'];

foreach($arr as $row)
	$IR_Start_Date[]=$row['In_Revew_Start_Date'];

foreach($arr as $row)
	$IR_Start_Date[]=$row['In_Revew_Start_Date'];
*/

$RP_SD_Emp_Str1=array();$RP_SD_Emp_Str2=array();
$IR_SD_Emp_Str1=array();$IR_SD_Emp_Str2=array();
$CO_SD_Emp_Str1=array();$CO_SD_Emp_Str2=array();
$FI_SD_Emp_Str1=array();$FI_SD_Emp_Str2=array();

for($i=0;$i<(sizeof($FI_SD_Array_Color));$i++)
{//set up font colors

	//if remaining time is greater than what was forecasted, then mark text as red. else black
	if ($RP_DaysLeft_Array_Color[$i] < 0) //> $forecast_Y_rp)
		$RP_DaysLeft_Array_Color[$i]="\"#FF2400\""; //red
	else
		$RP_DaysLeft_Array_Color[$i]="\"#0\""; //BLACK

	if($IR_DaysLeft_Array_Color[$i] < 0 ) //> $forecast_Y_ir)
		$IR_DaysLeft_Array_Color[$i]="\"#FF2400\""; //red
	else
		$IR_DaysLeft_Array_Color[$i]="\"#0\""; //BLACK

	if($CO_DaysLeft_Array_Color[$i] <0) // $forecast_Y_co)
		$CO_DaysLeft_Array_Color[$i]="\"#FF2400\""; //red
	else
		$CO_DaysLeft_Array_Color[$i]="\"#0\""; //BLACK

	//if the Start Date is forecasted, then mark as gray in italics. else black

	if($FI_SD_Array_Color[$i] == 0)
	{   $FI_SD_Emp_Str1[$i]="<em>"; 
		$FI_SD_Array_Color[$i]="\"#848482\""; //gray  
		$FI_SD_Emp_Str2[$i]="</em>"; 
	}
	else
	{	$FI_SD_Emp_Str1[$i]=""; 
		$FI_SD_Array_Color[$i]="\"#00000\""; //black
		$FI_SD_Emp_Str2[$i]=""; 
	}	

	if($IR_SD_Array_Color[$i]==0)
	{   $IR_SD_Emp_Str1[$i]="<em>"; 
		$IR_SD_Array_Color[$i]="\"#848482\""; //gray
		$IR_SD_Emp_Str2[$i]="</em>"; 
	}
	else
	{	$IR_SD_Emp_Str1[$i]=""; 	
		$IR_SD_Array_Color[$i]="\"#00000\""; //black
		$IR_SD_Emp_Str2[$i]=""; 
	}		

	if($CO_SD_Array_Color[$i]==0)
	{   $CO_SD_Emp_Str1[$i]="<em>"; 
		$CO_SD_Array_Color[$i]="\"#848482\""; //gray
		$CO_SD_Emp_Str2[$i]="</em>"; 
	}
	else
	{	$CO_SD_Emp_Str1[$i]=""; 	
		$CO_SD_Array_Color[$i]="\"#00000\""; //black
		$CO_SD_Emp_Str2[$i]=""; 
	}	
}  //set up font colors


$finalization_color="\"#ffff00\"";  //yellow.  Since I'm in the forecast app, finalization will never be green since green means done.

echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";

     


echo "<table>"; // start a table tag in the HTML
echo "<tr> ";


echo "<th bgcolor=LightBlue >Row</th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=1&Direction=".$Direction." \" >TID</a></th>  ";




echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=2&Direction=".$Direction." \" >Module</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=3&Direction=".$Direction." \" >Vendor</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=4&Direction=".$Direction." \" >".$lab_header."</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=5&Direction=".$Direction." \" >RP Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=6&Direction=".$Direction." \" >Days in RP</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=7&Direction=".$Direction." \" >Days Left in RP</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=8&Direction=".$Direction." \" >IR Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=9&Direction=".$Direction." \" >Days in IR</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=10&Direction=".$Direction." \" >Days Left in IR</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=11&Direction=".$Direction." \" >CO Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=12&Direction=".$Direction." \" >Days in CO</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=13&Direction=".$Direction." \" >Days Left in CO</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=14&Direction=".$Direction." \" >FI Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=15&Direction=".$Direction." \" >Total Days</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=17&Direction=".$Direction." \" >Module Type</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=18&Direction=".$Direction." \" >SL</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_forecast_stackedbar.php?forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&months_to_look_back=".$months_to_look_back."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=16&Direction=".$Direction." \" >Standard</a></th>  ";



echo "</tr>";
$i=1;
    if ($num_mod>0) { 
    foreach($arr as $row){   //Creates a loop to loop through results
      	


      echo "<tr><td>".$i
                      ."</td><td> "
                      . $row['TID'] . "  </td><td>  "  
                      . $row['Module_Name'] . "  </td><td>  "
                      . $row['Vendor_Name']. "  </td><td>  "
                      . $row['Lab_Name'].  "  </td><td>  "
                      . $row['Review_Pending_Start_Date']." </td><td> "
                      . $row['rpdays']." </td><td> <font color=".$RP_DaysLeft_Array_Color[$i-1].">"
                      . abs($row['rp_time_remaining'])."</font> </td><td> ".$IR_SD_Emp_Str1[$i-1]." <font color=".$IR_SD_Array_Color[$i-1].">"
                      . $row['In_Review_Start_Date']." </font> ".$IR_SD_Emp_Str2[$i-1]." </td><td>  "
                      . $row['irdays']." </td><td> <font color=".$IR_DaysLeft_Array_Color[$i-1].">"
                      . abs($row['ir_time_remaining'])." </font> </td><td> ".$CO_SD_Emp_Str1[$i-1]."<font color=".$CO_SD_Array_Color[$i-1]."> "
                      . $row['Coordination_Start_Date']." </font> ".$CO_SD_Emp_Str2[$i-1]."</td><td> <font color=".$CO_SD_Array_Color[$i-1]."> "
                      . $row['codays']." </font> </td><td> <font color=".$CO_DaysLeft_Array_Color[$i-1].">"
                      . abs($row['co_time_remaining'])."</font> </td><td> ".$FI_SD_Emp_Str1[$i-1]."<font color=".$FI_SD_Array_Color[$i-1]."> "
                      . $row['Finalization_Start_Date']." </font> ".$FI_SD_Emp_Str2[$i-1]." </td><td  bgcolor=".$finalization_color.">  "
                      . $row['totaldays']."  </td><td>  "
                      . $row['Module_Type']."  </td><td>  "
                      . $row['SL']."  </td><td>  "
                      . $row['Standard']. "  </td></tr>";
      $i++;  
      
           
      } //for each
  	} //if

echo "</table>"; //Close the table in HTML

?>

