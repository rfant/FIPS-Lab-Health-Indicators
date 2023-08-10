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

define        ("red",0x00FF0000);
define      ("green",0x0000FF00);
define       ("blue",0x000000FF);
define ("light_blue",0x00eeeeff);
define  ("deep_blue",0x000000cc);
define      ("white",0x00FFFFFF);
define      ("black",0x00000000);
define      ("grey1",0x00dcdcdc);
define      ("grey2",0x00f6f6f6);
//-----------------------------------------------------------------
function prevent_SQL_injection_attack(&$keyword) {

//echo "before-inside $keyword <br>";  
$keyword= str_replace("'","%",$keyword);
$keyword= str_replace(";","",$keyword);
$keyword= str_replace("\"","",$keyword);
$keyword= str_replace("®","",$keyword);
$keyword= str_replace("™","",$keyword);



//echo "after-inside $keyword<br>";

return($keyword);

}
//----------------------------------------------------------------





$search_option=isset($_REQUEST["search_option"]) ? $_REQUEST["search_option"] : 0;
$which_CMVP_list_1=1; //isset($_REQUEST["which_CMVP_list_1"]) ? $_REQUEST["which_CMVP_list_1"] : "";  //this will either be null or Validated
$which_CMVP_list_2=1; //isset($_REQUEST["which_CMVP_list_2"]) ? $_REQUEST["which_CMVP_list_2"] : "";  //this will either be null or MIP
$which_CMVP_list_3=1; //isset($_REQUEST["which_CMVP_list_3"]) ? $_REQUEST["which_CMVP_list_3"] : "";  //this will either be null or ESV
$which_CMVP_list_4=1;//isset($_REQUEST["which_CMVP_list_4"]) ? $_REQUEST["which_CMVP_list_4"] : "";  //this will either be null or CAVP





$search_cert=isset($_REQUEST["search_cert"]) ? $_REQUEST["search_cert"] : "";

$search_module_name=isset($_REQUEST["search_module_name"]) ? $_REQUEST["search_module_name"] : "";
$search_vendor_name="Intel Corp"; //isset($_REQUEST["search_vendor_name"]) ? $_REQUEST["search_vendor_name"] : "";
$search_lab_name=isset($_REQUEST["search_lab_name"]) ? $_REQUEST["search_lab_name"] : "";
$search_module_type=isset($_REQUEST["search_module_type"]) ? $_REQUEST["search_module_type"] : "";
$search_security_level=isset($_REQUEST["search_security_level"]) ? $_REQUEST["search_security_level"] : "";

//echo "<br>option=$search_option.  cert=$search_cert.  <br> list1=$which_CMVP_list_1 <br>list2=$which_CMVP_list_2. <br>list3=$which_CMVP_list_3 <br> module_name=$search_module_name. <br> vendor_name=$search_vendor_name. <br>";
//echo "lab_name=$search_lab_name.  <br>module_type=$search_module_type.   <br>";

prevent_SQL_injection_attack($search_cert);
prevent_SQL_injection_attack($search_module_name);
prevent_SQL_injection_attack($search_vendor_name);
prevent_SQL_injection_attack($search_lab_name);
prevent_SQL_injection_attack($search_module_type);
prevent_SQL_injection_attack($search_security_level);



if($which_CMVP_list_1 ==null AND $which_CMVP_list_2==null AND $which_CMVP_list_3==null AND $which_CMVP_list_4==null)
	$search_option=0;
else
	$search_option=1;



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
		
if ($stat == PGSQL_CONNECTION_OK) 
{
     	//	echo '<br><br>PGSQL Connection status ok';
} 
 else {echo '<br>ERROR 70: PGSQL Connection status bad<br>';  }


//-------------------------------------------------------------------------
// Hit Counter for Admin Page

