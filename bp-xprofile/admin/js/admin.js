/* exported add_option, show_options, hide, fixHelper */
/* jshint scripturl: true */
/* global XProfileAdmin, ajaxurl */

/**
 * Add option for the forWhat type.
 *
 * @param {string} forWhat Value of the field to show options for
 */
function add_option(forWhat) {
	var holder       = document.getElementById( forWhat + '_more' ),
		theId        = document.getElementById( forWhat + '_option_number' ).value,
		newDiv       = document.createElement( 'div' ),
		grabber      = document.createElement( 'span' ),
		newOption    = ( 'socialnetworks' !== forWhat ) ? document.createElement( 'input' ) : document.createElement( 'select' ),
		label        = document.createElement( 'label' ),
		isDefault    = document.createElement( 'input' ),
		txt1         = document.createTextNode( 'Default Value' ),
		toDeleteText = document.createTextNode( 'Delete' ),
		toDeleteWrap = document.createElement( 'div' ),
		s			 = 0,
		toDelete     = document.createElement( 'a' );

	newDiv.setAttribute( 'id', forWhat + '_div' + theId );
	newDiv.setAttribute( 'class', 'bp-option sortable' );

	grabber.setAttribute( 'class', 'bp-option-icon grabber' );

	if ( 'gender' === forWhat ) {
		newOption.setAttribute( 'type', 'text' );
		newOption.setAttribute( 'name', forWhat + '_option[' + theId + '_other]' );
		newOption.setAttribute( 'id', forWhat + '_option' + theId );
		var order_field = document.createElement( 'input' );
		order_field.setAttribute( 'type', 'hidden' );
		order_field.setAttribute( 'name', forWhat + '-option-order[]' );
		newDiv.appendChild( order_field );
	} else if ( 'socialnetworks' === forWhat ) {
		newOption.setAttribute( 'name', forWhat + '_option[' + theId + ']' );
		newOption.setAttribute( 'id', forWhat + '_option' + theId );
		newOption.setAttribute( 'class', 'select-social-networks' );
		var option_default  = document.createElement( 'option' );
		option_default.text = 'Select';
		option_default.setAttribute( 'value', '' );
		newOption.add( option_default );
		for ( s = 0; s < XProfileAdmin.social_networks_provider.length; s++ ) {
			var option  = document.createElement( 'option' );
			option.text = XProfileAdmin.social_networks_provider[s];
			option.setAttribute( 'value', XProfileAdmin.social_networks_provider_value[s] );
			newOption.add( option );
		}
	} else {
		newOption.setAttribute( 'type', 'text' );
		newOption.setAttribute( 'name', forWhat + '_option[' + theId + ']' );
		newOption.setAttribute( 'id', forWhat + '_option' + theId );
	}

	if ( forWhat === 'checkbox' || forWhat === 'multiselectbox' ) {
		isDefault.setAttribute( 'type', 'checkbox' );
		isDefault.setAttribute( 'name', 'isDefault_' + forWhat + '_option[' + theId + ']' );
	} else if ( 'gender' !== forWhat ) {
		isDefault.setAttribute( 'type', 'radio' );
		isDefault.setAttribute( 'name', 'isDefault_' + forWhat + '_option' );
	}

	isDefault.setAttribute( 'value', theId );
	if ( 'gender' === forWhat ) {
		toDelete.setAttribute('href', 'javascript:remove_div("' + forWhat + '_div' + theId + '")');
	} else {
		toDelete.setAttribute('href', 'javascript:hide("' + forWhat + '_div' + theId + '")');
	}
	toDelete.setAttribute( 'class', 'delete' );
	toDelete.appendChild( toDeleteText );

	toDeleteWrap.setAttribute( 'class', 'delete-button' );
	toDeleteWrap.appendChild( toDelete );

	if ( 'gender' === forWhat ) {
		txt1 = document.createTextNode( ' Other' );
		label.appendChild( txt1 );
	} else if ( 'socialnetworks' === forWhat ) {

	} else {
		label.appendChild( document.createTextNode( ' ' ) );
		label.appendChild( isDefault );
		label.appendChild( document.createTextNode( ' ' ) );
		label.appendChild( txt1 );
		label.appendChild( document.createTextNode( ' ' ) );
	}

	newDiv.appendChild( grabber );
	newDiv.appendChild( document.createTextNode( ' ' ) );
	newDiv.appendChild( newOption );
	if ( 'socialnetworks' !== forWhat ) {
		newDiv.appendChild( label );
	}
	newDiv.appendChild( toDeleteWrap );
	holder.appendChild( newDiv );

	// re-initialize the sortable ui
	enableSortableFieldOptions( forWhat );

	// set focus on newly created element
	document.getElementById( forWhat + '_option' + theId ).focus();

	if ( 'socialnetworks' === forWhat && theId === XProfileAdmin.social_networks_provider_count ) {
		jQuery( '.social_networks_add_more' ).hide();
	}

	theId++;

	document.getElementById( forWhat + '_option_number' ).value = theId;
}

