<?php

// testing
// comment this out for production
//echo $_GET['callback'] . '([{"from_airport": "SYD","from_city": "Sydney","from_lat": -33.946111,"from_lon": 151.177222,"to_airport": "DXB","to_city": "Dubai","to_lat": 25.252778,"to_lon": 55.364444,"depart_time": "2013-06-14T16:05:00","depart_timezone": "10","depart_time_utc": "Fri, 14 Jun 2013 06:05:00 GMT","arrival_time_utc": "Fri, 14 Jun 2013 20:35:00 GMT","elapsed_time": 870,"distance_km": 12034,"error": ""},{"from_airport": "DXB","from_city": "Dubai","from_lat": 25.252778,"from_lon": 55.364444,"to_airport": "LHR","to_city": "London","to_lat": 51.4775,"to_lon": -0.461389,"depart_time": "2013-06-15T02:05:00","depart_timezone": "4","depart_time_utc": "Fri, 14 Jun 2013 22:05:00 GMT","arrival_time_utc": "Sat, 15 Jun 2013 05:35:00 GMT","elapsed_time": 450,"distance_km": 5474,"error": ""}]);';
//die();

include("../lib/global.php");
include("../lib/php_fast_cache.php");

// Ajax Flight Route
// Parameters: Carrier Code, Service Number, Request Date
// References: http://ondemandtestharness.oag.com/CBWSTestHarnessPublic/#flightLookupRequest

// Get URL parameters
//////////////////////
$callback='flightmap';
if (array_key_exists("callback", $_GET)) {
	$callback=$_GET['callback'];
}

$carrier_code='JQ';
if (array_key_exists("carrier_code", $_GET)) {
	$carrier_code=$_GET['carrier_code'];
}

$service_number='7';
if (array_key_exists("service_number", $_GET)) {
	$service_number=$_GET['service_number'];
}

$request_date='2011-10-16';
if (array_key_exists("request_date", $_GET)) {
	$request_date=$_GET['request_date'];
}

$cache_key = "c" . $carrier_code . "_" . $service_number . "_" . $request_date;

// try to get from Cache first.
//phpFastCache::$path = "/PATH/FOR_PDO_FILES/";
phpFastCache::$storage = "auto";
$oag_data = phpFastCache::get($cache_key);

$cached = false;
if($oag_data != null) {
	$cached = true;
}

if(!$cached) {
	

	// Do OAG Lookup
	////////////////

	// Step 1
	// Fetch XML results via OAG test harness which gives us a REST
	// Hard-coded for now
	$username = $cfg['OAG_USERNAME'];
	$password = $cfg['OAG_PASSWORD'];
	$request_time = '12:00:00';

	//set POST variables
	$post_url = "http://ondemandtestharness.oag.com/CBWSTestHarnessPublic//FlightLookupRequestAction.do?";
	$fields = array(
	            'actionForm'=>'FlightLookupRequestForm',
	            'inputPrefix'=>'f_',
	            'f_username'=>urlencode($username),
	            'f_password'=>urlencode($password),
	            'f_carrierCode'=>urlencode($carrier_code),
	            'f_serviceNumber'=>urlencode($service_number),
	            'f_requestDate'=>urlencode($request_date),
	            'f_requestTime'=>urlencode($request_time)
	          );

	// url-ify the data for the POST
	$data = http_build_query($fields);

	// open connection
	$curl_conn = curl_init();

	// set the url, number of POST vars, POST data
	curl_setopt($curl_conn,CURLOPT_URL,$post_url);
	curl_setopt($curl_conn,CURLOPT_POST,count($fields));
	curl_setopt($curl_conn,CURLOPT_POSTFIELDS,$data);
	curl_setopt($curl_conn, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_conn, CURLOPT_HEADER, 0);

	// execute post
	$post_response = curl_exec($curl_conn);

	// close connection
	curl_close($curl_conn);

	$xml_data = simplexml_load_string($post_response);
	if ($post_response == "<RESPONSE></RESPONSE>") { 
		print($callback . "({'error':'Oops, no route found. Try a different date or carrier code'});");
		die();
	}
	// Step 2
	// pull out the fromairpot, toairport, flight depart time, elapsed time,
	// data for callback
	// this should return a jsonp object with flight route info from OAG..

	// Get callback url
	// also do this for carrier code, service number, request date
	$flight_segments = array();
	foreach($xml_data->Flight as $flight)
	{
		try {

			// ignore this segment as it does not fly on this day
			// note: assume this is always start or end of route)
			if (strval($flight->Dep["ElapsedTime"]) == "0") {	
				continue; 
			}

			$from_airport = strval($flight->Dep->Port["PortCode"]);
			$from_airport_openflights = getAirport($from_airport);
			$from_city = $from_airport_openflights["City"];
			$from_lat = $from_airport_openflights["Lat"];
			$from_lon = $from_airport_openflights["Lon"];
			$to_airport = strval($flight->Arr->Port["PortCode"]);
			$to_airport_openflights = getAirport($to_airport);
			$to_city = $to_airport_openflights["City"];
			$to_lat = $to_airport_openflights["Lat"];
			$to_lon = $to_airport_openflights["Lon"];
			$depart_time = strval($flight->Dep["DepTime"]); // local time of departure
			$arrival_time = strval($flight->Arr["ArrTime"]); // local time of arrival
			$depart_timezone = $from_airport_openflights["Timezone"]; // ($cfg["PHP_TIMEZONE_OFFSET"]-
			//$depart_time_utc = date("Y-m-d H:i", strtotime($depart_time) - ($depart_timezone * 60 * 60)); // calculate UTC time (todo: doesnt accomodate for daylight saving)
			$depart_time_utc = date("D, d M Y H:i:00", strtotime($depart_time) - ($depart_timezone * 60 * 60)) . " GMT"; // calculate UTC time (todo: doesnt accomodate for daylight saving)
			$elapsed_time = strval($flight->Dep["ElapsedTime"]);
			//$arrival_time_utc = date("D, d M Y H:i:00", strtotime($depart_time) + ($elapsed_time * 60) - ($depart_timezone * 60 * 60)) . " GMT"; // calculate UTC time (todo: doesnt accomodate for daylight saving)
			$distance_km = strval($flight->Dep["KM"]);
			$days_of_op = strval($flight["DaysOfOp"]);

			$flight_data = array();
			$flight_data["from_airport"] = $from_airport;
			$flight_data["from_city"] = $from_city;
			$flight_data["from_lat"] = floatval($from_lat);
			$flight_data["from_lon"] = floatval($from_lon);
			$flight_data["to_airport"] = $to_airport;
			$flight_data["to_city"] = $to_city;
			$flight_data["to_lat"] = floatval($to_lat);
			$flight_data["to_lon"] = floatval($to_lon);
			$flight_data["depart_time"] = $depart_time;
			$flight_data["arrival_time"] = $arrival_time;
			$flight_data["depart_timezone"] = $depart_timezone;
			$flight_data["depart_time_utc"] = $depart_time_utc;
			//$jsonp_flights .= '"arrival_time_utc": "' . $arrival_time_utc . '",';
			$flight_data["elapsed_time"] = intval($elapsed_time);
			$flight_data["distance_km"] = intval($distance_km);
			$flight_data["days_of_op"] = $days_of_op;
			$flight_data["error"] = "";

			

			$flight_segments[] = $flight_data;
			$oag_data = $flight_segments;

		} catch (Exception $e) {
			print($callback . "({'error':'Invalid flight info'});");
			die();
		}
	}

	// cache OAG data
	phpFastCache::set($cache_key,$oag_data,60 * 60 * 24); // cache for a day
}


