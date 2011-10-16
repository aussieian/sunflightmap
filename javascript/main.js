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
		$.getJSON("/ajax/ajax-flight-route.php",
		{
			carrier_code: "JQ",
			service_number: "7",
			request_date: "2011-10-14"
		},
		function(data) {
			alert(data);
		});
	}	
	
	// let's do it!
	main();
	
});