/**
 * Hide all "options" sections, and show the options section for the forWhat type.
 *
 * @param {string} forWhat Value of the field to show options for
 */
function show_options( forWhat ) {
	var do_autolink, i, xprofileSaveButton;

	xprofileSaveButton = jQuery( 'body #wpwrap #wpcontent #wpbody #wpbody-content .wrap #bp-xprofile-add-field #poststuff #post-body .postbox-container #submitdiv .inside #submitcomment #major-publishing-actions #publishing-action :input[type="submit"]' );

	if ( forWhat === 'gender' ) {
		xprofileSaveButton.prop( 'disabled', true );
		jQuery.ajax(
			{
				url : ajaxurl,
				type : 'post',
				data : {
					action : 'xprofile_check_gender_added_previously',
					type   : 'gender',
					referer: jQuery( '#bp-xprofile-add-field' ).find( 'input[name="_wp_http_referer"]' ).val()
				},
				success : function( response ) {
					var result = jQuery.parseJSON( response );

					if ( 'added' === result.status ) {
						alert( result.message );
						jQuery( '#fieldtype' ).val( '' );
						jQuery( '#fieldtype' ).val( 'textbox' );
						forWhat = 'textbox';
						for ( i = 0; i < XProfileAdmin.do_settings_section_field_types.length; i++ ) {
							document.getElementById( XProfileAdmin.do_settings_section_field_types[i] ).style.display = 'none';
						}

						if ( XProfileAdmin.do_settings_section_field_types.indexOf( forWhat ) >= 0 ) {
							document.getElementById( forWhat ).style.display = '';
							do_autolink                                      = 'on';
						} else {
							jQuery( '#do-autolink' ).val( '' );
							do_autolink = '';
						}

						// Only overwrite the do_autolink setting if no setting is saved in the database.
						if ( '' === XProfileAdmin.do_autolink ) {
							jQuery( '#do-autolink' ).val( do_autolink );
						}

						jQuery( document ).trigger( 'bp-xprofile-show-options', forWhat );
					} else {
						for ( i = 0; i < XProfileAdmin.do_settings_section_field_types.length; i++ ) {
							document.getElementById( XProfileAdmin.do_settings_section_field_types[i] ).style.display = 'none';
						}

						if ( XProfileAdmin.do_settings_section_field_types.indexOf( forWhat ) >= 0 ) {
							document.getElementById( forWhat ).style.display = '';
							do_autolink                                      = 'on';
						} else {
							jQuery( '#do-autolink' ).val( '' );
							do_autolink = '';
						}

						// Only overwrite the do_autolink setting if no setting is saved in the database.
						if ( '' === XProfileAdmin.do_autolink ) {
							jQuery( '#do-autolink' ).val( do_autolink );
						}

						jQuery( document ).trigger( 'bp-xprofile-show-options', forWhat );
					}
					xprofileSaveButton.prop( 'disabled', false );
				},
				error:function () {
					xprofileSaveButton.prop( 'disabled', false );
				}
			}
		);
	} else if ( forWhat === 'membertypes' ) {
		xprofileSaveButton.prop( 'disabled', true );
		jQuery.ajax(
			{
				url : ajaxurl,
				type : 'post',
				data : {
					action : 'xprofile_check_member_type_added_previously',
					type   : 'membertypes',
					referer: jQuery( '#bp-xprofile-add-field' ).find( 'input[name="_wp_http_referer"]' ).val()
				},
				success : function( response ) {
					var result = jQuery.parseJSON( response );

					if ( 'added' === result.status ) {
						alert( result.message );
						jQuery( '#fieldtype' ).val( '' );
						jQuery( '#fieldtype' ).val( 'textbox' );
						forWhat = 'textbox';
						for ( i = 0; i < XProfileAdmin.do_settings_section_field_types.length; i++ ) {
							document.getElementById( XProfileAdmin.do_settings_section_field_types[i] ).style.display = 'none';
						}

						if ( XProfileAdmin.do_settings_section_field_types.indexOf( forWhat ) >= 0 ) {
							document.getElementById( forWhat ).style.display = '';
							do_autolink                                      = 'on';
						} else {
							jQuery( '#do-autolink' ).val( '' );
							do_autolink = '';
						}

						// Only overwrite the do_autolink setting if no setting is saved in the database.
						if ( '' === XProfileAdmin.do_autolink ) {
							jQuery( '#do-autolink' ).val( do_autolink );
						}

						jQuery( document ).trigger( 'bp-xprofile-show-options', forWhat );
					} else {
						for ( i = 0; i < XProfileAdmin.do_settings_section_field_types.length; i++ ) {
							document.getElementById( XProfileAdmin.do_settings_section_field_types[i] ).style.display = 'none';
						}

						if ( XProfileAdmin.do_settings_section_field_types.indexOf( forWhat ) >= 0 ) {
							document.getElementById( forWhat ).style.display = '';
							do_autolink                                      = 'on';
						} else {
							jQuery( '#do-autolink' ).val( '' );
							do_autolink = '';
						}

						// Only overwrite the do_autolink setting if no setting is saved in the database.
						if ( '' === XProfileAdmin.do_autolink ) {
							jQuery( '#do-autolink' ).val( do_autolink );
						}
						jQuery( document ).trigger( 'bp-xprofile-show-options', forWhat );
					}
					xprofileSaveButton.prop( 'disabled', false );
				},
				error:function () {
					xprofileSaveButton.prop( 'disabled', false );
				}
			}
		);
	} else if ( forWhat === 'socialnetworks' ) {
		xprofileSaveButton.prop( 'disabled', true );
		jQuery.ajax(
			{
				url : ajaxurl,
				type : 'post',
				data : {
					action : 'xprofile_check_social_networks_added_previously',
					type   : 'socialnetworks',
					referer: jQuery( '#bp-xprofile-add-field' ).find( 'input[name="_wp_http_referer"]' ).val()
				},
				success : function( response ) {
					var result = jQuery.parseJSON( response );

					if ( 'added' === result.status ) {
						alert( result.message );
						jQuery( '#fieldtype' ).val( '' );
						jQuery( '#fieldtype' ).val( 'textbox' );
						forWhat = 'textbox';
						for ( i = 0; i < XProfileAdmin.do_settings_section_field_types.length; i++ ) {
							document.getElementById( XProfileAdmin.do_settings_section_field_types[i] ).style.display = 'none';
						}

						if ( XProfileAdmin.do_settings_section_field_types.indexOf( forWhat ) >= 0 ) {
							document.getElementById( forWhat ).style.display = '';
							do_autolink                                      = 'on';
						} else {
							jQuery( '#do-autolink' ).val( '' );
							do_autolink = '';
						}

						// Only overwrite the do_autolink setting if no setting is saved in the database.
						if ( '' === XProfileAdmin.do_autolink ) {
							jQuery( '#do-autolink' ).val( do_autolink );
						}

						jQuery( document ).trigger( 'bp-xprofile-show-options', forWhat );
					} else {
						for ( i = 0; i < XProfileAdmin.do_settings_section_field_types.length; i++ ) {
							document.getElementById( XProfileAdmin.do_settings_section_field_types[i] ).style.display = 'none';
						}

						if ( XProfileAdmin.do_settings_section_field_types.indexOf( forWhat ) >= 0 ) {
							document.getElementById( forWhat ).style.display = '';
							do_autolink                                      = 'on';
						} else {
							jQuery( '#do-autolink' ).val( '' );
							do_autolink = '';
						}

						// Only overwrite the do_autolink setting if no setting is saved in the database.
						if ( '' === XProfileAdmin.do_autolink ) {
							jQuery( '#do-autolink' ).val( do_autolink );
						}
						jQuery( document ).trigger( 'bp-xprofile-show-options', forWhat );
					}
					xprofileSaveButton.prop( 'disabled', false );
				},
				error:function () {
					xprofileSaveButton.prop( 'disabled', false );
				}
			}
		);
	} else {
		for ( i = 0; i < XProfileAdmin.do_settings_section_field_types.length; i++ ) {
			document.getElementById( XProfileAdmin.do_settings_section_field_types[i] ).style.display = 'none';
		}

		if ( XProfileAdmin.do_settings_section_field_types.indexOf( forWhat ) >= 0 ) {
			document.getElementById( forWhat ).style.display = '';
			do_autolink                                      = 'on';
		} else {
			jQuery( '#do-autolink' ).val( '' );
			do_autolink = '';
		}

		// Only overwrite the do_autolink setting if no setting is saved in the database.
		if ( '' === XProfileAdmin.do_autolink ) {
			jQuery( '#do-autolink' ).val( do_autolink );
		}

		jQuery( document ).trigger( 'bp-xprofile-show-options', forWhat );
	}
}

