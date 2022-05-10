
<?php

//this php file defines whether the URL is for production or development for all the PHP files.
//Change the URL value in the below file for it to reflect in all the URL's that are used for the indicators
include './cmvp_define_url_prod_vs_develop.php';  
//==========================================================

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
 $now = date("Y-m-d");
 
 $today1 = isset($_POST['today1']) ? $_POST['today1'] : '1995-01-01' ; //Ealiest CMVP validation date
 $today2 = isset($_POST['today2']) ? $_POST['today2'] : (new DateTime)->format('Y-m-d');
  
// $startDate = isset($_REQUEST["startDate"]) ? $_REQUEST["startDate"] : date('Y-m-d', strtotime($today1));
// $endDate = isset($_REQUEST["endDate"]) ? $_REQUEST["endDate"] : date('Y-m-d', strtotime($today2));

 $startDate = isset($_REQUEST["startDate"]) ? date('Y-m-d',strtotime($_REQUEST["startDate"])) : date('Y-m-d', strtotime($today1));
 $endDate = isset($_REQUEST["endDate"]) ? date('Y-m-d',strtotime($_REQUEST["endDate"])) : date('Y-m-d', strtotime($today2));

 $in_TopButtons=isset($_REQUEST["in_TopButtons"]) ? $_REQUEST["in_TopButtons"] : 4;
  
 $zoom=isset($_REQUEST["zoom"]) ? $_REQUEST["zoom"] : 1; 
 //echo "startDate=".$startDate." ";
 //echo "endDate=".$endDate;


//=================================================================================
#connect to postgreSQL database, build by SLQ query and get my chart data

$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$connStr = "host=localhost  dbname=postgres user=postgres password=postgres connect_timeout=5 options='--application_name=$appName'";


$User=get_current_user();
$conn = pg_connect($connStr);
$hit_counter= " INSERT INTO \"CMVP_Hit_Counter\" ( \"URL\", \"Timestamp\",\"Date\", \"Application\",\"User\") values('".$URL_str."',(select (current_time(0) - INTERVAL '5 HOURS')),'". $today2."',
'cmvp_active_by_module_type_pareto.php','".$User."')";
//$result = pg_query($conn, $hit_counter);


//non Intel vendors

