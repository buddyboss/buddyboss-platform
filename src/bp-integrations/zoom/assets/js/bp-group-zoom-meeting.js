/* jshint undef: false */
/* @version 1.0.0 */
( function( $ ){

	$( document ).ready( function() {

		$('.meeting-item-container').on( 'click', '.load-more a', function(e){
			var _this = $(this);
			e.preventDefault();

			if ( _this.hasClass('loading') ) {
				return false;
			}

			_this.addClass('loading');

			$.ajax({
				type: 'GET',
				url: bp_group_zoom_meeting_vars.ajax_url,
				data: { action: 'zoom_meeting_load_more', 'acpage' : bpZoomGetLinkParams($(this).prop( 'href' ), 'acpage') },
				success: function ( response ) {
					if ( typeof response.data !== 'undefined' && response.data.contents ) {
						_this.closest('.load-more').replaceWith(response.data.contents);
					}
				}
			});
		});

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

		$('#bp-zoom-meeting-alt-host-ids').select2({
			minimumInputLength: 0,
			closeOnSelect: true,
			language: ( typeof bp_select2 !== 'undefined' && typeof bp_select2.lang !== 'undefined' ) ? bp_select2.lang : 'en',
			dropdownCssClass: 'bb-select-dropdown',
			containerCssClass: 'bb-select-container',
		});

		$( document ).on( 'click', '#bp-zoom-meeting-form-submit', function(e){
			e.preventDefault();

			if ( $(this).hasClass('loading') ) {
				return false;
			}

			$(this).addClass('loading');

			var form_data = $('#bp-new-zoom-meeting-form').serializeArray();
			var data = {
				'action': 'zoom_meeting_add',
			};
			for( var i in form_data ) {
				if ( data.hasOwnProperty(form_data[i].name) ) {
					if( ! $.isArray(data[form_data[i].name]) ) {
						data[form_data[i].name] = [data[form_data[i].name]];
					}
					data[form_data[i].name] = data[form_data[i].name].concat( form_data[i].value );
				} else {
					data[form_data[i].name] = form_data[i].value;
				}
			}

			$.ajax({
				type: 'POST',
				url: bp_group_zoom_meeting_vars.ajax_url,
				data: data,
				success: function ( response ) {
					if ( typeof response.data !== 'undefined' && response.data.redirect_url ) {
						window.location.href = response.data.redirect_url;
						return false;
					}
				}
			});
		} );

		$( '.meeting-item-wrap' ).on('click','#bp-zoom-meeting-view-recordings',function(e){
			var target = $(e.target), meeting_item = target.closest('.meeting-item');
			e.preventDefault();

			$.ajax({
				type: 'GET',
				url: bp_group_zoom_meeting_vars.ajax_url,
				data: {
					'action': 'zoom_meeting_recordings',
					'meeting_id': meeting_item.data('meeting-id'),
				},
				success: function (response) {
					if ( response.success && response.data.recordings){
						meeting_item.parent().find('.recording-list').html(response.data.recordings);
					} else {
						meeting_item.parent().find('.recording-list').html(response.data.error);
					}
				},
			});
		});

		$('.meeting-item-wrap').on( 'click', '.bp-zoom-meeting-delete', function(e){
			var target = $( e.target ), meeting_item = target.closest('.meeting-item-wrap'), meeting_id = meeting_item.data('meeting-id'), id = meeting_item.data('id'), nonce = target.data('nonce');
			e.preventDefault();

			$.ajax({
				type: 'POST',
				url: bp_group_zoom_meeting_vars.ajax_url,
				data: {
					'action': 'zoom_meeting_delete',
					'meeting_id': meeting_id,
					'id': id,
					'_wpnonce': nonce,
				},
				success: function (response) {
					if ( true === response.data.deleted ) {
						if ( '1' === bp_group_zoom_meeting_vars.is_single_meeting && bp_group_zoom_meeting_vars.group_meetings_url !== '' ) {
							window.location.href = bp_group_zoom_meeting_vars.group_meetings_url;
							return false;
						} else {
							$(meeting_item).remove();
						}
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

			var textArea = document.createElement('textarea');
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

		/**
		 * [getLinkParams description]
		 * @param  {[type]} url   [description]
		 * @param  {[type]} param [description]
		 * @return {[type]}       [description]
		 */
		function bpZoomGetLinkParams( url, param ) {
			var qs;
			if (url) {
				qs = (-1 !== url.indexOf('?')) ? '?' + url.split('?')[1] : '';
			} else {
				qs = document.location.search;
			}

			if (!qs) {
				return null;
			}

			var params = qs.replace(/(^\?)/, '').split('&').map(function (n) {
				return n = n.split('='), this[n[0]] = n[1], this;
			}.bind({}))[0];

			if (param) {
				return params[param];
			}

			return params;
		}

	} );

} )( jQuery );
