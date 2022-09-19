<?php
//require_once("phpchartdir.php");
//this php file defines whether the URL is for production or development for all the PHP files.
include './cmvp_define_LHI_dev_vs_prod.php';

$admin_option=isset($_REQUEST["admin_option"]) ? $_REQUEST["admin_option"] : 0;

//$admin_option=0;


//figure out if it's the Production or Demo Version
switch ($PROD) {
    case 2:  //postgresql database on Ubuntu VM machine 
     	
     	$encryptedPW="xtw2D3obQa8=";
     	echo "pgsql=ubutun VM";
  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
		$connStr = "host=localhost  dbname=postgres user=postgres password=".$decryptedPW." connect_timeout=5 options='--application_name=$appName'";
		//$User=get_current_user();
		break;
    case 1: //postgresql database on intel interanet production
		$encryptedPW="WDu8gYvvVn6Pxw==";
		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
  		$connStr = "host=postgres5320-lb-fm-in.dbaas.intel.com  port=5432 dbname=lhi_prod2 user=lhi_prod2_so password=".$decryptedPW."  connect_timeout=5 options='--application_name=$appName'";
      	//$User = isset($_COOKIE['IDSID']) ? $_COOKIE['IDSID'] : '<i>no value</i>';
      break;
    
    default:
    	echo "ERROR 26: unknown PROD value";

	}




//----------------------------------------------------------------------------

switch ($admin_option) {
    case 0:  //nothing selected. So, show options 


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
			."<tr><td> 	<a href=\"".$URL_path."/cmvp_LHI_Admin.php?admin_option=6"." \" >Add Intel Certifiable Product</a>  </td></tr> "  
			
			;

     	echo "</table>"; //Close the table in HTML
  
    	break;

    case 1: //Show Hit Counter
    	$conn = pg_connect($connStr);
		$stat = pg_connection_status($conn);
		
		if ($stat === PGSQL_CONNECTION_OK) 
		{
      		//echo 'PGSQL Connection status ok';
 		} 
 		 else {echo '<br>ERROR: PGSQL Connection status bad<br>';  }


 		$hit_counter= " Select * from \"CMVP_Hit_Counter\" order by \"Row_ID\" desc";

 		 //echo "hit_str=".$hit_counter;
		$result = pg_query($conn, $hit_counter);

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

		$i=1;
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

    case 2: //Show Admins
    	//echo "<br>Show Admins<br>";
    	$conn = pg_connect($connStr);
		$stat = pg_connection_status($conn);
		
		if ($stat === PGSQL_CONNECTION_OK) 
		{
      		//echo 'PGSQL Connection status ok';
 		} 
 		 else {echo '<br>ERROR 130: PGSQL Connection status bad<br>';  }


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

		$i=1;
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



    	break;
    case 3: //add Admins
		echo "<br>Add Admins<br>";
    	break;
    case 4: //delete Admins
    	echo "<br>Delete Admins<br>";
    	break;

    case 5: //List Intel Certifiable Product
    	echo "<br>List All Intel Certifiable Product<br>";








    	break;

	 case 6: //Add Intel Certifiable Product
    	echo "<br>Add Intel Certifiable Product<br>";

    	break;

    default:
    	echo "<br>ERROR: unknown admin_option value=". $admin_option."<br>";

	}


?>