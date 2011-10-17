<?php

include_once("config.php");
include_once("airport_codes.php"); // todo: could move this to load on demand since its a large file

function getAirport($airport_code) {
	global $openflights_airports;
	if (array_key_exists($airport_code, $openflights_airports)) { 
		return $openflights_airports[$airport_code];
	}
	// not found
	return null;
}

// detect chrome
function isChrome() {
	$ischrome = (preg_match("/Chrome/i", $_SERVER['HTTP_USER_AGENT']) > 0);
	return $ischrome;
}

?>