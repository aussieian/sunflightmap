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
<html>
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
	<script type="text/javascript" src="/js/daynightmaptype.js"></script>
	<script type="text/javascript" src="/js/jQueryRotate.2.2.js"></script>
	<script type="text/javascript" src="/js/richmarker-compiled.js"></script>

	<script type="text/javascript">
	
	// OUR CODE SUCKS!
	var map;
	var flightPaths = Array();
	var markers = Array();
	var flightMarker = null;
	var sunMarker = null;
	var aboutClicked = false;
	var loadingMessages = Array("Loading route");
	var dn = null;
	// day night shadow
	var initSlider = false;
	// track if we have initialised the slider yet
	var timeslider = null;

	$(document).ready(function() {

	    function initializeMap() {
	        var myOptions = {
	            zoom: 1,
	            center: new google.maps.LatLng( 10, 150.644),
	            mapTypeId: google.maps.MapTypeId.ROADMAP,
	            streetViewControl: false,
	            mapTypeControl: false,
	            panControl: false
	        };
	        map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
	        <?php
	        if ($autoload) { ?>mapFlight(); <?
	        } ?>
	    }

	    main = function() {
	        google.maps.event.addDomListener(window, 'load', initializeMap);
	        $("#requestDate").datepicker({
	            dateFormat: 'yy-mm-dd',
	            defaultDate: +0
	        });
	        <?php
	        if (array_key_exists("debug", $_GET)) { ?>$('#debug').show(); <?
	        } ?>
	        updatePermalink();
	        <?php
	        if (! ($autoload)) { ?>showWelcomeWindow(); <?php
	        }?>
	        $('body').bind('click',
	        function() {
	            if (!aboutClicked) {
	                hideWelcomeWindow();
	            }
	        })
	        <?php
	        if (!isChrome()) { ?>alert("We only support Google Chrome at the moment. Firefox and IE is currently buggy, sorry..."); <?
	        } ?>
	        //init();
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
	        return trim($('#carrierCodeAndServiceNumber').val().replace(/[\d.]/g, ''));
	        // "JQ"
	    }

	    getInputServiceNumber = function() {
	        return trim($('#carrierCodeAndServiceNumber').val().replace(/[A-Za-z$-]/g, ''));
	        // 7
	    }

	    getInputRequestDate = function() {
	        return trim($('#requestDate').val());
	    }

	    validateInput = function() {
	        if (getInputCarrierCode() == "") {
	            alert("Please enter a carrier code (ie: JQ)");
	            return false;
	        }
	        if (getInputServiceNumber() == "") {
	            alert("Please enter a service number (ie: JQ7)");
	            return false;
	        }
	        if (getInputRequestDate() == "") {
	            alert("Please enter a date of travel (ie: 2011-10-14)");
	            return false;
	        }
	        return true;
	        // valid!
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
	            carrier_code: getInputCarrierCode(),
	            // JQ
	            service_number: getInputServiceNumber(),
	            // "7",
	            request_date: getInputRequestDate()
	            //"2011-10-14"
	        },
	        function(data) {

	        	if (data.error != null) {
	        		$('#loading-page').hide();
	        		alert(data.error);
	        	} else {
	        		$("#cached_result").val(data.cached);
	        		initFlightRoutes(data.flight_segments);
	        	}
	        });
	    }

	    drawFlightRoute = function(data) {

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

	    }

	    drawFlightSummary = function(flightdata) {

            var content_html = "<div>";

            content_html += "<table width='100%'>";

            content_html += "<tr>";
            content_html += "<td colspan='2'>Qantas Airways Flight 15</td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td colspan='2'>Total duration: 18 hours 15 minutes</td>";
       		content_html += "</tr>";

       		content_html += "</html>";

       		content_html += "</div>";

       		// draw results
       		$('#results-panel').append(content_html);
       	}


	    drawFlightData = function(data) {

       		var content_html = "<div>";

            content_html += "<table class='flightdata' width='100%'>";

            content_html += "<tr><td colspan='2'>";
            content_html += "<table width='100%'><tr>";
            content_html += "<td><span class='flightdata from_airport'>" + data.from_airport + "</span></td>";
            content_html += "<td><span class='flightdata plane_icon'>&#9992;</span></td>";
            content_html += "<td><span class='flightdata to_airport'>" + data.to_airport + "</span></td>";
            content_html += "</tr></table>";
            content_html += "</td></tr>";

            content_html += "<tr>";
            content_html += "<td width='50%'><span class='flightdata depart_city'>Depart " + data.from_city + "</span></td>";
       		content_html += "<td width='50%'><span class='flightdata arrive_city'>Arrive " + data.to_city + "</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td width='50%'><span class='flightdata scheduled_time'>Scheduled<br>" + data.depart_time + "</span></td>";
       		content_html += "<td width='50%'><span class='flightdata scheduled_time'>Scheduled<br>" + data.arrival_time + "</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td colspan='2'><span class='flightdata duration'>Duration: " + formatMinutes(data.elapsed_time) + "</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
       		var miles_to_km = 0.621371192;
            content_html += "<td colspan='2'><span class='flightdata distance'>Distance: " + addCommas(Math.round(data.distance_km * miles_to_km)) + " miles";
            content_html += " (" + addCommas(data.distance_km ) + " km)</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td colspan='2'><span class='flightdata operation'>Days of Operation: " + data.days_of_op + "</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td colspan='2'><span class='flightdata % left'>% LEFT: " + data.flight_stats.percent_left + "</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td colspan='2'><span class='flightdata % left'>% RIGHT: " + data.flight_stats.percent_right + "</span></td>";
       		content_html += "</tr>";

       		content_html += "<tr>";
            content_html += "<td colspan='2'><span class='flightdata % left'>% NIGHT: " + data.flight_stats.percent_night + "</span></td>";
       		content_html += "</tr>";

            content_html += "</table>";

            /*content_html += "Depart: " + data.from_city + " (" + data.from_airport + ")<br>";
            content_html += "Arrive: " + data.to_city + " (" + data.to_airport + ")<br>";
            content_html += "Local departure time: " + data.depart_time + "<br>";
            content_html += "Departure Timezone: " + data.depart_timezone + " GMT <br>";
            content_html += "UTC departure time: " + data.depart_time_utc + "<br>";
            content_html += "UTC arrival time: " + data.arrival_time_utc + "<br>";
            content_html += "Flight duration: " + data.elapsed_time + " mins<br>";*/
            <?php
            if ($topsecret) { ?>content_html += "<br><a style='font-size: larger;' href='javascript:void(0);' onClick='doHotelRedirect();'>$$$$$$ Get a cheap hotel!</a></div>"; <?php
            } ?>
            content_html += "<hr></div>";

           	$('#results-panel').append(content_html);
	    }


        function addCommas(nStr)
		{
		  nStr += '';
		  x = nStr.split('.');
		  x1 = x[0];
		  x2 = x.length > 1 ? '.' + x[1] : '';
		  var rgx = /(\d+)(\d{3})/;
		  while (rgx.test(x1)) {
		    x1 = x1.replace(rgx, '$1' + ',' + '$2');
		  }
		  return x1 + x2;
		}


	    formatMinutes = function(minutes) {
	    	var hours = Math.floor(minutes / 60);
	    	if (hours > 0) {
	    		var text = hours + "hrs " + (minutes % 60) + " mins";
	    	} else {
	    		var text = (minutes % 60) + " mins";
	    	}
	    	return text;
	    }

	    resetResults = function() {

	    	$('#loading-page').hide();
	    	$('#results-panel').html("");
	    }

	    drawFlightEndPoints = function(data) {

	    	var circle = {
    			path: google.maps.SymbolPath.CIRCLE,
    			scale: 3.0,
    			fillColor: "#F00",
    			strokeColor: "#eee",
    			stokeWeight: 0.1
  			};

            var flagimage = new google.maps.MarkerImage('images/flag.png',
	            new google.maps.Size(30, 36),
	            // marker dimensions
	            new google.maps.Point(0, 0),
	            // origin of image
	            new google.maps.Point(2, 36));

	            // anchor of image
	            var toLatLngFlag = new google.maps.LatLng(data.to_lat, data.to_lon);
	            var toMarker = new google.maps.Marker({
	                position: toLatLngFlag,
	                map: map,
	                title: 'Destination',
	                icon: circle
	                //'/images/flag.png'
            });
            markers.push(toMarker);
	    }

	    initTimeSlider = function(flightdata) {

	    	var first_flight = flightdata[0];
	    	var last_flight = flightdata[flightdata.length - 1];

	    	// calculate total flight time
	    	// use elapsed_time for each flight plus the time until the next departure
	    	var total_minutes = 0;
	    	for (var i = 0; i < flightdata.length; i++) {
				// calculate flight duration including layover time for next segment
	    		if (i < flightdata.length - 1) {
	    			var this_flight_arrival_time = new Date(Date.parse(flightdata[i].arrival_time));
	    			var next_flight_start_time = new Date(Date.parse(flightdata[i+1].depart_time));
	    			var flight_time_diff = Math.abs(next_flight_start_time.getTime() - this_flight_arrival_time.getTime());
	    			var flight_time_including_stopover = flightdata[i].elapsed_time + Math.ceil(flight_time_diff / 1000 / 60);
	    		} else {
	    			var flight_time_including_stopover = flightdata[i].elapsed_time;
	    		}

	    		total_minutes += flight_time_including_stopover; // add flight time included stop over
	    	}


			// record flight segment index
			var flight_segment_by_minute = []; // track which flight segment a given minute is in
			var flight_segment_start_time = []; // track the start time (of the total journey) of a flight

	    	for (var i = 0; i < flightdata.length; i++) {

	    		// calculate flight duration including layover time for next segment
	    		if (i < flightdata.length - 1) {
	    			var this_flight_arrival_time = new Date(Date.parse(flightdata[i].arrival_time));
	    			var next_flight_start_time = new Date(Date.parse(flightdata[i+1].depart_time));
	    			var flight_time_diff = Math.abs(next_flight_start_time.getTime() - this_flight_arrival_time.getTime());
	    			var flight_time_including_stopover = flightdata[i].elapsed_time + Math.ceil(flight_time_diff / 1000 / 60);
	    			//alert(flight_time_including_stopover);
	    		} else {
	    			var flight_time_including_stopover = flightdata[i].elapsed_time;
	    		}

	    		// for each minute of flight time, record the segment index (i)
	    		var flight_segment_by_minute_length = flight_segment_by_minute.length;
	    		//alert(flight_segment_by_minute_length + flight_time_including_stopover);
	    		for (var j = flight_segment_by_minute_length; j <= flight_segment_by_minute_length + flight_time_including_stopover; j++) {
	    			flight_segment_by_minute[j] = i;
	    			flight_segment_start_time[i] = j;
	    		}
	    	}

	    	if (timeslider != null) {
                timeslider = $("#slider").slider("destroy");
            }

	    	$("#slider_holder").empty();
            $("#slider_holder").append("<div id='slider'></div>")

            timeslider = $("#slider").slider({
                min: 0,
                max: total_minutes,
                value: 0,
                animate: false,
                slide: function(event, ui) {
                    clearTimeout(this.id);
                    this.id = setTimeout(function() {

                        //console.log(first_flight.depart_time_utc);
                        mapSunPosition(flightPaths, map, new Date(Date.parse(first_flight.depart_time_utc)), total_minutes, ui.value);
                        
                        // map path of the sun
                        // work out which flight segment we are in using flight_segment_by_minute index
                        var flight_segment = flight_segment_by_minute[ui.value];
                        var current_flight = flightdata[flight_segment];
                        $("#flight_segment").val(flight_segment);
                        var relative_ui_value = ui.value;
                        if (flight_segment > 0) {
                        	relative_ui_value -= flight_segment_start_time[flight_segment-1]; // offset with previous flight
                        }
                        var minute_of_segment = relative_ui_value;


                        var flight_points = current_flight["flight_points"];
                        $("#minute_of_segment").val(relative_ui_value);

                        if (minute_of_segment < current_flight.elapsed_time) {
							var flight_point = flight_points[minute_of_segment];
                        	$("#sfcalc_sun_side").val(flight_point["sun_side"]);
                        	$("#sfcalc_tod").val(flight_point["tod"]);
                        	$("#sfcalc_sun_east_west").val(flight_point["sun_east_west"]);
                        	$("#sfcalc_azimuth_from_north").val(flight_point["azimuth_from_north"]);
                        	$("#sfcalc_bearing_from_north").val(flight_point["bearing_from_north"]);
                        } else {
                        	$("#sfcalc_sun_side").val("stopover");
                        	$("#sfcalc_tod").val("stopover");
                        	$("#sfcalc_sun_east_west").val("stopover");
                        	$("#sfcalc_azimuth_from_north").val("stopover");
                        	$("#sfcalc_bearing_from_north").val("stopover");
                        }

                        /*var planeimage = new google.maps.MarkerImage('images/sun.png',
					        new google.maps.Size(32, 31),
					        // marker dimensions
					        new google.maps.Point(0, 0),
					        // origin of image
					        new google.maps.Point(16, 16));

				        // anchor of image
				        var flightpos = new google.maps.LatLng(flight_point["lat"], flight_point["lng"]);

				        flightMarker2 = new google.maps.Marker({
				            position: flightpos,
				            map: map,
				            title: 'Flight position: ' + to_deg,
				            icon: planeimage
				        });
				        markers.push(flightMarker2);*/


				        if (minute_of_segment < current_flight.elapsed_time) {
				        	current_bearing = flight_point["bearing_from_north"];
				        }
                        mapFlightPosition(flightPaths, map, current_flight.from_lat, current_flight.from_lon, current_flight.to_lat, current_flight.to_lon, current_flight.elapsed_time, relative_ui_value, current_bearing);
                        
                        // map path of the sun
                        mapDayNightShadow(map, new Date(Date.parse(first_flight.depart_time_utc)), ui.value);
                        $("#minutes_travelled").val(ui.value);
                        updateSliderTime(ui.value, total_minutes);                        
                    },
                    10);
                }
            });

			// update slider to begin with
            mapSunPosition(flightPaths, map, new Date(Date.parse(first_flight.depart_time_utc)), total_minutes, 0);
            // map path of the sun
            mapFlightPosition(flightPaths, map, first_flight.from_lat, first_flight.from_lon, first_flight.to_lat, first_flight.to_lon, first_flight.elapsed_time, 0, first_flight.flight_points[0]["bearing_from_north"]);
            // map path of the sun
            mapDayNightShadow(map, new Date(Date.parse(flightdata[0].depart_time_utc)), 0);
            $("#minutes_travelled").val(0);
            updateSliderTime(0, total_minutes);

	    }

	    initFlightRoutes = function(flightdata) {

            // get back jsonp
            // flightmap({"from_airport": "MEL","from_city": "Melbourne","from_lat": -37.673333,"from_lon": 144.843333,"to_airport": "SIN","to_city": "Singapore","to_lat": 1.350189,"to_lon": 103.994433,"depart_time": "2011-10-16T12:00:00","elapsed_time": 470})
			resetResults();

	        // draw flight routes
	        for(var i = 0; i < flightdata.length; i++) {

	        	// check for errors
	        	if (flightdata[i].error != "") {
	        		alert("Error processing flight route data: " + data.error);
	        		return;
	        	}

	        	drawFlightRoute(flightdata[i]);
	        	drawFlightData(flightdata[i]);
	        	drawFlightEndPoints(flightdata[i]);
	        }	      

	        // show slider
	        $('#slider-container').show();
	       	$('#results-panel').fadeIn();

	    	initTimeSlider(flightdata);

	    }

	    mapDayNightShadow = function(map, UTCTime, minutesOffset) {
	        //alert(maptime);
	        if (dn == null) {

	            dn = new DayNightMapType(UTCTime, minutesOffset);
	            map.overlayMapTypes.insertAt(0, dn);
	            dn.setMap(map);
	            //dn.setAutoRefresh(10);
	            dn.setShowLights(1);
	        }
	        else {
	            dn.calcCurrentTime(UTCTime, minutesOffset);
	            dn.redoTiles();
	        }
	    }

	    mapSunPosition = function(flightPaths, map, start_time_at_gmt, duration_minutes, minutes_travelled) {

	        // Sun is directly overhead LatLng(0, 0) at 12:00:00 midday
	        // 1440 minutes / 1 minute = 0.25 degrees
	        // Assuming maximum trip duration of 24 hours / single leg
	        // Calculate sun's starting longitude from the start time at gmt
	        //console.log(start_time_at_gmt);
	        //console.log(new Date(start_time_at_gmt).getTimezoneOffset());
	        local_offset = new Date(start_time_at_gmt).getTimezoneOffset();
	        minutes_gmt = local_offset + (start_time_at_gmt.getHours() * 60) + start_time_at_gmt.getMinutes();
	        //console.log(minutes_gmt);
	        from_deg = 180 - minutes_gmt * 0.25;

	        duration_deg = duration_minutes * 0.25 * (minutes_travelled / duration_minutes);
	        to_deg = from_deg - duration_deg;

			var dayofyear= (start_time_at_gmt - new Date(start_time_at_gmt.getFullYear(),0,1)) / 86400000;
			var sunlat = -23.44*Math.sin(((dayofyear + 10 + 91.25)*Math.PI)/(365/2));

			// Starting longitude is positive
			var toLatLng = new google.maps.LatLng(sunlat, to_deg);

	        // draw sun marker
	        if (sunMarker != null) {
	            sunMarker.setMap(null);
	        }

	        var sunimage = new google.maps.MarkerImage('images/sun.png',
	        new google.maps.Size(32, 32),
	        // marker dimensions
	        new google.maps.Point(0, 0),
	        // origin of image
	        new google.maps.Point(16, 16));
	        // anchor of image
	        sunMarker = new google.maps.Marker({
	            position: toLatLng,
	            map: map,
	            title: 'Sun Position: ' + to_deg,
	            icon: sunimage
	        });
	        markers.push(sunMarker);

	    }

	    mapFlightPosition = function(flightPaths, map, startLat, startLon, endLat, endLon, duration_minutes, minutes_travelled, bearing) {

	        // draw flight marker
	        if (flightMarker != null) {
	            flightMarker.setMap(null);
	        }

	        if (minutes_travelled > duration_minutes) {
	        	minutes_travelled = duration_minutes;
	        }

	        percentage_travelled = minutes_travelled / duration_minutes;

	        var fromLatLng = new google.maps.LatLng(startLat, startLon);
	        var toLatLng = new google.maps.LatLng(endLat, endLon);

	        try {
	            var flightpos = google.maps.geometry.spherical.interpolate(fromLatLng, toLatLng, percentage_travelled);
	        }
	        catch(error) {
	            // ignore it
	            }


	        var planeimage = new google.maps.MarkerImage('images/airplane.svg', null, null, null, new google.maps.Size(32, 32));
	        /*new google.maps.Size(32, 31),
	        // marker dimensions
	        new google.maps.Point(0, 0),
	        // origin of image
	        new google.maps.Point(16, 16));
	        // anchor of image*/

	        flightMarker = new google.maps.Marker({
	            position: flightpos,
	            map: map,
	            title: 'Flight position: ' + to_deg,
	            icon: {
	            	//url: "images/airplane.svg",
	            	//size: new google.maps.Size(32, 32),
			        //path: "M 0 5 L 20 5 L 10 40 z",
			        scale: 1.2,
			        //path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
			        //path: "M 100 100 L 300 100 L 200 300 z",
			        //path: 'M -1,0 A 1,1 0 0 0 -3,0 1,1 0 0 0 -1,0M 1,0 A 1,1 0 0 0 3,0 1,1 0 0 0 1,0M -3,3 Q 0,5 3,3',
			        //path: "M31.356,500.29c-17.26,0-31.256-13.995-31.256-31.261v-437.67c0-17.265,13.996-31.261,31.256-31.261h437.68c17.266,0,31.261,13.996,31.261,31.263v437.67c0,17.266-13.995,31.261-31.261,31.261h-437.67z",
			        //path: "M250.2,59.002c11.001,0,20.176,9.165,20.176,20.777v122.24l171.12,95.954v42.779l-171.12-49.501v89.227l40.337,29.946v35.446l-60.52-20.18-60.502,20.166v-35.45l40.341-29.946v-89.227l-171.14,49.51v-42.779l171.14-95.954v-122.24c0-11.612,9.15-20.777,20.16-20.777z",
			        path: "m16.194347,3.509549c0.7269,0 1.333155,0.605579 1.333155,1.372868l0,8.077136l11.306938,6.34025l0,2.826685l-11.306938,-3.270845l0,5.895784l2.665304,1.978716l0,2.342138l-3.99892,-1.333424l-3.997725,1.3325l0,-2.342411l2.665575,-1.978714l0,-5.895763l-11.308268,3.271421l0,-2.826664l11.308268,-6.340271l0,-8.077136c0,-0.767288 0.604597,-1.372868 1.332093,-1.372868l0.000519,0.000597z",
			        origin: new google.maps.Point(0, 0),
			        anchor: new google.maps.Point(16, 16),
			        strokeWeight: 0.5,
			        fillOpacity: 1,
			        fillColor: "#0F0",
			        rotation: bearing			    }
	        	});
	        markers.push(flightMarker);
	    }

	    function updateSliderTime(t, max)
	    {
	        //slider_text = t + " mins";
	        slider_text = formatMinutes(t);
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
	        $('#permalink').attr("href", "http://" + window.location.hostname + "/?flightcode=" + getInputCarrierCode() + getInputServiceNumber() + "&date=" + getInputRequestDate());
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

	    // let's do it!
	    main();

	});
	</script>
		
	
