<!DOCTYPE html>
<head>
	<title>SunCalc - sun position, sunlight phases, sunrise, sunset, dusk and dawn times calculator</title>

	<meta name="description" content="A little online application with interactive map that shows sun movement and sunlight phases during the given day at the given location.">
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	
	<meta property="og:title" content="SunCalc sun position and sunlight phases calculator" /> 
<meta property="og:description" content="SunClac is a nice web app for calculating sun position and sunrise/sunset/twilight times given location and date." /> 
	
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/ui-lightness/jquery-ui.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="stylesheets/screen.css" type="text/css" media="screen, projection" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />

	<!-- libraries -->
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js"></script>
	<script type="text/javascript" src="scripts/jquery.address-1.2rc.min.js"></script>
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true&language=en"></script>
	<script type="text/javascript" src="scripts/gears_init.js"></script>
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>
	<script type="text/javascript" src="scripts/raphael-min.js"></script>

	<!-- my code -->
	<script type="text/javascript" src="scripts/suncalc.js"></script>
	<script type="text/javascript" src="scripts/suncalc-overlay.js"></script>
	<script type="text/javascript" src="scripts/main.js"></script>
</head>
<body>
	<div id="header">This is the header</div>
	<div id="map-canvas"></div>
	<div id="footer">This is the footer</div>
</body>