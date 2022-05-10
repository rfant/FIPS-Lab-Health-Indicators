<?php
require_once("../lib/phpchartdir.php");


$data0 = array();   //Revoked
$data1 = array();   //Historic
$data2 = array();   //Active
$labels= array();	// Lab Names


#connect to postgreSQL database and get my chart data

$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$connStr = "host=postgres.aus.atsec  dbname=fantDatabase user=richard password==uwXg9Jo'5Ua connect_timeout=5 options='--application_name=$appName'";
$conn = pg_connect($connStr);


//---- Get a table with counts for all three status grouped by Lab_Name for every certificate every issued.
$result = pg_query($conn, " 
	;WITH sums AS 
	( 
	select \"Clean_Lab_Name\" as CLN, 
	(select count(*) from \"CMVP_Active_Table\" as t3 where \"Status\" like '%Revoked%' and t1.\"Clean_Lab_Name\"=t3.\"Clean_Lab_Name\" group by \"Clean_Lab_Name\" order by \"Clean_Lab_Name\") as revoked,
	(select count(*) from \"CMVP_Active_Table\" as t4 where \"Status\" like '%Historic%' and t1.\"Clean_Lab_Name\"=t4.\"Clean_Lab_Name\" group by \"Clean_Lab_Name\" order by \"Clean_Lab_Name\") as historic,
	(select count(*) from \"CMVP_Active_Table\" as t5 where \"Status\" like '%Active%' and t1.\"Clean_Lab_Name\"=t5.\"Clean_Lab_Name\" group by \"Clean_Lab_Name\" order by \"Clean_Lab_Name\") as active
	from \"CMVP_Active_Table\" as t1 group by CLN order by CLN
	)
	SELECT 
	   CLN,(case when revoked is null then 0 else revoked end)  , 
	   (case when historic is null then 0 else historic end) ,
	   (case when active is null then 0 else active end),
	   (case when revoked is null then 0 else revoked end) + (case when historic is null then 0 else historic end) + (case when active is null then 0 else active end)    
	   AS GrandTotal 
	FROM 
	   sums order by GrandTotal 
");
$arr = pg_fetch_all($result);

foreach($arr as $row){
	$labels[]=$row['cln'];
}

foreach($arr as $row){
	$data0[]=$row['revoked'];
}
foreach($arr as $row){
	$data1[]=$row['historic'];
}
foreach($arr as $row){
	$data2[]=$row['active'];
}




# The data for the bar chart
//$data0 = array(100, 115, 165, 107, 67);
//$data1 = array(85, 106, 129, 161, 123);
//$data2 = array(67, 87, 86, 167, 157);

# The labels for the bar chart
//$labels = array("Mon", "Tue", "Wed", "Thu", "Fri");

# Create a XYChart object of size 600 x 360 pixels
//$c = new XYChart(600, 360);
$c = new XYChart(1600, 1360);


# Set the plotarea at (70, 20) and of size 400 x 300 pixels, with transparent background and border
# and light grey (0xcccccc) horizontal grid lines
//$c->setPlotArea(70, 20, 400, 300, Transparent, -1, Transparent, 0xcccccc);
//$c->setPlotArea(70, 20, 400, 300, Transparent, -1, Transparent, 0xcccccc);
$c->setPlotArea(180, 120, 1500, 840, $c->linearGradientColor(60, 40, 60, 280, 0xeeeeff, 0x0000cc), -1,
    0xffffff, 0xffffff);


# Swap the x and y axes to create a horizontal bar chart
$c->swapXY();

//added from cmvp page 1
# Add a multi-color bar chart layer using the supplied data. Use soft lighting effect with light
# direction from top.
//$barLayerObj = $c->addBarLayer3($data);
//$barLayerObj->setBorderColor(Transparent, softLighting(Top));


# Add a legend box at (480, 20) using vertical layout and 12pt Arial font. Set background and border
# to transparent and key icon border to the same as the fill color.
$b = $c->addLegend(480, 20, true, "arial.ttf", 12);
$b->setBackground(Transparent, Transparent);
$b->setKeyBorder(SameAsMainColor);

# Set the x and y axis stems to transparent and the label font to 12pt Arial
$c->xAxis->setColors(Transparent);
$c->yAxis->setColors(Transparent);
$c->xAxis->setLabelStyle("arial.ttf", 12);
$c->yAxis->setLabelStyle("arial.ttf", 12);

# Add a stacked bar layer
$layer = $c->addBarLayer2(Stack);

# Add the three data sets to the bar layer
$layer->addDataSet($data0, 0xaaccee, "Revoked");
$layer->addDataSet($data1, 0xbbdd88, "Historic");
$layer->addDataSet($data2, 0xeeaa66, "Active");

# Set the bar border to transparent
//$layer->setBorderColor(Transparent);
$layer->setBorderColor(Transparent, softLighting(Top));

# Enable labelling for the entire bar and use 12pt Arial font
$layer->setAggregateLabelStyle("arial.ttf", 12);

# Enable labelling for the bar segments and use 12pt Arial font with center alignment
$textBoxObj = $layer->setDataLabelStyle("arial.ttf", 10);
$textBoxObj->setAlignment(Center);

# For a vertical stacked bar with positive data, the first data set is at the bottom. For the legend
# box, by default, the first entry at the top. We can reverse the legend order to make the legend
# box consistent with the stacked bar.
$layer->setLegendOrder(ReverseLegend);

# Set the labels on the x axis.
$c->xAxis->setLabels($labels);

# For the automatic y-axis labels, set the minimum spacing to 40 pixels.
$c->yAxis->setTickDensity(40);

# Add a title to the y axis using dark grey (0x555555) 14pt Arial Bold font
$c->yAxis->setTitle("Y-Axis Title Placeholder", "arialbd.ttf", 14, 0x555555);

# Output the chart
header("Content-type: image/png");
print($c->makeChart2(PNG));
?>
