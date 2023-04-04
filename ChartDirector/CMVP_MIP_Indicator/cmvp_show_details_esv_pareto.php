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

$OrderBy = isset($_REQUEST['OrderBy']) ? $_REQUEST['OrderBy'] : '1' ;
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

$in_StandardButton1=isset($_REQUEST["in_StandardButton1"]) ? $_REQUEST["in_StandardButton1"] : 0;
$in_StandardButton2=isset($_REQUEST["in_StandardButton2"]) ? $_REQUEST["in_StandardButton2"] : 0;

$in_noise_source_button1=isset($_REQUEST["in_noise_source_button1"]) ? $_REQUEST["in_noise_source_button1"] : 0;
$in_noise_source_button2=isset($_REQUEST["in_noise_source_button2"]) ? $_REQUEST["in_noise_source_button2"] : 0;
 
$in_reuse_status_button1=isset($_REQUEST["in_reuse_status_button1"]) ? $_REQUEST["in_reuse_status_button1"] : 0;
$in_reuse_status_button2=isset($_REQUEST["in_reuse_status_button2"]) ? $_REQUEST["in_reuse_status_button2"] : 0;
 


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
      echo "pgsql=ubutun VM<br>";
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
select '".$URL_str."', (select (current_time(0) - INTERVAL '5 HOURS')),'".$today2."', 'cmvp_show_details_esv_pareto.php', '".$User."'
where not exists (     select 1 from \"CMVP_Hit_Counter\" where \"User\" = 'rfant' and \"Date\" = (select current_date) );";

//echo "hit_str=".$hit_counter;
$result = pg_query($conn, $hit_counter);
//Build our SQL string now where I've clicked on one of the bars



  if($dataSet==1)
    $where_date="  and (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) between '".$startDate."' AND '".$endDate."'";
  else if($dataSet==2)  //became active as new cert
    $where_date="  and (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) between '".$startDate."' AND '".$endDate."'";
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


// Noise Source Physical
if($in_noise_source_button1==1)
  $where_noise1 = " and ( \"Noise_Source\" = 'Physical'  ) ";
else
  $where_noise1= " and 1=1 ";

// Noise Source Non-Physical
if($in_noise_source_button2==1)
  $where_noise2 = " and (  \"Noise_Source\" = 'Non-Physical' ) ";
else
  $where_noise2= " and 1=1 ";

if($in_noise_source_button1==1 && $in_noise_source_button2==1)
{
  $where_noise1 = " and ( \"Noise_Source\" = 'Physical' OR  \"Noise_Source\" = 'Non-Physical' ) ";
  $where_noise2=" and 1=1 ";
}

// Reuse Status Restricted
if($in_reuse_status_button1==1)
  $where_reuse1 = " and ( \"Reuse_Status\" like '%restricted%'  ) ";
else
  $where_reuse1= " and 1=1 ";

// Reuse Status Open
if($in_reuse_status_button2==1)
  $where_reuse2 = " and (  \"Reuse_Status\" like '%Open%' ) ";
else
  $where_reuse2= " and 1=1 ";

if($in_reuse_status_button1==1 && $in_reuse_status_button2==1)
{
  $where_reuse1 = " and ( \"Reuse_Status\" like '%restricted%' OR  \"Reuse_Status\" like '%Open%' ) ";
  $where_reuse2=" and 1=1 ";
}




$sql_Str = "select \"Row_ID\", \"ESV_Cert_Num\" ,\"Implementation_Name\",\"Vendor_Name\",\"Clean_Lab_Name\" ,(TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY'))as validation_date,\"Reuse_Status\",\"Noise_Source\" ,\"Lab_Name\" ,\"OE\",  \"Description\",\"Version\", \"CAVP_Certs\",\"Sample_Size\", \"Status3\"
from \"CMVP_ESV_Table\" "
  . " where  1=1  ".$where_reuse1.$where_reuse2.$where_vendor.$where_vendor2.$where_noise1.$where_noise2."   and \"Clean_Lab_Name\" like '" . $xLabel. "%' 
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

// "ESV_Cert_Num" ,"Implementation_Name","Vendor_Name","Clean_Lab_Name" ,"Validation_Date","Reuse_Status\","Noise_Source" ,"Lab_Name" ,"OE","Description","Version", "CAVP_Certs","Sample_Size"
//
echo "<tr> ";

echo "<th bgcolor=LightBlue >Row</th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_esv_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=1&Direction=".$Direction." \" >ESV Cert</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_esv_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=3&Direction=".$Direction." \" >Implementation</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_esv_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=4&Direction=".$Direction." \" >Vendor</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_esv_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=5&Direction=".$Direction." \" >Lab Name</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_esv_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=6&Direction=".$Direction." \" >Validation Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_esv_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=7&Direction=".$Direction." \" >Reuse Status</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_esv_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=8&Direction=".$Direction." \" >Noise Source</a></th>  ";





echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_esv_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=11&Direction=".$Direction." \" >Noise Description</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_esv_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=12&Direction=".$Direction." \" >OE</a></th>  ";



echo "<th bgcolor=LightBlue ><a href=\"".$URL_path."/cmvp_show_details_esv_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&show_detail_value=".$show_detail_value."&dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=14&Direction=".$Direction." \" >Entropy per Sample Size</a></th>  ";


echo "</tr>";
//echo "</table>";
//echo "<table>";

// "ESV_Cert_Num" ,"Implementation_Name","Vendor_Name","Clean_Lab_Name" ,"Validation_Date","Reuse_Status\","Noise_Source" ,"Lab_Name" ,"OE","Description","Version", "CAVP_Certs","Sample_Size"

//https://csrc.nist.gov/CSRC/media/projects/cryptographic-module-validation-program/documents/security-policies/140sp3855.pdf
$i=1;
    if ($num_mod>0) { 
    foreach($arr as $row){   //Creates a loop to loop through results
      echo "<tr><td>".$i
                      ."</td><td>  <a href=\"https://csrc.nist.gov/projects/cryptographic-module-validation-program/entropy-validations/certificate/".substr($row['ESV_Cert_Num'],1)." \"  target=\"_blank\"> "
                      . $row['ESV_Cert_Num'] . "</a> </td><td> "  
                      . $row['Implementation_Name'] . ", <a href=\"https://csrc.nist.gov/CSRC/media/projects/cryptographic-module-validation-program/documents/entropy/".$row['ESV_Cert_Num']."_PublicUse.pdf \"  target=\"_blank\">[PUD] </a> </td><td>  "
                      . $row['Vendor_Name']. "  </td><td>  "
                      . $row['Clean_Lab_Name'].  "  </td><td>  "
                      . $row['validation_date']."  </td><td>  "
                      . $row['Reuse_Status']."  </td><td>  "
                      . $row['Noise_Source']. "  </td><td>  "     
                      . $row['Description']." </td><td> "
                      . $row['OE']."  </td><td>  "
                      . "Ent: ".$row['Sample_Size']. "  </td></tr>";
      $i++;  
      
      } //for each
  	} //if

echo "</table>"; //Close the table in HTML

?>
           