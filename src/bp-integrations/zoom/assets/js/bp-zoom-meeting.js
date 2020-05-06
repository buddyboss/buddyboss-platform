/* jshint browser: true */
/* global bp, ZoomMtg, bp_zoom_meeting_vars, bp_select2 */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	/**
	 * [Zoom description]
	 * @type {Object}
	 */
	bp.Zoom = {
		/**
		 * [start description]
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();

			// Listen to events ("Add hooks!")
			this.addListeners();
		},

		/**
		 * [setupGlobals description]
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$('.meeting-item-container').on('click', '.load-more a', this.loadMoreMeetings.bind(this));
			$(document).on('click', '#bp-zoom-meeting-form-submit', this.updateMeeting.bind(this));
			$('.meeting-item-wrap').on('click', '.bp-zoom-meeting-delete', this.deleteMeeting.bind(this));
			$('.meeting-item-wrap').on('click', '#bp-zoom-meeting-view-recordings', this.viewRecordings.bind(this));
			$('#bp-zoom-single-meeting').on('click', '.toggle-password', this.togglePassword.bind(this));
			$('#bp-zoom-single-meeting').on('click', '#copy-invitation', this.copyInvitation.bind(this));
			$(document).on('click', '.join-meeting-in-browser', this.joinMeetingInBrowser.bind(this));
			$(document).on('click', '#bp-add-meeting', this.openCreateMeetingModal.bind(this));
			$(document).on('click', '#bp-meeting-create-meeting-close', this.closeCreateMeetingModal.bind(this));
			$(document).on('click', '.play_btn', this.openRecordingModal.bind(this));
			$(document).on('click', '.bb-close-model', this.closeRecordingModal.bind(this));
		},

		loadMoreMeetings: function (e) {
			var _this = $(e.currentTarget);
			e.preventDefault();

			if (_this.hasClass('loading')) {
				return false;
			}

			_this.addClass('loading');

			$.ajax({
				type: 'GET',
				url: bp_zoom_meeting_vars.ajax_url,
				data: {action: 'zoom_meeting_load_more', 'acpage': this.getLinkParams($(this).prop('href'), 'acpage')},
				success: function (response) {
					if (typeof response.data !== 'undefined' && response.data.contents) {
						_this.closest('.load-more').replaceWith(response.data.contents);
					}
				}
			});
		},

		updateMeeting: function (e) {
			var _this = $(e.currentTarget);
			e.preventDefault();

			if (_this.hasClass('loading')) {
				return false;
			}

			_this.addClass('loading');

			var form_data = $('#bp-new-zoom-meeting-form').serializeArray();
			var data = {
				'action': 'zoom_meeting_add',
			};
			for (var i in form_data) {
				if (data.hasOwnProperty(form_data[i].name)) {
					if (!$.isArray(data[form_data[i].name])) {
						data[form_data[i].name] = [data[form_data[i].name]];
					}
					data[form_data[i].name] = data[form_data[i].name].concat(form_data[i].value);
				} else {
					data[form_data[i].name] = form_data[i].value;
				}
			}

			$.ajax({
				type: 'POST',
				url: bp_zoom_meeting_vars.ajax_url,
				data: data,
				success: function (response) {
					if (response.success) {
						if (typeof response.data !== 'undefined' && response.data.redirect_url) {
							window.location.href = response.data.redirect_url;
							return false;
						}
					} else {
						console.log(response);
						_this.removeClass('loading');
					}
				}
			});
		},

		deleteMeeting: function (e) {
			var target = $(e.target), meeting_item = target.closest('.meeting-item-wrap'),
				meeting_id = meeting_item.data('meeting-id'), id = meeting_item.data('id'),
				nonce = target.data('nonce');
			e.preventDefault();

			$.ajax({
				type: 'POST',
				url: bp_zoom_meeting_vars.ajax_url,
				data: {
					'action': 'zoom_meeting_delete',
					'meeting_id': meeting_id,
					'id': id,
					'_wpnonce': nonce,
				},
				success: function (response) {
					if (true === response.data.deleted) {
						if ('1' === bp_zoom_meeting_vars.is_single_meeting && bp_zoom_meeting_vars.group_meetings_url !== '') {
							window.location.href = bp_zoom_meeting_vars.group_meetings_url;
							return false;
						} else {
							$(meeting_item).remove();
						}
					}
				}
			});
		},

		viewRecordings: function (e) {
			var target = $(e.target), meeting_item = target.closest('.meeting-item');
			e.preventDefault();

			$.ajax({
				type: 'GET',
				url: bp_zoom_meeting_vars.ajax_url,
				data: {
					'action': 'zoom_meeting_recordings',
					'meeting_id': meeting_item.data('meeting-id'),
				},
				success: function (response) {
					if (response.success && response.data.recordings) {
						meeting_item.parent().find('.recording-list').html(response.data.recordings);
					} else {
						meeting_item.parent().find('.recording-list').html(response.data.error);
					}
				},
			});
		},

		togglePassword: function (e) {
			var _this = $(e.currentTarget), meeting_row = _this.closest('.single-meeting-item');
			e.preventDefault();

			if (_this.hasClass('show-pass')) {
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
		},

		copyInvitation: function (e) {
			var _this = $(e.currentTarget);
			e.preventDefault();

			var textArea = document.createElement('textarea');
			textArea.value = _this.data('join-url');
			document.body.appendChild(textArea);
			textArea.select();
			try {
				var successful = document.execCommand('copy');
				//var msg = successful ? 'successful' : 'unsuccessful';
				if (successful) {
					_this.addClass('copied');

					setTimeout(function () {
						_this.removeClass('copied');
					}, 3000);
				}
			} catch (err) {
				console.log('Oops, unable to copy');
			}
			document.body.removeChild(textArea);
		},

		getLinkParams: function (url, param) {
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
		},

		joinMeetingInBrowser: function (e) {
			var _this = $(e.currentTarget);
			e.preventDefault();

			ZoomMtg.preLoadWasm();
			ZoomMtg.prepareJssdk();

			//var testTool = window.BpZoomTestTool;
			//var meetingId = $(this).data('meeting-id');
			//var meetingPwd = $(this).data('meeting-pwd');
			//var stmUserName = 'Local' + ZoomMtg.getJSSDKVersion()[0] + testTool.detectOS() + '#' + testTool.getBrowserInfo();

			$('#zmmtg-root').addClass('active');
			var meetConfig = {
				apiKey: bp_zoom_meeting_vars.bp_zoom_key,
				apiSecret: bp_zoom_meeting_vars.bp_zoom_secret,
				meetingNumber: _this.data('meeting-id'),
				userName: 'TEST USER',
				passWord: _this.data('meeting-pwd'),
				leaveUrl: bp_zoom_meeting_vars.home_url,
				role: _this.data('is-host') == '1' ? 1 : 0,
			};


			var signature = ZoomMtg.generateSignature({
				meetingNumber: meetConfig.meetingNumber,
				apiKey: meetConfig.apiKey,
				apiSecret: meetConfig.apiSecret,
				role: meetConfig.role,
				success: function (res) {
					console.log(res.result);
				}
			});

			ZoomMtg.init({
				leaveUrl: meetConfig.leaveUrl,
				isSupportAV: true,
				success: function () {
					ZoomMtg.join(
						{
							meetingNumber: meetConfig.meetingNumber,
							userName: meetConfig.userName,
							signature: signature,
							apiKey: meetConfig.apiKey,
							passWord: meetConfig.passWord,
							success: function (res) {
								console.log('join meeting success');
							},
							error: function (res) {
								console.log(res);
							}
						}
					);
				},
				error: function (res) {
					console.log(res);
				}
			});
		},

		openCreateMeetingModal: function (e) {
			e.preventDefault();

			$('#bp-meeting-create').show();

			$('#bp-zoom-meeting-start-date').datetimepicker({
				format: 'Y-m-d H:i:s',
				minDateTime: 0,
			});

			$('#bp-zoom-meeting-alt-host-ids').select2({
				minimumInputLength: 0,
				closeOnSelect: true,
				language: (typeof bp_select2 !== 'undefined' && typeof bp_select2.lang !== 'undefined') ? bp_select2.lang : 'en',
				dropdownCssClass: 'bb-select-dropdown',
				containerCssClass: 'bb-select-container',
			});
		},

		closeCreateMeetingModal: function (event) {
			event.preventDefault();

			$('#bp-meeting-create').hide();
		},

		openRecordingModal: function(e) {
			var _this = $(e.currentTarget);
			e.preventDefault();

			_this.closest('.video_link').find('.bb-media-model-wrapper').show();
		},

		closeRecordingModal: function(e) {
			e.preventDefault();

			$('.bb-media-model-wrapper').hide();
		}
	};

	// Launch BP Zoom
	bp.Zoom.start();

} )( bp, jQuery );
