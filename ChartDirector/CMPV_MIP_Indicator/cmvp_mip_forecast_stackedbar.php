
<?php
//this php file defines whether the URL is for production or development for all the PHP files.
//Change the URL value in the below file for it to reflect in all the URL's that are used for the indicators
include './cmvp_define_url_prod_vs_develop.php';  
include './cmvp_define_which_database.php';


//==========================================================



//RF: Note, I don't need to include the "phpchartdir.php" or "define which database" below since it's already included in the forecast_confidence_generator

//require_once("phpchartdir.php");
//include './cmvp_define_which_database.php';

include './cmvp_mip_forecast_confidence_generator.php';

//$PROD=2;
//===============================================================================================================================


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
define ("gray_cloud",0x00B6B6B4);
define ("battleship_gray",0x00848482);
define ("pumpkin_orange",0x00F87217);
define ("platinum",0x00E5E4E2);
define ("light_slate_gray",0x006D7B8D);
define ("marble_blue",0x00566D7E);
define ("dark_slate_blue",0x002B3856);
define ("transparent",0xFF000000);


//Here, SW1 means Software Module with Security Level 1. Or HW4 means Hardware Module with SL=4. HY is hybrid while FW is firmware
$MT_Security_Array=array("SW1","SW2","HW1","HW2","HW3","HW4","HY1","HY2","HY3","HY4","FW1","FW2","FW3","FW4");

//===============================================
// Get all my input parameters that are passed into this app


 $self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '#';
 $now = date("Y-m-d");
 
 $today1 = isset($_POST['today1']) ? $_POST['today1'] : '1995-01-01' ; //Ealiest CMVP validation date
 $today2 = isset($_POST['today2']) ? $_POST['today2'] : (new DateTime)->format('Y-m-d');
  
 $startDate = isset($_REQUEST["startDate"]) ? date('Y-m-d',strtotime($_REQUEST["startDate"])) : date('Y-m-d', strtotime($today1));
 $endDate = isset($_REQUEST["endDate"]) ? date('Y-m-d',strtotime($_REQUEST["endDate"])) : date('Y-m-d', strtotime($today2));

 $in_TopButtons=isset($_REQUEST["in_TopButtons"]) ? $_REQUEST["in_TopButtons"] : 4;

 $zoom=isset($_REQUEST["zoom"]) ? $_REQUEST["zoom"] : 1;

 $months_to_look_back=isset($_REQUEST["months_to_look_back"]) ? $_REQUEST["months_to_look_back"] : 24;

//1 means selected. 0 means not selected
 $in_IntelOnlyButton=isset($_REQUEST["in_IntelOnlyButton"]) ? $_REQUEST["in_IntelOnlyButton"] : 0;
 $in_ModuleTypeButton=isset($_REQUEST["in_ModuleTypeButton"]) ? $_REQUEST["in_ModuleTypeButton"] : 0;
 $in_SecurityLevelButton=isset($_REQUEST["in_SecurityLevelButton"]) ? $_REQUEST["in_SecurityLevelButton"] : 0;
 
//echo "IntelOnly=".$in_IntelOnlyButton." MT=".$in_ModuleTypeButton." SL=".$in_SecurityLevelButton."<BR>";



 //echo "startDate=".$startDate." ";
 //echo "endDate=".$endDate;

//===============================================================================
//get sql query


//===============================================
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
'cmvp_mip_forecast_stackedbar.php','".$User."')";
//$result = pg_query($conn, $hit_counter);



//===============================================================================================================
//First off, get a list of all the Labs currently in the MIP table.
//This is needed whether I have to refresh the Forecast SQL table or not.

$get_lab_names_str="select distinct (\"Clean_Lab_Name\" ) from \"CMVP_MIP_Table\" order by \"Clean_Lab_Name\" ;";
$result = pg_query($conn,$get_lab_names_str);
$arr = pg_fetch_all($result);

foreach($arr as $row)
	$Clean_Lab_Name_Array[]=$row['Clean_Lab_Name'];  //get a list of all the Labs to calculate their time_in_mip


