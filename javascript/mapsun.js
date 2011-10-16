$(document).ready(function() {
	
mapSunPath = function(flightPaths, map, start_time_at_gmt, duration_minutes) {
	
		//alert("mapping sun path...");
		
		// Sun is directly overhead LatLng(0, 0) at 12:00:00 midday
		// 1440 minutes / 1 minute = 0.25 degrees 
		// Assuming maximum trip duration of 24 hours / single leg
		
		// Calculate sun's starting longitude from the start time at gmt
		$fromLng = 0
		$duration_minutes = 720;
		
		$duration_deg = $duration_minutes * 0.25;
		
		// Starting longitude is positive
		if ($fromLng >= 0)
		{
			// End longitude will be negative
			if ((180 - $fromLng) < $duration_deg)
			{
				// Ending longitude = -180 degrees + duration in degrees - (180 - starting position in degrees)
				$toLng = -360 + $duration_deg + $from_deg
			}
			// End longitude will be positive
			else
			{
				$toLng = $fromLng + $duration_deg;
			}
		}
		// Starting longitude is negative
		else
		{
			// End longitude will be negative
			if ((180 + $fromLng) < $duration_deg)
			{
				$toLng = $fromLng - $duration_deg
			}
			// Ending longitude will be positive
			else
			{
				// Ending longitude = 180 degrees - duration in degrees + (180 + starting position in degrees)
				$toLng = 360 - $duration_deg + $from_deg
			}
		
		var fromLatLng = new google.maps.LatLng(0, $fromLng);
		var toLatLng = new google.maps.LatLng(0, $toLng);

		var flightPath = new google.maps.Polyline({
			path: [fromLatLng, toLatLng],
			strokeColor: "#0000ff",
			strokeOpacity: 1.0,
			strokeWeight: 2,
			geodesic: false,
			clickable: false 
		});

		flightPaths.push(flightPath);
		flightPath.setMap(map);
}

});
