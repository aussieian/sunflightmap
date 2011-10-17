<?php
include("lib/global.php");

// allow input from URL
$autoload = false;
$flightcode = $cfg["EXAMPLE_FLIGHT_ROUTES"][array_rand($cfg["EXAMPLE_FLIGHT_ROUTES"])]; //'JQ7';
if (array_key_exists("flightcode", $_GET)) {
	$flightcode = $_GET['flightcode'];
	$autoload = true;
}
$date_depart=date("Y-m-d");
if (array_key_exists("date", $_GET)) {
	$date_depart=$_GET['date'];
	$autoload = true;
}

if(array_key_exists("autoload", $_GET)) {
	$autoload = true;
}

$topsecret = false;
if(array_key_exists("topsecret", $_GET)) {
	$topsecret = true;
}


?>
<!DOCTYPE html>
<head>
	<title>SunFlight.net - Chase the sun and map the path of your flight with it | Built at Tnooz tHack Singapore!</title>

	<meta name="description" content="Map the path of your flight and the sun. Built at tnooz tHack Singapore!">
	<!--<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">-->
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	
	<meta property="og:title" content="SunFlight.net" /> 
	<meta property="og:description" content="Chase the sun and map the path of your flight with it." /> 
	
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/excite-bike/jquery-ui.css" type="text/css" media="screen, projection" />
	<link rel="stylesheet" href="css/stylesheet.css" type="text/css" media="screen, projection" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />

	<!-- libraries -->
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js"></script>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?libraries=geometry&sensor=false"></script>
	
	<!-- custom code -->
	<script type="text/javascript">
	
	// OUR CODE SUCKS!
	
	var map;
	var flightPaths = Array();
	var markers = Array();
	var flightMarker = null;
	var sunMarker = null;
	var aboutClicked = false;
	var loadingMessages = Array("Loading stuff", "Drinking a beer", "Having a yarn", "LOLing your cat");
		
	$(document).ready(function() {

		function initializeMap() {
			var myOptions = {
				zoom: 2,
				center: new google.maps.LatLng(-34.397, 150.644),
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				streetViewControl: false,
				mapTypeControl: false,
				panControl: false
			};
			map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
		}

		main = function() {
			google.maps.event.addDomListener(window, 'load', initializeMap);
			$("#requestDate").datepicker({ dateFormat: 'yy-mm-dd', defaultDate: +0});
			<?php if ($autoload) { ?> mapFlight(); <? } ?>
			<?php if(array_key_exists("debug", $_GET)) { ?> $('#debug').show(); <? } ?>
			updatePermalink();
			<?php if (!($autoload)) { ?> showWelcomeWindow(); <?php } ?>
			$('body').bind('click', function() { if (!aboutClicked) { hideWelcomeWindow(); } })
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
			
			// make permalink
			updatePermalink();
			
			// clear previous map routes
			clearMapRoutes();
			
			// show loading page
			$('#loading-message').html(loadingMessages[Math.floor(Math.random() * loadingMessages.length)] + "..."); 
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
					var content_html = "<div>";
					content_html += "Depart: " + data.from_city + " (" + data.from_airport + ")<br>Arrive: " + data.to_city + " (" + data.to_airport + ")<br>Local departure time: " + data.depart_time + "<br>Departure Timezone: " + data.depart_timezone + " GMT <br>UTC departure time: " + data.depart_time_utc + "<br>Flight duration: " + data.elapsed_time + " mins<br>";
					<?php if($topsecret) { ?> content_html += "<br><a style='font-size: larger;' href='javascript:void(0);' onClick='doHotelRedirect();'>$$$$$$ Get a cheap hotel!</a></div>"; <?php } ?>
					content_html += "</div>";
					
					$('#results-panel').html(content_html).fadeIn();
					/*var fromInfoWindow = new google.maps.InfoWindow({ content: content_txt });*/
					/*var fromMarker = new google.maps.Marker({
				        position: fromLatLng,
				        map: map,
				        title: 'Origin'
				    });*/
					//fromInfoWindow.open(map,fromMarker);
					//markers.push(fromMarker);
					
					// draw end marker
					var toLatLngFlag = new google.maps.LatLng(data.to_lat, data.to_lon);
					var toMarker = new google.maps.Marker({
				        position: toLatLngFlag,
				        map: map,
				        title: 'Destination',
						icon: '/images/flag.png'
				    });
					markers.push(toMarker);
				
					
					$("#slider").slider({ 
						min: 0,
						max: data.elapsed_time,
						slide: function( event, ui ) {
								clearTimeout(this.id);
								this.id = setTimeout(function(){
									mapSunPosition(flightPaths, map, new Date(Date.parse(data.depart_time_utc)), data.elapsed_time, ui.value); // map path of the sun
									mapFlightPosition(flightPaths, map, data.from_lat, data.from_lon, data.to_lat, data.to_lon, data.elapsed_time, ui.value); // map path of the sun
									$("#minutes_travelled" ).val( ui.value );
									updateSliderTime(ui.value, data.elapsed_time);
								}, 10);
						}
					});
					// update slider to begin with
					mapSunPosition(flightPaths, map, new Date(Date.parse(data.depart_time_utc)), data.elapsed_time, 0); // map path of the sun
					mapFlightPosition(flightPaths, map, data.from_lat, data.from_lon, data.to_lat, data.to_lon, data.elapsed_time, 0); // map path of the sun
					$("#minutes_travelled" ).val( 0 );
					updateSliderTime(0, data.elapsed_time);
						
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
		
		function updateSliderTime(t, max)
		{
			slider_text = t + " mins";
			if (t == 0) { 
				slider_text = "Take off...";
			}
			else if (t == max) {
				slider_text = "Landed!";
			} 
			
			$('#slider-time').html(slider_text);
		}
		
		function updatePermalink()
		{
			$('#permalink').attr("href", "http://" + window.location.hostname + "/?flightcode="+getInputCarrierCode() + getInputServiceNumber() + "&date=" + getInputRequestDate());
		}
		
		hideWelcomeWindow = function()
		{
			aboutClicked = false;
			$('#welcome').fadeOut();
		}
		
		showWelcomeWindow = function()
		{
			$('#welcome').show();
		}
		
		hideHypnoToad = function()
		{
			$('#hypnotoad').show();
		}
		
		showHypnoToad = function()
		{
			$('#hypnotoad').show();
		}
		
		doHotelRedirect = function()
		{
			showHypnoToad();
			hypnotoad = setTimeout(function(){
				document.location = '<?php print($cfg["HYPNOTOAD_URL"]);?>';
				//window.open('<?php print($cfg["HYPNOTOAD_URL"]);?>','hypnotoad');
			},150);
		}
		
		// let's do it!
		main();

	});
	</script>
		
	
</head>
<body>
	<div id="map_canvas">Loading cool stuff...</div>
	<div id="ui-container">
		<div id="ui-panel" class="shadow">
			<input id="carrierCodeAndServiceNumber" value="<?php print($flightcode);?>" size="5">
			<input id="requestDate" value="<?php print($date_depart); ?>" size="12">
			<button class="shiny-blue" onClick="mapFlight();">Chase the sun!</button>
			<br>
			<span style="font-size: 10pt;">Enter carrier code and flight number (ie: JQ7). I'm feeling <a style="color: #FF0080" href="/?autoload<?php if($topsecret) { print("&topsecret"); }?>">lucky</a> | <a id="permalink" style="color: blue;" href="#">permalink</a></span>
		</div>
		<div id="results-panel"></div>
	</div>
	
	<div id="loading-page">
		<img src='/images/loading.gif' width='32' height='32' style='margin-bottom: -10px; padding-right: 10px;'>
		<span id="loading-message">Loading...</span>
	</div>
	<div id="slider-container">
		<table width="100%"><tr>
			<td width="20%"><span style='color: #222'>Slide me</a></td>
			<td width="60%"><div id="slider"></div></td>
			<td width="20%" align="right"><div id="slider-time"></div></td>
		</tr></table>
	</div>
	<div id="welcome">
		<a href="javascript:void();" onClick="hideWelcomeWindow();">Continue</a>
		<center><img src="/images/thack-singapore-logo1.jpg"></center>
		<p>Because flying with the sun in your face isn't cool!</p>
		<p>Want to choose the <strong>best</strong> side of the aircraft to fly on?</p>
		<p>Want to make sure you can <strong>see the sunset</strong> over New York or <strong>watch the sunrise</strong> over the Pacific?</p>
		<p>SunFlight.net shows you where the sun will be during your journey, so you can choose the best side of the aircraft to be seated!</p>
		<p style="font-size: smaller; color: #888;">Notes: Google Chrome only at the moment. Most likely a few bugs!</p>
		<p style="font-size: smaller;">Powered by <img width="80" style="margin-bottom: -12px;" src="/images/oag-aviation.jpg"> &nbsp; OnDemand</p>
		
	</div>
	<div id="info" class="shadow">A <a href="http://tnooz.com">Tnooz.com tHack</a> at <a href="http://www.webintravel.com">Web In Travel Singapore</a> by <a href="http://twitter.com/aussie_ian">@aussie_ian</a> and <a href="http://twitter.com/dansync">@dansync</a>.
		<br>Powered by <a href="http://www.oagaviation.com/Solutions/Aviation-Data/OAG-Schedules-Data/OAG-OnDemand">OAG OnDemand</a>. 
		Shouts to <a href="http://www.travelmassive.com">#travelmassive</a> world-wide!
		<a href="javascript:void(0);" onClick="aboutClicked = true; showWelcomeWindow();">About</a>
	</div>
	<div id="hypnotoad">
		<img width="600" height="600" src="/images/hypnotoad.gif">
	</div>
	<div id="topsecret">
		<a href="/?topsecret&autoload">Enable top secret ad engine</a>
	</div>
	<div id="debug">
		<input id="minutes_travelled">
	</div>
	
	<!-- google anlaytics -->
	<script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-26351606-1']);
	  _gaq.push(['_trackPageview']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>
	<!-- analytics -->
	
</body>