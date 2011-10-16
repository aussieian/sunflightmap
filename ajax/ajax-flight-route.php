<?php

include("../lib/config.php");

// steps:
// references: http://ondemandtestharness.oag.com/CBWSTestHarnessPublic/#flightLookupRequest

// Step 1
// fetch XML results via OAG test harness which gives us a REST 
// url: http://ondemandtestharness.oag.com/CBWSTestHarnessPublic//FlightLookupRequestAction.do?
// post vars: 
// actionForm:FlightLookupRequestForm
// inputPrefix:f_
// f_username:THACK
// f_password:THACK
// f_carrierCode:JQ
// f_serviceNumber:7
// f_requestDate:2011-10-16
// f_requestTime:12:00:00

// Step 2
// pull out the fromairpot, toairport, flight depart time, elapsed time, 

// this should return a jsonp object with flight route info from OAG..

// Get callback url
$callback='flightmap';
if (array_key_exists("callback", $_GET)) {
	$callback=$_GET['callback'];
}

print($callback . '({"from_airport":"MEL","to_airport":"SIN","depart_time":"2011-10-14","elapsed_time":470});');

?>