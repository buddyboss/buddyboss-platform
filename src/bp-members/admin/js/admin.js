/* global BB_Member_Admin, tinymce, quicktags */
/* exported clear */

( function ( $ ) {
	// Profile Visibility Settings.

	$( '.visibility-toggle-link' ).on(
		'click',
		function ( event ) {
			event.preventDefault();

			$( this ).attr( 'aria-expanded', 'true' ).parent().hide().siblings( '.field-visibility-settings' ).show();
		}
	);

	$( '.field-visibility-settings-close' ).on(
		'click',
		function ( event ) {
			event.preventDefault();

			$( '.visibility-toggle-link' ).attr( 'aria-expanded', 'false' );

			var settings_div     = $( this ).parent(),
				vis_setting_text = settings_div.find( 'input:checked' ).parent().text();

			settings_div.hide().siblings( '.field-visibility-settings-toggle' ).children( '.current-visibility-level' ).text( vis_setting_text ).end().show();
		}
	);

	if ( $( '.bb_admin_repeater_group' ).length > 0 ) {
		// Collapse repeater sets on page load, if there are more than one sets.
		$( '.bb_admin_repeater_group' ).each(
			function () {
				var $parentContainer = $( this );
				var groupId          = $parentContainer.attr( 'id' ).split( '-' ).pop(); // Extract group ID from the container's ID.

				// Collapse repeater sets on page load.
				var repeater_set_count = $parentContainer.find( '.repeater_group_outer' ).length;

				if ( repeater_set_count > 0 ) {
						var repeater_set_sequence = [];

						// Loop through each repeater group outer element.
						$parentContainer.find( '.repeater_group_outer' ).each(
							function () {
								var $set = $( this );
								repeater_set_sequence.push( $set.data( 'set_no' ) );

								// Hide repeater group inner if there are more than one sets.
								if ( repeater_set_count > 1 ) {
											$set.find( '.repeater_group_inner' ).hide();
								}

								var title = '';

								// Determine title based on field values.
								$set.find( '.editfield' ).each(
									function () {
										var field                   = $( this ).find( 'input[type=text], input[type=number], input[type=email], input[type=phone], input:checked, textarea, select' );
										var field_val               = field.val();
										var field_name              = field.attr( 'name' );
										var name_split              = ( field_name ? field_name.split( '_' ) : '' );
										var arrayContainsVisibility = ( name_split.indexOf( 'visibility' ) > -1 );

										if ( $.trim( field_val ) !== '' && ! arrayContainsVisibility ) {
											if ( field.is( 'select' ) ) {
												title = $.trim( field.find( 'option:selected' ).text() );
												return false;
											}
											title = $.trim( field_val );
											return false;
										}
									}
								);

								// Set title for each repeater set.
								if ( '' === title ) {
										$set.find( '.repeater_set_title' ).addClass( 'repeater_set_title_empty' ).html( BB_Member_Admin.empty_field );
								} else {
											$set.find( '.repeater_set_title' ).html( title );
								}
							}
						);

						// Append repeater set sequence input.
						$parentContainer.append( '<input type="hidden" name="repeater_set_sequence[' + groupId + ']" value="' + repeater_set_sequence.join( ',' ) + '">' );

						// Initialize sortable for each repeater group.
						$parentContainer.find( '.repeater_sets_sortable' ).sortable(
							{
								items: '.repeater_group_outer',
								update: function () {
										var repeater_set_sequence = [];
										$( this ).find( '.repeater_group_outer' ).each(
											function () {
													repeater_set_sequence.push( $( this ).data( 'set_no' ) );
											}
										);
										$parentContainer.find( '[name="repeater_set_sequence[' + groupId + ']"]' ).val( repeater_set_sequence.join( ',' ) );
								}
							}
						);
				}
			}
		);

		// Edit button.
		$( '.bb_admin_repeater_group' ).on(
			'click',
			'.repeater_group_outer .repeater_set_edit',
			function ( e ) {
				e.preventDefault();
				$( this ).closest( '.repeater_group_outer' ).find( '.repeater_group_inner' ).slideToggle();
				$( this ).parents( '.repeater_group_outer' ).toggleClass( 'active' );
			}
		);

		// Delete button.
		$( '.bb_admin_repeater_group' ).on(
			'click',
			'.repeater_group_outer .repeater_set_delete',
			function ( e ) {
				var $delete_button  = $( this );
				var parentContainer = $( this ).parents( '.bb_admin_repeater_group' ).attr( 'id' );
				var groupId         = $( '#' + parentContainer ).find( '#group' ).val();
				e.preventDefault();
				if ( $delete_button.hasClass( 'disabled' ) ) {
					return;
				}
				var deleted_field_ids          = [];
				var existing_deleted_field_ids = '';
				if ( $( '#' + parentContainer + ' [name="deleted_field_ids[' + groupId + ']"]' ).length ) {
					existing_deleted_field_ids = $( '#' + parentContainer + ' [name="deleted_field_ids[' + groupId + ']"]' ).val();
					deleted_field_ids.push( existing_deleted_field_ids );
				}
				var r = confirm( BB_Member_Admin.confirm_delete_set );
				if ( r ) {
					$delete_button.closest( '.repeater_group_outer' ).find( '.editfield' ).each(
						function () {
							var $field   = $( this );
							var field_id = $field.find( 'input,textarea,select' ).attr( 'name' );
							field_id     = ( typeof field_id !== 'undefined' ) ? field_id : $field.find( 'textarea.wp-editor-area' ).attr( 'name' );
							if ( 'undefined' !== typeof field_id ) {
									field_id = field_id.replace( 'field_', '' );
									field_id = field_id.replace( '_day', '' );
									field_id = field_id.replace( '[]', '' );
									deleted_field_ids.push( field_id );
							}
						}
					);

					// Remove field set.
					$delete_button.closest( '.repeater_group_outer' ).remove();

					// Update sorting order.
					var repeater_set_sequence = [];
					$( '#' + parentContainer + ' .repeater_group_outer' ).each(
						function () {
							repeater_set_sequence.push( $( this ).data( 'set_no' ) );
						}
					);
					$( '#' + parentContainer + ' [name="repeater_set_sequence[' + groupId + ']"]' ).val( repeater_set_sequence.join( ',' ) );

					// Remove the deleted field ids, so that it doesn't generate validation errors.
					var all_field_ids       = $( '#' + parentContainer + ' [name="field_ids[' + groupId + ']"]' ).val().split( ',' );
					var remaining_field_ids = [];
					var loop_length         = all_field_ids.length;
					var deleted_length      = deleted_field_ids.length;
					for ( var i = 0; i < loop_length; i++ ) {
						var is_deleted = false;
						for ( var j = 0; j < deleted_length; j++ ) {
							if ( all_field_ids[i] === deleted_field_ids[j] ) {
								is_deleted = true;
								break;
							}
						}

						if ( ! is_deleted ) {
							remaining_field_ids.push( all_field_ids[i] );
						}
					}

					remaining_field_ids = remaining_field_ids.join( ',' );
					$( '#' + parentContainer + ' [name="field_ids[' + groupId + ']"]' ).val( remaining_field_ids );

					// keep a record of deleted fields.
					if ( ! $( '#' + parentContainer + ' [name="deleted_field_ids[' + groupId + ']"]' ).length ) {
						$( '#' + parentContainer ).append( '<input type="hidden" name="deleted_field_ids[' + groupId + ']" >' );
					}
					$( '#' + parentContainer + ' [name="deleted_field_ids[' + groupId + ']"]' ).val( deleted_field_ids.join( ',' ) );

					// Disable the delete button if it's the only set remaining.
					if ( $( '#' + parentContainer + ' .repeater_group_outer' ).length === 1 ) {
						// $( '#profile-edit-form .repeater_group_outer .repeater_set_delete' ).addClass( 'disabled' );
					}
				}
			}
		);

		// Add repeater set button, on edit profile screens.
		$( '.bb_admin_repeater_group #btn_add_repeater_set' ).click(
			function ( e ) {
				e.preventDefault();
				var $button  = $( this );
				var groupId  = $button.data( 'group' );
				var parentId = $( '#profile-edit-form-' + groupId );

				if ( $button.hasClass( 'disabled' ) ) {
						return;
				}

				$button.addClass( 'disabled' );
				$button.attr( 'disabled', 'disabled' );
				$button.css( 'pointer-events', 'none' );

				$.ajax(
					{
						'url': ajaxurl,
						'method': 'POST',
						'data': {
							'action': 'bb_admin_xprofile_add_repeater_set',
							'_wpnonce': $button.data( 'nonce' ),
							'group': groupId,
							'user_id': $( '#user_id' ).val(),
							'set_no': parentId.find( 'input[name="repeater_set_sequence[' + groupId + ']"]' ).val(),
							'existing_field_ids': parentId.find( 'input[name="field_ids[' + groupId + ']"]' ).val(),
							'deleted_field_ids': parentId.find( 'input[name="deleted_field_ids[' + groupId + ']"]' ).val(),
						},
						'success': function ( response ) {
							if ( response.success && response.data ) {
								if ( parentId.find( '.repeater_group_outer' ).length ) {
									parentId.find( '.repeater_group_outer' ).last().after( response.data.html );
								} else {
									parentId.find( '.repeater_sets_sortable' ).html( response.data.html );
								}
								parentId.find( '[name="field_ids[' + groupId + ']"]' ).val( response.data.field_ids.join( ',' ) );
								parentId.find( '[name="repeater_set_sequence[' + groupId + ']"]' ).val( response.data.set_no.join( ',' ) );
								parentId.find( '.repeater_sets_sortable' ).sortable( 'destroy' ).sortable();
								parentId.find( '.repeater_group_outer' ).last().addClass( 'active' );
								parentId.find( '.repeater_group_outer .repeater_group_inner:last' ).show();
								$button.removeClass( 'disabled' );
								$button.removeAttr( 'disabled', 'disabled' );
								$button.css( 'pointer-events', 'initial' );

								var lastSetNo = jQuery( response.data.set_no ).get( -1 );
								jQuery( '#profile-edit-form-' + groupId + ' .repeater_group_outer[data-set_no=' + lastSetNo + '] .field_type_textarea textarea' ).each(
									function () {
										tinymce.EditorManager.execCommand( 'mceAddEditor', false, jQuery( this ).attr( 'name' ) );

										if ( typeof quicktags !== 'undefined' ) {
											quicktags( { id: jQuery( this ).attr( 'name' ) } );
										}
									}
								);
							}
						}
					}
				);
			}
		);

		/** Profile Visibility Settings *********************************/

		// Initially hide the 'field-visibility-settings' block.
		$( '.field-visibility-settings' ).addClass( 'bp-hide' );
		// Add initial aria state to button.
		$( '.visibility-toggle-link' ).attr( 'aria-expanded', 'false' );

		$( '.bb_admin_repeater_group' ).on(
			'click',
			'.repeater_group_outer .visibility-toggle-link',
			function ( e ) {
				e.preventDefault();

				$( this ).attr( 'aria-expanded', 'true' );

				$( this ).parent().addClass( 'field-visibility-settings-hide bp-hide' ).siblings( '.field-visibility-settings' ).removeClass( 'bp-hide' ).addClass( 'field-visibility-settings-open' );
			}
		);

		$( '.bb_admin_repeater_group' ).on(
			'click',
			'.repeater_group_outer .field-visibility-settings-close',
			function ( e ) {
				e.preventDefault();

				var settings_div = $( this ).parent(),
				vis_setting_text = $( '.iradio_minimal' ).length > 0 ? settings_div.find( 'input:checked' ).parent().next( '.field-visibility-text' ).text() : settings_div.find( 'input:checked' ).parent().text();

				settings_div.removeClass( 'field-visibility-settings-open' ).addClass( 'bp-hide' ).siblings( '.field-visibility-settings-toggle' ).find( '.current-visibility-level' ).text( vis_setting_text ).closest( '.field-visibility-settings-toggle' ).addClass( 'bp-show' ).removeClass( 'field-visibility-settings-hide bp-hide' );

				$( '.visibility-toggle-link' ).attr( 'aria-expanded', 'false' );
			}
		);
	}

	window.clear = function ( container ) {
		if ( ! container ) {
			return;
		}

		container = container.replace( '[', '\\[' ).replace( ']', '\\]' );

		if ( $( '#' + container + ' option' ).length ) {
			$.each(
				$( '#' + container + ' option' ),
				function ( c, option ) {
					$( option ).prop( 'selected', false );
				}
			);
		} else if ( $( '#' + container + ' [type=radio]' ).length ) {
			$.each(
				$( '#' + container + ' [type=radio]' ),
				function ( c, checkbox ) {
					$( checkbox ).prop( 'checked', false );
				}
			);
		}
	};

} )( jQuery );

/**
 * Deselects any select options or input options for the specified field element.
 *
 * @param {String} container HTML ID of the field
 * @since BuddyPress 1.0.0
 */
function clear( container ) {
	container = document.getElementById( container );
	if ( ! container ) {
		return;
	}

	var radioButtons   = container.getElementsByTagName( 'INPUT' ),
		options        = container.getElementsByTagName( 'OPTION' ),
		i              = 0,
		radio_length   = radioButtons.length,
		options_length = options.length;

	if ( radioButtons ) {
		for ( i = 0; i < radio_length; i++ ) {
			radioButtons[i].checked = '';
		}
	}

	if ( options ) {
		for ( i = 0; i < options_length; i++ ) {
			options[i].selected = false;
		}
	}
}
