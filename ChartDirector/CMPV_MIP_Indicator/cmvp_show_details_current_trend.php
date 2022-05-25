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

$today2 =  (new DateTime)->format('Y-m-d');
$todaysDate = date('Y-m-d', strtotime($today2));
 


$dataSetName = $_REQUEST["dataSetName"];
$xLabel= $_REQUEST["xLabel"];
//$value= $_REQUEST["value"];

$OrderBy = isset($_REQUEST['OrderBy']) ? $_REQUEST['OrderBy'] : '9' ;   //default will be to order by time_in_MIP
$Direction = isset($_REQUEST['Direction']) ? $_REQUEST['Direction'] : 'asc' ;  //default is 'ascending' order

//$in_TopButtons=isset($_REQUEST["in_TopButtons"]) ? $_REQUEST["in_TopButtons"] : 5;


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
 
//echo "IntelOnly=".$in_IntelOnlyButton." MT=".$in_ModuleTypeButton." SL=".$in_SecurityLevelButton."<BR>";



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


//we can set the minimum number of modules a lab has done to be included in this plot
// Intel Vendor Only
if($in_IntelOnlyButton==1)
{
  
  $where_vendor = " and ( \"Vendor_Name\" like '%Intel Corp%'  OR \"Status3\" like '%Intel_Certifiable%' ) ";
  $module_count=0; //we can set the minimum number of modules a lab has done to be included in this plot
}
else
{ 
  $where_vendor= " and  4=4 ";
  $module_count=0; //we can set the minimum number of modules a lab has done to be included in this plot
}

// Security Level
switch ($in_SecurityLevelButton) 
{
  case 0:
  	$where_security= " and 3=3 ";   //this is for ALL security types. 
    break;
  case 1:
    $where_security = " and \"SL\" =1 ";
    break;
  case 2:
    $where_security = " and \"SL\" =2 ";
    break;
  case 3:
    $where_security = " and \"SL\" =3 ";
    break;
  case 4:
    $where_security = " and \"SL\" =4 ";
    break;
  default:
    echo "ERROR 117:********* SecurityLevel=".$in_SecurityLevelButton."<BR>";
} //switch security level


//Module TYpe
switch ($in_ModuleTypeButton) 
{
  case 0:
    $where_MT= " and 2=2 ";   //this is for ALL module types
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

$orderby_str=" order by ".$OrderBy." ".$Direction." ";


//I have to strip out the : and the module count from the xLabel since that will fail the SQL query
$new_xLabel=substr($xLabel,0,strpos($xLabel,":"));


$sql_Str="

 select \"Cert_Num\",\"Module_Name\",\"Vendor_Name\",\"Clean_Lab_Name\" , 
 \"Review_Pending_Start_Date\"::date  as rp, 
 trunc((abs(\"Review_Pending_Start_Date\" - \"In_Review_Start_Date\")))as days_in_rp,
 \"In_Review_Start_Date\"::date as ir, 
 trunc((abs(\"In_Review_Start_Date\" - \"Coordination_Start_Date\")))as days_in_ir,
  \"Coordination_Start_Date\" as co,
  trunc((abs(\"Coordination_Start_Date\" - \"Finalization_Start_Date\")))as days_in_co,
 \"Finalization_Start_Date\" as fi, 
 TRUNC((abs (\"Finalization_Start_Date\" - \"In_Review_Start_Date\")) )as time_in_mip ,
 \"Standard\",\"Module_Type\",\"SL\",\"Status2\"
 from \"CMVP_MIP_Table\" where 1=1
 AND \"Review_Pending_Start_Date\" between '".$startDate."' and '".$endDate."' 
 and (\"Status2\" like '%Promoted%' OR \"Status2\" like '%Reappear%' OR \"Status2\" is null) 
 and \"In_Review_Start_Date\" is not null AND \"Finalization_Start_Date\" is not null 
 and \"Clean_Lab_Name\"  like '%".$new_xLabel."%'".$where_vendor.$where_security.$where_MT."  ".$orderby_str." 

";
//and (\"Status2\" like '%Promoted%' OR \"Status2\" like '%Reappear%' OR \"Status2\" is null) 
//echo "papa: sql_str=<br>".$sql_Str."<br>";


$result = pg_query($conn, $sql_Str);

echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";



      
$arr = pg_fetch_all($result);
$num_mod=(int) sizeof($arr); //-1 ;

//echo $xLabel.": ".$num_mod." modules";
echo $xLabel." modules";

echo "<table>"; // start a table tag in the HTML
echo "<tr> ";

$URL_str="/ChartDirector/CMVP_MIP_Indicator";

echo "<th bgcolor=LightBlue >Row</th>  ";
echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=1&Direction=".$Direction." \" >Cert</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=2&Direction=".$Direction." \" >Module</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=3&Direction=".$Direction." \" >Vendor</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=4&Direction=".$Direction." \" >Lab</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=5&Direction=".$Direction." \" >RP Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=5&Direction=".$Direction." \" >days in RP</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=6&Direction=".$Direction." \" >IR Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=6&Direction=".$Direction." \" >days in IR</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=7&Direction=".$Direction." \" >CO Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=7&Direction=".$Direction." \" >days in CO</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=8&Direction=".$Direction." \" >FI Start Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=9&Direction=".$Direction." \" >Total Days IR+CO</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=10&Direction=".$Direction." \" >Standard</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=11&Direction=".$Direction." \" >Module Type</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/cmvp_show_details_current_trend.php?in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&xLabel=".$xLabel."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=12&Direction=".$Direction." \" >Security Level</a></th>  ";

echo "</tr>";
$i=1;
    if ($num_mod>0) { 
    foreach($arr as $row){   //Creates a loop to loop through results
      	$RPendDate=isset($row['ir']) ? $row['ir'] : $todaysDate;//$endDate;
      	$IRendDate=isset($row['co']) ? $row['co'] : $todaysDate;//$endDate;
      	$COendDate=isset($row['co']) ? $row['co'] : $todaysDate;//$endDate;



      	$RP_days=  isset($row['rp']) ? (round((strtotime($RPendDate) - strtotime($row['rp']))/(60 * 60 * 24))):0;
      	$IR_days=  isset($row['ir']) ? (round((strtotime($IRendDate) - strtotime($row['ir']))/(60 * 60 * 24))):0;
      	$CO_days=isset($row['co'])? (round((strtotime($COendDate) - strtotime($row['co']))/(60 * 60 * 24))):0;
      	


      echo "<tr><td>".$i
                      ."</td><td> <a href=\"https://csrc.nist.gov/projects/cryptographic-module-validation-program/certificate/".$row['Cert_Num']." \"  target=\"_blank\"> "
                      . $row['Cert_Num'] . "</a> </td><td> "  
                      . $row['Module_Name'] . "  </td><td>  "
                      . $row['Vendor_Name']. "  </td><td>  "
                      . $row['Clean_Lab_Name'].  "  </td><td>  "
                      . $row['rp']." </td><td> "
                      . $row['days_in_rp']." </td><td> "
                      . $row['ir']."  </td><td>  "
                      . $row['days_in_ir']."  </td><td>  "
                      . $row['co']."  </td><td>  "
                      . $row['days_in_co']."  </td><td>  "
                      . $row['fi']."  </td><td>  "
                      . $row['time_in_mip']."  </td><td>  "
                      . $row['Standard']."  </td><td>  "
                      . $row['Module_Type']."  </td><td>  "
                      . $row['SL']. "  </td></tr>";
      $i++;  
      
      } //for each
  	} //if

echo "</table>"; //Close the table in HTML


?>

