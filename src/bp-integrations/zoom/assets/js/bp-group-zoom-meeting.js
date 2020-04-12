/* jshint undef: false */
/* @version 1.0.0 */
( function( $ ){

	$( document ).ready( function() {
		$( document ).on( 'click', '#bp-zoom-meeting-form-submit', function(e){
			e.preventDefault();

			var form_data = $('#bp-new-zoom-meeting-form').serializeArray();
			var data = {
				'action': 'zoom_meeting_add',
			};
			for( var i in form_data ) {
				data[form_data[i].name] = form_data[i].value;
			}

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data,
				success: function ( response ) {
					if ( typeof response.data !== 'undefined' && response.data.redirect_url ) {
						window.location.href = response.data.redirect_url;
						return false;
					}
				}
			});
		} );

		$( document ).on('click','#meeting-item',function(e){
			var target = $(e.currentTarget);
			$.ajax({
				type: 'GET',
				url: ajaxurl,
				data: {
					'action': 'zoom_meeting_recordings',
					'meeting_id': target.data('meeting-id'),
				},
				success: function (response) {
					if (response.data.recordings){
						target.closest('#meeting-item').find('.recording-list').html(response.data.recordings);
					}
				}
			});
		});
	} );

} )( jQuery );