function hide( id ) {
	if ( ! document.getElementById( id ) ) {
		return false;
	}

	document.getElementById( id ).style.display = 'none';
	// the field id is [fieldtype]option[iterator] and not [fieldtype]div[iterator]
	var field_id                              = id.replace( 'div', 'option' );
	document.getElementById( field_id ).value = '';
}

// ignoring this because it is used as javascript attribute and added via code.
/* jshint ignore:start */
function remove_div( id ) {
	if ( ! document.getElementById( id ) ) {
		return false;
	}

	document.getElementById( id ).remove();
}
/* jshint ignore:end */

/**
 * @summary Toggles "no profile type" notice.
 *
 * @since BuddyPress 2.4.0
 */
function toggle_no_member_type_notice() {
	var $member_type_checkboxes = jQuery( 'input.member-type-selector' );

	// No checkboxes? Nothing to do.
	if ( ! $member_type_checkboxes.length ) {
		return;
	}

	var has_checked = false;
	$member_type_checkboxes.each(
		function() {
			if ( jQuery( this ).is( ':checked' ) ) {
				  has_checked = true;
				  return false;
			}
		}
	);

	if ( has_checked ) {
		jQuery( 'p.member-type-none-notice' ).addClass( 'hide' );
	} else {
		jQuery( 'p.member-type-none-notice' ).removeClass( 'hide' );
	}
}

