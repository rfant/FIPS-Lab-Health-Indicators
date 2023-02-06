
<?php
//this php file defines whether the URL is for production or development for all the PHP files.
include './cmvp_define_LHI_dev_vs_prod.php';
//==========================================================

//echo "alpha23"."<br>";

//function exception_error_handler($errno, $errstr, $errfile, $errline ) {
//   throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
//}
//set_error_handler("exception_error_handler");

//============================================================

//<td style="height:100px;width:100px"  >
//require_once("../lib/phpchartdir.php");
require_once("phpchartdir.php");

define        ("red",0x00FF0000);
define      ("green",0x0000FF00);
define       ("blue",0x000000FF);
define ("light_blue",0x00eeeeff);
define  ("blue_gray",0x0098AFC7);
define ("light_blue2",0x0099ccff);
define  ("deep_blue",0x000000cc);
define      ("white",0x00FFFFFF);
define      ("black",0x00000000);
define      ("gray1",0x00dcdcdc);
define     ("yellow",0x00FFFF00);
define ("yellow_green",0x0052D017);
define ("green_yellow",0x00B1FB17);
define ("frog_green",0x57E964);
define ("gray_cloud",0x00B6B6B4);
define ("battleship_gray",0x00848482);
define ("pumpkin_orange",0x00F87217);
define ("platinum",0x00E5E4E2);
define ("light_slate_gray",0x006D7B8D);
define ("marble_blue",0x00566D7E);

define ("dark_slate_blue",0x002B3856);
define ("cobalt_blue",0x000020C2);


//get the start and end date. 
 $self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '#';
 //echo "<br>self=$self<br>";

 $now = date("Y-m-d");
 
 $today1 = isset($_POST['today1']) ? $_POST['today1'] : '1995-01-01' ; //Earliest CMVP validation date
 $today2 = isset($_POST['today2']) ? $_POST['today2'] : (new DateTime)->format('Y-m-d');
  
 $startDate = isset($_REQUEST["startDate"]) ? date('Y-m-d',strtotime($_REQUEST["startDate"])) : date('Y-m-d', strtotime($today1));
 $endDate = isset($_REQUEST["endDate"]) ? date('Y-m-d',strtotime($_REQUEST["endDate"])) : date('Y-m-d', strtotime($today2));


 $in_TopButtons=isset($_REQUEST["in_TopButtons"]) ? $_REQUEST["in_TopButtons"] : 4;
 $in_TopButtons=abs($in_TopButtons);  //not sure why, but sometimes in_TopButtons comes in as a negative number. Weird.

 //echo "startDate1=".$startDate." ";
 //echo "endDate1=$endDate<br>";

 //if($in_TopButtons==4)
 //	$startDate='1995-01-01';

 $zoom=isset($_REQUEST["zoom"]) ? $_REQUEST["zoom"] : 1;

 //1 means selected. 0 means not selected
 $in_IntelOnlyButton=isset($_REQUEST["in_IntelOnlyButton"]) ? $_REQUEST["in_IntelOnlyButton"] : 0;
 $in_IntelOnlyButton2=isset($_REQUEST["in_IntelOnlyButton2"]) ? $_REQUEST["in_IntelOnlyButton2"] : 0;
 $in_ModuleTypeButton=isset($_REQUEST["in_ModuleTypeButton"]) ? $_REQUEST["in_ModuleTypeButton"] : 0;
 $in_SecurityLevelButton=isset($_REQUEST["in_SecurityLevelButton"]) ? $_REQUEST["in_SecurityLevelButton"] : 0;
 
//echo "IntelOnly=".$in_IntelOnlyButton." MT=".$in_ModuleTypeButton." SL=".$in_SecurityLevelButton."<BR>";



 
//echo "startDate2=".$startDate." ";
//echo "endDate2=$endDate<br>";



$data0 = array();   //Revoked
$data1 = array();   //Historic
$data2 = array();   //Became Active
$data3 = array();   //Already Active
$data4 = array();   //revaliated
$labels= array();	// Lab Names

//echo "<BR>bravo<br>";

//===============================================
#connect to postgreSQL database and get my chart data

$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];


//---------------------------------------------------
//get the user from the Cloud Foundry PHP variable
ob_start();

// send phpinfo content
phpinfo();

// get phpinfo content
$User = ob_get_contents();

// flush the output buffer
ob_end_clean();

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
    	echo "ERROR: unknown PROD value";

	}
	

//echo "<br>delta<br>";
//echo "<br> ConnStr: $connStr<BR>";
//echo "<br>User=$User<br>";

//=====================================================


$conn = pg_connect($connStr);