$sql_Str1 = " 
	WITH sums AS ( 
SELECT 'Hardware' as type1, \"Clean_Module_Type\" as type, 
	count(hardware + sl1 +InclusiveDates ) as sl1, 
	count(hardware + sl2 + InclusiveDates ) as sl2, 
	count(hardware + sl3 + InclusiveDates) as sl3,
	count(hardware + sl4 + InclusiveDates) as sl4 
	FROM ( SELECT \"Clean_Module_Type\", 
		  CASE WHEN (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDates, 
		  CASE WHEN \"Clean_Module_Type\" like 'Hardware' THEN 1 END hardware, 
		  CASE WHEN \"SL\" =1 THEN 1 END sl1, 
		  CASE WHEN \"SL\" =2 THEN 1 END sl2, 
		  CASE WHEN \"SL\" =3 THEN 1 END sl3, 
		  CASE WHEN \"SL\" =4 THEN 1 END sl4 FROM \"CMVP_Active_Table\" where \"Vendor_Name\" not like '%Intel Corp%' ) \"CMVP_Active_Table\" group by type 
Union 
SELECT 'Software' as type1,\"Clean_Module_Type\" as type, 
	count(software + sl1 +InclusiveDates ) as sl1, 
	count(software + sl2 + InclusiveDates ) as sl2, 
	count(software + sl3 + InclusiveDates) as sl3, 
	count(software + sl4 + InclusiveDates) as sl4 
	FROM ( SELECT \"Clean_Module_Type\", 
		  CASE WHEN (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDates, 
		  CASE WHEN \"Clean_Module_Type\" like 'Software' THEN 1 END software, 
		  CASE WHEN \"SL\" =1 THEN 1 END sl1, 
		  CASE WHEN \"SL\" =2 THEN 1 END sl2, 
		  CASE WHEN \"SL\" =3 THEN 1 END sl3, 
		  CASE WHEN \"SL\" =4 THEN 1 END sl4 FROM \"CMVP_Active_Table\" where \"Vendor_Name\" not like '%Intel Corp%' ) \"CMVP_Active_Table\" group by type 
Union 
SELECT 'Firmware' as type1,\"Clean_Module_Type\" as type , 
	count(firmware + sl1 +InclusiveDates ) as sl1, 
	count(firmware + sl2 + InclusiveDates ) as sl2, 
	count(firmware + sl3 + InclusiveDates) as sl3, 
	count(firmware + sl4 + InclusiveDates) as sl4 
	FROM ( SELECT \"Clean_Module_Type\", 
		  CASE WHEN (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDates, 
		  CASE WHEN \"Clean_Module_Type\" like 'Firmware' THEN 1 END firmware, 
		  CASE WHEN \"SL\" =1 THEN 1 END sl1, 
		  CASE WHEN \"SL\" =2 THEN 1 END sl2, 
		  CASE WHEN \"SL\" =3 THEN 1 END sl3, 
		  CASE WHEN \"SL\" =4 THEN 1 END sl4 FROM \"CMVP_Active_Table\" where \"Vendor_Name\" not like '%Intel Corp%' ) \"CMVP_Active_Table\" group by type 
union 
SELECT 'Hybrid' as type1,\"Clean_Module_Type\" as type, 
	count(hybrid + sl1 +InclusiveDates ) as sl1, 
	count(hybrid + sl2 + InclusiveDates ) as sl2, 
	count(hybrid + sl3 + InclusiveDates) as sl3, 
	count(hybrid + sl4 + InclusiveDates) as sl4 
	FROM ( SELECT \"Clean_Module_Type\", 
		  CASE WHEN (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDates, 
		  CASE WHEN \"Clean_Module_Type\" like 'Hybrid' THEN 1 END hybrid, 
		  CASE WHEN \"SL\" =1 THEN 1 END sl1, 
		  CASE WHEN \"SL\" =2 THEN 1 END sl2, 
		  CASE WHEN \"SL\" =3 THEN 1 END sl3, 
		  CASE WHEN \"SL\" =4 THEN 1 END sl4 FROM \"CMVP_Active_Table\" where \"Vendor_Name\" not like '%Intel Corp%' ) \"CMVP_Active_Table\" group by type 
) 
SELECT type1,type, sl1, sl2,sl3, sl4, sl1+sl2+sl3+sl4 AS grandtotal FROM sums where type1=type
order by type1 


";

//Intel  only
$sql_Str2 = "
 
	WITH sums AS ( 
SELECT 'Hardware' as type1, \"Clean_Module_Type\" as type, 
	count(hardware + sl1 +InclusiveDates ) as sl1, 
	count(hardware + sl2 + InclusiveDates ) as sl2, 
	count(hardware + sl3 + InclusiveDates) as sl3,
	count(hardware + sl4 + InclusiveDates) as sl4 
	FROM ( SELECT \"Clean_Module_Type\", 
		  CASE WHEN (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDates, 
		  CASE WHEN \"Clean_Module_Type\" like 'Hardware' THEN 1 END hardware, 
		  CASE WHEN \"SL\" =1 THEN 1 END sl1, 
		  CASE WHEN \"SL\" =2 THEN 1 END sl2, 
		  CASE WHEN \"SL\" =3 THEN 1 END sl3, 
		  CASE WHEN \"SL\" =4 THEN 1 END sl4 FROM \"CMVP_Active_Table\" where \"Vendor_Name\" like '%Intel Corp%' ) \"CMVP_Active_Table\" group by type 
Union 
SELECT 'Software' as type1,\"Clean_Module_Type\" as type, 
	count(software + sl1 +InclusiveDates ) as sl1, 
	count(software + sl2 + InclusiveDates ) as sl2, 
	count(software + sl3 + InclusiveDates) as sl3, 
	count(software + sl4 + InclusiveDates) as sl4 
	FROM ( SELECT \"Clean_Module_Type\", 
		  CASE WHEN (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDates, 
		  CASE WHEN \"Clean_Module_Type\" like 'Software' THEN 1 END software, 
		  CASE WHEN \"SL\" =1 THEN 1 END sl1, 
		  CASE WHEN \"SL\" =2 THEN 1 END sl2, 
		  CASE WHEN \"SL\" =3 THEN 1 END sl3, 
		  CASE WHEN \"SL\" =4 THEN 1 END sl4 FROM \"CMVP_Active_Table\" where \"Vendor_Name\" like '%Intel Corp%' ) \"CMVP_Active_Table\" group by type 
Union 
SELECT 'Firmware' as type1,\"Clean_Module_Type\" as type , 
	count(firmware + sl1 +InclusiveDates ) as sl1, 
	count(firmware + sl2 + InclusiveDates ) as sl2, 
	count(firmware + sl3 + InclusiveDates) as sl3, 
	count(firmware + sl4 + InclusiveDates) as sl4 
	FROM ( SELECT \"Clean_Module_Type\", 
		  CASE WHEN (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDates, 
		  CASE WHEN \"Clean_Module_Type\" like 'Firmware' THEN 1 END firmware, 
		  CASE WHEN \"SL\" =1 THEN 1 END sl1, 
		  CASE WHEN \"SL\" =2 THEN 1 END sl2, 
		  CASE WHEN \"SL\" =3 THEN 1 END sl3, 
		  CASE WHEN \"SL\" =4 THEN 1 END sl4 FROM \"CMVP_Active_Table\" where \"Vendor_Name\" like '%Intel Corp%' ) \"CMVP_Active_Table\" group by type 
union 
SELECT 'Hybrid' as type1,\"Clean_Module_Type\" as type, 
	count(hybrid + sl1 +InclusiveDates ) as sl1, 
	count(hybrid + sl2 + InclusiveDates ) as sl2, 
	count(hybrid + sl3 + InclusiveDates) as sl3, 
	count(hybrid + sl4 + InclusiveDates) as sl4 
	FROM ( SELECT \"Clean_Module_Type\", 
		  CASE WHEN (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) BETWEEN '".$startDate."' AND '".$endDate."' THEN 1 END InclusiveDates, 
		  CASE WHEN \"Clean_Module_Type\" like 'Hybrid' THEN 1 END hybrid, 
		  CASE WHEN \"SL\" =1 THEN 1 END sl1, 
		  CASE WHEN \"SL\" =2 THEN 1 END sl2, 
		  CASE WHEN \"SL\" =3 THEN 1 END sl3, 
		  CASE WHEN \"SL\" =4 THEN 1 END sl4 FROM \"CMVP_Active_Table\" where \"Vendor_Name\" like '%Intel Corp%' ) \"CMVP_Active_Table\" group by type 
) 
SELECT type1,type, sl1, sl2,sl3, sl4, sl1+sl2+sl3+sl4 AS grandtotal FROM sums where type1=type
order by type1 

";
//echo "Alpha SQL= " . $sql_Str2 ;



$datan = array();  //grandtotal

$data0n = array(); //non Intel
$data0a = array(); //Intel

$data1n = array();
$data1a = array();

$data2n= array();
$data2a = array();

$data3n = array();
$data3a = array();

$labelsn=array();

//------non Intel
$result = pg_query($conn,$sql_Str1);
$arr = pg_fetch_all($result);
//print_r($arr);

if($arr==null)
	$num_mod=0;
else
	$num_mod=sizeof($arr);

if($num_mod>0)
{
	

	foreach($arr as $row){
		$labelsn[]=$row['type'];
	}

	foreach($arr as $row){
		$datan[]=$row['grandtotal'];
	}

	foreach($arr as $row){
		$data0n[]=$row['sl1'];
	}
	foreach($arr as $row){
		$data1n[]=$row['sl2'];
	}
	foreach($arr as $row){
		$data2n[]=$row['sl3'];
	}
	foreach($arr as $row){
		$data3n[]=$row['sl4'];
	}
} //num_mod >0

//------Intel only
$result = pg_query($conn,$sql_Str2);
$arr = pg_fetch_all($result);
//print_r($arr);
if($arr==null)
	$num_mod=0;
else
	$num_mod=sizeof($arr);

if($num_mod>0)
{
	foreach($arr as $row){
		$labelsa[]=$row['type'];
	}

	foreach($arr as $row){
		$dataa[]=$row['grandtotal'];
	}


	foreach($arr as $row){
		$data0a[]=$row['sl1'];
	}
	foreach($arr as $row){
		$data1a[]=$row['sl2'];
	}
	foreach($arr as $row){
		$data2a[]=$row['sl3'];
	}
	foreach($arr as $row){
		$data3a[]=$row['sl4'];
	}
} //num_mod > 0


//===================  configure and draw the chart ========================

# Create a XYChart object of size 600 x 360 pixels
#$c = new XYChart(600, 360);
//rf 3/22 $c = new XYChart(1600, 1360);

# Create a XYChart object of size 600 x 800 pixels. Set background color to brushed silver, with a 2
# pixel 3D border. Use rounded corners of 20 pixels radius.
//$zoom=1;
$width=$zoom*800;
$height=$zoom*600;

//$c = new XYChart($width,$height, brushedSilverColor(), Transparent, 2);
$c = new XYChart($width, $height, light_blue, black, 1);
$c->setRoundedFrame();


//-------------
//-------------
# Add a title to the chart using 15pt Times Bold Italic font. The text is white (ffffff) on a blue
# (0000cc) background, with glass effect.
$title = $c->addTitle("CMVP Validated Modules by Type Indicator", "timesbi.ttf", 15, 0xffffff);
$title->setBackground(0x0000cc, 0x000000, glassEffect(ReducedGlare));



//----------
# Set the plotarea at (60, 40) and of size 500 x 280 pixels. Use a vertical gradient color from
# light blue (eeeeff) to deep blue (0000cc) as background. Set border and grid lines to white
# (ffffff).
//start x, start y, width, height,   background color, alt background color,edge color, horiz grid color, vert grid color
//rf 3/22 $c->setPlotArea(180, 50, 1500, 840,  gray1,                             -1,        -1);



//start          x,   y, w,   h,   background color, alt background color,edge color, horiz grid color, vert grid color
$c->setPlotArea(80, 100, $width-200, $height-160, white, -1, Transparent, 0x000000);
$c->setRoundedFrame(0xffffff, 20);
//-----------------------------------------------
//Draw some buttons

$buttonX=$width-100; //700;
$buttonY=$height - 500; //100;





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

//gray1 on
//battleship_gray off

$button1 = $c->addText($buttonX, $buttonY, "Status","arialbd.ttf", 10); //draw button
$button1->setSize(80, 30);
$button1->setBackground(gray1,-1,2);
$button1->setAlignment (5);
$coor_button1 = $button1->getImageCoor();

$button2 = $c->addText($buttonX, $buttonY+50, "Mod Type","arialbd.ttf", 10); //draw button
$button2->setSize(80, 30);
$button2->setBackground(battleship_gray,-1,-2);
$button2->setAlignment (5);
$coor_button2 = $button2->getImageCoor();


$button3 = $c->addText($buttonX, $buttonY+100, "MIP","arialbd.ttf", 10); //draw button
$button3->setSize(80, 30);
$button3->setBackground(gray1,-1,2);
$button3->setAlignment (5);
$coor_button3 = $button3->getImageCoor();





///------ Trend Label



$button4 = $c->addText($buttonX, $buttonY+150, "Trend","arialbd.ttf", 10); //draw button
$button4->setSize(80, 30);
$button4->setBackground(gray1,-1,2);
$button4->setAlignment (5);
$coor_button4 = $button4->getImageCoor();

$button5 = $c->addText($buttonX, $buttonY+200, "Forecast","arialbd.ttf", 10); //draw button
$button5->setSize(80, 30);
$button5->setBackground(gray1,-1,2);
$button5->setAlignment (5);
$coor_button5 = $button5->getImageCoor();



//------------------
# Swap the x and y axes to create a horizontal bar chart
$c->swapXY();

//------------------
# Add a legend box at (480, 20) using vertical layout and 12pt Arial font. Set background and border
# to transparent and key icon border to the same as the fill color.
//                   x,  y,  vertical?, font       , font size
//$b = $c->addLegend(80, 50,      false, "arialbd.ttf", 12);
//$b->setBackground(Transparent, Transparent);
//$b->setKeyBorder(SameAsMainColor);

# draw legend by hand
$legendX=190;
$legendY=50;

$cover = $c->addText($legendX, $legendY, "","", 12); //draw box
$cover->setSize(20, 20);
$cover->setBackground(light_blue2);
$cover = $c->addText($legendX+20, $legendY, "SL1","arialbd.ttf", 12);
$legendX=$legendX + 75;

$cover = $c->addText($legendX, $legendY, "","", 12);  //draw box
$cover->setSize(20, 20);
$cover->setBackground(red);
$cover = $c->addText($legendX+20, $legendY, "SL2","arialbd.ttf", 12);
$legendX=$legendX+75;

$cover = $c->addText($legendX, $legendY, "","", 12);  //draw box
$cover->setSize(20, 20);
$cover->setBackground(green);
$cover = $c->addText($legendX+20, $legendY, "SL3","arialbd.ttf", 12); 

$legendX=$legendX+75;

$cover = $c->addText($legendX, $legendY, "","", 12);  //draw box
$cover->setSize(20, 20);
$cover->setBackground(yellow);
$cover = $c->addText($legendX+20, $legendY, "SL4","arialbd.ttf", 12);

//--------------------
# Add a stacked bar layer
$layer = $c->addBarLayer2(Stack,5);



//$layer2 = $c->addBarLayer2(Stack);
//$layer2->setBarWidth(20 );
//-----------------------
# Add the three data sets to the bar layer
$layer->addDataGroup("others");
$layer->addDataSet($data0n, light_blue2, "SL1");
$layer->addDataSet($data1n, red, "SL2");
$layer->addDataSet($data2n, green, "SL3");
$layer->addDataSet($data3n, yellow, "SL4");

$layer->addDataGroup("Intel only ");  //
$layer->addDataSet($data0a, light_blue2, "SL1");
$layer->addDataSet($data1a, red, "SL2");
$layer->addDataSet($data2a, green, "SL3");
$layer->addDataSet($data3a, yellow, "SL4");

# Set the sub-bar gap to 0, so there is no gap between stacked bars with a group
$layer->setBarGap(0.2, .05);

# Set the bar border to transparent
//$layer->setBorderColor(Transparent);
$layer->setBorderColor(Transparent, softLighting(Top));

# Enable labelling for the entire bar and use 12pt Arial font
$layer->setAggregateLabelStyle("arialbd.ttf", 12);

# Set the aggregate label format
$layer->setAggregateLabelFormat("{dataGroupName}: {value} ");

# Enable labelling for the bar segments and use 12pt Arial font with center alignment
$textBoxObj = $layer->setDataLabelStyle("arialbd.ttf", 10);
$textBoxObj->setAlignment(Center);

# Set x axis labels using the given labels
$c->xAxis->setLabels($labelsn);

# Draw the ticks between label positions (instead of at label positions)
$c->xAxis->setTickOffset(.5); //.5
 
# When auto-scaling, use tick spacing of 40 pixels as a guideline
$c->yAxis->setTickDensity(40);

# Add a title to the y axis with 12pt Times Bold Italic font
$c->yAxis->setTitle("Number of Certificates", "timesbi.ttf", 12);

# Set axis label style to 8pt Arial Bold
$c->xAxis->setLabelStyle("arialbd.ttf", 12);
$c->yAxis->setLabelStyle("arialbd.ttf", 8);

# Set axis line width to 2 pixels
$c->xAxis->setWidth(2);
$c->yAxis->setWidth(2);

# Create the image and save it in a temporary location
$chart1URL = $c->makeSession("chart1");

# Create an image map for the chart
$imageMap = $c->getHTMLImageMap("cmvp_show_details_active_by_module_type_pareto.php", "{default}&startDate=".$startDate."&endDate=".$endDate, "title='{xLabel}: {value|0} certificates'");
?>


<!--//=======================================================-->
<!--//HTML code. Title, get date info, etc.-->

<body style="margin:5px 0px 0px 5px">
<!--<div style="font-size:18pt; font-family:verdana; font-weight:bold">
    CMVP Active By Module Type Indicator
</div>-->


<table> <!-- date buttons -->

	<form action="<?= $self; ?>" method="POST"> 
	
   	<tr>    <td align="right"> Start Date <input type="date" name="startDate" value="<?= $startDate;?>">   
   		
   	<td rowspan="2"> <td colspan="2"> CST Lab Health Indicator</td></td>
   			
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
		<!--	<td style="width:100px" >

			</td>
		-->
			<td>
				
				<script>
					AendDate=  y + '-' + m + '-' + d ;  //today's date 
					
					AstartDate= y-1+ '-' + m +'-' + d; //12 months earlier
					 Aurl="<?= $URL_str; ?>";

				</script>
   				<?php
   				if($in_TopButtons==1)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
   				 onclick="window.location.href='http:'+ Aurl+ '/ChartDirector/CMVP_MIP_Indicator/cmvp_active_by_module_type_pareto.php?in_TopButtons=1&startDate='+ AstartDate+ '&endDate='+ AendDate;"> Last 12 Months  
   				
   				</button> 
   				

   			</td>
   			<td> 
				<script>
					BendDate=  y-1 + '-12-31' ;  //Dec 31st of the current year
					
					BstartDate= y-1 + '-01' +'-01'; //Jan 1st of last year 
					Burl="<?= $URL_str; ?>";

				</script>

   			 	<?php
   				if($in_TopButtons==2)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>


   				 onclick="window.location.href='http:'+ Burl+ '/ChartDirector/CMVP_MIP_Indicator/cmvp_active_by_module_type_pareto.php?in_TopButtons=2&startDate='+BstartDate+ '&endDate='+BendDate;"> Last Year  

   				
   				</button>  
   			</td> 
			<td>
				
				<script>
					CendDate=  y + '-' + m + '-' + d ;  //today's date 
					
					CstartDate= y + '-01' +'-01'; //january 1st of the current year
					 C_url="<?= $URL_str; ?>";

				</script>
				<?php
   				if($in_TopButtons==3)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
   				
   				 onclick="window.location.href='http:'+ C_url+ '/ChartDirector/CMVP_MIP_Indicator/cmvp_active_by_module_type_pareto.php?in_TopButtons=3&startDate='+ CstartDate+ '&endDate='+ CendDate;"> This Year  
   				</button> 
   			
   			</td>
   			
   			

   			<td>
				<script>
					
					DendDate=  y + '-' +  m + '-' + d;  //today
					 
					DstartDate=1995 + '-01-01'  ;  //birth of the CMVP program
					 Durl="<?= $URL_str; ?>";

				</script>
				<?php
   				if($in_TopButtons==4)
   					echo "<button  style=\"background-color: gray;\" type=\"button\" ";
   				else
   					echo "<button  style=\"background-color: silver;\" type=\"button\" ";
   				?>
				
   				  onclick="window.location.href='http:'+ Durl+ '/ChartDirector/CMVP_MIP_Indicator/cmvp_active_by_module_type_pareto.php?in_TopButtons=4&startDate='+ DstartDate+ '&endDate=' + DendDate ;"> All Time  
   				</button> 
   			</td>
</tr>
   
 </table> <!-- date buttons -->


   
<hr style="border:solid 1px #000080" />


<table>
	<tr><td style="width:100px">
		</td>
		<td>
	<img src="getchart.php?<?php echo $chart1URL?>" border="0" usemap="#map1">
	</td>
	</tr>
</table>

<map name="map1">
<?php echo $imageMap?>



<area <?php echo $coor_button1.  " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_active_by_status_pareto.php?zoom=".$zoom."&in_TopButtons=". $in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
    " title='Status Pareto' />"; ?>
<area <?php echo $coor_button2. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_active_by_module_type_pareto.php?zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Module Type Pareto' />"; ?>
<area <?php echo $coor_button3. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_pareto.php?zoom=".$zoom."&in_TopButtons=".($in_TopButtons * -1)."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Module In Process' />"?>
<area <?php echo $coor_button4. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_historic_stackedbar.php?zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='MIP Historic Trend' />"?>
<area <?php echo $coor_button5. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_mip_forecast_stackedbar.php?zoom=".$zoom."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='MIP Forecast (Linear Regression Model) ' />"?>
<area <?php echo $coor_zoomIn. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_active_by_module_type_pareto.php?zoom=".($zoom + .25)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom In' />"?>
<area <?php echo $coor_zoomOut. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_active_by_module_type_pareto.php?zoom=".($zoom - .25)."&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom Out) ' />"?>
<area <?php echo $coor_zoomClear. " href='http:".$URL_str."/ChartDirector/CMVP_MIP_Indicator/cmvp_active_by_module_type_pareto.php?zoom=1&in_TopButtons=".$in_TopButtons."&startDate=".$startDate."&endDate=".$endDate."'".
   " title='Zoom Clear) ' />"?>



</map>
</body>
</html>
