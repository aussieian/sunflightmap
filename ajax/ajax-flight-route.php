<?php

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
if ($xml_data == "") { 
	print($callback . "({'error':'No response or invalid flight info'});");
	die();
}
// Step 2
// pull out the fromairpot, toairport, flight depart time, elapsed time,
// data for callback
// this should return a jsonp object with flight route info from OAG..

// Get callback url
// also do this for carrier code, service number, request date
try {
	$from_airport = strval($xml_data->Flight->Dep->Port["PortCode"]);
	$from_airport_openflights = getAirport($from_airport);
	$from_city = $from_airport_openflights["City"];
	$from_lat = $from_airport_openflights["Lat"];
	$from_lon = $from_airport_openflights["Lon"];
	$to_airport = strval($xml_data->Flight->Arr->Port["PortCode"]);
	$to_airport_openflights = getAirport($to_airport);
	$to_city = $to_airport_openflights["City"];
	$to_lat = $to_airport_openflights["Lat"];
	$to_lon = $to_airport_openflights["Lon"];
	$depart_time = strval($xml_data->Flight->Dep["DepTime"]);
	$elapsed_time = strval($xml_data->Flight->Dep["ElapsedTime"]);	
} catch (Exception $e) {
	print($callback . "({'error':'Invalid flight info'});");
	die();
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
$jsonp = $callback . "({";
$jsonp .= '"from_airport": "' . $from_airport . '",';
$jsonp .= '"from_city": "' . $from_city . '",';
$jsonp .= '"from_lat": ' . $from_lat . ',';
$jsonp .= '"from_lon": ' . $from_lon . ',';
$jsonp .= '"to_airport": "' . $to_airport . '",';
$jsonp .= '"to_city": "' . $to_city . '",';
$jsonp .= '"to_lat": ' . $to_lat . ',';
$jsonp .= '"to_lon": ' . $to_lon . ',';
$jsonp .= '"depart_time": "' . $depart_time . '",';
$jsonp .= '"elapsed_time": ' . $elapsed_time . '';
$jsonp .= '"error": ""';
$jsonp .= "});";

// example:
//flightmap({"from_airport": "MEL","from_city": "Melbourne","from_lat": -37.673333,"from_lon": 144.843333,"to_airport": "SIN","to_city": "Singapore","to_lat": 1.350189,"to_lon": 103.994433,"depart_time": "2011-10-16T12:00:00","elapsed_time": 470});
print($jsonp);

?>