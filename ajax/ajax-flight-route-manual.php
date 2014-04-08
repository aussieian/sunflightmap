<?php

// testing
// comment this out for production
//echo $_GET['callback'] . '([{"from_airport": "SYD","from_city": "Sydney","from_lat": -33.946111,"from_lon": 151.177222,"to_airport": "DXB","to_city": "Dubai","to_lat": 25.252778,"to_lon": 55.364444,"depart_time": "2013-06-14T16:05:00","depart_timezone": "10","depart_time_utc": "Fri, 14 Jun 2013 06:05:00 GMT","arrival_time_utc": "Fri, 14 Jun 2013 20:35:00 GMT","elapsed_time": 870,"distance_km": 12034,"error": ""},{"from_airport": "DXB","from_city": "Dubai","from_lat": 25.252778,"from_lon": 55.364444,"to_airport": "LHR","to_city": "London","to_lat": 51.4775,"to_lon": -0.461389,"depart_time": "2013-06-15T02:05:00","depart_timezone": "4","depart_time_utc": "Fri, 14 Jun 2013 22:05:00 GMT","arrival_time_utc": "Sat, 15 Jun 2013 05:35:00 GMT","elapsed_time": 450,"distance_km": 5474,"error": ""}]);';
//die();

// NOTE: NO LONGER WORKS BECAUSE NO MORE OAG FEED :(
// BUT LEAVING THIS CODE HERE IN CASE YOU WANT TO SEE HOW IT'S DONE

include("../lib/global.php");

// Ajax Flight Route
// Parameters: Carrier Code, Service Number, Request Date
// References: http://ondemandtestharness.oag.com/CBWSTestHarnessPublic/#flightLookupRequest

// Get URL parameters
//////////////////////
$callback='flightmap';
if (array_key_exists("callback", $_GET)) {
	$callback=$_GET['callback'];
}

$origin='DXB';
if (array_key_exists("origin", $_GET)) {
	$origin=strtoupper($_GET['origin']);
}

$destination='LIS';
if (array_key_exists("destination", $_GET)) {
	$destination=strtoupper($_GET['destination']);
}

$duration='600'; // minutes
if (array_key_exists("duration", $_GET)) {
	$duration=$_GET['duration'];
}

$departure_datetime='2011-10-16 04:30 pm';
if (array_key_exists("departure_datetime", $_GET)) {
	$departure_datetime=$_GET['departure_datetime'];
}


$from_airport_openflights = getAirport($origin);
if ($from_airport_openflights == "") { 
	print($callback . "({'error':'Unable to lookup origin airport. Please use a valid airport code (ie: SYD)'});");
	die();
}
$from_city = $from_airport_openflights["City"];
$from_lat = $from_airport_openflights["Lat"];
$from_lon = $from_airport_openflights["Lon"];
$to_airport_openflights = getAirport($destination);
if ($to_airport_openflights == "") { 
	print($callback . "({'error':'Unable to lookup destination airport. Please use a valid airport code (ie: SYD)'});");
	die();
}
$to_city = $to_airport_openflights["City"];
$to_lat = $to_airport_openflights["Lat"];
$to_lon = $to_airport_openflights["Lon"];
$depart_timezone = $from_airport_openflights["Timezone"]; // $cfg["PHP_TIMEZONE_OFFSET"]
$depart_time_utc = date("D, d M Y H:i:00", strtotime($departure_datetime) - ($depart_timezone * 60 * 60)) . " GMT"; // calculate UTC time (todo: doesnt accomodate for daylight saving)
$distance_km = calcDistance($from_lat, $from_lon, $to_lat, $to_lon, "K");

$flight_data = array();
$flight_data["from_airport"] = $origin;
$flight_data["from_city"] = $from_city;
$flight_data["from_lat"] = floatval($from_lat);
$flight_data["from_lon"] = floatval($from_lon);
$flight_data["to_airport"] = $destination;
$flight_data["to_city"] = $to_city;
$flight_data["to_lat"] = floatval($to_lat);
$flight_data["to_lon"] = floatval($to_lon);
$flight_data["depart_time"] = $departure_datetime;
$flight_data["arrival_time"] = 0; // not implemented
$flight_data["depart_timezone"] = $depart_timezone;
$flight_data["depart_time_utc"] = $depart_time_utc;
//$jsonp_flights .= '"arrival_time_utc": "' . $arrival_time_utc . '",';
$flight_data["elapsed_time"] = intval($duration);
$flight_data["distance_km"] = intval($distance_km);
$flight_data["error"] = "";
$flight_segments[] = $flight_data;
$flight_routes = $flight_segments;


// run sfcalc 
for ($i = 0; $i < sizeof($flight_routes); $i++) {

	$flight_data = &$flight_routes[$i]; // get reference

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
	$cmd = escapeshellcmd($cmd); // escape input to be safe
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
$data["flight_segments"] = $flight_routes;
$data["cached"] = false;
/*$jsonp = $callback . "({'flightdata':[";
$jsonp .= implode(",", $jsonp_flights_arr); // make array of Flights
$jsonp .= "],'cached':'false'});";*/

// example:
// jsonp1319362367283({"from_airport": "MEL","from_city": "Melbourne","from_lat": -37.673333,"from_lon": 144.843333,"to_airport": "SIN","to_city": "Singapore","to_lat": 1.350189,"to_lon": 103.994433,"depart_time": "2011-10-23T12:00:00","depart_timezone": "10","depart_time_utc": "Sun, 23 Oct 2011 02:00:00 GMT","elapsed_time": 470,"error": ""});
//print($callback . '({"from_airport": "MEL","from_city": "Melbourne","from_lat": -37.673333,"from_lon": 144.843333,"to_airport": "SIN","to_city": "Singapore","to_lat": 1.350189,"to_lon": 103.994433,"depart_time": "2011-10-23T12:00:00","depart_timezone": "10","depart_time_utc": "Sun, 23 Oct 2011 02:00:00 GMT","elapsed_time": 470,"error": ""});');
print($callback . "(" . json_encode($data) . ");");


?>
