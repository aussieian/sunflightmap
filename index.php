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
	<script type="text/javascript">
	$(document).ready(function() {

		var map;
		var flightPaths = Array();
		var markers = Array();

		function initializeMap() {
			var myOptions = {
				zoom: 2,
				center: new google.maps.LatLng(-34.397, 150.644),
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
		}

		main = function() {
			google.maps.event.addDomListener(window, 'load', initializeMap);
			<?php if(array_key_exists("autoload", $_GET)) { ?> mapFlight(); <? } ?>
		}
		
		function clearMapRoutes() {
			//alert(flightPaths.length);
			// remove existing polys
			for (i = 0; i < flightPaths.length; i++) {
				flightPaths[i].setMap(null);
			}
			for (i = 0; i < markers.length; i++) {
				markers[i].setMap(null);
			}
		}

		mapFlight = function() {
			var carrier_code_txt = $('#carrierCodeAndServiceNumber').val().replace(/[\d.]/g, ''); //"JQ",
			var service_number_txt = $('#carrierCodeAndServiceNumber').val().replace(/[A-Za-z$-]/g, ''); // 7
			//alert(carrier_code_txt);
			
			$('#loading-page').show();
			$.getJSON("/ajax/ajax-flight-route.php?callback=?",
			{
				carrier_code: carrier_code_txt, // JQ
				service_number: service_number_txt, // "7",
				request_date: $('#requestDate').val() //"2011-10-14"
			},
			function(data) {
				// get back jsonp
				//alert("here..");
				// flightmap({"from_airport": "MEL","from_city": "Melbourne","from_lat": -37.673333,"from_lon": 144.843333,"to_airport": "SIN","to_city": "Singapore","to_lat": 1.350189,"to_lon": 103.994433,"depart_time": "2011-10-16T12:00:00","elapsed_time": 470})
				$('#loading-page').hide();
				if (data.error != "") { 
					alert(data.error);
				} else {
					clearMapRoutes(); 
					var flightPaths = Array();
					
					var fromLatLng = new google.maps.LatLng(data.from_lat, data.from_lon);
					var toLatLng = new google.maps.LatLng(data.to_lat, data.to_lon);

					var flightPath = new google.maps.Polyline({
						path: [fromLatLng, toLatLng],
						strokeColor: "#FF0080",
						strokeOpacity: 1.0,
						strokeWeight: 2,
						geodesic: true,
						clickable: false 
					});

					flightPaths.push(flightPath);
					flightPath.setMap(map);
					
					mapSunPath(flightPaths, map, "",470); // map path of the sun			
				}

			});
		}	

		// let's do it!
		main();

	});
	</script>
	
	<script type="text/javascript" src="javascript/mapsun.js"></script>
	
	
</head>
<body>
	<div id="map_canvas">Loading cool stuff...</div>
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