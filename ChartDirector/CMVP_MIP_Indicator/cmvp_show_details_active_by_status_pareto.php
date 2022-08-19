<?php

//this php file defines whether the URL is for production or development for all the PHP files.
include './cmvp_define_LHI_dev_vs_prod.php';

//==========================================================

//============================================================

define        ("red",0x00FF0000);
define      ("green",0x0000FF00);
define       ("blue",0x000000FF);
define ("light_blue",0x0099ccff);
define  ("deep_blue",0x000000cc);
define      ("white",0x00FFFFFF);
define      ("black",0x00000000);
define      ("grey1",0x00dcdcdc);
define      ("grey2",0x00f6f6f6);

#=======================================================
//variables passed into this program from the calling program
$startDate=$_REQUEST["startDate"];
$endDate=$_REQUEST["endDate"];
$dataSetName = isset($_REQUEST['dataSetName'])?$_REQUEST["dataSetName"] :'';
$xLabel= isset($_REQUEST["xLabel"])?$_REQUEST["xLabel"]:'';
$dataSet=isset($_REQUEST["dataSet"])?$_REQUEST["dataSet"]:'';
$show_detail_value=isset($_REQUEST['show_detail_value']) ? $_REQUEST['show_detail_value'] : '';
//$value= $_REQUEST["value"];

$today1 = isset($_POST['today1']) ? $_POST['today1'] : '1995-01-01' ; //Ealiest CMVP validation date
 $today2 = isset($_POST['today2']) ? $_POST['today2'] : (new DateTime)->format('Y-m-d');

$OrderBy = isset($_REQUEST['OrderBy']) ? $_REQUEST['OrderBy'] : '3' ;
$Direction = isset($_REQUEST['Direction']) ? $_REQUEST['Direction'] : 'desc' ;

//toggle the direction each time.
if($Direction=='asc')
	$Direction='desc';
else
	$Direction='asc';

//1 means selected. 0 means not selected
 $in_IntelOnlyButton=isset($_REQUEST["in_IntelOnlyButton"]) ? $_REQUEST["in_IntelOnlyButton"] : 0;
 $in_IntelOnlyButton2=isset($_REQUEST["in_IntelOnlyButton2"]) ? $_REQUEST["in_IntelOnlyButton2"] : 0;

 $in_ModuleTypeButton=isset($_REQUEST["in_ModuleTypeButton"]) ? $_REQUEST["in_ModuleTypeButton"] : 0;
 $in_SecurityLevelButton=isset($_REQUEST["in_SecurityLevelButton"]) ? $_REQUEST["in_SecurityLevelButton"] : 0;
 
//echo "IntelOnly=".$in_IntelOnlyButton." MT=".$in_ModuleTypeButton." SL=".$in_SecurityLevelButton."<BR>";


//echo "startDate=".$startDate." ";
//echo "endDate=".$endDate."<br></br> ";


#===========================================================================

#connect to postgreSQL database and get my chart data

//get the user from the Cloud Foundry PHP variable
ob_start();

// send phpinfo content
phpinfo();

// get phpinfo content
$User = ob_get_contents();

// flush the output buffer
ob_end_clean();

$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

switch ($PROD) {
    case 2:  //postgresql database on Ubuntu VM machine 
      $encryptedPW="xtw2D3obQa8=";
      $decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
      $connStr = "host=localhost  dbname=postgres user=postgres password=".$decryptedPW." connect_timeout=5 options='--application_name=$appName'";
      echo "pgsql=ubutun VM";
      $User=get_current_user();
        break;
    case 1: //postgresql database on intel interanet production
     $encryptedPW="WDu8gYvvVn6Pxw==";
      $decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
      $connStr = "host=postgres5320-lb-fm-in.dbaas.intel.com  port=5432 dbname=lhi_prod2 user=lhi_prod2_so password=".$decryptedPW."  connect_timeout=5 options='--application_name=$appName'";
      $User = isset($_COOKIE['IDSID']) ? $_COOKIE['IDSID'] : '<i>no value</i>';
      break;
    
    default:
      echo "ERROR: unknown PROD value";

  }


//echo "PROD= $PROD"." ConnStr= ".$connStr;

//echo "<br>$User<br>";

//=====================================================

$conn = pg_connect($connStr);

//$hit_counter= " INSERT INTO \"CMVP_Hit_Counter\" ( \"URL\", \"Timestamp\",\"Date\", \"Application\",\"User\") values('".$URL_str."',(select (current_time(0) - INTERVAL '5 HOURS')),'". $today2."','cmvp_active_by_status_pareto.php','".$User."')";


