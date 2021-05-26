/* global BP_ADMIN */
(function() {
	var $                             = jQuery.noConflict();
	var BbToolsCommunityRepairActions = [];
	var BbToolsForumsRepairActions    = [];

	$(
		function() {
			$( '[data-run-js-condition]' ).each(
				function() {
					var id  = $( this ).data( 'run-js-condition' );
					var tag = $( this ).prop( 'tagName' );

					$( this ).on(
						'change.run-condition',
						function() {
							if ( 'SELECT' === tag && $( this ).is( ':visible' ) ) {
								var selector = id + '-' + $( this ).val();
								$( '[class*="js-show-on-' + id + '-"]:not(.js-show-on-' + selector + ')' ).hide();
								$( '.js-show-on-' + selector ).show().find( '[data-run-js-condition]' ).trigger( 'change.run-condition' );
								return true;
							}

							var checked = $( this ).prop( 'checked' );
							if (checked && $( this ).is( ':visible' )) {
								$( '.js-hide-on-' + id ).hide();
								$( '.js-show-on-' + id ).show().find( '[data-run-js-condition]' ).trigger( 'change.run-condition' );
							} else {
								$( '.js-hide-on-' + id ).show();
								$( '.js-show-on-' + id ).hide().find( '[data-run-js-condition]' ).trigger( 'change.run-condition' );
							}
						}
					).trigger( 'change.run-condition' );
				}
			);

			var bpPages = $( document ).find( '.buddyboss_page_bp-pages .bp-admin-card' ).length;
			if ( bpPages ) {
				$(document).on('click','.create-background-page',function() {
						var dataPage = $( this ).attr( 'data-name' );
						$.ajax(
							{
								'url' : BP_ADMIN.ajax_url,
								'method' : 'POST',
								'data' : {
									'action' : 'bp_core_admin_create_background_page',
									'page' : dataPage
								},
								'success' : function( response ) {
									if ( response.success ) {
										window.location.href = response.data.url;
									}
								}
							}
						);
					}
				);
			}

			// Auto check parent search type.
			$( '.bp-search-child-field' ).on(
				'click',
				'input[type="checkbox"]',
				function( e ) {
					var $parentRow = $( e.delegateTarget ).prevAll( '.bp-search-parent-field' ).first();
					if ( $parentRow.length && e.currentTarget.checked ) {
						$parentRow.find( 'input[type="checkbox"]' ).prop( 'checked', true );
					}
				}
			);

			// Auto uncheck child search types.
			$( '.bp-search-parent-field' ).on(
				'click',
				'input[type="checkbox"]',
				function( e ) {
					var $childRows = $( e.delegateTarget ).nextUntil( '.bp-search-parent-field' );
					if ( $childRows.length && ! e.currentTarget.checked ) {
						$childRows.find( 'input[type="checkbox"]' ).prop( 'checked', false );
					}
				}
			);
		}
	);

	$( document ).ready(
		function() {
			var menuOpen = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu #toplevel_page_buddyboss-platform ul.wp-submenu li' );

				// Set Groups selected on Group Type post types.
			if ( $( 'body.buddypress.post-type-bp-group-type' ).length ) {
				var selectorGroups = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-groups"]' );
				$( menuOpen ).removeClass( 'current' );
				$( selectorGroups ).addClass( 'current' );
				$( selectorGroups ).attr( 'aria-current','page' );
				$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="bp-groups"]' ).parent().addClass( 'current' );
			}

			// Set Emails selected on email templates post types.
			if ( $( 'body.buddypress.post-type-bp-email' ).length ) {
				var selectorEmails = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-email"]' );
				$( menuOpen ).removeClass( 'current' );
				$( selectorEmails ).addClass( 'current' );
				$( selectorEmails ).attr( 'aria-current','page' );
				$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="bp-email"]' ).parent().addClass( 'current' );
			}

			// Set Forums selected on Reply post types.
			if ( $( 'body.buddypress.post-type-topic' ).length ) {
				var selector = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="post_type=forum"]' );
				$( selector ).addClass( 'current' );
				$( selector ).attr( 'aria-current','page' );
				$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="post_type=forum"]' ).parent().addClass( 'current' );
			}

			// Set Forums selected on Reply post types.
			if ( $( 'body.buddypress.post-type-reply' ).length ) {
				var selectorReply = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="post_type=forum"]' );
				$( selectorReply ).addClass( 'current' );
				$( selectorReply ).attr( 'aria-current','page' );
				$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="post_type=forum"]' ).parent().addClass( 'current' );
			}

			// Set Profile selected on Profile Type post types.
			if ( $( 'body.buddypress.post-type-bp-member-type' ).length ) {
				var selectorProfileTypes = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-profile-setup"]' );
				$( menuOpen ).removeClass( 'current' );
				$( selectorProfileTypes ).addClass( 'current' );
				$( selectorProfileTypes ).attr( 'aria-current','page' );
				$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="bp-profile-setup"]' ).parent().addClass( 'current' );
			}

			// Set Profile selected on Profile Search post types.
			if ( $( 'body.buddypress.post-type-bp_ps_form' ).length ) {
				var selectorProfileSearch = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-profile-setup"]' );
				$( menuOpen ).removeClass( 'current' );
				$( selectorProfileSearch ).addClass( 'current' );
				$( selectorProfileSearch ).attr( 'aria-current','page' );
				$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="bp-profile-setup"]' ).parent().addClass( 'current' );
			}

			// Set Tools selected on Repair Forums Page.
			if ( $( 'body.buddypress.buddyboss_page_bbp-repair' ).length ) {
				var selectorForumRepair = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-tools"]' );
				$( menuOpen ).removeClass( 'current' );
				$( selectorForumRepair ).addClass( 'current' );
				$( selectorForumRepair ).attr( 'aria-current','page' );
				$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="bp-tools"]' ).parent().addClass( 'current' );
			}

			// Set Tools selected on Import Forums Page.
			if ( $( 'body.buddypress.buddyboss_page_bbp-converter' ).length ) {
				var selectorForumImport = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-tools"]' );
				$( menuOpen ).removeClass( 'current' );
				$( selectorForumImport ).addClass( 'current' );
				$( selectorForumImport ).attr( 'aria-current','page' );
				$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="bp-tools"]' ).parent().addClass( 'current' );
			}

			// Set Tools selected on Import Media Page.
			if ( $( 'body.buddypress.buddyboss_page_bp-media-import' ).length ) {
					var selectorMediaImport = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-tools"]' );
					$( menuOpen ).removeClass( 'current' );
					$( selectorMediaImport ).addClass( 'current' );
					$( selectorMediaImport ).attr( 'aria-current','page' );
					$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="bp-tools"]' ).parent().addClass( 'current' );
			}

			// Set Tools selected on Import Profile Types Page.
			if ( $( 'body.buddypress.buddyboss_page_bp-member-type-import' ).length || $( 'body.buddypress.buddyboss_page_bp-repair-community' ).length ) {
					var selectorProfileImport = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-tools"]' );
					$( menuOpen ).removeClass( 'current' );
					$( selectorProfileImport ).addClass( 'current' );
					$( selectorProfileImport ).attr( 'aria-current','page' );
					$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="bp-tools"]' ).parent().addClass( 'current' );
			}

			// Set Moderation selected on Reporting category
			if ($('body.buddypress.taxonomy-bpm_category').length) {
				var menus = $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu li');
				var buddyBossMenu = $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform');
				var selectorModeration = $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-moderation"]');

				$(menus).removeClass('wp-has-current-submenu');
				$(menus).removeClass('wp-menu-open');
				$(buddyBossMenu).addClass('wp-has-current-submenu wp-menu-open');

				$(menuOpen).removeClass('current');
				$(selectorModeration).addClass('current');
				$(selectorModeration).attr('aria-current', 'page');
				$('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li').find('a[href*="bp-moderation"]').parent().addClass('current');
			}

			if ( $( 'body .section-bp_search_settings_community' ).length ) {

				if ($( 'body .section-bp_search_settings_community table td input:checkbox:checked' ).length === $( 'body .section-bp_search_settings_community table td input:checkbox' ).length) {
					$( '#bp_search_select_all_components' ).prop( 'checked', true );
				}

					$( '#bp_search_select_all_components' ).click(
						function () {
							var table = $( 'body .section-bp_search_settings_community table' );
							$( 'td input:checkbox', table ).prop( 'checked', this.checked );
						}
					);

					$( 'body .section-bp_search_settings_community table td input:checkbox' ).click(
						function () {
							if ($( 'body .section-bp_search_settings_community table td input:checkbox:checked' ).length === $( 'body .section-bp_search_settings_community table td input:checkbox' ).length) {
								$( '#bp_search_select_all_components' ).prop( 'checked', true );
							} else {
								$( '#bp_search_select_all_components' ).prop( 'checked', false );
							}

							if ( 'bp_search_topic_tax_topic-tag' === $( this ).attr( 'id' ) && true === $( this ).prop( 'checked' ) && false === $( '#bp_search_post_type_topic' ).prop( 'checked' ) ) {
								$( '#bp_search_post_type_topic' ).prop( 'checked', true );
							}

							if ( 'bp_search_post_type_topic' === $( this ).attr( 'id' ) && true !== $( this ).prop( 'checked' ) && true === $( '#bp_search_topic_tax_topic-tag' ).prop( 'checked' ) ) {
								$( '#bp_search_topic_tax_topic-tag' ).prop( 'checked', false );
							}
						}
					);
			}

			if ( $( 'body .section-bp_search_settings_post_types' ).length ) {

				if ($( 'body .section-bp_search_settings_post_types table td input:checkbox:checked' ).length === $( 'body .section-bp_search_settings_post_types table td input:checkbox' ).length) {
					$( '#bp_search_select_all_post_types' ).prop( 'checked', true );
				}

				$( '#bp_search_select_all_post_types' ).click(
					function () {
						var table = $( 'body .section-bp_search_settings_post_types table' );
						$( 'td input:checkbox', table ).prop( 'checked', this.checked );
					}
				);

				$( 'body .section-bp_search_settings_post_types table td input:checkbox' ).click(
					function () {
						if ($( 'body .section-bp_search_settings_post_types table td input:checkbox:checked' ).length === $( 'body .section-bp_search_settings_post_types table td input:checkbox' ).length) {
							$( '#bp_search_select_all_post_types' ).prop( 'checked', true );
						} else {
							$( '#bp_search_select_all_post_types' ).prop( 'checked', false );
						}
					}
				);
			}

			if ( $( 'body .section-bp_document_settings_extensions' ).length ) {


				$( document ).find( '.nav-settings-subsubsub .subsubsub li.bp-media a' ).addClass( 'current' );

				if ($( 'body .section-bp_document_settings_extensions table tbody tr td table tbody tr td input:checkbox:checked' ).length === $( 'body .section-bp_document_settings_extensions table tbody tr td table tbody tr td input:checkbox' ).length) {
					$( '#bp_select_extensions' ).prop( 'checked', true );
				}

				$( '#bp_select_extensions' ).click(
					function () {
						var table = $( 'body .section-bp_document_settings_extensions table tbody tr td table tbody tr' );
						$( 'td input:checkbox', table ).prop( 'checked', this.checked );
					}
				);

				$( 'body .section-bp_document_settings_extensions table tbody tr td table tbody tr td input:checkbox' ).click(
					function () {
						if ($( 'body .section-bp_document_settings_extensions table tbody tr td table tbody tr td input:checkbox:checked' ).length === $( 'body .section-bp_document_settings_extensions table tbody tr td table tbody tr td input:checkbox' ).length) {
							$( '#bp_select_extensions' ).prop( 'checked', true );
						} else {
							$( '#bp_select_extensions' ).prop( 'checked', false );
						}
					}
				);

				$('form').submit(function () {
					var error = false;
					$('body .section-bp_document_settings_extensions table tbody tr td table tbody tr.document-extensions td [type="text"]').each(function() {
						var value = $.trim( $(this).val() );
						if ( '' === value ) {
							$( this ).addClass( 'error' );
							error = true;
						} else if ( $( this ). hasClass( 'error' ) ) {
							$( this ).removeClass( 'error' );
						}
					});
					if ( error ) {
						return false;
					}
				});
			}

			if ( $( '.buddyboss_page_bp-activity' ).length ) {
					$( document ).on(
						'click',
						'.activity-attached-gif-container',
						function ( e ) {
							e.preventDefault();
							var video = $( this ).find( 'video' ).get( 0 ),
							$button   = $( this ).find( '.gif-play-button' );
							if ( true === video.paused ) {
								// Play the video.
								video.play();

								// Update the button text to 'Pause'.
								$button.hide();
							} else {
								// Pause the video.
								video.pause();

								// Update the button text to 'Play'.
								$button.show();
							}
						}
					);
			}

			// Set Help selected on Help/Documentation Page.
			if ( $( 'body.buddyboss_page_bp-help' ).length ) {

					// Show sub menu when user click on main menu.
					$( '.bp-help-card-grid' ).on(
						'click',
						'span.open',
						function () {

							$( this ).toggleClass( 'active' );
							$( this ).closest( '.main' ).find( 'ul:first' ).toggle();
						}
					);

					// show the closest UI.
					$( '.bp-help-card-grid li.selected' ).closest( 'ul' ).show().closest( 'li' ).find( '> span.actions .open' ).addClass( 'active' );

					// Show the child sub menu
					$( '.bp-help-card-grid li.selected' ).find( 'ul:first' ).show();
					$( '.bp-help-card-grid li.selected' ).find( '> span.actions .open' ).addClass( 'active' );

					// Update LI count via JS.
					$( '.bp-help-card-grid .sub-menu-count' ).each(
						function () {
								$( this ).text( '(' + $( this ).closest( 'li' ).find( 'ul:first li' ).size() + ')' );
						}
					);
			}

			// As soon as an admin selects the option "Hierarchies - Allow groups to have subgroups" they
			// should instantly see the option to "Restrict Invitations".
			// We should also make it so deselect "hierarchies" will automatically deselect "restrict invitations" to
			// prevent any unwanted errors.
			if ( $( '.buddyboss_page_bp-settings .section-bp_groups_hierarchies' ).length ) {

					var checkbox = document.getElementById( 'bp-enable-group-hierarchies' );

				if (checkbox.checked) {
					$( '.bp-enable-group-restrict-invites, .bp-enable-group-hide-subgroups' ).show();
				} else {
					$( '.bp-enable-group-restrict-invites, .bp-enable-group-hide-subgroups' ).hide();
				}

					$( document ).on(
						'click',
						'#bp-enable-group-hierarchies',
						function () {
							if ( true === this.checked ) {
								$( '.bp-enable-group-restrict-invites, .bp-enable-group-hide-subgroups' ).show();
							} else {
								$( '.bp-enable-group-restrict-invites, .bp-enable-group-hide-subgroups' ).hide();
								$( '#bp-enable-group-restrict-invites, #bp-enable-group-hide-subgroups' ).prop( 'checked', false );
							}
						}
					);
			}

			$( '#bp_media_profile_media_support' ).change(
				function () {
					if ( ! this.checked) {
						$( '#bp_media_profile_albums_support' ).prop( 'disabled', true );
						$( '#bp_media_profile_albums_support' ).attr( 'checked', false );
					} else {
						$( '#bp_media_profile_albums_support' ).prop( 'disabled', false );
					}
				}
			);

			$( '#bp_media_group_media_support' ).change(
				function () {
					if ( ! this.checked) {
						$( '#bp_media_group_albums_support' ).prop( 'disabled', true );
						$( '#bp_media_group_albums_support' ).attr( 'checked', false );
					} else {
						$( '#bp_media_group_albums_support' ).prop( 'disabled', false );
					}
				}
			);

			/**
			 * Admin Tools Default data setting Page
			 */
			if ( $( '.buddyboss_page_bp-tools .section-default_data' ).length ) {
					jQuery( '.bp-admin-form .checkbox' ).click(
						function () {

							if ( jQuery( this ).attr( 'id' ) === 'import-f-replies' ) {
								  jQuery( '#import-f-topics' ).prop( 'checked', true );
							}

							if ( jQuery( this ).attr( 'id' ) === 'import-f-topics' ) {
								jQuery( '#import-f-replies' ).prop( 'checked', false );
							}

							if ( jQuery( this ).attr( 'checked' ) === 'checked' && ! jQuery( this ).closest( 'li.main' ).find( '.main-header' ).attr( 'disabled' ) ) {
								jQuery( this ).closest( 'li.main' ).find( '.main-header' ).prop( 'checked', true );
							}
						}
					);

					jQuery( '.bp-admin-form #import-groups, .bp-admin-form #import-users, .bp-admin-form #import-forums' ).click(
						function () {
							if ( jQuery( this ).attr( 'checked' ) !== 'checked' ) {
									 jQuery( this ).closest( 'li' ).find( 'ul .checkbox' ).prop( 'checked', false );
							}
						}
					);

					jQuery( '.bp-admin-form #bp-admin-submit' ).click(
						function () {
							if ( confirm( BP_ADMIN.tools.default_data.submit_button_message ) ) {
									 return true;
							}

								return false;
						}
					);

					jQuery( '.bp-admin-form #bp-admin-clear' ).click(
						function () {
							if ( confirm( BP_ADMIN.tools.default_data.clear_button_message ) ) {
									 return true;
							}
								return false;
						}
					);
			}

			var doFitVids = function() {
				setTimeout(
					function () {
						$( 'iframe[src*="youtube"], iframe[src*="vimeo"]' ).parent().fitVids();
					},
					300
				);
			};
			doFitVids();

			var bp_media_import_send_status_requests = function() {
					$.ajax(
						{
							'url' : BP_ADMIN.ajax_url,
							'method' : 'POST',
							'data' : {
								'action' : 'bp_media_import_status_request',
							},
							'success' : function( response ) {
								if ( typeof response.success !== 'undefined' && typeof response.data !== 'undefined' ) {
									var total_media   = response.data.total_media;
									var total_albums  = response.data.total_albums;
									var albums_done   = response.data.albums_done;
									var media_done    = response.data.media_done;
									var import_status = response.data.import_status;

									$( '#bp-media-import-albums-total' ).text( total_albums );
									$( '#bp-media-import-media-total' ).text( total_media );
									$( '#bp-media-import-albums-done' ).text( albums_done );
									$( '#bp-media-import-media-done' ).text( media_done );

									if ( import_status == 'reset_albums' ||
									import_status == 'reset_media' ||
									import_status == 'reset_forum' ||
									import_status == 'reset_topic' ||
									import_status == 'reset_reply' ||
									import_status == 'reset_options'
									) {
										$( '#bp-media-resetting' ).show();
									} else {
										$( '#bp-media-resetting' ).hide();
									}

									if ( import_status == 'done' && total_albums == albums_done && total_media == media_done ) {
										$( '#bp-media-import-msg' ).text( response.data.success_msg );
										$( '#bp-media-import-submit' ).show();
									} else {
										bp_media_import_send_status_requests();
									}
								} else {
									$( '#bp-media-import-msg' ).text( response.data.error_msg );
								}
							},
							'error' : function() {

							}
						}
					);
			};

			if ( $( '#bp-media-import-updating' ).length ) {
					bp_media_import_send_status_requests();
			}

			// Show/Hide options ( Display Name Fields ) based on the ( Display Name Format ) selected.
			if ( $( '.display-options' ).length ) {

					var selectorAll    = $( '.display-options' );
					var displayOptions = $( 'select[name=bp-display-name-format]' );
					var currentValue   = displayOptions.val();

					$( selectorAll ).each(
						function() {
							$( this ).hide();
						}
					);

				if ( 'first_name' === currentValue ) {
					$( '.first-name-options' ).show();
					$( '.nick-name-options' ).hide();
					$( '.first-last-name-options' ).hide();
				} else if ( 'first_last_name' === currentValue ) {
					$( '.first-last-name-options' ).show();
					$( '.first-name-options' ).hide();
					$( '.nick-name-options' ).hide();
				} else {
					$( '.nick-name-options' ).show();
					$( '.first-name-options' ).hide();
					$( '.first-last-name-options' ).hide();
				}

					$( displayOptions ).change(
						function () {

							$( selectorAll ).each(
								function() {
									$( this ).hide();
								}
							);

							currentValue = $( this ).val();

							if ( 'first_name' === currentValue ) {
								$( '.first-name-options' ).show();
								$( '.nick-name-options' ).hide();
								$( '.first-last-name-options' ).hide();
							} else if ( 'first_last_name' === currentValue ) {
								$( '.first-last-name-options' ).show();
								$( '.first-name-options' ).hide();
								$( '.nick-name-options' ).hide();
							} else {
								$( '.nick-name-options' ).show();
								$( '.first-name-options' ).hide();
								$( '.first-last-name-options' ).hide();
							}

						}
					);
			}

			// For Profile layout options.
			var profileSelectorType = $( '.profile-layout-options' );
			if ( profileSelectorType.length ) {

				var profileSelectorOptions = $( 'select[name=bp-profile-layout-format]' );
				var profileView            = profileSelectorOptions.val();

				$( profileSelectorType ).each(
					function() {
						$( this ).hide();
					}
				);

				if ( 'list_grid' === profileView ) {
					$( '.profile-default-layout' ).show();
				} else {
					$( '.profile-default-layout' ).hide();
				}

				$( profileSelectorOptions ).change(
					function () {

						$( profileSelectorType ).each(
							function() {
								$( this ).hide();
							}
						);

						profileView = $( this ).val();

						if ( 'list_grid' === profileView ) {
							$( '.profile-default-layout' ).show();
						} else {
							$( '.profile-default-layout' ).hide();
						}

					}
				);

				/* jshint ignore:start */
				var getCookies      = function(){
					var pairs   = document.cookie.split( ';' );
					var cookies = {};
					for (var i = 0; i < pairs.length; i++) {
						var pair                       = pairs[i].split( '=' );
						cookies[(pair[0] + '').trim()] = unescape( pair.slice( 1 ).join( '=' ) );
					}
					return cookies;
				};
				var getResetCookies = getCookies();
				if ( getResetCookies.reset_member ) {
					localStorage.setItem( 'bp-members', '' );
					localStorage.setItem( 'bp-group_members', '' );
					setCookie( 'reset_member','',0 ); // this will delete the cookie.
				}
				/* jshint ignore:end */

			}

			// For Group layout options.
			var groupSelectorType = $( '.group-layout-options' );
			if ( groupSelectorType.length ) {

				var groupSelectorOptions = $( 'select[name=bp-group-layout-format]' );
				var groupView            = groupSelectorOptions.val();

				$( groupSelectorType ).each(
					function() {
						$( this ).hide();
					}
				);

				if ( 'list_grid' === groupView ) {
					$( '.group-default-layout' ).show();
				} else {
					$( '.group-default-layout' ).hide();
				}

				$( groupSelectorOptions ).change(
					function () {

						$( groupSelectorType ).each(
							function() {
								$( this ).hide();
							}
						);

						groupView = $( this ).val();

						if ( 'list_grid' === groupView ) {
							$( '.group-default-layout' ).show();
						} else {
							$( '.group-default-layout' ).hide();
						}

					}
				);

				/* jshint ignore:start */
				var getGroupCookies      = function(){
					var pairs   = document.cookie.split( ';' );
					var cookies = {};
					for (var i = 0; i < pairs.length; i++) {
						var pair                       = pairs[i].split( '=' );
						cookies[(pair[0] + '').trim()] = unescape( pair.slice( 1 ).join( '=' ) );
					}
					return cookies;
				};
				var getGroupResetCookies = getGroupCookies();
				if ( getGroupResetCookies.reset_group ) {
					localStorage.setItem( 'bp-groups', '' );
					setCookie( 'reset_group','',0 ); // this will delete the cookie.
				}
				/* jshint ignore:end */

			}

			if ( $( '#bp-tools-submit' ).length ) {

					var bp_admin_repair_tools_wrapper_function = function( offset, currentAction ) {
						if ( typeof BbToolsCommunityRepairActions[currentAction] === 'undefined' ) {
							return false;
						}
						$( 'body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"]' ).append( '<div class="loader-repair-tools"></div>' );
						$.ajax(
							{
								'url' : BP_ADMIN.ajax_url,
								'method' : 'POST',
								'data' : {
									'action' : 'bp_admin_repair_tools_wrapper_function',
									'type' : BbToolsCommunityRepairActions[currentAction],
									'offset' : offset,
									'nonce' : $( 'body .section-repair_community .settings fieldset .submit input[name="_wpnonce"]' ).val()
								},
								'success' : function( response ) {
									if ( typeof response.success !== 'undefined' ) {
										if ( response.success && typeof response.data !== 'undefined' ) {
											if ('running' === response.data.status) {
												$('body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"] .loader-repair-tools').remove();
												$('body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"] code').remove();
												$('body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"]').append('<code>' + response.data.records + '</code>');
												bp_admin_repair_tools_wrapper_function(response.data.offset, currentAction);
											} else {
												$('body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"] .loader-repair-tools').remove();
												$('.section-repair_community .settings fieldset').append('<div class="updated"><p>' + response.data.message + '</p></div>');
												currentAction = currentAction + 1;
												bp_admin_repair_tools_wrapper_function(response.data.offset, currentAction);
											}
											if (BbToolsCommunityRepairActions.length === currentAction) {
												$('body .section-repair_community .settings fieldset .submit a').removeClass('disable-btn');
											}
										} else {
											$( 'body .section-repair_community .settings fieldset .submit a' ).removeClass( 'disable-btn' );
											return false;
										}
									}
								},
								'error' : function() {
									$( 'body .section-repair_community .settings fieldset .submit a' ).removeClass( 'disable-btn' );
									return false;
								}
							}
						);
					};

					$( document ).on(
						'click',
						'#bp-tools-submit',
						function( e ) {
							e.preventDefault();

							BbToolsCommunityRepairActions = [];

							setTimeout(
								function () {
									$( 'body .section-repair_community .settings fieldset .updated' ).remove();
								},
								500
							);

							$.each(
								$( '.section-repair_community .settings fieldset .checkbox input[class="checkbox"]:checked' ),
								function(){
									BbToolsCommunityRepairActions.push( $( this ).val() );
								}
							);

							if ( BbToolsCommunityRepairActions.length ) {
								$( 'body .section-repair_community .settings fieldset .submit a' ).addClass( 'disable-btn' );
								$( 'body .section-repair_community .settings fieldset .checkbox code' ).remove();
								bp_admin_repair_tools_wrapper_function( 1, 0 );
							}
						}
					);
			}

			if ( $( '#bp-tools-forum-submit' ).length ) {
				var bp_admin_forum_repair_tools_wrapper_function = function( offset, currentAction ) {
					$( 'body .section-repair_forums .settings fieldset .checkbox label[for="' + BbToolsForumsRepairActions[currentAction] + '"]' ).append( '<div class="loader-repair-tools"></div>' );
					if ( typeof BbToolsForumsRepairActions[currentAction] !== 'undefined' ) {
						$.ajax(
							{
								'url': BP_ADMIN.ajax_url,
								'method': 'POST',
								'data': {
									'action': 'bp_admin_forum_repair_tools_wrapper_function',
									'type': BbToolsForumsRepairActions[currentAction],
									'offset': offset,
									'site_id': $('body .section-repair_forums #bbp-network-site').val(),
									'nonce': $( 'body .section-repair_forums .settings fieldset .submit input[name="_wpnonce"]' ).val()
								},
								'success': function (response) {
									if (typeof response.success !== 'undefined' && typeof response.data !== 'undefined') {
										if ('running' === response.data.status) {
											$( 'body .section-repair_forums .settings fieldset .checkbox label[for="' + BbToolsForumsRepairActions[currentAction] + '"] .loader-repair-tools' ).remove();
											$( 'body .section-repair_forums .settings fieldset .checkbox label[for="' + BbToolsForumsRepairActions[currentAction] + '"] code' ).remove();
											$( 'body .section-repair_forums .settings fieldset .checkbox label[for="' + BbToolsForumsRepairActions[currentAction] + '"]' ).append( '<code>' + response.data.records + '</code>' );
											bp_admin_forum_repair_tools_wrapper_function( response.data.offset, currentAction );
										} else {
											$( 'body .section-repair_forums .settings fieldset .checkbox label[for="' + BbToolsForumsRepairActions[currentAction] + '"] .loader-repair-tools' ).remove();
											if ( response.data.status === 0 ) {
												$( '.section-repair_forums .settings fieldset' ).append( '<div class="error"><p>' + response.data.message + '</p></div>' );
											} else {
												$( '.section-repair_forums .settings fieldset' ).append( '<div class="updated"><p>' + response.data.message + '</p></div>' );
											}
											currentAction = currentAction + 1;
											bp_admin_forum_repair_tools_wrapper_function( response.data.offset, currentAction );
										}
										if (BbToolsForumsRepairActions.length === currentAction) {
											$( 'body .section-repair_forums .settings fieldset .submit a' ).removeClass( 'disable-btn' );
										}
									}
								},
								'error': function () {
									$( 'body .section-repair_forums .settings fieldset .submit a' ).removeClass( 'disable-btn' );
									return false;
								}
							}
						);
					}
				};

				$( document ).on(
					'click',
					'#bp-tools-forum-submit',
					function( e ) {
						e.preventDefault();

						BbToolsForumsRepairActions = [];
						var $bbp_network_site = $('body .section-repair_forums #bbp-network-site');

						if ($bbp_network_site.length && '0' === $bbp_network_site.val() ) {
							alert(BP_ADMIN.tools.repair_forums.validate_site_id_message);
							return false;
						}

						setTimeout(
							function () {
								$( 'body .section-repair_forums .settings fieldset .updated' ).remove();
							},
							500
						);

						$.each(
							$( '.section-repair_forums .settings fieldset .checkbox input[class="checkbox"]:checked' ),
							function(){
								BbToolsForumsRepairActions.push( $( this ).val() );
							}
						);

						if ( BbToolsForumsRepairActions.length ) {
							$( 'body .section-repair_forums .settings fieldset .submit a' ).addClass( 'disable-btn' );
							$( 'body .section-repair_forums .settings fieldset .checkbox code' ).remove();
							bp_admin_forum_repair_tools_wrapper_function( 1, 0 );
						}
					}
				);
			}

			// Registration Settings Show/Hide.
			var registrationSettings = $( '#allow-custom-registration' );
			var currentSettings 	 = 0;

			if ( registrationSettings.length ) {
				currentSettings = parseInt( registrationSettings.val() );

				if ( 0 === currentSettings ) {
					$( '.register-text-box' ).hide();
					$( '.register-email-checkbox' ).show();
					$( '.register-password-checkbox' ).show();
					$( '.register-legal-agreement-checkbox' ).show();
				} else {
					$( '.register-email-checkbox' ).hide();
					$( '.register-password-checkbox' ).hide();
					$( '.register-legal-agreement-checkbox' ).hide();
					$( '.register-text-box' ).show();
				}

				$( registrationSettings ).change(
					function () {
							currentSettings = parseInt( $( this ).val() );
						if ( 0 === currentSettings ) {
							  $( '.register-text-box' ).hide();
							  $( '.register-email-checkbox' ).show();
							  $( '.register-password-checkbox' ).show();
							  $( '.register-legal-agreement-checkbox' ).show();
							  $( '.registration-form-main-select p.description' ).show();
						} else {
							  $( '.register-email-checkbox' ).hide();
							  $( '.register-password-checkbox' ).hide();
							  $( '.register-legal-agreement-checkbox' ).hide();
							  $( '.register-text-box' ).show();
							  $( '.registration-form-main-select p.description' ).hide();
						}

					}
				);
			}

			$( document ).on(
				'click',
				'table.extension-listing #btn-add-extensions',
				function() {
					var parent     = $( this ).closest( 'table.extension-listing' );
					var newOption  = $( this ).closest( 'table.extension-listing' ).find( 'tbody tr.custom-extension-data' ).html();
					var totalCount = 1;
					parent.find( 'tbody' ).append( ' <tr class="custom-extension extra-extension document-extensions"> ' + newOption + ' </tr> ' );


					makeIconSelect();

					parent.find( 'tbody tr.extra-extension' ).each(
						function() {
								$( this ).find( 'input.extension-check' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][is_active]' );
								$( this ).find( 'input.extension-check' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][is_active]' );
								$( this ).find( 'input.extension-name' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][name]' );
								$( this ).find( 'input.extension-name' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][name]' );
								$( this ).find( 'input.extension-hidden' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][hidden]' );
								$( this ).find( 'input.extension-hidden' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][hidden]' );
								$( this ).find( 'input.extension-extension' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][extension]' );
								$( this ).find( 'input.extension-extension' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][extension]' );
								$( this ).find( 'select.extension-icon' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][icon]' );
								$( this ).find( 'select.extension-icon' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][icon]' );
								$( this ).find( 'input.extension-mime' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][mime_type]' );
								$( this ).find( 'input.extension-mime' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][mime_type]' );
								$( this ).find( 'input.extension-desc' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][description]' );
								$( this ).find( 'input.extension-desc' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][description]' );
								$( this ).find( 'a.btn-check-mime-type' ).attr( 'id', 'bp_document_extensions_support[' + totalCount + '][mime_type]' );
								totalCount = totalCount + 1;
						}
					);

					totalCount = parseInt( $( '.extension-listing tr.default-extension' ).length );

				}
			);

			function makeIconSelect() {

				$('.document-extensions-listing .extension-icon').each( function() {

					if( $( this ).closest('td').find( '.icon-select-main' ).length === 0 ) {
						var iconsArray = [];
						$( this ).closest('td').find('select.extension-icon option').each( function(){
							var iconClass = $(this).val();
							var text = this.innerText;
							var item = '<li><i class="'+ iconClass +'"></i><span>'+ text + '</span></li>';
							iconsArray.push(item);
						});

						$( this ).closest('td').find('select.extension-icon').parent().append('<div class="icon-select-main"><span class="icon-select-button"></span><div class="custom-extension-list"> <ul class="custom-extension-list-select">'+ iconsArray +'</ul></div></div>');

						//Set the button value to the first el of the array by default
						var currentSelectedIcon = $( this ).closest('td').find( '.extension-icon' ).val();
						var currentSelectedIconText = $( this ).closest('td').find( '.extension-icon option:selected' ).text();
						$( this ).closest('td').find( '.icon-select-main .icon-select-button' ).html('<li><i class="'+ currentSelectedIcon +'"></i><span>'+ currentSelectedIconText +'</span></li>');
					}

				});

			}

			makeIconSelect();

			$( document ).on( 'click', '.custom-extension-list-select li', function() {
				var iconClass = $( this ).find( 'i' ).attr( 'class' );
				var text = this.innerText;
				var item = '<li><i class="'+ iconClass +'"></i><span>'+ text +'</span></li>';
				$( this ).closest( 'td' ).find( '.icon-select-main .icon-select-button' ).html( item );
				$( this ).closest( 'td' ).find( '.icon-select-main .custom-extension-list' ).toggle();
				$( this ).closest( 'td' ).find( 'select.extension-icon option[value="'+ iconClass +'"]' ).attr( 'selected','selected' );
				if( $(this).closest( '.icon-select-main' ).siblings( '.bb-icon' ).length ) {
					$(this).closest( '.icon-select-main' ).siblings( '.bb-icon' ).attr( 'class', 'bb-icon ' + iconClass );
				}
			});

			$( document ).on( 'click', '.icon-select-main .icon-select-button', function() {
				$( this ).siblings( '.custom-extension-list' ).toggle();
			});

			$( document ).on(
				'click',
				'table.extension-listing #btn-remove-extensions',
				function() {

					var parent = $( this ).closest( 'table.extension-listing' );
					console.log ( parseInt( $( '.extension-listing tr.extra-extension' ).length ) );
					$( this ).closest( 'tr' ).remove();
					console.log ( parseInt( $( '.extension-listing tr.extra-extension' ).length ) );
					var totalCount = parseInt( $( '.extension-listing tr.extra-extension' ).length );
					totalCount = 1;
					parent.find( 'tbody tr.extra-extension' ).each(
						function() {
							console.log ( totalCount );
								$( this ).find( 'input.extension-check' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][is_active]' );
								$( this ).find( 'input.extension-check' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][is_active]' );
								$( this ).find( 'input.extension-name' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][name]' );
								$( this ).find( 'input.extension-name' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][name]' );
								$( this ).find( 'input.extension-hidden' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][hidden]' );
								$( this ).find( 'input.extension-hidden' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][hidden]' );
								$( this ).find( 'input.extension-extension' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][extension]' );
								$( this ).find( 'input.extension-extension' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][extension]' );
								$( this ).find( 'input.extension-mime' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][mime_type]' );
								$( this ).find( 'input.extension-mime' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][mime_type]' );
								$( this ).find( 'input.extension-desc' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][description]' );
								$( this ).find( 'input.extension-desc' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][description]' );
								$( this ).find( 'select.extension-icon' ).attr( 'name', 'bp_document_extensions_support[' + totalCount + '][icon]' );
								$( this ).find( 'select.extension-icon' ).attr( 'data-name', 'bp_document_extensions_support[' + totalCount + '][icon]' );
								totalCount = totalCount + 1;
						}
					);

				}
			);

			$( document ).on(
				'click',
				'#input-mime-type-submit-check',
				function(e) {
					e.preventDefault();
					var file_data = $( '#bp-document-file-input' ).prop( 'files' )[0];
					if ( 'undefined' === typeof file_data ) {
						alert( BP_ADMIN.select_document );
						return false;
					}
					var form_data = new FormData();
					form_data.append( 'file', file_data );
					form_data.append( 'action', 'bp_document_check_file_mime_type' );
					$.ajax(
						{
							url: BP_ADMIN.ajax_url, // point to server-side PHP script.
							cache: false,
							contentType: false,
							processData: false,
							data: form_data,
							type: 'post',
							success: function( response ){
								$( '.show-document-mime-type' ).show();
								$( '.show-document-mime-type input.type' ).val( response.data.type );
							}
						}
					);
				}
			);

			$( document ).on(
				'click',
				'.show-document-mime-type .mime-copy',
				function(e) {
					e.preventDefault();

					var mimeToId =  $( this ).attr( 'id' );

					console.log( mimeToId );
					$( document ).find( 'input[name="' + mimeToId + '"]' ).val('');
					var valueCopied = $( document ).find( '#mime-type' ).val();
					console.log( valueCopied );
					$( document ).find( 'input[name="' + mimeToId + '"]' ).val( valueCopied );
					$( document ).find( '.close-modal' ).trigger( 'click' );
				}
			);

			$( document ).on(
				'click',
				'.btn-check-mime-type',
				function(e) {
					e.preventDefault();

					var copiedValue = $( this ).attr( 'id' );
					$( document ).find( '.mime-copy' ).attr( 'id', copiedValue );
					$( document ).find( '.bp-hello-mime').attr( 'id', 'bp-hello-container' );
					if ( $( document ).find( '#bp-hello-backdrop' ).length ) {
					} else {
						var finder = $( document ).find( '.bp-hello-mime' );
						$( '<div id="bp-hello-backdrop" style="display: none;"></div>' ).insertBefore( finder );
					}
					var backdrop = document.getElementById( 'bp-hello-backdrop' ),
						modal    = document.getElementById( 'bp-hello-container' );

					if ( null === backdrop ) {
						return;
					}
					document.body.classList.add( 'bp-disable-scroll' );

					// Show modal and overlay.
					backdrop.style.display = '';
					modal.style.display    = '';

					// Focus the "X" so bp_hello_handle_keyboard_events() works.
					var focus_target = modal.querySelectorAll( 'a[href], button' );
					focus_target     = Array.prototype.slice.call( focus_target );
					focus_target[0].focus();

				}
			);

			document.addEventListener(
				'click',
				function( event ) {
					var backdrop = document.getElementById( 'bp-hello-backdrop' );
					if ( ! backdrop || ! document.getElementById( 'bp-hello-container' ) ) {
						return;
					}

					var backdrop_click = backdrop.contains( event.target ),
						modal_close_click  = event.target.classList.contains( 'close-modal' );

					if ( !modal_close_click && !backdrop_click ) {
						return;
					}

					$( document ).find( '#bp-document-file-input' ).val( '' );
					$( document ).find( '.show-document-mime-type' ).hide();
					$( document ).find( '.show-document-mime-type input#mime-type' ).val( '' );
				},
				false
			);

			// Show the moderation activate popup when admin click spam user link.
			$( document ).on( 'click', '.bp-show-moderation-alert', function () {
				var ActionType = $(this).attr('data-action');
				$( '#bp-hello-backdrop' ).show();
				$( '#bp-hello-container' ).find( '.bp-spam-action-msg' ).hide();
				$( '#bp-hello-container' ).find( '.bp-not-spam-action-msg' ).hide();
				if( 'spam' === ActionType ){
					$( '#bp-hello-container' ).find( '.bp-spam-action-msg' ).show();
				}else if( 'not-spam' === ActionType ){
					$( '#bp-hello-container' ).find( '.bp-not-spam-action-msg' ).show();
				}
				$( '#bp-hello-container' ).show().addClass('deactivation-popup');
			} );

			// Show the confirmation popup when user clicks single BB component disable link.
			$( document ).on( 'click', '.bp-show-deactivate-popup a', function ( event ) {
				event.preventDefault();
				$( '#bp-hello-container' ).find( '.bp-hello-content' ).empty();
				$( '#bp-hello-backdrop' ).show();
				$( '#bp-hello-container' ).show().addClass('deactivation-popup');
				$( '#bp-hello-container' ).find( '.component-deactivate' ).attr( 'data-redirect', $( this ).attr( 'href' ) );
				$( '#bp-hello-container' ).find( '.bp-hello-content' ).append( $( this ).parent().closest( '.row-actions' ).find( '.component-deactivate-msg' ).text() );
			} );

			// Close popup.
			$( document ).on( 'click', '.close-modal', function () {
				$( '#bp-hello-backdrop' ).hide();
				$( '#bp-hello-container' ).hide().removeClass('deactivation-popup');
				$( '#bp-hello-container' ).find( '.component-deactivate' ).removeClass( 'form-submit' );
			} );

			// Disable the component when click appropriate button in popup.
			$( document ).on( 'click', '.component-deactivate', function ( event ) {
				event.preventDefault();
				if ( $( this ).hasClass( 'form-submit' ) ) {
					$( 'form#bp-admin-component-form' ).find( 'input[name="bp-admin-component-submit"]' ).trigger( 'click' );
				} else {
					window.location = $( this ).attr( 'data-redirect' );
				}
			} );

			// Show the confirmation popup when bulk component disabled
			$( 'form#bp-admin-component-form' ).submit( function ( e ) {

				var action = $( '#bulk-action-selector-top[name="action"]' ).find( ':selected' ).val();

				if ( ! action ) {
					action = $( '#bulk-action-selector-top[name="action2"]' ).find( ':selected' ).val();
				}

				var confirmChecked = false;
				var msg = '';
				$( '.mass-check-deactivate' ).each( function () {
					if ( $( this ).prop( 'checked' ) == true ) {
						confirmChecked = true;
					}
					msg = msg + $( this ).parent().find( '.component-deactivate-msg' ).text();
				} );

				if ( !$( '#bp-hello-container' ).find( '.component-deactivate' ).hasClass( 'form-submit' ) && 'inactive' === action && true === confirmChecked ) {
					$( '#bp-hello-container' ).find( '.bp-hello-content' ).empty();
					if ( msg ) {
						e.preventDefault();
						$( '#bp-hello-backdrop' ).show();
						$( '#bp-hello-container' ).show().addClass('deactivation-popup');
						$( '#bp-hello-container' ).find( '.bp-hello-content' ).html( msg );
						$( '#bp-hello-container' ).find( '.component-deactivate' ).addClass( 'form-submit' );
					}
				}
			} );

			//Moderation Reporting Block
			$( document ).on( 'change', '#bp_moderation_settings_reporting .bpm_reporting_content_content_label > input', function () {
				if ( $( this ).prop( 'checked' ) ) {
					$( this ).parent().next( 'label' ).removeClass( 'is_disabled' ).find( 'input[type="checkbox"]' ).prop('disabled', false);
				} else {
					$( this ).parent().next( 'label' ).addClass( 'is_disabled' ).find( 'input[type="checkbox"]' ).removeProp( 'checked' ).removeAttr( 'checked' ).prop( 'disabled', 'disabled' );
				}
			} );

			$( document ).on ( 'click', '.bp-suspend-user, .bp-unsuspend-user', function () {
				var DataAction = $( this ).attr('data-action');
				if ( 'suspend'===DataAction ) {
					return confirm ( BP_ADMIN.moderation.suspend_confirm_message );
				} else if ( 'unsuspend'===DataAction ) {
					return confirm ( BP_ADMIN.moderation.unsuspend_confirm_message );
				}
			} );
		}
	);

	/* jshint ignore:start */
	function setCookie(cname, cvalue, exMins) {
		var d = new Date();
		d.setTime( d.getTime() + (exMins * 60 * 1000) );
		var expires     = 'expires=' + d.toUTCString();
		document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/';
	}
	/* jshint ignore:end */

}());