//Second off, see if I need to update my confidence numbers that are in the SQL table.
//If I don't see today's date in the forecast_sql table, then I'll have to
//take 60 seconds to refresh the table with today's data. Else, the numbers are current

$sql_Str_Confidence1="select * from \"Model_Forecast_Confidence\" where \"Date\" = '".$today2."' order by \"Row_ID\" desc limit 1 ;";
//echo "juliet sql=<br> ".$sql_Str_Confidence1."<br>";
$result = pg_query($conn,$sql_Str_Confidence1);
$arr = pg_fetch_all($result);

//calculate_confidence will be run once a day to refresh the numbers. Once today's numbers are in table, it will skip this.
//I'll loop on all the Lab Names and all the MT_Security types.
if ($arr==null)
{	for($i=0;$i<sizeof($Clean_Lab_Name_Array)-1;$i++)
		for($j=0;$j<sizeof($MT_Security_Array)-1;$j++)
			//calculate_confidence($Clean_Lab_Name_Array[$i],$MT_Security_Array[$j],$PROD);
			calculate_confidence($Clean_Lab_Name_Array[$i],$MT_Security_Array[$j],$PROD,$ciphering,$iv_length,$decryption_key,$options,$decryption_iv);
			

	//update all the averages for each Lab.  Since, I don't know the Module Type and SL of any of the Labs in MIP (except for Intel of course)
	// then I'll have to take the average for all the module types and SL for each lab. Not ideal, but better than nothing. That way too, if
	// the CMVP ever starts to publish the Module_Type and SL on their MIP page, I'll be ready to take advantage of it.

	//Also, if any of the Lab specific forecast numbers are null, then I'll replace them with RP=270 IR=72 C0=99 (which are the overall lab average for h/w modules)
	$RP_default=270;
	$IR_default=72;
	$CO_default=99;

    
	$update_avg_str="UPDATE  \"CMVP_MIP_Table\" SET \"Y_RP_Avg\" = t2.\"rp_avg\" , \"Y_IR_Avg\"=t2.\"ir_avg\", \"Y_CO_Avg\"=t2.\"co_avg\" 
	from(	SELECT 	\"Clean_Lab_Name\", 	AVG (\"Model_Forecast_Confidence\".\"RP_Value\") as rp_avg,avg(\"Model_Forecast_Confidence\".\"IR_Value\") as ir_avg,avg(\"Model_Forecast_Confidence\".\"CO_Value\")as co_avg FROM \"Model_Forecast_Confidence\"
	INNER JOIN \"CMVP_MIP_Table\" USING(\"Clean_Lab_Name\") GROUP BY \"Clean_Lab_Name\" ) as t2  	where \"CMVP_MIP_Table\".\"Clean_Lab_Name\" = t2.\"Clean_Lab_Name\"; 

 	UPDATE  \"CMVP_MIP_Table\" SET 		\"Y_RP_Avg\"= (case when \"Y_RP_Avg\" is null then ".$RP_default." else \"Y_RP_Avg\" end) , 
								\"Y_IR_Avg\"=  (case when \"Y_IR_Avg\" is null then ".$IR_default." else \"Y_IR_Avg\" end), 
								\"Y_CO_Avg\"=  (case when \"Y_CO_Avg\" is null then ".$CO_default." else \"Y_CO_Avg\" end) ;";

 	//echo "zulu2 sql_str=<br>".$update_avg_str."<br>";
 	$result = pg_query($conn,$update_avg_str);
	$arr = pg_fetch_all($result);


} //if arr null


//=======================================================================================
// draw the chart

// Create a XYChart object of size width x height pixels. Set the background to light blue # with a black border (0x0)

if($in_IntelOnlyButton==0)
{
	$width=$zoom*900;
	$height=$zoom*600;
}
else
{
	$width=$zoom*900;
	$height=$zoom*700;//600
}


$c = new XYChart($width, $height, light_blue, black, 1);

$c->setRoundedFrame();


# Set the plotarea at (w-300,h-200) and of size w x h pixels. Use white (0xffffff) background.
$plotAreaObj = $c->setPlotArea(80,100, $width-300, $height-200);
$plotAreaObj->setBackground(0xffffff);

# Add a title to the chart using 15pt Times Bold Italic font. The text is white (ffffff) on a blue
# (0000cc) background, with glass effect.
$title = $c->addTitle("CMVP MIP Forecast ", "timesbi.ttf", 15, 0xffffff);
$title->setBackground(0x0000cc, 0x000000, glassEffect(ReducedGlare));



//---------------------------------------------------------------------
//Draw some buttons on the right side of the plot



// define the where the buttons starting point based on zoom level
$buttonY=100;
$buttonX=$width - 100;  

//------------- zoom buttons -----
//draw a box around the zoom buttons with a label
$zoom_box = $c->addText($buttonX, $buttonY-65, "","arialbd.ttf", 10); //draw box outline around filter button
$zoom_box->setSize(95, 45);
$zoom_box->setBackground(light_blue,black,0);
$zoom_box->setAlignment (5);
   
// add a zoom label
$zoom_label=$c->addText($buttonX+33,$buttonY-37,"Zoom","arialbd.tff",8);
$zoom_label->SetFontColor(black);
$zoom_label->setSize(150,30);


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


//-----  Chart Type buttons----------------------------
//gray1 means 'not selected'
//battleship_gray means 'selected'

$button1 = $c->addText($buttonX-100, $buttonY, "Validated","arialbd.ttf", 10); //draw button
$button1->setSize(80, 30);
$button1->setBackground(gray1,-1,2);
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
$button5->setBackground(battleship_gray,-1,-2);
$button5->setAlignment (5);
$coor_button5 = $button5->getImageCoor();

//---------- Filter Buttons for Charts -----------------------
$filter_box = $c->addText($buttonX-100, $buttonY+105, "","arialbd.ttf", 10); //draw box outline around filter button
$filter_box->setSize(187, 250);
$filter_box->setBackground(light_blue,red,0);
$filter_box->setAlignment (5);
   
// do the labels first
$filter_label=$c->addText($buttonX-30,$buttonY+110,"Filters:","arialbd.tff",10);
$filter_label->SetFontColor(red);
$filter_label->setSize(150,30);
   
$security_label=$c->addText($buttonX-50,$buttonY+175,"Security Level:","arialbd.tff",10);
$security_label->SetFontColor(red);
$security_label->setSize(150,30);


$security_label=$c->addText($buttonX-50,$buttonY+250,"Module Type:","arialbd.tff",10);
$security_label->SetFontColor(red);
$security_label->setSize(150,30);

//now do the clickable buttons
$IntelOnlyButton = $c->addText($buttonX-40,  $buttonY+130, "Intel Only","arialbd.ttf", 8,black); //draw button
$IntelOnlyButton->setSize(60, 25);
if ($in_IntelOnlyButton==1)
	$IntelOnlyButton->setBackground(battleship_gray,-1,-2);
else
 	$IntelOnlyButton->setBackground(gray1,-1,2);
$IntelOnlyButton->setAlignment (5);
$coor_IntelOnlyButton = $IntelOnlyButton->getImageCoor();


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

//--------------------------------------------
# Add a legend box at (480, 20) using vertical layout and 12pt Arial font. Set background and border
# to transparent and key icon border to the same as the fill color.
#
$b = $c->addLegend(50,50, false, "arialbd.ttf", 12);
$b->setBackground(Transparent, Transparent);
$b->setKeyBorder(SameAsMainColor);


//---------------------------------------------------------------------------------------------



//get my forecast information from sql table "Model_Forecast_Table" where I've stored all the model calculations
//already. The calculations are run once a day, and take several minutes to calculate. So, it's far better to do it once in the early morning (as a cron job), and stored the
//information so I can retrieve it here later.



//========================================================
//Build my SQL query using the forecast values/confidence I just retrieved.
//
//these are for  modules that have already entered "Reivew_Pending"


// Intel Vendor Only
if($in_IntelOnlyButton==1)
{
  //$where_vendor = " and \"Vendor_Name\" like '%Intel Corp%' and \"Cert_Num\" is null ";
  $where_vendor = " and ( \"Vendor_Name\" like '%Intel Corp%'  OR \"Status3\" like '%Intel_Certifiable%' ) ";
  $module_count=0;  //we can set the minimum number of modules a lab has done to be included in this plot
}
else
{ 
  $where_vendor= " and  \"Vendor_Name\" like '%' and \"Cert_Num\" is null ";
  $module_count=0;  //we can set the minimum number of modules a lab has done to be included in this plot
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
    $where_security = " and \"SL\" =2 ";
    break;
  case 3:
    $where_security = " and \"SL\" =3 ";
    break;
  case 4:
    $where_security = " and \"SL\" =4 ";
    break;
  default:
    echo "ERROR 470:********* SecurityLevel=".$in_SecurityLevelButton."<BR>";
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
    $where_MT = " and \"Clean_Module_Type\" = 'Software' ";
    break;
  case 3:
    $where_MT = " and \"Clean_Module_Type\" ='Hybrid' ";
    break;
  case 4:
    $where_MT = " and \"Clean_Module_Type\" ='Firmware' ";
    break;
  default:
    echo "ERROR 493:********* Module TYpe=".$in_ModuleTypeButton."<BR>";
} //switch security level


//adding 0.1 in the below SQL query means that it is running late. This allows me to add a message at the end of the bar to the plot "running late"


$months_to_look_back=36; //24;
$sql_Str_Forecast=" 
(
select \"Module_Name\",\"Clean_Lab_Name\",\"Vendor_Name\", \"Review_Pending_Start_Date\" as StartDate, rp_time_remaining, \"Y_IR_Avg\" as ir_time_remaining, \"Y_CO_Avg\" as co_time_remaining from ( 
	select \"Y_IR_Avg\",\"Y_CO_Avg\",\"Module_Name\",\"Clean_Lab_Name\",\"Vendor_Name\", \"Review_Pending_Start_Date\", 
	case when (select (current_date)::date - (\"Review_Pending_Start_Date\" + INTERVAL '1 day' *\"Y_RP_Avg\")::date ) <0 then abs((select (current_date)::date - (\"Review_Pending_Start_Date\" + INTERVAL 	'1 day' * \"Y_RP_Avg\" )::date ))  
	else
	(select (current_date)::date - (\"Review_Pending_Start_Date\" + INTERVAL '1 day' * \"Y_RP_Avg\" )::date ) +.1 end  as rp_time_remaining 
	from \"CMVP_MIP_Table\" where 1=1 ".$where_vendor.$where_security.$where_MT." and \"Clean_Lab_Name\" is not null and \"Review_Pending_Start_Date\" between (select CURRENT_DATE) - INTERVAL '".$months_to_look_back." months' and (select current_date) and (\"Status2\" is null OR \"Status2\" like '%Reappear%' ) and \"In_Review_Start_Date\" is null and \"Coordination_Start_Date\" is null and \"Finalization_Start_Date\" is null ) as subquery where rp_time_remaining >=0  order by \"Review_Pending_Start_Date\" 
) 
UNION
(
select \"Module_Name\",\"Clean_Lab_Name\", \"Vendor_Name\",\"In_Review_Start_Date\" as StartDate , 0 as rp_time_remaining, ir_time_remaining, \"Y_CO_Avg\" as co_time_remaining 
 from ( 
	select \"Y_CO_Avg\",\"Module_Name\",\"Clean_Lab_Name\",\"Vendor_Name\", \"Review_Pending_Start_Date\",\"In_Review_Start_Date\", 
	 case when (select (current_date)::date - (\"In_Review_Start_Date\" + INTERVAL '1 day' * \"Y_IR_Avg\" )::date )<0 then abs((select (current_date)::date - (\"In_Review_Start_Date\" + INTERVAL 
	 '1 day' * \"Y_IR_Avg\" )::date ))  else
	 abs((select (current_date)::date - (\"In_Review_Start_Date\" + INTERVAL '1 day' * \"Y_IR_Avg\" )::date )) + .1 end as ir_time_remaining 
	 from \"CMVP_MIP_Table\" where 1=1 ".$where_vendor.$where_security.$where_MT." and \"Clean_Lab_Name\" is not null and \"Review_Pending_Start_Date\" between (select CURRENT_DATE) - INTERVAL '".$months_to_look_back." months' and (select current_date) and (\"Status2\" is null OR \"Status2\" like '%Reappear%') and \"In_Review_Start_Date\" is not null and \"Coordination_Start_Date\" is null and \"Finalization_Start_Date\" is null ) as subquery order by \"Review_Pending_Start_Date\" )
union 
( select \"Module_Name\",\"Clean_Lab_Name\",\"Vendor_Name\", \"Coordination_Start_Date\" as StartDate, 0 as rp_time_remaining, 0 as ir_time_remaining, co_time_remaining from ( 
	select \"Module_Name\",\"Clean_Lab_Name\",\"Vendor_Name\",\"Review_Pending_Start_Date\",\"Coordination_Start_Date\", 
	case when ( select (current_date)::date - (\"Coordination_Start_Date\" + INTERVAL '1 day' * \"Y_CO_Avg\" )::date )<0 then abs(( select (current_date)::date - (\"Coordination_Start_Date\" + INTERVAL 
	'1 day' * \"Y_CO_Avg\")::date ))  else 
	abs(( select (current_date)::date - (\"Coordination_Start_Date\" + INTERVAL '1 day' *\"Y_CO_Avg\" )::date )) + .1 end as co_time_remaining 
	from \"CMVP_MIP_Table\" where 1=1 ".$where_vendor.$where_security.$where_MT." and \"Clean_Lab_Name\" is not null and \"Review_Pending_Start_Date\" between (select CURRENT_DATE) - INTERVAL '".$months_to_look_back." months' and (select current_date) and (\"Status2\" is null OR \"Status2\" like '%Reappear%') and \"Coordination_Start_Date\" is not null and \"Finalization_Start_Date\" is null ) as subquery order by \"Review_Pending_Start_Date\" 
) order by StartDate 
";
//echo "delta2 sql =<br>".$sql_Str_Forecast."<BR>"; 

$result = pg_query($conn,$sql_Str_Forecast);
$arr = pg_fetch_all($result);
if($arr==null)
	{   //echo "<br>ERROR 372d:  SQL Query returned nothing.MT_Security=".$MT_Security."<br> SQL=<br>".$sql_Str_Forecast."</br>";
	$num_mod=0;
	$no_data_found_label=1;
	}
else
{	$num_mod=sizeof($arr);
	$no_data_found_label=0;
	//echo "Records Found=".$num_mod;
}
foreach($arr as $row) 
	$dataY_rp_forecast[]=$row['rp_time_remaining'];  //how much time is left before I exit "Review Pending"?

foreach($arr as $row) 
	$dataY_ir_forecast[]=$row['ir_time_remaining'];  //how much time is left before I exit "In Review"?

foreach($arr as $row) 
	$dataY_co_forecast[]=$row['co_time_remaining'];  //how much time is left before I exit "Coordination"?

foreach($arr as $row) 
	$labels_forecast[]=$row['startdate']; // forecasted start dates used for x-axis

//foreach($arr as $row) 
//	$TID_forecast[]=$row['TID']; //TID for tool tip / hover information

foreach($arr as $row) 
	$Vendor_Name_forecast[]=$row['Vendor_Name']; //Get the Vendor Name for tool tip/ hover information

foreach($arr as $row) 
	$Lab_Name_forecast[]=$row['Clean_Lab_Name']; //."(".$row['Vendor_Name'].")"; 

foreach($arr as $row) 
	$Module_Name_forecast[]=$row['Module_Name']; 



//------------------------------
// Let's draw the plot now!
//
$layer = $c->addBarLayer2(Stack);

// assign my data to the bars now.
$layer->addDataSet($dataY_rp_forecast,light_blue2, "Review_Pending");
$layer->addDataSet($dataY_ir_forecast,red, "In_Review");
$layer->addDataSet($dataY_co_forecast,green, "Coordination");
$layer->addExtraField($Module_Name_forecast);  //field0
$layer->addExtraField($Vendor_Name_forecast);  //field1
//$layer->setAggregateLabelFormat("{field0}");


# Use the format "RUNNING LATE" as the bar label
//$layer->setAggregateLabelFormat("LATE: {value} days");

# Set the bar label font to 10pt Times Bold Italic/dark red (0x663300)
//$layer->setAggregateLabelStyle("timesbi.ttf", 6*$zoom, 0x663300);



if($no_data_found_label==1)
{// add a no data found label
  $no_data_label=$c->addText(300,250,"No Data Found That Matches The Selected Filters","arialbd.tff",12*$zoom);
  $no_data_label->SetFontColor(red);
  $no_data_label->setSize(300,300);
}



for ($i = 0; $i < $num_mod; $i++)
{
	$rp_diff=$dataY_rp_forecast[$i] - number_format($dataY_rp_forecast[$i],0);
	$ir_diff=$dataY_ir_forecast[$i] - number_format($dataY_ir_forecast[$i],0);
	$co_diff=$dataY_co_forecast[$i] - number_format($dataY_co_forecast[$i],0);
  if(	$dataY_rp_forecast[$i] - number_format($dataY_rp_forecast[$i],0) >0 
			OR
    	$dataY_ir_forecast[$i] - number_format($dataY_ir_forecast[$i],0) >0 
    	OR
    	$dataY_co_forecast[$i] - number_format($dataY_co_forecast[$i],0) >0 )
		  {
			  $layer->addCustomAggregateLabel($i, "RUNNING LATE ","timesbd.ttf",6*$zoom, red);
			  //echo "i=".$i." LATE: rp=".$rp_diff.".   ir=".$ir_diff.".   co=".$co_diff.".  <br></br>"; 
			  $late_keyword[$i]='Late';  
		  }
		  else
		  	$late_keyword[$i]='Remaining';  //used for the hover tool-tip description
		  
}
$layer->addExtraField($late_keyword);  //field2.  //used for the hover tool-tip description

// set attributes of stacked bars
//Set the sub-bar gap to 0, so there is no gap between stacked bars with a group
$layer->setBarWidth (4); //(20 );
$layer->setBarGap(0.15, 1);
//$layer->setBorderColor(Transparent); // Set the bar border to transparent

//set the x-axis labels (xLabels) using the $TID labels
if($zoom==1)   //have to play with the TID axis font size since it gets truncated with zooming.
	$TID_fontsize=7;
if($zoom==1.5)
	$TID_fontsize=10;
if($zoom>=2)
	$TID_fontsize=11;
else
	$TID_fontsize=8;

$textBoxObj = $c->xAxis->setLabelStyle("arial.ttf", $TID_fontsize, black);
//$c->xAxis->setLabels($TID_forecast);
$c->xAxis->setLabels($Lab_Name_forecast);

//set up the y-axis. 
$textBoxObj = $c->yAxis->setLabelStyle("arial.ttf", 10, black);
$textBoxObj->setFontAngle(90);
$c->yAxis->setLabelFormat("{={value}*86400+" . chartTime(strtotime($today2)) . "|yyyy-mm-dd}"); //calculate the number of seconds elapsed, then covert to days, months, etc.

# Set the  major tick length to 0 pixels and minor tick length to 0 pixels (-ve means ticks inside  the plot area)
$c->yAxis->setTickLength2(0,0);



//Set up the 2nd x-axis ( xAxis2) located at the top of the plot. set up the x2Labels to use TID for the mouse-over tool -tip
//set the color the same as the background so as to hide them. I don't really want the clutter of all the TIDs showing.
$textBoxObj=$c->xAxis2->setLabelStyle("arial.ttf",10,light_blue);  
$c->xAxis2->setLabels($Vendor_Name);
$c->xAxis2->setTickLength2(0,0);# Set the  major tick length to 0 pixels and minor tick length to 0 pixels 

# Swap the x and y axis to become a rotated chart
$c->swapXY();

//-------------------------------------------------------------------------------
//add some milestone bars here
//

$milestone1 =  strtotime("2021-07-22") - strtotime($today2);
$milestone1= number_format( ($milestone1 / (60*60*24)),0);
//if($milestone1 > 0)
//	$c->yAxis->addMark($milestone1, red, ""); # Add a vertical red mark line 

$milestone1 =  strtotime("2021-08-31") - strtotime($today2);
$milestone1= number_format( ($milestone1 / (60*60*24)),0);
//if($milestone1 > 0)
//	$c->yAxis->addMark($milestone1, red, ""); # Add a vertical red mark line 

//$milestone1 =  strtotime("2021-12-21") - strtotime($today2);
//$milestone1= number_format( ($milestone1 / (60*60*24)),0);
//if($milestone1 > 0)
//	$c->yAxis->addMark($milestone1, green, ""); # Add a vertical red mark line 


//------------------------------------------------------


# Create the image and save it in a temporary location
$chart1URL = $c->makeSession("chart1");

# Create an image map for the chart
//$imageMap = $c->getHTMLImageMap("cmvp_show_details_mip_forecast_stackedbar.php", "{default}&months_to_look_back=".$months_to_look_back."&forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&startDate=".$startDate."&endDate=".$endDate, 	"title='lab: {xLabel|0}\nVendor: \"{x2Label|0}\"\nmodue: {field0}\n{dataSetName|0} DaysLeft={values}  '");
  
//$imageMap = $c->getHTMLImageMap("cmvp_show_details_mip_forecast_stackedbar.php", "{default}&months_to_look_back=".$months_to_look_back."&forecast_Y_rp=".$forecast_Y_rp."&forecast_Y_ir=".$forecast_Y_ir."&forecast_Y_co=".$forecast_Y_co."&startDate=".$startDate."&endDate=".$endDate,"title='Lab: {xLabel}\nVendor: {field1}\nModule: {field0}\n{dataSetName} Days Left={values}'");


//can re-use the show_details php from the historic stackedbar chart since it's the same info. No point in reinventing the wheel   
$imageMap = $c->getHTMLImageMap("cmvp_show_details_mip_historic_stackedbar.php", "{default}&Vendor_Name='{field1}'&Module_Name='{field0}'&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate, " title='Vendor: {field1}\nModule: {field0}\n{dataSetName}: {value} Days {field2}'");

 


?>  
<!----------------------------------------------------------------------------------------------------------------->
<body style="margin:5px 0px 0px 5px">


<table> <!-- date buttons -->

	<form action="<?= $self; ?>" method="POST"> 
	
   	<tr>    <td align="right"> Start Date <input type="date" name="startDate" value="<?= $startDate;?>">   
   	 		<td rowspan="2"> <td colspan="3"><img src = "http:<?=$URL_str?>/ChartDirector/CMVP_MIP_Indicator/INTEL_FIPS_LOGO_v3.png"     height = "70" width = "262" /></td></td>
   	
   <!--	<td rowspan="2"> <td colspan="2"> CST Lab Health Indicator</td></td>	-->	
   			
   			
   	</tr>
	<tr>	<td align="right"> End Date   <input type="date" name="endDate" value="<?= $endDate;?>"> </td> <td>&nbsp</td></tr>
   	<tr> 	<td align="center">  <button type='submit' >    Refresh  </button> 
   	</form> 	
   			</td>  
   				<script>
					n =  new Date();
					y = n.getFullYear();
					m = n.getMonth() +1;   //have to add one to get current month since array is zero-index based.
					d = n.getDate();
					
				</script>
		
			<td>
				
				<script>
					AendDate=  y + '-' + m + '-' + d ;  //today's date 
					
					AstartDate= y-2+ '-' + m +'-' + d; //24 months earlier

					Azoom= <?php echo $zoom ?>;
				  Aurl="<?= $URL_str; ?>";
					Azoom="<?=$zoom ?>";

				</script>
   				<?php
   				if($in_TopButtons==1)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
   				 onclick="window.location.href='http:'+ Aurl+ '/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?zoom='+Azoom+'&in_TopButtons=1&startDate='+ AstartDate+ '&endDate='+ AendDate;"> Last 24 Months  
   				
   				</button> 
   			</td>
   			<td> 
				<script>
					BendDate=  y-1 + '-12-31' ;  //Dec 31st of the current year
					BstartDate= y-1 + '-01' +'-01'; //Jan 1st of last year 
					Bzoom= <?php echo $zoom ?>;
					 Burl="<?= $URL_str; ?>";
					Bzoom="<?=$zoom ?>"; 
				</script>

   			 	<?php
   				if($in_TopButtons==2)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
   				 onclick="window.location.href='http:'+ Burl+ '/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?zoom='+Bzoom+'&in_TopButtons=2&startDate='+BstartDate+ '&endDate='+BendDate;"> Last Year  
   				</button>  
   			</td> 
			<td>
				<script>
					CendDate=  y + '-' + m + '-' + d ;  //today's date 
					CstartDate= y + '-01' +'-01'; //january 1st of the current year
					Czoom= <?php echo $zoom ?>;
					C_url="<?= $URL_str; ?>";
					Czoom="<?=$zoom ?>";
				</script>
				<?php
   				if($in_TopButtons==3)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
   				 onclick="window.location.href='http:'+ C_url+ '/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?zoom='+Czoom+'&in_TopButtons=3&startDate='+ CstartDate+ '&endDate='+ CendDate;"> This Year  
   				</button> 
   			</td>
   			<td>
				<script>
					DendDate=  y + '-' +  m + '-' + d;  //today
					DstartDate=1995 + '-01-01'  ;  //birth of the CMVP program
					Dzoom= <?php echo $zoom ?>;
					Durl="<?= $URL_str; ?>";
					Dzoom="<?=$zoom ?>";
				</script>
				<?php
   				if($in_TopButtons==4)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
   				  onclick="window.location.href='http:'+ Durl+ '/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?zoom='+Dzoom+'&in_TopButtons=4&startDate='+ DstartDate+ '&endDate=' + DendDate ;"> All Time  
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
<area <?php echo $coor_button1.  " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_active_by_status_pareto.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
    " title='Validated Modules Status By Lab' />"; ?>

<area <?php echo $coor_button3. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_current_trend.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Average Number of Days in MIP based on Labs Past Performance (In Review + Coordination) ' />"?>
<area <?php echo $coor_button4. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_historic_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Current & Historic MIP Data By Individual Module' />"?>
<area <?php echo $coor_button5. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='MIP Forecast based on Labs past performace (Linear Regression Model) ' />"?>
   
<area <?php echo $coor_zoomIn. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom + .25)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom In' />"?>
<area <?php echo $coor_zoomOut. " href='http:/".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom - .25)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom Out) ' />"?>
<area <?php echo $coor_zoomClear. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=1&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom Clear) ' />"?>




<area <?php echo $coor_IntelOnlyButton. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton ^1 )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>



<area <?php echo $coor_SL_ALL. " href='http:".$URL_str."//ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=0&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_SL_1." href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=1&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_SL_2. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=2&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_SL_3. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=3&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_SL_4. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=4&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>


<area <?php echo $coor_MT_ALL. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=0&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_MT_1. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=1&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_MT_2. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=2&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_MT_3. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=3&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_MT_4. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?in_ModuleTypeButton=4&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>



</map>
</body>
</html> 


