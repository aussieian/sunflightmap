$(document).ready(function() {
	
mapSunPath = function(flightPaths, map, start_time_at_dateline, duration_minutes) {
	
		//alert("mapping sun path...");
		
		var fromLatLng = new google.maps.LatLng(0, 0);
		var toLatLng = new google.maps.LatLng(0, -100);

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
