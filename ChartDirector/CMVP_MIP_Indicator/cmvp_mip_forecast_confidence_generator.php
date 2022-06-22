

<?php



//echo "Enter PROD=".$PROD."<BR>";

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

//============================================================================================


//===============================================================================================
function use_average ($Clean_Lab_Name,$MT_Security,$PROD,$ciphering,$iv_length,$decryption_key,$options,$decryption_iv){
			
global $forecast_Y_rp;   //predicted duration of being in "Review Pending"
global $forecast_Y_ir;   //predicted duration of being in "In Review"
global $forecast_Y_co;   //predicated duration of being in "Coordination"
		
		
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
			
		//echo "pgsql=intel prod";
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


//	echo "<BR>"."forecast_conf_gen:PROD=". $PROD."<BR>"." ConnStr= ".$connStr."<BR>";

//=====================================================

  		$conn = pg_connect($connStr);

  		if (pg_connection_Status($conn) === PGSQL_CONNECTION_OK) {
      		//echo 'LHI SQL Connection status ok';
  		} 
  		else {
      		echo 'ERROR 87: SQL LHI Connection status bad';
  		}    




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
		if(pg_result_status($result) != PGSQL_TUPLES_OK)
			echo "<br> ERROR 118a: php pg result status=".pg_result_status($result).".<br> sql=<br>".$sql_str_average."<br>";
		//else
		//	echo "<br> November okay 1.<br>";
//
		
		if($arr==null)
			{   //echo "<br>Warning 472y:  SQL Query returned nothing. MT_Security=".$MT_Security." Sql=<br>".$sql_str_average."</br>";
			$num_mod=0;
			}
		else
			$num_mod=sizeof($arr);

		
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


			
		$global['forecast_Y_rp']=$forecast_Y_rp;
		$global['forecast_Y_ir']=$forecast_Y_ir;
		$global['forecast_Y_co']=$forecast_Y_co;


		//echo "oscar: Clean_Lab_Name=".$Clean_Lab_Name." MT_Security=".$MT_Security."  Average model: rp=".$forecast_Y_rp." ir=".$forecast_Y_ir." co=".$forecast_Y_co."<br></br>";
} //use average
//================================================================================================
function use_linear_regression ($Clean_Lab_Name,$months_to_look_back,$source_table,$MT_Security,$PROD,$ciphering,$iv_length,$decryption_key,$options,$decryption_iv) {


} // use linear regression

//----------------------------------------------------------------------------------------------------
// calculate confidence in prediction for a single model
// 
function get_confidence($Clean_Lab_Name,$model,$plus_minus_days,$forecast_rp,$forecast_ir,$forecast_co,$MT_Security,$PROD,$ciphering,$iv_length,$decryption_key,$options,$decryption_iv){

//Here, I will take the predicted value for RP,IR and CO and see how close it matches up with actual historic
//data from  CMVP_MIP_Table  .
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

//===============================================
#connect to postgreSQL database and get my chart data

	$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	switch ($PROD) {
	    case 2:  //postgresql database on Ubuntu VM machine 
	     	$encryptedPW="xtw2D3obQa8=";
	  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
			$connStr = "host=localhost  dbname=postgres user=postgres password=".$decryptedPW." connect_timeout=5 options='--application_name=$appName'";
			//echo "pgsql=ubutun VM";
	        break;
	    case 1: //postgresql database on intel interanet production
			$encryptedPW="39ABDntQEJtweA==";
	  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
			$connStr = "host=postgres5320-lb-fm-in.dbaas.intel.com  dbname=lhi_prod2 user=lhi_prod2_so password=".$decryptedPW."  connect_timeout=5 options='--application_name=$appName'";
			//echo "pgsql=intel prod";
	        break;
	    case 0:   //postgresql database on intel intranet pre-production
	    	$encryptedPW="39ABDntQEJtweA==";
	  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
			$connStr = "host=postgres5596-lb-fm-in.dbaas.intel.com  dbname=lhi_pre_prod user=lhi_pre_prod_so password=".$decryptedPW." connect_timeout=5 options='--application_name=$appName'";
			//echo "pgsql=intel pre-prod";
	        break;
	    default:
	    	echo "ERROR: unknown PROD value";

		}


	//echo "PROD= $PROD"." ConnStr= ".$connStr;

//=====================================================


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

			if(pg_result_status($result) != PGSQL_TUPLES_OK)
				echo "<br> ERROR 118b php pg result status=".pg_result_status($result).".<br> sql=<br>".$sql_str_average."<br>";
			//else
			//	echo "<br> November 2 okay.<br>";

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
		

} //get_confidence
			 

//====================================================================================================


