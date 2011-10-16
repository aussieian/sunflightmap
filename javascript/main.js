$(document).ready(function() {

	var map;

	function initializeMap() {

		// check if we have google loaded
		if (typeof google === 'undefined') {
			alert("Sorry, we're having trouble loading Google Maps. The flight map will be disabled.");
			return;
		}

		//try {
			startLatLng = new google.maps.LatLng("50,50");
			var myOptions = {
				disableDefaultUI: false,
				scaleControl: true,
				streetViewControl: false,
				panControl: true,
				panControlOptions: {
					position: google.maps.ControlPosition.LEFT_CENTER
				},
				zoomControlOptions: {
					position: google.maps.ControlPosition.LEFT_CENTER
				},
				maxZoom: 8,
				minZoom: 1,
				mapTypeControl: false,
				scrollwheel: false,
				zoom: 4,
				center: startLatLng,
				mapTypeId: google.maps.MapTypeId.TERRAIN
			}
			map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
			
		//} catch(err) {
		//	alert("Sorry, we're having trouble loading Google Maps. Please try again later.");
		//}

	}
	
	function main() { 
		initializeMap();
	}
	
	// let's do it!
	main();
	
});