var fixHelper = function(e, ui) {
	ui.children().each(
		function() {
			jQuery( this ).width( jQuery( this ).width() );
		}
	);
	return ui;
};

function enableSortableFieldOptions() {
	jQuery( '.bp-options-box' ).sortable(
		{
			cursor: 'move',
			items: 'div.sortable',
			tolerance: 'intersect',
			axis: 'y'
		}
	);

	jQuery( '.sortable, .sortable span' ).css( 'cursor', 'move' );
}

function destroySortableFieldOptions() {
	jQuery( '.bp-options-box' ).sortable( 'destroy' );
	jQuery( '.sortable, .sortable span' ).css( 'cursor', 'default' );
}

function titleHint( id ) {
	id = id || 'title';

	var title = jQuery( '#' + id ), titleprompt = jQuery( '#' + id + '-prompt-text' );

	if ( '' === title.val() ) {
		titleprompt.removeClass( 'screen-reader-text' );
	} else {
		titleprompt.addClass( 'screen-reader-text' );
	}

	titleprompt.click(
		function(){
			jQuery( this ).addClass( 'screen-reader-text' );
			title.focus();
		}
	);

	title.blur(
		function(){
			if ( '' === this.value ) {
				titleprompt.removeClass( 'screen-reader-text' );
			}
		}
	).focus(
		function(){
				titleprompt.addClass( 'screen-reader-text' );
		}
	).keydown(
		function(e){
				titleprompt.addClass( 'screen-reader-text' );
				jQuery( this ).unbind( e );
		}
	);
}

