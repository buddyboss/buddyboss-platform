/* jshint undef: false */
/* @version 1.0.0 */
( function( $ ){

	$( document ).ready( function() {

		$('#bp-zoom-meeting-start-date').datetimepicker({
			format:'Y-m-d H:i:s',
			minDateTime:0,
		});

		$( document ).on( 'click', '#bp-zoom-meeting-form-submit', function(e){
			e.preventDefault();

			$(this).addClass('loading');

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

		$( '.meeting-item' ).on('click','#bp-zoom-meeting-view-recordings',function(e){
			var target = $(e.target), meeting_item = target.closest('.meeting-item');
			e.preventDefault();

			$.ajax({
				type: 'GET',
				url: ajaxurl,
				data: {
					'action': 'zoom_meeting_recordings',
					'meeting_id': meeting_item.data('meeting-id'),
				},
				success: function (response) {
					if ( response.success && response.data.recordings){
						meeting_item.find('.recording-list').html(response.data.recordings);
					} else {
						meeting_item.find('.recording-list').html(response.data.error);
					}
				},
			});
		});

		$('.meeting-item').on( 'click', '#bp-zoom-meeting-delete', function(e){
			var target = $( e.target ), meeting_item = target.closest('.meeting-item'), meeting_id = meeting_item.data('meeting-id'), id = meeting_item.data('id'), nonce = target.data('nonce');
			e.preventDefault();

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					'action': 'zoom_meeting_delete',
					'meeting_id': meeting_id,
					'id': id,
					'_wpnonce': nonce,
				},
				success: function (response) {
					if ( true === response.data.deleted ) {
						$(meeting_item).remove();
					}
				}
			});
		});
	} );

} )( jQuery );
