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
//variables passed into this program from the calling program
$startDate=$_REQUEST["startDate"];
$endDate=$_REQUEST["endDate"];

$today2 =  (new DateTime)->format('Y-m-d');
$todaysDate = date('Y-m-d', strtotime($today2));
 


$dataSetName = $_REQUEST["dataSetName"];
$xLabel= $_REQUEST["xLabel"];
//$value= $_REQUEST["value"];

$OrderBy = isset($_REQUEST['OrderBy']) ? $_REQUEST['OrderBy'] : '1' ;
$Direction = isset($_REQUEST['Direction']) ? $_REQUEST['Direction'] : 'asc' ;

$in_TopButtons=isset($_REQUEST["in_TopButtons"]) ? $_REQUEST["in_TopButtons"] : 5;


//toggle the direction each time.
if($Direction=='asc')
	$Direction='desc';
else
	$Direction='asc';

//echo "startDate=".$startDate." ";
//echo "endDate=".$endDate."<br></br> ";

//1 means selected. 0 means not selected
$in_IntelOnlyButton=isset($_REQUEST["in_IntelOnlyButton"]) ? $_REQUEST["in_IntelOnlyButton"] : 0;
$in_ModuleTypeButton=isset($_REQUEST["in_ModuleTypeButton"]) ? $_REQUEST["in_ModuleTypeButton"] : 0;
$in_SecurityLevelButton=isset($_REQUEST["in_SecurityLevelButton"]) ? $_REQUEST["in_SecurityLevelButton"] : 0;
 
echo "IntelOnly=".$in_IntelOnlyButton." MT=".$in_ModuleTypeButton." SL=".$in_SecurityLevelButton."<BR>";



#===========================================================================
#connect to postgreSQL database and get my detailed data
$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$connStr = "host=postgres.aus.atsec  dbname=fantDatabase user=richard password==uwXg9Jo'5Ua connect_timeout=5 options='--application_name=$appName'";

$User=get_current_user();
$conn = pg_connect($connStr);
$hit_counter= " INSERT INTO \"CMVP_Hit_Counter\" ( \"URL\", \"Timestamp\",\"Date\", \"Application\",\"User\") values('".$URL_str."',(select (current_time(0) - INTERVAL '5 HOURS')),'". $today2."',
'cmvp_show_details_mip_pareto.php','".$User."')";
//$result = pg_query($conn, $hit_counter);


// Intel Vendor Only
if($in_IntelOnlyButton==1)
{
  $where_vendor = " and \"Vendor_Name\" like '%Intel Corp%' ";
  $module_count=0;
}
else
{ 
  $where_vendor= " and  1=1 ";
  $module_count=4;
}


// Security Level
switch ($in_SecurityLevelButton) 
{
  case 0:
  $where_security= " and 1=1 ";   //this is for ALL security types. 
    break;
  case 1:
    $where_security = " and \"SL\" =1 ";
    break;
  case 2:
    $where_security = " and \"SL\" =2 ";;
    break;
  case 3:
    $where_security = " and \"SL\" =3 ";;
    break;
  case 4:
    $where_security = " and \"SL\" =4 ";;
    break;
  default:
    echo "ERROR 117:********* SecurityLevel=".$in_SecurityLevelButton."<BR>";
} //switch security level


//Module TYpe
switch ($in_ModuleTypeButton) 
{
  case 0:
  $where_MT= " and 1=1 ";   //this is for ALL module types
    break;
  case 1:
    $where_MT = " and \"Clean_Module_Type\" ='Hardware' ";
    break;
  case 2:
    $where_MT = " and \"Clean_Module_Type\" = 'Software' ";;
    break;
  case 3:
    $where_MT = " and \"Clean_Module_Type\" ='Hybrid' ";;
    break;
  case 4:
    $where_MT = " and \"Clean_Module_Type\" ='Firmware' ";;
    break;
  default:
    echo "ERROR 138:********* Module TYpe=".$in_ModuleTypeButton."<BR>";
} //switch security level




#--\"Review_Pending_Start_Date\",\"In_Review_Start_Date\",\"Coordination_Start_Date\",\"Finalization_Start_Date\",

$sql_Str="

