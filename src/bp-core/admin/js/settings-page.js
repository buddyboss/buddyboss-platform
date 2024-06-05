/* global BP_ADMIN, BP_Uploader, BP_Confirm, bp */

window.bp = window.bp || {};

(function() {
	var $                             = jQuery.noConflict();
	var BbToolsCommunityRepairActions = [];
	var BbToolsForumsRepairActions    = [];

	$(
		function() {


			// Add buttons to Email Template screen.
			var $email_template_screen = $( '.edit-php.post-type-bp-email' ),
				$title_action   = $email_template_screen.find( '.page-title-action:first' );

			$title_action.after( BP_ADMIN.email_template.html );

			$( document ).on(
				'click',
				'.btn-open-missing-email',
				function(e) {
					e.preventDefault();

					if ( $( document ).find( '#bp-hello-backdrop' ).length ) {
					} else {
						var finder = $( document ).find( '.bp-hello-email' );
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

			var displayInstallEMailPopup = $.bbGetParameterByName( 'popup' );
			if ( 'yes' === displayInstallEMailPopup ) {
				$( '.btn-open-missing-email' ).trigger( 'click' );
			}

			/* jshint ignore:start */
			var missingEmailInstallScroll = bbgetUrlParameter( 'scrollto' );
			if ( 'bpmissingemails' === missingEmailInstallScroll ) {
				$('html, body').animate({
					scrollTop: $( '#bp-missing-emails' ).offset().top
				}, 1500);
				$( '.label-bp-missing-emails' ).css('background-color', '#faafaa');
				setTimeout(function () {
					$( '.label-bp-missing-emails' ).css('background-color', 'transparent');
				}, 1500);
			}

			var reInstallEmailScroll = bbgetUrlParameter( 'scrollto' );
			if ( 'bpreinstallemails' === reInstallEmailScroll ) {
				$('html, body').animate({
					scrollTop: $( '#bp-reinstall-emails' ).offset().top
				}, 1500);
				$( '.label-bp-reinstall-emails' ).css('background-color', '#faafaa');
				setTimeout(function () {
					$( '.label-bp-reinstall-emails' ).css('background-color', 'transparent');
				}, 1500);
			}
			/* jshint ignore:end */

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
				$( document ).on(
					'click',
					'.create-background-page',
					function() {
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

			/**
			 * Function for hide/show auto suspend fields on member blocking enable/disabled.
			 */
			$( document ).on(
				'change',
				'#bpm_blocking_member_blocking',
				function () {
					$( 'label[for="bpm_blocking_auto_suspend"]' ).toggleClass( 'is_disabled' );
					$( '#bpm_blocking_auto_suspend' ).prop( 'checked', false );
					$( 'label[for="bpm_blocking_email_notification"]' ).removeClass( 'is_disabled' );
					if( false === $( '#bpm_blocking_member_blocking' ).prop( 'checked' ) && false === $( '#bb_blocking_member_reporting' ).prop( 'checked' ) ) {
						$( '#bpm_blocking_email_notification' ).prop( 'checked', false );
						$( 'label[for="bpm_blocking_email_notification"]' ).addClass( 'is_disabled' );
					}
				}
			);

			/**
			 * Function for hide/show auto suspend fields on member reporting enable/disabled.
			 */
			$( document ).on(
				'change',
				'#bb_blocking_member_reporting',
				function () {
					$( 'label[for="bb_reporting_auto_suspend"]' ).toggleClass('is_disabled');
					$( '#bb_reporting_auto_suspend' ).prop( 'checked', false );
					$( 'label[for="bpm_blocking_email_notification"]' ).removeClass( 'is_disabled' );
					if( false === $( '#bpm_blocking_member_blocking' ).prop( 'checked' ) && false === $( '#bb_blocking_member_reporting' ).prop( 'checked' ) ) {
						$( '#bpm_blocking_email_notification' ).prop( 'checked', false );
						$( 'label[for="bpm_blocking_email_notification"]' ).addClass( 'is_disabled' );
					}
				}
			);

			/**
			 * Checked if member block and reporting both inactive then disabled the email notification for it.
			 */
			if( false === $( '#bpm_blocking_member_blocking' ).prop( 'checked' ) && false === $( '#bb_blocking_member_reporting' ).prop( 'checked' ) ) {
				$( '#bpm_blocking_email_notification' ).prop( 'checked', false );
				$( 'label[for="bpm_blocking_email_notification"]' ).addClass( 'is_disabled' );
			}

			if( $( '#bb_reporting_category_description' ).length ) {
				$('.wp-heading-inline').append( $( '#bb_reporting_category_description' ) );
			}

			$( 'body.taxonomy-bpm_category span:contains("Slug")' ).each( function () {
				$( this ).parent().remove();
			} );

			$( '.taxonomy-bpm_category #the-list' ).on( 'click', 'button.editinline', function ( e ) {
				e.preventDefault();
				var $tr = $( this ).closest( 'tr' );
				var val = $tr.find( 'td.bb_category_show_when_reporting' ).text();
				if ( val !== '' ) {
					$( 'tr.inline-edit-row select[name="bb_category_show_when_reporting"] option' )
						.prop( 'selected', false );
					$( 'tr.inline-edit-row select[name="bb_category_show_when_reporting"] option' )
						.filter( function () {
							return this.text === val;
						} )
						.prop( 'selected', true );
				}
			} );
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

			// Set Moderation selected on Reporting category.
			if ($( 'body.buddypress.taxonomy-bpm_category' ).length) {
				var menus              = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu li' );
				var buddyBossMenu      = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform' );
				var selectorModeration = $( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-moderation"]' );

				$( menus ).removeClass( 'wp-has-current-submenu' );
				$( menus ).removeClass( 'wp-menu-open' );
				$( buddyBossMenu ).addClass( 'wp-has-current-submenu wp-menu-open' );

				$( menuOpen ).removeClass( 'current' );
				$( selectorModeration ).addClass( 'current' );
				$( selectorModeration ).attr( 'aria-current', 'page' );
				$( '#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li' ).find( 'a[href*="bp-moderation"]' ).parent().addClass( 'current' );
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

				$( 'form' ).submit(
					function () {
						var error = false;
						$( 'body .section-bp_document_settings_extensions table tbody tr td table tbody tr.document-extensions td [type="text"]' ).each(
							function() {
								var value = $.trim( $( this ).val() );
								if ( '' === value ) {
									$( this ).addClass( 'error' );
									error = true;
								} else if ( $( this ). hasClass( 'error' ) ) {
									$( this ).removeClass( 'error' );
								}
							}
						);
						if ( error ) {
							return false;
						}
					}
				);
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

					// Show the child sub menu.
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

				// Show confirmation dialog when user enable restrict invite option.
				$( document ).on(
					'click',
					'#bp-enable-group-restrict-invites',
					function () {
						if ( true === this.checked ) {
							if ( confirm( BP_ADMIN.group.restrict_invites_confirm_message ) ) {
								return true;
							} else {
								return false;
							}
						}
					}
				);
			}

			// Hide/show group header element group type.
			if ( $( '.buddyboss_page_bp-settings .section-bp_groups' ).length ) {

				var group_type_header_element = document.getElementById( 'bp-disable-group-type-creation' );

				if (group_type_header_element.checked) {
					$( '.bb-group-headers-elements .bb-group-headers-element-group-type' ).show();
				} else {
					$( '.bb-group-headers-elements .bb-group-headers-element-group-type' ).hide();
				}

				$( document ).on(
					'click',
					'#bp-disable-group-type-creation',
					function () {
						if ( true === this.checked ) {
							$( '.bb-group-headers-elements .bb-group-headers-element-group-type' ).show();
							$( '.bb-group-headers-elements #bb-group-headers-element-group-type' ).prop( 'checked', true );
						} else {
							$( '.bb-group-headers-elements .bb-group-headers-element-group-type' ).hide();
							$( '.bb-group-headers-elements #bb-group-headers-element-group-type' ).prop( 'checked', false );
						}
					}
				);
			}

			// Hide/show group element cover image.
			if ( $( '.buddyboss_page_bp-settings .section-bp_groups' ).length ) {

				var cover_image_element = document.getElementById( 'bp-disable-group-cover-image-uploads' );

				if (cover_image_element.checked) {
					$( '.bb-group-elements .bb-group-element-cover-images' ).show();
				} else {
					$( '.bb-group-elements .bb-group-element-cover-images' ).hide();
				}

				$( document ).on(
					'click',
					'#bp-disable-group-cover-image-uploads',
					function () {
						if ( true === this.checked ) {
							$( '.bb-group-elements .bb-group-element-cover-images' ).show();
							$( '.bb-group-elements #bb-group-directory-layout-element-cover-images' ).prop( 'checked', true );
						} else {
							$( '.bb-group-elements .bb-group-element-cover-images' ).hide();
							$( '.bb-group-elements #bb-group-directory-layout-element-cover-images' ).prop( 'checked', false );
						}
					}
				);
			}

			// Hide/show group element avatars.
			if ( $( '.buddyboss_page_bp-settings .section-bp_groups' ).length ) {

				var avatar_element = document.getElementById( 'bp-disable-group-avatar-uploads' );

				if (avatar_element.checked) {
					$( '.bb-group-elements .bb-group-element-avatars' ).show();
				} else {
					$( '.bb-group-elements .bb-group-element-avatars' ).hide();
				}

				$( document ).on(
					'click',
					'#bp-disable-group-avatar-uploads',
					function () {
						if ( true === this.checked ) {
							$( '.bb-group-elements .bb-group-element-avatars' ).show();
							$( '.bb-group-elements #bb-group-directory-layout-element-avatars' ).prop( 'checked', true );
						} else {
							$( '.bb-group-elements .bb-group-element-avatars' ).hide();
							$( '.bb-group-elements #bb-group-directory-layout-element-avatars' ).prop( 'checked', false );
						}
					}
				);
			}

			// Hide/show group element group type.
			if ( $( '.buddyboss_page_bp-settings .section-bp_groups_types' ).length ) {

				var group_type_element = document.getElementById( 'bp-disable-group-type-creation' );

				if (group_type_element.checked) {
					$( '.bb-group-elements .bb-group-element-group-type' ).show();
				} else {
					$( '.bb-group-elements .bb-group-element-group-type' ).hide();
				}

				$( document ).on(
					'click',
					'#bp-disable-group-type-creation',
					function () {
						if ( true === this.checked ) {
							$( '.bb-group-elements .bb-group-element-group-type' ).show();
							$( '.bb-group-elements #bb-group-directory-layout-element-group-type' ).prop( 'checked', true );
						} else {
							$( '.bb-group-elements .bb-group-element-group-type' ).hide();
							$( '.bb-group-elements #bb-group-directory-layout-element-group-type' ).prop( 'checked', false );
						}
					}
				);
			}

			// Activity settings.
			if ( $( '.buddyboss_page_bp-settings .section-bp_custom_post_type' ).length ) {
				$( '.bp-feed-post-type-checkbox' ).each(
					function() {
						var post_type = $( this ).data( 'post_type' );

						if ( true === this.checked ) {
							$( '.bp-feed-post-type-comment-' + post_type )
							.closest( 'tr' )
							.show();
						}
					}
				);

				$( '.buddyboss_page_bp-settings .section-bp_custom_post_type' ).on(
					'click',
					'.bp-feed-post-type-checkbox',
					function () {
						var post_type    = $( this ).data( 'post_type' ),
							commentField = $( '.bp-feed-post-type-comment-' + post_type );

						if ( true === this.checked ) {
							commentField
								.closest( 'tr' )
								.show();
						} else {
							commentField
								.prop( 'checked', false )
								.closest( 'tr' )
								.hide();
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

			if ( $( 'body .section-bp_video_settings_extensions' ).length ) {

				$( document ).find( '.nav-settings-subsubsub .subsubsub li.bp-media a' ).addClass( 'current' );

				$( '.video-extensions-listing #bp_select_extensions' ).click(
					function () {
						var table = $( 'body .section-bp_video_settings_extensions table tbody tr td table tbody tr' );
						$( 'td input:checkbox', table ).prop( 'checked', this.checked );
					}
				);

			}

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
			if ( $( '.display-options:not(.button)' ).length ) {

					var selectorAll    = $( '.display-options:not(.button)' );
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
			var groupSelectorType = $( '.group-layout-options:not(.group-header-style)' );
			if ( groupSelectorType.length ) {

				var groupSelectorOptions = $( 'select[name=bp-group-layout-format]' );
				var groupView            = groupSelectorOptions.val();

				$( groupSelectorType ).each(
					function() {
						$( this ).hide();
					}
				);

				if ( 'list_grid' === groupView ) {
					$( '.group-gride-style' ).show();
					$( '.group-default-layout' ).show();
				} else if ( 'grid' === groupView ) {
					$( '.group-gride-style' ).show();
					$( '.group-default-layout' ).hide();
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
							$( '.group-gride-style' ).show();
							$( '.group-default-layout' ).show();
						} else if ( 'grid' === groupView ) {
							$( '.group-gride-style' ).show();
							$( '.group-default-layout' ).hide();
						} else {
							$( '.group-default-layout' ).hide();
						}

					}
				);

				/* jshint ignore:start */
				var getGroupCookies      = function(){
					var pairs   = document.cookie.split( ';' );
					var cookies = {};
					for ( var i = 0; i < pairs.length; i++ ) {
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
												$( 'body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"] .loader-repair-tools' ).remove();
												$( 'body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"] code' ).remove();
												$( 'body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"]' ).append( '<code>' + response.data.records + '</code>' );
												bp_admin_repair_tools_wrapper_function( response.data.offset, currentAction );
											} else {
												$( 'body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"] .loader-repair-tools' ).remove();
												$( '.section-repair_community .settings fieldset' ).append( '<div class="updated"><p>' + response.data.message + '</p></div>' );
												if ( typeof response.data.records !== 'undefined' && '' !== response.data.records ) {
													$( 'body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"] code' ).remove();
													$( 'body .section-repair_community .settings fieldset .checkbox label[for="' + BbToolsCommunityRepairActions[currentAction] + '"]' ).append( '<code>' + response.data.records + '</code>' );
												}
												currentAction = currentAction + 1;
												bp_admin_repair_tools_wrapper_function( response.data.offset, currentAction );
											}
											if (BbToolsCommunityRepairActions.length === currentAction) {
												$( 'body .section-repair_community .settings fieldset .submit a' ).removeClass( 'disable-btn' );
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
								bp_admin_repair_tools_wrapper_function( 0, 0 );
							}
						}
					);
			}

			if ( $( '#bb-giphy-connect' ).length ) {

				$( document ).on(
					'click',
					'#bb-giphy-connect',
					function( e ) {
						e.preventDefault();
						if ( $( '#bb-giphy-connect' ).data( 'connected' ) ) {
							$( '#bb-giphy-connect' ).data( 'connected', false );
							$( '#bb-giphy-connect' ).addClass( 'button-primary' );
							$( '#bp_media_gif_api_key' ).attr( 'readonly', false );
							$( '#bp_media_gif_api_key' ).val( '' );
							$( '#bp_media_profiles_gif_support' ).attr( 'disabled', true ).attr( 'checked', false );
							$( '#bp_media_groups_gif_support' ).attr( 'disabled', true ).attr( 'checked', false );
							$( '#bp_media_messages_gif_support' ).attr( 'disabled', true ).attr( 'checked', false );
							$( '#bp_media_forums_gif_support' ).attr( 'disabled', true ).attr( 'checked', false );
							$( '#bb-giphy-connect' ).val( $( '#bb-giphy-connect' ).data( 'connect-text' ) );
							return false;
						}
						$( '#bb-giphy-connect' ).addClass( 'disable-btn' );
						$( '#bp_media_settings_gifs .bp-new-notice-panel-notice' ).addClass( 'hidden' );
						$.ajax(
							{
								'url' : BP_ADMIN.ajax_url,
								'method' : 'POST',
								'data' : {
									'action' : 'bb_admin_check_valid_giphy_key',
									'key'    : $( '#bp_media_gif_api_key' ).val(),
									'nonce'  : $( '#bb-giphy-connect' ).data( 'nonce' )
								},
								'success' : function( response ) {
									if ( response.data.code && 200 === response.data.code ) {
										$( '#bb-giphy-connect' ).data( 'connected', true );
										$( '#bb-giphy-connect' ).removeClass( 'button-primary' );
										$( '#bp_media_gif_api_key' ).attr( 'readonly', true );
										$( '#bp_media_profiles_gif_support' ).attr( 'disabled', false );
										$( '#bp_media_groups_gif_support' ).attr( 'disabled', false );
										$( '#bp_media_messages_gif_support' ).attr( 'disabled', false );
										$( '#bp_media_forums_gif_support' ).attr( 'disabled', false );
										$( '#bb-giphy-connect' ).val( $( '#bb-giphy-connect' ).data( 'disconnect-text' ) );
									} else {
										$( '#bp_media_settings_gifs .bp-new-notice-panel-notice' ).removeClass( 'hidden' );
										$( '#bp_media_settings_gifs .bp-new-notice-panel-notice span#giphy_response_code' ).html( response.data.code );
										$( '#bp_media_settings_gifs .bp-new-notice-panel-notice span#giphy_response_message' ).html( response.data.message );
									}
								},
								'error' : function() {
									$( '#bb-giphy-connect' ).removeClass( 'disable-btn' );
								}
							}
						);
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
									'site_id': $( 'body .section-repair_forums #bbp-network-site' ).val(),
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
						var $bbp_network_site      = $( 'body .section-repair_forums #bbp-network-site' );

						if ($bbp_network_site.length && '0' === $bbp_network_site.val() ) {
							alert( BP_ADMIN.tools.repair_forums.validate_site_id_message );
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

			// Profile Avatar Settings Show/Hide.
			var profileAvatarType                   = $( '#bp-profile-avatar-type' ),
				defaultProfileAvatarType            = $( 'input[type=radio][name=bp-default-profile-avatar-type]' ),
				defaultProfileAvatarTypeVal         = $( 'input[type=radio][name=bp-default-profile-avatar-type]:checked' ).val(),
				defaultProfileAvatarTypeContainer   = $( '.default-profile-avatar-type' ),
				defaultProfileAvatarCustomContainer = $( '.default-profile-avatar-custom' ),
				enableProfileGravatarContainer      = $( '.enable-profile-gravatar-field' ),
				profileAvatarfeedbackContainer      = $( '.bb-wordpress-profile-gravatar-warning' ),
				previewContainer                    = $( '.preview-avatar-cover-image' ),
				webPreviewContainer                 = previewContainer.find( '.web-preview-wrap' ),
				appPreviewContainer                 = previewContainer.find( '.app-preview-wrap' );

			// Show/Hide Profile Avatars.
			if ( profileAvatarType.length ) {
				$( profileAvatarType ).change(
					function () {
						defaultProfileAvatarTypeContainer.addClass( 'bp-hide' );
						defaultProfileAvatarCustomContainer.addClass( 'bp-hide' );
						profileAvatarfeedbackContainer.addClass( 'bp-hide' );
						enableProfileGravatarContainer.addClass( 'bp-hide' );

						var profileAvatarURL          = '',
							webAvatarPreviewContainer = webPreviewContainer.find( '.preview-item-avatar img' );

						previewContainer.find( '.preview_avatar_cover' ).removeClass( 'has-avatar' );

						if ( 'BuddyBoss' === $( this ).val() ) {

							enableProfileGravatarContainer.removeClass( 'bp-hide' );
							defaultProfileAvatarTypeContainer.removeClass( 'bp-hide' );

							defaultProfileAvatarTypeVal = $( 'input[type=radio][name=bp-default-profile-avatar-type]:checked' ).val();
							profileAvatarURL            = $( '.' + defaultProfileAvatarTypeVal + '-profile-avatar' ).prop( 'src' );

							if ( 'custom' === defaultProfileAvatarTypeVal ) {
								defaultProfileAvatarCustomContainer.removeClass( 'bp-hide' );

								profileAvatarURL = $( '#bp-default-user-custom-avatar' ).val();
								if ( typeof profileAvatarURL.length !== 'undefined' && 0 == profileAvatarURL.length ) {
									profileAvatarURL = webAvatarPreviewContainer.attr( 'data-blank-avatar' );
								}
							}

						} else if ( 'WordPress' === $( this ).val() ) {

							if ( ! BP_ADMIN.avatar_settings.wordpress_show_avatar ) {
								profileAvatarfeedbackContainer.removeClass( 'bp-hide' );
							}
							profileAvatarURL = webAvatarPreviewContainer.attr( 'data-wordpress-avatar' );
						}

						if ( typeof profileAvatarURL.length !== 'undefined' && 0 < profileAvatarURL.length ) {
							previewContainer.find( '.preview_avatar_cover' ).addClass( 'has-avatar' );
						}
						webAvatarPreviewContainer.prop( 'src', profileAvatarURL );
						appPreviewContainer.find( '.preview-item-avatar img' ).prop( 'src', profileAvatarURL );
					}
				);
			}

			// Show/Hide Upload Custom Avatar.
			if ( defaultProfileAvatarType.length ) {

				$( defaultProfileAvatarType ).change(
					function () {

						previewContainer.find( '.preview_avatar_cover' ).removeClass( 'has-avatar' );

						var profileAvatarURL          = '',
							webAvatarPreviewContainer = webPreviewContainer.find( '.preview-item-avatar img' );

						if ( 'BuddyBoss' === profileAvatarType.val() && 'custom' === this.value ) {
							defaultProfileAvatarCustomContainer.removeClass( 'bp-hide' );

							profileAvatarURL = $( '#bp-default-user-custom-avatar' ).val();
							if ( typeof profileAvatarURL.length !== 'undefined' && 0 == profileAvatarURL.length ) {
								profileAvatarURL = webAvatarPreviewContainer.attr( 'data-blank-avatar' );
							}
						} else {
							profileAvatarURL = $( '.' + this.value + '-profile-avatar' ).prop( 'src' );
							defaultProfileAvatarCustomContainer.addClass( 'bp-hide' );
						}

						var is_available_image_library = $( 'input[name="bb-is-available-image-library"]' ).val();
						if ( 'display-name' === this.value ) {
							$( '.bp-default-profile-avatar-display-name-description' ).removeClass( 'bp-hide' );
							if ( 0 === is_available_image_library.length ) {
								$( '.bp-default-profile-avatar-display-name-notice' ).removeClass( 'bp-hide' );
							}
						} else {
							$( '.bp-default-profile-avatar-display-name-description' ).addClass( 'bp-hide' );
							$( '.bp-default-profile-avatar-display-name-notice' ).addClass( 'bp-hide' );
						}

						if ( typeof profileAvatarURL.length !== 'undefined' && 0 < profileAvatarURL.length ) {
							previewContainer.find( '.preview_avatar_cover' ).addClass( 'has-avatar' );
						}
						webAvatarPreviewContainer.prop( 'src', profileAvatarURL );
						appPreviewContainer.find( '.preview-item-avatar img' ).prop( 'src', profileAvatarURL );
					}
				);
			}

			// Profile Cover Settings Show/Hide.
			var allowCoverUpload    = $( '#bp-disable-cover-image-uploads' ),
				profileCoverType    = $( 'input[type=radio][name=bp-default-profile-cover-type]' ),
				profileCoverTypeVal = $( 'input[type=radio][name=bp-default-profile-cover-type]:checked' ).val();

			if ( allowCoverUpload.length ) {

				$( allowCoverUpload ).change(
					function () {

						previewContainer.find( '.preview_avatar_cover' ).removeClass( 'has-cover' );

						var webCoverPreviewContainer = webPreviewContainer.find( '.preview-item-cover img' ),
							appCoverPreviewContainer = appPreviewContainer.find( '.preview-item-cover img' );

						if ( $( this ).prop( 'checked' ) ) {
							$( '.profile-cover-options' ).removeClass( 'bp-hide' );

							profileCoverTypeVal = $( 'input[type=radio][name=bp-default-profile-cover-type]:checked' ).val();
							previewContainer.find( '.preview_avatar_cover' ).addClass( 'has-cover' );

							if ( 'custom' !== profileCoverTypeVal ) {
								$( '.default-profile-cover-custom' ).addClass( 'bp-hide' );

								if ( 'buddyboss' === profileCoverTypeVal ) {
									webCoverPreviewContainer.prop( 'src', webCoverPreviewContainer.attr( 'data-buddyboss-cover' ) );
									appCoverPreviewContainer.prop( 'src', appCoverPreviewContainer.attr( 'data-buddyboss-cover' ) );
								}
							}
						} else {
							$( '.profile-cover-options' ).addClass( 'bp-hide' );
						}
					}
				);
			}

			// Upload Custom Cover Settings Show/Hide.
			if ( profileCoverType.length ) {

				$( profileCoverType ).change(
					function () {

						previewContainer.find( '.preview_avatar_cover' ).removeClass( 'has-cover' ).addClass( 'has-cover' );

						var webCoverPreviewContainer = webPreviewContainer.find( '.preview-item-cover img' ),
							appCoverPreviewContainer = appPreviewContainer.find( '.preview-item-cover img' );

						webCoverPreviewContainer.prop( 'src', '' );
						appCoverPreviewContainer.prop( 'src', '' );

						if ( 'custom' === this.value ) {
							$( '.default-profile-cover-custom' ).removeClass( 'bp-hide' );

							webCoverPreviewContainer.prop( 'src', $( '#bp-default-custom-user-cover' ).val() );
							appCoverPreviewContainer.prop( 'src', $( '#bp-default-custom-user-cover' ).val() );

						} else {
							$( '.default-profile-cover-custom' ).addClass( 'bp-hide' );

							profileCoverTypeVal = $( 'input[type=radio][name=bp-default-profile-cover-type]:checked' ).val();

							if ( 'buddyboss' === profileCoverTypeVal ) {
								webCoverPreviewContainer.prop( 'src', webCoverPreviewContainer.attr( 'data-buddyboss-cover' ) );
								appCoverPreviewContainer.prop( 'src', appCoverPreviewContainer.attr( 'data-buddyboss-cover' ) );
							}

						}
					}
				);
			}

			function profileGroupFileFeedback( container, feedback, type ) {
				container.find( '.bp-feedback' ).removeClass( 'success error' ).addClass( type ).find( 'p' ).html( feedback );
				container.show();
			}

			$( '.custom-profile-group-avatar' ).on(
				'click',
				'a.bp-delete-custom-avatar',
				function(e) {
					e.preventDefault();

					if ( confirm( BP_Confirm.are_you_sure ) ) {
						var $this                   = $( this ),
							avatarContainer         = $this.parents( 'tr' ),
							avatarFeedbackContainer = avatarContainer.find( '.bb-custom-profile-group-avatar-feedback' ),
							avatarItemID            = BP_Uploader.settings.defaults.multipart_params.bp_params.item_id,
							avatarObject            = BP_Uploader.settings.defaults.multipart_params.bp_params.object;

						$this.html( $this.data( 'removing' ) );
						avatarFeedbackContainer.hide();
						avatarFeedbackContainer.find( '.bp-feedback' ).removeClass( 'success error' );

						// Remove the avatar !
						bp.ajax.post(
							'bp_avatar_delete',
							{
								json:      true,
								item_id:   avatarItemID,
								item_type: BP_Uploader.settings.defaults.multipart_params.bp_params.item_type,
								object:    avatarObject,
								nonce:     BP_Uploader.settings.defaults.multipart_params.bp_params.nonces.remove
							}
						).done(
							function( response ) {
								$this.html( $this.data( 'remove' ) );

								// Update each avatars of the page.
								$( '.default-profile-avatar-custom .' + avatarObject + '-' + response.item_id + '-avatar' ).each(
									function() {
										$( this ).prop( 'src', response.avatar );
									}
								);

								// Hide 'Remove' button when avatar deleted.
								if ( $( '.custom-profile-group-avatar a.bb-img-remove-button' ).length ) {
									$( '.custom-profile-group-avatar a.bb-img-remove-button' ).addClass( 'bp-hide' );
								}

								// Hide image preview when avatar deleted.
								$( '.custom-profile-group-avatar .bb-upload-container img' ).prop( 'src', response.avatar ).addClass( 'bp-hide' );

								// Update each avatars fields of the page.
								$( '.custom-profile-group-avatar .bb-upload-container .bb-default-custom-avatar-field' ).val( '' );

								// Remove image from the live preview.
								$( '.preview_avatar_cover .preview-item-avatar img' ).prop( 'src', '' );
							}
						).fail(
							function( response ) {
								var feedback     = BP_Uploader.strings.default_error,
									feedbackType = 'error';

								$this.html( $this.data( 'remove' ) );

								if ( ! _.isUndefined( response ) ) {
									feedback = BP_Uploader.strings.feedback_messages[ response.feedback_code ];
								}

								// Show feedback.
								profileGroupFileFeedback( avatarFeedbackContainer, feedback, feedbackType );
							}
						);
					}
				}
			);

			$( document ).on(
				'change',
				'#default-profile-cover-file, #default-group-cover-file',
				function(e) {
					e.preventDefault();
					var fileData                = $( this )[0].files[0],
						coverContainer          = $( '.custom-profile-group-cover' ),
						coverUploadBtnContainer = coverContainer.find( '.bb-img-upload-button' ),
						feedbackContainer       = coverContainer.find( '.bb-custom-profile-group-cover-feedback' ),
						imageContainer          = coverContainer.find( '.bb-upload-preview' ),
						imageFieldContainer     = coverContainer.find( '#bp-default-custom-' + BP_ADMIN.profile_group_cover.upload.object + '-cover' ),
						deleteBtnContainer      = coverContainer.find( 'a.bb-img-remove-button' );

					coverUploadBtnContainer.html( coverUploadBtnContainer.data( 'uploading' ) );
					feedbackContainer.hide();
					feedbackContainer.find( '.bp-feedback' ).removeClass( 'success error' );

					if ( 'undefined' === typeof fileData ) {
						profileGroupFileFeedback( feedbackContainer, BP_ADMIN.profile_group_cover.select_file, 'error' );
						return false;
					}

					var form_data = new FormData();
					form_data.append( 'file', fileData );
					form_data.append( 'name', fileData.name );
					form_data.append( '_wpnonce', BP_ADMIN.profile_group_cover.upload.nonce );
					form_data.append( 'action', BP_ADMIN.profile_group_cover.upload.action );
					form_data.append( 'bp_params[object]', BP_ADMIN.profile_group_cover.upload.object );
					form_data.append( 'bp_params[item_id]', BP_ADMIN.profile_group_cover.upload.item_id );
					form_data.append( 'bp_params[item_type]', BP_ADMIN.profile_group_cover.upload.item_type );
					form_data.append( 'bp_params[has_cover_image]', BP_ADMIN.profile_group_cover.upload.has_cover_image );
					form_data.append( 'bp_params[nonces][remove]', BP_ADMIN.profile_group_cover.remove.nonce );

					$.ajax(
						{
							url:         BP_ADMIN.ajax_url, // point to server-side PHP script.
							cache:       false,
							contentType: false,
							processData: false,
							data:        form_data,
							type:        'post',
							success: function( response ){
								coverUploadBtnContainer.html( coverUploadBtnContainer.data( 'upload' ) );

								var feedback,
									feedbackType = 'error';

								if ( 'undefined' === typeof response ) {
									feedback = BP_ADMIN.profile_group_cover.file_upload_error;

									// Show feedback.
									profileGroupFileFeedback( feedbackContainer, feedback, feedbackType );
								} else if ( ! response.success ) {

									if ( 'undefined' === typeof response.data.feedback_code ) {
										feedback = response.data.message;
									} else {
										feedback = BP_ADMIN.profile_group_cover.feedback_messages[response.data.feedback_code];
									}

									// Show feedback.
									profileGroupFileFeedback( feedbackContainer, feedback, feedbackType );
								} else {

									imageContainer.prop( 'src', response.data.url ).removeClass( 'bp-hide' );
									imageFieldContainer.val( response.data.url );
									deleteBtnContainer.removeClass( 'bp-hide' );
									feedbackType = 'success';
									feedback     = BP_ADMIN.profile_group_cover.feedback_messages[response.data.feedback_code];

									// Update image for live preview.
									$( '.preview-avatar-cover-image .preview-item-cover img' ).prop( 'src', response.data.url );
								}

								// Reset the file field.
								$( '#default-profile-cover-file, #default-group-cover-file' ).val( '' );
							}
						}
					);
				}
			);

			$( '.custom-profile-group-cover' ).on(
				'click',
				'a.bb-img-remove-button',
				function(e) {
					e.preventDefault();

					if ( confirm( BP_Confirm.are_you_sure ) ) {
						var $this                   = $( this ),
							coverContainer          = $( '.custom-profile-group-cover' ),
							feedbackContainer       = coverContainer.find( '.bb-custom-profile-group-cover-feedback' ),
							imageContainer          = coverContainer.find( '.bb-upload-preview' ),
							imageFieldContainer     = coverContainer.find( '#bp-default-custom-' + BP_ADMIN.profile_group_cover.upload.object + '-cover' ),
							defaultImageplaceholder = imageContainer.data( 'default' );

						$this.html( $this.data( 'removing' ) );
						feedbackContainer.hide();
						feedbackContainer.find( '.bp-feedback' ).removeClass( 'success error' );

						$.ajax(
							{
								url: BP_ADMIN.ajax_url, // point to server-side PHP script.
								cache: false,
								data: {
									json:      true,
									action:    BP_ADMIN.profile_group_cover.remove.action,
									item_id:   BP_ADMIN.profile_group_cover.upload.item_id,
									item_type: BP_ADMIN.profile_group_cover.upload.item_type,
									object:    BP_ADMIN.profile_group_cover.upload.object,
									nonce:     BP_ADMIN.profile_group_cover.remove.nonce
								},
								type: 'post',
								success: function( response ){
									$this.html( $this.data( 'remove' ) );

									var feedback,
										feedbackType = 'error';

									if ( 'undefined' === typeof response ) {
										feedback = BP_ADMIN.profile_group_cover.file_upload_error;

										// Show feedback.
										profileGroupFileFeedback( feedbackContainer, feedback, feedbackType );
									} else if ( ! response.success ) {

										if ( 'undefined' === typeof response.data.feedback_code ) {
											feedback = response.data.message;
										} else {
											feedback = BP_ADMIN.profile_group_cover.feedback_messages[response.data.feedback_code];
										}

										// Show feedback.
										profileGroupFileFeedback( feedbackContainer, feedback, feedbackType );
									} else {

										imageContainer.prop( 'src', defaultImageplaceholder ).addClass( 'bp-hide' );
										imageFieldContainer.val( '' );
										$this.addClass( 'bp-hide' );
										feedbackType = 'success';
										feedback     = BP_ADMIN.profile_group_cover.feedback_messages[response.data.feedback_code];

										// Update image for live preview.
										$( '.preview-avatar-cover-image .preview-item-cover img' ).prop( 'src', '' );
									}
								}
							}
						);
					}
				}
			);

			// Group Avatar Settings Show/Hide.
			var allowGroupAvatarUpload = $( '#bp-disable-group-avatar-uploads' ),
				groupAvatarType        = $( 'input[type=radio][name=bp-default-group-avatar-type]' ),
				groupAvatarTypeVal     = $( 'input[type=radio][name=bp-default-group-avatar-type]:checked' ).val();

			if ( allowGroupAvatarUpload.length ) {

				$( allowGroupAvatarUpload ).change(
					function () {

						var groupAvatarURL            = '',
							webAvatarPreviewContainer = webPreviewContainer.find( '.preview-item-avatar img' );

						previewContainer.find( '.preview_avatar_cover' ).removeClass( 'has-avatar' );
						webAvatarPreviewContainer.prop( 'src', '' );
						appPreviewContainer.find( '.preview-item-avatar img' ).prop( 'src', '' );

						if ( $( this ).prop( 'checked' ) ) {
							$( '.group-avatar-options' ).removeClass( 'bp-hide' );

							groupAvatarTypeVal = $( 'input[type=radio][name=bp-default-group-avatar-type]:checked' ).val();

							groupAvatarURL = $( '#bp-default-group-custom-avatar' ).val();
							if ( 0 == groupAvatarURL.length ) {
								groupAvatarURL = webAvatarPreviewContainer.attr( 'data-blank-avatar' );
							}

							if ( 'custom' !== groupAvatarTypeVal ) {
								$( '.default-group-avatar-custom' ).addClass( 'bp-hide' );
								groupAvatarURL = $( '.' + groupAvatarTypeVal + '-group-avatar' ).prop( 'src' );
							}

							if ( typeof groupAvatarURL.length !== 'undefined' && 0 < groupAvatarURL.length ) {
								previewContainer.find( '.preview_avatar_cover' ).addClass( 'has-avatar' );
							}

							webAvatarPreviewContainer.prop( 'src', groupAvatarURL );
							appPreviewContainer.find( '.preview-item-avatar img' ).prop( 'src', groupAvatarURL );

						} else {
							$( '.group-avatar-options' ).addClass( 'bp-hide' );
						}
					}
				);
			}

			// Upload Custom Group Settings Show/Hide.
			if ( groupAvatarType.length ) {

				$( groupAvatarType ).change(
					function () {

						var groupAvatarURL            = '',
							webAvatarPreviewContainer = webPreviewContainer.find( '.preview-item-avatar img' );

						previewContainer.find( '.preview_avatar_cover' ).removeClass( 'has-avatar' );
						webAvatarPreviewContainer.prop( 'src', '' );
						appPreviewContainer.find( '.preview-item-avatar img' ).prop( 'src', '' );

						if ( 'custom' === this.value ) {
							$( '.default-group-avatar-custom' ).removeClass( 'bp-hide' );

							groupAvatarURL = $( '#bp-default-group-custom-avatar' ).val();
							if ( 0 == groupAvatarURL.length ) {
								groupAvatarURL = webAvatarPreviewContainer.attr( 'data-blank-avatar' );
							}

						} else {
							$( '.default-group-avatar-custom' ).addClass( 'bp-hide' );
							groupAvatarURL = $( '.' + this.value + '-group-avatar' ).prop( 'src' );
						}

						var group_is_available_image_library = $( 'input[name="bb-is-available-image-library"]' ).val();
						if ( 'group-name' === this.value && 0 === group_is_available_image_library.length ) {
							$( '.bp-default-group-avatar-group-name-notice' ).removeClass( 'bp-hide' );
						} else {
							$( '.bp-default-group-avatar-group-name-notice' ).addClass( 'bp-hide' );
						}

						if ( typeof groupAvatarURL.length !== 'undefined' && 0 < groupAvatarURL.length ) {
							previewContainer.find( '.preview_avatar_cover' ).addClass( 'has-avatar' );
						}

						webAvatarPreviewContainer.prop( 'src', groupAvatarURL );
						appPreviewContainer.find( '.preview-item-avatar img' ).prop( 'src', groupAvatarURL );
					}
				);
			}

			// Group Cover Settings Show/Hide.
			var allowGroupCoverUpload = $( '#bp-disable-group-cover-image-uploads' ),
				groupCoverType        = $( 'input[type=radio][name=bp-default-group-cover-type]' ),
				groupCoverTypeVal     = $( 'input[type=radio][name=bp-default-group-cover-type]:checked' ).val();

			if ( allowGroupCoverUpload.length ) {

				$( allowGroupCoverUpload ).change(
					function () {

						previewContainer.find( '.preview_avatar_cover' ).removeClass( 'has-cover' );

						var webCoverPreviewContainer = webPreviewContainer.find( '.preview-item-cover img' ),
							appCoverPreviewContainer = appPreviewContainer.find( '.preview-item-cover img' );

						if ( $( this ).prop( 'checked' ) ) {
							$( '.group-cover-options' ).removeClass( 'bp-hide' );

							groupCoverTypeVal = $( 'input[type=radio][name=bp-default-group-cover-type]:checked' ).val();
							previewContainer.find( '.preview_avatar_cover' ).addClass( 'has-cover' );

							if ( 'custom' !== groupCoverTypeVal ) {
								$( '.default-group-cover-custom' ).addClass( 'bp-hide' );

								if ( 'buddyboss' === profileCoverTypeVal ) {
									webCoverPreviewContainer.prop( 'src', webCoverPreviewContainer.attr( 'data-buddyboss-cover' ) );
									appCoverPreviewContainer.prop( 'src', appCoverPreviewContainer.attr( 'data-buddyboss-cover' ) );
								}
							}
						} else {
							$( '.group-cover-options' ).addClass( 'bp-hide' );
						}
					}
				);
			}

			// Upload Custom Group Cover Settings Show/Hide.
			if ( groupCoverType.length ) {

				$( groupCoverType ).change(
					function () {

						previewContainer.find( '.preview_avatar_cover' ).removeClass( 'has-cover' ).addClass( 'has-cover' );

						var webCoverPreviewContainer = webPreviewContainer.find( '.preview-item-cover img' ),
							appCoverPreviewContainer = appPreviewContainer.find( '.preview-item-cover img' );

						webCoverPreviewContainer.prop( 'src', '' );
						appCoverPreviewContainer.prop( 'src', '' );

						if ( 'custom' === this.value ) {
							$( '.default-group-cover-custom' ).removeClass( 'bp-hide' );

							webCoverPreviewContainer.prop( 'src', $( '#bp-default-custom-group-cover' ).val() );
							appCoverPreviewContainer.prop( 'src', $( '#bp-default-custom-group-cover' ).val() );
						} else {
							$( '.default-group-cover-custom' ).addClass( 'bp-hide' );

							profileCoverTypeVal = $( 'input[type=radio][name=bp-default-group-cover-type]:checked' ).val();

							if ( 'buddyboss' === profileCoverTypeVal ) {
								webCoverPreviewContainer.prop( 'src', webCoverPreviewContainer.attr( 'data-buddyboss-cover' ) );
								appCoverPreviewContainer.prop( 'src', appCoverPreviewContainer.attr( 'data-buddyboss-cover' ) );
							}
						}
					}
				);
			}

			// Confirmed box appears when change profile sizes options.
			var is_confirmed_show = false;

			if ( $( '#bp_member_avatar_settings .image-width-height' ).length ) {
				var bpCoverProfileWidth  = $( 'select#bb-cover-profile-width' ).find( 'option:selected' ).val();
				var bpCoverProfileHeight = $( 'select#bb-cover-profile-height' ).find( 'option:selected' ).val();
				$( '#bp_member_avatar_settings' ).on(
					'change',
					'select#bb-cover-profile-width, select#bb-cover-profile-height',
					function(e) {
						e.preventDefault();

						is_confirmed_show = true;
						if ( 'bb-cover-profile-width' === $( this ).attr( 'id' ) && bpCoverProfileWidth === $( this ).val() ) {
							is_confirmed_show = false;
						} else if ( 'bb-cover-profile-height' === $( this ).attr( 'id' ) && bpCoverProfileHeight === $( this ).val() ) {
							is_confirmed_show = false;
						}

						// Add class to preview section for browser only.
						if ( 'bb-cover-profile-height' === $( this ).attr( 'id' ) ) {
							if ( 'small' === $( this ).val() ) {
								$( '.preview_avatar_cover .web-preview-wrap .preview-item-cover' ).removeClass( 'large-image' );
							} else {
								$( '.preview_avatar_cover .web-preview-wrap .preview-item-cover' ).addClass( 'large-image' );
							}
						}
					}
				);
			}

			if ( $( '#bp_groups_avatar_settings .image-width-height' ).length ) {
				var bpCoverGroupWidth  = $( 'select#bb-cover-group-width' ).find( 'option:selected' ).val();
				var bpCoverGroupHeight = $( 'select#bb-cover-group-height' ).find( 'option:selected' ).val();
				$( '#bp_groups_avatar_settings' ).on(
					'change',
					'select#bb-cover-group-width, select#bb-cover-group-height',
					function(e) {
						e.preventDefault();

						is_confirmed_show = true;
						if ( 'bb-cover-group-width' === $( this ).attr( 'id' ) && bpCoverGroupWidth === $( this ).val() ) {
							is_confirmed_show = false;
						} else if ( 'bb-cover-group-height' === $( this ).attr( 'id' ) && bpCoverGroupHeight === $( this ).val() ) {
							is_confirmed_show = false;
						}

						// Add class to preview section for browser only.
						if ( 'bb-cover-group-height' === $( this ).attr( 'id' ) ) {
							if ( 'small' === $( this ).val() ) {
								$( '.preview_avatar_cover .web-preview-wrap .preview-item-cover' ).removeClass( 'large-image' );
							} else {
								$( '.preview_avatar_cover .web-preview-wrap .preview-item-cover' ).addClass( 'large-image' );
							}
						}
					}
				);
			}

			$( 'body.buddyboss_page_bp-settings' ).on(
				'click',
				'input[name="submit"]',
				function(e) {

					if ( is_confirmed_show && ( $( '#bp_member_avatar_settings' ).length || $( '#bp_groups_avatar_settings' ).length ) ) {

						var coverWarningMessage = BP_ADMIN.cover_size_alert.profile;
						if ( $( '#bp_groups_avatar_settings' ).length ) {
							coverWarningMessage = BP_ADMIN.cover_size_alert.group;
						}

						if (  confirm( coverWarningMessage ) ) {
							return true;
						} else {
							e.preventDefault();
							return false;
						}
					}

					return true;
				}
			);

			// Show/hide web/app preview.
			$( '.preview-switcher .button' ).on(
				'click',
				function( event ) {
					event.preventDefault();

					var tab = $( this ).attr( 'href' );
					$( this ).closest( '.preview-switcher-main' ).find( '.preview-block.active' ).removeClass( 'active' );
					$( tab ).addClass( 'active' );
					$( this ).addClass( 'button-primary' ).siblings().removeClass( 'button-primary' );
				}
			);

			// Change preview avatar on change member header layout.
			$( '.profile-header-style input[type="radio"], .group-header-style input[type="radio"]' ).on(
				'change',
				function() {
					$( '.web-preview-wrap .preview-item-avatar' ).removeClass( 'left-image' ).removeClass( 'centered-image' ).addClass( $( this ).val() + '-image' );
				}
			);

			// Show/hide Profile action for member directories section.
			$( '#bp_profile_list_settings' ).on(
				'change',
				'.member-directory-profile-actions input[type="checkbox"]',
				function () {

					var member_profile_actions      = []; // Create an array for primary action field to hide/show.
					var selected_member_actions_arr = []; // Create an array for primary action option.
					var current_primary_action      = ''; // Get selected primary action button.

					current_primary_action = BP_ADMIN.member_directories.profile_action_btn;
					$( '.member-directory-profile-actions input[type="checkbox"]:checked' ).each(
						function ( i, e ) {
							member_profile_actions.push( e.value );
							selected_member_actions_arr.push( BP_ADMIN.member_directories.profile_actions.filter( function (actions) { return actions.element_name === e.value; } ) );
						}
					);

					// Remove the options when checked/unchecked profile actions.
					$( '.member-directory-profile-primary-action select' ).find( 'option:not(:first)' ).remove();
					$.each(
						selected_member_actions_arr,
						function(key, value) {

							// Add the options when checked/unchecked profile actions.
							if ( typeof value[0] !== 'undefined' && typeof value[0].element_name !== 'undefined' && typeof value[0].element_label !== 'undefined' ) {

								var primary_action_btn_select_attr = false;
								if ( current_primary_action === value[0].element_name ) {
									primary_action_btn_select_attr = true;
								}

								$( '.member-directory-profile-primary-action select' )
									.append(
										$( '<option></option>' )
											.attr( 'value', value[0].element_name )
											.text( value[0].element_label )
											.attr( 'selected', primary_action_btn_select_attr )
									);
							}
						}
					);

					if ( 0 === member_profile_actions.length ) {
						// Hide the primary action field if no profile actions selected.
						$( '.member-directory-profile-primary-action' ).addClass( 'bp-hide' );
						// Default none selected if no profile actions selected.
						$( '.member-directory-profile-primary-action select' ).find( 'option:eq(0)' ).prop( 'selected', true );
					} else {
						// Show the primary action field if profile actions selected.
						$( '.member-directory-profile-primary-action' ).removeClass( 'bp-hide' );
					}
				}
			);

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

			$( document ).on(
				'click',
				'table.extension-listing #btn-add-video-extensions',
				function() {
					var parent     = $( this ).closest( 'table.extension-listing' );
					var newOption  = $( this ).closest( 'table.extension-listing' ).find( 'tbody tr.custom-extension-data' ).html();
					var totalCount = 1;
					parent.find( 'tbody' ).append( ' <tr class="custom-extension extra-extension video-extensions"> ' + newOption + ' </tr> ' );

					makeIconSelect();

					parent.find( 'tbody tr.extra-extension' ).each(
						function() {
							$( this ).find( 'input.extension-check' ).attr( 'name', 'bp_video_extensions_support[' + totalCount + '][is_active]' );
							$( this ).find( 'input.extension-check' ).attr( 'data-name', 'bp_video_extensions_support[' + totalCount + '][is_active]' );
							$( this ).find( 'input.extension-name' ).attr( 'name', 'bp_video_extensions_support[' + totalCount + '][name]' );
							$( this ).find( 'input.extension-name' ).attr( 'data-name', 'bp_video_extensions_support[' + totalCount + '][name]' );
							$( this ).find( 'input.extension-hidden' ).attr( 'name', 'bp_video_extensions_support[' + totalCount + '][hidden]' );
							$( this ).find( 'input.extension-hidden' ).attr( 'data-name', 'bp_video_extensions_support[' + totalCount + '][hidden]' );
							$( this ).find( 'input.extension-extension' ).attr( 'name', 'bp_video_extensions_support[' + totalCount + '][extension]' );
							$( this ).find( 'input.extension-extension' ).attr( 'data-name', 'bp_video_extensions_support[' + totalCount + '][extension]' );
							$( this ).find( 'input.extension-mime' ).attr( 'name', 'bp_video_extensions_support[' + totalCount + '][mime_type]' );
							$( this ).find( 'input.extension-mime' ).attr( 'data-name', 'bp_video_extensions_support[' + totalCount + '][mime_type]' );
							$( this ).find( 'input.extension-desc' ).attr( 'name', 'bp_video_extensions_support[' + totalCount + '][description]' );
							$( this ).find( 'input.extension-desc' ).attr( 'data-name', 'bp_video_extensions_support[' + totalCount + '][description]' );
							$( this ).find( 'a.btn-check-mime-type' ).attr( 'id', 'bp_video_extensions_support[' + totalCount + '][mime_type]' );
							totalCount = totalCount + 1;
						}
					);

					totalCount = parseInt( $( '.extension-listing tr.default-extension' ).length );

				}
			);

			function makeIconSelect() {

				$( '.document-extensions-listing .extension-icon' ).each(
					function() {

						if ( $( this ).closest( 'td' ).find( '.icon-select-main' ).length === 0 ) {
							var iconsArray = [];
							$( this ).closest( 'td' ).find( 'select.extension-icon option' ).each(
								function(){
									var iconClass = $( this ).val();
									var text      = this.innerText;
									var item      = '<li><i class="' + iconClass + '"></i><span>' + text + '</span></li>';
									iconsArray.push( item );
								}
							);

							$( this ).closest( 'td' ).find( 'select.extension-icon' ).parent().append( '<div class="icon-select-main"><span class="icon-select-button"></span><div class="custom-extension-list"> <ul class="custom-extension-list-select">' + iconsArray + '</ul></div></div>' );

							// Set the button value to the first el of the array by default.
							var currentSelectedIcon     = $( this ).closest( 'td' ).find( '.extension-icon' ).val();
							var currentSelectedIconText = $( this ).closest( 'td' ).find( '.extension-icon option:selected' ).text();
							$( this ).closest( 'td' ).find( '.icon-select-main .icon-select-button' ).html( '<li><i class="' + currentSelectedIcon + '"></i><span>' + currentSelectedIconText + '</span></li>' );
						}

					}
				);

			}

			makeIconSelect();

			$( document ).on(
				'click',
				'.custom-extension-list-select li',
				function() {
					var iconClass = $( this ).find( 'i' ).attr( 'class' );
					var text      = this.innerText;
					var item      = '<li><i class="' + iconClass + '"></i><span>' + text + '</span></li>';
					$( this ).closest( 'td' ).find( '.icon-select-main .icon-select-button' ).html( item );
					$( this ).closest( 'td' ).find( '.icon-select-main .custom-extension-list' ).toggle();
					$( this ).closest( 'td' ).find( 'select.extension-icon option[value="' + iconClass + '"]' ).attr( 'selected','selected' );
					if ( $( this ).closest( '.icon-select-main' ).siblings( '.bb-icon' ).length ) {
						$( this ).closest( '.icon-select-main' ).siblings( '.bb-icon' ).attr( 'class', 'bb-icon ' + iconClass );
					}
				}
			);

			$( document ).on(
				'click',
				'.icon-select-main .icon-select-button',
				function() {
					$( this ).siblings( '.custom-extension-list' ).toggle();
				}
			);

			$( document ).on(
				'click',
				'table.extension-listing #btn-remove-extensions',
				function() {

					var parent = $( this ).closest( 'table.extension-listing' );
					$( this ).closest( 'tr' ).remove();
					var totalCount = parseInt( $( '.extension-listing tr.extra-extension' ).length );
					totalCount     = 1;
					var media_type = 'bp_document';
					if ( 
						$( this ).closest('tr.custom-extension').length > 0 &&
						$( this ).closest('tr.custom-extension').hasClass('video-extensions')
					) {
						media_type = 'bp_video';
					}
					parent.find( 'tbody tr.extra-extension' ).each(
						function() {
								$( this ).find( 'input.extension-check' ).attr( 'name', media_type + '_extensions_support[' + totalCount + '][is_active]' );
								$( this ).find( 'input.extension-check' ).attr( 'data-name', media_type + '_extensions_support[' + totalCount + '][is_active]' );
								$( this ).find( 'input.extension-name' ).attr( 'name', media_type + '_extensions_support[' + totalCount + '][name]' );
								$( this ).find( 'input.extension-name' ).attr( 'data-name', media_type + '_extensions_support[' + totalCount + '][name]' );
								$( this ).find( 'input.extension-hidden' ).attr( 'name', media_type + '_extensions_support[' + totalCount + '][hidden]' );
								$( this ).find( 'input.extension-hidden' ).attr( 'data-name', media_type + '_extensions_support[' + totalCount + '][hidden]' );
								$( this ).find( 'input.extension-extension' ).attr( 'name', media_type + '_extensions_support[' + totalCount + '][extension]' );
								$( this ).find( 'input.extension-extension' ).attr( 'data-name', media_type + '_extensions_support[' + totalCount + '][extension]' );
								$( this ).find( 'input.extension-mime' ).attr( 'name', media_type + '_extensions_support[' + totalCount + '][mime_type]' );
								$( this ).find( 'input.extension-mime' ).attr( 'data-name', media_type + '_extensions_support[' + totalCount + '][mime_type]' );
								$( this ).find( 'input.extension-desc' ).attr( 'name', media_type + '_extensions_support[' + totalCount + '][description]' );
								$( this ).find( 'input.extension-desc' ).attr( 'data-name', media_type + '_extensions_support[' + totalCount + '][description]' );
								$( this ).find( 'select.extension-icon' ).attr( 'name', media_type + '_extensions_support[' + totalCount + '][icon]' );
								$( this ).find( 'select.extension-icon' ).attr( 'data-name', media_type + '_extensions_support[' + totalCount + '][icon]' );
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

					var mimeToId = $( this ).attr( 'id' );

					$( document ).find( 'input[name="' + mimeToId + '"]' ).val( '' );
					var valueCopied = $( document ).find( '#mime-type' ).val();
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
					$( document ).find( '.bp-hello-mime' ).attr( 'id', 'bp-hello-container' );
					if ( $( document ).find( '#bp-hello-backdrop' ).length ) {
					} else {
						var finder = $( document ).find( '.bp-hello-mime' );
						$( '<div id="bp-hello-backdrop" style="display: none;"></div>' ).insertBefore( finder );
					}
					var backdrop = document.getElementById( 'bp-hello-backdrop' ),
						modal    = document.querySelector( '#bp-hello-container:not(.bb-onload-modal)' );

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

					var backdrop_click    = backdrop.contains( event.target ),
						modal_close_click = event.target.classList.contains( 'close-modal' );

					if ( ! modal_close_click && ! backdrop_click ) {
						return;
					}

					$( event.target ).closest( '#bp-hello-container' ).hide();

					$( document ).find( '#bp-document-file-input' ).val( '' );
					$( document ).find( '.show-document-mime-type' ).hide();
					$( document ).find( '.show-document-mime-type input#mime-type' ).val( '' );
				},
				false
			);

			// Show the moderation activate popup when admin click spam user link.
			$( document ).on(
				'click',
				'.bp-show-moderation-alert',
				function () {
					var ActionType = $( this ).attr( 'data-action' );
					$( '#bp-hello-backdrop' ).show();
					$( '#bp-hello-container' ).find( '.bp-spam-action-msg' ).hide();
					$( '#bp-hello-container' ).find( '.bp-not-spam-action-msg' ).hide();
					if ( 'spam' === ActionType ) {
						$( '#bp-hello-container' ).find( '.bp-spam-action-msg' ).show();
					} else if ( 'not-spam' === ActionType ) {
						$( '#bp-hello-container' ).find( '.bp-not-spam-action-msg' ).show();
					}
					$( '#bp-hello-container' ).show().addClass( 'deactivation-popup' );
				}
			);

			// Show the confirmation popup when user clicks single BB component disable link.
			$( document ).on(
				'click',
				'.bp-show-deactivate-popup a',
				function ( event ) {
					event.preventDefault();
					$( '#bp-hello-container' ).find( '.bp-hello-content' ).empty();
					$( '#bp-hello-backdrop' ).show();
					$( '#bp-hello-container' ).show().addClass( 'deactivation-popup' );
					$( '#bp-hello-container' ).find( '.component-deactivate' ).attr( 'data-redirect', $( this ).attr( 'href' ) );
					$( '#bp-hello-container' ).find( '.bp-hello-content' ).append( $( this ).parent().closest( '.row-actions' ).find( '.component-deactivate-msg' ).text() );
				}
			);

			// Close popup.
			$( document ).on(
				'click',
				'.close-modal',
				function () {
					$( '#bp-hello-backdrop' ).hide();
					$( '#bp-hello-container' ).hide().removeClass( 'deactivation-popup' );
					$( '#bp-hello-container' ).find( '.component-deactivate' ).removeClass( 'form-submit' );
				}
			);

			// Disable the component when click appropriate button in popup.
			$( document ).on(
				'click',
				'.component-deactivate',
				function ( event ) {
					event.preventDefault();
					if ( $( this ).hasClass( 'form-submit' ) ) {
						$( 'form#bp-admin-component-form' ).find( 'input[name="bp-admin-component-submit"]' ).trigger( 'click' );
					} else {
						window.location = $( this ).attr( 'data-redirect' );
					}
				}
			);

			// Show the confirmation popup when bulk component disabled.
			$( 'form#bp-admin-component-form' ).submit(
				function ( e ) {

					var action = $( '#bulk-action-selector-top[name="action"]' ).find( ':selected' ).val();

					if ( ! action ) {
						action = $( '#bulk-action-selector-top[name="action2"]' ).find( ':selected' ).val();
					}

					var confirmChecked = false;
					var msg            = '';
					$( '.mass-check-deactivate' ).each(
						function () {
							if ( $( this ).prop( 'checked' ) == true ) {
								confirmChecked = true;
							}
							msg = msg + $( this ).parent().find( '.component-deactivate-msg' ).text();
						}
					);

					if ( ! $( '#bp-hello-container' ).find( '.component-deactivate' ).hasClass( 'form-submit' ) && 'inactive' === action && true === confirmChecked ) {
						$( '#bp-hello-container' ).find( '.bp-hello-content' ).empty();
						if ( msg ) {
							e.preventDefault();
							$( '#bp-hello-backdrop' ).show();
							$( '#bp-hello-container' ).show().addClass( 'deactivation-popup' );
							$( '#bp-hello-container' ).find( '.bp-hello-content' ).html( msg );
							$( '#bp-hello-container' ).find( '.component-deactivate' ).addClass( 'form-submit' );
						}
					}
				}
			);

			// Moderation Reporting Block.
			$( document ).on(
				'change',
				'#bp_moderation_settings_reporting .bpm_reporting_content_content_label > input',
				function () {
					if ( $( this ).prop( 'checked' ) ) {
						$( this ).parent().next( 'label' ).removeClass( 'is_disabled' ).find( 'input[type="checkbox"]' ).prop( 'disabled', false );
					} else {
						$( this ).parent().next( 'label' ).addClass( 'is_disabled' ).find( 'input[type="checkbox"]' ).removeProp( 'checked' ).removeAttr( 'checked' ).prop( 'disabled', 'disabled' );
					}
					$( 'label[for="bpm_reporting_email_notification"]' ).removeClass( 'is_disabled' );
					if ( 0 === $( '.bpm_reporting_content_content_label > input:checked' ).length ) {
						$( '#bpm_reporting_email_notification' ).prop( 'checked', false );
						$( 'label[for="bpm_reporting_email_notification"]' ).addClass( 'is_disabled' );
					}
				}
			);

			/**
			 * Checked all item reporting inactive then disabled the email notification for it.
			 */
			if ( 0 === $( '.bpm_reporting_content_content_label > input:checked' ).length ) {
				$( '#bpm_reporting_email_notification' ).prop( 'checked', false );
				$( 'label[for="bpm_reporting_email_notification"]' ).addClass( 'is_disabled' );
			}

			$( document ).on(
				'click',
				'.bp-suspend-user, .bp-unsuspend-user',
				function () {
					var DataAction = $( this ).attr( 'data-action' );
					if ( 'suspend' === DataAction ) {
						return confirm( BP_ADMIN.moderation.suspend_confirm_message );
					} else if ( 'unsuspend' === DataAction ) {
						return confirm( BP_ADMIN.moderation.unsuspend_confirm_message );
					}
				}
			);

			$( document ).on(
				'click',
				'.notification-defaults',
				function () {
					var isHidden = $( this ).next( '.manage-defaults' );
					if ( isHidden.hasClass( 'manage-defaults-hide' ) ) {
						$( this ).next( '.manage-defaults' ).removeClass( 'manage-defaults-hide' );
					} else {
						$( this ).next( '.manage-defaults' ).addClass( 'manage-defaults-hide' );
					}
				}
			);

			$( document ).on(
				'click',
				'.bb-notification-checkbox',
				function () {
					if ( false === $( this ).prop( 'checked' ) ) {
						$( this ).parents( '.field-block' ).find( '.manage-defaults .field-wrap' ).addClass( 'disabled' );
					} else {
						$( this ).parents( '.field-block' ).find( '.manage-defaults .field-wrap' ).removeClass( 'disabled' );
					}
				}
			);

			// Run only post_type is member type and group type.
			if (
				'undefined' !== typeof BP_ADMIN.post_type &&
				'object' === typeof jQuery.wp &&
				'function' === typeof jQuery.wp.wpColorPicker
			) {
				var type = BP_ADMIN.post_type;
				if ( type ) {
					if ( $( '#' + type + '-label-background-color' ).length ) {
						$( '#' + type + '-label-background-color' ).wpColorPicker();
					}
					if ( $( '#' + type + '-label-text-color' ).length ) {
						$( '#' + type + '-label-text-color' ).wpColorPicker();
					}
					if ( $( '#' + type + '-label-color-type' ).length ) {
						$( document ).on(
							'change',
							'#' + type + '-label-color-type',
							function ( e ) {
								e.preventDefault();
								if ( 'default' === $( this ).val() ) {
									$( '#' + type + '-color-settings' ).hide();
								} else {
									$( '#' + type + '-color-settings' ).show();
								}
							}
						);
					}

					$( '.wp-picker-input-wrap .wp-picker-clear' ).click( function ( e ) {
						e.preventDefault();
						var colorPickerId = $( this ).closest( 'span' ).find( '.wp-color-picker' ).attr( 'id' );
						if ( type + '-label-background-color' === colorPickerId ) {
							var defaultBgColor = ( 'undefined' !== typeof BP_ADMIN.background_color ) ? BP_ADMIN.background_color : '';
							if ( defaultBgColor ) {
								$( '#' + type + '-label-background-color' ).val( defaultBgColor );
								$( '#' + type + '-background-color-colorpicker .wp-color-result' ).css( 'background-color', defaultBgColor );
							}
						}
						if ( type + '-label-text-color' === colorPickerId ) {
							var defaultTextColor = 'undefined' !== typeof BP_ADMIN.color ? BP_ADMIN.color : '';
							if ( defaultTextColor ) {
								$( '#' + type + '-label-text-color' ).val( defaultTextColor );
								$( '#' + type + '-text-color-colorpicker .wp-color-result' ).css( 'background-color', defaultTextColor );
							}
						}
					} );
				}
			}

			$( document ).on(
				'click',
				'.bb-hide-pw',
				function ( e ) {
					e.preventDefault();
					var $this  = $( this );
					var $input = $this.closest( '.password-toggle' ).find( 'input' );
					var $default_type = $input.data( 'type' ) ? $input.data( 'type' ) : 'text';
					$this.toggleClass( 'bb-show-pass' );
					if ( $this.hasClass( 'bb-show-pass' ) ) {
						$input.attr( 'type', $default_type );
					} else {
						$input.attr( 'type', 'password' );
					}
				}
			);

			// Redirection select box Select2
			if( typeof $.fn.select2 !== 'undefined' ) {
				if( $( '#bb-login-redirection' ).length > 0 ) {
					$( '#bb-login-redirection' ).select2({
						containerCssClass: 'custom-select2',
						dropdownCssClass: 'custom-dropdown-select2'
					});
				}
				if( $( '#bb-logout-redirection' ).length > 0 ) {
					$( '#bb-logout-redirection' ).select2({
						containerCssClass: 'custom-select2',
						dropdownCssClass: 'custom-dropdown-select2'
					});
				}
			}

			// Login Redirection Settings Show/Hide.
			var loginRedSettings = $( '#bb-login-redirection' );
			var currentLoginRedSettings = '0';

			if ( loginRedSettings.length ) {
				currentLoginRedSettings = loginRedSettings.val();

				if ( '0' === currentLoginRedSettings ) {
					$( '.login-redirection-text-box' ).show();
					loginRedSettings.closest( 'td' ).find( '.description, .bb-description' ).hide();
				} else {
					$( '.login-redirection-text-box' ).hide();
					loginRedSettings.closest( 'td' ).find( '.description, .bb-description' ).show();
				}

				$( loginRedSettings ).change(
					function () {
						currentLoginRedSettings = $( this ).val();
						if ( '0' === currentLoginRedSettings ) {
							$( '.login-redirection-text-box' ).show();
							loginRedSettings.closest( 'td' ).find( '.description, .bb-description' ).hide();
						} else {
							$( '.login-redirection-text-box' ).hide();
							loginRedSettings.closest( 'td' ).find( '.description, .bb-description' ).show();
						}

					}
				);
			}

			// Logout Redirection Settings Show/Hide.
			var logoutRedSettings = $( '#bb-logout-redirection' );
			var currentLogoutRedSettings = '0';

			if ( logoutRedSettings.length ) {
				currentLogoutRedSettings = logoutRedSettings.val();

				if ( '0' === currentLogoutRedSettings ) {
					$( '.logout-redirection-text-box' ).show();
					logoutRedSettings.closest( 'td' ).find( '.description, .bb-description' ).hide();
				} else {
					$( '.logout-redirection-text-box' ).hide();
					logoutRedSettings.closest( 'td' ).find( '.description, .bb-description' ).show();
				}

				$( logoutRedSettings ).change(
					function () {
						currentLogoutRedSettings = $( this ).val();
						if ( '0' === currentLogoutRedSettings ) {
							$( '.logout-redirection-text-box' ).show();
							logoutRedSettings.closest( 'td' ).find( '.description, .bb-description' ).hide();
						} else {
							$( '.logout-redirection-text-box' ).hide();
							logoutRedSettings.closest( 'td' ).find( '.description, .bb-description' ).show();
						}

					}
				);
			}
		}
	);

	/* jshint ignore:start */
	function setCookie(cname, cvalue, exMins) {
		var d = new Date();
		d.setTime( d.getTime() + (exMins * 60 * 1000) );
		var expires     = 'expires=' + d.toUTCString();
		document.cookie = cname + '=' + cvalue + ';' + expires + ';path=/';
	}

	// Read a page's GET URL variables and return them as an associative array.

	$.bbGetParameterByName = function(name) {
		var results = new RegExp('[\\?&amp;]' + name + '=([^&amp;#]*)').exec(window.location.href);
		return (results && results[1]) ? results[1] : null;
	};

	function bbgetUrlParameter(name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
		return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	}

	// Domain Restrictions.
	$( document ).on(
		'click',
		'.registration-restrictions-listing .registration-restrictions-add-rule',
		function ( e ) {
			e.preventDefault();
			var $this = $( this );
			var newindex = parseInt( $this.prev( '.registration-restrictions-lastindex' ).val() ) + 1;
			var regex = new RegExp( 'placeholder_priority_index', 'g' );
			var $row = $this.closest( '.registration-restrictions-listing' ).find( '.registration-restrictions-rule-list .registration-restrictions-rule.custom' ).html();
			$this.closest( '.registration-restrictions-listing' ).
				find( '.registration-restrictions-rule-list' ).
				append( ' <div class="registration-restrictions-rule untouched"> ' + $row.replace( regex, newindex ) + ' </div> ' );
			$this.prev( '.registration-restrictions-lastindex' ).val( newindex );
			domainRestictionUpdatePriorityNumber();
		}
	);

	$( document ).on(
		'click',
		'.registration-restrictions-listing .registration-restrictions-rule-remove',
		function ( e ) {
			e.preventDefault();
			var $this = $( e.currentTarget );
			var $listing = $this.parents( '.bb-domain-restrictions-listing' );
			var newindex = parseInt( $this.prev( '.registration-restrictions-lastindex' ).val() ) - 1;
			if ( !$this.closest( '.registration-restrictions-rule' ).hasClass( 'custom' ) ) {
				$this.closest( '.registration-restrictions-rule' ).remove();
				$this.parents( '.registration-restrictions-listing' ).find( '.registration-restrictions-lastindex' ).val( newindex );
			}

			// If no rules or all are never allow then enable all option.
			if ( 'undefined' !== typeof $listing && $listing.length > 0 ) {
				if ( 0 === $listing.find( '.registration-restrictions-rule' ).not( '.custom' ).length ) {
					$( '.bb-domain-restrictions-listing .registration-restrictions-select' ).find( 'option' ).attr( 'disabled', false );
				} else {
					var $select = $listing.find( '.registration-restrictions-rule:not(.custom) .registration-restrictions-input-select' );
					if (
						0 === $select.find( 'option[value="only_allow"]:selected' ).length &&
						0 === $select.find( 'option[value="always_allow"]:selected' ).length
					) {
						$( '.bb-domain-restrictions-listing .registration-restrictions-select' ).find( 'option' ).attr( 'disabled', false );
					}
				}
			}

			domainRestictionUpdatePriorityNumber();
		}
	);

	$( document ).on(
		'change',
		'.registration-restrictions-listing .registration-restrictions-select',
		function ( e ) {
			e.preventDefault();
			var $this = $( e.currentTarget );
			var $dropdowns = $this.parents( '.bb-domain-restrictions-listing' ).find( '.registration-restrictions-select' );
			var $other_dropdowns = $this.parents( '.bb-domain-restrictions-listing' ).find( '.registration-restrictions-select' ).not( $this );
			var current_val = '';
			var all_values = [];

			$dropdowns.each( function ( index, element ) {
				var element_val = $( this ).find( 'option:selected' ).val();
				all_values[ index ] = element_val;

				if ( $this.length !== 0 && $this.get( 0 ) === element ) {
					current_val = element_val;
				}
			} );

			var filtered_val = bb_unique( all_values );
			filtered_val = filtered_val.filter( function ( v ) {
				return v !== '';
			} );

			if ( filtered_val.length === 1 ) {
				if ( filtered_val[ 0 ] === 'always_allow' ) {
					if ( 1 === $other_dropdowns.length ) {
						$other_dropdowns.find( 'option' ).attr( 'disabled', false );
						$other_dropdowns.find( 'option[value="only_allow"]' ).attr( 'disabled', true );
					} else {
						$dropdowns.find( 'option' ).attr( 'disabled', false );
						$dropdowns.find( 'option[value="only_allow"]' ).attr( 'disabled', true );
					}
				} else if ( filtered_val[ 0 ] === 'only_allow' ) {
					if ( 1 === $other_dropdowns.length ) {
						$other_dropdowns.find( 'option' ).attr( 'disabled', false );
						$other_dropdowns.find( 'option[value="always_allow"]' ).attr( 'disabled', true );
					} else {
						$dropdowns.find( 'option' ).attr( 'disabled', false );
						$dropdowns.find( 'option[value="always_allow"]' ).attr( 'disabled', true );
					}
				} else if ( filtered_val[ 0 ] === 'never_allow' ) {
					$dropdowns.find( 'option' ).attr( 'disabled', false );
				}
			} else if (
				filtered_val.length !== 0 &&
				filtered_val.length > 1 &&
				current_val !== ''
			) {
				if ( 'only_allow' === current_val ) {
					$other_dropdowns.find( 'option' ).attr( 'disabled', false );
					$other_dropdowns.find( 'option[value="always_allow"]' ).attr( 'disabled', true );
				} else if ( 'always_allow' === $this.find( ':selected' ).val() ) {
					$other_dropdowns.find( 'option' ).attr( 'disabled', false );
					$other_dropdowns.find( 'option[value="only_allow"]' ).attr( 'disabled', true );
				}
			}
			validateDuplicateDomainRuleEntry();
		}
	);

	$( '.registration-restrictions-rule-list.bb-sortable' ).sortable( {
		update: function () {
			domainRestictionUpdatePriorityNumber();
		},
	} );

	function domainRestictionUpdatePriorityNumber() {
		$( '.bb-sortable .registration-restrictions-rule:not(.custom)' ).each( function ( index ) {
			$( this ).find( '.registration-restrictions-priority' ).html( index + 1 );
		} );
	}

	// Function to validate duplicate entry.
	function validateDuplicateDomainRuleEntry( e, eventType ) {
		var wrapper = $( '#bb-domain-restrictions-setting' );
		//Store all the entry.
		var allDomainExtension = [];
		// Store Errors
		var domainRestrictionsErrors = [];

		if ( e && e.currentTarget && $( e.currentTarget ).closest( '.registration-restrictions-rule' ).hasClass( 'untouched' ) ) {
			$( e.currentTarget ).closest( '.registration-restrictions-rule' ).removeClass( 'untouched' );
		}

		if ( eventType === 'submit' || eventType === 'remove' || eventType === 'add' ) {
			$( '.registration-restrictions-rule' ).removeClass( 'untouched' );
		}

		$( '.bb-domain-restrictions-listing .registration-restrictions-rule' ).not( '.custom' ).not( '.untouched' ).each( function () {
			var currentDomain = $( this ).find( '.registration-restrictions-domain' ).val().trim().toLowerCase();
			var currentExtension = $( this ).find( '.registration-restrictions-tld' ).val().trim().toLowerCase();
			var conditionType = $( this ).find( '.registration-restrictions-input-select' ).val();

			$( this ).removeClass( 'error' );
			wrapper.children( '.restrictions-error' ).html( '' );

			if ( '' !== currentDomain && '' !== currentExtension ) {
				if ( 'undefined' === typeof allDomainExtension[ currentDomain + '_' + currentExtension ] ) {
					allDomainExtension[ currentDomain + '_' + currentExtension ] = 1;
				} else {
					allDomainExtension[ currentDomain + '_' + currentExtension ] += 1;
					$( this ).addClass( 'error' );
					if ( !domainRestrictionsErrors.includes( BP_ADMIN.bb_registration_restrictions.feedback_messages.duplicate ) ) {
						domainRestrictionsErrors.push( BP_ADMIN.bb_registration_restrictions.feedback_messages.duplicate );
					}
				}
			}

			if ( eventType === 'submit' || eventType === 'remove' || eventType === 'add' ) {
				if ( '' === currentDomain || '' === currentExtension || '' === conditionType ) {
					$( this ).addClass( 'error' );
					if ( !domainRestrictionsErrors.includes( BP_ADMIN.bb_registration_restrictions.feedback_messages.empty ) ) {
						domainRestrictionsErrors.push( BP_ADMIN.bb_registration_restrictions.feedback_messages.empty );
					}
				}
			}
		} );

		$( '#bb-domain-restrictions-setting' ).children( '.restrictions-error' ).html( '' );

		for ( var i = 0; i < domainRestrictionsErrors.length; i++ ) {
			var error = '<p>' + domainRestrictionsErrors[ i ] + '</p>';
			$( '#bb-domain-restrictions-setting' ).children( '.restrictions-error' ).append( error );
		}

		if ( domainRestrictionsErrors.length > 0 && eventType === 'submit' ) {
			e.preventDefault();
		}
	}

	// Validate Email Restrictions emails duplicate entry.
	function validateDuplicateEmailRuleEntry( e, eventType ) {
		//Store all the entry.
		var allEmailEntries = [];
		var emailRestrictionsErrors = [];
		$( '#bb-email-restrictions-setting .registration-restrictions-rule' ).not( '.custom' ).each( function () {
			var currentEmailValue = $( this ).find( '.registration-restrictions-domain' ).val().trim().toLowerCase();
			var currentEmailCondition = $( this ).find( '.registration-restrictions-input-select' ).val();

			if ( currentEmailValue !== '' ) {
				if ( 'undefined' === typeof allEmailEntries[ currentEmailValue ] ) {
					allEmailEntries[ currentEmailValue ] = 1;
					$( this ).removeClass( 'error' );
				} else {
					allEmailEntries[ currentEmailValue ] += 1;
					$( this ).addClass( 'error' );
					if ( !emailRestrictionsErrors.includes( BP_ADMIN.bb_registration_restrictions.feedback_messages.duplicate ) ) {
						emailRestrictionsErrors.push( BP_ADMIN.bb_registration_restrictions.feedback_messages.duplicate );
					}
				}
			}

			var emptyValueOnSubmit = ( eventType === 'submit' || eventType === 'remove' || eventType === 'add' ) && ( currentEmailValue === '' || currentEmailCondition === '' );
			var eventFromField = e && e.currentTarget;
			var emptyConditionOnChange = eventFromField && e.currentTarget.classList.contains( 'registration-restrictions-input-select' ) && currentEmailCondition === '';
			var emptyEmailOnChange = eventFromField && e.currentTarget.classList.contains( 'registration-restrictions-domain' ) && currentEmailValue === '';

			if ( emptyValueOnSubmit || emptyConditionOnChange || emptyEmailOnChange ) {
				$( this ).addClass( 'error' );
				if ( !emailRestrictionsErrors.includes( BP_ADMIN.bb_registration_restrictions.feedback_messages.empty ) ) {
					emailRestrictionsErrors.push( BP_ADMIN.bb_registration_restrictions.feedback_messages.empty );
				}
			}
		} );

		$( '#bb-email-restrictions-setting' ).children( '.restrictions-error' ).html( '' );

		for ( var i = 0; i < emailRestrictionsErrors.length; i++ ) {
			var error = '<p>' + emailRestrictionsErrors[ i ] + '</p>';
			$( '#bb-email-restrictions-setting' ).children( '.restrictions-error' ).append( error );
		}

		if ( emailRestrictionsErrors.length > 0 ) {
			if ( eventType === 'submit' ) {
				e.preventDefault();
			}
		}
	}

	// Handle input and blur event on domain and extension textboxes.
	$( document ).on( 'input blur', '.bb-domain-restrictions-listing .registration-restrictions-domain, .registration-restrictions-tld', function ( e ) {
		validateDuplicateDomainRuleEntry( e );
	} );

	// Handle adding domain restriction.
	$( '.registration-restrictions-add-rule' ).on( 'click', function () {
		var $this = $( this );
		if ( $this.hasClass( 'domain-rule-add' ) ) {
			validateDuplicateDomainRuleEntry( null, 'add' );
		} else {
			validateDuplicateEmailRuleEntry( null, 'add' );
		}
	} );

	// Handle removing domain restriction.
	$( document ).on( 'click', '.registration-restrictions-rule-remove', function () {
		var $this = $( this );
		if ( $this.hasClass( 'domain-rule-remove' ) ) {
			validateDuplicateDomainRuleEntry( null, 'remove' );
		} else {
			validateDuplicateEmailRuleEntry( null, 'remove' );
		}
	} );

	// Handle settings save.
	$( '#bb_registration_restrictions' ).parents( 'form' ).on( 'submit', function ( e ) {
		validateDuplicateDomainRuleEntry( e, 'submit' );
		validateDuplicateEmailRuleEntry( e, 'submit' );
		if ( $( '#bb-domain-restrictions-setting .registration-restrictions-rule.error' ).length > 0 ) {
			$( 'html, body' ).animate( {
				'scrollTop': $( '#bb-domain-restrictions-setting' ).offset().top - 75,
			} );
		} else if ( $( '#bb-email-restrictions-setting .registration-restrictions-rule.error' ).length > 0 ) {
			$( 'html, body' ).animate( {
				'scrollTop': $( '#bb-email-restrictions-setting' ).offset().top - 75,
			} );
		}
	} );

	// Handle input and blur event on email textboxes.
	$( document ).on( 'input blur', '#bb-email-restrictions-setting .registration-restrictions-domain', function ( e ) {
		validateDuplicateEmailRuleEntry( e );
	} );

	$( document ).on( 'change', '#bb-email-restrictions-setting .registration-restrictions-input-select', function ( e ) {
		validateDuplicateEmailRuleEntry( e );
	} );

	function bb_unique( array ) {
		return $.grep( array, function ( el, index ) {
			return index === $.inArray( el, array );
		} );
	}

	function renderIntegrations() {
		var renderOptions = {
			previewParent: $( '.bb-integrations-section-listing' ),
			data: [
				{
					"type": "title",
					"text": "Ad Manager"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Advanced-Ads.png",
					"int_type": "Compatible",
					"title": "Advanced Ads",
					"desc": "Manage and optimize your ads in WordPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Advanced-Ads-Add-ons-e1588826603796.png",
					"int_type": "Compatible",
					"title": "Advanced Ads Add-Ons",
					"desc": "Support for almost all Advanced Ads Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/09/Advanced-Ads-BuddyBoss-Integration.png",
					"int_type": "Third-party",
					"title": "Advanced Ads Pro - BuddyBoss Integration",
					"desc": "Connect BuddyBoss with Advanced Ads Pro"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/AWCP.png",
					"int_type": "Compatible",
					"title": "AWP Classifieds",
					"desc": "Add a classified ads section to your WordPress site"
				},
				{
					"type": "title",
					"text": "Affiliate Management"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Affiliate-Manager.png",
					"int_type": "Compatible",
					"title": "WP Affiliate Manager",
					"desc": "Recruit, manage, track and pay your affiliates"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Affilate-Manager-Add-ons.png",
					"int_type": "Compatible",
					"title": "WP Affiliate Manager Add-ons",
					"desc": "Support for almost all WP Affiliate Manager Add-ons"
				},
				{
					"type": "title",
					"text": "Anti-spam"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/Social_Login.png",
					"int_type": "Compatible",
					"title": "Social Login",
					"desc": "Fully-customizable plugin that integrates with your existing login/registration system"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/Thrive-Comments.png",
					"int_type": "Compatible",
					"title": "Thrive Comments",
					"desc": "A superior WordPress comments plugin"
				},
				{
					"type": "title",
					"text": "Automation"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/06/AutomatorWP.png",
					"int_type": "Compatible",
					"title": "AutomatorWP",
					"desc": "Create automated workflows for your WordPress website"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/06/AutomatorWP-Add-ons.png",
					"int_type": "Third-party",
					"title": "AutomatorWP + BuddyBoss Integration",
					"desc": "Connect BuddyBoss with AutomatorWP"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/06/AutomatorWP-Add-ons.png",
					"int_type": "Compatible",
					"title": "AutomatorWP Add-ons",
					"desc": "Support for almost all AutomatorWP Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2023/04/Suretrigger-512x512-logo-624x624.png",
					"int_type": "Third-party",
					"title": "SureTriggers",
					"desc": "We help you connect your apps and automate your business."
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/uncanny-automator-mascot-icon-4-624x624.png",
					"int_type": "Third-party",
					"title": "Uncanny Automator",
					"desc": "Set triggers and actions to automate stuff on your WordPress"
				},
				{
					"type": "title",
					"text": "bbPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "bbPress Auto Block Spammers",
					"desc": "Block spammers from using your forums"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/rtMedia-for-WordPress-BuddyPress-and-bbPress.png",
					"int_type": "Compatible",
					"title": "rtMedia for WordPress, BuddyPress, and bbPress",
					"desc": "Media solution for your WordPress, BuddyPress, and bbPress sites"
				},
				{
					"type": "title",
					"text": "BuddyBoss App"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2023/01/blockli-624x624.jpeg",
					"int_type": "Third-party",
					"title": "Blockli",
					"desc": "Beautiful Screens For Your BuddyBoss App"
				},
				{
					"type": "title",
					"text": "BuddyPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Block, Suspend, Report for BuddyPress",
					"desc": "Allow users to block and report other members, and allow administrators to suspend users"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BP Auto Group Join",
					"desc": "Automatically join new and existing BuddyPress members to BuddyPress Groups"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BP Bulk Delete",
					"desc": "Bulk delete BuddyPress Activity, Message and Notifications"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BP Email Assign Templates",
					"desc": "Override the default BuddyPress email template"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BP Local Avatars",
					"desc": "Create Gravatar Avatars and store them locally"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BP Maps for Members",
					"desc": "Add your BuddyPress member locations and maps to your website"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BP Messages Tool",
					"desc": "Manage messages sent and received by your BuddyPress users"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BP Profile Shortcodes Extra",
					"desc": "Display a range of aspects from member profiles and groups using shortcodes"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BP Simple Private",
					"desc": "Hide content from non-logged in users"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BP xProfile Location",
					"desc": "Populate and validate the address fields for members"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Third-party",
					"title": "Buddy User Notes",
					"desc": "Let your members create notes and reminders from their Profile page"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyBlog",
					"desc": "Easy frontend blogging with BuddyPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyBoss_Plugin.png",
					"int_type": "Compatible",
					"title": "BuddyBoss Media",
					"desc": "Upload photos, create and manage albums for BuddyPress profiles and groups"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyDrive",
					"desc": "Let community members share files or folders with ease"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyForms_Members.png",
					"int_type": "Compatible",
					"title": "BuddyForms Members",
					"desc": "Extension for the BuddyForms form builder plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyMessageUX",
					"desc": "Allow users to send messages without leaving the profile page"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyNotes",
					"desc": "Let your members create notes from their Profile page"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPages",
					"desc": "Add custom pages to BuddyPress groups and member profiles"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Analytics",
					"desc": "Track users' visits across your BuddyPress website"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Clear Notifications",
					"desc": "Allow your users to clear all the notifications in one click"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Compliments",
					"desc": "Add a smart way for BuddyPress members to interact with each other via compliments"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Confirm Actions",
					"desc": "Ask the user to confirm before cancelling friendship/leaving group/unfollowing other users"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Deactivate Account",
					"desc": "Allows users to deactivate/reactivate their account"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Default Cover Photo",
					"desc": "Replace the default BuddyPress cover photo "
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Default Group Tab",
					"desc": "Control the default landing component/page of a group"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Edit Activity",
					"desc": "Let BuddyPress members edit their activity posts and replies on the front-end"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Editable Activity",
					"desc": "Allow users to edit their activity and activity comments easily "
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Extended Friendship Request",
					"desc": "Allow users to send a personalized message with the BuddyPress friendship requests"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Featured Members",
					"desc": "Display the list of featured users as list or slider"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress for LearnDash",
					"desc": "Turn your online courses site into a social education platform"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Friendship Restrictions",
					"desc": "Restrict BuddyPress Friendship features"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Global Search",
					"desc": "Let your members search through every BuddyPress component"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Group Email Subscription",
					"desc": "Email subscriptions for BuddyPress Groups"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Group Tabs Creator Pro",
					"desc": "Create and manage unlimited BuddyPress Group tabs and sub-tabs"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Lock Unlock Activity",
					"desc": "Allow users to lock/open their activity feeds for commenting"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Moderation Tools",
					"desc": "Add a Report Abuse and other moderation functionality to BuddyPress "
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Poke",
					"desc": "Allow your BuddyPress users to poke each other like Facebook"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Power SEO",
					"desc": "Enable SEO functionality for BuddyPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Private Message Rate Limiter",
					"desc": "Restrict users from sending a large number of messages"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Profile Visibility Manager",
					"desc": "Allow users to manage their account privacy"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Recent Profile Visitors",
					"desc": "Show most recent profile visitors and most popular users"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Registration Options",
					"desc": "Prevent users and bots from accessing your BuddyPress or bbPress components until they are approved"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Simple Events",
					"desc": "Allow members to create, edit and delete Events from their profile page"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Simple Terms And Conditions",
					"desc": "Add an opt-in checkbox to the BuddyPress registration form"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress User Badges",
					"desc": "Add badge functionality to BuddyPress based communities"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyBoss_Plugin.png",
					"int_type": "Compatible",
					"title": "BuddyPress User Blog",
					"desc": "Personal blog space for your members"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress User Circles",
					"desc": "Allow users to create user lists"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress User Contact Form",
					"desc": "Let users have a contact form on their profile"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Third-party",
					"title": "BuddyPress User Profile Tabs Creator Pro",
					"desc": "Create and manage BuddyPress user profile tabs "
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress User Testimonials",
					"desc": "Allow users to leave recommendations/testiomonials for other users"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "BuddyPress Xprofile Custom Field Types",
					"desc": "Add essential field types to BuddyPress profile"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Conditional Profile Fields for BuddyPress",
					"desc": "Set conditions for the hiding/showing profile fields based conditional logic"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/GamiPress-BuddyPress-Integration.png",
					"int_type": "Compatible",
					"title": "GamiPress – BuddyPress Group Leaderboard",
					"desc": "Add a leaderboard in BuddyPress groups "
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/GamiPress-BuddyPress-Integration.png",
					"int_type": "Compatible",
					"title": "GamiPress + BuddyPress Integration",
					"desc": "Gamify your BuddyPress community website"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/GeoDirectory-BuddyPress-Integration.png",
					"int_type": "Third-party",
					"title": "GeoDirectory + BuddyPress Integration",
					"desc": "Create a hybrid listings directory and social network"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/08/Groundhogg-BuddyPress-Integration.png",
					"int_type": "Third-party",
					"title": "Groundhogg - BuddyBoss Integration",
					"desc": "Combine the power of BuddyBoss and Groundhogg"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Invite Anyone",
					"desc": "Allow your user to  send email invites to groups and the site"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/LifterLMS-BuddyPress-Integration.png",
					"int_type": "Compatible",
					"title": "LifterLMS - BuddyPress Integration",
					"desc": "Display the LifterLMS Student Dashboard page content on a user's BuddyPress profile"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Limit BuddyPress Groups Per User",
					"desc": "Restrict the number of groups a can create on your BuddyPress site"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "MediaMark",
					"desc": "Watermark solution for MediaPress add-on"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress.png",
					"int_type": "Compatible",
					"title": "MediaPress",
					"desc": "Modern media gallery solution for WordPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "MediaPress Add-ons",
					"desc": "Support for almost all MediaPress Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "MediaPress Featured Content",
					"desc": "Let your users showcase their BuddyPress media"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "MediaPress Media Moderator",
					"desc": "Keep your media managed with the moderation tools"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "MediaPress Paid Memberships Pro Restrictions",
					"desc": "Add restrictions based on membership levels for MediaPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "MediaPress User Watermark",
					"desc": "Allow users to add a watermark on media"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/MemberPress-BuddyPress.png",
					"int_type": "Compatible",
					"title": "MemberPress + BuddyPress",
					"desc": "Integrate powerful social features of BuddyPress with MemberPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/myCRED-Add-Ons.png",
					"int_type": "Compatible",
					"title": "myCRED BuddyPress Charges",
					"desc": "Charge your BuddyPress/BuddyBoss users for event triggers"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/myCRED-Add-Ons.png",
					"int_type": "Compatible",
					"title": "myCred for BuddyPress Compliments",
					"desc": "Let BuddyPress users send each other compliments or eGifts"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "One Click Mark Spammer",
					"desc": "Let site administrators mark users as a spammer with a single click "
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Restrict-Content-Pro-Add-ons.png",
					"int_type": "Compatible",
					"title": "Restrict Content Pro - BuddyPress Integration",
					"desc": "Connect BuddyPress with Restrict Content Pro"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Restrictions for BuddyPress",
					"desc": "BuddyPress area restrictions WordPress plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2022/04/Smart-User-Slug-Hider.png",
					"int_type": "Compatible",
					"title": "Smart User Slug Hider",
					"desc": "Enhance the security of your BuddyBoss site by hiding usernames in the User Profile URLs of BuddyBoss members."
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Social Articles",
					"desc": "Let your users create posts directly from their BuddyPress profiles"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Verified Member for BuddyPress",
					"desc": "Display a twitter-like ‘verified’ badge on a user's profile"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/WC4BP-WooCommerce-BuddyPress-Integration.png",
					"int_type": "Compatible",
					"title": "WC4BP - WooCommerce BuddyPress Integration",
					"desc": "Integrate your WooCommerce store with BuddyPress community"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/WishList-Member-BuddyBoss-Integration-Copy.png",
					"int_type": "Third-party",
					"title": "WishList Member + BuddyBoss Platform Integration",
					"desc": "Connect BuddyBoss Platform with WishList Member"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Fusion-BuddyPress-Integration.png",
					"int_type": "Third-party",
					"title": "WP Fusion + BuddyPress / BuddyBoss",
					"desc": "Send new BuddyPress users to your connected CRM"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/WP-Project-Manager.png",
					"int_type": "Compatible",
					"title": "WP Project Manager - BuddyPress Integration",
					"desc": "WordPress Project Management Plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "WP ULike",
					"desc": "Allow your visitors to like and unlike pages, posts, comments, bbPress & BuddyPress activities"
				},
				{
					"type": "title",
					"text": "CRM"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Fusion.png",
					"int_type": "Compatible",
					"title": "WP Fusion",
					"desc": "Synchronize your WordPress users with leading CRMs and marketing automation systems"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Fusion-BuddyPress-Integration.png",
					"int_type": "Third-party",
					"title": "WP Fusion + BuddyBoss App",
					"desc": "WP fusion and BuddyBoss App allows you to customize in-app purchases and push notifications based on tags"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Fusion-Add-Ons.png",
					"int_type": "Compatible",
					"title": "WP Fusion Add-ons",
					"desc": "Support for almost all WP Fusion Add-ons"
				},
				{
					"type": "title",
					"text": "Custom Login"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LoginPress.png",
					"int_type": "Compatible",
					"title": "LoginPress",
					"desc": "Tranform your boring wp-login.php login page into a beautiful customized login experience"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LoginPress_AddOns.png",
					"int_type": "Compatible",
					"title": "LoginPress Add-ons",
					"desc": "Support for almost all LoginPress Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/WordPress-Social-Login.png",
					"int_type": "Compatible",
					"title": "WordPress Social Login, Social Sharing",
					"desc": "Let your visitors login, comment, share and optionally auto-register from their favorite social login apps"
				},
				{
					"type": "title",
					"text": "Custom Redirect"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/Coming-Soon-Page-Under-Construction-Maintenance-Mode.png",
					"int_type": "Compatible",
					"title": "Coming Soon Page, Under Construction & Maintenance Mode",
					"desc": "Create simple Coming Soon Page, Under Construction or Maintenance Mode Page"
				},
				{
					"type": "title",
					"text": "Dynamic Content"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/11/If-So.png",
					"int_type": "Compatible",
					"title": "If-So",
					"desc": "Dynamic content WordPress plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/11/If-So-Add-ons.png",
					"int_type": "Compatible",
					"title": "If-So Add-ons",
					"desc": "Support for almost all If-So Add-ons "
				},
				{
					"type": "title",
					"text": "eCommerce"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2022/01/WC-Vendors-Pro.png",
					"int_type": "Third-party",
					"title": "WC Vendors Pro",
					"desc": "Create a multivendor marketplace and earn commission from every sale"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WooCommerce.png",
					"int_type": "Official",
					"title": "WooCommerce",
					"desc": "Open-source eCommerce plugin for WordPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WooCommerce-Add-Ons.png",
					"int_type": "Compatible",
					"title": "WooCommerce Add-ons",
					"desc": "Support for almost all WooCommerce Add-ons"
				},
				{
					"type": "title",
					"text": "Emails"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/Email-Subscribers-Newsletters.png",
					"int_type": "Compatible",
					"title": "Email Subscribers & Newsletters",
					"desc": "A simple and effective newsletter system"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2021/12/FluentCRM.png",
					"int_type": "Third-party",
					"title": "FluentCRM",
					"desc": "With the BuddyBoss and FluentCRM integration, turn your online community members into your email subscribers and start email marketing automation for your online community."
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/07/MailPoet.png",
					"int_type": "Compatible",
					"title": "MailPoet",
					"desc": "Emails and newsletters plugin for WordPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/Newsletter.png",
					"int_type": "Compatible",
					"title": "Newsletter",
					"desc": "A newsletter and email marketing system"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/SOCIAL-SUBSCRIBE-BOX.png",
					"int_type": "Compatible",
					"title": "Social Subscribe Box",
					"desc": "Let your users subscribe to your MailChimp newsletter"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/Thrive-Leads.png",
					"int_type": "Compatible",
					"title": "Thrive Leads",
					"desc": "All-in-one list-building solution"
				},
				{
					"type": "title",
					"text": "Events"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/mec-logo-icon.png",
					"int_type": "Compatible",
					"title": "Modern Events Calendar",
					"desc": "Responsive, mobile-friendly, and comprehensive events management plugin"
				},
				{
					"type": "title",
					"text": "Forms"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/BuddyForms_Members.png",
					"int_type": "Compatible",
					"title": "BuddyForms",
					"desc": "Contact, Registration, Post form builder & frontend editor"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/Contact-Form-7.png",
					"int_type": "Compatible",
					"title": "Contact Form 7",
					"desc": "WordPress plugin for creating lead generating forms"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/GravityForms.png",
					"int_type": "Compatible",
					"title": "Gravity Forms",
					"desc": "Premium Form Builder Plugin "
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/GravityForms_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Gravity Forms Add-ons",
					"desc": "Support for almost all Gravity Forms Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/Ninja-Forms.png",
					"int_type": "Compatible",
					"title": "Ninja Forms",
					"desc": "Easy and powerful form builder plugin "
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/Ninja-Forms-Add-ons.png",
					"int_type": "Compatible",
					"title": "Ninja Forms Add-ons",
					"desc": "Support for almost all Ninja Forms Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/WP-Fluent-Forms.png",
					"int_type": "Compatible",
					"title": "WP Fluent Forms",
					"desc": "Customizable drag-and-drop WordPress contact form plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WPForms.png",
					"int_type": "Compatible",
					"title": "WPForms",
					"desc": "Drag-and-drop WordPress form builder plugin "
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WPForms-Add-ons.png",
					"int_type": "Compatible",
					"title": "WPForms Add-ons",
					"desc": "Support for almost all WPForms Add-ons"
				},
				{
					"type": "title",
					"text": "Gamification"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/GamiPress.png",
					"int_type": "Official",
					"title": "GamiPress",
					"desc": "Gamification solution for your WordPress site"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/10/GamiPress-BuddyBoss-Integration.png",
					"int_type": "Third-party",
					"title": "GamiPress + BuddyBoss Integration",
					"desc": "Gamify your BuddyBoss community website"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/GamiPress-LifterLMS-Integration.png",
					"int_type": "Compatible",
					"title": "GamiPress + LifterLMS Integration",
					"desc": "Gamify your LifterLMS-powered online courses website"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/GamiPress-Add-Ons.png",
					"int_type": "Official",
					"title": "GamiPress Add-ons",
					"desc": "Support for almost all GamiPress Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/GamiPress-Add-Ons.png",
					"int_type": "Compatible",
					"title": "GamiPress Leaderboards",
					"desc": "Easily create, configure and add leaderboards on your website"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/myCRED.png",
					"int_type": "Compatible",
					"title": "myCRED",
					"desc": "Points management system for your WordPress site"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/myCRED-Add-Ons.png",
					"int_type": "Compatible",
					"title": "myCRED Add-ons",
					"desc": "Support for almost all myCRED Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/Rating_Widget.png",
					"int_type": "Compatible",
					"title": "RatingWidget: Star Review System",
					"desc": "Popular, GDPR compliant, Five Star Review System"
				},
				{
					"type": "title",
					"text": "Job Listings"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WP_Job_Manager.png",
					"int_type": "Official",
					"title": "WP Job Manager",
					"desc": "WordPress Job Listings Plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/WP_Job_Manager_Add-Ons.png",
					"int_type": "Compatible",
					"title": "WP Job Manager Add-ons",
					"desc": "Support for almost all WP Job Manager Add-ons"
				},
				{
					"type": "title",
					"text": "Listings"
				},
				{
					"type": "item",
					"int_type": "Third-party",
					"title": "Directorist",
					"desc": "Bring a modern directory to your online community"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/GeoDirectory-1.jpg",
					"int_type": "Compatible",
					"title": "GeoDirectory",
					"desc": "A lightweight yet rocket-fast business directory WordPress plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/GeoDirectory-Add-ons.png",
					"int_type": "Compatible",
					"title": "GeoDirectory Add-ons",
					"desc": "Support for almost all GeoDirectory Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2022/12/Spaces-Engine-624x624.png",
					"int_type": "Third-party",
					"title": "Spaces Engine",
					"desc": "All-in-One Directory Solution For Your BuddyBoss Community"
				},
				{
					"type": "title",
					"text": "Live Streaming"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2023/04/WPStream-624x351.png",
					"int_type": "Third-party",
					"title": "WP Stream",
					"desc": "Video Streaming for WordPress"
				},
				{
					"type": "title",
					"text": "LMS"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Content Cloner",
					"desc": "Clone LearnDash courses with a click of a button"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/LearnDash_Add-Ons.png",
					"int_type": "Official",
					"title": "LearnDash Course Grid",
					"desc": "Customizable course grids for LearnDash"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/LearnDash.png",
					"int_type": "Official",
					"title": "LearnDash LMS",
					"desc": "The go-to choice for people creating (and selling) online courses."
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/LearnDash_Add-Ons.png",
					"int_type": "Official",
					"title": "LearnDash LMS Add-ons",
					"desc": "Support for almost all LearnDash Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
					"int_type": "Compatible",
					"title": "LearnDash Notes",
					"desc": "On-site note taking system"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
					"int_type": "Compatible",
					"title": "LearnDash Private Sessions",
					"desc": "Provide personalized coaching to LearnDash students"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/LifterLMS.png",
					"int_type": "Official",
					"title": "LifterLMS",
					"desc": "A powerful WordPress LMS software for Experts, Coaches & Entrepreneurs"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/02/LifterLMS_AddOns.png",
					"int_type": "Compatible",
					"title": "LifterLMS Add-ons",
					"desc": "Support for almost all LifterLMS Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Multiple Instructors for LearnDash",
					"desc": "Allow users to create their own LearnDash courses"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/LearnDash.png",
					"int_type": "Compatible",
					"title": "ProPanel by LearnDash",
					"desc": "Manage your LearnDash activity"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/06/Tutor-LMS.jpg",
					"int_type": "Official",
					"title": "Tutor LMS",
					"desc": "A lightweight, robust WordPress LMS plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/LearnDash_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Uncanny LearnDash Toolkit",
					"desc": "Build better LearnDash sites"
				},
				{
					"type": "title",
					"text": "Marketing"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/08/Groundhogg.png",
					"int_type": "Third-party",
					"title": "Groundhogg",
					"desc": "A freemium marketing automation WordPress plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/08/Groundhogg-Add-Ons.png",
					"int_type": "Compatible",
					"title": "Groundhogg Add-ons",
					"desc": "Support for almost all Groundhogg Add-ons"
				},
				{
					"type": "title",
					"text": "Media Gallery"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "MediaPress Downloadable Media",
					"desc": "Let visitors/users download any media file"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/MediaPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "MediaPress Upload Terms of Service",
					"desc": "Configure terms of service agreement for uploading media on your site"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2022/04/wp-offload-media-1.png",
					"int_type": "Compatible",
					"title": "WP Offload Media",
					"desc": "With the BuddyBoss and WP Offload Media integration, you can automatically offload user-generated content uploaded by BuddyBoss users."
				},
				{
					"type": "title",
					"text": "Membership Plugins"
				},
				{
					"type": "item",
					"int_type": "Third-party",
					"title": "Digital Access Pass",
					"desc": "BuddyBoss and Digital Access Pass (DAP) integration ensures you can create an advanced Membership & Community Site."
				},
				{
					"type": "item",
					"int_type": "Third-party",
					"title": "Memberium",
					"desc": "A premium membership plugin that connects your WordPress site to Keap and ActiveCampaign"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/MemberPress.png",
					"int_type": "Official",
					"title": "MemberPress",
					"desc": "The “All-In-One” Membership Plugin for WordPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/MemberPress_Add-Ons.png",
					"int_type": "Compatible",
					"title": "MemberPress Add-ons",
					"desc": "Support for almost all MemberPress Add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/Paid_Memberships_Pro.png",
					"int_type": "Compatible",
					"title": "Paid Memberships Pro",
					"desc": "A complete membership solution WordPress site"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/Paid_Memberships_Pro_Add-Ons.png",
					"int_type": "Compatible",
					"title": "Paid Memberships Pro Add-ons",
					"desc": "Support for almost all Paid Memberships Pro add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Restrict-Content-Pro.png",
					"int_type": "Third-party",
					"title": "Restrict Content",
					"desc": "A full-featured, powerful membership solution for WordPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Restrict-Content-Pro-Add-ons.png",
					"int_type": "Compatible",
					"title": "Restrict Content Pro Add-Ons",
					"desc": "Support for almost all Restrict Content Pro add-ons"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/WishList-Member.png",
					"int_type": "Third-party",
					"title": "WishList Member",
					"desc": "Premium membership software solution"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/WishList-Member-Add-ons.png",
					"int_type": "Compatible",
					"title": "WishList Member Add-ons",
					"desc": "Support for almost all WishList Member Add-ons"
				},
				{
					"type": "title",
					"text": "Page Builder"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Element-Pack.png",
					"int_type": "Compatible",
					"title": "Element Pack for Elementor",
					"desc": "An essential add-on for Elementor Page Builder"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/11/Elementor.png",
					"int_type": "Official",
					"title": "Elementor Page Builder",
					"desc": "World’s leading WordPress page builder"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/05/Thrive-Architect.png",
					"int_type": "Compatible",
					"title": "Thrive Architect",
					"desc": "Fastest and most intuitive visual editor for WordPress"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/WPBakery_Page_Builder.png",
					"int_type": "Compatible",
					"title": "WPBakery Page Builder",
					"desc": "Drag-and-drop frontend and backend editor"
				},
				{
					"type": "title",
					"text": "Polls"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/07/AnsPress.png",
					"int_type": "Compatible",
					"title": "AnsPress",
					"desc": "A developer friendly, question and answer plugin for WordPress"
				},
				{
					"type": "title",
					"text": "Popup Builder"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Popup-Builder.png",
					"int_type": "Compatible",
					"title": "Popup Builder",
					"desc": "Create and manage unlimited promotion modal popups"
				},
				{
					"type": "title",
					"text": "Project Management"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/03/WP-Project-Manager.png",
					"int_type": "Compatible",
					"title": "WP Project Manager",
					"desc": "WordPress Project Management Plugin"
				},
				{
					"type": "title",
					"text": "SEO"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/text-logo.svg",
					"int_type": "Compatible",
					"title": "All in One SEO",
					"desc": "Optimize your WordPress site for SEO"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/Rank_Math_SEO.png",
					"int_type": "Compatible",
					"title": "Rank Math SEO",
					"desc": "A ground-breaking, free SEO plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/logo-square_purple.svg",
					"int_type": "Compatible",
					"title": "SEOPress",
					"desc": "A powerful WordPress SEO plugin"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Yoast-SEO.png",
					"int_type": "Compatible",
					"title": "Yoast SEO",
					"desc": "The favorite WordPress SEO plugin of millions of users worldwide"
				},
				{
					"type": "title",
					"text": "Social"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/Social-Media-Share-Buttons-Social-Sharing-Icons.png",
					"int_type": "Compatible",
					"title": "Social Media Share Buttons & Social Sharing Icons",
					"desc": "Add share icons for RSS, email, social media platforms and custom social buttons to your website"
				},
				{
					"type": "title",
					"text": "Support Ticketing"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2021/12/Fluent-Support.png",
					"int_type": "Third-party",
					"title": "Fluent Support",
					"desc": "A self-hosted support ticketing system with unlimited tickets, support agents, users, products, tags, and channels"
				},
				{
					"type": "title",
					"text": "Translation"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2019/12/Loco-Translate.png",
					"int_type": "Compatible",
					"title": "Loco Translate",
					"desc": "In-browser editing of WordPress translation files"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/04/TranslatePress-Multilingual.png",
					"int_type": "Compatible",
					"title": "TranslatePress - Multilingual",
					"desc": "Translate your WordPress site directly from the front-end"
				},
				{
					"type": "item",
					"logo_url": "https://www.buddyboss.com/wp-content/uploads/2020/01/WPML.jpg",
					"int_type": "Third-party",
					"title": "WPML",
					"desc": "Powerful enough for corporate sites, yet simple for blogs, easily build multilingual sites"
				}
			],
			searchQuery: null,
			category: 'all',
			searchType: 'all',
			page: 1,
		}

		

		function render( renderOptions ) {
			var itemsPerPage = 30;
			var currentPage = renderOptions.page;
			var startIndex = (currentPage - 1) * itemsPerPage;
			var endIndex = startIndex + itemsPerPage;

			// Get items for the current page
			var itemsToDisplay = renderOptions.data.slice(startIndex, endIndex);
			renderOptions.data = itemsToDisplay;

			// Link Preview Template
			var tmpl = $('#tmpl-bb-integrations').html();

			// Compile the template
			var compiled = _.template(tmpl);

			var html = compiled( renderOptions );

			if( renderOptions.previewParent ) {
				renderOptions.previewParent.html( html );
			}
		}

		render( renderOptions );
	}

	renderIntegrations();

	/* jshint ignore:end */

}());
