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
// Hit Counter for Admin Page

//Only add to the Admin Hit Counter once per day per visit. otherwise  there may be too many duplicate hits then. 
$hit_counter=  " INSERT INTO \"CMVP_Hit_Counter\" (\"URL\",\"Timestamp\",\"Date\", \"Application\",\"User\") 
select '".$URL_str."', (select (current_time(0) - INTERVAL '5 HOURS')),'".$today2."', 'Admin Page', '".$User."'
where not exists ( select 1 from \"CMVP_Hit_Counter\" where \"User\" = '".$User."' and \"Date\" = (select current_date) and \"Application\" like '%Admin%');";


//echo "<br>hit_str=".$hit_counter;
$result = pg_query($conn, $hit_counter);

//remove all the older entries for 'rfant' since there will be tons of them since rfant is the developer.
$hit_counter= "delete from \"CMVP_Hit_Counter\" where \"Date\" <> (select current_date) and \"User\" like 'rfant'; ";
//echo "<br>hit_str=".$hit_counter;
$result = pg_query($conn, $hit_counter);

//----------------------------------------------------------------------------
//Now, execute which ever admin option was selected

switch ($admin_option) {
    case 0:  //nothing selected. So, show options //------------------------------------------------


		echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
		echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";
		echo " <br></br>";

      
    	echo "<table>"; // start a table tag in the HTML
		echo "<tr> ";
		echo "<th bgcolor=LightBlue >Admin Options</th>  ";
		echo "</tr>";

		echo "<tr><td> 	<a href=\"".$URL_path."/cmvp_LHI_Admin.php?admin_option=1"." \" >Show Hit Counter</a>  </td></tr> "  
			."<tr><td> 	<a href=\"".$URL_path."/cmvp_LHI_Admin.php?admin_option=2"." \" >Show Admin List</a>  </td></tr> "  
			."<tr><td> 	<a href=\"".$URL_path."/cmvp_LHI_Admin.php?admin_option=3"." \" >Add Admin</a>  </td></tr> "  
			."<tr><td> 	<a href=\"".$URL_path."/cmvp_LHI_Admin.php?admin_option=4"." \" >Delete Admin</a>  </td></tr> "  
			."<tr><td> 	<a href=\"".$URL_path."/cmvp_LHI_Admin.php?admin_option=5"." \" >List Intel Certifiable Products</a>  </td></tr> "  
			."<tr><td> 	<a href=\"".$URL_path."/cmvp_LHI_Admin.php?admin_option=6"." \" >Mark a module as Intel Certifiable </a>  </td></tr> "  ;
     	echo "</table>"; //Close the table in HTML
  
    	break;

    case 1: //Show Hit Counter //----------------------------------------------------------------
    	

 		$show_hit_counter= " Select * from \"CMVP_Hit_Counter\" order by \"Row_ID\" desc";

 		 //echo "show_hit_str=".$show_hit_counter;
		$result = pg_query($conn, $show_hit_counter);

		$arr = pg_fetch_all($result);

		if($arr==null)
			$num_rows=0;
		else
			$num_rows=sizeof($arr);


		
    	echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
		echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";
		echo " <br></br>";

      
    	echo "<table>"; // start a table tag in the HTML
		echo "<tr> ";

		echo "<th bgcolor=LightBlue >Date</th>  ";
		echo "<th bgcolor=LightBlue >Time</th>  ";
		echo "<th bgcolor=LightBlue >App</th>  ";
		echo "<th bgcolor=LightBlue >User</th>  ";
		echo "</tr>";

		//$i=1;
    	if ($num_rows>0) { 
    		foreach($arr as $row){   //Creates a loop to loop through results
      				echo "<tr><td>"
                      . $row['Date']. "  </td><td>  "
                      . $row['Timestamp'].  "  </td><td>  "
                      . $row['Application']."  </td><td>  "
                      . $row['User']."  </td>  "
                      . "  </tr>";
      
      		} //for each
  		} //if

		echo "</table>"; //Close the table in HTML

    	break;

    case 2: //Show Admins //-----------------------------------------------------------------------
    	

 		$admin_list_sql= " Select * from \"CMVP_Admin_Table\" order by \"Row_ID\" desc";

 		 //echo "admin_str=".$admin_list_sql;
		$result = pg_query($conn, $admin_list_sql);

		$arr = pg_fetch_all($result);

		if($arr==null)
			$num_rows=0;
		else
			$num_rows=sizeof($arr);


    	
    	echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
		echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";
		echo " <br></br>";

      
    	echo "<table>"; // start a table tag in the HTML
		echo "<tr> ";

		echo "<th bgcolor=LightBlue >Admin Name</th>  ";
		echo "<th bgcolor=LightBlue >Date Added</th>  ";
		echo "<th bgcolor=LightBlue >Added By</th>  ";
		echo "</tr>";

		//$i=1;
    	if ($num_rows>0) { 
    		foreach($arr as $row){   //Creates a loop to loop through results
      				echo "<tr><td>"
                      . $row['Admin_Name']. "  </td><td>  "
                      . $row['Date_Added'].  "  </td><td>  "
                      . $row['Added_By']."  </td>  "
                      . "  </tr>";
      
      		} //for each
  		} //if

		echo "</table>"; //Close the table in HTML

    	break;
    case 3: //add Admins  //------------------------------------------------------------------------------
    	
    	if($add_name != null)
    	{ //if I already know the IDSID, then go ahead and add it to the Admin Table
    		echo "<br> Added: ".$add_name."<br>";

			//insert the idsid
			$IDSID_str=  " INSERT INTO \"CMVP_Admin_Table\" (\"Admin_Name\",\"Date_Added\",\"Added_By\")  
			 values('".$add_name."','". $today2."','".$User."')";

			$result = pg_query($conn, $IDSID_str);

			//echo "<br>hit_str=".$IDSID_str;

    	} //already have the IDSID
    	else
    	{	 //get the IDSID

		// define variables and set to empty values
		$nameErr =  "";
		$add_name =  "";

		if ($_SERVER["REQUEST_METHOD"] == "POST") {
		  if (empty($_POST["add_name"])) 
		    $nameErr = "IDSID is required";
		  else 
		    $add_name = ($_POST["add_name"]);
		  }
	
		?>

		<h2>Enter Admin's IDSID to ADD (exactly as shown on workers.intel.com)</h2>
		<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
		  IDSID: <input type="text" name="add_name">
		  <span class="error"> <?php echo $nameErr;?></span>
		  <br><br>
			  <input type="submit" name="submit" value="Submit">  
		</form>

		<?php
	
		} //get the IDSID
    	break;
    case 4: //delete Admins //--------------------------------------------------------------
    	//echo "<br>Delete Admins<br>";
    	if($delete_name != null)
    	{ //if I already know the IDSID to delete, then go ahead and delete it from the Admin Table
    		echo "<br> Deleted: ".$delete_name."<br>";

			//delete the idsid
			$IDSID_str=  " delete from \"CMVP_Admin_Table\" where \"Admin_Name\" like '".$delete_name."' ";
			//echo "<br>IDSTR_str=".$IDSID_str;

			$result = pg_query($conn, $IDSID_str);

			

    	} //already have the IDSID
    	else
    	{	 //get the IDSID

		// define variables and set to empty values
		$nameErr =  "";
		$$delete_name =  "";

		if ($_SERVER["REQUEST_METHOD"] == "POST") {
		  if (empty($_POST["delete_name"])) 
		    $nameErr = "IDSID is required";
		  else 
		    $delete_name = ($_POST["delete_name"]);
		  }
	
		?>

		<h2>Enter Admin's IDSID to DELETE (exactly as shown on workers.intel.com)</h2>
		<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
		  IDSID: <input type="text" name="delete_name">
		  <span class="error"> <?php echo $nameErr;?></span>
		  <br><br>
			  <input type="submit" name="submit" value="Submit">  
		</form>

		<?php
	
		} //get the IDSID


    	break;

    case 5: //List Intel Certifiable Product
    	

 		$certifiable_list_sql= " 
				select \"Cert_Num\",\"Module_Name\",\"Vendor_Name\",\"Clean_Lab_Name\",\"Module_Type\",\"SL\" from \"CMVP_Active_Table\" where \"Status3\" like '%Intel_Certifiable%'
				union
				select \"Cert_Num\",\"Module_Name\",\"Vendor_Name\",\"Clean_Lab_Name\",\"Module_Type\",\"SL\" from \"CMVP_MIP_Table\" where \"Status3\" like '%Intel_Certifiable%'
 		";

 		 //echo "admin_str=".$admin_list_sql;
		$result = pg_query($conn, $certifiable_list_sql);

		$arr = pg_fetch_all($result);

		if($arr==null)
			$num_rows=0;
		else
			$num_rows=sizeof($arr);


    	
    	echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
		echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";
		echo " <br></br>";

      
    	echo "<table>"; // start a table tag in the HTML
		echo "<tr> ";

		echo "<th bgcolor=LightBlue >Cert Num</th>  ";
		echo "<th bgcolor=LightBlue >Module Name</th>  ";
		echo "<th bgcolor=LightBlue >Vendor Name</th>  ";
		echo "<th bgcolor=LightBlue >Lab Name</th>  ";
		echo "<th bgcolor=LightBlue >Module Type</th>  ";
		echo "<th bgcolor=LightBlue >Security Level</th>  ";
		

		echo "</tr>";

		//$i=1;
    	if ($num_rows>0) { 
    		foreach($arr as $row){   //Creates a loop to loop through results
      				echo "<tr><td>"
                      . $row['Cert_Num']. "  </td><td>  "
                      . $row['Module_Name'].  "  </td><td>  "
                      . $row['Vendor_Name'].  "  </td><td>  "
                      . $row['Clean_Lab_Name'].  "  </td><td>  "
                      . $row['Module_Type'].  "  </td><td>  "
                      . $row['SL']."  </td>  "
                      . "  </tr>";
      
      		} //for each
  		} //if

		echo "</table>"; //Close the table in HTML


    	break;

	 case 6: //Add Intel Certifiable Product  -----------------------------------------------------------
    	
    	//if I know I'm adding a module to the Certifiable, then go ahead and update the Status3 field in either the CMVP_MIP_Table or CMVP_Active_Table
    	
    	if($which_CMVP_list!= "")
    	{ 	//if I know I'm adding a module to the Certifiable, then go ahead and update the Status3 field in either the CMVP_MIP_Table or CMVP_Active_Table
    	
    		switch ($which_CMVP_list)
    		{
    			case "active":

		    		?>

					<h2>Enter the Cert Number</h2>
					<p><span class="error">* required field</span></p>
					<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
					 Cert Number: <input type="text" name="add_cert">
					  <span class="error">* <?php echo $nameErr;?></span>
					  <br><br>
					 <!-- Module Name: <input type="text" name="add_module_name">
					  <span class="error">* <?php echo $emailErr;?></span>
					  <br><br>
					 -->
					  <input type="submit" name="submit" value="Submit">  
					</form>

					<?php


    				break;
    			case "mip":
    				?>

					<h2>Enter the Following Information</h2>
					<p><span class="error">* required field</span></p>
					<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
					  Module Name: <input type="text" name="add_module_name">
					  <span class="error">* <?php echo $nameErr;?></span>
					  <br><br>
					  Vendor Name: <input type="text" name="add_vendor_name">
					  <span class="error">* <?php echo $nameErr;?></span>
					  <br><br>
					  Lab Name: <input type="text" name="add_lab_name">
					  <span class="error">* <?php echo $nameErr;?></span>
					  <br><br>
					  Module Type: <input type="text" name="add_module_type">
					  <span class="error">* <?php echo $nameErr;?></span>
					  <br><br>
					  Security Level: <input type="text" name="add_security_level">
					  <span class="error">* <?php echo $nameErr;?></span>
					  <br><br>
			

					  <input type="submit" name="submit" value="Submit">  
					</form>

					<?php

    				break;
    			default:
    				echo "<br> Error 399: Unknown cmvp_list=$which_CMVP_list <br>";

    		} //which cmvp list switch

    		} //if I know I'm adding a module to the Certifiable, then go ahead and update the Status3 field in either the CMVP_MIP_Table or CMVP_Active_Table

    	else
	    	{	 //get the information for the module which is to marked as certifiable



			// define variables and set to empty values
			$nameErr = $emailErr = $genderErr = $websiteErr = "";
			$name = $email = $gender = $comment = $website = "";

			$add_cert="";
			$add_module_name="";
			$add_vendor_name="";
			$add_lab_name="";
			$add_sl="";
			$add_module_type="";
			$which_CMVP_list=""

			?>

			<h2>Mark a Module as Intel Certifiable</h2>
			<p><span class="error">* required field</span></p>
			<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  
			 Which list is the Module in:
			  <input type="radio" name="which_CMVP_list" value="active">Active
			  <input type="radio" name="which_CMVP_list" value="mip">MIP
			  <span class="error">* <?php echo $genderErr;?></span>
			  <br><br>

			  <input type="submit" name="submit" value="Submit">  
			</form>

			<?php
			
			}  //get the information for the module which is to marked as certifiable

	    	break;

    case 7:  // use the Cert number to find module 
	
	     // I know the cert number so mark it certifiable

		echo "<br>Marking Cert# $add_cert as an Intel Certifiable Product. <br>";
		
		//update the CMVP_Active Table
		$cert_str=  " Update \"CMVP_Active_Table\"  set \"Status3\" = '.Intel_Certifiable.' where \"Cert_Num\" = ".$add_cert." ";

		$result = pg_query($conn, $cert_str);
		//echo "<br>cert_str=".$cert_str;

    	break;

    case 8: //use the module name to find module
		//on the MIP list, so mark it certifiable

    		$sql_str="select \"Module_Name\",\"Vendor_Name\",\"Status3\",\"Clean_Lab_Name\",\"Clean_Module_Type\",\"SL\" from \"CMVP_MIP_Table\" where \"Module_Name\" like '%".$add_module_name."%' and \"Vendor_Name\" like '%".$add_vendor_name."%' order by \"Row_ID\" desc ";
    		$result=pg_query($conn,$sql_str);

		//	echo "<br>sql_str=$sql_str<br>";
				
			$arr = pg_fetch_all($result);

			if($arr==null)
				$num_rows=0;
			else
				$num_rows=sizeof($arr);


			
	    	echo "<style> table {border-collapse: collapse; } td, th { padding: 10px; border: 2px solid #1c87c9;  } </style>";
			echo "<style> table,   {border: 1px solid black;background-color:#f6f6f6;}</style>";
			echo " <br></br>";
	      
	    	echo "<table>"; // start a table tag in the HTML
			echo "<tr> ";

			echo "<th bgcolor=LightBlue >Module Name</th>  ";
			echo "<th bgcolor=LightBlue >VendorName</th>  ";
			echo "<th bgcolor=LightBlue >Lab Name</th>  ";
			echo "<th bgcolor=LightBlue >Module Type</th>  ";
			echo "<th bgcolor=LightBlue >Status</th>  ";
			echo "<th bgcolor=LightBlue >Security Level</th>  ";
			echo "</tr>";

			//$i=1;
	    	if ($num_rows>0) { 
	    		foreach($arr as $row){   //Creates a loop to loop through results
	      				echo "<tr><td>"
	                      . $row['Module_Name']. "  </td><td>  "
	                      . $row['Vendor_Name'].  "  </td><td>  "
	                      . $row['Clean_Lab_Name']."  </td><td>  "
	                      . $row['Clean_Module_Type']."  </td><td>  "
	                      . $row['Status3']."  </td><td>  "
	                      . $row['SL']."  </td>  "
	                      . "  </tr>";
	      
	      		} //for each
	  		} //if //num rows >0

			echo "</table>"; //Close the table in HTML


			if($num_rows==0)
				echo "<br>can't find this module based on sql_str=<br>$sql_str<br>";
			elseif($num_rows>1) 
				echo "<br> Too many modules match that description. Unable to mark it as Certifiable. <br>";
			else
			{	
	    		echo "<br> Is this the correct module?<br>";

			//<a href="delete.php?id=22" onclick="return confirm('Are you sure?')">Link</a>


	
			}


    		
    	break;
    default:
    	echo "<br>ERROR: unknown admin_option value=". $admin_option."<br>";
	} //select case




?>
</body>
</html>