select \"Clean_Lab_Name\" , count (*) as num_of_modules,
TRUNC(avg(abs(\"Review_Pending_Start_Date\"::date -\"In_Review_Start_Date\"::date) )) as rp,
TRUNC(avg(abs(\"In_Review_Start_Date\"::date - \"Coordination_Start_Date\"::date)) )as ir,
TRUNC(avg(abs(\"Coordination_Start_Date\" - \"Finalization_Start_Date\")) )as co,
TRUNC(avg(abs (\"Finalization_Start_Date\" - \"In_Review_Start_Date\")) )as time_in_mip
from \"CMVP_MIP_Table\" where \"Review_Pending_Start_Date\" between '".$startDate."' and '".$endDate."' 
and (\"Status2\" like '%Promoted%' OR \"Status2\" like '%Reappear%' OR \"Status2\" is null) 
and   \"In_Review_Start_Date\" is not null 
AND  \"Finalization_Start_Date\" is not null
and \"Clean_Lab_Name\" is not null ".$where_vendor.$where_security.$where_MT."
  group by \"Clean_Lab_Name\" 
having count(*) > ".$module_count."
  order by time_in_mip
";

echo "Sq1_Str=".$sql_Str."<br>";




//echo "<br></br>alpha SQL1= " . $sql_Str."<br></br>" ;
$result = pg_query($conn, $sql_Str);

echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";


      
$arr = pg_fetch_all($result);
$num_mod=(int) sizeof($arr); //-1 ;

echo ": ".$num_mod;

echo "<table>"; // start a table tag in the HTML
echo "<tr> ";


echo "<th bgcolor=LightBlue >Row</th>  ";
echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=1&Direction=".$Direction." \" >TID</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=2&Direction=".$Direction." \" >Module</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=3&Direction=".$Direction." \" >Vendor</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=4&Direction=".$Direction." \" >".$lab_header."</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=6&Direction=".$Direction." \" >RP Start Date</a></th>  ";
echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=6&Direction=".$Direction." \" >Days in RP</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=7&Direction=".$Direction." \" >IR Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=7&Direction=".$Direction." \" >Days in IR</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=8&Direction=".$Direction." \" >CO Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=8&Direction=".$Direction." \" >Days in CO </a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=9&Direction=".$Direction." \" >FI Start Date</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_mip_pareto.php?in_TopButtons=".$in_TopButtons."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=14&Direction=".$Direction." \" >Standard</a></th>  ";



echo "</tr>";
$i=1;
    if ($num_mod>0) { 
    foreach($arr as $row){   //Creates a loop to loop through results
      	$RPendDate=isset($row['In_Review_Start_Date']) ? $row['In_Review_Start_Date'] : $todaysDate;//$endDate;
      	$IRendDate=isset($row['Coordination_Start_Date']) ? $row['Coordination_Start_Date'] : $todaysDate;//$endDate;
      	$COendDate=isset($row['Finalization_Start_Date']) ? $row['Finalization_Start_Date'] : $todaysDate;//$endDate;



      	$RP_days=  isset($row['Review_Pending_Start_Date']) ? (round((strtotime($RPendDate) - strtotime($row['Review_Pending_Start_Date']))/(60 * 60 * 24))):0;
      	$IR_days=  isset($row['In_Review_Start_Date']) ? (round((strtotime($IRendDate) - strtotime($row['In_Review_Start_Date']))/(60 * 60 * 24))):0;
      	$CO_days=isset($row['Coordination_Start_Date'])? (round((strtotime($COendDate) - strtotime($row['Coordination_Start_Date']))/(60 * 60 * 24))):0;
      	


      echo "<tr><td>".$i
                      ."</td><td> "
                      . $row['TID'] . "  </td><td>  "  
                      . $row['Module_Name'] . "  </td><td>  "
                      . $row['Vendor_Name']. "  </td><td>  "
                      . $row['Lab_Name'].  "  </td><td>  "
                      . $row['Review_Pending_Start_Date']." </td><td> "
                      . $RP_days." </td><td> "
                      . $row['In_Review_Start_Date']."  </td><td>  "
                      . $IR_days." </td><td> "
                      . $row['Coordination_Start_Date']."  </td><td>  "
                      . $CO_days." </td><td> "
                      . $row['Finalization_Start_Date']."  </td><td>  "
                      . $row['Standard']. "  </td></tr>";
      $i++;  
      
      } //for each
  	} //if

echo "</table>"; //Close the table in HTML

?>

