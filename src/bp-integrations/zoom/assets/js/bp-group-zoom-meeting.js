/* jshint undef: false */
/* @version 1.0.0 */
( function( $ ){

	$( document ).ready( function() {
		$( document ).on( 'click', '#save-meeting', function(e){
			e.preventDefault();

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					'action': 'zoom_meeting_add',
					'group_id': $('#group-id').val(),
					'user_id': $('#user-id').val(),
					'start_date': $('#start_date').val(),
					'timezone': $('#timezone').val(),
					'duration': $('#duration').val(),
					'join_before_host': $('#join_before_host:checked').length,
					'host_video': $('#option_host_video:checked').length,
					'participants_video': $('#participants_video:checked').length,
					'mute_participants': $('#mute_participants:checked').length,
					'auto_recording': $('#auto_recording').val(),
				},
				success: function () {
				}
			});
		} );
	} );

} )( jQuery );
