$(document).ready(function() {

	var map;

	function initializeMap() {
		var myOptions = {
			zoom: 8,
			center: new google.maps.LatLng(-34.397, 150.644),
			mapTypeId: google.maps.MapTypeId.ROADMAP
		};
		map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
	}

	main = function() {
		google.maps.event.addDomListener(window, 'load', initializeMap);
	}
	
	mapFlight = function() {
		$('#loading-page').show();
		$.getJSON("/ajax/ajax-flight-route.php?callback=?",
		{
			carrier_code: "JQ",
			service_number: "7",
			request_date: "2011-10-14"
		},
		function(data) {
			// get back jsonp
			// flightmap({"from_airport": "MEL","from_city": "Melbourne","from_lat": -37.673333,"from_lon": 144.843333,"to_airport": "SIN","to_city": "Singapore","to_lat": 1.350189,"to_lon": 103.994433,"depart_time": "2011-10-16T12:00:00","elapsed_time": 470})
			alert(data.error);
			$('#loading-page').hide();
		});
	}	
	
	// let's do it!
	main();
	
});