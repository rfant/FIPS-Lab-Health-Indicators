

<?php



//TBD:  Machine Learning Library Module
//require_once("../lib/phpchartdir.php");
require_once("phpchartdir.php");
/*require_once __DIR__ . '/vendor/autoload.php';

use Phpml\Classification\KNearestNeighbors;

$samples = [[1, 3], [1, 4], [2, 4], [3, 1], [4, 1], [4, 2]];
$labels = ['a', 'a', 'a', 'b', 'b', 'b'];

$classifier = new KNearestNeighbors();
$classifier->train($samples, $labels);

$classifier->predict([3, 2]);
// return 'b'
*/




//Model Forecast




//==============================================================================================
function brute_force ($Clean_Lab_Name,$months_to_look_back,$source_table,$MT_Security){
//this uses duration values provided by the CMVP.

global $forecast_Y_rp;   //predicted duration of being in "Review Pending"
global $forecast_Y_ir;   //predicted duration of being in "In Review"
global $forecast_Y_co;   //predicated duration of being in "Coordination"

//I'm really only interested in the RP value here.

		$forecast_Y_rp=270;
		$forecast_Y_ir=1;  //dummy value. really just want the RP time as predicted by CMVP.
		$forecast_Y_co=1;

		$global['forecast_Y_rp']=$forecast_Y_rp;
		$global['forecast_Y_ir']=$forecast_Y_ir;
		$global['forecast_Y_co']=$forecast_Y_co;
//echo "<br>alpha 2 brute<br>";

} //brute force
//============================================================================================

