
<!-- atsec CMVP indicators v1 -->

<?php

require_once("../lib/phpchartdir.php");

define        ("red",0x00FF0000);
define      ("green",0x0000FF00);
define       ("blue",0x000000FF);
define ("light_blue",0x0099ccff);
define       ("grey2",0x00eeeeff);
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
  
 $startDate = isset($_REQUEST["startDate"]) ? date('Y-m-d',strtotime($_REQUEST["startDate"])) : date('Y-m-d', strtotime($today1));
 $endDate = isset($_REQUEST["endDate"]) ? date('Y-m-d',strtotime($_REQUEST["endDate"])) : date('Y-m-d', strtotime($today2));

 
//get my button settings
/* $ComingFrom=isset($REQUEST['ComingFrom']) ? $REQUEST['ComingFrom'] : 'Status';
 $buttonToggle=isset($_REQUEST['buttonToggle']) ? $_REQUEST['buttonToggle'] : -2;

//toggle the button pushed in/ pulled out  each time.
 // pushed in -> -2 while  pulled out -> +2
if($buttonToggle==-2)
	$buttonToggle=2;
else
	$buttonToggle==2;
*/
  
 //echo "startDate=".$startDate." ";
 //echo "endDate=".$endDate;


$data0 = array();   //Revoked
$data1 = array();   //Historic
$data2 = array();   //Became Active
$data3 = array();   //Already Active
$labels= array();	// Lab Names




//===============================================
#connect to postgreSQL database and get my chart data

$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$connStr = "host=postgres.aus.atsec  dbname=fantDatabase user=richard password==uwXg9Jo'5Ua connect_timeout=5 options='--application_name=$appName'";


$conn = pg_connect($connStr);

$inclusiveDate=" and ((TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) >= '".$startDate."' AND (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) <= '".$endDate."' )";
//$exclusvieDate=" and ((TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) < '".$startDate."' OR (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) > '".$endDate."') ";
$exclusiveDate=" and ((TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) < '".$startDate."' ) ";

