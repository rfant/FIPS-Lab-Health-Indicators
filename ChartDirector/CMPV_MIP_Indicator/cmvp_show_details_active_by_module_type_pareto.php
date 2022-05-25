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
$dataSetName = $_REQUEST["dataSetName"]; // "SL"
$xLabel= $_REQUEST["xLabel"]; //Module TYpe
$dataSet=$_REQUEST["dataSet"];  //Intel OR non-Intel


$today1 = isset($_POST['today1']) ? $_POST['today1'] : '1995-01-01' ; //Ealiest CMVP validation date
$today2 = isset($_POST['today2']) ? $_POST['today2'] : (new DateTime)->format('Y-m-d');

$OrderBy = isset($_REQUEST['OrderBy']) ? $_REQUEST['OrderBy'] : '3' ;
$Direction = isset($_REQUEST['Direction']) ? $_REQUEST['Direction'] : 'desc' ;

//toggle the direction each time.
if($Direction=='asc')
	$Direction='desc';
else
	$Direction='asc';

if($dataSet==0 || $dataSet==1 || $dataSet==2 || $dataSet==3)
	$where_clause = " and \"Vendor_Name\" not like '%Intel Corp%' ";
else
	$where_clause= " and \"Vendor_Name\" like '%Intel Corp%' ";


//echo "startDate=".$startDate." ";
//echo "endDate=".$endDate."<br></br> ";
//echo "dataSetname=".$dataSetName."<br></br>";
//echo "xLabel=".$xLabel."<br></br>";
//echo "OrderBy=".$OrderBy."<br></br>";
//echo "Direction=".$Direction."<br></br>";

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
'cmvp_show_details_active_by_module_type_pareto.php','".$User."')";
$result = pg_query($conn, $hit_counter);



$sql_Str = "select to_number(\"Cert_Num\",'99999') as \"Cert_Num\",\"Module_Name\",\"Vendor_Name\",\"Clean_Lab_Name\" ,(TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY'))as validation_date,\"Status\",\"Standard\" ,\"Lab_Name\" ,\"Module_Type\",  \"SL\" from \"CMVP_Active_Table\" "
  . " where \"SL\" like '" . substr($dataSetName, -1) . "' and \"Module_Type\" like '%" . $xLabel . "' and
	(TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) between'".$startDate."' and  '".$endDate."' ".$where_clause." 
	  order by ".$OrderBy." ".$Direction." ; ";

//echo "Alpha SQL= " . $sql_Str ;


$result = pg_query($conn, $sql_Str);
$arr = pg_fetch_all($result);
if($arr==null)
	$num_mod=0;
else
	$num_mod=sizeof($arr);

//$num_mod=(null!==$arr) ? sizeof($arr) : '0';
echo $xLabel." ".$dataSetName." modules: " . $num_mod;
 
echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";

echo " <br></br>";
  
 echo "<table>"; // start a table tag in the HTML



echo "<tr> ";
echo "<th bgcolor=LightBlue >Row</th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_show_details_active_by_module_type_pareto.php?dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=1&Direction=".$Direction." \" >Cert</a></th>  ";


echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_show_details_active_by_module_type_pareto.php?dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=2&Direction=".$Direction." \" >Module</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_show_details_active_by_module_type_pareto.php?dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=3&Direction=".$Direction." \" >Vendor</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_show_details_active_by_module_type_pareto.php?dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=4&Direction=".$Direction." \" >Lab</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_show_details_active_by_module_type_pareto.php?dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=5&Direction=".$Direction." \" >Validation Date</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_show_details_active_by_module_type_pareto.php?dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=6&Direction=".$Direction." \" >Status</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_show_details_active_by_module_type_pareto.php?dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=7&Direction=".$Direction." \" >Standard</a></th>  ";


//NOTE: skip OrderBy=8 18  since I don't want to see the "Lab Name" since I've already shown the "clean Lab name"

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_show_details_active_by_module_type_pareto.php?dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=9&Direction=".$Direction." \" >Module Type</a></th>  ";

echo "<th bgcolor=LightBlue ><a href=\"http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_show_details_active_by_module_type_pareto.php?dataSet=".$dataSet."&xLabel=".$xLabel."&dataSetName=".$dataSetName."&startDate=".$startDate."&endDate=".$endDate."&OrderBy=10&Direction=".$Direction." \" >SL</a></th>  ";


echo "</tr>";
$i=1;
    if ($num_mod>0) {
	    foreach($arr as $row){   //Creates a loop to loop through results
	      echo "<tr><td>".$i
	      				."</td><td> <a href=\"https://csrc.nist.gov/projects/cryptographic-module-validation-program/certificate/".$row['Cert_Num']." \"  target=\"_blank\"> "
	      				. $row['Cert_Num'] . "</a>  </td><td>  "  
	      				. $row['Module_Name'] . ", <a href=\"https://csrc.nist.gov/CSRC/media/projects/cryptographic-module-validation-program/documents/security-policies/140sp".$row['Cert_Num'].".pdf \"  target=\"_blank\">[SP] </a> </td><td>  "
	                    . $row['Vendor_Name']. "  </td><td>  "
	                    . $row['Lab_Name'].  "  </td><td>  "
	                    . $row['validation_date']."  </td><td>  "
	                    . $row['Status']. "  </td><td>  "     
	                    . $row['Standard']." </td><td> "
	                    . $row['Module_Type']."  </td><td>  "
	                    . $row['SL']. "  </td></tr>";  
	      //$row['index'] the index here is a field name
	       $i++; 
	      } //for each
	  } //if num_mod

echo "</table>"; //Close the table in HTML

?>