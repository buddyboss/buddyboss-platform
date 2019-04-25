/* global _, BP_Customizer */
/* @version 3.0.0 */
window.wp = window.wp || {};

( function( wp, $ ) {

	if ( 'undefined' === typeof wp.customize ) {
		return;
	}

	wp.customize.bind( 'ready', function() {
		var groupFrontPage = wp.customize.control( 'group_front_page' );

		// If the Main Group setting is disabled, hide all others
		if ( 'undefined' !== typeof groupFrontPage ) {
			$( groupFrontPage.selector ).on( 'click', 'input[type=checkbox]', function( event ) {
				var checked = $( event.currentTarget ).prop( 'checked' ), controller = $( event.delegateTarget ).prop( 'id' );

				_.each( wp.customize.section( 'bp_nouveau_group_front_page' ).controls(), function( control ) {
					if ( control.selector !== '#' + controller ) {
						if ( true === checked ) {
							$( control.selector ).show();
						} else {
							$( control.selector ).hide();
						}
					}
				} );
			} );
		}

		// Show hide "Use tab styling for primary nav" & "Use tab styling for secondary nav" based on the "Display the profile navigation vertically" option.
		wp.customize.control('user_nav_display', function (control) {
			/**
			 * Run function on setting change of control.
			 */
			control.setting.bind(function (value) {
				switch (value) {
					/**
					 * The select was switched to the hide option.
					 */
					case false:
						/**
						 * Deactivate the conditional control.
						 */
						wp.customize.control('user_subnav_tabs').deactivate();
						wp.customize.control('user_nav_tabs').activate();
						break;
					/**
					 * The select was switched to »show«.
					 */
					case true:
						/**
						 * Activate the conditional control.
						 */
						wp.customize.control('user_nav_tabs').deactivate();
						wp.customize.control('user_subnav_tabs').activate();
						break;
				}
			});
		});

		// Show hide "Use tab styling for primary navigation" & "Use tab styling for secondary navigation" based on the "Display the group navigation vertically" option.
		wp.customize.control('group_nav_display', function (control) {
			/**
			 * Run function on setting change of control.
			 */
			control.setting.bind(function (value) {
				switch (value) {
					/**
					 * The select was switched to the hide option.
					 */
					case false:
						/**
						 * Deactivate the conditional control.
						 */
						wp.customize.control('group_subnav_tabs').deactivate();
						wp.customize.control('group_nav_tabs').activate();
						break;
					/**
					 * The select was switched to »show«.
					 */
					case true:
						/**
						 * Activate the conditional control.
						 */
						wp.customize.control('group_nav_tabs').deactivate();
						wp.customize.control('group_subnav_tabs').activate();
						break;
				}
			});
		});

		$( 'ul#customize-control-group_nav_order, ul#customize-control-user_nav_order' ).sortable( {
			cursor    : 'move',
			axis      : 'y',
			opacity   : 1,
			items     : 'li:not(.ui-sortable-disabled)',
			tolerance : 'intersect',

			update: function() {
				var order = [];

				$( this ).find( '[data-bp-nav]' ).each( function( s, slug ) {
					order.push( $( slug ).data( 'bp-nav' ) );
				} );

				if ( order.length ) {
					$( '#bp_item_' + $( this ).data( 'bp-type' ) ).val( order.join() ).trigger( 'change' );
				}
			}
		} ).disableSelection();

		$( '#accordion-section-bp_nouveau_mail > h3' ).off( 'click' );
		$( '#accordion-section-bp_nouveau_mail' ).on( 'click', function() {
			location.replace( BP_Customizer.emailCustomizerUrl );
		} );

		$( '#sub-accordion-panel-bp_mailtpl' ).on( 'click', '.customize-panel-back', function() {
			location.replace( BP_Customizer.platformCustomizerUrl );
		} );
	} );

} )( window.wp, jQuery );
