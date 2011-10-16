$(document).ready(function() {
	
mapSunPath = function(flightPaths, map, start_time_at_gmt, duration_minutes, minutes_travelled) {
	
		//alert("mapping sun path...");
		
		// Sun is directly overhead LatLng(0, 0) at 12:00:00 midday
		// 1440 minutes / 1 minute = 0.25 degrees 
		// Assuming maximum trip duration of 24 hours / single leg
		
		// Calculate sun's starting longitude from the start time at gmt
		from_deg = 151;
		
		duration_deg = duration_minutes * 0.25 * (minutes_travelled / duration_minutes);
		to_deg = from_deg - duration_deg;
		
		// Starting longitude is positive
		/*if (from_deg >= 0)
		{
			// End longitude will be negative
			if ((180 - from_deg) < duration_deg)
			{
				// Ending longitude = -180 degrees + duration in degrees - (180 - starting position in degrees)
				toLng = -360 + duration_deg + from_deg
			}
			// End longitude will be positive
			else
			{
				toLng = from_deg + duration_deg;
			}
		}
		// Starting longitude is negative
		else
		{
			// End longitude will be negative
			if ((180 + from_deg) < duration_deg)
			{
				toLng = from_deg - duration_deg
			}
			// Ending longitude will be positive
			else
			{
				// Ending longitude = 180 degrees - duration in degrees + (180 + starting position in degrees)
				toLng = 360 - duration_deg + from_deg
			}
		}*/
		
		var fromLatLng = new google.maps.LatLng(0, from_deg);
		var toLatLng = new google.maps.LatLng(0, to_deg);

		/*var flightPath = new google.maps.Polyline({
			path: [fromLatLng, toLatLng],
			strokeColor: "#0000ff",
			strokeOpacity: 1.0,
			strokeWeight: 2,
			geodesic: false,
			clickable: false 
		});
		
		// draw start marker
		var sunStartMarker = new google.maps.Marker({
	        position: fromLatLng,
	        map: map,
	        title: 'Sun Start Marker: ' + from_deg
	    }); 
		markers.push(sunStartMarker);*/
			
		// draw end marker
		if (sunMarker != null) { 
			sunMarker.setMap(null);
		}
		
		sunMarker = new google.maps.Marker({
	        position: toLatLng,
	        map: map,
	        title: 'Sun End Marker: ' + to_deg
	    }); 
		//markers.push(sunEndMarker);
			
		//flightPaths.push(flightPath);
		//flightPath.setMap(map);
}

});
