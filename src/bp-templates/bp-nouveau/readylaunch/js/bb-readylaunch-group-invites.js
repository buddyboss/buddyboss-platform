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

	var bpNouveau        = BP_Nouveau,
	    bbRlAjaxUrl      = bpNouveau.ajaxurl,
	    bbRlNonces       = bpNouveau.nonces,
	    bbRlGroupInvites = bpNouveau.group_invites;
	
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
		start : function () {
			var self       = this;
			this.views     = new Backbone.Collection();
			this.isLoading = false;

			var $body                 = $( 'body' );
			var $group_invites_select = $body.find( '#group-invites-send-to-input' );
			var page                  = 1;
			var scope                 = '';

			// Activate bp_mentions
			this.addSelect2( $group_invites_select );

			self.selectors = {
				page                         : page,
				scope                        : scope,
				groupInvitesSelect           : $group_invites_select,
				groupInvitesContainer        : $body.find( '#group-invites-container' ),
				feedbackParagraphTagClass    : $body.find( '#group-invites-container .bb-groups-invites-right .bp-invites-feedback .bp-feedback' ),
				feedbackParagraphTagSelector : $( '#group-invites-container .bb-groups-invites-right .bp-invites-feedback .bp-feedback p' ),
				feedbackDivHide              : $( '#group-invites-container .bb-groups-invites-right .bp-invites-feedback' ),
				feedbackInviteColumn         : $( '#group-invites-container .group-invites-column .bp-invites-feedback .bp-feedback' ),
				feedbackInvitePTag           : $( '#group-invites-container .group-invites-column .bp-invites-feedback .bp-feedback p' ),

				feedbackSelectorLeftClass        : $( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback .bp-feedback' ),
				feedbackParagraphTagSelectorLeft : $( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback .bp-feedback p' ),
				listSelector                     : $( '.group-invites-members-listing #members-list' ),
				lastSelector                     : $( '.group-invites-members-listing .last' ),
				memberInvitedList                : $( '.members.bp-invites-content #members-list' ),
				subNavFilterLast                 : $( '#group-invites-container .group-invites-column .subnav-filters div .last' )
			};

			// Set Feedback for all members.
			self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
			self.selectors.feedbackSelectorLeftClass.addClass( 'loading' );
			self.selectors.feedbackParagraphTagSelectorLeft.html( bbRlGroupInvites.loading );

			$( '#group-invites-container .bb-groups-invites-right #send_group_invite_form .bb-groups-invites-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field' ).prop( 'disabled', true );

			$group_invites_select.on(
				'select2:unselect',
				function ( e ) {
					var data = e.params.data;
					$( '#group-invites-send-to-input option[value="' + data.id + '"]' ).each(
						function () {
							$( this ).remove();
						}
					);
					$( '#group-invites-container .bb-groups-invites-left #members-list li.' + data.id ).removeClass( 'selected' );
					$( '#group-invites-container .bb-groups-invites-left #members-list li.' + data.id + ' .action button' ).attr( 'data-bp-tooltip', bbRlGroupInvites.add_recipient );
					
					if ( 0 === $( '#group-invites-send-to-input' ).val().length ) {
						$( '#send_group_invite_button' ).attr( 'disabled', true );
					}
				}
			);

			self.selectors.isPendingInvitePageSelector = $( '.groups.group-invites.pending-invites' );
			if ( self.selectors.isPendingInvitePageSelector.length ) {
				var isWorking = 0;
				$( window ).scroll(
					function () {
						var selectorElement = $( '.groups.group-invites.pending-invites #group-invites-container .group-invites-column .bp-invites-content #members-list li.load-more' );
						if ( selectorElement.length ) {
							var docViewTop    = $( window ).scrollTop();
							var docViewBottom = docViewTop + $( window ).height();
							var elemTop       = $( selectorElement ).offset().top;
							var elemBottom    = elemTop + $( selectorElement ).height();
							var show          = (
								(
									elemBottom <= docViewBottom
								) && (
									elemTop >= docViewTop
								)
							);
							if ( show || $( window ).scrollTop() + $( window ).height() >= $( document ).height() ) {
								if ( 0 === isWorking && ! self.isLoading ) {
									$( '#group-invites-container .last #bp-group-invites-next-page' ).trigger( 'click' );
									isWorking = 1;
								}
							}
						}
					}
				);
			}

			if ( self.selectors.isPendingInvitePageSelector.length ) {
				$( '.groups.group-invites.pending-invites .subnav #pending-invites-groups-li' ).addClass( 'current selected' );
				var data = {
					'action'   : 'groups_get_group_potential_invites',
					'nonce'    : bbRlNonces.groups,
					'group_id' : bbRlGroupInvites.group_id,
					'scope'    : 'invited',
					'page'     : self.selectors.page
				};

				$.ajax(
					{
						type    : 'POST',
						url     : bbRlAjaxUrl,
						async   : false,
						data    : data,
						success : function ( response ) {
							if ( response.success ) {
								self.selectors.memberInvitedList.html( response.data.html );
								self.selectors.subNavFilterLast.html( response.data.pagination );
								self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackInviteColumn.addClass( 'info' );
								self.selectors.feedbackInvitePTag.html( response.data.feedback );
								self.selectors.page = self.selectors.page + 1;
								$( '#group-invites-container .bp-invites-feedback' ).hide();
							} else {
								self.selectors.memberInvitedList.html( '' );
								self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackInviteColumn.addClass( response.data.type );
								self.selectors.feedbackInvitePTag.html( response.data.feedback );
								$( '#group-invites-container .bp-invites-feedback' ).show();
							}
						}
					}
				);
			} else {
				self.selectors.listSelector.scroll( this.loadMoreInvitesMembers );
				$( '.groups.group-invites.send-invites .subnav #send-invites-groups-li' ).addClass( 'current selected' );

				var param = {
					'action'   : 'groups_get_group_potential_invites',
					'nonce'    : bbRlNonces.groups,
					'group_id' : bbRlGroupInvites.group_id,
					'scope'    : 'members',
					'page'     : self.selectors.page
				};

				$.ajax(
					{
						type    : 'POST',
						url     : bbRlAjaxUrl,
						async   : false,
						data    : param,
						success : function ( response ) {
							var self = bp.Nouveau.GroupInvites;
							if ( response.success ) {
								self.selectors.listSelector.html( response.data.html );
								self.selectors.lastSelector.html( response.data.pagination );
								$( '#group-invites-container .bb-groups-invites-right' ).show();
								$( '.bb-groups-invites-right .bp-invites-feedback' ).show();
							} else {
								$( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
								self.selectors.listSelector.html( '' );
								self.selectors.lastSelector.html( '' );
								self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackSelectorLeftClass.addClass( response.data.type );
								self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
								$( '#group-invites-container .bb-groups-invites-right' ).hide();
							}
						}
					}
				);
			}

			this.addEventListeners();
		},

		addEventListeners : function () {
			$( document ).on(
				'click',
				'#group-invites-container .bb-groups-invites-right #send_group_invite_form .bb-groups-invites-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field',
				this.disabledGroupsInvitesRightTopSearch.bind( this )
			);

			$( document ).on(
				'search',
				'.send-invites #group-invites-container #group_invites_search',
				this.groupsInvitesSearch.bind( this )
			);

			$( document ).on(
				'search',
				'.group-create.group-invites.create #group-invites-container #group_invites_search',
				this.groupCreateGroupsInvitesSearch.bind( this )
			);

			$( '#bp-group-send-invite-switch-checkbox' ).on(
				'change',
				this.groupSendInvitesSwitchCheckbox.bind( this )
			);

			$( document ).on(
				'click',
				'#group-invites-container .bb-groups-invites-left .group-add-remove-invite-button',
				this.groupAddRemoveInviteButton.bind( this )
			);

			$( document ).on(
				'click',
				'#group-invites-container .bb-groups-invites-left .last #bp-group-invites-next-page',
				this.groupInvitesNextPage.bind( this )
			);

			$( document ).on(
				'click',
				'.bb-more-invites-wrap .bb-add-invites',
				this.groupInvitesAddInvites.bind( this )
			);

			$( document ).on(
				'click',
				'.bb-close-invites-members',
				this.groupInvitesCloseMembers.bind( this )
			);

			$( document ).on(
				'click',
				'#group-invites-container .bb-groups-invites-right #send_group_invite_form .bb-groups-invites-right-bottom #send_group_invite_button',
				this.groupInvitesSendInviteButton.bind( this )
			);

			$( document ).on(
				'click',
				'#group-invites-container #bp_invites_reset',
				this.groupInvitesReset.bind( this )
			);

			$( document ).on(
				'click',
				'.send-invites #group-invites-container #group_invites_search_submit',
				this.groupInvitesSearchSubmit.bind( this )
			);

			$( document ).on(
				'click',
				'.group-create.group-invites.create #group-invites-container #group_invites_search_submit',
				this.groupCreateGroupsInvitesSearchSubmit.bind( this )
			);

			if ( this.selectors.isPendingInvitePageSelector.length ) {

				$( document ).on(
					'click',
					'#group-invites-container .last #bp-group-invites-next-page',
					this.groupInvitesContainerNextPage.bind( this )
				);

				$( document ).on(
					'click',
					'#group-invites-container .last #bp-group-invites-prev-page',
					this.groupInvitesContainerPrevPage.bind( this )
				);

				$( document ).on(
					'search',
					'.pending-invites #group-invites-container #group_invites_search',
					this.pendingInvitesGroupInvitesSearch.bind( this )
				);

				$( document ).on(
					'click',
					'#group-invites-container #group_invites_search_submit',
					this.groupInvitesContainerSearchSubmit.bind( this )
				);

				$( document ).on(
					'click',
					'#group-invites-container #members-list li .action .group-remove-invite-button',
					this.groupInvitesContainerRemoveInviteButton.bind( this )
				);
			}
		},

		disabledGroupsInvitesRightTopSearch : function ( event ) {
			$( event.currentTarget ).prop( 'disabled', true );
		},

		groupsInvitesSearch : function ( event ) {
			var self = this;
			event.preventDefault();
			if ( '' === event.currentTarget.value ) {
				self.selectors.scope = '';
				if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
					self.selectors.scope = 'friends';
				} else {
					self.selectors.scope = 'members';
				}

				self.selectors.page = 1;
				var param           = {
					'action'   : 'groups_get_group_potential_invites',
					'nonce'    : bbRlNonces.groups,
					'group_id' : bbRlGroupInvites.group_id,
					'scope'    : self.selectors.scope,
					'page'     : self.selectors.page
				};

				self.selectors.feedbackSelectorLeftClass.show().parent().show();
				self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
				self.selectors.feedbackSelectorLeftClass.addClass( 'info' );
				self.selectors.feedbackParagraphTagSelectorLeft.html( bbRlGroupInvites.loading );

				$.ajax(
					{
						type    : 'POST',
						url     : bbRlAjaxUrl,
						async   : false,
						data    : param,
						success : function ( response ) {
							if ( response.success ) {
								self.selectors.listSelector.html( response.data.html );
								self.selectors.lastSelector.html( response.data.pagination );
								self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackSelectorLeftClass.addClass( 'info' );
								self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
								$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).hide();
							} else {
								$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
								self.selectors.listSelector.html( '' );
								self.selectors.lastSelector.html( '' );
								self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackSelectorLeftClass.addClass( response.data.type );
								self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
							}
						}
					}
				);
			}
		},

		groupCreateGroupsInvitesSearch : function ( event ) {
			var self = this;
			event.preventDefault();

			if ( '' === event.currentTarget.value ) {
				var scope;
				if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
					scope = 'friends';
				} else {
					scope = 'members';
				}

				var page  = 1;
				var param = {
					'action'   : 'groups_get_group_potential_invites',
					'nonce'    : bbRlNonces.groups,
					'group_id' : bbRlGroupInvites.group_id,
					'scope'    : scope,
					'page'     : page
				};

				$.ajax(
					{
						type    : 'POST',
						url     : bbRlAjaxUrl,
						async   : false,
						data    : param,
						success : function ( response ) {
							if ( response.success ) {
								self.selectors.listSelector.html( response.data.html );
								self.selectors.lastSelector.html( response.data.pagination );
								self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackSelectorLeftClass.addClass( 'info' );
								self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
								$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).hide();
							} else {
								$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
								self.selectors.listSelector.html( '' );
								self.selectors.lastSelector.html( '' );
								self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackSelectorLeftClass.addClass( response.data.type );
								self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
							}
						}
					}
				);
			}
		},

		groupSendInvitesSwitchCheckbox : function ( event ) {
			var self = this;
			$( '#group-invites-container .bb-groups-invites-left #bp-invites-dropdown-options-loader' ).removeClass( 'bp-invites-dropdown-options-loader-hide' );
			var valueSelected;
			if ( $( event.currentTarget ).is( ':checked' ) ) {
				valueSelected = 'friends';
			} else {
				valueSelected = 'members';
			}

			if ( valueSelected ) {
				var page = 1;
				var data = {
					'action'   : 'groups_get_group_potential_invites',
					'nonce'    : bbRlNonces.groups,
					'group_id' : bbRlGroupInvites.group_id,
					'scope'    : valueSelected,
					'page'     : page
				};

				$.ajax(
					{
						type    : 'POST',
						url     : bbRlAjaxUrl,
						data    : data,
						success : function ( response ) {
							if ( response.success ) {
								$( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).hide();
								self.selectors.listSelector.html( response.data.html );
								self.selectors.lastSelector.html( response.data.pagination );
								self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackSelectorLeftClass.addClass( 'info' );
								self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );

								var alreadySelected = $( '#group-invites-send-to-input' ).val();
								if ( $.isArray( alreadySelected ) && alreadySelected.length ) {
									$.each(
										alreadySelected,
										function ( index, value ) {
											if ( value ) {
												$( '#members-list li.' + value ).addClass( 'selected' );
												$( '#members-list li.' + value + ' .action button' ).attr( 'data-bp-tooltip', bbRlGroupInvites.cancel_invite_tooltip );
											}
										}
									);
								}
							} else {
								$( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
								self.selectors.listSelector.html( '' );
								self.selectors.lastSelector.html( '' );
								self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackSelectorLeftClass.addClass( response.data.type );
								self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
							}
							$( '#group-invites-container .bb-groups-invites-left #bp-invites-dropdown-options-loader' ).addClass( 'bp-invites-dropdown-options-loader-hide' );
						}
					}
				);
			}
		},

		groupAddRemoveInviteButton : function ( event ) {
			var self       = this;
			var $button    = $( event.currentTarget );
			var userId     = $button.attr( 'data-bp-user-id' );
			var userName   = $button.attr( 'data-bp-user-name' );
			var userAvatar = $button.closest( 'li' ).find( '.item-avatar img' ).attr( 'src' );
			var data       = {
				id     : userId,
				text   : userName,
				avatar : userAvatar
			};

			if ( $button.closest( 'li' ).hasClass( 'selected' ) ) {
				$button.closest( 'li' ).removeClass( 'selected' );

				var newArray = [];
				var newData  = $.grep(
					self.selectors.groupInvitesSelect.select2( 'data' ),
					function ( value ) {
						return value[ 'id' ] !== userId; // jshint ignore:line
					}
				);

				newData.forEach(
					function ( data ) {
						newArray.push( +data.id );
					}
				);

				self.selectors.groupInvitesSelect.val( newArray ).trigger( 'change' );

				$( '#group-invites-send-to-input option[value="' + userId + '"]' ).each(
					function () {
						$( this ).remove();
					}
				);

				$button.attr( 'data-bp-tooltip', bbRlGroupInvites.add_invite_tooltip );

				if ( 0 === newArray.length ) {
					$( '#send_group_invite_button' ).attr( 'disabled', true );
				}

			} else {
				$button.closest( 'li' ).addClass( 'selected' );
				if ( ! self.selectors.groupInvitesSelect.find( 'option[value="' + data.id + '"]' ).length ) { // jshint ignore:line
					var newOption = new Option( data.text, data.id, true, true );
					$( newOption ).attr( 'data-avatar', data.avatar );
					self.selectors.groupInvitesSelect.append( newOption ).trigger( 'change' );
					$( '#send_group_invite_button' ).removeAttr( 'disabled' );
				}
				$button.attr( 'data-bp-tooltip', bbRlGroupInvites.cancel_invite_tooltip );
			}
		},

		groupInvitesNextPage : function () {
			var self = this;

			if ( self.isLoading ) {
				return;
			}

			self.isLoading      = true;
			self.selectors.page = self.selectors.page + 1;

			if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
				self.selectors.scope = 'friends';
			} else {
				self.selectors.scope = 'members';
			}

			var data = {
				'action'       : 'groups_get_group_potential_invites',
				'nonce'        : bbRlNonces.groups,
				'group_id'     : bbRlGroupInvites.group_id,
				'scope'        : self.selectors.scope,
				'page'         : self.selectors.page,
				'search_terms' : $( '#group-invites-container .bb-groups-invites-left #group_invites_search' ).val()
			};

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					success : function ( response ) {
						if ( response.success ) {
							$( '#group-invites-container .group-invites-members-listing #members-list li.load-more' ).remove();
							self.selectors.listSelector.append( response.data.html );
							self.selectors.lastSelector.html( '' );
							self.selectors.lastSelector.html( response.data.pagination );
							self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackSelectorLeftClass.addClass( 'info' );
							self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
						} else {
							self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackSelectorLeftClass.addClass( response.data.type );
							self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
						}
						self.isLoading = false;
					}
				}
			);
		},

		groupInvitesAddInvites : function ( event ) {
			event.preventDefault();
			$( '.bb-groups-invites-left' ).addClass( 'bb-select-member-view' );
		},

		groupInvitesCloseMembers : function ( event ) {
			event.preventDefault();
			$( '.bb-groups-invites-left' ).removeClass( 'bb-select-member-view' );
		},

		groupInvitesSendInviteButton : function ( event ) {
			var self = this;
			event.preventDefault();
			var target     = $( event.currentTarget );
			var users_list = [];
			var newData    = $.grep(
				self.selectors.groupInvitesSelect.select2( 'data' ),
				function ( value ) {
					return value[ 'id' ] !== 0; // jshint ignore:line
				}
			);

			$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).show();

			newData.forEach(
				function ( data ) {
					users_list.push( +data.id );
				}
			);

			var data = {
				'action'   : 'groups_send_group_invites',
				'nonce'    : bbRlNonces.groups,
				'_wpnonce' : bbRlGroupInvites.nonces.send_invites,
				'group_id' : bbRlGroupInvites.group_id,
				'message'  : $( 'textarea#send-invites-control' ).val(),
				'users'    : users_list
			};

			target.attr( 'disabled', true );

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					success : function ( response ) {
						if ( response.success ) {
							self.selectors.feedbackDivHide.show();
							self.selectors.feedbackParagraphTagClass.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackParagraphTagClass.addClass( response.data.type );
							self.selectors.feedbackParagraphTagSelector.html( '' );
							self.selectors.feedbackParagraphTagSelector.html( response.data.feedback );
							setTimeout(
								function () {
									self.selectors.feedbackParagraphTagClass.removeClass( response.data.type );
									self.selectors.feedbackParagraphTagClass.addClass( 'info' );
									self.selectors.feedbackParagraphTagSelector.html( bbRlGroupInvites.member_invite_info_text );
									$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).hide();
								},
								4000
							); // <-- time in milliseconds
							self.selectors.groupInvitesSelect.find( 'option' ).remove();
							$( 'textarea#send-invites-control' ).val( '' );

							if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
								self.selectors.scope = 'friends';
							} else {
								self.selectors.scope = 'members';
							}

							self.selectors.page = 1;
							var data            = {
								'action'   : 'groups_get_group_potential_invites',
								'nonce'    : bbRlNonces.groups,
								'group_id' : bbRlGroupInvites.group_id,
								'scope'    : self.selectors.scope,
								'page'     : self.selectors.page
							};

							$.ajax(
								{
									type    : 'POST',
									url     : bbRlAjaxUrl,
									async   : false,
									data    : data,
									success : function ( response ) {
										if ( response.success ) {
											self.selectors.listSelector.html( response.data.html );
											self.selectors.lastSelector.html( response.data.pagination );
											self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
											self.selectors.feedbackSelectorLeftClass.addClass( 'info' );
											self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
										} else {
											$( '#group-invites-container .bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
											self.selectors.listSelector.html( '' );
											self.selectors.lastSelector.html( '' );
											self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
											self.selectors.feedbackSelectorLeftClass.addClass( response.data.type );
											self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
										}
									}
								}
							);
						} else {
							self.selectors.feedbackDivHide.show();
							self.selectors.feedbackParagraphTagClass.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackParagraphTagClass.addClass( response.data.type );
							self.selectors.feedbackParagraphTagSelector.html( '' );
							self.selectors.feedbackParagraphTagSelector.html( response.data.feedback );

							setTimeout(
								function () {
									self.selectors.feedbackParagraphTagClass.removeClass( response.data.type );
									self.selectors.feedbackParagraphTagClass.addClass( 'info' );
									self.selectors.feedbackParagraphTagSelector.html( bbRlGroupInvites.member_invite_info_text );
									$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).hide();
								},
								4000
							); // <-- time in milliseconds
						}

						target.attr( 'disabled', false );
					}
				}
			);
		},

		groupInvitesReset : function ( event ) {
			var self = this;
			event.preventDefault();
			$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).show();
			self.selectors.feedbackDivHide.hide();
			self.selectors.groupInvitesSelect.find( 'option' ).remove();
			$( 'textarea#send-invites-control' ).val( '' );
			self.selectors.page = 1;

			if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
				self.selectors.scope = 'friends';
			} else {
				self.selectors.scope = 'members';
			}
			var data = {
				'action'   : 'groups_get_group_potential_invites',
				'nonce'    : bbRlNonces.groups,
				'group_id' : bbRlGroupInvites.group_id,
				'scope'    : self.selectors.scope,
				'page'     : self.selectors.page
			};

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					async   : false,
					data    : data,
					success : function ( response ) {
						if ( response.success ) {
							self.selectors.listSelector.html( response.data.html );
							self.selectors.lastSelector.html( response.data.pagination );
							self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackSelectorLeftClass.addClass( 'info' );
							self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
							$( '.bb-groups-invites-right-top .bp-invites-feedback' ).show();
							setTimeout(
								function () {
									$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).hide();
								},
								1000
							); // <-- time in milliseconds
						} else {
							self.selectors.listSelector.html( '' );
							self.selectors.lastSelector.html( '' );
							self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackSelectorLeftClass.addClass( response.data.type );
							self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
							setTimeout(
								function () {
									$( '#group-invites-container .bb-groups-invites-right .bp-invites-submit-loader-hide' ).hide();
								},
								1000
							); // <-- time in milliseconds
						}
					}
				}
			);
		},

		groupInvitesSearchSubmit : function ( event ) {
			var self = this;
			event.preventDefault();

			var searchText = $( '#group-invites-container #group_invites_search' ).val();
			if ( '' === searchText ) {
				return false;
			}
			self.selectors.page = 1;
			if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
				self.selectors.scope = 'friends';
			} else {
				self.selectors.scope = 'members';
			}
			var data = {
				'action'       : 'groups_get_group_potential_invites',
				'nonce'        : bbRlNonces.groups,
				'group_id'     : bbRlGroupInvites.group_id,
				'scope'        : self.selectors.scope,
				'page'         : self.selectors.page,
				'search_terms' : searchText
			};

			self.selectors.feedbackSelectorLeftClass.show().parent().show();
			self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
			self.selectors.feedbackSelectorLeftClass.addClass( 'info' );
			self.selectors.feedbackParagraphTagSelectorLeft.html( bbRlGroupInvites.loading );
			var form = $( event.currentTarget ).closest( 'form' );
			form.addClass( 'is-loading' );
			$( 'button.search-form_reset' ).hide();

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					success : function ( response ) {
						if ( response.success ) {
							self.selectors.listSelector.html( response.data.html );
							self.selectors.lastSelector.html( response.data.pagination );
							self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackSelectorLeftClass.addClass( 'info' );
							self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
							$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).hide();
						} else {
							self.selectors.listSelector.html( '' );
							self.selectors.lastSelector.html( '' );
							self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackSelectorLeftClass.addClass( response.data.type );
							self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
							$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
						}
						form.removeClass( 'is-loading' );
						setTimeout( function () {
							if ( ! form.hasClass( 'is-loading' ) ) {
								$( 'button.search-form_reset' ).show();
							}
						}, 800 );
					}
				}
			);
		},

		groupCreateGroupsInvitesSearchSubmit : function ( event ) {
			var self = this;
			event.preventDefault();

			var searchText = $( '#group-invites-container #group_invites_search' ).val();
			if ( '' === searchText ) {
				return false;
			}
			self.selectors.page = 1;
			if ( $( '#bp-group-send-invite-switch-checkbox' ).is( ':checked' ) ) {
				self.selectors.scope = 'friends';
			} else {
				self.selectors.scope = 'members';
			}
			var data = {
				'action'       : 'groups_get_group_potential_invites',
				'nonce'        : bbRlNonces.groups,
				'group_id'     : bbRlGroupInvites.group_id,
				'scope'        : self.selectors.scope,
				'page'         : self.selectors.page,
				'search_terms' : searchText
			};
			var form = $( event.currentTarget ).closest( 'form' );
			form.addClass( 'is-loading' );
			$( 'button.search-form_reset' ).hide();

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					success : function ( response ) {
						if ( response.success ) {
							self.selectors.listSelector.html( response.data.html );
							self.selectors.lastSelector.html( response.data.pagination );
							self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackSelectorLeftClass.addClass( 'info' );
							self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
							$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).hide();
						} else {
							self.selectors.listSelector.html( '' );
							self.selectors.lastSelector.html( '' );
							self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackSelectorLeftClass.addClass( response.data.type );
							self.selectors.feedbackParagraphTagSelectorLeft.html( response.data.feedback );
							$( '.bb-groups-invites-left .group-invites-members-listing .bp-invites-feedback' ).show();
						}
						form.removeClass( 'is-loading' );
						setTimeout( function () {
							if ( ! form.hasClass( 'is-loading' ) ) {
								$( 'button.search-form_reset' ).show();
							}
						}, 800 );
					}
				}
			);
		},

		groupInvitesContainerNextPage : function () {
			var self = this;
			
			if (self.isLoading) {
				return;
			}
			
			self.isLoading = true;
			self.selectors.feedbackSelectorLeftClass.attr( 'class', 'bp-feedback' );
			self.selectors.feedbackSelectorLeftClass.addClass( 'loading' );
			self.selectors.feedbackParagraphTagSelectorLeft.html( bbRlGroupInvites.loading );

			var data = {
				'action'       : 'groups_get_group_potential_invites',
				'nonce'        : bbRlNonces.groups,
				'group_id'     : bbRlGroupInvites.group_id,
				'scope'        : 'invited',
				'page'         : self.selectors.page,
				'search_terms' : $( '#group-invites-container .group-invites-column #group_invites_search' ).val()
			};

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					success : function ( response ) {
						if ( response.success ) {
							self.selectors.memberInvitedList.find( '.load-more' ).remove();
							self.selectors.memberInvitedList.append( response.data.html );
							self.selectors.subNavFilterLast.html( '' );
							self.selectors.subNavFilterLast.html( response.data.pagination );
							self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackInviteColumn.addClass( 'info' );
							self.selectors.feedbackInvitePTag.html( response.data.feedback );
							self.selectors.page = self.selectors.page + 1;
						} else {
							self.selectors.memberInvitedList.html( '' );
							self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackInviteColumn.addClass( response.data.type );
							self.selectors.feedbackInvitePTag.html( response.data.feedback );
						}
						self.isLoading = false;
					}
				}
			);
		},

		groupInvitesContainerPrevPage : function () {
			var self = this;
			$( '#group-invites-container .bp-invites-feedback' ).show();
			$( '#group-invites-container .bp-invites-feedback .bp-feedback' ).addClass( 'info' );
			self.selectors.feedbackInvitePTag.html( bbRlGroupInvites.loading );
			self.selectors.page = self.selectors.page - 1;

			var data = {
				'action'       : 'groups_get_group_potential_invites',
				'nonce'        : bbRlNonces.groups,
				'group_id'     : bbRlGroupInvites.group_id,
				'scope'        : 'invited',
				'page'         : self.selectors.page,
				'search_terms' : $( '#group-invites-container .group-invites-column #group_invites_search' ).val()
			};

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					success : function ( response ) {
						if ( response.success ) {
							self.selectors.memberInvitedList.html( '' );
							self.selectors.memberInvitedList.html( response.data.html );
							self.selectors.subNavFilterLast.html( '' );
							self.selectors.subNavFilterLast.html( response.data.pagination );
							self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackInviteColumn.addClass( 'info' );
							self.selectors.feedbackInvitePTag.html( response.data.feedback );
						} else {
							self.selectors.memberInvitedList.html( '' );
							self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackInviteColumn.addClass( response.data.type );
							self.selectors.feedbackInvitePTag.html( response.data.feedback );
						}
					}
				}
			);
		},

		pendingInvitesGroupInvitesSearch : function ( event ) {
			var self = this;
			event.preventDefault();
			if ( '' === event.currentTarget.value ) {
				self.selectors.page = 1;
				var data            = {
					'action'   : 'groups_get_group_potential_invites',
					'nonce'    : bbRlNonces.groups,
					'group_id' : bbRlGroupInvites.group_id,
					'scope'    : 'invited',
					'page'     : self.selectors.page
				};

				self.selectors.feedbackInviteColumn.show().parents( '.bp-invites-feedback' ).show();
				self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
				self.selectors.feedbackInviteColumn.addClass( 'info' );
				self.selectors.feedbackInvitePTag.html( bbRlGroupInvites.loading );

				$.ajax(
					{
						type    : 'POST',
						url     : bbRlAjaxUrl,
						async   : false,
						data    : data,
						success : function ( response ) {
							if ( response.success ) {
								self.selectors.memberInvitedList.html( response.data.html );
								self.selectors.subNavFilterLast.html( response.data.pagination );
								self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackInviteColumn.addClass( 'info' );
								self.selectors.feedbackInvitePTag.html( response.data.feedback );
								self.selectors.page = self.selectors.page + 1;
								$( '#group-invites-container .bp-invites-feedback' ).hide();
							} else {
								self.selectors.memberInvitedList.html( '' );
								self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackInviteColumn.addClass( response.data.type );
								self.selectors.feedbackInvitePTag.html( response.data.feedback );
								$( '#group-invites-container .bp-invites-feedback' ).show();
							}
						}
					}
				);
			}
		},

		groupInvitesContainerSearchSubmit : function ( event ) {
			var self = this;
			event.preventDefault();

			var searchText = $( '#group-invites-container #group_invites_search' ).val();
			if ( '' === searchText ) {
				return false;
			}
			self.selectors.page = 1;
			var data            = {
				'action'       : 'groups_get_group_potential_invites',
				'nonce'        : bbRlNonces.groups,
				'group_id'     : bbRlGroupInvites.group_id,
				'scope'        : 'invited',
				'page'         : self.selectors.page,
				'search_terms' : searchText
			};

			self.selectors.feedbackInviteColumn.show().parents( '.bp-invites-feedback' ).show();
			self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
			self.selectors.feedbackInviteColumn.addClass( 'info' );
			self.selectors.feedbackInvitePTag.html( bbRlGroupInvites.loading );

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					success : function ( response ) {
						if ( response.success ) {
							self.selectors.memberInvitedList.html( response.data.html );
							self.selectors.subNavFilterLast.html( response.data.pagination );
							self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackInviteColumn.addClass( 'info' );
							self.selectors.feedbackInvitePTag.html( response.data.feedback );
							$( '#group-invites-container .bp-invites-feedback' ).hide();
						} else {
							self.selectors.memberInvitedList.html( '' );
							self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackInviteColumn.addClass( response.data.type );
							self.selectors.feedbackInvitePTag.html( response.data.feedback );
							$( '#group-invites-container .bp-invites-feedback' ).show();
						}
					}
				}
			);
		},

		groupInvitesContainerRemoveInviteButton : function ( event ) {
			var self = this;
			event.preventDefault();

			$( '#group-invites-container .group-invites-column #bp-pending-invites-loader' ).show();

			self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
			self.selectors.feedbackInviteColumn.addClass( 'loading' );
			self.selectors.feedbackInvitePTag.html( bbRlGroupInvites.removing );

			var li = $( event.currentTarget ).closest( 'li' );

			var data = {
				'action'   : 'groups_delete_group_invite',
				'nonce'    : bbRlNonces.groups,
				'_wpnonce' : bbRlGroupInvites.nonces.uninvite,
				'group_id' : bbRlGroupInvites.group_id,
				'user'     : $( event.currentTarget ).attr( 'data-bp-user-id' )
			};

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					success : function ( response ) {
						if ( response.success ) {
							li.remove();
							if ( ! response.data.has_invites ) {
								self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
								self.selectors.feedbackInviteColumn.addClass( response.data.type );
								self.selectors.feedbackInvitePTag.html( '' );
								self.selectors.feedbackInvitePTag.html( response.data.feedback );
							}
							$( '#group-invites-container .group-invites-column .bp-invites-content #members-list' ).html( '' );
							self.selectors.page = 1;
							var data            = {
								'action'   : 'groups_get_group_potential_invites',
								'nonce'    : bbRlNonces.groups,
								'group_id' : bbRlGroupInvites.group_id,
								'scope'    : 'invited',
								'page'     : self.selectors.page
							};

							$.ajax(
								{
									type    : 'POST',
									url     : bbRlAjaxUrl,
									async   : false,
									data    : data,
									success : function ( response ) {
										if ( response.success ) {
											self.selectors.memberInvitedList.html( response.data.html );
											self.selectors.subNavFilterLast.html( response.data.pagination );
											self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
											self.selectors.feedbackInviteColumn.addClass( 'info' );
											self.selectors.feedbackInvitePTag.html( response.data.feedback );
											$( '#group-invites-container .bp-invites-feedback' ).hide();
										} else {
											$( '#group-invites-container .bp-invites-feedback' ).show();
											self.selectors.memberInvitedList.html( '' );
											self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
											self.selectors.feedbackInviteColumn.addClass( response.data.type );
											self.selectors.feedbackInvitePTag.html( response.data.feedback );
										}
										$( '#group-invites-container .group-invites-column #bp-pending-invites-loader' ).hide();
									}
								}
							);
						} else {
							self.selectors.feedbackInviteColumn.attr( 'class', 'bp-feedback' );
							self.selectors.feedbackInviteColumn.addClass( response.data.type );
							self.selectors.feedbackInvitePTag.html( '' );
							self.selectors.feedbackInvitePTag.html( response.data.feedback );
							$( '#group-invites-container .group-invites-column #bp-pending-invites-loader' ).hide();
						}
					}
				}
			);
		},

		addSelect2 : function ( $input ) {

			var ArrayData = [];
			$input.select2(
				{
					placeholder        : '',
					minimumInputLength : 1,
					language           : {
						errorLoading    : function () {
							return bp_select2.i18n.errorLoading;
						},
						inputTooLong    : function ( e ) {
							var n = e.input.length - e.maximum;
							return bp_select2.i18n.inputTooLong.replace( '%%', n );
						},
						inputTooShort   : function ( e ) {
							return bp_select2.i18n.inputTooShort.replace( '%%', (
								e.minimum - e.input.length
							) );
						},
						loadingMore     : function () {
							return bp_select2.i18n.loadingMore;
						},
						maximumSelected : function ( e ) {
							return bp_select2.i18n.maximumSelected.replace( '%%', e.maximum );
						},
						noResults       : function () {
							return bp_select2.i18n.noResults;
						},
						searching       : function () {
							return bp_select2.i18n.searching;
						},
						removeAllItems  : function () {
							return bp_select2.i18n.removeAllItems;
						}
					},
					ajax               : {
						url            : bp.ajax.settings.url,
						dataType       : 'json',
						delay          : 250,
						data           : function ( params ) {
							return $.extend(
								{},
								params,
								{
									nonce  : bbRlGroupInvites.nonces.retrieve_group_members,
									action : 'groups_get_group_potential_user_send_invites',
									group  : bbRlGroupInvites.group_id
								}
							);
						},
						cache          : true,
						processResults : function ( data ) {

							// Removed the element from results if already selected.
							if ( false === jQuery.isEmptyObject( ArrayData ) ) {
								$.each(
									ArrayData,
									function ( index, value ) {
										for ( var i = 0; i < data.data.results.length; i++ ) {
											if ( data.data.results[ i ].id === value ) {
												data.data.results.splice( i, 1 );
											}
										}
									}
								);
							}

							return {
								results : data && data.success ? data.data.results : []
							};
						}
					},
					templateSelection  : function ( data ) {
						if ( ! data.id ) {
							return data.text;
						}

						return $(
							'<div class="bb-rl-select2-selection-user">' +
							'<img class="select2-user-avatar" src="' + $( data.element ).data( 'avatar' ) + '" alt=""/>' +
							'<span class="select2-selection-user__name">' + data.text + '</span>' +
							'</div>'
						);
					}
				}
			);

			// Add element into the Arrdata array.
			$input.on(
				'select2:select',
				function ( e ) {
					var data = e.params.data;
					ArrayData.push( data.id );
				}
			);

			// Remove element into the Arrdata array.
			$input.on(
				'select2:unselect',
				function ( e ) {
					var data  = e.params.data;
					ArrayData = jQuery.grep(
						ArrayData,
						function ( value ) {
							return value !== data.id;
						}
					);
				}
			);

		},

		isScrolledIntoView : function ( elem ) {
			var docViewTop    = $( window ).scrollTop();
			var docViewBottom = docViewTop + $( window ).height();

			var elemTop    = $( elem ).offset().top;
			var elemBottom = elemTop + $( elem ).height();

			return (
				(
					elemBottom <= docViewBottom
				) && (
					elemTop >= docViewTop
				)
			);
		},

		loadMoreInvitesMembers : function ( event ) {
			var self   = bp.Nouveau.GroupInvites;
			var target = $( event.currentTarget );
			if (
				(
					target[ 0 ].scrollHeight - target.scrollTop() - target.innerHeight()
				) <= 30
			) {
				var element = $( '#group-invites-container .group-invites-members-listing #members-list li.load-more' );
				if ( element.length && ! self.isLoading ) {
					$( '#group-invites-container .group-invites-members-listing .last #bp-group-invites-next-page' ).trigger( 'click' );
				}
			}
		}

	};

	// Launch BP Nouveau Groups
	bp.Nouveau.GroupInvites.start();

} )( bp, jQuery );
