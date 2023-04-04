
<?php


//this php file defines whether the URL is for production or development for all the PHP files.
include './cmvp_define_LHI_dev_vs_prod.php';

//==========================================================

//require_once("../lib/phpchartdir.php");
require_once("phpchartdir.php");

//get the start and end date. 
 $self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '#';
 $now = date("Y-m-d");
 
 $today1 = isset($_POST['today1']) ? $_POST['today1'] : '1995-01-01' ; //Ealiest CMVP validation date
 $today2 = isset($_POST['today2']) ? $_POST['today2'] : (new DateTime)->format('Y-m-d');
  
 $startDate = isset($_REQUEST["startDate"]) ? date('Y-m-d',strtotime($_REQUEST["startDate"])) : date('Y-m-d', strtotime($today2));
 $endDate = isset($_REQUEST["endDate"]) ? date('Y-m-d',strtotime($_REQUEST["endDate"])) : date('Y-m-d', strtotime($today2));


//hard code this since I only have MIP intermediate date from this time range.
//$startDate='2020-07-28';
//$startDate='2019-09-01';

$endDate=date('Y-m-d',strtotime($today2));

$startDateOut=$startDate;  //used to keep track of which button is selected at the top if "Today" is choosen since there is no other app with "Today" option
$endDateOut=$endDate;

$in_TopButtons=isset($_REQUEST["in_TopButtons"]) ? $_REQUEST["in_TopButtons"] : 4;

$in_TopButtons=abs($in_TopButtons);  //not sure why, but sometimes in_TopButtons comes in as a negative number. Weird.



 $zoom=isset($_REQUEST["zoom"]) ? $_REQUEST["zoom"] : 1;

//1 means selected. 0 means not selected
$in_IntelOnlyButton=isset($_REQUEST["in_IntelOnlyButton"]) ? $_REQUEST["in_IntelOnlyButton"] : 0;
$in_IntelOnlyButton2=isset($_REQUEST["in_IntelOnlyButton2"]) ? $_REQUEST["in_IntelOnlyButton2"] : 0;

$in_ModuleTypeButton=isset($_REQUEST["in_ModuleTypeButton"]) ? $_REQUEST["in_ModuleTypeButton"] : 0;
$in_SecurityLevelButton=isset($_REQUEST["in_SecurityLevelButton"]) ? $_REQUEST["in_SecurityLevelButton"] : 0;
 
//echo "IntelOnly=".$in_IntelOnlyButton." MT=".$in_ModuleTypeButton." SL=".$in_SecurityLevelButton."<BR>";


 $in_StandardButton1=isset($_REQUEST["in_StandardButton1"]) ? $_REQUEST["in_StandardButton1"] : 0;
 $in_StandardButton2=isset($_REQUEST["in_StandardButton2"]) ? $_REQUEST["in_StandardButton2"] : 0;




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
define ("cobalt_blue",0x000020C2);


$data0 = array();   //average time spent in Review Pending
$data1 = array();   //average time spent in In_Review
$data2 = array();   //average time spent in Coordination
$data3=  array();   //average time spent in IR + CO

$labels= array();	  // Clean Lab Names
$data4 = array();   //number of modules this Clean Lab has done.


//===============================================
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

//echo $User;
//========================================
  
$conn = pg_connect($connStr);

//$hit_counter= " INSERT INTO \"CMVP_Hit_Counter\" ( \"URL\", \"Timestamp\",\"Date\", \"Application\",\"User\") values('".$URL_str."',(select (current_time(0) - INTERVAL '5 HOURS')),'". $today2."','cmvp_active_by_status_pareto.php','".$User."')";


