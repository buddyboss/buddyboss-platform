/* jshint undef: false */
/* @version 1.0.0 */
( function( $ ){

	$( document ).ready( function() {

		$('#bp-zoom-meeting-start-date').datetimepicker({
			format:'Y-m-d H:i:s',
			minDateTime:0,
		});

		$('#bp-zoom-meeting-host').select2({
			minimumInputLength: 0,
			closeOnSelect: true,
			language: ( typeof bp_select2 !== 'undefined' && typeof bp_select2.lang !== 'undefined' ) ? bp_select2.lang : 'en',
			dropdownCssClass: 'bb-select-dropdown',
			containerCssClass: 'bb-select-container',
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

		$('#bp-zoom-single-meeting').on('click', '.toggle-password',function(e){
			var _this = $(this), meeting_row = _this.closest('.single-meeting-item');
			e.preventDefault();

			if ( _this.hasClass( 'show-pass' ) ) {
				_this.hide();
				meeting_row.find('.toggle-password.hide-pass').show();
				meeting_row.find('.hide-password').hide();
				meeting_row.find('.show-password').show();
			} else {
				_this.hide();
				meeting_row.find('.toggle-password.show-pass').show();
				meeting_row.find('.show-password').hide();
				meeting_row.find('.hide-password').show();
			}
		});

		$('#bp-zoom-single-meeting').on('click', '#copy-invitation',function(e){
			var _this = $(this);
			e.preventDefault();

			var textArea = document.createElement("textarea");
			textArea.value = _this.data('join-url');
			document.body.appendChild(textArea);
			textArea.select();
			try {
				var successful = document.execCommand('copy');
				//var msg = successful ? 'successful' : 'unsuccessful';
				if ( successful ) {
					_this.addClass('copied');

					setTimeout(function(){
						_this.removeClass('copied');
					},3000);
				}
			} catch (err) {
				console.log('Oops, unable to copy');
			}
			document.body.removeChild(textArea);
		});
	} );

} )( jQuery );