$stat = pg_connection_status($conn);
if ($stat === PGSQL_CONNECTION_OK) {
      //echo '<br>PGSQL Connection status ok<br>';
  } else {
      echo '<br>PGSQL Connection status bad<br>';
  }    
//echo "<br>echo1c<br>";


//$hit_counter= " INSERT INTO \"CMVP_Hit_Counter\" ( \"URL\", \"Timestamp\",\"Date\", \"Application\",\"User\") values('".$URL_str."',(select (current_time(0) - INTERVAL '5 HOURS')),'". $today2."','cmvp_active_by_status_pareto.php','".$User."')";


//Don't add the developer "rfant' since there will be too many hits then.
$hit_counter=  " INSERT INTO \"CMVP_Hit_Counter\" (\"URL\",\"Timestamp\",\"Date\", \"Application\",\"User\") 
select '".$URL_str."', (select (current_time(0) - INTERVAL '5 HOURS')),'".$today2."', 'cmvp_active_by_status_pareto.php', '".$User."'
where not exists (     select 1 from \"CMVP_Hit_Counter\" where \"User\" = 'rfant' and \"Date\" = (select current_date) and \"Application\"='cmvp_active_by_status_pareto.php');";

//echo "hit_str=".$hit_counter;
$result = pg_query($conn, $hit_counter);

//print "in_IntelOnlyButton=".$in_IntelOnlyButton."<br>";

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


//echo "<br>echo2<br>";
// Security Level
switch ($in_SecurityLevelButton) 
{
  case 0:
	$where_security= "and 1=1 ";   //this is for ALL security types. 
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
//echo "<br>echo3<br>";

//Module TYpe
switch ($in_ModuleTypeButton) 
{
  case 0:
	$where_MT= "and 1=1 ";   //this is for ALL module types
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



//echo "<br>echo4<br>";

	$show_detail_value=1;
	$sql_Str="
	;WITH sums AS ( 
	SELECT \"Clean_Lab_Name\" as lab, 
		 count(revoked + InclusiveDates ) as revoked, 
		 count(historic + InclusiveDates) as historic, 
		 count(active + InclusiveDatesNew) as became_active, 
		 count(active + ExclusiveDates ) as already_active , 
		 count(active + InclusiveDatesRevalidate) as revalidate 
	FROM ( SELECT \"Clean_Lab_Name\", 
		  CASE WHEN (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDates, 
		  CASE WHEN length(\"Validation_Date\")=10 AND (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDatesNew, 
		  CASE WHEN length(\"Validation_Date\")>10 AND (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDatesRevalidate, 
		  case WHEN (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) <'".$startDate."' then 1 end ExclusiveDates, 
		  CASE WHEN \"Status\" like 'Revoked' THEN 1 END revoked, 
		  CASE WHEN \"Status\" like 'Historical' THEN 1 END historic, 
		  CASE WHEN \"Status\" like 'Active' THEN 1 END active 
	FROM \"CMVP_Active_Table\" where 1=1  ".$where_vendor.$where_vendor2.$where_security.$where_MT.") \"CMVP_Active_Table\" group by \"Clean_Lab_Name\" order by lab 
	 ) 
	 SELECT lab, 
	 (case when revoked is null then 0 else revoked end) , 
	 (case when historic is null then 0 else historic end) , 
	 (case when became_active is null then 0 else became_active end), 
	 (case when already_active is null  then 0 else already_active end),
	 (case when revalidate is null then 0 else revalidate end),
	 (case when revoked is null then 0 else revoked end) 
	  + (case when historic is null then 0 else historic end) 
	  + (case when became_active is null then 0 else became_active end) 
	  + (case when already_active is null then 0 else already_active end)
	  + (case when revalidate is null then 0 else revalidate end)
	  AS grandtotal FROM sums 
	  where 
	  (case when revoked is null then 0 else revoked end) 
	  + (case when historic is null then 0 else historic end) 
	  + (case when became_active is null then 0 else became_active end) 
	  + (case when already_active is null then 0 else already_active end)
	  + (case when revalidate is null then 0 else revalidate end) >=0 
		 order by grandtotal 
	 
	";
//} //not 1995 01 01 
//echo " Bravo SQL= " . $sql_Str ;
//echo "<br>echo5<br>";



$data = array();
$labels=array();



$result = pg_query($conn,$sql_Str);
if (!$result) {
  echo "<br>ERROR 259: An error occurred fetching Posgresql:  ";
  echo pg_last_error($conn)."<br>";
  exit;
}
else
	//echo "<br>DB fetch successful<br>";

$arr = pg_fetch_all($result);
//print_r($arr);

if($arr==null)
{
	$num_mod=0;
	$no_data_found_label=1;
}
else
{
	$num_mod=sizeof($arr);
	$no_data_found_label=0;

}
if($num_mod>0)
{
	foreach($arr as $row){
		$labels[]=$row['lab'];
	}

	foreach($arr as $row){
		$data[]=$row['grandtotal'];
	}

	foreach($arr as $row){
		$data0[]=$row['revoked'];
	}
	foreach($arr as $row){
		$data1[]=$row['historic'];
	}
	foreach($arr as $row){
		$data2[]=$row['became_active'];
	}
	foreach($arr as $row){
		$data3[]=$row['already_active'];
	}
	foreach($arr as $row){
		$data4[]=$row['revalidate'];
	}


} //num_mod > 0



//===================  configure and draw the chart ========================




# Create a XYChart object of size 1600 x 1000 pixels. Set background color to brushed silver, with a 2
# pixel 3D border. Use rounded corners of 20 pixels radius.


//$zoom=1;
if($in_IntelOnlyButton==0 && $in_IntelOnlyButton2==0)
{
	$width=$zoom*900;
	$height=$zoom*1000;
}
else
{
//	$width=$zoom*900;
//	$height=$zoom*700; //900;
	$height=75 * $num_mod * $zoom + 25;
	if($height<500)   $height=500;
	$width=$zoom*900;


}






//print "width=".$width." height=".$height."<br>";

//$c = new XYChart($width,$height, brushedSilverColor(), Transparent, 2);

# Create an XYChart object of size 600 x 360 pixels, with a light blue (EEEEFF) background, black
# border, 1 pxiel 3D border effect and rounded corners
$c = new XYChart($width, $height, light_blue, black, 1);
$c->setRoundedFrame();

//-----------------------------------------------
//Draw some buttons

if($no_data_found_label==1)
{// add a no data found label
  $no_data_label=$c->addText(200,250,"No Data Found That Matches The Selected Filters","arialbd.ttf",12*$zoom);
  $no_data_label->SetFontColor(red);
  $no_data_label->setSize(300,300);
}


// define the where the buttons start based on zoom level

$buttonY=100;
$buttonX=$width - 100;  
//echo "zoom=".$zoom."  buttonY=".$buttonY."  buttonX=".$buttonX."<br>";

$ShowList = $c->addText($buttonX+10,  $buttonY+300, "Show All","arialbd.ttf", 8,black); //draw button
$ShowList->setSize(60, 25);
$ShowList->setBackground(light_blue,-1,2);
$ShowList->setAlignment (5);
$coor_ShowList = $ShowList->getImageCoor();


//------------------ "Active Parser Error" warning flash ----------------------------------------------
//Draw a flashing warning flag if any Active parser errors have happend 
$sql_warning_str = "select * from \"Active_Error_Table\" where \"Error_Flag\" = 'TRUE' "; 
$result = pg_query($conn,$sql_warning_str);
$arr = pg_fetch_all($result);

if ($arr!=null)
{
	$active_warning_flash = $c->addText($buttonX-300, $buttonY-65, "ActParseErr!","arialbd.ttf",8); //draw button
	$active_warning_flash->SetFontColor(black);
	$active_warning_flash->setSize(90, 25);
	//$active_warning_flash->setBackground(red); //,-1,-2);
	$active_warning_flash->setBackground(yellow,-1,2);
	//$button3->setBackground(gray1,-1,2);
	$active_warning_flash->setAlignment (5);
	$coor_active_warning_flash = $active_warning_flash->getImageCoor();
}

//------------------ "MIP Parser Error" warning flash ----------------------------------------------
//Draw a flashing warning flag if any MIP parser errors have happend 
$sql_warning_str = "select * from \"MIP_Error_Table\" where \"Error_Flag\" = 'TRUE' "; 
$result = pg_query($conn,$sql_warning_str);
$arr = pg_fetch_all($result);


if ($arr!=null)
{
	$mip_warning_flash = $c->addText($buttonX-200, $buttonY-65, "MIPParseErr!","arialbd.ttf",8); //draw button
	$mip_warning_flash->SetFontColor(black);
	$mip_warning_flash->setSize(90, 25);
	//$mip_warning_flash->setBackground(red); //,-1,-2);
	$mip_warning_flash->setBackground(yellow,-1,2);
	//$button3->setBackground(gray1,-1,2);
	$mip_warning_flash->setAlignment (5);
	$coor_mip_warning_flash = $mip_warning_flash->getImageCoor();
}



// ----------------- Admin Button -------------------------------------------------------------

//Draw a button that is only visible to "Admins" 
//1) see if this current user is an admin
$sql_admin_str = "select * from \"CMVP_Admin_Table\" where \"Admin_Name\" = '".$User."' order by \"Row_ID\" desc limit 1"; 
$result = pg_query($conn,$sql_admin_str);
$arr = pg_fetch_all($result);
if ($arr!=null)
{
	//echo "<br> You are ADMIN<br>";
	$admin_button = $c->addText($buttonX-70, $buttonY-55, "ADMIN","arialbd.ttf",8); //draw button
	$admin_button->SetFontColor(white);
	$admin_button->setSize(50, 25);
	//$admin_button->setBackground(red); //,-1,-2);
	$admin_button->setBackground(red,-1,2);
	//$button3->setBackground(gray1,-1,2);
	$admin_button->setAlignment (5);
	$coor_admin_button = $admin_button->getImageCoor();

}
//------------- zoom buttons ---------------------------------------------------------

//draw a box around the zoom buttons with a label
$zoom_box = $c->addText($buttonX, $buttonY-65, "","arialbd.ttf", 10); //draw box outline around filter button
$zoom_box->setSize(95, 45);
$zoom_box->setBackground(light_blue,black,0);
$zoom_box->setAlignment (5);
   
// add a zoom label
$zoom_label=$c->addText($buttonX+33,$buttonY-37,"Zoom","arialbd.tff",8);
$zoom_label->SetFontColor(black);
$zoom_label->setSize(150,30);

if($zoom>1){
$zoom_str="Zoom=". $zoom;
$zoom_rp = $c->addText($buttonX+5,$buttonY-100, $zoom_str ,"arialbd.ttf", 10,black); //draw button
$zoom_rp->setSize(20, 20);
}

$zoomIn = $c->addText($buttonX+10, $buttonY-60, "+","arialbd.ttf", 10); //draw button
$zoomIn->setSize(20, 20);
$zoomIn->setBackground(gray1,-1,2);
$zoomIn->setAlignment (5);
$coor_zoomIn = $zoomIn->getImageCoor();


$zoomOut = $c->addText($buttonX+40, $buttonY-60, "-","arialbd.ttf", 12); //draw button
$zoomOut->setBackground(gray1,-1,2);
$zoomOut->setSize(20, 20);
$zoomOut->setAlignment (5);
if($zoom <=1)
	$coor_zoomOut =0;
 else
	$coor_zoomOut = $zoomOut->getImageCoor();	//only make clickable button if zoom in already used.


$zoomClear = $c->addText($buttonX+70, $buttonY-60, "!","arialbd.ttf", 12); //draw button
$zoomClear->setSize(20, 20);
$zoomClear->setBackground(gray1,-1,2);
$zoomClear->setAlignment (5);
$coor_zoomClear = $zoomClear->getImageCoor();


//-----  Chart Type buttons----------------------------------------------------------------


//gray1 means not selected
//battleship_gray means selected

$button1 = $c->addText($buttonX-100, $buttonY, "Validated","arialbd.ttf", 10); //draw button
$button1->setSize(80, 30);
$button1->setBackground(battleship_gray,-1,-2);
$button1->setAlignment (5);
$coor_button1 = $button1->getImageCoor();



$button3 = $c->addText($buttonX, $buttonY, "Lab Trend","arialbd.ttf", 10); //draw button
$button3->setSize(80, 30);
$button3->setBackground(gray1,-1,2);
$button3->setAlignment (5);
$coor_button3 = $button3->getImageCoor();



$button4 = $c->addText($buttonX-100, $buttonY+50, "MIP Data","arialbd.ttf", 10); //draw button
$button4->setSize(80, 30);
$button4->setBackground(gray1,-1,2);
$button4->setAlignment (5);
$coor_button4 = $button4->getImageCoor();

$button5 = $c->addText($buttonX, $buttonY+50, "Forecast","arialbd.ttf", 10); //draw button
$button5->setSize(80, 30);
$button5->setBackground(gray1,-1,2);
$button5->setAlignment (5);
$coor_button5 = $button5->getImageCoor();


//---------- Filter Buttons for Charts --------------------------------------------------------
$filter_box = $c->addText($buttonX-100, $buttonY+105, "","arialbd.ttf", 10); //draw box outline around filter button
$filter_box->setSize(187, 250);
$filter_box->setBackground(light_blue,red,0);
$filter_box->setAlignment (5);
   
// do the labels first
$filter_label=$c->addText($buttonX-45,$buttonY+110,"Intel Filters:","arialbd.tff",10);
$filter_label->SetFontColor(red);
$filter_label->setSize(150,30);
   
$security_label=$c->addText($buttonX-50,$buttonY+175,"Security Level:","arialbd.tff",10);
$security_label->SetFontColor(red);
$security_label->setSize(150,30);


$security_label=$c->addText($buttonX-50,$buttonY+250,"Module Type:","arialbd.tff",10);
$security_label->SetFontColor(red);
$security_label->setSize(150,30);

//now do the clickable buttons
//print "in_IntelOnlyButton=".$in_IntelOnlyButton."<br>";
$IntelOnlyButton = $c->addText($buttonX-80,  $buttonY+130, "Certified","arialbd.ttf", 8,black); //draw button
$IntelOnlyButton->setSize(60, 25);
if ($in_IntelOnlyButton==1)
	$IntelOnlyButton->setBackground(battleship_gray,-1,-2);
else
 	$IntelOnlyButton->setBackground(gray1,-1,2);
$IntelOnlyButton->setAlignment (5);
$coor_IntelOnlyButton = $IntelOnlyButton->getImageCoor();

$IntelOnlyButton2 = $c->addText($buttonX,  $buttonY+130, "Certifiable","arialbd.ttf", 8,black); //draw button
$IntelOnlyButton2->setSize(60, 25);
if ($in_IntelOnlyButton2==1)
	$IntelOnlyButton2->setBackground(battleship_gray,-1,-2);
else
 	$IntelOnlyButton2->setBackground(gray1,-1,2);
$IntelOnlyButton2->setAlignment (5);
$coor_IntelOnlyButton2 = $IntelOnlyButton2->getImageCoor();

$SL_ALL = $c->addText($buttonX-90,  $buttonY+200, "All","arial.ttf", 8,black); //draw button
$SL_ALL->setSize(30,30);
if ($in_SecurityLevelButton==0)
	$SL_ALL->setBackground(battleship_gray,-1,-2);
else
 	$SL_ALL->setBackground(gray1,-1,2);
$SL_ALL->setAlignment (5);
$coor_SL_ALL = $SL_ALL->getImageCoor();

$SL_1 = $c->addText($buttonX-55,  $buttonY+200, "1","arial.ttf", 8,black); //draw button
$SL_1->setSize(30,30);
if ($in_SecurityLevelButton==1)
	$SL_1->setBackground(battleship_gray,-1,-2);
else
 	$SL_1->setBackground(gray1,-1,2);
$SL_1->setAlignment (5);
$coor_SL_1 = $SL_1->getImageCoor();

$SL_2 = $c->addText($buttonX-20,  $buttonY+200, "2","arial.ttf", 8,black); //draw button
$SL_2->setSize(30,30);
if ($in_SecurityLevelButton==2)
	$SL_2->setBackground(battleship_gray,-1,-2);
else
 	$SL_2->setBackground(gray1,-1,2);
$SL_2->setAlignment (5);
$coor_SL_2 = $SL_2->getImageCoor();

$SL_3 = $c->addText($buttonX+15,  $buttonY+200, "3","arial.ttf", 8,black); //draw button
$SL_3->setSize(30,30);
if ($in_SecurityLevelButton==3)
	$SL_3->setBackground(battleship_gray,-1,-2);
else
 	$SL_3->setBackground(gray1,-1,2);
$SL_3->setAlignment (5);
$coor_SL_3 = $SL_3->getImageCoor();


$SL_4 = $c->addText($buttonX+50,  $buttonY+200, "4","arial.ttf", 8,black); //draw button
$SL_4->setSize(30,30);
if ($in_SecurityLevelButton==4)
	$SL_4->setBackground(battleship_gray,-1,-2);
else
 	$SL_4->setBackground(gray1,-1,2);
$SL_4->setAlignment (5);
$coor_SL_4 = $SL_4->getImageCoor();


$MT_ALL = $c->addText($buttonX-90,  $buttonY+275, "All","arial.ttf", 8,black); //draw button
$MT_ALL->setSize(30,30);
if ($in_ModuleTypeButton==0)
	$MT_ALL->setBackground(battleship_gray,-1,-2);
else
 	$MT_ALL->setBackground(gray1,-1,2);
$MT_ALL->setAlignment (5);
$coor_MT_ALL = $MT_ALL->getImageCoor();

$MT_1 = $c->addText($buttonX-55,  $buttonY+275, "HW","arial.ttf", 8,black); //draw button
$MT_1->setSize(30,30);
if ($in_ModuleTypeButton==1)
	$MT_1->setBackground(battleship_gray,-1,-2);
else
 	$MT_1->setBackground(gray1,-1,2);
$MT_1->setAlignment (5);
$coor_MT_1 = $MT_1->getImageCoor();

$MT_2 = $c->addText($buttonX-20,  $buttonY+275, "SW","arial.ttf", 8,black); //draw button
$MT_2->setSize(30,30);
if ($in_ModuleTypeButton==2)
	$MT_2->setBackground(battleship_gray,-1,-2);
else
 	$MT_2->setBackground(gray1,-1,2);
$MT_2->setAlignment (5);
$coor_MT_2 = $MT_2->getImageCoor();

$MT_3 = $c->addText($buttonX+15,  $buttonY+275, "Hy","arial.ttf", 8,black); //draw button
$MT_3->setSize(30,30);
if ($in_ModuleTypeButton==3)
	$MT_3->setBackground(battleship_gray,-1,-2);
else
 	$MT_3->setBackground(gray1,-1,2);
$MT_3->setAlignment (5);
$coor_MT_3 = $MT_3->getImageCoor();


$MT_4 = $c->addText($buttonX+50,  $buttonY+275, "FW","arial.ttf", 8,black); //draw button
$MT_4->setSize(30,30);
if ($in_ModuleTypeButton==4)
	$MT_4->setBackground(battleship_gray,-1,-2);
else
 	$MT_4->setBackground(gray1,-1,2);
$MT_4->setAlignment (5);
$coor_MT_4 = $MT_4->getImageCoor();



//==== Get the Last_Updated date =====================================================
$sql_Str_Last_Updated="select * from \"CMVP_Active_Table\"  order by \"Row_ID\" desc limit 1 ;";
//echo "juliet sql=<br> ".$sql_Str_Last_Updated."<br>";
$result = pg_query($conn,$sql_Str_Last_Updated);
$arr = pg_fetch_all($result);
$row=sizeof($arr);
foreach($arr as $row){
    $Last_Updated=$row['Last_Updated'];
  }
//======================================================================================




//----------------------------------------------------------------------------------------------
# Add a title to the chart using 15pt Times Bold Italic font. The text is white (ffffff) on a blue
# (0000cc) background, with glass effect.
$title = $c->addTitle("CMVP Validated Modules (Updated:".$Last_Updated.")", "timesbi.ttf", 15, 0xffffff);
$title->setBackground(0x0000cc, 0x000000, glassEffect(ReducedGlare));



# Set the plotarea corner (80, 100) and of size wxh  pixels. Use transparent border and black grid
# lines. Use rounded frame with radius of 20 pixels.
//          start x, start y, width, height,   background color, alt background color,edge color, horiz grid color, vert grid color

$c->setPlotArea(80, 100, $width-300, $height-160, white, -1, Transparent, 0x000000);
$c->setRoundedFrame(0xffffff, 20);

//------------------
# Swap the x and y axes to create a horizontal bar chart
$c->swapXY();

//------------------
# Add a legend box at (480, 20) using vertical layout and 12pt Arial font. Set background and border
# to transparent and key icon border to the same as the fill color.
//                   x,  y,  vertical?, font       , font size
$b = $c->addLegend(10, 50,      false, "arialbd.ttf", 12);
$b->setBackground(Transparent, Transparent);
$b->setKeyBorder(SameAsMainColor);

//--------------------
# Add a stacked bar layer
$layer = $c->addBarLayer2(Stack);



//-----------------------
# Add the four data sets to the bar layer
$layer->addDataSet($data0, black, "Revoked");
$layer->addDataSet($data1, red, "Historic");
$layer->addDataSet($data2, green, "Became Active");
$layer->addDataSet($data3, yellow, "Already Active");
$layer->addDataSet($data4, pumpkin_orange, "Reval Active");

if ($num_mod < 10)
	$layer->setBarWidth(50);

# Set the bar border to transparent with softlighting
$layer->setBorderColor(Transparent, softLighting(Top));

# Enable labelling for the entire bar and use 12pt Arial font
$layer->setAggregateLabelStyle("arialbd.ttf", 12);
# Set the aggregate label format
$layer->setAggregateLabelFormat("{value} Certs");

# Enable labelling for the bar segments and use 12pt Arial font with center alignment
$textBoxObj = $layer->setDataLabelStyle("arialbd.ttf", 10);
$textBoxObj->setAlignment(Center);

# Set x axis labels using the given labels
$c->xAxis->setLabels($labels);

# Draw the ticks between label positions (instead of at label positions)
$c->xAxis->setTickOffset(0.5);

# When auto-scaling, use tick spacing of 40 pixels as a guideline
$c->yAxis->setTickDensity(40);

# Add a title to the y axis with 12pt Times Bold Italic font
$c->yAxis->setTitle("Number of Certificates", "timesbi.ttf", 12);

# Set axis label style to 8pt Arial Bold
$c->xAxis->setLabelStyle("arialbd.ttf", 8);
$c->yAxis->setLabelStyle("arialbd.ttf", 8);

# Set axis line width to 2 pixels
$c->xAxis->setWidth(2);
$c->yAxis->setWidth(2);
//$c->yAxis->setLogScale();

# Create the image and save it in a temporary location
$chart1URL = $c->makeSession("chart1");

# Create an image map for the chart

$imageMap = $c->getHTMLImageMap("cmvp_show_details_active_by_status_pareto.php", "{default}&show_detail_value=".$show_detail_value."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&startDate=".$startDate."&endDate=".$endDate, "title='{xLabel}: {value|0} certificates'");


?>


<!--//=======================================================-->
<hmtl>
<body style="margin:5px 0px 0px 5px">
<!--<div style="font-size:18pt; font-family:verdana; font-weight:bold">
    CMVP Active By Status Indicator (99.99% accurate. Data pulled directly from CMVP. But GIGO).
</div>-->

<title>Intel FIPS LHI</title>

<table> <!-- date buttons -->

	<form action="<?= $self; ?>" method="POST"> 
   	<tr>    
   	<!--	<label for="start2">Start date2:</label>
   		<input type="date" id="star2t" name="trip-start"      value="<?=$startDate;?>" >-->

   					<td align="right"> Start Date <input type="date" name="startDate" value="<?= $startDate;?>">
    				<td rowspan="2"> <td colspan="3"><img src = "./INTEL_FIPS_LOGO_v3.png"     height = "70" width = "262" /></td></td>	
 		</tr>
		<tr>	
						<td align="right"> End Date   <input type="date" name="endDate" value="<?= $endDate;?>"> </td> 
						<td>&nbsp</td>
		</tr>
   	<tr> 	
   					<td align="center">  <button  type='submit' >    Refresh  </button>  	</td>  
  </form>

  <script>
			n =  new Date();
			y = n.getFullYear();
			m = n.getMonth() +1 ;   //have to add one to get current month since array is zero-index based.
			d = n.getDate();
	</script>
		
	<td>
				<script>
					//24 months earlier button
					AendDate=  y + '-' + m + '-' + d ;  //today's date 
				
					AstartDate= y-2+ '-' + m +'-' + d; //24 months earlier
					
					Ain_IntelOnly="<?= $in_IntelOnlyButton  ?>";
					Ain_IntelOnly2="<?= $in_IntelOnlyButton2  ?>";
					
					Ain_SL       ="<?= $in_SecurityLevelButton ?>";
					Ain_MT       ="<?= $in_ModuleTypeButton ?>";
					Azoom="<?=$zoom ?>";
					Aurl= "<?=$URL_path ?>";
				</script>
   				<?php
   				if($in_TopButtons==1)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
   				 onclick="window.location.href=  Aurl + '/cmvp_active_by_status_pareto.php?zoom='+Azoom+'&in_ModuleTypeButton='+Ain_MT+'&in_SecurityLevelButton='+Ain_SL+ 
   				 '&in_IntelOnlyButton2='+Ain_IntelOnly2+'&in_IntelOnlyButton='+Ain_IntelOnly+'&in_TopButtons=1&startDate='+ AstartDate+ '&endDate='+ AendDate;"> Last 24 Months  
   				
   				</button> 
   </td>
   			<td> 
				<script>
					// last year button
					BendDate=  y-1 + '-12-31' ;  //Dec 31st of the current year
					BstartDate= y-1 + '-01' +'-01'; //Jan 1st of last year 
					
					Bin_IntelOnly="<?= $in_IntelOnlyButton  ?>";
					Bin_IntelOnly2="<?= $in_IntelOnlyButton2  ?>";
					
					Bin_SL="<?= $in_SecurityLevelButton ?>";
					Bin_MT="<?= $in_ModuleTypeButton ?>";
					Bzoom="<?=$zoom ?>";
					Burl= "<?=$URL_path ?>";
				</script>

   			 	<?php
   				if($in_TopButtons==2)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
   				 onclick="window.location.href=  Burl + '/cmvp_active_by_status_pareto.php?zoom='+Bzoom+'&in_ModuleTypeButton='+Bin_MT+'&in_SecurityLevelButton='+Bin_SL+
   				 '&in_IntelOnlyButton2='+Bin_IntelOnly2+'&in_IntelOnlyButton='+Bin_IntelOnly+ '&in_TopButtons=2&startDate='+BstartDate+ '&endDate='+BendDate;"> Last Year  
   				</button>  
   			</td> 
			<td>
				
				<script>
					//this year button
					CendDate=  y + '-' + m + '-' + d ;  //today's date 
					CstartDate= y + '-01' +'-01'; //january 1st of the current year
					
					Cin_IntelOnly="<?= $in_IntelOnlyButton  ?>";
					Cin_IntelOnly2="<?= $in_IntelOnlyButton2  ?>";

					Cin_SL="<?= $in_SecurityLevelButton ?>";
					Cin_MT="<?= $in_ModuleTypeButton ?>";
					Czoom="<?=$zoom ?>";
					Curl= "<?=$URL_path ?>";
				</script>
				<?php
   				if($in_TopButtons==3)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
   				
   				 onclick="window.location.href=  Curl + '/cmvp_active_by_status_pareto.php?zoom='+Czoom+'&in_ModuleTypeButton='+Cin_MT+'&in_SecurityLevelButton='+Cin_SL+ 
   				 '&in_IntelOnlyButton2='+ Cin_IntelOnly2 + '&in_IntelOnlyButton='+Cin_IntelOnly+'&in_TopButtons=3&startDate='+ CstartDate+ '&endDate='+ CendDate;"> This Year  
   				</button> 
   			
   			</td>
   			
   			

   			<td>
				<script>
					//all time button
					DendDate=  y + '-' +  m + '-' + d;  //today
					DstartDate=1995 + '-01-01'  ;  //birth of the CMVP program
					
					Din_IntelOnly="<?= $in_IntelOnlyButton  ?>";
					Din_IntelOnly2="<?= $in_IntelOnlyButton2  ?>";
					
					Din_SL="<?= $in_SecurityLevelButton ?>";
					Din_MT="<?= $in_ModuleTypeButton ?>";
					Dzoom="<?=$zoom ?>";
					Durl= "<?=$URL_path ?>";
				</script>
				<?php
   				if($in_TopButtons==4)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
				
   				  onclick="window.location.href=  Durl +  '/cmvp_active_by_status_pareto.php?zoom='+Dzoom+'&in_ModuleTypeButton='+Din_MT+'&in_SecurityLevelButton='+Din_SL+ 
   				  '&in_IntelOnlyButton2=' + Din_IntelOnly2 +'&in_IntelOnlyButton='+ Din_IntelOnly+ '&in_TopButtons=4&startDate='+ DstartDate+ '&endDate=' + DendDate ;"> All Time  
   				</button> 
   			</td>
</tr>
   
 </table> <!-- date buttons -->
   
<hr style="border:solid 1px #000080" />

<table>
	<tr>		<td style="width:100px">
	</td>
	<td>
		<img src="getchart.php?<?php echo $chart1URL?>" border="0" usemap="#map1">
	</td>
	</tr>
</table>

<map name="map1">
<?php echo $imageMap?>

<area <?php echo $coor_button1.  " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".$zoom."&in_TopButtons=". $in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
    " title='Validated Modules Status By Lab' />"; ?>

<area <?php echo $coor_button3. " href='".$URL_path."/cmvp_current_trend.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".$zoom."&in_TopButtons=".($in_TopButtons * -1)."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Average Number of Days in MIP based on Labs Past Performance (In Review + Coordination) ' />"?>

<area <?php echo $coor_button4. " href='".$URL_path."/cmvp_mip_historic_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Current & Historic MIP Data By Individual Module' />"?>

<area <?php echo $coor_button5. " href='".$URL_path."/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='MIP Forecast based on Labs past performace (Linear Regression Model) ' />"?>


<area <?php echo $coor_mip_warning_flash. " href='".$URL_path."/dump_mip_log_file.php' target='_new'"."title='Dump MIP Log File' />"?>
<area <?php echo $coor_active_warning_flash. " href='".$URL_path."/dump_active_log_file.php' target='_new'"."title='Dump_Active_Log_File' />"?>


<area <?php echo $coor_admin_button. " href='".$URL_path."/cmvp_LHI_Admin.php' target='_new'"."title='Admin Stuff' />"?>


<area <?php echo $coor_zoomIn. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom + .25)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom In' />"?>

<area <?php echo $coor_zoomOut." href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom - .25)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='ZoomerOut' />"?>

<area <?php echo $coor_zoomClear. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=1&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom Clear' />"?>


<area <?php echo $coor_IntelOnlyButton. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton ^ 1)."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_IntelOnlyButton2. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton)."&in_IntelOnlyButton2=".($in_IntelOnlyButton2 ^ 1) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>


<area <?php echo $coor_SL_ALL. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=0&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Show All Security Levels ' />"?>

<area <?php echo $coor_SL_1." href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=1&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Show SL=1 only ' />"?>

<area <?php echo $coor_SL_2. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=2&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Show SL=2 only ' />"?>

<area <?php echo $coor_SL_3. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=3&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Show SL=3 only ' />"?>

<area <?php echo $coor_SL_4. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=4&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Show SL=4 only ' />"?>


<area <?php echo $coor_MT_ALL. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=0&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Show All Module Types ' />"?>

<area <?php echo $coor_MT_1. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=1&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Show Hardware Modules only ' />"?>

<area <?php echo $coor_MT_2. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=2&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Show Software Modules only' />"?>

<area <?php echo $coor_MT_3. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=3&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Show Hybrid Modules (Firmware-Hybid or Software-Hybrid) only' />"?>

<area <?php echo $coor_MT_4. " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=4&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Show Firmware Modules only ' />"?>


</map>
</body>
</html> 