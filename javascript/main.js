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

	function main() {
		google.maps.event.addDomListener(window, 'load', initializeMap);
	}
	
	// let's do it!
	main();
	
});