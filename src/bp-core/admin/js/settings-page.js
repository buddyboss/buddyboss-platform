/* global BP_ADMIN */
(function() {
	var $ = jQuery.noConflict();

	$(function() {
		$('[data-run-js-condition]').each(function() {
			var id = $(this).data('run-js-condition');
			var tag= $(this).prop('tagName');

			$(this).on('change.run-condition', function() {
				if (tag == 'SELECT' && $(this).is(':visible')) {
					var selector = id + '-' + $(this).val();
					$('[class*="js-show-on-' + id + '-"]:not(.js-show-on-' + selector + ')').hide();
					$('.js-show-on-' + selector).show().find('[data-run-js-condition]').trigger('change.run-condition');
					return true;
				}

				var checked = $(this).prop('checked');
				if (checked && $(this).is(':visible')) {
					$('.js-hide-on-' + id).hide();
					$('.js-show-on-' + id).show().find('[data-run-js-condition]').trigger('change.run-condition');
				} else {
					$('.js-hide-on-' + id).show();
					$('.js-show-on-' + id).hide().find('[data-run-js-condition]').trigger('change.run-condition');
				}
			}).trigger('change.run-condition');
		});

		var bpPages = $('body.buddypress.buddyboss_page_bp-pages .bp-admin-card').length;
		if ( bpPages > 1 ) {
			$('.create-background-page').click(function () {
				var dataPage = $(this).attr('data-name');

				$.ajax({
					'url' : BP_ADMIN.ajax_url,
					'method' : 'POST',
					'data' : {
						'action' : 'bp_core_admin_create_background_page',
						'page' : dataPage
					},
					'success' : function( response ) {
						window.location.href = response.data.url;
					}
				});
			});
		}

		// Auto check parent search type
		$( '.bp-search-child-field' ).on( 'click', 'input[type="checkbox"]', function( e ) {
			var $parentRow = $( e.delegateTarget ).prevAll( '.bp-search-parent-field' ).first();
			if ( $parentRow.length && e.currentTarget.checked ) {
				$parentRow.find( 'input[type="checkbox"]' ).prop( 'checked', true );
			}
		} );

		// Auto uncheck child search types
		$( '.bp-search-parent-field' ).on( 'click', 'input[type="checkbox"]', function( e ) {
			var $childRows = $( e.delegateTarget ).nextUntil( '.bp-search-parent-field' );
			if ( $childRows.length && ! e.currentTarget.checked ) {
				$childRows.find( 'input[type="checkbox"]' ).prop( 'checked', false );
			}
		} );
	});

	$( document ).ready(function() {
		var menuOpen = $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu #toplevel_page_buddyboss-platform ul.wp-submenu li');

		// Set Groups selected on Group Type post types.
		if ( $('body.buddypress.post-type-bp-group-type').length ) {
			var selectorGroups = $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-groups"]');
			$(menuOpen).removeClass('current');
			$(selectorGroups).addClass('current');
			$(selectorGroups).attr('aria-current','page');
			$('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li').find('a[href*="bp-groups"]').parent().addClass('current');
		}

		// Set Emails selected on email templates post types.
		if ( $('body.buddypress.post-type-bp-email').length ) {
			var selectorEmails = $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-email"]');
			$(menuOpen).removeClass('current');
			$(selectorEmails).addClass('current');
			$(selectorEmails).attr('aria-current','page');
			$('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li').find('a[href*="bp-email"]').parent().addClass('current');
		}

		// Set Forums selected on Reply post types.
		if ( $('body.buddypress.post-type-topic').length ) {
			var selector = $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="post_type=forum"]');
			$(selector).addClass('current');
			$(selector).attr('aria-current','page');
			$('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li').find('a[href*="post_type=forum"]').parent().addClass('current');
		}

		// Set Forums selected on Reply post types.
		if ( $('body.buddypress.post-type-reply').length ) {
			var selectorReply = $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="post_type=forum"]');
			$(selectorReply).addClass('current');
			$(selectorReply).attr('aria-current','page');
			$('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li').find('a[href*="post_type=forum"]').parent().addClass('current');
		}

		// Set Profile selected on Profile Type post types.
		if ( $('body.buddypress.post-type-bp-member-type').length ) {
			var selectorProfileTypes = $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-profile-setup"]');
			$(menuOpen).removeClass('current');
			$(selectorProfileTypes).addClass('current');
			$(selectorProfileTypes).attr('aria-current','page');
			$('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li').find('a[href*="bp-profile-setup"]').parent().addClass('current');
		}

		// Set Profile selected on Profile Search post types.
		if ( $('body.buddypress.post-type-bp_ps_form').length ) {
			var selectorProfileSearch = $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-profile-setup"]');
			$(menuOpen).removeClass('current');
			$(selectorProfileSearch).addClass('current');
			$(selectorProfileSearch).attr('aria-current','page');
			$('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li').find('a[href*="bp-profile-setup"]').parent().addClass('current');
		}

		// Set Tools selected on Repair Forums Page.
		if ( $('body.buddypress.buddyboss_page_bbp-repair').length ) {
			var selectorForumRepair= $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-tools"]');
			$(menuOpen).removeClass('current');
			$(selectorForumRepair).addClass('current');
			$(selectorForumRepair).attr('aria-current','page');
			$('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li').find('a[href*="bp-tools"]').parent().addClass('current');
		}

		// Set Tools selected on Import Forums Page.
		if ( $('body.buddypress.buddyboss_page_bbp-converter').length ) {
			var selectorForumImport= $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-tools"]');
			$(menuOpen).removeClass('current');
			$(selectorForumImport).addClass('current');
			$(selectorForumImport).attr('aria-current','page');
			$('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li').find('a[href*="bp-tools"]').parent().addClass('current');
		}

		// Set Tools selected on Import Profile Types Page.
		if ( $('body.buddypress.buddyboss_page_bp-member-type-import').length ) {
			var selectorProfileImport= $('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li a[href*="bp-tools"]');
			$(menuOpen).removeClass('current');
			$(selectorProfileImport).addClass('current');
			$(selectorProfileImport).attr('aria-current','page');
			$('#wpwrap #adminmenumain #adminmenuwrap #adminmenu .toplevel_page_buddyboss-platform ul.wp-submenu-wrap li').find('a[href*="bp-tools"]').parent().addClass('current');
		}

		if ( $('body .section-bp_search_settings_community').length ) {

			if ($('body .section-bp_search_settings_community table td input:checkbox:checked').length === $('body .section-bp_search_settings_community table td input:checkbox').length) {
				$('#bp_search_select_all_components').prop( 'checked', true );
			}

			$('#bp_search_select_all_components').click(function () {
				var table = $('body .section-bp_search_settings_community table');
				$('td input:checkbox', table).prop('checked', this.checked);
			});

			$('body .section-bp_search_settings_community table td input:checkbox').click(function () {
				if ($('body .section-bp_search_settings_community table td input:checkbox:checked').length === $('body .section-bp_search_settings_community table td input:checkbox').length) {
					$('#bp_search_select_all_components').prop( 'checked', true );
				} else {
					$('#bp_search_select_all_components').prop( 'checked', false );
				}
			});
		}

		if ( $('body .section-bp_search_settings_post_types').length ) {

			if ($('body .section-bp_search_settings_post_types table td input:checkbox:checked').length === $('body .section-bp_search_settings_post_types table td input:checkbox').length) {
				$('#bp_search_select_all_post_types').prop( 'checked', true );
			}

			$('#bp_search_select_all_post_types').click(function () {
				var table = $('body .section-bp_search_settings_post_types table');
				$('td input:checkbox', table).prop('checked', this.checked);
			});

			$('body .section-bp_search_settings_post_types table td input:checkbox').click(function () {
				if ($('body .section-bp_search_settings_post_types table td input:checkbox:checked').length === $('body .section-bp_search_settings_post_types table td input:checkbox').length) {
					$('#bp_search_select_all_post_types').prop( 'checked', true );
				} else {
					$('#bp_search_select_all_post_types').prop( 'checked', false );
				}
			});
		}

		if ( $('.buddyboss_page_bp-activity').length ) {
			$(document).on('click', '.activity-attached-gif-container', function () {
				var video = $(this).find('video').get(0),
					$button = $(this).find('.gif-play-button');
				if (video.paused == true) {
					// Play the video
					video.play();

					// Update the button text to 'Pause'
					$button.hide();
				} else {
					// Pause the video
					video.pause();

					// Update the button text to 'Play'
					$button.show();
				}
			} );
		}

		/**
		 * Check if the Dashboard BB help page
		 */
		if ( $('body.buddyboss_page_bp-help').length ) {

			/**
			 * Show sub menu when user click on main menu
			 */
			$( '.bp-help-card-grid' ).on('click', 'span.open', function () {

				$( this ).closest( '.main' ).find( 'ul:first' ).toggle();
			});

			/**
			 * show the closest UI
			 */
			$( '.bp-help-card-grid li.selected' ).closest( 'ul' ).show();
		}

	});
}());



