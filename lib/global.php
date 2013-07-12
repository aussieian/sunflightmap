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

// detect a mobile device
function isMobile() {
	$iphone = (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),"iphone") !== false);
	$android = (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),"android") > 0);
	$palmpre = (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),"webos") !== false);
	$ipod = (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),"ipod") !== false);
	if($iphone || $android || $palmpre || $ipod) {
		// it's a mobile device
		return true;
		}
	return false;
}

// detect ipad
function isiPad() {
	$isiPad = (bool) strpos($_SERVER['HTTP_USER_AGENT'],'iPad');
	return $isiPad;
}

// detect IE 6
function isIE6() {
	$ua=getBrowser();
	$is_ie7 = ($ua["name"] == 'Internet Explorer' && $ua['version'] == 6);
	return $is_ie7;
}

// detect IE 7
function isIE7() {
	$ua=getBrowser();
	$is_ie7 = ($ua["name"] == 'Internet Explorer' && $ua['version'] == 7);
	return $is_ie7;
}

// detect IE 8
function isIE8() {
	$ua=getBrowser();
	$is_ie8 = ($ua["name"] == 'Internet Explorer' && $ua['version'] == 8);
	return $is_ie8;
}

// detect IE 9
function isIE9() {
	$ua=getBrowser();
	$is_ie9 = ($ua["name"] == 'Internet Explorer' && $ua['version'] == 9);
	return $is_ie9;
}

// detect any IE
function isIE() {
	$ua=getBrowser();
	$is_ie = ($ua["name"] == 'Internet Explorer');
	return $is_ie;
}

?>