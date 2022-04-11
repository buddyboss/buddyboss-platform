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

		$(document).on('click', '.visible-checkboxes', function () {
			var hide = [];
			var finder = 'ul.customize-control-' + $(this).data('bp-which-type') + '_nav_order [data-bp-nav]';
			$(document).find(finder).each(function () {
				if ($(this).find('.visible-checkboxes').is(':checked')) {
					hide.push($(this).find('.visible-checkboxes').data('bp-hide'));
				}
			});
			$('#bp_item_' + $(this).data('bp-which-type') + '_hide').val(hide.join()).trigger('change');
		});

		// Show/Hide checkbox based on the Profile Navigation Order.
		$( document ).on( 'change', '#_customize-input-user_default_tab', function() {
			var currentValue = $( this ).val();
			if ( 'media' === currentValue ) {
				currentValue = 'photos';
			} else if ( 'document' === currentValue ) {
				currentValue = 'documents';
			}
			$( document ).find( 'ul#customize-control-user_nav_order li .checkbox-wrap' ).removeClass( 'bp-hide');
			if ( $( document ).find( 'ul#customize-control-user_nav_order li.' + currentValue + ' .checkbox-wrap' ).find( '.visible-checkboxes').is(':checked') ) {
				$( document ).find( 'ul#customize-control-user_nav_order li.' + currentValue + ' .checkbox-wrap' ).find( '.visible-checkboxes').trigger( 'click' );
			}
			$( document ).find( 'ul#customize-control-user_nav_order li.' + currentValue + ' .checkbox-wrap' ).addClass( 'bp-hide');
		});

		// Show/Hide checkbox based on the Group Navigation Order.
		$( document ).on( 'change', '#_customize-input-group_default_tab', function() {
			var currentValue = $( this ).val();

			$( document ).find( 'ul#customize-control-group_nav_order li .checkbox-wrap' ).removeClass( 'bp-hide');
			if ( $( document ).find( 'ul#customize-control-group_nav_order li.' + currentValue + ' .checkbox-wrap' ).find( '.visible-checkboxes').is(':checked') ) {
				$( document ).find( 'ul#customize-control-group_nav_order li.' + currentValue + ' .checkbox-wrap' ).find( '.visible-checkboxes').trigger( 'click' );
			}
			$( document ).find( 'ul#customize-control-group_nav_order li.' + currentValue + ' .checkbox-wrap' ).addClass( 'bp-hide');
		});

		$( '#accordion-section-bp_nouveau_mail > h3' ).off( 'click' );
		$( '#accordion-section-bp_nouveau_mail' ).on( 'click', function() {
			location.replace( BP_Customizer.emailCustomizerUrl );
		} );

		$( '#sub-accordion-panel-bp_mailtpl' ).on( 'click', '.customize-panel-back', function() {
			location.replace( BP_Customizer.platformCustomizerUrl );
		} );


		$( 'ul#customize-control-profile-header' ).sortable( {
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
					$( '#bp_user_profile_actions_order' ).val( order.join() ).trigger( 'change' );
				}

			}
		} ).disableSelection();

	} );


} )( window.wp, jQuery );