//Don't add the developer "rfant' since there will be too many hits then.
$hit_counter=  " INSERT INTO \"CMVP_Hit_Counter\" (\"URL\",\"Timestamp\",\"Date\", \"Application\",\"User\") 
select '".$URL_str."', (select (current_time(0) - INTERVAL '5 HOURS')),'".$today2."', 'cmvp_current_trend.php', '".$User."'
where not exists (     select 1 from \"CMVP_Hit_Counter\" where \"User\" = 'rfant' and \"Date\" = (select current_date) );";

//echo "hit_str=".$hit_counter;
$result = pg_query($conn, $hit_counter);
//print "in_IntelOnlyButton=".$in_IntelOnlyButton."<br>";




$module_count=0;  //we can set the minimum number of modules a lab has done to be included in this plot
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



// Standard Selection Button 1 "140-2"
if($in_StandardButton1==1)
  $where_standard1 = " and ( \"Standard\" like '%140-2%'  ) ";
else
  $where_standard1= " and 1=1 ";

// Standard Secltion Button 2 "140-3"
if($in_StandardButton2==1)
  $where_standard2 = " and (  \"Standard\" like '%140-3%' ) ";
else
  $where_standard2= " and 1=1 ";

if($in_StandardButton1==1 && $in_StandardButton2==1)
{
  $where_standard1= " and ( \"Standard\" like '%140-2%' OR  \"Standard\" like '%140-3%' ) ";
  $where_standard2=" and 1=1 ";
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
    echo "ERROR 117:********* SecurityLevel=".$in_SecurityLevelButton."<BR>";
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
    echo "ERROR 138:********* Module TYpe=".$in_ModuleTypeButton."<BR>";
} //switch security level




#--\"Review_Pending_Start_Date\",\"In_Review_Start_Date\",\"Coordination_Start_Date\",\"Finalization_Start_Date\",

//--between '".$startDate."' and '".$endDate."'  and (\"Status2\" like '%Promoted%' OR \"Status2\" like '%Reappear%' OR \"Status2\" is null) 
//--between (select current_date) - INTERVAL '36 months' AND (select current_date)
$sql_Str="

select \"Clean_Lab_Name\" , count (*) as num_of_modules,
TRUNC(avg(abs(\"Review_Pending_Start_Date\"::date -\"In_Review_Start_Date\"::date) )) as rp,
TRUNC(avg(abs(\"In_Review_Start_Date\"::date - \"Coordination_Start_Date\"::date)) )as ir,
TRUNC(avg(abs(\"Coordination_Start_Date\" - \"Finalization_Start_Date\")) )as co,
TRUNC(avg(abs(\"Coordination_Start_Date\" - \"Finalization_Start_Date\")) ) + TRUNC(avg(abs(\"In_Review_Start_Date\"::date - \"Coordination_Start_Date\"::date)) ) as time_in_mip
from \"CMVP_MIP_Table\" where \"Review_Pending_Start_Date\" 
between '".$startDate."' and '".$endDate."'  
and   \"In_Review_Start_Date\" is not null 
AND  \"Finalization_Start_Date\" is not null
and (\"Status2\" like '%Promoted%' OR \"Status2\" like '%Reappear%' OR \"Status2\" is null) 
and \"Clean_Lab_Name\" is not null ".$where_standard1.$where_standard2.$where_vendor.$where_vendor2.$where_security.$where_MT."
  group by \"Clean_Lab_Name\" 
having count(*) > ".$module_count."
  order by time_in_mip
";

//echo "charlie Sq1_Str=<br>".$sql_Str."<br>";



$result= pg_query($conn,$sql_Str);

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
//echo "num_mod=".$num_mod."<br>";


if($num_mod>0)
{
	foreach($arr as $row){
    //$labels[]=$row['Clean_Lab_Name'] ; //.":".$row['num_of_modules']."";
    $labels[]=$row['Clean_Lab_Name'] .":".$row['num_of_modules']."";
  }

	foreach($arr as $row){
		$data0[]=$row['rp'];
	}
	foreach($arr as $row){
		$data1[]=$row['ir'];
	}
	foreach($arr as $row){
		$data2[]=$row['co'];
	}
  foreach($arr as $row){
    $data3[]=$row['time_in_mip'];
  }
 foreach($arr as $row){
    $data4[]=$row['num_of_modules'];
  }
} //num_mod > 0






//===================  configure and draw the chart ========================

# Create a XYChart object of size 800 x 600 pixels


$height=75 * $num_mod * $zoom + 25;
if($height<600)  $height=600;
$width=$zoom*900;

$buttonY=100;
$buttonX=$width-100;


//echo "zoom=".$zoom."  buttonY=".$buttonY."  buttonX=".$buttonX."<br>";

//$c = new XYChart($width,$height, brushedSilverColor(), Transparent, 2);
$c = new XYChart($width, $height, light_blue, black, 1);
$c->setRoundedFrame();


//==== Get the Last_Updated date =====================================================
$sql_Str_Last_Updated="select * from \"CMVP_MIP_Table\"  order by \"Row_ID\" desc limit 1 ;";
//echo "juliet sql=<br> ".$sql_Str_Last_Updated."<br>";
$result = pg_query($conn,$sql_Str_Last_Updated);
$arr = pg_fetch_all($result);
$row=sizeof($arr);
foreach($arr as $row){
    $Last_Updated=$row['Last_Updated'];
  }
//======================================================================================


//-------------
# Add a title to the chart using 15pt Times Bold Italic font. The text is white (ffffff) on a blue
# (0000cc) background, with glass effect.
$title = $c->addTitle("CMVP Average Time In MIP (Updated:".$Last_Updated.") ", "timesbi.ttf", 15, 0xffffff);
$title->setBackground(0x0000cc, 0x000000, glassEffect(ReducedGlare));

# Set the plotarea at (80, 100) and of size w-200 x h-160 pixels. 
//start          x,   y, w,   h,   background color, alt background color,edge color, horiz grid color, vert grid color
$c->setPlotArea(80, 100, $width-300, $height-160, white, -1, Transparent, 0x000000);
$c->setRoundedFrame(0xffffff, 20);


//-----------------------------------------------
//Draw some buttons
if($no_data_found_label==1)
{// add a no data found label
  $no_data_label=$c->addText(300,250,"No Data Found That Matches The Selected Filters","arialbd.tff",12*$zoom);
  $no_data_label->SetFontColor(red);
  $no_data_label->setSize(300,300);
}


// Admin Buttons

//------------- zoom buttons ---------------------------------------------------------

//draw a box around the zoom buttons with a label
$zoom_box = $c->addText($buttonX, $buttonY-65, "","arialbd.ttf", 10); //draw box outline around zoom buttons
$zoom_box->setSize(95, 45);
$zoom_box->setBackground(light_blue,black,0);
$zoom_box->setAlignment (5);
   
// add a zoom label
$zoom_label=$c->addText($buttonX+33,$buttonY-37,"Zoom","arialbd.tff",8);
$zoom_label->SetFontColor(black);
$zoom_label->setSize(150,30);


//if($zoom>1){
//$zoom_str="Zoom=". $zoom;
//$zoom_rp = $c->addText($buttonX+5,$buttonY-100, $zoom_str ,"arialbd.ttf", 10,black); //draw button
//$zoom_rp->setSize(20, 20);
//}

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
  $coor_zoomOut = $zoomOut->getImageCoor(); //only make clickable button if zoom in already used.


$zoomClear = $c->addText($buttonX+70, $buttonY-60, "!","arialbd.ttf", 12); //draw button
$zoomClear->setSize(20, 20);
$zoomClear->setBackground(gray1,-1,2);
$zoomClear->setAlignment (5);
$coor_zoomClear = $zoomClear->getImageCoor();


//gray1 on
//battleship_gray off

//-----  Chart Type buttons----------------------------


//gray1 means not selected
//battleship_gray means selected

$button1 = $c->addText($buttonX-100, $buttonY, "Validated","arialbd.ttf", 10); //draw button
$button1->setSize(80, 30);
$button1->setBackground(gray1,-1,2);
$button1->setAlignment (5);
$coor_button1 = $button1->getImageCoor();

$button3 = $c->addText($buttonX, $buttonY, "Lab Trend","arialbd.ttf", 10); //draw button
$button3->setSize(80, 30);
$button3->setBackground(battleship_gray,-1,-2);
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

$button6 = $c->addText($buttonX-100, $buttonY+100, "ESV Cert","arialbd.ttf", 10); //draw button
$button6->setSize(80, 30);
$button6->setBackground(gray1,-1,2);
$button6->setAlignment (5);
$coor_button6 = $button6->getImageCoor();

$buttonY=150; //100;
//---------- Filter Buttons for Charts --------------------------------------------------------
$filter_box = $c->addText($buttonX-100, $buttonY+105, "","arialbd.ttf", 10); //draw box outline around filter button
$filter_box->setSize(187, 250);
$filter_box->setBackground(light_blue,red,0);
$filter_box->setAlignment (5);
   
// do the labels first
//$data_from_label=$c->addText(400,60,"Only have MIP Data from 7/28/2020 to present","arialbd.tff",12);
//$data_from_label->SetFontColor(red);
//$data_from_label->setSize(150,30);


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



$security_label=$c->addText($buttonX-35,$buttonY+315,"Standard:","arialbd.tff",10);
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




$StandardButton1 = $c->addText($buttonX-65,  $buttonY+332, "140-2","arial.ttf", 8,black); //draw button
$StandardButton1->setSize(50,20);
if ($in_StandardButton1==1)
  $StandardButton1->setBackground(battleship_gray,-1,-2);
else
  $StandardButton1->setBackground(gray1,-1,2);
$StandardButton1->setAlignment (5);
$coor_StandardButton1 = $StandardButton1->getImageCoor();

$StandardButton2 = $c->addText($buttonX+3,  $buttonY+332, "140-3","arial.ttf", 8,black); //draw button
$StandardButton2->setSize(50,20);
if ($in_StandardButton2==1)
  $StandardButton2->setBackground(battleship_gray,-1,-2);
else
  $StandardButton2->setBackground(gray1,-1,2);
$StandardButton2->setAlignment (5);
$coor_StandardButton2 = $StandardButton2->getImageCoor();

//-------------------------------------------------------------------

# Swap the x and y axes to create a horizontal bar chart
$c->swapXY();

# Add a legend box at (280, 50) using vertical layout and 12pt Arial font. Set background and border
# to transparent and key icon border to the same as the fill color.
//                   x,  y,  vertical?, font       , font size
$b = $c->addLegend(80, 50,      false, "arialbd.ttf", 12);
$b->setBackground(Transparent, Transparent);
$b->setKeyBorder(SameAsMainColor);



# Add a stacked bar layer chart
$layer = $c->addBarLayer2(Stack);


# Add the four data sets to the bar layer
//$layer->addDataSet($data0, light_blue2, "Review Pending");
$layer->addDataSet($data1, yellow, "In Review");
$layer->addDataSet($data2, pumpkin_orange, "Coordination");
//$layer->addDataSet($data3, yellow,"Time In MIP");
//------------------------
if ($num_mod < 10) 
  $layer->setBarWidth(50);

//----------------------
# Set the bar border to transparent
$layer->setBorderColor(Transparent, softLighting(Top));

$layer->setAggregateLabelStyle("arialbd.ttf", 12);  # Enable labelling for the entire bar and use 12pt Arial font

# Set the aggregate label format

$layer->setAggregateLabelFormat("{value} days\n");
//$layer->setAggregateLabelFormat("{=$data4[8]}");
//$layer->setAggregateLabelFormat("{={x} +.25}");
//  $layer->setAggregateLabelFormat("{=$data4[8] +.25}");
//  $layer->setAggregateLabelFormat("{=$data4[={x}] alpha}");

                                  

//$c->yAxis->setLabelFormat("{={value}*86400+" . chartTime(strtotime($today2)) . "|yyyy-mm-dd}");


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
$c->yAxis->setTitle("Number of Days", "timesbi.ttf", 12);

# Set axis label style to 8pt Arial Bold
$c->xAxis->setLabelStyle("arialbd.ttf", 8);
$c->yAxis->setLabelStyle("arialbd.ttf", 8);

# Set axis line width to 2 pixels
$c->xAxis->setWidth(2);
$c->yAxis->setWidth(2);

# Create the image and save it in a temporary location
$chart1URL = $c->makeSession("chart1");

# Create an image map for the chart

$imageMap = $c->getHTMLImageMap("cmvp_show_details_current_trend.php", "{default}&in_IntelOnlyButton=".$in_IntelOnlyButton."&in_IntelOnlyButton2=".$in_IntelOnlyButton2."&in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate, "title='{xLabel}: {value|0} days'");
  

?>


<!--//=======================================================-->

<body style="margin:5px 0px 0px 5px">
<!--<div style="font-size:18pt; font-family:verdana; font-weight:bold">
    CMVP MIP Indicator as of  <?php echo $endDate ?>
</div>-->


 <table> <!-- date buttons -->

  <form action="<?= $self; ?>" method="POST"> 
  
    <tr>    <td align="right"> Start Date <input type="date" name="startDate" value="<?= $startDate;?>">   
        <td rowspan="2"> <td colspan="3"><img src = "./INTEL_FIPS_LOGO_v3.png"     height = "70" width = "262" /></td></td>
    
   <!-- <td rowspan="2"> <td colspan="2"> CST Lab Health Indicator</td></td>  --> 
        
    </tr>
  <tr>  <td align="right"> End Date   <input type="date" name="endDate" value="<?= $endDate;?>"> </td> <td>&nbsp</td></tr>
    <tr>  <td align="center">  <button type='submit' >    Refresh  </button> 
    </form>   
        </td>  
          <script>
          n =  new Date();
          y = n.getFullYear();
          m = n.getMonth() +1;   //have to add one to get current month since array is zero-index based.
          d = n.getDate();
          
        </script>
    <!--  <td style="width:100px" >

      </td>
    -->
      <td>
        
        <script>
          AendDate=  y + '-' + m + '-' + d ;  //today's date 
          
          AstartDate= y-2+ '-' + m +'-' + d; //16 months earlier
         
          Ain_IntelOnly="<?= $in_IntelOnlyButton  ?>";
          Ain_IntelOnly2="<?= $in_IntelOnlyButton2  ?>";
          Ain_SL="<?= $in_SecurityLevelButton ?>";
          Ain_MT="<?= $in_ModuleTypeBUtton ?>";
          Azoom="<?=$zoom ?>";
          Aurl= "<?=$URL_path ?>";
        </script>
          <?php
          if($in_TopButtons==1)
            echo "<button  style=\"background-color: gray;\" type=\"button\" ";
          else
            echo "<button  style=\"background-color: silver;\" type=\"button\" ";
          ?>
           onclick="window.location.href=Aurl + '/cmvp_current_trend.php?zoom='+Azoom+'&in_ModuleTypeButton='+Ain_MT+'&in_SecurityLevelButton='+Ain_SL+
           '&in_IntelOnlyButton2='+Ain_IntelOnly2+'&in_IntelOnlyButton='+Ain_IntelOnly+'&in_TopButtons=1&startDate='+ AstartDate+ '&endDate='+ AendDate;"> Last 24 Months  
          
          </button> 
          

        </td>
        <td> 
       <script>
          BendDate=  y-1 + '-12-31' ;  //Dec 31st of the current year
          
          BstartDate= y-1 + '-01' +'-01'; //Jan 1st of last year 
          
          Bin_IntelOnly="<?= $in_IntelOnlyButton  ?>";
          Bin_IntelOnly2="<?= $in_IntelOnlyButton2  ?>";
          Bin_SL="<?= $in_SecurityLevelButton ?>";
          Bin_MT="<?= $in_ModuleTypeBUtton ?>";
          Bzoom="<?=$zoom ?>";
          Burl= "<?=$URL_path ?>";
        </script>

          <?php
          if($in_TopButtons==2)
            echo "<button  style=\"background-color: gray;\" type=\"button\" ";
          else
            echo "<button  style=\"background-color: silver;\" type=\"button\" ";
          ?>


           onclick="window.location.href=Burl + '/cmvp_current_trend.php?zoom='+Bzoom+'&in_ModuleTypeButton='+Bin_MT+'&in_SecurityLevelButton='+Bin_SL+
           '&in_IntelOnlyButton2='+Bin_IntelOnly2+ '&in_IntelOnlyButton='+Bin_IntelOnly+ '&in_TopButtons=2&startDate='+BstartDate+ '&endDate='+BendDate;"> Last Year  
          </button>  
        </td> 
      <td>
        
        <script>
          CendDate=  y + '-' + m + '-' + d ;  //today's date 
          
          CstartDate= y + '-01' +'-01'; //january 1st of the current year
          
          Cin_IntelOnly="<?= $in_IntelOnlyButton  ?>";
          Cin_IntelOnly2="<?= $in_IntelOnlyButton2  ?>";
          Cin_SL="<?= $in_SecurityLevelButton ?>";
          Cin_MT="<?= $in_ModuleTypeBUtton ?>";
          Czoom="<?=$zoom ?>";
          Curl= "<?=$URL_path ?>";
        </script>
        <?php
          if($in_TopButtons==3)
            echo "<button  style=\"background-color: gray;\" type=\"button\" ";
          else
            echo "<button  style=\"background-color: silver;\" type=\"button\" ";
          ?>
          
           onclick="window.location.href=Curl + '/cmvp_current_trend.php?zoom='+Czoom+'&in_ModuleTypeButton='+Cin_MT+'&in_SecurityLevelButton='+Cin_SL+
           '&in_IntelOnlyButton2='+Cin_IntelOnly2+'&in_IntelOnlyButton='+Cin_IntelOnly+'&in_TopButtons=3&startDate='+ CstartDate+ '&endDate='+ CendDate;"> This Year  
          </button> 
        
        
        </td>
        
        <td>
       <script>
          
          DendDate=  y + '-' +  m + '-' + d;  //today
           
          DstartDate=1995 + '-01-01'  ;  //birth of the CMVP program
          
          Din_IntelOnly="<?= $in_IntelOnlyButton  ?>";
          Din_IntelOnly2="<?= $in_IntelOnlyButton2  ?>";
          Din_SL="<?= $in_SecurityLevelButton ?>";
          Din_MT="<?= $in_ModuleTypeBUtton ?>";
          Dzoom="<?=$zoom ?>";
          Durl= "<?=$URL_path ?>";
        </script>
        <?php
          if($in_TopButtons==4)
            echo "<button  style=\"background-color: gray;\" type=\"button\" ";
          else
            echo "<button  style=\"background-color: silver;\" type=\"button\" ";
          ?>
        
            onclick="window.location.href=Durl + '/cmvp_current_trend.php?zoom='+Dzoom+'&in_ModuleTypeButton='+Din_MT+'&in_SecurityLevelButton='+Din_SL+
           '&in_IntelOnlyButton2='+Din_IntelOnly2+'&in_IntelOnlyButton='+ Din_IntelOnly+ '&in_TopButtons=4&startDate='+ DstartDate+ '&endDate=' + DendDate ;"> All Time  
          </button> 
        </td>
        <td style="width:75px"></td>
        
       
</tr>
   
 </table> <!-- date buttons -->

  
<hr style="border:solid 1px #000080" />


<table>
	<tr>	<td style="width:100px">
	</td>
	<td>
		<img src="getchart.php?<?php echo $chart1URL?>" border="0" usemap="#map1">

	</td>
	</tr>
</table>
<map name="map1">
<?php echo $imageMap?>




<area <?php echo $coor_button1.  " href='".$URL_path."/cmvp_active_by_status_pareto.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
    " title='Validated Modules Status By Lab' />"; ?>

<area <?php echo $coor_button3. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Average Number of Days in MIP based on Labs Past Performance (In Review + Coordination) ' />"?>

<area <?php echo $coor_button4. " href='".$URL_path."/cmvp_mip_historic_stackedbar.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Current & Historic MIP Data By Individual Module' />"?>

<area <?php echo $coor_button5. " href='".$URL_path."/cmvp_mip_forecast_stackedbar.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='MIP Forecast based on Labs past performace (Linear Regression Model) ) ' />"?>

<area <?php echo $coor_button6. " href='".$URL_path."/cmvp_esv_pareto.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Entropy Source Validation Cert Pareto ' />"?>   
   
<area <?php echo $coor_zoomIn. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom + .25)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom In' />"?>

<area <?php echo $coor_zoomOut. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom - .25)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom Out) ' />"?>

<area <?php echo $coor_zoomClear. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=1&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom Clear) ' />"?>



<area <?php echo $coor_IntelOnlyButton. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton ^ 1)."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_IntelOnlyButton2. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2 ^ 1) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>



<area <?php echo $coor_SL_ALL. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=0&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_SL_1." href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=1&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_SL_2. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=2&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_SL_3. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=3&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_SL_4. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=4&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>


<area <?php echo $coor_MT_ALL. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=0&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_MT_1. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=1&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_MT_2. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=2&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_MT_3. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=3&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_MT_4. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".$in_StandardButton2."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=4&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Only Show Intel Products ' />"?>

<area <?php echo $coor_StandardButton1. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton1=".($in_StandardButton1 ^ 1)."&in_StandardButton2=".$in_StandardButton2."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Filter on FIPS 140-2  ' />"?>

<area <?php echo $coor_StandardButton2. " href='".$URL_path."/cmvp_current_trend.php?in_StandardButton2=".($in_StandardButton2 ^ 1)."&in_StandardButton1=".$in_StandardButton1."&in_ModuleTypeButton=".$in_ModuleTypeButton."&in_SecurityLevelButton=".$in_SecurityLevelButton."&in_IntelOnlyButton=".($in_IntelOnlyButton )."&in_IntelOnlyButton2=".($in_IntelOnlyButton2) ."&zoom=".($zoom)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Filter on FIPS 140-3 ' />"?>

</map>
</body>
</html>