//Don't add the developer "rfant' since there will be too many hits then.
$hit_counter=  " INSERT INTO \"CMVP_Hit_Counter\" (\"URL\",\"Timestamp\",\"Date\", \"Application\",\"User\") 
select '".$URL_str."', (select (current_time(0) - INTERVAL '5 HOURS')),'".$today2."', 'cmvp_show_details_active_by_status_pareto.php', '".$User."'
where not exists (     select 1 from \"CMVP_Hit_Counter\" where \"User\" = 'rfant' and \"Date\" = (select current_date) );";

//echo "hit_str=".$hit_counter;
$result = pg_query($conn, $hit_counter);
//Build our SQL string now where I've clicked on one of the bars



  if($dataSet==1)
    $where_date="  and (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) between '".$startDate."' AND '".$endDate."'";
  else if($dataSet==2)  //became active as new cert
    $where_date=" and length (\"Validation_Date\") = 10  and (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) between '".$startDate."' AND '".$endDate."'";
  else if ($dataSet==3)
    $where_date=" and (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY'))  < '".$startDate."'  ";
  else if ($dataSet==4)  //already active but revalidatedbetween
    $where_date=" and length (\"Validation_Date\") > 10  and (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) between '".$startDate."' AND '".$endDate."'";
  else
    $where_date=" ";
//}

$order_by_str =" order by ".$OrderBy." ".$Direction." ; ";


// Intel Vendor Only
if($in_IntelOnlyButton==1)
  $where_vendor = " and ( \"Vendor_Name\" like '%Intel Corp%'  ) ";
else
  $where_vendor= " and 1=1 ";

// Intel Vendor Only2
if($in_IntelOnlyButton2==1)
  $where_vendor2 = " and (  \"Status3\" like '%Intel_Certifiable%' ) ";
else
  $where_vendor2= " and 1=1 ";

if($in_IntelOnlyButton==1 && $in_IntelOnlyButton2==1)
{
  $where_vendor = " and ( \"Vendor_Name\" like '%Intel Corp%' OR  \"Status3\" like '%Intel_Certifiable%' ) ";
  $where_vendor2=" and 1=1 ";
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


//Module Type
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



$sql_Str = "select \"Cert_Num\"::int ,\"Module_Name\",\"Vendor_Name\",\"Clean_Lab_Name\" ,(TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY'))as validation_date,\"Sunset_Date\",\"Status\",\"Standard\" ,\"Lab_Name\" ,\"Module_Type\",  \"SL\", 
\"FIPS_Algorithms\"
from \"CMVP_Active_Table\" "
  . " where  1=1  ".$where_vendor.$where_vendor2.$where_security.$where_MT." AND \"Status\" like '%' || right('" .$dataSetName . "',6) || '%'  and \"Clean_Lab_Name\" like '" . $xLabel. "%' 
  ".$where_date.$order_by_str."

 ";


//echo "Alpha SQL=<br>" . $sql_Str. "<br></br>" ;


$result = pg_query($conn, $sql_Str);
$arr = pg_fetch_all($result);

if($arr==null)
	$num_mod=0;
else
	$num_mod=sizeof($arr);


echo $xLabel." ".$dataSetName." modules: " . $num_mod;

//draw the "back" 
//echo " <button  style=\"background-color: silver;\" type=\"button\" ";
//echo "  onclick=\"window.location.href='http:".$URL_str."/cmvp_active_by_status_pareto.php?in_button=1&startDate='+ ".$startDate." +'&endDate='+ ".$endDate.";\"> BACK </button>"; 
//echo "  onclick=\"window.location.href='http:".$URL_str."/cmvp_active_by_status_pareto.php;\"> BACK </button>"; 
         
  
 

          
          



echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";
//td td:nth-child(1) { text-align: center;}
echo " <br></br>";

      
    echo "<table>"; // start a table tag in the HTML



echo "<tr> ";

echo "<th bgcolor=LightBlue >Row</th>  ";
//echo "<th bgcolor=LightBlue ><a href=\"/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=1&Direction=".$Direction." \" >Cert</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=1&Direction=".$Direction." \" >Cert</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=2&Direction=".$Direction." \" >Module</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=3&Direction=".$Direction." \" >Vendor</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=4&Direction=".$Direction." \" >Lab</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=5&Direction=".$Direction." \" >Validation Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=6&Direction=".$Direction." \" >Sunset Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=7&Direction=".$Direction." \" >Status</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=8&Direction=".$Direction." \" >Standard</a></th>  ";

//Skipping 9 since that just displays the lab name again

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=10&Direction=".$Direction." \" >Module Type</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=11&Direction=".$Direction." \" >SL</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=12&Direction=".$Direction." \" >FIPS Algorithms</a></th>  ";


echo "</tr>";
//echo "</table>";
//echo "<table>";

//https://csrc.nist.gov/CSRC/media/projects/cryptographic-module-validation-program/documents/security-policies/140sp3855.pdf
$i=1;
    if ($num_mod>0) { 
    foreach($arr as $row){   //Creates a loop to loop through results
      echo "<tr><td>".$i
                      ."</td><td>  <a href=\"https://csrc.nist.gov/projects/cryptographic-module-validation-program/certificate/".$row['Cert_Num']." \"  target=\"_blank\"> "
                      . $row['Cert_Num'] . "</a> </td><td> "  
                      . $row['Module_Name'] . ", <a href=\"https://csrc.nist.gov/CSRC/media/projects/cryptographic-module-validation-program/documents/security-policies/140sp".$row['Cert_Num'].".pdf \"  target=\"_blank\">[SP] </a> </td><td>  "
                      . $row['Vendor_Name']. "  </td><td>  "
                      . $row['Lab_Name'].  "  </td><td>  "
                      . $row['validation_date']."  </td><td>  "
                      . $row['Sunset_Date']."  </td><td>  "
                      . $row['Status']. "  </td><td>  "     
                      . $row['Standard']." </td><td> "
                      . $row['Module_Type']."  </td><td>  "
                      . $row['SL']."  </td><td>  "
                      . $row['FIPS_Algorithms']. "  </td></tr>";
      $i++;  
      
      } //for each
  	} //if

echo "</table>"; //Close the table in HTML

?>
           