// run sfcalc 
for ($i = 0; $i < sizeof($oag_data); $i++) {

	$flight_data = &$oag_data[$i]; // get reference

	$from_lat_lng = $flight_data["from_lat"] . "," . $flight_data["from_lon"];
	$to_lat_lng = $flight_data["to_lat"] . "," . $flight_data["to_lon"];
	$flight_mins = $flight_data["elapsed_time"];
	$departure_date = date("Y-m-d", strtotime($flight_data["depart_time"]));
	$departure_time = date("H:i:00", strtotime($flight_data["depart_time"]));
	$gmt_offset = $flight_data["depart_timezone"];

	// Usage:   sfcalc.py from_lat_lng to_lat_lng flight_mins departure_date departure_time gmt_offset
	// example: sfcalc.py -33.946,151.177 1.350,103.994 710 2013-06-15 09:05:00 10
	$cmd = $cfg["SFCALC_CMD"] . " " . $from_lat_lng . " " . $to_lat_lng . " " . $flight_mins . " " . $departure_date . " " . $departure_time . " " . $gmt_offset;
	//die($cmd);
	$sfcalc_results = exec($cmd);
	$sfcalc_data = json_decode($sfcalc_results);

	// merge sfcalc results into data
	$flight_data["debug_sfcalc_cmd"] = $cmd;
	$flight_data["flight_points"] = $sfcalc_data->flight_points;
	$flight_data["flight_stats"] = $sfcalc_data->flight_stats;
}


//$from_airport = "MEL";
//$from_city = "Melbourne";
//$from_lat = -37.673333;
//$from_lon = 144.843333;
//$to_airport = "SIN";
//$to_city = "Singapore";
//$to_lat = 1.350189;
//$to_lon = 103.994433;
//$depart_time = "2011-10-16T12:00:00";
//$elapsed_time = 470;

// make jsonp
$data = array();
$data["flight_segments"] = $oag_data;
$data["cached"] = $cached;
/*$jsonp = $callback . "({'flightdata':[";
$jsonp .= implode(",", $jsonp_flights_arr); // make array of Flights
$jsonp .= "],'cached':'false'});";*/

// example:
// jsonp1319362367283({"from_airport": "MEL","from_city": "Melbourne","from_lat": -37.673333,"from_lon": 144.843333,"to_airport": "SIN","to_city": "Singapore","to_lat": 1.350189,"to_lon": 103.994433,"depart_time": "2011-10-23T12:00:00","depart_timezone": "10","depart_time_utc": "Sun, 23 Oct 2011 02:00:00 GMT","elapsed_time": 470,"error": ""});
//print($callback . '({"from_airport": "MEL","from_city": "Melbourne","from_lat": -37.673333,"from_lon": 144.843333,"to_airport": "SIN","to_city": "Singapore","to_lat": 1.350189,"to_lon": 103.994433,"depart_time": "2011-10-23T12:00:00","depart_timezone": "10","depart_time_utc": "Sun, 23 Oct 2011 02:00:00 GMT","elapsed_time": 470,"error": ""});');
print($callback . "(" . json_encode($data) . ");");


?>