$sql_Str = " 
	;WITH sums AS ( 
	SELECT
	  \"Clean_Lab_Name\" as lab,
	  count(revoked+InclusiveDates ) as revoked,
	  count(historic + InclusiveDates) as historic,
	  count(active + InclusiveDates) as became_active,
	  count(active + ExclusiveDates) as already_active
	  
	FROM (
	  SELECT
		\"Clean_Lab_Name\",
	    CASE WHEN  (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY'))  BETWEEN '".$startDate."' AND '".$endDate."' THEN 1  END InclusiveDates,
		case WHEN  (TO_DATE(right(\"Validation_Date\",10),'MM/DD/YYYY')) <'".$startDate."' then 1  end ExclusiveDates,
	    CASE WHEN \"Status\" like 'Revoked'          THEN 1  END revoked,
	    CASE WHEN \"Status\" like 'Historical'         THEN 1  END historic,
	    CASE WHEN \"Status\" like 'Active'           THEN 1  END active
	  FROM \"CMVP_Active_Table\"
	) \"CMVP_Active_Table\" 	group by \"Clean_Lab_Name\" 
	) 
	SELECT lab,(case when revoked is null then 0 else revoked end) , 
	(case when historic is null then 0 else historic end) , 
	(case when became_active is null then 0 else became_active end), 
	(case when already_active is null then 0 else already_active end), 
	(case when revoked is null then 0 else revoked end) 
	+ (case when historic is null then 0 else historic end) 
	+ (case when became_active is null then 0 else became_active end) 
	+ (case when already_active is null then 0 else already_active end) 
	AS grandtotal FROM sums 
where (case when revoked is null then 0 else revoked end) 
	+ (case when historic is null then 0 else historic end) 
	+ (case when became_active is null then 0 else became_active end) 
	+ (case when already_active is null then 0 else already_active end) >=0

	order by grandtotal 

 
";
//echo "Bravo SQL= " . $sql_Str ;


$result = pg_query($conn,$sql_Str);

$arr = pg_fetch_all($result);
//print_r($arr);

$data = array();
$labels=array();

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




//===================  configure and draw the chart ========================




# Create a XYChart object of size 1600 x 1000 pixels. Set background color to brushed silver, with a 2
# pixel 3D border. Use rounded corners of 20 pixels radius.

$width=800;//1000;//1600;
$height=1000;

//$c = new XYChart($width,$height, brushedSilverColor(), Transparent, 2);

# Create an XYChart object of size 600 x 360 pixels, with a light blue (EEEEFF) background, black
# border, 1 pxiel 3D border effect and rounded corners
$c = new XYChart($width, $height, 0xeeeeff, 0x000000, 1);
$c->setRoundedFrame();

//-----------------------------------------------
//Draw some buttons

$buttonX=700;
$buttonY=100;

//gray1 on
//battleship_gray off

$button1 = $c->addText($buttonX, $buttonY, "Status","arialbd.ttf", 10); //draw button
$button1->setSize(80, 30);
$button1->setBackground(battleship_gray,-1,-2);
$button1->setAlignment (5);
$coor_button1 = $button1->getImageCoor();

$button2 = $c->addText($buttonX, $buttonY+50, "Mod Type","arialbd.ttf", 10); //draw button
$button2->setSize(80, 30);
$button2->setBackground(gray1,-1,2);
$button2->setAlignment (5);
$coor_button2 = $button2->getImageCoor();


$button3 = $c->addText($buttonX, $buttonY+100, "MIP","arialbd.ttf", 10); //draw button
$button3->setSize(80, 30);
$button3->setBackground(gray1,-1,2);
$button3->setAlignment (5);
$coor_button3 = $button3->getImageCoor();


$button4 = $c->addText($buttonX, $buttonY+150, "Trend","arialbd.ttf", 10); //draw button
$button4->setSize(80, 30);
$button4->setBackground(gray1,-1,2);
$button4->setAlignment (5);
$coor_button4 = $button4->getImageCoor();

//-------------
# Add a title to the chart using 15pt Times Bold Italic font. The text is white (ffffff) on a blue
# (0000cc) background, with glass effect.
$title = $c->addTitle("CMVP Active By Status Indicator ", "timesbi.ttf", 15, 0xffffff);
$title->setBackground(0x0000cc, 0x000000, glassEffect(ReducedGlare));



# Set the plotarea corner (180, 100) and of size 1500x840  pixels. Use transparent border and black grid
# lines. Use rounded frame with radius of 20 pixels.
//          start x, start y, width, height,   background color, alt background color,edge color, horiz grid color, vert grid color
$c->setPlotArea(80, 100, $width-200, $height-160, white, -1, Transparent, 0x000000);
$c->setRoundedFrame(0xffffff, 20);

//------------------
# Swap the x and y axes to create a horizontal bar chart
$c->swapXY();

//------------------
# Add a legend box at (480, 20) using vertical layout and 12pt Arial font. Set background and border
# to transparent and key icon border to the same as the fill color.
//                   x,  y,  vertical?, font       , font size
$b = $c->addLegend(80, 50,      false, "arialbd.ttf", 12);
$b->setBackground(Transparent, Transparent);
$b->setKeyBorder(SameAsMainColor);

//--------------------
# Add a stacked bar layer
$layer = $c->addBarLayer2(Stack);



//-----------------------
# Add the four data sets to the bar layer
$layer->addDataSet($data0, yellow, "Revoked");
$layer->addDataSet($data1, red, "Historic");
$layer->addDataSet($data2, green, "Became Active");
$layer->addDataSet($data3, green_yellow, "Already Active");


# Set the bar border to transparent with softlighting
$layer->setBorderColor(Transparent, softLighting(Top));

# Enable labelling for the entire bar and use 12pt Arial font
$layer->setAggregateLabelStyle("arialbd.ttf", 12);

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

$imageMap = $c->getHTMLImageMap("CMVP_Show_Details_Active_By_Status_Pareto.php", "{default}&startDate=".$startDate."&endDate=".$endDate, "title='{xLabel}: {value|0} certificates'");

?>


<!--//=======================================================-->

<body style="margin:5px 0px 0px 5px">
<!--<div style="font-size:18pt; font-family:verdana; font-weight:bold">
    CMVP Active By Status Indicator (99.99% accurate. Data pulled directly from CMVP. But GIGO).
</div>-->



<table>
	<form action="<?= $self; ?>" method="POST"> 

   	<tr>    <td> Start Date <input type="date" name="startDate" value="<?= $startDate;?>"> </td> </tr>
	<tr>	<td> End Date   <input type="date" name="endDate" value="<?= $endDate;?>"> </td> </tr>
   	<tr> 	<td>  <button type='submit' >    Refresh  </button> 
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
					
					AstartDate= y-1+ '-' + m +'-' + d; //12 months earlier

				</script>
   				<button style="background-color: silver;" type="button"
   				 onclick="window.location.href='http://127.0.0.1:8080/CMVP_Active_By_Status_Pareto.php?startDate='+ AstartDate+ '&endDate='+ AendDate;"> Last 12 Months  
   				</button> 
   			
   			</td>
   			<td> 
				<script>
					BendDate=  y-1 + '-12-31' ;  //Dec 31st of the current year
					
					BstartDate= y-1 + '-01' +'-01'; //Jan 1st of last year 

				</script>

   			 <button style="background-color: silver;" type="button"
   				 onclick="window.location.href='http://127.0.0.1:8080/CMVP_Active_By_Status_Pareto.php?startDate='+BstartDate+ '&endDate='+BendDate;"> Last Year  
   				</button>  
   			</td> 
			<td>
				
				<script>
					CendDate=  y + '-' + m + '-' + d ;  //today's date 
					
					CstartDate= y + '-01' +'-01'; //january 1st of the current year

				</script>
   				<button style="background-color: silver;" type="button"
   				 onclick="window.location.href='http://127.0.0.1:8080/CMVP_Active_By_Status_Pareto.php?startDate='+ CstartDate+ '&endDate='+ CendDate;"> This Year  
   				</button> 
   			
   			</td>
   			
   			

   			<td>
				<script>
					
					DendDate=  y + '-' +  m + '-' + d;  //today
					 
					DstartDate=1995 + '-01-01'  ;  //birth of the CMVP program

				</script>
				<button style="background-color: silver;" type="button"
   				  onclick="window.location.href='http://127.0.0.1:8080/CMVP_Active_By_Status_Pareto.php?startDate='+ DstartDate+ '&endDate=' + DendDate ;"> All Time  
   				</button> 
   			</td>

   	</tr>
 </table>
   
<hr style="border:solid 1px #000080" />

<img src="getchart.php?<?php echo $chart1URL?>" border="0" usemap="#map1">
<map name="map1">
<?php echo $imageMap?>

<area <?php echo $coor_button1?>  href='http://127.0.0.1:8080/CMVP_Active_By_Status_Pareto.php'
    title='Status Pareto' />
<area <?php echo $coor_button2?> href='http://127.0.0.1:8080/CMVP_Active_By_Module_Type_Pareto.php'
    title='Module Type Pareto' />
<area <?php echo $coor_button3?> href='http://127.0.0.1:8080/CMVP_MIP_Pareto.php'
    title='Module In Process' />
<area <?php echo $coor_button4?> href='javascript:popMsg("the Trend button");'
    title='tool tip button4' />


</map>
</body>
</html>





