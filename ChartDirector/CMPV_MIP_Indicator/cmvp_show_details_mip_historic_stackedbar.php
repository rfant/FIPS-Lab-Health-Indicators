<?php

//this php file defines whether the URL is for production or development for all the PHP files.
//Change the URL value in the below file for it to reflect in all the URL's that are used for the indicators
include './cmvp_define_url_prod_vs_develop.php'; 
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

//================================================================================================
function strip_quote ($input_Module_Name) {

global $g_in_Module_Name;

//	$input_Module_Name[0]='%';
//	$input_Module_Name[strlen($input_Module_Name)-2]='%';

	//str_replace(find,replace,string,count)
	//echo str_replace("world","Peter","Hello world!");
	
	//$input_Module_Name=str_replace("'","''",$input_Module_Name);

	//$input_Module_Name=str_replace("%","'",$input_Module_Name);


	$global['g_in_Module_Name']=$input_Module_Name;

} // use linear regression
//================================================================================================

//variables passed into this program from the calling program
$startDate=$_REQUEST["startDate"] ;
$endDate=$_REQUEST["endDate"];

$today2 =  (new DateTime)->format('Y-m-d');
$todaysDate = date('Y-m-d', strtotime($today2));



$dataSetName = isset($_REQUEST["dataSetName"])? $_REQUEST["dataSetName"] : -1;
if($dataSetName=='RP Exit')
{
	echo "Nothing to see here. Click back arrow";
	return 1;
}

$in_Module_Name=isset($_REQUEST["Module_Name"]) ? $_REQUEST["Module_Name"] : '%';
$in_Vendor_Name=isset($_REQUEST["Vendor_Name"]) ? $_REQUEST["Vendor_Name"] : '%';

$xLabel= isset($_REQUEST["xLabel"])? $_REQUEST["xLabel"] : "%";
$Module_Name_Index=isset($_REQUEST["x"]) ? $_REQUEST["x"] : -1;  // Negative One means to Show All Modules

$value= $_REQUEST["value"];


$OrderBy = isset($_REQUEST['OrderBy']) ? $_REQUEST['OrderBy'] : '1' ;
$Direction = isset($_REQUEST['Direction']) ? $_REQUEST['Direction'] : 'asc' ;

$in_TopButtons=isset($_REQUEST["in_TopButtons"]) ? $_REQUEST["in_TopButtons"] : 5;


//toggle the direction each time.
if($Direction=='asc')
	$Direction='desc';
else
	$Direction='asc';

//alpha echo "xLabel=".$xLabel." x=".$Module_Name_Index." Order_by=".$OrderBy."<br>";


//echo "startDate=".$startDate." ";
//echo "endDate=".$endDate."<br></br> ";

//1 means selected. 0 means not selected
$in_IntelOnlyButton=isset($_REQUEST["in_IntelOnlyButton"]) ? $_REQUEST["in_IntelOnlyButton"] : 0;
$in_ModuleTypeButton=isset($_REQUEST["in_ModuleTypeButton"]) ? $_REQUEST["in_ModuleTypeButton"] : 0;
$in_SecurityLevelButton=isset($_REQUEST["in_SecurityLevelButton"]) ? $_REQUEST["in_SecurityLevelButton"] : 0;
 
//echo "IntelOnly=".$in_IntelOnlyButton." MT=".$in_ModuleTypeButton." SL=".$in_SecurityLevelButton."<BR>";
#===========================================================================
#connect to postgreSQL database and get my detailed data
$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$connStr = "host=localhost  dbname=postgres user=postgres password=postgres connect_timeout=5 options='--application_name=$appName'";

$User=get_current_user();
$conn = pg_connect($connStr);
$hit_counter= " INSERT INTO \"CMVP_Hit_Counter\" ( \"URL\", \"Timestamp\",\"Date\", \"Application\",\"User\") values('".$URL_str."',(select (current_time(0) - INTERVAL '5 HOURS')),'". $today2."',
'cmvp_show_details_mip_historic_stackedbar.php','".$User."')";
//$result = pg_query($conn, $hit_counter);

global $g_in_Module_Name;
strip_quote ($in_Module_Name); 

