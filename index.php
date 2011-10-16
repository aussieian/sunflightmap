<?php
include("lib/global.php");
?>
<!DOCTYPE html>
<head>
	<title>Sun Flight Map - Map the path of your flight and the sun | built at tnooz tHack Singapore!</title>

	<meta name="description" content="Map the path of your flight and the sun. Built at tnooz tHack Singapore!">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	
	<meta property="og:title" content="Sun Flight Map" /> 
	<meta property="og:description" content="Map the path of your flight and the sun." /> 
	
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/ui-lightness/jquery-ui.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="css/stylesheet.css" type="text/css" media="screen, projection" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />

	<!-- libraries -->
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js"></script>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>

	<!-- custom code -->
	<script type="text/javascript" src="/javascript/main.js"></script>
	
</head>
<body>
	<div id="map_canvas">Loading...</div>
	<div id="ui-panel">
		<input id="carrierCodeAndServiceNumber" value="JQ7" size="5">
		<input id="requestDate" value="2011-10-14" size="12">
		<button onClick="mapFlight();">Map Flight</button>
	</div>
	<div id="loading-page"><img src='/images/loading.gif' width='32' height='32' style='margin-bottom: -10px; padding-right: 10px;'>Freaking out...</div>
	<div id="debug">
	<?php
		$airport = getAirport("SYD");
		print_r($airport);
	?>
	</div>
</body>