function sortFieldOptions( sortElem ){
	var valArray   = [];
	var sortOrder  = sortElem.val();
	var parentElem = sortElem.closest( '.bp-options-box' );
	parentElem.find( 'input[type="text"]' ).each(
		function(){
			valArray.push( jQuery( this ).val() );
		}
	);
	if (sortOrder === 'asc') {
		valArray.sort();
	} else if (sortOrder === 'desc') {
		valArray.sort();
		valArray.reverse();
	}
	parentElem.find( 'input[type="text"]' ).each(
		function( index ){
			jQuery( this ).val( valArray[index] );
		}
	);
}

jQuery( document ).ready(
	function() {

			// Set focus in Field Title, if we're on the right page
			jQuery( '#bp-xprofile-add-field #title' ).focus();

			// Set up the notice that shows when no profile types are selected for a field.
			toggle_no_member_type_notice();
			jQuery( 'input.member-type-selector' ).on(
				'change',
				function() {
					toggle_no_member_type_notice();
				}
			);

			// Set up deleting options ajax
			jQuery( 'a.ajax-option-delete' ).on(
				'click',
				function() {
					var theId = this.id.split( '-' );
					theId     = theId[1];

					jQuery.post(
						ajaxurl,
						{
							action: 'xprofile_delete_option',
							'cookie': encodeURIComponent( document.cookie ),
							'_wpnonce': jQuery( 'input#_wpnonce' ).val(),
							'option_id': theId
						},
						function() {}
					);
				}
			);

			// Set up the sort order change actions
			jQuery( '[id^="sort_order_"]' ).change(
				function() {
					if ( jQuery( this ).val() !== 'custom' ) {
						if (jQuery( '.sortable, .sortable span' ).attr( 'cursor' ) === 'move') {
							destroySortableFieldOptions();
						}
						sortFieldOptions( jQuery( this ) );
					} else {
						 enableSortableFieldOptions( jQuery( '#fieldtype :selected' ).val() );
					}
				}
			);

			// Show object if JS is enabled
			jQuery( 'ul#field-group-tabs' ).show();

			// Allow reordering of field group tabs
			jQuery( 'ul#field-group-tabs' ).sortable(
				{
					cursor: 'move',
					axis: 'x,y',
					opacity: 1,
					items: 'li',
					tolerance: 'intersect',

					update: function() {
						jQuery.post(
							ajaxurl,
							{
								action: 'xprofile_reorder_groups',
								'cookie': encodeURIComponent( document.cookie ),
								'_wpnonce_reorder_groups': jQuery( 'input#_wpnonce_reorder_groups' ).val(),
								'group_order': jQuery( this ).sortable( 'serialize' )
							  },
							function() {}
						);
					}
				}
			).disableSelection();

			// Allow reordering of fields within groups
			jQuery( 'fieldset.field-group' ).sortable(
				{
					cursor: 'move',
					opacity: 0.7,
					items: 'fieldset.sortable',
					tolerance: 'pointer',

					update: function() {
						jQuery.post(
							ajaxurl,
							{
								action: 'xprofile_reorder_fields',
								'cookie': encodeURIComponent( document.cookie ),
								'_wpnonce_reorder_fields': jQuery( 'input#_wpnonce_reorder_fields' ).val(),
								'field_order': jQuery( this ).sortable( 'serialize' ),
								'field_group_id': jQuery( this ).attr( 'id' )
							},
							function() {}
						);
					}
				}
			)

			// Disallow text selection
			.disableSelection();

			// Allow reordering of field options
			enableSortableFieldOptions( jQuery( '#fieldtype :selected' ).val() );

			// Handle title placeholder text the WordPress way
			titleHint( 'title' );

			// On Date fields, selecting a date_format radio button should change the Custom value.
			var $date_format              = jQuery( 'input[name="date_format"]' );
			var $date_format_custom_value = jQuery( '#date-format-custom-value' );
			var $date_format_sample       = jQuery( '#date-format-custom-sample' );
			$date_format.click(
				function( e ) {
					switch ( e.target.value ) {
						case 'elapsed' :
							$date_format_custom_value.val( '' );
							$date_format_sample.html( '' );
						  break;

						case 'custom' :
						  break;

						default :
							$date_format_custom_value.val( e.target.value );
							$date_format_sample.html( jQuery( e.target ).siblings( '.date-format-label' ).html() );
						  break;
					}
				}
			);

			// Clicking into the custom date format field should select the Custom radio button.
			var $date_format_custom = jQuery( '#date-format-custom' );
			$date_format_custom_value.focus(
				function() {
					$date_format_custom.prop( 'checked', 'checked' );
				}
			);

			// Validate custom date field.
			var $date_format_spinner = jQuery( '#date-format-custom-spinner' );
			$date_format_custom_value.change(
				function( e ) {
					$date_format_spinner.addClass( 'is-active' );
					jQuery.post(
						ajaxurl,
						{
							action: 'date_format',
							date: e.target.value
						},
						function( response ) {
							$date_format_spinner.removeClass( 'is-active' );
							$date_format_sample.html( response );
						}
					);
				}
			);

			// tabs init with a custom tab template and an "add" callback filling in the content
			var $tab_items,
			$tabs = jQuery( '#tabs' ).tabs();

			set_tab_items( $tabs );

		function set_tab_items( $tabs ) {
			$tab_items = jQuery( 'ul:first li', $tabs ).droppable(
				{
					accept: '.connectedSortable fieldset:not(.primary_field)',
					hoverClass: 'ui-state-hover',
					activeClass: 'ui-state-acceptable',
					touch: 'pointer',
					tolerance: 'pointer',

					// When field is dropped on tab
					drop: function( ev, ui ) {
							var $item = jQuery( this ), // The tab
							$list     = jQuery( $item.find( 'a' ).attr( 'href' ) ).find( '.connectedSortable' ); // The tab body

							// Remove helper class
							jQuery( $item ).removeClass( 'drop-candidate' );

							// Hide field, change selected tab, and show new placement
							ui.draggable.hide(
								'slow',
								function() {

									// Select new tab as current
									$tabs.tabs( 'option', 'active', $tab_items.index( $item ) );

									// Show new placement
									jQuery( this ).appendTo( $list ).show( 'slow' ).animate( {opacity: '1'}, 500 );

									// Refresh $list variable
									$list = jQuery( $item.find( 'a' ).attr( 'href' ) ).find( '.connectedSortable' );
									jQuery( $list ).find( 'p.nofields' ).hide( 'slow' );

									// Ajax update field locations and orders
									jQuery.post(
										ajaxurl,
										{
											action: 'xprofile_reorder_fields',
											'cookie': encodeURIComponent( document.cookie ),
											'_wpnonce_reorder_fields': jQuery( 'input#_wpnonce_reorder_fields' ).val(),
											'field_order': jQuery( $list ).sortable( 'serialize' ),
											'field_group_id': jQuery( $list ).attr( 'id' )
										},
										function() {}
									);
								}
							);
					},
					over: function() {
						jQuery( this ).addClass( 'drop-candidate' );
					},
					out: function() {
						jQuery( this ).removeClass( 'drop-candidate' );
					}
					}
			);
		}

			jQuery( '.delete-profile-field-group' ).click(
				function( ){
					return confirm( XProfileAdmin.confirm_delete_field_group );
				}
			);

			jQuery( '.bb-delete-profile-field' ).click(
				function( ){
					return confirm( XProfileAdmin.confirm_delete_field );
				}
			);

			jQuery( document ).on(
				'change',
				'.select-social-networks',
				function() {
					var new_value      = this;
					var new_id         = this.id;
					var selected_value = this.value;
					jQuery( '.select-social-networks' ).each(
						function() {
							if ( new_id !== this.id ) {
								var existing_value = jQuery( '#' + this.id ).val();
								if (selected_value === existing_value) {
									new_value.value = '';
									alert( XProfileAdmin.social_networks_duplicate_value_message );
								}
							}
						}
					);
				}
			);
	}
);
