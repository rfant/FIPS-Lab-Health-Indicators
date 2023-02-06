<!DOCTYPE HTML>  
<html>
<head>
<style>
.error {color: #FF0000;}
</style>
</head>
<body>  

<?php
//require_once("phpchartdir.php");
//this php file defines whether the URL is for production or development for all the PHP files.
include './cmvp_define_LHI_dev_vs_prod.php';

$admin_option=isset($_REQUEST["admin_option"]) ? $_REQUEST["admin_option"] : 0;
$add_name=isset($_REQUEST["add_name"]) ? $_REQUEST["add_name"] : "";
$delete_name=isset($_REQUEST["delete_name"]) ? $_REQUEST["delete_name"] : "";
$which_CMVP_list=isset($_REQUEST["which_CMVP_list"]) ? $_REQUEST["which_CMVP_list"] : "";  //this will either be null, MIP or Validated
$add_cert=isset($_REQUEST["add_cert"]) ? $_REQUEST["add_cert"] : "";

$add_module_name=isset($_REQUEST["add_module_name"]) ? $_REQUEST["add_module_name"] : "";
$add_vendor_name=isset($_REQUEST["add_vendor_name"]) ? $_REQUEST["add_vendor_name"] : "";
$add_lab_name=isset($_REQUEST["add_lab_name"]) ? $_REQUEST["add_lab_name"] : "";
$add_module_type=isset($_REQUEST["add_module_type"]) ? $_REQUEST["add_module_type"] : "";
$add_security_level=isset($_REQUEST["add_security_level"]) ? $_REQUEST["add_security_level"] : "";


if($add_name!=null)
	$admin_option=3;

if($delete_name!=null)
	$admin_option=4;

if($which_CMVP_list!=null)
	$admin_option=6;

if($add_cert!=null)
	$admin_option=7;

if($add_module_name!=null)
	$admin_option=8;



//echo "<br>option=$admin_option.  add_cert=$add_cert.  which_CMVP_list=$which_CMVP_list module_name=$add_module_name vendor_name=$add_vendor_name <br>";

 $now = date("Y-m-d");
 

 $today2 = isset($_POST['today2']) ? $_POST['today2'] : (new DateTime)->format('Y-m-d');

//---------------------------------------------------
//get the user from the Cloud Foundry PHP variable
ob_start();

// send phpinfo content
phpinfo();

// get phpinfo content
$User = ob_get_contents();

// flush the output buffer
ob_end_clean();



//figure out if it's the Production or Demo Version
switch ($PROD) {
    case 2:  //postgresql database on Ubuntu VM machine 
     	
     	$encryptedPW="xtw2D3obQa8=";
     	echo "pgsql=ubutun VM";
  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
		$connStr = "host=localhost  dbname=postgres user=postgres password=".$decryptedPW." connect_timeout=5 options='--application_name=$appName'";
		$User=get_current_user();
		break;
    case 1: //postgresql database on intel interanet production
		$encryptedPW="WDu8gYvvVn6Pxw==";
		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
  		$connStr = "host=postgres5320-lb-fm-in.dbaas.intel.com  port=5432 dbname=lhi_prod2 user=lhi_prod2_so password=".$decryptedPW."  connect_timeout=5 options='--application_name=$appName'";
      	$User = isset($_COOKIE['IDSID']) ? $_COOKIE['IDSID'] : '<i>no value</i>';
      break;
    
    default:
    	echo "ERROR 26: unknown PROD value";

	}


//------------------------------------------------------------------
//connect to database

$conn = pg_connect($connStr);
$stat = pg_connection_status($conn);
		
if ($stat === PGSQL_CONNECTION_OK) 
{
     	//	echo '<br><br>PGSQL Connection status ok';
} 
 else {echo '<br>ERROR 70: PGSQL Connection status bad<br>';  }


//-------------------------------------------------------------------------


//----------------------------------------------------------------------------
//Now, execute which ever code is needed

$get_log_file_name= " Select * from \"MIP_Error_Table\" order by \"Row_ID\" desc";

	 //echo "log_file_name_str=".$get_log_file_name;
$result = pg_query($conn, $get_log_file_name);

$arr = pg_fetch_all($result);

if($arr==null)
	$num_rows=0;
else
	$num_rows=sizeof($arr);



if ($num_rows>0) { 
   	foreach($arr as $row){   //Creates a loop to loop through results
			echo "<tr><td>"
          . $row['Error_Log_File']. "  </td><td>  "
          . "  </tr>";

	} //for each
} //if


 //echo file_get_contents( "filename.php" );


?>
</body>
</html>