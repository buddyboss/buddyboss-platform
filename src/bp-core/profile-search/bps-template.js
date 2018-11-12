
function bps_clear_radio(container) {

	container = document.getElementById (container);

	var radioButtons = container.getElementsByTagName ('input');
	for (i = 0; i < radioButtons.length; i++) {
		radioButtons[i].checked = '';
	}
}

function bps_autocomplete(input, lat, lng) {
	input = document.getElementById(input);
	var options = {types: ['geocode']};
	var autocomplete = new google.maps.places.Autocomplete(input, options);
	google.maps.event.addListener(autocomplete, 'place_changed', function() {
		var place = autocomplete.getPlace();
		document.getElementById(lat).value = place.geometry.location.lat();
		document.getElementById(lng).value = place.geometry.location.lng();
	});
}

function bps_locate(input, lat, lng) {
	if (navigator.geolocation) {
		var options = {timeout: 5000};
		navigator.geolocation.getCurrentPosition(function(position) {
			document.getElementById(lat).value = position.coords.latitude;
			document.getElementById(lng).value = position.coords.longitude;
			bps_address(position, input);
		}, function(error) {
			alert('ERROR ' + error.code + ': ' + error.message);
		}, options);
	} else {
		alert('ERROR: Geolocation is not supported by this browser');
	}
}

function bps_address(position, input) {
	var geocoder = new google.maps.Geocoder;
	var latlng = {lat: position.coords.latitude, lng: position.coords.longitude};
	geocoder.geocode({'location': latlng}, function(results, status) {
		if (status === 'OK') {
			if (results[0]) {
				document.getElementById(input).value = results[0].formatted_address;
			} else {
				alert('ERROR: Geocoder found no results');
			}
		} else {
			alert('ERROR: Geocoder status: ' + status);
		}
	});
}
