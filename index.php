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
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?libraries=geometry&sensor=false"></script>
	
	<!-- custom code -->
	<script type="text/javascript">
	
	var map;
	var flightPaths = Array();
	var markers = Array();
	var flightMarker = null;
	var sunMarker = null;
		
	$(document).ready(function() {

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
			$("#requestDate").datepicker({ dateFormat: 'yy-mm-dd' });
			<?php if(array_key_exists("autoload", $_GET)) { ?> mapFlight(); <? } ?>
			<?php if(array_key_exists("debug", $_GET)) { ?> $('#debug').show(); <? } ?>
		}
		
		clearMapRoutes = function() {
			//alert(flightPaths.length);
			// remove existing polys
			for (i = 0; i < flightPaths.length; i++) {
				flightPaths[i].setMap(null);
			}
			for (i = 0; i < markers.length; i++) {
				markers[i].setMap(null);
			}
			// reset array
			flightPaths = Array();
			markers = Array();
		}
		
		trim = function(str) {
			return str.replace(/^\s\s*/, '').replace(/\s\s*$/, '');
		}
		
		getInputCarrierCode = function() {
			return trim($('#carrierCodeAndServiceNumber').val().replace(/[\d.]/g, '')); // "JQ"
		}
		
		getInputServiceNumber = function() {
			return trim($('#carrierCodeAndServiceNumber').val().replace(/[A-Za-z$-]/g, '')); // 7
		}
		
		getInputRequestDate = function() {
			return trim($('#requestDate').val());
		}
		
 		validateInput = function() {
			if (getInputCarrierCode() == "") { alert("Please enter a carrier code (ie: JQ)"); return false; }
			if (getInputServiceNumber() == "") { alert("Please enter a service number (ie: JQ7)"); return false; }
			if (getInputRequestDate() == "") { alert("Please enter a date of travel (ie: 2011-10-14)"); return false; }
			return true; // valid!
		}
		
		mapFlight = function() {
			
			// validate input
			if (!validateInput()) { 
				return;
			}
			
			// clear previous map routes
			clearMapRoutes();
			
			// show loading page
			$('#loading-page').show();
			$('#results-panel').hide();

			// lookup flight data from OAG wrapper
			$.getJSON("/ajax/ajax-flight-route.php?callback=?",
			{
				carrier_code: getInputCarrierCode(), // JQ
				service_number: getInputServiceNumber(), // "7",
				request_date: getInputRequestDate() //"2011-10-14"
			},
			function(data) {
				// get back jsonp
				// flightmap({"from_airport": "MEL","from_city": "Melbourne","from_lat": -37.673333,"from_lon": 144.843333,"to_airport": "SIN","to_city": "Singapore","to_lat": 1.350189,"to_lon": 103.994433,"depart_time": "2011-10-16T12:00:00","elapsed_time": 470})
				$('#loading-page').hide();
				if (data.error != "") { 
					alert(data.error);
				} else {
										
					// show slider
					$('#slider-container').show();
					
					// get lat lon
					var fromLatLng = new google.maps.LatLng(data.from_lat, data.from_lon);
					var toLatLng = new google.maps.LatLng(data.to_lat, data.to_lon);

					// get date
					var depart_date = Date.parse(data.depart_time);
					//alert(depart_date.format("UTC:h:MM:ss TT Z"));
					
					// draw path of flight
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
					
					// draw start marker
					var content_html = "<div id='marker'>From: " + data.from_city + " (" + data.from_airport + ")<br>To: " + data.to_city + " (" + data.to_airport + ")<br>Local departure time: " + data.depart_time + "<br>Departure Timezone: " + data.depart_timezone + "<br>UTC departure time: " + data.depart_time_utc + "<br>Flight duration: " + data.elapsed_time + " mins</div>";
					$('#results-panel').html(content_html).fadeIn();
					/*var fromInfoWindow = new google.maps.InfoWindow({ content: content_txt });*/
					/*var fromMarker = new google.maps.Marker({
				        position: fromLatLng,
				        map: map,
				        title: 'Origin'
				    });*/
					/*fromInfoWindow.open(map,fromMarker);*/
					//markers.push(fromMarker);
					
					// draw end marker
					/*var toMarker = new google.maps.Marker({
				        position: toLatLng,
				        map: map,
				        title: 'Destination'
				    });*/
					//markers.push(toMarker);
				
					
					$("#slider").slider({ 
						min: 0,
						max: data.elapsed_time,
						slide: function( event, ui ) {
								mapSunPosition(flightPaths, map, new Date(Date.parse(data.depart_time_utc)), data.elapsed_time, ui.value); // map path of the sun
								mapFlightPosition(flightPaths, map, data.from_lat, data.from_lon, data.to_lat, data.to_lon, data.elapsed_time, ui.value); // map path of the sun
								$("#minutes_travelled" ).val( ui.value );
						}
					});
						
				}

			});
		}	
		
		mapSunPosition = function(flightPaths, map, start_time_at_gmt, duration_minutes, minutes_travelled) {

				// Sun is directly overhead LatLng(0, 0) at 12:00:00 midday
				// 1440 minutes / 1 minute = 0.25 degrees 
				// Assuming maximum trip duration of 24 hours / single leg

				// Calculate sun's starting longitude from the start time at gmt
				minutes_gmt = (start_time_at_gmt.getHours() * 60) + start_time_at_gmt.getMinutes();
				from_deg = 180 - (minutes_gmt * 0.25) ;
				
				duration_deg = duration_minutes * 0.25 * (minutes_travelled / duration_minutes);
				to_deg = from_deg - duration_deg;

				// Starting longitude is positive
				var toLatLng = new google.maps.LatLng(-6, to_deg);

				// draw sun marker
				if (sunMarker != null) { 
					sunMarker.setMap(null);
				}

				sunMarker = new google.maps.Marker({
			        position: toLatLng,
			        map: map,
			        title: 'Sun Position: ' + to_deg,
					icon: '/images/sun.png'
			    }); 
		}
		
		mapFlightPosition = function(flightPaths, map, startLat, startLon, endLat, endLon, duration_minutes, minutes_travelled) {
			
			// draw flight marker
			if (flightMarker != null) { 
				flightMarker.setMap(null);
			}
			
			percentage_travelled = minutes_travelled / duration_minutes;
			
			var fromLatLng = new google.maps.LatLng(startLat, startLon);
			var toLatLng = new google.maps.LatLng(endLat, endLon);
			
			var flightpos = google.maps.geometry.spherical.interpolate(fromLatLng, toLatLng, percentage_travelled)
			
			flightMarker = new google.maps.Marker({
		        position: flightpos,
		        map: map,
		        title: 'Flight position: ' + to_deg,
				icon: '/images/plane.png'
		    });
		}
		
		// let's do it!
		main();

	});
	</script>
		
	
</head>
<body>
	<div id="map_canvas">Loading cool stuff...</div>
	<div id="ui-panel">
		<input id="carrierCodeAndServiceNumber" value="JQ7" size="5">
		<input id="requestDate" value="2011-10-14" size="12">
		<button onClick="mapFlight();">Map Flight</button>
	</div>
	<div id="results-panel">
	</div>
	<div id="loading-page"><img src='/images/loading.gif' width='32' height='32' style='margin-bottom: -10px; padding-right: 10px;'>Doing stuff...</div>
	<div id="slider-container">
		<div id="slider"></div>
	</div>
	<div id="debug">
		<input id="minutes_travelled">
	</div>
</body>