</head>
<body>
	<!--<canvas id="canvas" width="800" height="620">You do not have a canvas capable browser</canvas>-->
	
	<center>

	<div id="ui-container">
		<div id="ui-panel">
			<span>What's your flight code?</span><br>
			<input id="carrierCodeAndServiceNumber" value="<?php print($flightcode);?>" size="5">
			<input id="requestDate" value="<?php print($date_depart); ?>" size="12">
			<button class="shiny-blue" onClick="mapFlight();">Map Route</button>
			<br>
			<span style="font-size: 10pt;">Enter carrier code and flight number (ie: JQ7).<br>I'm feeling <a style="color: #FF0080" href="/?autoload<?php if($topsecret) { print("&topsecret"); }?>">lucky</a> | <a id="permalink" style="color: blue;" href="#">Link to results</a></span>
		</div>

		<div id="slider-container">
			<table width="400" border="0"><tr>
			<td width="70" nowrap><span style='color: #222; white-space: nowrap;'>Slide me</a></td>
			<td width="190"><div id="slider_holder" style="width: 100%;"></div></td>
			<td width="110" align="left" nowrap><div id="slider-time"></div></td>
			</tr></table>
		</div>

		<!--<div>
			<div class="vk_c">
				<span class="vk_h vk_gy"> Qantas Airways Flight 15 </span>
					<ol>
						<li>
							<div style="margin-top:2px">
								<span class="vk_gn vk_sh">Landed</span>
							</div>
							<table class="ts" style="width:100%;margin:12px 0">
								<tbody>
									<tr><td class="vk_ans vk_bk" style="width:0%">BNE</td>
										<td style="height:30px;padding:0 17px;width:100%">
											<div style="display:inline-block;position:relative;width:100%">
												<div style="height:2px;position:absolute;top:16px;width:100%;background-color:#3d9400"></div> 
												<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAAAMCAYAAAC0qUeeAAAAfElEQVQoz5WRUQqAIAyGd5KQ7tFz0fmCsttlJ0jqwaf1T2ZQJNnDh3P7kG0SM1MpV9AMVIERLCDoOUn+JiPRAg/4Bcl3UUZgwJYRE1I3ItsPMWFFdoWyIx2mRA6/Xy7teU7b8B+ij9vQPfdgz4iH1J8/aLQlp0Oveq+TcwII8fvh/Y1f4gAAAABJRU5ErkJggg==" style="height:9px;left:-6px;position:absolute;top:12px;widht:9px"> 
												<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAAAMCAYAAAC0qUeeAAAAfElEQVQoz5WRUQqAIAyGd5KQ7tFz0fmCsttlJ0jqwaf1T2ZQJNnDh3P7kG0SM1MpV9AMVIERLCDoOUn+JiPRAg/4Bcl3UUZgwJYRE1I3ItsPMWFFdoWyIx2mRA6/Xy7teU7b8B+ij9vQPfdgz4iH1J8/aLQlp0Oveq+TcwII8fvh/Y1f4gAAAABJRU5ErkJggg==" style="height:9px;position:absolute;right:-6px;top:12px;widht:9px">
												<div style="height:2px;position:absolute;top:16px;width:100%;background-color:#3d9400"></div>
												<div style="height:30px;min-width:6px;position:relative;width:100%">
												<div style="height:2px;position:absolute;top:16px;width:100%;background-color:#3d9400"></div>
												<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACcAAAAlCAYAAADBa/A+AAADj0lEQVRYw82YW0gUURiAtyIKC5+sh4igqz4I0YWiQKMHwUINTMs0SEvFlAhKyDDIMIuM1Ehr84aXtbxUmLcuLnnJ+z1TMyN3zTaz3Exh3chTdv5htTNnz+zVHXz4XmbO/P83/8w585+RzMzMSAzQgUFmcM5IPLMwdNKeTKz5PYlOFWxHQflb52j9VEnLdbkkSyTmYKkcMEgm98leh3DAOTzSHVjVixNLLpdMXPz2Hk8OkLVfZwnuFkMujEyq1ozoyQETWjUtlyqGHNBPJob3jpa7WH6IVb1AMeQSyaSZzZeZ1Xs32kLLVYkh50MmVf7oY8r5y7awqhdja7nlmFEy6ZGc9UzBpz1SWk6LcbJGzgsTj5HqCIaA1MAsMmly3Xmm3P67S9Gv6SlaMMEaOaHVXo5x1A0MIc/B+8WSA+LkJ1ixfOZbDqjG2GEcMBrynFfGakHBofF+Ok6JLeSASt3gIvL4NXmgoBwsN4w4URbJpdRHoptVoRylvWmswO91zB2rU5QIygHygYd0DAWmzQDtOqAIZzE7OLnQol28wM1Dz412IH/+IuR2f4WgnHuqPTKzo9GDk3s9WKw368Y0X4xeHFt53GD1pA0XrJeD6e8mteMFDshz4tokQxfTN8Xi888P1skB0RXeeoE9M1ahY7LNyC93owCbjMrB4w0p3MlNEkOcLNjGcenZYTQyqeTL1StKjSYSC3hqPLlOVfWCkQN4cvGvQswO4Jq8yCZimS0xfDl6xYcZ26Wq4bqQj2PdeijUvaisL8NoIuieG5XlqEFZxr06szQqKyjKOSA2b0KwHikcMzabhHq7Wa689Ld+KYFOlgya33nLpIthgyMkti9lMdJOa6yXi3nhhyKeuKDTj/aipNozJl34RlVrsGqFXYnWiKlhF2fKh78F40zvxBJqIgTFjuZsYMWJxXgQHKA4qMPZ1K4EOuCdmJWY74b2sCQ9XxvpOE3z3TKN6vYPMCaIPDeo7hEUiyrzZMUKtlQuHPMAU4Opw9zG+GLWEAPTyWRpTdGCcow9bKYtNzjLMCoyIXxvWWJ57TdYVdtjSzlfMtnw+ABTDN5BS/6bWCuXxFt48aeFJQdLCyXWIcammteeQ1tDi0WWuLOqFmZruXAyIXTHrKqNT32jxXLE+JGTRyZ93H1HTyy79Sqraq5iyA2RSb2z1i6Yn4cOZNIJ7RgKkDn+B3erbcNyWqwbs2S+5P4BOBRlVuj7kZ0AAAAASUVORK5CYII=" style="height:28px;position:absolute;right:-10px;top:2px;width:28px"> </div>
											</div>
										</td>
										<td class="vk_ans vk_bk" style="width:0%">LAX</td>
									</tr>
								</tbody>
							</table>
							<table class="ts" style="width:100%">
								<tbody><tr> <td>  <div class="vk_gy vk_sh" style="border-bottom:1px solid #eee;padding:0 0 3px 0;width:100%"> Departs Brisbane, yesterday </div>  <table class="ts" style="margin:2px 0;text-align:left;width:100%"> <tbody><tr class="vk_gy"> <td class="vk_txt" style="width:55%">  Scheduled 10:30 am </td> <td class="vk_txt" style="width:25%">Terminal</td> <td class="vk_txt" style="text-align:right">Gate</td> </tr> <tr> <td class="vk_h vk_bk">11:00 am</td> <td class="vk_h vk_gy">I</td> <td class="vk_h vk_gy" style="text-align:right">-</td> </tr> </tbody></table>   </td> <td style="width:10%"></td> <td>  <div class="vk_gy vk_sh" style="border-bottom:1px solid #eee;padding:0 0 3px 0;width:100%"> Arrives Los Angeles, today </div>  <table class="ts" style="margin:2px 0;text-align:left;width:100%"> <tbody><tr class="vk_gy"> <td class="vk_txt" style="width:55%">  Scheduled 6:35 am </td> <td class="vk_txt" style="width:25%">Terminal</td> <td class="vk_txt" style="text-align:right">Gate</td> </tr> <tr> <td class="vk_h vk_bk">6:30 am</td> <td class="vk_h vk_gy">B</td> <td class="vk_h vk_gy" style="text-align:right">-</td> </tr> </tbody></table>   </td> </tr> </tbody></table>   </li> </ol>  </div>
		</div>-->



	</div>
	
	<div id="map_container">
		<div id="map_canvas">Loading cool stuff...</div>
    </div>

    <div id="results-panel"></div>


	<div id="loading-page">
		<img src='/images/loading.gif' width='32' height='32' style='margin-bottom: -10px; padding-right: 10px;'>
		<span id="loading-message">Loading...</span>
	</div>
	
	<!--<div id="welcome">
		<a href="javascript:void(0);" onClick="hideWelcomeWindow();">Continue</a>
		<center><img src="/images/thack-singapore-logo1.jpg"></center>
		<p>Because flying with the sun in your face isn't cool!</p>
		<p>Want to choose the <strong>best</strong> side of the aircraft to fly on?</p>
		<p>Want to make sure you can <strong>see the sunset</strong> over New York or <strong>watch the sunrise</strong> over the Pacific?</p>
		<p>SunFlight.net shows you where the sun will be during your journey, so you can choose the best side of the aircraft to be seated!</p>
		<p style="font-size: smaller; color: #888;">Notes: Google Chrome only at the moment. Most likely a few bugs!</p>
		<p style="font-size: smaller;">Powered by <img width="80" style="margin-bottom: -12px;" src="/images/oag-aviation.jpg"> &nbsp; OnDemand</p>
		
	</div>-->
	<!--<div id="info" class="shadow">A <a href="http://tnooz.com">Tnooz.com tHack</a> at <a href="http://www.webintravel.com">Web In Travel Singapore</a> by <a href="http://twitter.com/aussie_ian">@aussie_ian</a> and <a href="http://twitter.com/dansync">@dansync</a>.
		<br>Powered by <a href="http://www.oagaviation.com/Solutions/Aviation-Data/OAG-Schedules-Data/OAG-OnDemand">OAG OnDemand</a>. 
		Shouts to <a href="http://www.travelmassive.com">#travelmassive</a> world-wide!
		<a href="javascript:void(0);" onClick="aboutClicked = true; showWelcomeWindow();">About</a>
	</div>-->
	<!--<div id="hypnotoad">
		<img width="600" height="600" src="/images/hypnotoad.gif">
	</div>-->
	<!--<div id="topsecret">
		<a href="/?topsecret&autoload">Enable top secret ad engine</a>
	</div>-->
	<div id="debug">
		Minutes travelled: <input id="minutes_travelled"><br>
		Current flight segment: <input id="flight_segment"><br>
		Minute of segment: <input id="minute_of_segment"><br>
		Sun position from plane: <input id="sfcalc_sun_side"><br>
		Time of day: <input id="sfcalc_tod"><br>
		Sun East West: <input id="sfcalc_sun_east_west"><br>
		Azimuth from North: <input id="sfcalc_azimuth_from_north"><br>
		Bearing from North: <input id="sfcalc_bearing_from_north"><br>
		Cached result: <input id="cached_result"><br>
	</div>
	
	<!-- google anlaytics -->
	<script type="text/javascript">

	  var _gaq = _gaq || [];
	  _gaq.push(['_setAccount', 'UA-26345499-1']);
	  _gaq.push(['_trackPageview']);

	  (function() {
	    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	  })();

	</script>
	<!-- analytics -->
	
	</center>
	
</body>
