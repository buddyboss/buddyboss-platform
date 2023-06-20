/* global wp, bp, BP_Nouveau, _, bp_select2 */
/* @version 3.0.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	bp.Nouveau = bp.Nouveau || {};

	bp.Models.ACReply = Backbone.Model.extend(
		{
			defaults: {
				gif_data: {}
			}
		}
	);

	/**
	 * [Nouveau description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.GroupInvites = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function() {
			this.views = new Backbone.Collection();

			var $group_invites_select = $( 'body' ).find( '#group-invites-send-to-input' );
			var page                  = 1;
			var scope                 = '';

			// Activate bp_mentions
			this.addSelect2( $group_invites_select );

			var feedbackParagraphTagClass    = $( '#group-invites-container .bb-groups-invites-right .bp-invites-feedback .bp-feedback' );
			var feedbackParagraphTagSelector = $( '#group-invites-container .bb-groups-invites-right .bp-invites-feedback .bp-feedback p' );
			var feedbackDivHide              = $( '#group-invites-container .bb-groups-invites-right .bp-invites-feedback' );
			var feedbackInviteColumn         = $( '#group-invites-container .group-invites-column .bp-invites-feedback .bp-feedback' );
			var feedbackInvitePTag           = $( '#group-invites-container .group-invites-column .bp-invites-feedback .bp-feedback p' );

			// Feedback Selector left.
			var feedbackSelectorLeftClass 		 = $( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback .bp-feedback' );
			var feedbackParagraphTagSelectorLeft = $( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback .bp-feedback p' );
			var listSelector 					 = $( '.group-invites-members-listing #members-list' );
			var lastSelector 					 = $( '.group-invites-members-listing .last' );
			var memberInvitedList 				 = $( '.members.bp-invites-content #members-list' );
			var subNavFilterLast  				 = $( '#group-invites-container .group-invites-column .subnav-filters div .last' );

			// Set Feedback for all members.
			feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
			feedbackSelectorLeftClass.addClass( 'loading' );
			feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_invites.loading );
			// feedbackParagraphTagClass.attr( 'class', 'bp-feedback' );
			// feedbackParagraphTagClass.addClass( 'help' );
			// feedbackParagraphTagSelector.html( '' );
			// feedbackParagraphTagSelector.html( BP_Nouveau.group_invites.invites_form );

			$( '#group-invites-container .bb-groups-invites-right #send_group_invite_form .bb-groups-invites-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field' ).prop( 'disabled', true );
			$( document ).on(
				'click',
				'#group-invites-container .bb-groups-invites-right #send_group_invite_form .bb-groups-invites-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field',
				function() {
					$( this ).prop( 'disabled', true );
				}
			);

			$group_invites_select.on(
				'select2:unselect',
				function(e) {
					var data = e.params.data;
					$( '#group-invites-send-to-input option[value="' + data.id + '"]' ).each(
						function() {
							$( this ).remove();
						}
					);
					$( '#group-invites-container .bb-groups-invites-left #members-list li.' + data.id ).removeClass( 'selected' );
					$( '#group-invites-container .bb-groups-invites-left #members-list li.' + data.id + ' .action button' ).attr( 'data-bp-tooltip', BP_Nouveau.group_invites.add_recipient );
				}
			);

			var isPendingInvitePageSelector = $( '.groups.group-invites.pending-invites' );
			if ( isPendingInvitePageSelector.length ) {
				var isWorking = 0;
				$( window ).scroll(
					function () {
						var selectorElement = $( '.groups.group-invites.pending-invites #group-invites-container .group-invites-column .bp-invites-content #members-list li.load-more' );
						if ( selectorElement.length ) {
							  var docViewTop    = $( window ).scrollTop();
							  var docViewBottom = docViewTop + $( window ).height();
							  var elemTop       = $( selectorElement ).offset().top;
							  var elemBottom    = elemTop + $( selectorElement ).height();
							  var show          = ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
							if ( show || $( window ).scrollTop() + $( window ).height() >= $( document ).height() ) {
								if ( isWorking == 0 ) {
									  $( '#group-invites-container .last #bp-group-invites-next-page' ).trigger( 'click' );
									  isWorking = 1;
								}
							}
						}
					}
				);
			}

			if ( isPendingInvitePageSelector.length ) {

				$( '.groups.group-invites.pending-invites .subnav #pending-invites-groups-li' ).addClass( 'current selected' );
				var data = {
					'action'   : 'groups_get_group_potential_invites',
					'nonce'    : BP_Nouveau.nonces.groups,
					'group_id' : BP_Nouveau.group_invites.group_id,
					'scope'    : 'invited',
					'page'     : page
				};

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						async: false,
						data: data,
						success: function (response) {
							if ( response.success ) {
								memberInvitedList.html( '' );
								memberInvitedList.html( response.data.html );
								subNavFilterLast.html( '' );
								subNavFilterLast.html( response.data.pagination );
								feedbackInviteColumn.attr( 'class', 'bp-feedback' );
								feedbackInviteColumn.addClass( 'info' );
								feedbackInvitePTag.html( response.data.feedback );
								page = page + 1;
								$( '#group-invites-container .bp-invites-feedback' ).hide();
							} else {
								memberInvitedList.html( '' );
								feedbackInviteColumn.attr( 'class', 'bp-feedback' );
								feedbackInviteColumn.addClass( response.data.type );
								feedbackInvitePTag.html( response.data.feedback );
								$( '#group-invites-container .bp-invites-feedback' ).show();
							}
						}
					}
				);

				$( document ).on(
					'click',
					'#group-invites-container .last #bp-group-invites-next-page',
					function() {

						feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
						feedbackSelectorLeftClass.addClass( 'loading' );
						feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_invites.loading );

						var data = {
							'action'       : 'groups_get_group_potential_invites',
							'nonce'        : BP_Nouveau.nonces.groups,
							'group_id'     : BP_Nouveau.group_invites.group_id,
							'scope'        : 'invited',
							'page'     	   : page,
							'search_terms' : $( '#group-invites-container .group-invites-column #group_invites_search' ).val()
						};

						$.ajax(
							{
								type: 'POST',
								url: BP_Nouveau.ajaxurl,
								data: data,
								success: function (response) {
									isWorking = 0;
									if ( response.success ) {
										memberInvitedList.find( '.load-more' ).remove();
										memberInvitedList.append( response.data.html );
										subNavFilterLast.html( '' );
										subNavFilterLast.html( response.data.pagination );
										feedbackInviteColumn.attr( 'class', 'bp-feedback' );
										feedbackInviteColumn.addClass( 'info' );
										feedbackInvitePTag.html( response.data.feedback );
										page = page + 1;
									} else {
										memberInvitedList.html( '' );
										feedbackInviteColumn.attr( 'class', 'bp-feedback' );
										feedbackInviteColumn.addClass( response.data.type );
										feedbackInvitePTag.html( response.data.feedback );
									}
								}
							}
						);
					}
				);

				$( document ).on(
					'click',
					'#group-invites-container .last #bp-group-invites-prev-page',
					function() {
						$( '#group-invites-container .bp-invites-feedback' ).show();
						$( '#group-invites-container .bp-invites-feedback .bp-feedback' ).addClass( 'info' );
						feedbackInvitePTag.html( BP_Nouveau.group_invites.loading );
						page = page - 1;

						var data = {
							'action'       : 'groups_get_group_potential_invites',
							'nonce'        : BP_Nouveau.nonces.groups,
							'group_id'     : BP_Nouveau.group_invites.group_id,
							'scope'        : 'invited',
							'page'     	   : page,
							'search_terms' : $( '#group-invites-container .group-invites-column #group_invites_search' ).val()
						};

						$.ajax(
							{
								type: 'POST',
								url: BP_Nouveau.ajaxurl,
								data: data,
								success: function (response) {
									if ( response.success ) {
										memberInvitedList.html( '' );
										memberInvitedList.html( response.data.html );
										subNavFilterLast.html( '' );
										subNavFilterLast.html( response.data.pagination );
										feedbackInviteColumn.attr( 'class', 'bp-feedback' );
										feedbackInviteColumn.addClass( 'info' );
										feedbackInvitePTag.html( response.data.feedback );
									} else {
										memberInvitedList.html( '' );
										feedbackInviteColumn.attr( 'class', 'bp-feedback' );
										feedbackInviteColumn.addClass( response.data.type );
										feedbackInvitePTag.html( response.data.feedback );
									}
								}
							}
						);
					}
				);

				$( document ).on(
					'search',
					'.pending-invites #group-invites-container #group_invites_search',
					function( e ) {
						e.preventDefault();
						if ( '' === this.value ) {

							page     = 1;
							var data = {
								'action'   : 'groups_get_group_potential_invites',
								'nonce'    : BP_Nouveau.nonces.groups,
								'group_id' : BP_Nouveau.group_invites.group_id,
								'scope'    : 'invited',
								'page'     : page
							};

							feedbackInviteColumn.show().parents( '.bp-invites-feedback' ).show();
							feedbackInviteColumn.attr( 'class', 'bp-feedback' );
							feedbackInviteColumn.addClass( 'info' );
							feedbackInvitePTag.html( BP_Nouveau.group_invites.loading );

							$.ajax(
								{
									type: 'POST',
									url: BP_Nouveau.ajaxurl,
									async: false,
									data: data,
									success: function (response) {
										if ( response.success ) {
											memberInvitedList.html( '' );
											memberInvitedList.html( response.data.html );
											subNavFilterLast.html( '' );
											subNavFilterLast.html( response.data.pagination );
											feedbackInviteColumn.attr( 'class', 'bp-feedback' );
											feedbackInviteColumn.addClass( 'info' );
											feedbackInvitePTag.html( response.data.feedback );
											page = page + 1;
											$( '#group-invites-container .bp-invites-feedback' ).hide();
										} else {
											memberInvitedList.html( '' );
											feedbackInviteColumn.attr( 'class', 'bp-feedback' );
											feedbackInviteColumn.addClass( response.data.type );
											feedbackInvitePTag.html( response.data.feedback );
											$( '#group-invites-container .bp-invites-feedback' ).show();
										}
									}
								}
							);
						}
					}
				);

				$( document ).on(
					'click',
					'#group-invites-container #group_invites_search_submit',
					function( e ) {
						e.preventDefault();

						var searchText = $( '#group-invites-container #group_invites_search' ).val();
						if ( '' === searchText ) {
							return false;
						}
						page     = 1;
						var data = {
							'action'       : 'groups_get_group_potential_invites',
							'nonce'        : BP_Nouveau.nonces.groups,
							'group_id'     : BP_Nouveau.group_invites.group_id,
							'scope'        : 'invited',
							'page'     	   : page,
							'search_terms' : searchText
						};

						feedbackInviteColumn.show().parents( '.bp-invites-feedback' ).show();
						feedbackInviteColumn.attr( 'class', 'bp-feedback' );
						feedbackInviteColumn.addClass( 'info' );
						feedbackInvitePTag.html( BP_Nouveau.group_invites.loading );

						$.ajax(
							{
								type: 'POST',
								url: BP_Nouveau.ajaxurl,
								data: data,
								success: function (response) {
									if ( response.success ) {
										memberInvitedList.html( '' );
										memberInvitedList.html( response.data.html );
										subNavFilterLast.html( '' );
										subNavFilterLast.html( response.data.pagination );
										feedbackInviteColumn.attr( 'class', 'bp-feedback' );
										feedbackInviteColumn.addClass( 'info' );
										feedbackInvitePTag.html( response.data.feedback );
										$( '#group-invites-container .bp-invites-feedback' ).hide();
									} else {
										memberInvitedList.html( '' );
										feedbackInviteColumn.attr( 'class', 'bp-feedback' );
										feedbackInviteColumn.addClass( response.data.type );
										feedbackInvitePTag.html( response.data.feedback );
										$( '#group-invites-container .bp-invites-feedback' ).show();
									}
								}
							}
						);
					}
				);

				$( document ).on(
					'click',
					'#group-invites-container #members-list li .action .group-remove-invite-button',
					function( e ) {
						e.preventDefault();

						$( '#group-invites-container .group-invites-column #bp-pending-invites-loader' ).show();

						feedbackInviteColumn.attr( 'class', 'bp-feedback' );
						feedbackInviteColumn.addClass( 'loading' );
						feedbackInvitePTag.html( '' );
						feedbackInvitePTag.html( BP_Nouveau.group_invites.removing );

						var li = $( this ).closest( 'li' );

						var data = {
							'action'  	 	: 'groups_delete_group_invite',
							'nonce'   	 	: BP_Nouveau.nonces.groups,
							'_wpnonce' 		: BP_Nouveau.group_invites.nonces.uninvite,
							'group_id'   	: BP_Nouveau.group_invites.group_id,
							'user'   		: $( this ).attr( 'data-bp-user-id' )
						};

						$.ajax(
							{
								type: 'POST',
								url: BP_Nouveau.ajaxurl,
								data: data,
								success: function (response) {
									if ( response.success ) {
										li.remove();
										if ( ! response.data.has_invites ) {
											feedbackInviteColumn.attr( 'class', 'bp-feedback' );
											feedbackInviteColumn.addClass( response.data.type );
											feedbackInvitePTag.html( '' );
											feedbackInvitePTag.html( response.data.feedback );
										}
										$( '#group-invites-container .group-invites-column .bp-invites-content #members-list' ).html( '' );
										page     = 1;
										var data = {
											'action'   : 'groups_get_group_potential_invites',
											'nonce'    : BP_Nouveau.nonces.groups,
											'group_id' : BP_Nouveau.group_invites.group_id,
											'scope'    : 'invited',
											'page'	   : page
										};

										$.ajax(
											{
												type: 'POST',
												url: BP_Nouveau.ajaxurl,
												async: false,
												data: data,
												success: function (response) {
													if ( response.success ) {
														memberInvitedList.html( '' );
														memberInvitedList.html( response.data.html );
														subNavFilterLast.html( '' );
														subNavFilterLast.html( response.data.pagination );
														feedbackInviteColumn.attr( 'class', 'bp-feedback' );
														feedbackInviteColumn.addClass( 'info' );
														feedbackInvitePTag.html( response.data.feedback );
														$( '#group-invites-container .bp-invites-feedback' ).hide();
													} else {
														$( '#group-invites-container .bp-invites-feedback' ).show();
														memberInvitedList.html( '' );
														feedbackInviteColumn.attr( 'class', 'bp-feedback' );
														feedbackInviteColumn.addClass( response.data.type );
														feedbackInvitePTag.html( response.data.feedback );
													}
													$( '#group-invites-container .group-invites-column #bp-pending-invites-loader' ).hide();
												}
											}
										);
									} else {
										feedbackInviteColumn.attr( 'class', 'bp-feedback' );
										feedbackInviteColumn.addClass( response.data.type );
										feedbackInvitePTag.html( '' );
										feedbackInvitePTag.html( response.data.feedback );
										$( '#group-invites-container .group-invites-column #bp-pending-invites-loader' ).hide();
									}
								}
							}
						);
					}
				);
			} else {
				listSelector.scroll( this.loadMoreInvitesMembers );
				$( '.groups.group-invites.send-invites .subnav #send-invites-groups-li' ).addClass( 'current selected' );

				 var param = {
						'action'   : 'groups_get_group_potential_invites',
						'nonce'    : BP_Nouveau.nonces.groups,
						'group_id' : BP_Nouveau.group_invites.group_id,
						'scope'    : 'members',
						'page'     : page
				};

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						async: false,
						data: param,
						success: function (response) {
							if ( response.success ) {
								listSelector.html( '' );
								listSelector.html( response.data.html );
								lastSelector.html( '' );
								lastSelector.html( response.data.pagination );
								$( '#group-invites-container .bb-groups-invites-right' ).show();
								$( '.bb-groups-invites-right .bp-invites-feedback' ).show();
							} else {
								$( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
								listSelector.html( '' );
								lastSelector.html( '' );
								feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
								feedbackSelectorLeftClass.addClass( response.data.type );
								feedbackParagraphTagSelectorLeft.html( response.data.feedback );
								$( '#group-invites-container .bb-groups-invites-right' ).hide();
							}
						}
					}
				);
			}

			$( document ).on(
				'search',
				'.send-invites #group-invites-container #group_invites_search',
				function( e ) {
					e.preventDefault();

					if ( '' === this.value ) {
						var scope = '';
						if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
							scope = 'friends';
						} else {
							scope = 'members';
						}

						page      = 1;
						var param = {
							'action'   : 'groups_get_group_potential_invites',
							'nonce'    : BP_Nouveau.nonces.groups,
							'group_id' : BP_Nouveau.group_invites.group_id,
							'scope'    : scope,
							'page'     : page
						};

						feedbackSelectorLeftClass.show().parent().show();
						feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
						feedbackSelectorLeftClass.addClass( 'info' );
						feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_invites.loading );

						$.ajax(
							{
								type: 'POST',
								url: BP_Nouveau.ajaxurl,
								async: false,
								data: param,
								success: function (response) {
									if ( response.success ) {
										listSelector.html( '' );
										listSelector.html( response.data.html );
										lastSelector.html( '' );
										lastSelector.html( response.data.pagination );
										feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
										feedbackSelectorLeftClass.addClass( 'info' );
										feedbackParagraphTagSelectorLeft.html( response.data.feedback );
										$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).hide();
									} else {
										$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
										listSelector.html( '' );
										lastSelector.html( '' );
										feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
										feedbackSelectorLeftClass.addClass( response.data.type );
										feedbackParagraphTagSelectorLeft.html( response.data.feedback );
									}
								}
							}
						);
					}

				}
			);
			$( document ).on(
				'search',
				'.group-create.group-invites.create #group-invites-container #group_invites_search',
				function( e ) {
					e.preventDefault();

					if ( '' === this.value ) {
						var scope = '';
						if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
							scope = 'friends';
						} else {
							scope = 'members';
						}

						page      = 1;
						var param = {
							'action'   : 'groups_get_group_potential_invites',
							'nonce'    : BP_Nouveau.nonces.groups,
							'group_id' : BP_Nouveau.group_invites.group_id,
							'scope'    : scope,
							'page'     : page
						};

						$.ajax(
							{
								type: 'POST',
								url: BP_Nouveau.ajaxurl,
								async: false,
								data: param,
								success: function (response) {
									if ( response.success ) {
										listSelector.html( '' );
										listSelector.html( response.data.html );
										lastSelector.html( '' );
										lastSelector.html( response.data.pagination );
										feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
										feedbackSelectorLeftClass.addClass( 'info' );
										feedbackParagraphTagSelectorLeft.html( response.data.feedback );
										$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).hide();
									} else {
										$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
										listSelector.html( '' );
										lastSelector.html( '' );
										feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
										feedbackSelectorLeftClass.addClass( response.data.type );
										feedbackParagraphTagSelectorLeft.html( response.data.feedback );
									}
								}
							}
						);
					}

				}
			);

			$( '#bp-group-send-invite-switch-checkbox' ).on(
				'change',
				function () {

					$( '#group-invites-container .bb-groups-invites-left #bp-invites-dropdown-options-loader' ).removeClass( 'bp-invites-dropdown-options-loader-hide' );
					var valueSelected = '';
					if ( $( this ).is( ':checked' ) ) {
						valueSelected = 'friends';
					} else {
						valueSelected = 'members';
					}

					if ( valueSelected ) {
						page     = 1;
						var data = {
							'action'   : 'groups_get_group_potential_invites',
							'nonce'    : BP_Nouveau.nonces.groups,
							'group_id' : BP_Nouveau.group_invites.group_id,
							'scope'    : valueSelected,
							'page'     : page
						};

						$.ajax(
							{
								type: 'POST',
								url: BP_Nouveau.ajaxurl,
								//async: false,
								data: data,
								success: function (response) {
									if ( response.success ) {
										$( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).hide();
										listSelector.html( '' );
										listSelector.html( response.data.html );
										lastSelector.html( '' );
										lastSelector.html( response.data.pagination );
										feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
										feedbackSelectorLeftClass.addClass( 'info' );
										feedbackParagraphTagSelectorLeft.html( response.data.feedback );

										var alreadySelected = $( '#group-invites-send-to-input' ).val();
										if ( $.isArray( alreadySelected ) && alreadySelected.length ) {
											$.each(
												alreadySelected,
												function( index, value ) {
													if ( value ) {
														$( '#members-list li.' + value ).addClass( 'selected' );
														$( '#members-list li.' + value + ' .action button' ).attr( 'data-bp-tooltip', BP_Nouveau.group_invites.cancel_invite_tooltip );
													}
												}
											);
										}
									} else {
										$( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
										listSelector.html( '' );
										lastSelector.html( '' );
										feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
										feedbackSelectorLeftClass.addClass( response.data.type );
										feedbackParagraphTagSelectorLeft.html( response.data.feedback );
									}
									$( '#group-invites-container .bb-groups-invites-left #bp-invites-dropdown-options-loader' ).addClass( 'bp-invites-dropdown-options-loader-hide' );
								}
							}
						);
					}
				}
			);

			$( document ).on(
				'click',
				'#group-invites-container .bb-groups-invites-left .group-add-remove-invite-button',
				function() {

					var userId   = $( this ).attr( 'data-bp-user-id' );
					var userName = $( this ).attr( 'data-bp-user-name' );
					var data     = {
						id: userId,
						text: userName
					};

					if ( $( this ).closest( 'li' ).hasClass( 'selected' ) ) {

						$( this ).closest( 'li' ).removeClass( 'selected' );

						var newArray = [];
						var newData  = $.grep(
							$group_invites_select.select2( 'data' ),
							function (value) {
								return value['id'] != userId; // jshint ignore:line
							}
						);

						newData.forEach(
							function(data) {
								newArray.push( +data.id );
							}
						);

						$group_invites_select.val( newArray ).trigger( 'change' );

						$( '#group-invites-send-to-input option[value="' + userId + '"]' ).each(
							function() {
								$( this ).remove();
							}
						);

						$( this ).attr( 'data-bp-tooltip', BP_Nouveau.group_invites.add_invite_tooltip );

					} else {
						$( this ).closest( 'li' ).addClass( 'selected' );
						if ( ! $group_invites_select.find( "option[value='" + data.id + "']" ).length ) { // jshint ignore:line
							var newOption = new Option( data.text, data.id, true, true );
							$group_invites_select.append( newOption ).trigger( 'change' );
						}
						$( this ).attr( 'data-bp-tooltip', BP_Nouveau.group_invites.cancel_invite_tooltip );
					}
				}
			);

			$( document ).on(
				'click',
				'#group-invites-container .bb-groups-invites-left .last #bp-group-invites-next-page',
				function() {
					page = page + 1;

					if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
						scope = 'friends';
					} else {
						scope = 'members';
					}

					var data = {
						'action'       : 'groups_get_group_potential_invites',
						'nonce'        : BP_Nouveau.nonces.groups,
						'group_id'     : BP_Nouveau.group_invites.group_id,
						'scope'        : scope,
						'page'     	   : page,
						'search_terms' : $( '#group-invites-container .bb-groups-invites-left #group_invites_search' ).val()
					};

					$.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							data: data,
							success: function (response) {
								if ( response.success ) {
									$( '#group-invites-container .group-invites-members-listing #members-list li.load-more' ).remove();
									listSelector.append( response.data.html );
									lastSelector.html( '' );
									lastSelector.html( response.data.pagination );
									feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
									feedbackSelectorLeftClass.addClass( 'info' );
									feedbackParagraphTagSelectorLeft.html( response.data.feedback );
								} else {
									feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
									feedbackSelectorLeftClass.addClass( response.data.type );
									feedbackParagraphTagSelectorLeft.html( response.data.feedback );
								}
							}
						}
					);
				}
			);

			$( document ).on(
				'click',
				'.bb-more-invites-wrap .bb-add-invites',
				function(e) {
					e.preventDefault();
					$( '.bb-groups-invites-left' ).addClass( 'bb-select-member-view' );
				}
			);

			$( document ).on(
				'click',
				'.bb-close-invites-members',
				function(e) {
					e.preventDefault();
					$( '.bb-groups-invites-left' ).removeClass( 'bb-select-member-view' );
				}
			);

			$( document ).on(
				'click',
				'#group-invites-container .bb-groups-invites-right #send_group_invite_form .bb-groups-invites-right-bottom #send_group_invite_button',
				function( e ) {
					e.preventDefault();
					var users_list = [];
					var newData    = $.grep(
						$group_invites_select.select2( 'data' ),
						function (value) {
							return value['id'] != 0; // jshint ignore:line
						}
					);

					$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).show();

					newData.forEach(
						function(data) {
							users_list.push( +data.id );
						}
					);

					var data = {
						'action'  	 	: 'groups_send_group_invites',
						'nonce'   	 	: BP_Nouveau.nonces.groups,
						'_wpnonce' 		: BP_Nouveau.group_invites.nonces.send_invites,
						'group_id'   	: BP_Nouveau.group_invites.group_id,
						'message' 	 	: $( 'textarea#send-invites-control' ).val(),
						'users'   		: users_list
					};

					$.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							data: data,
							success: function (response) {
								if ( response.success ) {
									feedbackDivHide.show();
									feedbackParagraphTagClass.attr( 'class', 'bp-feedback' );
									feedbackParagraphTagClass.addClass( response.data.type );
									feedbackParagraphTagSelector.html( '' );
									feedbackParagraphTagSelector.html( response.data.feedback );
									setTimeout(
										function() {
											feedbackParagraphTagClass.removeClass( response.data.type );
											feedbackParagraphTagClass.addClass( 'info' );
											feedbackParagraphTagSelector.html( '' );
											feedbackParagraphTagSelector.html( BP_Nouveau.group_invites.member_invite_info_text );
											$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).hide();
										},
										4000
									); // <-- time in milliseconds
									$group_invites_select.find( 'option' ).remove();
									$( 'textarea#send-invites-control' ).val( '' );

									if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
										scope = 'friends';
									} else {
										scope = 'members';
									}

									page     = 1;
									var data = {
										'action'   : 'groups_get_group_potential_invites',
										'nonce'    : BP_Nouveau.nonces.groups,
										'group_id' : BP_Nouveau.group_invites.group_id,
										'scope'    : scope,
										'page'     : page
									};

									$.ajax(
										{
											type: 'POST',
											url: BP_Nouveau.ajaxurl,
											async: false,
											data: data,
											success: function (response) {
												if ( response.success ) {
													listSelector.html( '' );
													listSelector.html( response.data.html );
													lastSelector.html( '' );
													lastSelector.html( response.data.pagination );
													feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
													feedbackSelectorLeftClass.addClass( 'info' );
													feedbackParagraphTagSelectorLeft.html( response.data.feedback );
												} else {
													$( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
													listSelector.html( '' );
													lastSelector.html( '' );
													feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
													feedbackSelectorLeftClass.addClass( response.data.type );
													feedbackParagraphTagSelectorLeft.html( response.data.feedback );
												}
											}
										}
									);
								} else {
									feedbackDivHide.show();
									feedbackParagraphTagClass.attr( 'class', 'bp-feedback' );
									feedbackParagraphTagClass.addClass( response.data.type );
									feedbackParagraphTagSelector.html( '' );
									feedbackParagraphTagSelector.html( response.data.feedback );

									setTimeout(
										function() {
											feedbackParagraphTagClass.removeClass( response.data.type );
											feedbackParagraphTagClass.addClass( 'info' );
											feedbackParagraphTagSelector.html( '' );
											feedbackParagraphTagSelector.html( BP_Nouveau.group_invites.member_invite_info_text );
											$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).hide();
										},
										4000
									); // <-- time in milliseconds
								}
							}
						}
					);

				}
			);

			$( document ).on(
				'click',
				'#group-invites-container #bp_invites_reset',
				function( e ) {
					e.preventDefault();
					$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).show();
					feedbackDivHide.hide();
					$group_invites_select.find( 'option' ).remove();
					$( 'textarea#send-invites-control' ).val( '' );
					page = 1;

					if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
						scope = 'friends';
					} else {
						scope = 'members';
					}
					var data = {
						'action'   : 'groups_get_group_potential_invites',
						'nonce'    : BP_Nouveau.nonces.groups,
						'group_id' : BP_Nouveau.group_invites.group_id,
						'scope'    : scope,
						'page'     : page
					};

					$.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							async: false,
							data: data,
							success: function (response) {
								if ( response.success ) {
									listSelector.html( '' );
									listSelector.html( response.data.html );
									lastSelector.html( '' );
									lastSelector.html( response.data.pagination );
									feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
									feedbackSelectorLeftClass.addClass( 'info' );
									feedbackParagraphTagSelectorLeft.html( response.data.feedback );
									$( '.bb-groups-invites-right-top .bp-invites-feedback' ).show();
									setTimeout(
										function() {
											$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).hide();
										},
										1000
									); // <-- time in milliseconds
								} else {
									listSelector.html( '' );
									lastSelector.html( '' );
									feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
									feedbackSelectorLeftClass.addClass( response.data.type );
									feedbackParagraphTagSelectorLeft.html( response.data.feedback );
									setTimeout(
										function() {
											$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).hide();
										},
										1000
									); // <-- time in milliseconds
								}
							}
						}
					);

				}
			);

			$( document ).on(
				'click',
				'.send-invites #group-invites-container #group_invites_search_submit',
				function( e ) {
					e.preventDefault();

					var searchText = $( '#group-invites-container #group_invites_search' ).val();
					if ( '' === searchText ) {
						return false;
					}
					page = 1;
					if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
						scope = 'friends';
					} else {
						scope = 'members';
					}
					var data = {
						'action'       : 'groups_get_group_potential_invites',
						'nonce'        : BP_Nouveau.nonces.groups,
						'group_id'     : BP_Nouveau.group_invites.group_id,
						'scope'        : scope,
						'page'     	   : page,
						'search_terms' : searchText
					};

					feedbackSelectorLeftClass.show().parent().show();
					feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
					feedbackSelectorLeftClass.addClass( 'info' );
					feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_invites.loading );

					$.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							data: data,
							success: function (response) {
								if ( response.success ) {
									listSelector.html( '' );
									listSelector.html( response.data.html );
									lastSelector.html( '' );
									lastSelector.html( response.data.pagination );
									// page = response.data.page;
									feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
									feedbackSelectorLeftClass.addClass( 'info' );
									feedbackParagraphTagSelectorLeft.html( response.data.feedback );
									$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).hide();
								} else {
									listSelector.html( '' );
									lastSelector.html( '' );
									feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
									feedbackSelectorLeftClass.addClass( response.data.type );
									feedbackParagraphTagSelectorLeft.html( response.data.feedback );
									$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
								}
							}
						}
					);
				}
			);
			$( document ).on(
				'click',
				'.group-create.group-invites.create #group-invites-container #group_invites_search_submit',
				function( e ) {
					e.preventDefault();

					var searchText = $( '#group-invites-container #group_invites_search' ).val();
					if ( '' === searchText ) {
						return false;
					}
					page = 1;
					if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
						scope = 'friends';
					} else {
						scope = 'members';
					}
					var data = {
						'action'       : 'groups_get_group_potential_invites',
						'nonce'        : BP_Nouveau.nonces.groups,
						'group_id'     : BP_Nouveau.group_invites.group_id,
						'scope'        : scope,
						'page'     	   : page,
						'search_terms' : searchText
					};

					$.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							data: data,
							success: function (response) {
								if ( response.success ) {
									listSelector.html( '' );
									listSelector.html( response.data.html );
									lastSelector.html( '' );
									lastSelector.html( response.data.pagination );
									// page = response.data.page;
									feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
									feedbackSelectorLeftClass.addClass( 'info' );
									feedbackParagraphTagSelectorLeft.html( response.data.feedback );
									$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).hide();
								} else {
									listSelector.html( '' );
									lastSelector.html( '' );
									feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
									feedbackSelectorLeftClass.addClass( response.data.type );
									feedbackParagraphTagSelectorLeft.html( response.data.feedback );
									$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
								}
							}
						}
					);
				}
			);
		},

		addSelect2: function( $input ) {

			var ArrayData = [];
			$input.select2(
				{
					placeholder: '',
					minimumInputLength: 1,
					language: {
						errorLoading: function () {
							return bp_select2.i18n.errorLoading;
						},
						inputTooLong: function ( e ) {
							var n = e.input.length - e.maximum;
							return bp_select2.i18n.inputTooLong.replace( '%%', n );
						},
						inputTooShort: function ( e ) {
							return bp_select2.i18n.inputTooShort.replace( '%%', (e.minimum - e.input.length) );
						},
						loadingMore: function () {
							return bp_select2.i18n.loadingMore;
						},
						maximumSelected: function ( e ) {
							return bp_select2.i18n.maximumSelected.replace( '%%', e.maximum );
						},
						noResults: function () {
							return bp_select2.i18n.noResults;
						},
						searching: function () {
							return bp_select2.i18n.searching;
						},
						removeAllItems: function () {
							return bp_select2.i18n.removeAllItems;
						}
					},
					ajax: {
						url: bp.ajax.settings.url,
						dataType: 'json',
						delay: 250,
						data: function(params) {
							return $.extend(
								{},
								params,
								{
									nonce: BP_Nouveau.group_invites.nonces.retrieve_group_members,
									action: 'groups_get_group_potential_user_send_invites',
									group: BP_Nouveau.group_invites.group_id
								}
							);
						},
						cache: true,
						processResults: function( data ) {

							// Removed the element from results if already selected.
							if ( false === jQuery.isEmptyObject( ArrayData ) ) {
								$.each(
									ArrayData,
									function( index, value ) {
										for (var i = 0;i < data.data.results.length;i++) {
											if (data.data.results[i].id === value) {
												data.data.results.splice( i,1 );
											}
										}
									}
								);
							}

							return {
								results: data && data.success ? data.data.results : []
							};
						}
					}
				}
			);

			// Add element into the Arrdata array.
			$input.on(
				'select2:select',
				function(e) {
					var data = e.params.data;
					ArrayData.push( data.id );
				}
			);

			// Remove element into the Arrdata array.
			$input.on(
				'select2:unselect',
				function(e) {
					var data  = e.params.data;
					ArrayData = jQuery.grep(
						ArrayData,
						function(value) {
							return value !== data.id;
						}
					);
				}
			);

		},

		isScrolledIntoView:function ( elem ) {
			var docViewTop    = $( window ).scrollTop();
			var docViewBottom = docViewTop + $( window ).height();

			var elemTop    = $( elem ).offset().top;
			var elemBottom = elemTop + $( elem ).height();

			return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
		},

		loadMoreInvitesMembers:function ( event ) {
			var target = $( event.currentTarget );
			if ( ( target[0].scrollHeight - target.scrollTop() - target.innerHeight() ) <= 30 ) {
				var element =  $( '#group-invites-container .group-invites-members-listing #members-list li.load-more' );
				if ( element.length ) {
					$( '#group-invites-container .group-invites-members-listing .last #bp-group-invites-next-page' ).trigger( 'click' );
				}
			}
		}

	};

	// Launch BP Nouveau Groups
	bp.Nouveau.GroupInvites.start();

} )( bp, jQuery );