function calculate_confidence($Clean_Lab_Name,$MT_Security,$PROD,$ciphering,$iv_length,$decryption_key,$options,$decryption_iv) {

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

		// $months_to_look_back=isset($_REQUEST["months_to_look_back"]) ? $_REQUEST["months_to_look_back"] : 24;

		 //echo "hotel startDate=".$startDate." ";
		 //echo "endDate=".$endDate;

		

		
		
		//===============================================
		#connect to postgreSQL database and get my chart data

		$appName = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		switch ($PROD) {
		    case 2:  //postgresql database on Ubuntu VM machine 
		     	$encryptedPW="xtw2D3obQa8=";
		  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
				$connStr = "host=localhost  dbname=postgres user=postgres password=".$decryptedPW." connect_timeout=5 options='--application_name=$appName'";
				//echo "pgsql=ubutun VM";
		        break;
		    case 1: //postgresql database on intel interanet production
				$encryptedPW="39ABDntQEJtweA==";
		  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
				$connStr = "host=postgres5320-lb-fm-in.dbaas.intel.com  dbname=lhi_prod2 user=lhi_prod2_so password=".$decryptedPW."  connect_timeout=5 options='--application_name=$appName'";
			//echo "pgsql=intel prod";
		        break;
		    case 0:   //postgresql database on intel intranet pre-production
		    	$encryptedPW="39ABDntQEJtweA==";
		  		$decryptedPW=openssl_decrypt ($encryptedPW, $ciphering, $decryption_key, $options, $decryption_iv);
				$connStr = "host=postgres5596-lb-fm-in.dbaas.intel.com  dbname=lhi_pre_prod user=lhi_pre_prod_so password=".$decryptedPW." connect_timeout=5 options='--application_name=$appName'";
				//echo "pgsql=intel pre-prod";
		        break;
		    default:
		    	echo "ERROR: unknown PROD value";

			}


		//echo "PROD= $PROD"." ConnStr= ".$connStr;

//=====================================================


		$conn = pg_connect($connStr);

		if (pg_connection_Status($conn) === PGSQL_CONNECTION_OK) {
      		//echo 'LHI SQL Connection status ok';
  		} 
  		else {
      		echo "ERROR 87z: SQL LHI Connection status bad. connStr=".$connStr."<br>";
  		}   

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
		$plus_minus_days=10;  //$j; //what is my tolerance (in days) for errors (over/under estimating)?
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

		
		
			//----------------------  SIMPLE AVERAGE MODEL (NOT WEIGHTED) ----------------------------------------------------
			
			//Use a simple average of last 24 months to forecast future.
					
						
			use_average($Clean_Lab_Name,$MT_Security,$PROD,$ciphering,$iv_length,$decryption_key,$options,$decryption_iv);   
			//echo "hotel: rp=".$forecast_Y_rp." ir=".$forecast_Y_ir." co=".$forecast_Y_co."<br>";
			$model="SimpleAverage";

			
			//get_confidence($Clean_Lab_Name,$model,$plus_minus_days,$forecast_Y_rp,$forecast_Y_ir,$forecast_Y_co,$MT_Security,$PROD);		
		
			if($confidence_rp >$rp_conf_max ){
		//		$rp_conf_max=$confidence_rp;
		//		$rp_forecast=$forecast_Y_rp;
		//		$rp_model=$model;
		//		echo "bravo:RP=".$forecast_Y_rp."(".$rp_conf_max."% ) +/- ".$plus_minus_days." Model:".$model.",  Table=".$source_table."<br></br>";
			}
			
			if($confidence_ir >$ir_conf_max ){
			//	$ir_conf_max=$confidence_ir;
			//	$ir_forecast=$forecast_Y_ir;
			//	$ir_model=$model;
				echo "bravo:IR=".$forecast_Y_ir."(".$ir_conf_max."%) +/- ".$plus_minus_days."   Model:".$model.",  Table=".$source_table."<br></br>";	
			}

			if($confidence_co >$co_conf_max ){
			//	$co_conf_max=$confidence_co;
			//	$co_forecast=$forecast_Y_co;
			//	$co_model=$model;
				echo "bravo:CO=".$forecast_Y_co."(".$co_conf_max."%) +/- ".$plus_minus_days."   Model:".$model.",  Table=".$source_table."<br></br>";		
			} 

			//----------------------  LINEAR REGRESSION MODEL ----------------------------------------------------
			
			

	


		$forecast_Y_rp=$forecast_Y_rp + $plus_minus_days;
		$forecast_Y_ir=$forecast_Y_ir + $plus_minus_days;
		$forecast_Y_co=$forecast_Y_co + $plus_minus_days;


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
	   			 echo "ERROR 791d:********* MT_Security=".$MT_Security."<BR>";

		} //switch


		//add these calculated values to my SQL table for future references by the indicators.
		//But only if they are non-zero.
		if($forecast_Y_rp!=$plus_minus_days)  //this means $forecast_Y_rp is zero, so don't insert a zero forecast into table
		{
			$sql_Str_Confidence="
			insert into \"Model_Forecast_Confidence\" (\"Date\",\"RP_Value\",\"RP_Confidence\",\"RP_Model\",\"IR_Value\",\"IR_Confidence\",\"IR_Model\",\"CO_Value\",\"CO_Confidence\",\"CO_Model\",
			\"plus_minus_days\",\"Clean_Lab_Name\",\"MT_Security\", \"Module_Type\",\"SL\") 
			values('".$today2."',".$forecast_Y_rp.",".$rp_conf_max.",'".$rp_model."',".$forecast_Y_ir.",".$ir_conf_max.",'".$ir_model."',
			".$forecast_Y_co.",".$co_conf_max.",'".$co_model."',".$plus_minus_days.",'".$Clean_Lab_Name."','".$MT_Security."','".$Module_Type."',".$SL." ); 
			";


			//echo "lima confidence str=<br>".$sql_Str_Confidence."<br>";	

			
			$result = pg_query($conn,$sql_Str_Confidence);
			$k=pg_result_Status($result);

			//if(pg_result_status($result) != PGSQL_COMMAND_OK)
			if($k != PGSQL_COMMAND_OK)
				echo "<br> ERROR 820: php pg result status=".$k.".<br>"."connStr=".$connStr."<br>"."sql1=".$sql_Str_Confidence."<br>";

			//echo "refresh done";
		}
		return;



} // calculate confidence





?>  
<!----------------------------------------------------------------------------------------------------------------->