//Only add to the Admin Hit Counter once per day per visit. otherwise  there may be too many duplicate hits then. 
$hit_counter=  "INSERT INTO \"CMVP_Hit_Counter\" (\"URL\",\"Timestamp\",\"Date\", \"Application\",\"User\") 
select '".$URL_str."', (select (current_time(0) - INTERVAL '5 HOURS')),'".$today2."', 'Search Page', '".$User."'
where not exists ( select 1 from \"CMVP_Hit_Counter\" where \"User\" = '".$User."' and \"Date\" = (select current_date) and \"Application\" like '%Admin%');";

//echo "<br>hit_str=".$hit_counter;
$result = pg_query($conn, $hit_counter);


//remove all the older entries for 'rfant' since there will be tons of them.
$hit_counter= "delete from \"CMVP_Hit_Counter\" where \"Date\" <> (select current_date) and \"User\" like 'rfant'; ";
//echo "<br>hit_str=".$hit_counter;
$result = pg_query($conn, $hit_counter);


//echo "search option=$serach_option";

//----------------------------------------------------------------------------
//Now, select & search 


 //get the information for which database list to search


// define variables and set to empty values
$nameErr = $emailErr = $genderErr = $websiteErr = "";
$name = $email = $gender = $comment = $website = "";



//---------------------------------------------------------------------------------
// now do the sql query and print out the tables

if ($search_option==1)
{ //big if search_option

	//------------------------------------------

	if($which_CMVP_list_1 != null)   
	{ //validated list 1


		if ($search_cert == null)
			$cert_str= " and 1=1 ";
		else
			$cert_str= " and \"Cert_Num\" = ".$search_cert." ";


		if ($search_module_name== null)
			$module_name_str= " and 1=1 ";
		else
			$module_name_str= " and upper(\"Module_Name\") like upper('%".$search_module_name."%')  ";

		
		$vendor_name_str= " and (   upper(\"Vendor_Name\") like upper('%".$search_vendor_name."%')  OR   \"Status3\" like '%Intel_Certifiable%' ) ";
		//$vendor2_name_str = " and (  \"Status3\" like '%Intel_Certifiable%' ) ";


		if ($search_lab_name==null)
			$lab_name_str=" and 1=1 ";
		else
			$lab_name_str= " and upper(\"Clean_Lab_Name\") like upper('%".$search_lab_name."%') ";


		if ($search_module_type==null)
			$module_type_str=" and 1=1 ";
		else
			$module_type_str= " and upper(\"Module_Type\") like upper('%".$search_module_type."%') ";


		if ($search_security_level==null)
			$security_level_str=" and 1=1 ";
		else
			$security_level_str= " and \"SL\" = ".$search_security_level." ";

		$str1_sql= " Select * from \"CMVP_Active_Table\" where 1=1 ".$cert_str.$module_name_str.$vendor_name_str.$lab_name_str.$module_type_str.$security_level_str."   order by \"Row_ID\" desc";

	 	//echo "alpha Validate str1_sql=<br>".$str1_sql;
		$result = pg_query($conn, $str1_sql);

		$arr = pg_fetch_all($result);

		if($arr==null)
			$num_rows=0;
		else
			$num_rows=sizeof($arr);
		
		echo"<h1> Validated Module List ($num_rows items)   <font color=\"#ff1493\">Color</font>=Certifiable</h1>";

  	echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
	  echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";
		    
   	echo "<table>"; // start a table tag in the HTML
		

		echo "<tr> ";
		echo "<th bgcolor=LightBlue >Row</th>  ";
		echo "<th bgcolor=LightBlue >Cert_Num</th>  ";
		echo "<th bgcolor=LightBlue >Status</th>  ";
		echo "<th bgcolor=LightBlue >Sunset_Date</th>  ";
		echo "<th bgcolor=LightBlue >Module Name</th>  ";
		echo "<th bgcolor=LightBlue >VendorName</th>  ";
		echo "<th bgcolor=LightBlue >Lab Name</th>  ";
		echo "<th bgcolor=LightBlue >Module Type</th>  ";
		echo "<th bgcolor=LightBlue >Standard</th>  ";
		echo "<th bgcolor=LightBlue >Security Level</th>  ";
		echo "</tr>";

	
		$i=1;
   	if ($num_rows>0) { 
  		foreach($arr as $row){   //Creates a loop to loop through results
				
  		
  			if ($row['Vendor_Name']=="Intel Corporation" ) 
  				$my_color="\"#ffffff\""; //white: certified
  			else
  				$my_color="\"#ff1493\"";  //yellow: certifiable

				echo "<tr> <td>".$i
          ."</td><td>  <a href=\"https://csrc.nist.gov/projects/cryptographic-module-validation-program/certificate/".$row['Cert_Num']." \"  target=\"_blank\"> "
			  . $row['Cert_Num']. "  </td><td>  "
			  . $row['Status']. "  </td><td>  "
			  . $row['Sunset_Date']. "  </td><td>  "
              . $row['Module_Name']. "  </td><td bgcolor=".$my_color."> "
              . $row['Vendor_Name'].  "  </td><td>  "
              . $row['Clean_Lab_Name']."  </td><td>  "
              . $row['Clean_Module_Type']."  </td><td>  "
              . $row['Standard']."  </td><td>  "
              . $row['SL']."  </td>  "
              . "  </tr>";
				$i++; 

    	} //for each
		} //if //num rows >0

		if($arr==null)
	  		echo "<tr>  <font color=red> Nothing Matches Those Search Parameters <font color=black></tr>";
		echo "</table>"; //Close the table in HTML	

 		

	} //validated list 1

//----------------------------------------
	if($which_CMVP_list_2 != null)   
	{ //MIP list 2


		if ($search_cert == null)
			$cert_str= " and 1=1 ";
		else
			$cert_str= " and \"Cert_Num\" = ".$search_cert." ";


		if ($search_module_name== null)
			$module_name_str= " and 1=1 ";
		else
			$module_name_str= " and upper(\"Module_Name\") like upper('%".$search_module_name."%')  ";

		$vendor_name_str= " and (upper(\"Vendor_Name\") like upper('%".$search_vendor_name."%') OR \"Status3\" like '%Intel_Certifiable%' ) ";
		

		if ($search_lab_name==null)
			$lab_name_str=" and 1=1 ";
		else
			$lab_name_str= " and upper(\"Clean_Lab_Name\") like upper('%".$search_lab_name."%') ";


		if ($search_module_type==null)
			$module_type_str=" and 1=1 ";
		else
			$module_type_str= " and upper(\"Module_Type\") like upper('%".$search_module_type."%') ";


		if ($search_security_level==null)
			$security_level_str=" and 1=1 ";
		else
			$security_level_str= " and \"SL\" = ".$search_security_level." ";

		$str1_sql= " Select *, 
		(case 	when \"In_Review_Start_Date\" is null then (select current_date)::date - \"Review_Pending_Start_Date\"::date else \"In_Review_Start_Date\"::date - \"Review_Pending_Start_Date\"::date end )as rpDays, 
		(case when \"Coordination_Start_Date\" is null then (select current_date)::date - \"In_Review_Start_Date\"::date else \"Coordination_Start_Date\"::date - \"In_Review_Start_Date\"::date end) as irDays,
		(case when \"Finalization_Start_Date\" is null then (select current_date)::date - \"Coordination_Start_Date\"::date else 
		 \"Finalization_Start_Date\"::date-\"Coordination_Start_Date\"::date end) as coDays,

	 (case when \"Finalization_Start_Date\" is not null then \"Finalization_Start_Date\"::date - \"Review_Pending_Start_Date\"::date else  
		(select current_date)::date - \"Review_Pending_Start_Date\"::date 	end)	as totalDays
			from \"CMVP_MIP_Table\" 
			where 1=1 ".$cert_str.$module_name_str.$vendor_name_str.$lab_name_str.$module_type_str.$security_level_str."   ";
		
		$str1_sql=$str1_sql." order by \"Cert_Num\" desc,\"Review_Pending_Start_Date\" desc, \"Module_Name\" ";

	 	
	 	//echo "Bravo MIP str1_sql=<br>".$str1_sql;
		$result = pg_query($conn, $str1_sql);
			$arr = pg_fetch_all($result);

		if($arr==null)
			$num_rows=0;
		else
			$num_rows=sizeof($arr);
		
		echo"<h1>MIP List ($num_rows items) <font color=\"#ff1493\">Color</font>=Certifiable</h1>";


    	echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
			echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";
			
	      
    	echo "<table>"; // start a table tag in the HTML
		echo "<tr> ";
		echo "<th bgcolor=LightBlue >Row</th>  ";
		echo "<th bgcolor=LightBlue >Cert_Num</th>  ";
		echo "<th bgcolor=LightBlue >Module</th>  ";
		echo "<th bgcolor=LightBlue >Vendor</th>  ";
		echo "<th bgcolor=LightBlue >Lab</th>  ";
		echo "<th bgcolor=LightBlue >RP Start Date</th>  ";
		echo "<th bgcolor=LightBlue >Days in RP</th>  ";
		echo "<th bgcolor=LightBlue >IR Start Date</th>  ";
		echo "<th bgcolor=LightBlue >Days in IR</th>  ";
		echo "<th bgcolor=LightBlue >CO Start Date</th>  ";
		echo "<th bgcolor=LightBlue >Days in CO</th>  ";
		echo "<th bgcolor=LightBlue >FI Start Date</th>  ";
		echo "<th bgcolor=LightBlue >Total Days RP+IR+CO</th>  ";
		echo "<th bgcolor=LightBlue >Module Type</th>  ";
		echo "<th bgcolor=LightBlue >SL</th>  ";
		echo "<th bgcolor=LightBlue >Standard</th>  ";
		echo "</tr>";

		$i=1;
   		 if ($num_rows>0) { 
	 		foreach($arr as $row){   //Creates a loop to loop through results

				if ($row['Vendor_Name']=="Intel Corporation" ) 
	  				$my_color="\"#ffffff\""; //white: certified
	  			else
	  				$my_color="\"#ff1493\"";  //yellow: certifiable



				 if($row['Cert_Num'] != null)
				 {
				 	echo "<tr bgcolor=gray><td>";   //gray out any modules here that have already been promoted to the validated list
				 	 if ($row['Vendor_Name']=="Intel Corporation" ) 
	  					$my_color="\"#808080\""; //gray: certified
				  			else
	  					$my_color="\"#ff1493\"";  //yellow: certifiable

				 }
				 else	
					echo "<tr><td>";
				 echo $i
          ."</td><td>  <a href=\"https://csrc.nist.gov/projects/cryptographic-module-validation-program/certificate/".$row['Cert_Num']." \"  target=\"_blank\"> "
          . $row['Cert_Num'] . " </a>  </td><td>  "  
          . $row['Module_Name'] . "  </td><td  bgcolor=".$my_color.">  "
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
  	if($arr==null)
	  		echo "<tr>  <font color=red> Nothing Matches Those Search Parameters <font color=black></tr>";
	echo "</table>"; //Close the table in HTML

		
  		

	} //MIP list 2




//-----------------------------------
	if($which_CMVP_list_3 != null)   
	{ //ESV list 3


		$search_esv_cert=$search_cert;
		$search_implementation_name=$search_module_name;


		if ($search_esv_cert == null)
			$esv_cert_str= " and 1=1 ";
		else
			$esv_cert_str= " and \"ESV_Cert_Num\" = ".$search_esv_cert." ";


		if ($search_implementation_name== null)
			$implementation_name_str= " and 1=1 ";
		else
			$implementation_name_str= " and upper(\"Implementation_Name\") like upper('%".$search_implementation_name."%')  ";

		$vendor_name_str= " and upper(\"Vendor_Name\") like upper('%".$search_vendor_name."%') ";
//		$vendor2_name_str = " and (  \"Status3\" like '%Intel_Certifiable%' ) ";


		if ($search_lab_name==null)
			$lab_name_str=" and 1=1 ";
		else
			$lab_name_str= " and upper(\"Lab_Name\") like upper('%".$search_lab_name."%') ";



		$str1_sql= " Select * from \"CMVP_ESV_Table\" where 1=1 ".$esv_cert_str.$implementation_name_str.$vendor_name_str.$lab_name_str."   ";
		$str1_sql=$str1_sql." order by to_date(\"Validation_Date\",'MM/DD/YYY') desc  ";

	 	//echo "charlie esv str1_sql=<br>".$str1_sql;
		$result = pg_query($conn, $str1_sql);

		$arr = pg_fetch_all($result);

		if($arr==null)
			$num_rows=0;
		else
			$num_rows=sizeof($arr);



		echo"<h1>ESV List ($num_rows items)</h1>";


    	echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
		echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";
		
      
    	echo "<table>"; // start a table tag in the HTML
		echo "<tr> ";
		echo "<th bgcolor=LightBlue >Row</th>  ";
		echo "<th bgcolor=LightBlue >ESV Cert_Num</th>  ";
		echo "<th bgcolor=LightBlue >Implementation</th>  ";
		echo "<th bgcolor=LightBlue >Vendor</th>  ";
		echo "<th bgcolor=LightBlue >Lab Name</th>  ";
		echo "<th bgcolor=LightBlue >Validation Date</th>  ";
		echo "<th bgcolor=LightBlue >Reuse Status</th>  ";
		echo "<th bgcolor=LightBlue >Noise Source</th>  ";
		echo "<th bgcolor=LightBlue >Noise Description</th>  ";
		echo "<th bgcolor=LightBlue >OE</th>  ";
		echo "<th bgcolor=LightBlue >Entropy per Sample Size</th>  ";
		echo "</tr>";



		$i=1;
		

    if ($num_rows>0) { 
	    foreach($arr as $row){   //Creates a loop to loop through results
	      echo "<tr><td>".$i
	      ."</td><td>  <a href=\"https://csrc.nist.gov/projects/cryptographic-module-validation-program/entropy-validations/certificate/".substr($row['ESV_Cert_Num'],1)." \"  target=\"_blank\"> "
	      . $row['ESV_Cert_Num'] . "</a> </td><td> "  
	      . $row['Implementation_Name'] . ", <a href=\"https://csrc.nist.gov/CSRC/media/projects/cryptographic-module-validation-program/documents/entropy/".$row['ESV_Cert_Num']."_PublicUse.pdf \"  target=\"_blank\">[PUD] </a> </td><td>  "
	      . $row['Vendor_Name']. "  </td><td>  "
	      . $row['Clean_Lab_Name'].  "  </td><td>  "
	      . $row['Validation_Date']."  </td><td>  "
	      . $row['Reuse_Status']."  </td><td>  "
	      . $row['Noise_Source']. "  </td><td>  "     
	      . $row['Description']." </td><td> "
	      . $row['OE']."  </td><td>  "
	      . "Ent: ".$row['Sample_Size']. "  </td></tr>";
	      $i++;  
	      
	      } //for each
	  	} //if num_rows>0

	  	if($arr==null)
	  		echo "<tr>  <font color=red> Nothing Matches Those Search Parameters<font color=black></tr>";
		echo "</table>"; //Close the table in HTML

		
 	} //ESV list 3



} //big if search_option==1	





?>
</body>
</html>