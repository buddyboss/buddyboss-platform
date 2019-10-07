/*jshint unused:false*/
/* global google */
function bp_ps_clear_radio(container) {
	var event = new Event( 'change' );

	container = document.getElementById( container );

	var radioButtons = container.getElementsByTagName( 'input' );
	for (var i = 0; i < radioButtons.length; i++) {
		radioButtons[i].checked = '';
		radioButtons[i].dispatchEvent( event );
	}
}

function bp_ps_autocomplete(input, lat, lng) {
	input            = document.getElementById( input );
	var options      = {types: ['geocode']};
	var autocomplete = new google.maps.places.Autocomplete( input, options );
	google.maps.event.addListener(
		autocomplete,
		'place_changed',
		function() {
			var place                            = autocomplete.getPlace();
			document.getElementById( lat ).value = place.geometry.location.lat();
			document.getElementById( lng ).value = place.geometry.location.lng();
		}
	);
}

function bp_ps_locate(input, lat, lng) {
	if (navigator.geolocation) {
		var options = {timeout: 5000};
		navigator.geolocation.getCurrentPosition(
			function(position) {
				document.getElementById( lat ).value = position.coords.latitude;
				document.getElementById( lng ).value = position.coords.longitude;
				bp_ps_address( position, input );
			},
			function(error) {
				alert( 'ERROR ' + error.code + ': ' + error.message );
			},
			options
		);
	} else {
		alert( 'ERROR: Geolocation is not supported by this browser' );
	}
}

function bp_ps_address(position, input) {
	var geocoder = new google.maps.Geocoder();
	var latlng   = {lat: position.coords.latitude, lng: position.coords.longitude};
	geocoder.geocode(
		{'location': latlng},
		function(results, status) {
			if (status === 'OK') {
				if (results[0]) {
					document.getElementById( input ).value = results[0].formatted_address;
				} else {
					alert( 'ERROR: Geocoder found no results' );
				}
			} else {
				alert( 'ERROR: Geocoder status: ' + status );
			}
		}
	);
}

/**
 * Reset the search form value in members screen
 * Need to add this function here for
 * Network Search component is deactivate
 *
 * @param ele
 */
function bp_ps_clear_form_elements( ele ) {
	var $form = jQuery( ele ).closest( 'form' );
	var event = new Event( 'change' );

	$form.find( ':input' ).each(
		function () {
			switch ( this.type ) {
				case 'password':
				case 'select-multiple':
				case 'select-one':
				case 'text':
				case 'email':
				case 'date':
				case 'url':
				case 'search':
				case 'textarea':
					jQuery( this ).val( '' );
					  break;
				case 'checkbox':
				case 'radio':
					this.checked = false;
					this.dispatchEvent( event );
					 break;
			}
		}
	);

	jQuery.removeCookie( 'bp_ps_request', {path: '/'} );
	$form.find( '.submit' ).trigger( 'click' );
}