$where_module_name=" and 3=3 and \"Module_Name\" =".$in_Module_Name." and 4=4";
//$where_module_name=" and 3=3 and \"Module_Name\" =".$g_in_Module_Name." and 4=4";

$where_vendor_name=" and 5=5 and \"Vendor_Name\" =".$in_Vendor_Name." and 6=6";
			
		
	  
//$lab_header='Lab';  //used for the column header "LAB".

$sql_Str="

	select \"Clean_Lab_Name\",\"Module_Name\",\"TID\",\"Cert_Num\",\"Vendor_Name\", \"Review_Pending_Start_Date\", (case
	when \"In_Review_Start_Date\" is null then (select current_date)::date - \"Review_Pending_Start_Date\"::date else \"In_Review_Start_Date\"::date - \"Review_Pending_Start_Date\"::date end )as rpDays, 

		
		
	\"In_Review_Start_Date\",(case when \"Coordination_Start_Date\" is null then (select current_date)::date - \"In_Review_Start_Date\"::date else \"Coordination_Start_Date\"::date - \"In_Review_Start_Date\"::date end) as irDays,
		
	 \"Coordination_Start_Date\", (case when \"Finalization_Start_Date\" is null then (select current_date)::date - \"Coordination_Start_Date\"::date else 
	 \"Finalization_Start_Date\"::date-\"Coordination_Start_Date\"::date end) as coDays,

	  (case when \"Finalization_Start_Date\" is null then 0 else 
	 (select current_date)::date-\"Finalization_Start_Date\"::date end) as fiDays,
		
	 \"Finalization_Start_Date\" ,
	 (case when \"Finalization_Start_Date\" is not null then \"Finalization_Start_Date\"::date - \"In_Review_Start_Date\"::date else  
		(select current_date)::date - \"In_Review_Start_Date\"::date 	end)	as totalDays,
		\"Standard\", \"Module_Type\",\"SL\" 

		from \"CMVP_MIP_Table\" where 2=2 ".$where_module_name.$where_vendor_name."
		 
		";
	


//do the query 
$sql_Str=$sql_Str." order by \"Review_Pending_Start_Date\", \"Module_Name\" ";
//echo "<br>bravo sql_str= " . $sql_Str."<br>" ;

$result = pg_query($conn, $sql_Str);
$arr = pg_fetch_all($result);

$num_mod=(int) sizeof($arr); //-1 ;

//echo ": ".$num_mod;

foreach($arr as $row)
	$FI_SD_Array_Color[]=$row['fidays'];  //get this to set font color toll gray if this null. else color as black

foreach($arr as $row)
	$IR_SD_Array_Color[]=$row['irdays'];  //get this to set font color toll gray if this null. else color as black

foreach($arr as $row)
	$CO_SD_Array_Color[]=$row['codays'];  //get this to set font color toll gray if this null. else color as black


foreach($arr as $row)
	//$RP_DaysLeft_Array_Color[]=$row['rp_time_remaining'];  //get this to set font color toll red if this is greater than forecast.  else color as black

foreach($arr as $row)
//	$IR_DaysLeft_Array_Color[]=$row['ir_time_remaining'];  //get this to set font color toll red if this is greater than forecast.  else color as black

foreach($arr as $row)
//	$CO_DaysLeft_Array_Color[]=$row['co_time_remaining'];  //get this to set font color toll red if this is greater than forecast.  else color as black

$FI_Array=array();

foreach($arr as $row)
{	
	$Finalization_Start_Date_Array[]=$row['Finalization_Start_Date'];  //get this to color the cell yellow if this null. else color as green
	
}



$RP_SD_Emp_Str1=array();$RP_SD_Emp_Str2=array();
$IR_SD_Emp_Str1=array();$IR_SD_Emp_Str2=array();
$CO_SD_Emp_Str1=array();$CO_SD_Emp_Str2=array();
$FI_SD_Emp_Str1=array();$FI_SD_Emp_Str2=array();
$FI_background=array();

for($i=0;$i<(sizeof($FI_SD_Array_Color));$i++)
{//set up font colors

	//echo $i.": -".$Finalization_Start_Date_Array[$i]."-<br></br>";

	
	if($FI_SD_Array_Color[$i] == 0)
		$FI_background[$i]="\"#ffff00\"";  //yellow: still in progress
	else
		$FI_background[$i]="\"#90EE90\""; //green: finished


	//if remaining time is greater than what was forecasted, then mark text as red. else black
	//if($RP_DaysLeft_Array_Color[$i] > $forecast_Y_rp)
		//$RP_DaysLeft_Array_Color[$i]="\"#FF2400\""; //red
	//else
		$RP_DaysLeft_Array_Color[$i]="\"#0\""; //BLACK

	//if($IR_DaysLeft_Array_Color[$i] > $forecast_Y_ir)
		//$IR_DaysLeft_Array_Color[$i]="\"#FF2400\""; //red
	//else
		$IR_DaysLeft_Array_Color[$i]="\"#0\""; //BLACK

	//if($CO_DaysLeft_Array_Color[$i] > $forecast_Y_co)
		//$CO_DaysLeft_Array_Color[$i]="\"#FF2400\""; //red
	//else
		$CO_DaysLeft_Array_Color[$i]="\"#0\""; //BLACK

	//if the Start Date is forecasted, then mark as gray in italics. else black

	if($FI_SD_Array_Color[$i] == 0)
	{   $FI_SD_Emp_Str1[$i]="<em>"; 
		$FI_SD_Array_Color[$i]="\"#848482\""; //gray  
		$FI_SD_Emp_Str2[$i]="</em>"; 
		//$mycolor="\"#ffff00\"";  //yellow

	}
	else
	{	$FI_SD_Emp_Str1[$i]=""; 
		$FI_SD_Array_Color[$i]="\"#00000\""; //black
		$FI_SD_Emp_Str2[$i]=""; 
		//$mycolor="\"#90EE90\""; //green
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


echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";


echo "<table>"; // start a table tag in the HTML
echo "<tr> ";
$URL_Str="/ChartDirector/CMVP_MIP_Indicator";

echo "<th bgcolor=LightBlue >Row</th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=2&Direction=".$Direction." \" >Cert_Num</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=3&Direction=".$Direction." \" >Module</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=4&Direction=".$Direction." \" >Vendor</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=5&Direction=".$Direction." \" >Lab</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=6&Direction=".$Direction." \" >RP Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=7&Direction=".$Direction." \" >Days in RP</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=8&Direction=".$Direction." \" >IR Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=9&Direction=".$Direction." \" >Days in IR</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=10&Direction=".$Direction." \" >CO Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=11&Direction=".$Direction." \" >Days in CO </a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=13&Direction=".$Direction." \" >FI Start Date</a></th>  ";

//echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=12&Direction=".$Direction." \" >Days in FI</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=14&Direction=".$Direction." \" >Total Days IR+CO</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=16&Direction=".$Direction." \" >Module Type</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=17&Direction=".$Direction." \" >SL</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_historic_stackedbar.php?x=".$Module_Name_Index."&in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=15&Direction=".$Direction." \" >Standard</a></th>  ";


echo "</tr>";
$i=1;
    if ($num_mod>0) { 
    foreach($arr as $row){   //Creates a loop to loop through results
      	


      echo "<tr><td>".$i
                      ."</td><td>  <a href=\"https://csrc.nist.gov/projects/cryptographic-module-validation-program/certificate/".$row['Cert_Num']." \"  target=\"_blank\"> "
                      . $row['Cert_Num'] . " </a>  </td><td>  "  
                      . $row['Module_Name'] . "  </td><td>  "
                      . $row['Vendor_Name']. "  </td><td>  "
                      . $row['Clean_Lab_Name'].  "  </td><td>  "
                      . $row['Review_Pending_Start_Date']." </td><td> "
                      . $row['rpdays']." </td><td> "
                      . $row['In_Review_Start_Date']."  </td><td>  "
                      . $row['irdays']." </td><td> "
                      . $row['Coordination_Start_Date']."  </td><td>  "
                      . $row['codays']." </td><td> "
                      . $row['Finalization_Start_Date']."  </td><td bgcolor=".$FI_background[$i-1].">  "
                     
                      . $row['totaldays']."  </td><td>  "
                      . $row['Module_Type']."  </td><td>  "
                      . $row['SL']."  </td><td>  "
                      . $row['Standard']. "  </td></tr>";
      $i++;  
      
      } //for each
  	} //if

echo "</table>"; //Close the table in HTML

?>