function k_nearest_neighbor ($Clean_Lab_Name,$months_to_look_back,$source_table){

/*
$sql_str_knn ="
(select 'rp' as type, \"Review_Pending_Start_Date\" ,abs(\"In_Review_Start_Date\" - \Review_Pending_Start_Date\") as num_days,
	(
	select count(*) from ".$source_table." where 
	\"Review_Pending_Start_Date\"::Date <= t1.\"Review_Pending_Start_Date\"::date AND
	((\"In_Review_Start_Date\" is NULL AND \"Coordination_Start_Date\" is null) OR (\"In_Review_Start_Date\"::date > t1.\"Review_Pending_Start_Date"::date))
	)  as num_mod_in_rp,
 0 as num_mod_in_ir
 from \"CMVP_MIP_Table\" as t1
			where \"In_Review_Start_Date\" is not null 	AND \"Review_Pending_Start_Date\" is not null
			and \"Review_Pending_Start_Date\" between (select current_date) - INTERVAL '".$months_to_look_back." months'  AND (select current_date) 
			order  by t1.\"Review_Pending_Start_Date\" desc )


";
echo "sql_knn=".$sql_str_knn."<br></br>";
*/
$k=4;


} // K nearest neighbor
//===============================================================================================
function use_average ($Clean_Lab_Name,$months_to_look_back,$source_table,$MT_Security){
global $forecast_Y_rp;   //predicted duration of being in "Review Pending"
global $forecast_Y_ir;   //predicted duration of being in "In Review"
global $forecast_Y_co;   //predicated duration of being in "Coordination"
		
		//$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		//$connStr = "host=localhost  dbname=postgres user=postgres password=postgres connect_timeout=5 options='--application_name=$appName'";

		//$conn = pg_connect($connStr);


		switch ($MT_Security){
			case "SW1":
				$MT_Security_Str=" \"Module_Type\" = 'Software' AND \"SL\"=1 AND ";
				break;
			case "SW2":
				$MT_Security_Str=" \"Module_Type\" = 'Software' AND \"SL\"=2 AND ";
				break;
			case "HW1":
				$MT_Security_Str=" \"Module_Type\" = 'Hardware' AND \"SL\"=1 AND ";
				break;
			case "HW2":
				$MT_Security_Str=" \"Module_Type\" = 'Hardware' AND \"SL\"=2 AND ";
				break;
			case "HW3":
				$MT_Security_Str=" \"Module_Type\" = 'Hardware' AND \"SL\"=3 AND ";
				break;
			case "HW4":
				$MT_Security_Str=" \"Module_Type\" = 'Hardware' AND \"SL\"=4 AND ";
				break;
			case "HY1":
				$MT_Security_Str=" \"Module_Type\" like '%Hybrid%' AND \"SL\"=1 AND ";
				break;
			case "HY2":
				$MT_Security_Str=" \"Module_Type\" like '%Hybrid%' AND \"SL\"=2 AND ";
				break;
			case "HY3":
				$MT_Security_Str=" \"Module_Type\" like '%Hybrid%' AND \"SL\"=3 AND ";
				break;
			case "HY4":
				$MT_Security_Str=" \"Module_Type\" like '%Hybrid%' AND \"SL\"=4 AND ";
				break;
			case "FW1":
				$MT_Security_Str=" \"Module_Type\" = 'Firmware' AND \"SL\"=1 AND ";
				break;
			case "FW2":
				$MT_Security_Str=" \"Module_Type\" = 'Firmware' AND \"SL\"=2 AND ";
				break;
			case "FW3":
				$MT_Security_Str=" \"Module_Type\" = 'Firmware' AND \"SL\"=3 AND ";
				break;
			case "FW4":
				$MT_Security_Str=" \"Module_Type\" = 'Firmware' AND \"SL\"=4 AND ";
				break;	
			default:
	   			 echo "ERROR 134a:********* MT_Security=".$MT_Security."<BR>";

		} //switch

	
	/*	$sql_str_average="
			(select 'rp' as type, AVG(abs(\"In_Review_Start_Date\" - \"Review_Pending_Start_Date\")) as avg from ".$source_table."
			where ".$MT_Security_Str." \"Clean_Lab_Name\" like '%".$Clean_Lab_Name."%' and \"In_Review_Start_Date\" is not null AND \"Review_Pending_Start_Date\" is not null
			and \"Review_Pending_Start_Date\" between (select current_date) - INTERVAL '".$months_to_look_back." months'  AND (select current_date) )
		UNION
			(select 'ir' as type, AVG(abs(\"Coordination_Start_Date\" - \"In_Review_Start_Date\")) as avg from ".$source_table."
			where  ".$MT_Security_Str." \"Clean_Lab_Name\" like '%".$Clean_Lab_Name."%' and \"Coordination_Start_Date\" is not null AND \"In_Review_Start_Date\" is not null
			and \"In_Review_Start_Date\" between (select current_date) - INTERVAL '".$months_to_look_back." months'  AND (select current_date))
		UNION
			(select 'co' as type, AVG(abs(\"Finalization_Start_Date\" - \"Coordination_Start_Date\")) as avg from ".$source_table."
			where  ".$MT_Security_Str." \"Clean_Lab_Name\" like '%".$Clean_Lab_Name."%' and \"Coordination_Start_Date\" is not null AND \"Finalization_Start_Date\" is not null
			and \"Coordination_Start_Date\" between (select current_date) - INTERVAL '".$months_to_look_back." months'  AND (select current_date))
		
		order by type desc

		";

*/
	$sql_str_average="
	select \"Clean_Lab_Name\" , count (*) as num_of_modules, TRUNC(avg(abs(\"In_Review_Start_Date\"::date -\"Review_Pending_Start_Date\"::date) )) as rp, TRUNC(avg(abs(\"In_Review_Start_Date\"::date - \"Coordination_Start_Date\"::date)) )as ir, TRUNC(avg(abs(\"Coordination_Start_Date\" - \"Finalization_Start_Date\")) )as co, TRUNC(avg(abs(\"Coordination_Start_Date\" - 
		\"Finalization_Start_Date\")) ) + TRUNC(avg(abs(\"In_Review_Start_Date\"::date - \"Coordination_Start_Date\"::date)) ) as time_in_mip from \"CMVP_MIP_Table\" where 
		".$MT_Security_Str." 	\"Review_Pending_Start_Date\" 
 		between (select current_date) - INTERVAL '24 months' AND (select current_date) 
  		and \"In_Review_Start_Date\" is not null AND \"Finalization_Start_Date\" is not null and \"Clean_Lab_Name\" like '%".$Clean_Lab_Name."%'  
 		group by \"Clean_Lab_Name\" having count(*) > 0 order by time_in_mip 
 

		";


		//echo "<br> November. average sql=<br>".$sql_str_average."<br>";

		$result = pg_query($conn,$sql_str_average);
		
		$arr = pg_fetch_all($result);
		//if(pg_result_status($result) != PGSQL_COMMAND_OK)
		//	echo "<br> ERROR 163: php pg result status=".pg_result_status($result).".<br> sql=<br>".$sql_str_average."<br>";

		
		if($arr==null)
			{   //echo "<br>Warning 472y:  SQL Query returned nothing. MT_Security=".$MT_Security." Sql=<br>".$sql_str_average."</br>";
			$num_mod=0;
			}
		else
			$num_mod=sizeof($arr);

		//foreach($arr as $row){
		//	$averages[]=$row['avg']; //
		//}
		foreach($arr as $row){
			$averages_rp[]=$row['rp']; //
		}
		foreach($arr as $row){
			$averages_ir[]=$row['ir']; //
		}
		foreach($arr as $row){
			$averages_co[]=$row['co']; //
		}



		$forecast_Y_rp=number_format($averages_rp[0],0);
		$forecast_Y_ir=number_format($averages_ir[0],0);
		$forecast_Y_co=number_format($averages_co[0],0);


		//$forecast_Y_rp=number_format($averages[0],0);
		//$forecast_Y_ir=number_format($averages[1],0);
		//$forecast_Y_co=number_format($averages[2],0);

		
		$global['forecast_Y_rp']=$forecast_Y_rp;
		$global['forecast_Y_ir']=$forecast_Y_ir;
		$global['forecast_Y_co']=$forecast_Y_co;


		//echo "Average model: rp=".$forecast_Y_rp." ir=".$forecast_Y_ir." co=".$forecast_Y_co."<br></br>";
} //use average
//================================================================================================
function use_linear_regression ($Clean_Lab_Name,$months_to_look_back,$source_table,$MT_Security) {

	
//global variables for forecasting
global $forecast_Y_rp;   //predicted duration of being in "Review Pending"
global $forecast_Y_ir;   //predicted duration of being in "In Review"
global $forecast_Y_co;   //predicated duration of being in "Coordination"

		$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$connStr = "host=localhost  dbname=postgres user=postgres password=postgres connect_timeout=5 options='--application_name=$appName'";


		$conn = pg_connect($connStr);

		//echo " alpha 7<br>";


		$sql_Str_Trend_RP="
		select \"TID\",
		\"Review_Pending_Start_Date\" as date,\"In_Review_Start_Date\", 
		EXTRACT (EPOCH from (\"Review_Pending_Start_Date\"::timestamp - '0001-01-01'::timestamp)) as rp, 
		EXTRACT (EPOCH from (\"In_Review_Start_Date\"::timestamp - '0001-01-01'::timestamp)) as ir, 
		(case when rpdays is not null then rpdays else (select current_date)::date - \"Review_Pending_Start_Date\"::date end) as rpdays , 
		(case when irdays is not null then irdays else (select current_date)::date - \"In_Review_Start_Date\"::date end) as irdays 
		from (select \"TID\", \"Review_Pending_Start_Date\",\"In_Review_Start_Date\"::date - \"Review_Pending_Start_Date\"::date as rpDays, \"In_Review_Start_Date\",\"Coordination_Start_Date\"::date - \"In_Review_Start_Date\"::date as irDays, \"Coordination_Start_Date\",\"Finalization_Start_Date\"::date-\"Coordination_Start_Date\"::date as coDays, \"Finalization_Start_Date\", \"Finalization_Start_Date\"::date - \"Review_Pending_Start_Date\"::date as fidays, \"Status2\" from ".$source_table." 
			  where \"Clean_Lab_Name\" like '%%".$Clean_Lab_Name."%%' and \"Review_Pending_Start_Date\" between  (select CURRENT_DATE) - INTERVAL '".$months_to_look_back." months' and (select current_date)  
			   and  (\"Status2\" like '%Promoted%' OR \"Status2\" like '%Reappear%' OR \"Status2\" is null) 
			  and (		  \"In_Review_Start_Date\" is not null 		  )

			) as t1 order by \"Review_Pending_Start_Date\" 
		";

		$sql_Str_Trend_IR="
		select \"TID\",
		\"Review_Pending_Start_Date\" as date,\"In_Review_Start_Date\", 
		EXTRACT (EPOCH from (\"Review_Pending_Start_Date\"::timestamp - '0001-01-01'::timestamp)) as rp, 
		EXTRACT (EPOCH from (\"In_Review_Start_Date\"::timestamp - '0001-01-01'::timestamp)) as ir,
		EXTRACT (EPOCH from (\"Coordination_Start_Date\"::timestamp - '0001-01-01'::timestamp)) as co,  
		(case when rpdays is not null then rpdays else (select current_date)::date - \"Review_Pending_Start_Date\"::date end) as rpdays , 
		(case when irdays is not null then irdays else (select current_date)::date - \"In_Review_Start_Date\"::date end) as irdays 
		from (select \"TID\", \"Review_Pending_Start_Date\",\"In_Review_Start_Date\"::date - \"Review_Pending_Start_Date\"::date as rpDays, \"In_Review_Start_Date\",\"Coordination_Start_Date\"::date - \"In_Review_Start_Date\"::date as irDays, \"Coordination_Start_Date\",\"Finalization_Start_Date\"::date-\"Coordination_Start_Date\"::date as coDays, \"Finalization_Start_Date\", \"Finalization_Start_Date\"::date - \"Review_Pending_Start_Date\"::date as fidays, \"Status2\" from ".$source_table." 
			  
			  where \"Clean_Lab_Name\" like '%%".$Clean_Lab_Name."%%' and \"Review_Pending_Start_Date\" between  (select CURRENT_DATE) - INTERVAL '".$months_to_look_back." months' and (select current_date) 
			  and (\"Status2\" like '%Promoted%' OR \"Status2\" like '%Reappear%' OR \"Status2\" is null) 
			  and (		  \"In_Review_Start_Date\" is not null 		and \"Coordination_Start_Date\" is not null  ) 
			) as t1 order by \"In_Review_Start_Date\" 
		";

		$sql_Str_Trend_CO="
		select \"TID\", 
		\"Review_Pending_Start_Date\" as date,\"In_Review_Start_Date\", \"Coordination_Start_Date\",
		EXTRACT (EPOCH from (\"Review_Pending_Start_Date\"::timestamp - '0001-01-01'::timestamp)) as rp, 
		EXTRACT (EPOCH from (\"In_Review_Start_Date\"::timestamp - '0001-01-01'::timestamp)) as ir, 
		EXTRACT (EPOCH from (\"Coordination_Start_Date\"::timestamp - '0001-01-01'::timestamp)) as co, 
		(case when rpdays is not null then rpdays else (select current_date)::date - \"Review_Pending_Start_Date\"::date end) as rpdays , 
		(case when irdays is not null then irdays else (select current_date)::date - \"In_Review_Start_Date\"::date end) as irdays, 
		(case when codays is not null then codays else (select current_date)::date - \"Coordination_Start_Date\"::date end) as codays 

		from (
		select \"TID\", \"Review_Pending_Start_Date\",\"In_Review_Start_Date\"::date - \"Review_Pending_Start_Date\"::date as rpDays, 
			\"In_Review_Start_Date\",\"Coordination_Start_Date\"::date - \"In_Review_Start_Date\"::date as irDays, 
			\"Coordination_Start_Date\",\"Finalization_Start_Date\"::date-\"Coordination_Start_Date\"::date as coDays, 
			\"Finalization_Start_Date\", \"Finalization_Start_Date\"::date - \"Review_Pending_Start_Date\"::date as fidays, 
			\"Status2\" from ".$source_table." 
			where \"Clean_Lab_Name\" like '%%".$Clean_Lab_Name."%%' and \"Review_Pending_Start_Date\" between (select CURRENT_DATE) - INTERVAL '".$months_to_look_back." months' and (select current_date) 
			and (\"Status2\" like '%Promoted%' OR \"Status2\" like '%Reappear%' OR \"Status2\" is null) 
			and ( \"In_Review_Start_Date\" is not null and \"Coordination_Start_Date\" is not null and \"Finalization_Start_Date\" is not null ) ) as t1 order by \"In_Review_Start_Date\" 
			
			
		";


		//----- this is just used to calcualte the HISTORIC Trend line. It is not actually plotted on the chart
		$result = pg_query($conn,$sql_Str_Trend_RP);
		$arr = pg_fetch_all($result);
		if($arr==null)
			{   //echo "ERROR 472a:  SQL Query returned nothing. Lab=".$Clean_Lab_Name."  MT_Security=".$MT_Security." str=<br>".$sql_Str_Trend_RP."</br>";
			$num_mod=0;
			}
		else
			$num_mod=sizeof($arr);

		foreach($arr as $row){
			$dataX_rp_trend[]=$row['rp']; //HISTORIC TREND ONLY:rp start date converted to seconds since 0001:01-01. Need this to calculate y=mx+b
		}
		foreach($arr as $row) {
			$dataY_rp_trend[]=$row['rpdays'];  //number of days historically spent in rp
		}
		foreach($arr as $row) {
			$labels_trend[]=$row['date']; //review pending  historical start date (yyy-mm-dd) used for x-axis
		}

		//----- this is just used to calcualte the HISTORIC Trend line. It is not actually plotted on the chart
		$result = pg_query($conn,$sql_Str_Trend_IR);
		$arr = pg_fetch_all($result);
		if($arr==null)
			{   //echo "ERROR 472z:  SQL Query returned nothing<br></br>";
			$num_mod=0;
			return;
			}
		else
			$num_mod=sizeof($arr);

		foreach($arr as $row){
			$dataX_ir_trend[]=$row['ir']; //"In Review" historical start date converted to number of seconds since 0001-01-01
		}
		foreach($arr as $row) {
			$dataY_ir_trend[]=$row['irdays']; //number of  days historically spent in ir
		}

		//----- this is just used to calcualte the HISTORIC Trend line. It is not actually plotted on the chart
		$result = pg_query($conn,$sql_Str_Trend_CO);
		$arr = pg_fetch_all($result);
		if($arr==null)
			{   //echo "ERROR 472c:  SQL Query returned nothing<br></br>";
				$num_mod=0;
				return;
			}
		else
			$num_mod=sizeof($arr);


		foreach($arr as $row){
			$dataX_co_trend[]=$row['co'];//coordination Historical start date converted to number of seconds since 0001-01-01
		}
		foreach($arr as $row) {
			$dataY_co_trend[]=$row['codays']; //number of days spent  historically in co
		}

		// draw the chart

		# Create a XYChart object of size width x height pixels. Set the background to light blue # with a black border (0x0)

		$width=800;
		$height=600;
		$c = new XYChart($width, $height, 0, 0, 1);

		$trendLayer = $c->addTrendLayer($dataY_rp_trend, 0);  //Historical RP End (or IR Start): historical number of days in RP
		$m_slope_rp=$trendLayer->getSlope();
		$b_intercept_rp=$trendLayer->getIntercept();
		$x_date_rp=sizeof($dataX_rp_trend);
		$forecast_Y_rp= number_format($m_slope_rp * $x_date_rp + $b_intercept_rp,0);
		if($forecast_Y_rp<1) 
			$forecast_Y_rp=1; 

		$trendLayer = $c->addTrendLayer($dataY_ir_trend, 0);  //Historical IR End (or CO Start): historical number of days in IR
		$m_slope_ir=$trendLayer->getSlope();
		$b_intercept_ir=$trendLayer->getIntercept();
		$x_date_ir=sizeof($dataX_ir_trend);
		$forecast_Y_ir=number_format(( $m_slope_ir * $x_date_ir + $b_intercept_ir),0) *1;  //multiply by this value for testing. KLUDGE.
		if($forecast_Y_ir<1)
			$forecast_Y_ir=1;

		$trendLayer = $c->addTrendLayer($dataY_co_trend, 0);  //Historical CO End (or FI Start): historical number of days in CO
		$m_slope_co=$trendLayer->getSlope();
		$b_intercept_co=$trendLayer->getIntercept();
		$x_date_co=sizeof($dataX_co_trend);
		$forecast_Y_co=number_format(( $m_slope_co * $x_date_co + $b_intercept_co),0); 
		if($forecast_Y_co<1)
			$forecast_Y_co=1;

		$global['forecast_Y_rp']=$forecast_Y_rp;
		$global['forecast_Y_ir']=$forecast_Y_ir;
		$global['forecast_Y_co']=$forecast_Y_co;

		//echo "Linear Regression Model: rp=".$forecast_Y_rp." ir=".$forecast_Y_ir." co=".$forecast_Y_co."<br></br>";

} // use linear regression

//----------------------------------------------------------------------------------------------------
// calculate confidence in prediction for a single model
// 
function get_confidence($Clean_Lab_Name,$months_to_look_back, $source_table,$model,$plus_minus_days,$forecast_rp,$forecast_ir,$forecast_co,$MT_Security){

//Here, I will take the predicted value for RP,IR and CO and see how close it matches up with historic
//data from $source_table (CMVP_MIP_Table)  .
//algorithm:
// 1) take the first value (e.g. RP forecast) and compare that value against every historic module for which we have data.
// 2) if the delta between the RP forecast and each historic RP is <=  $plus_minus_days, then the model works so increment good_rp by 1
//    if the delta > $plus_minus_days then the model failed so increment bad_rp
// 3) goto 2) until all historic RP data is complete.
// 4) confidence rate for this RP model = (# good_rp) / (#good_rp + #bad_rp)
// 5) return the RP confidence value in a global variable 
// 6) repeat 1-5 above with IR and CO.


global $confidence_rp;
global $confidence_ir;
global $confidence_co;
			//calculate trend first. One each for: RP, IR, CO
$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$connStr = "host=localhost  dbname=postgres user=postgres password=postgres connect_timeout=5 options='--application_name=$appName'";

$conn = pg_connect($connStr);


switch ($MT_Security){
			case "SW1":
				$MT_Security_Str=" \"Module_Type\" = 'Software' AND \"SL\"=1 AND ";
				break;
			case "SW2":
				$MT_Security_Str=" \"Module_Type\" = 'Software' AND \"SL\"=2 AND ";
				break;
			case "HW1":
				$MT_Security_Str=" \"Module_Type\" = 'Hardware' AND \"SL\"=1 AND ";
				break;
			case "HW2":
				$MT_Security_Str=" \"Module_Type\" = 'Hardware' AND \"SL\"=2 AND ";
				break;
			case "HW3":
				$MT_Security_Str=" \"Module_Type\" = 'Hardware' AND \"SL\"=3 AND ";
				break;
			case "HW4":
				$MT_Security_Str=" \"Module_Type\" = 'Hardware' AND \"SL\"=4 AND ";
				break;
			case "HY1":
				$MT_Security_Str=" \"Module_Type\" like '%Hybrid%' AND \"SL\"=1 AND ";
				break;
			case "HY2":
				$MT_Security_Str=" \"Module_Type\" like '%Hybrid%' AND \"SL\"=2 AND ";
				break;
			case "HY3":
				$MT_Security_Str=" \"Module_Type\" like '%Hybrid%' AND \"SL\"=3 AND ";
				break;
			case "HY4":
				$MT_Security_Str=" \"Module_Type\" like '%Hybrid%' AND \"SL\"=4 AND ";
				break;
			case "FW1":
				$MT_Security_Str=" \"Module_Type\" = 'Firmware' AND \"SL\"=1 AND ";
				break;
			case "FW2":
				$MT_Security_Str=" \"Module_Type\" = 'Firmware' AND \"SL\"=2 AND ";
				break;
			case "FW3":
				$MT_Security_Str=" \"Module_Type\" = 'Firmware' AND \"SL\"=3 AND ";
				break;
			case "FW4":
				$MT_Security_Str=" \"Module_Type\" = 'Firmware' AND \"SL\"=4 AND ";
				break;	
			default:
	   			 echo "ERROR 134b:********* MT_Security=".$MT_Security."<BR>";

		} //switch


$sql_substring_plus_minus="select 	case when abs(predict_rp - actual_rp) <= ".$plus_minus_days." then 1 else 0 end as good_rp,
							case when abs(predict_rp - actual_rp)  > ".$plus_minus_days." then 1 else 0 end as bad_rp,
							case when abs(predict_ir - actual_ir) <= ".$plus_minus_days." then 1 else 0 end as good_ir,
							case when abs(predict_ir - actual_ir)  > ".$plus_minus_days." then 1 else 0 end as bad_ir,
							case when abs(predict_co - actual_co) <= ".$plus_minus_days." then 1 else 0 end as good_co,
							case when abs(predict_co - actual_co)  > ".$plus_minus_days." then 1 else 0 end as bad_co  ";

$sql_substring_less_than="select 	case when predict_rp >= actual_rp  then 1 else 0 end as good_rp,
							case when predict_rp <  actual_rp  then 1 else 0 end as bad_rp,
							case when predict_ir >= actual_ir  then 1 else 0 end as good_ir,
							case when predict_ir <  actual_ir  then 1 else 0 end as bad_ir,
							case when predict_co >= actual_co  then 1 else 0 end as good_co,
							case when predict_co <  actual_co  then 1 else 0 end as bad_co ";

			//now measure how succesful the predictions were

			$sql_Confidence = "
				SELECT 
					(sum(good_rp)::real * 100/(sum(good_rp) + sum(bad_rp))) as rp_conf,
					(sum(good_ir)::real * 100/(sum(good_ir) + sum(bad_ir))) as ir_conf,
					(sum(good_co)::real * 100/(sum(good_co) + sum(bad_co))) as co_conf, 
					(sum(good_rp)+sum(bad_rp)) as total_rp,
					(sum(good_ir)+sum(bad_ir)) as total_ir,
					(sum(good_co)+sum(bad_co)) as total_co
				FROM ( 
					
					".$sql_substring_plus_minus."
					from
					( 
					select \"TID\",  
					(case when \"In_Review_Start_Date\" is null then -1 else (\"In_Review_Start_Date\"::date - \"Review_Pending_Start_Date\"::date )end) as actual_rp,
					".$forecast_rp." as predict_rp, 

					(case when \"Coordination_Start_Date\" is null then -1 else (\"Coordination_Start_Date\"::date - \"In_Review_Start_Date\"::date )end)as actual_ir,
					".$forecast_ir." predict_ir,

					(case when \"Finalization_Start_Date\" is null then -1 else (\"Finalization_Start_Date\"::date - \"Coordination_Start_Date\"::date ) end) as actual_co,
					".$forecast_co." as predict_co 
					
					from ".$source_table." where ".$MT_Security_Str." \"Clean_Lab_Name\" like '%".$Clean_Lab_Name."%' and \"Review_Pending_Start_Date\" 
					between (select CURRENT_DATE) - INTERVAL '22 months' and (select current_date) 
					and (  (\"Review_Pending_Start_Date\" is not null AND \"In_Review_Start_Date\" is not null)
						 OR (\"In_Review_Start_Date\" is not null AND \"Coordination_Start_Date\" is not null)
						 OR (\"Coordination_Start_Date\" is not null AND \"Finalization_Start_Date\" is not null)
						 )
					) as subquery1
			) as subquery2
			";
			//echo "str_confidence1=".$sql_Confidence;

			$result = pg_query($conn,$sql_Confidence);
			$arr = pg_fetch_all($result);
			if($arr==null)
				{   //echo "ERROR 472d:  SQL Query returned nothing. Lab=".$Clean_Lab_Name."  MT_Security=".$MT_Security."<br>SQL=</br>".$str_confidence;
					$num_mod=0;
				}
			else
			{	$num_mod=sizeof($arr);
				
			}
			foreach($arr as $row) 
				$rp_conf[]=$row['rp_conf'];  
			foreach($arr as $row)
				$ir_conf[]=$row['ir_conf'];
			foreach($arr as $row)
				$co_conf[]=$row['co_conf'];
			foreach($arr as $row)
				$total_rp[]=$row['total_rp'];
			foreach($arr as $row)
				$total_ir[]=$row['total_ir'];
			foreach($arr as $row)
				$total_co[]=$row['total_co'];

			$confidence_rp=number_format($rp_conf[0],0);
			$confidence_ir=number_format($ir_conf[0],0);
			$confidence_co=number_format($co_conf[0],0);
			


			$global['confidence_rp']=$confidence_rp;
			$global['confidence_ir']=$confidence_ir;
			$global['confidence_co']=$confidence_co;

			  // print out the confidence for: RP, IR and CO.
		/*	echo "RP=".$forecast_rp."(".number_format($rp_conf[0],0)."%) IR=".$forecast_ir." (".number_format($ir_conf[0],0)."%) CO=".$forecast_co." (".number_format($co_conf[0],0)."%)  within +/- ".$plus_minus_days." days.  Historical look back:".$months_to_look_back.".  Source=".$source_table.". model=".$model.". Totrp=".$total_rp[0].", Totir=".$total_ir[0].", Totco=".$total_co[0]."<br></br>"; */


			

} //get_confidence
			 

//====================================================================================================


function calculate_confidence($Clean_Lab_Name,$MT_Security) {

//calculate confidence for all models and durations using historic data from public CMPV tables.

//I use global variables here to pass info between this high level function and the 
// individual model-functions to determine which model has the highest confidence.
//There was some tricky memory leak stuff going on when I tried to pass as parameters, so I switched to global.

global $forecast_Y_rp;  //predicted duration of being in "Review Pending" for individual model
global $forecast_Y_ir;  //predicted duration of being in "In Review" for individual model
global $forecast_Y_co;  //predicated duration of being in "Coordination" for individual model
global $confidence_rp;  //confidence value for RP for individual model
global $confidence_ir;	//confidence value for IR for individual model
global $confidence_co;	//confidence value for CO for individual model

//echo "<br>alpha 1<br>MT_Security=".$MT_Security."<br>";

			
		//===============================================
		// Get all my input parameters that are passed into this app
		//

		 $self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '#';
		 $now = date("Y-m-d");
		 
		 $today1 = isset($_POST['today1']) ? $_POST['today1'] : '1995-01-01' ; //Ealiest CMVP validation date
		 $today2 = isset($_POST['today2']) ? $_POST['today2'] : (new DateTime)->format('Y-m-d');
		  
		 $startDate = isset($_REQUEST["startDate"]) ? date('Y-m-d',strtotime($_REQUEST["startDate"])) : date('Y-m-d', strtotime($today1));
		 $endDate = isset($_REQUEST["endDate"]) ? date('Y-m-d',strtotime($_REQUEST["endDate"])) : date('Y-m-d', strtotime($today2));

		 $in_TopButtons=isset($_REQUEST["in_TopButtons"]) ? $_REQUEST["in_TopButtons"] : 4;

		 $zoom=isset($_REQUEST["zoom"]) ? $_REQUEST["zoom"] : 1;

		 $months_to_look_back=isset($_REQUEST["months_to_look_back"]) ? $_REQUEST["months_to_look_back"] : 24;

		 //echo "hotel startDate=".$startDate." ";
		 //echo "endDate=".$endDate;

		

		//===============================================================================
		#connect to postgreSQL database and get my chart data

		$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$connStr = "host=localhost  dbname=postgres user=postgres password=postgres connect_timeout=5 options='--application_name=$appName'";


		$conn = pg_connect($connStr);



		//=======================================================================================
		
		
		//Model forecasting
		//these are the max confidence for ALL the models for each individual Lab. If a higher confidence is achieved by
		//a particular model, then that confidence because the new max_confidence value.
		$rp_conf_max=0;
		$ir_conf_max=0;
		$co_conf_max=0;
		$rp_forecast=0;
		$ir_forecast=0;
		$co_forecast=0;


		//*******************************
		//*** KEY VALUE HERE  **********
		$plus_minus_days=145;  //$j; //what is my tolerance (in days) for errors (over/under estimating)?
		//*******************************
		

		//just some dummy names placeholders for initialization
		$rp_model="abc";
		$ir_model="def";
		$co_model="jhi";

		//use the public CMVP MIP table here. Lots of modules in the dataset, but its history only goes back to July 28, 2020.
		$source_table="\"CMVP_MIP_Table\"";

		//for($month_to_look_back=6;$month_to_look_back<=24;$month_to_look_back++) //months to look back
		//{

	
			//----------------------  BRUTE FORCE MODEL ----------------------------------------------------

		/*	//Don't forecast any numbers in this model. Instead, use the queue wait time data that the CMVP gives us at the Lab's Manager meeting.
			brute_force($Clean_Lab_Name,$month_to_look_back,$source_table,$MT_Security);
			$model="BruteForce";   
			get_confidence($Clean_Lab_Name,$month_to_look_back,$source_table,$model,$plus_minus_days,$forecast_Y_rp,$forecast_Y_ir,$forecast_Y_co,$MT_Security);		
		
			if($confidence_rp >$rp_conf_max ){
				$rp_conf_max=$confidence_rp;
				$rp_forecast=$forecast_Y_rp;
				$rp_model=$model;
				$rp_table=$source_table;
				$rp_months_to_look_back=$month_to_look_back;
				//echo "alpha: RP=".$forecast_Y_rp."(".$rp_conf_max."% ) +/- ".$plus_minus_days." Model:".$model.",  Table=".$source_table."<br></br>";
			}
			if($confidence_ir >$ir_conf_max ){
				$ir_conf_max=$confidence_ir;
				$ir_forecast=$forecast_Y_ir;
				$ir_model=$model;
				$ir_table=$source_table;
				$ir_months_to_look_back=$month_to_look_back;
				//echo "alpha: IR=".$forecast_Y_ir."(".$ir_conf_max."% ) +/- ".$plus_minus_days." Model:".$model.",  Table=".$source_table."<br></br>";
			}	

			if($confidence_co >$co_conf_max ){
				$co_conf_max=$confidence_co;
				$co_forecast=$forecast_Y_co;
				$co_model=$model;
				$co_table=$source_table;
				$co_months_to_look_back=$month_to_look_back;
				//echo "alpha: CO=".$forecast_Y_co."(".$co_conf_max."%) +/- ".$plus_minus_days."   Model:".$model.",  Table=".$source_table."<br></br>";		
			}
			*/
			
			//----------------------  SIMPLE AVERAGE MODEL (NOT WEIGHTED) ----------------------------------------------------
			
			//Use a simple average of previous months to forecast future.
			$month_to_look_back=24;
			//$rp_months_to_look_back=24;
			//$ir_months_to_look_back=24;
			//$co_months_to_look_back=24;
			
			use_average($Clean_Lab_Name,$month_to_look_back,$source_table,$MT_Security);   
			$model="SimpleAverage";

			

		
			
			/*get_confidence($Clean_Lab_Name,$month_to_look_back,$source_table,$model,$plus_minus_days,$forecast_Y_rp,$forecast_Y_ir,$forecast_Y_co,$MT_Security);		
			if($confidence_rp >$rp_conf_max ){
				$rp_conf_max=$confidence_rp;
				$rp_forecast=$forecast_Y_rp;
				$rp_model=$model;
				$rp_table=$source_table;
				$rp_months_to_look_back=$month_to_look_back;
				//echo "bravo:RP=".$forecast_Y_rp."(".$rp_conf_max."% ) +/- ".$plus_minus_days." Model:".$model.",  Table=".$source_table."<br></br>";
			}
			
			if($confidence_ir >$ir_conf_max ){
				$ir_conf_max=$confidence_ir;
				$ir_forecast=$forecast_Y_ir;
				$ir_model=$model;
				$ir_table=$source_table;
				$ir_months_to_look_back=$month_to_look_back;

				//echo "bravo:IR=".$forecast_Y_ir."(".$ir_conf_max."%) +/- ".$plus_minus_days."   Model:".$model.",  Table=".$source_table."<br></br>";	
			}

			if($confidence_co >$co_conf_max ){
				$co_conf_max=$confidence_co;
				$co_forecast=$forecast_Y_co;
				$co_model=$model;
				$co_table=$source_table;
				$co_months_to_look_back=$month_to_look_back;
				//echo "bravo:CO=".$forecast_Y_co."(".$co_conf_max."%) +/- ".$plus_minus_days."   Model:".$model.",  Table=".$source_table."<br></br>";		
			} */

			//----------------------  LINEAR REGRESSION MODEL ----------------------------------------------------
			
			//Use linear regression (aka Least Squares) to forecast a model.
		/*	use_linear_regression($Clean_Lab_Name,$month_to_look_back,$source_table,$MT_Security);
			$model="LinearRegression";
			get_confidence($Clean_Lab_Name,$month_to_look_back,$source_table,$model,$plus_minus_days,$forecast_Y_rp,$forecast_Y_ir,$forecast_Y_co,$MT_Security);		
			
			if($confidence_rp >$rp_conf_max ){
				$rp_conf_max=$confidence_rp;
				$rp_forecast=$forecast_Y_rp;
				$rp_model=$model;
				$rp_table=$source_table;
				$rp_months_to_look_back=$month_to_look_back;
				//echo "charlie:RP=".$forecast_Y_rp."(".$rp_conf_max."%) +/- ".$plus_minus_days."   Model:".$model.",  Table=".$source_table."<br></br>";
			}
			
			if($confidence_ir >$ir_conf_max ){
				$ir_conf_max=$confidence_ir;
				$ir_forecast=$forecast_Y_ir;
				$ir_model=$model;
				$ir_table=$source_table;
				$ir_months_to_look_back=$month_to_look_back;
				//echo "charlie:IR=".$forecast_Y_ir."(".$ir_conf_max."%) +/- ".$plus_minus_days."  Model:".$model.",  Table=".$source_table."<br></br>";	
			}

			if($confidence_co >$co_conf_max ){
				$co_conf_max=$confidence_co;
				$co_forecast=$forecast_Y_co;
				$co_model=$model;
				$co_table=$source_table;
				$co_months_to_look_back=$month_to_look_back;
				//echo "charlie:CO=".$forecast_Y_co."(".$co_conf_max."%) +/- ".$plus_minus_days."   Model:".$model.",  Table=".$source_table."<br></br>";		
			}
			*/
		//} //big for loop for months to look back

	


		$forecast_Y_rp=$rp_forecast + $plus_minus_days;
		$forecast_Y_ir=$ir_forecast + $plus_minus_days;
		$forecast_Y_co=$co_forecast + $plus_minus_days;


		$trend_str="RP=". $forecast_Y_rp." (".$rp_conf_max."%)     IR=".$forecast_Y_ir." (".$ir_conf_max."%)     CO=".$forecast_Y_co." //(".$co_conf_max."%) ";
		//echo "Final calculation: ".$trend_str."<br></br>";

		switch ($MT_Security){
			case "SW1":
				$Module_Type= "Software";
				$SL=1;
				break;
			case "SW2":
				$Module_Type= "Software";
				$SL=2;
				break;
			case "HW1":
				$Module_Type= "Hardware";
				$SL=1;
				break;
			case "HW2":
				$Module_Type= "Hardware";
				$SL=2;
				break;
			case "HW3":
				$Module_Type= "Hardware";
				$SL=3;
				break;
			case "HW4":
				$Module_Type= "Hardware";
				$SL=4;
				break;
			case "HY1":
				$Module_Type= "Hybrid";
				$SL=1;
				break;
			case "HY2":
				$Module_Type= "Hybrid";
				$SL=2;
				break;
			case "HY3":
				$Module_Type= "Hybrid";
				$SL=3;
				break;
			case "HY4":
				$Module_Type= "Hybrid";
				$SL=4;
				break;
			case "FW1":
				$Module_Type= "Firmware";
				$SL=1;
				break;
			case "FW2":
				$Module_Type= "Firmware";
				$SL=2;
				break;
			case "FW3":
				$Module_Type= "Firmware";
				$SL=3;
				break;
			case "FW4":
				$Module_Type= "Firmware";
				$SL=4;
				break;	
			default:
	   			 echo "ERROR 134d:********* MT_Security=".$MT_Security."<BR>";

		} //switch


		//add these calculated values to my SQL table for future references by the indicators.
		$sql_Str_Confidence="
		insert into \"Model_Forecast_Confidence\" (\"Date\",\"RP_Value\",\"RP_Confidence\",\"RP_Model\",\"RP_Table\",\"IR_Value\",\"IR_Confidence\",\"IR_Model\",\"IR_Table\",\"CO_Value\",\"CO_Confidence\",\"CO_Model\",\"CO_Table\",\"RP_months_to_look_back\",\"IR_months_to_look_back\",\"CO_months_to_look_back\",\"plus_minus_days\",\"Clean_Lab_Name\",\"MT_Security\",
		\"Module_Type\",\"SL\") 
		values('".$today2."',".$forecast_Y_rp.",".$rp_conf_max.",'".$rp_model."','".$rp_table."',".$forecast_Y_ir.",".$ir_conf_max.",'".$ir_model."','".$ir_table."',
		".$forecast_Y_co.",".$co_conf_max.",'".$co_model."','".$co_table."',".$rp_months_to_look_back.",".$ir_months_to_look_back.",".$co_months_to_look_back.",".$plus_minus_days.",'".$Clean_Lab_Name."','".$MT_Security."','".$Module_Type."',".$SL." ); 
		";

/*		$sql_Str_Confidence="
		insert into \"Model_Forecast_Confidence\" (\"Date\",\"RP_Value\",\"RP_Confidence\",\"RP_Model\",\"RP_Table\",\"IR_Value\",\"IR_Confidence\",\"IR_Model\",\"IR_Table\",\"CO_Value\",\"CO_Confidence\",\"CO_Model\",\"CO_Table\",\"RP_months_to_look_back\",\"IR_months_to_look_back\",\"CO_months_to_look_back\",\"plus_minus_days\",\"Clean_Lab_Name\",\"MT_Security\") 
		values('".$today2."',".$forecast_Y_rp.",".$rp_conf_max.",'".$rp_model."','".$rp_table."',".$forecast_Y_ir.",".$ir_conf_max.",'".$ir_model."','".$ir_table."',
		".$forecast_Y_co.",".$co_conf_max.",'".$co_model."','".$co_table."',".$rp_months_to_look_back.",".$ir_months_to_look_back.",".$co_months_to_look_back.",".$plus_minus_days.",'".$Clean_Lab_Name."','".$MT_Security."'); 
		";
*/
		//echo "lima confidence str=<br>".$sql_Str_Confidence."<br>";	

		$result = pg_query($conn,$sql_Str_Confidence);
		if(pg_result_status($result) != PGSQL_COMMAND_OK)
			echo "<br> ERROR 820: php pg result status=".pg_result_status($result).".<br>";


		//echo "refresh done";
		return;

} // generate confidence





?>  
<!----------------------------------------------------------------------------------------------------------------->
