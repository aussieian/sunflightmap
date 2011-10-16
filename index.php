<?php
include("lib/global.php");
?>
<!DOCTYPE html>
<head>
	<title>Sun Flight Map - Map the path of your flight and the sun | built at tnooz tHack Singapore!</title>

	<meta name="description" content="Map the path of your flight and the sun. Built at tnooz tHack Singapore!">
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	
	<meta property="og:title" content="Sun Flight Map" /> 
	<meta property="og:description" content="Map the path of your flight and the sun." /> 
	
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/ui-lightness/jquery-ui.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="css/stylesheet.css" type="text/css" media="screen, projection" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />

	<!-- libraries -->
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js"></script>
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=true&language=en"></script>

	<!-- custom code -->
	<script type="text/javascript" src="/javascript/main.js"></script>
	
</head>
<body>
	<div id="header">This is the header</div>
	<div id="map-canvas">This is the google map</div>
	<div id="footer">This is the footer</div>
	<div id="debug">
	<?php
		$airport = getAiport("SYD");
		print_r($airport);
	?>
	</div>
</body>