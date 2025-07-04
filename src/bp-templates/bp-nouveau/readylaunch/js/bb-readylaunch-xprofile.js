/* global bp, BP_Nouveau */
/* @version 3.0.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

    // Collapse repeater sets on page load, if there are more than one sets
    var repeaterGroupOuter = $( '#profile-edit-form .repeater_group_outer' );
    var repeater_set_count = repeaterGroupOuter.length;
    window.deleteFieldEvent = false;

    if ( repeater_set_count > 0 ) {
        var repeater_set_sequence = [];
        repeaterGroupOuter.each( function(){
            var $set = $(this);
            repeater_set_sequence.push( $set.data('set_no') );

            if ( repeater_set_count > 1 ) {
                $set.find( '.repeater_group_inner' ).hide();
            }

            var title = '';
            $set.find('.editfield').each( function(){
                var field = $(this).find( 'input[type=text],input[type=number],input[type=email],input[type=phone],input:checked,textarea,select' );
                var field_val = field.val();
                var field_name = field.attr('name' );
				var name_split = ( field_name ? field_name.split('_') : '' );
				var arrayContainsVisibility = (name_split.indexOf('visibility') > -1);
                if ( $.trim( field_val ) !== '' && ! arrayContainsVisibility ) {
                    if ( field.is( 'select' ) ) {
                        title = $.trim( field.find('option:selected').text() );
                        return false;
                    }
                    title = $.trim( field_val );
                    return false;
                }
            });

            if ( '' === title ) {
				$set.find('.repeater_set_title').addClass( 'repeater_set_title_empty' );
				$set.find('.repeater_set_title').html( BP_Nouveau.empty_field );
			} else {
				$set.find('.repeater_set_title').html( title );
			}

        });

        $( '#profile-edit-form' ).append( '<input type="hidden" name="repeater_set_sequence" value="'+ repeater_set_sequence.join(',') +'">' );

        // Sortable
        $( '.repeater_sets_sortable' ).sortable({
            items: '.repeater_group_outer',
            update: function( ) {
                var repeater_set_sequence = [];
                $( '#profile-edit-form .repeater_group_outer' ).each( function(){
                    repeater_set_sequence.push( $(this).data('set_no') );
                });
                $( '#profile-edit-form [name="repeater_set_sequence"]' ).val( repeater_set_sequence.join(',') );
            }
        });
    }

    // Edit button
    $( '#profile-edit-form .repeater_group_outer .repeater_set_edit' ).click( function(e){
        e.preventDefault();
        $(this).closest('.repeater_group_outer').find('.repeater_group_inner').slideToggle();
        $(this).parents('.repeater_group_outer').toggleClass('active');
    });


    if (window.location.href.indexOf('#bpxpro') > -1) {
        repeaterGroupOuter.last().find('.repeater_group_inner').slideToggle();
        repeaterGroupOuter.last().toggleClass('active');
    }

	var deleted_field_ids = [];

    // Delete button
    $( '#profile-edit-form .repeater_group_outer .repeater_set_delete' ).click( function(e){
        var $delete_button = $(this);
        var $form = $delete_button.closest( '#profile-edit-form' );
        e.preventDefault();
        if ( $delete_button.hasClass( 'disabled' ) ) {
            return;
        }

        var r = confirm( BP_Nouveau.confirm_delete_set );
        if ( r ) {
	        $delete_button.closest( '.repeater_group_outer' ).find( '.editfield' ).each( function () {
		        var $field = $( this );
		        var field_id = $field.find( 'input,textarea,select' ).attr( 'name' );
		        field_id = ( typeof field_id !== 'undefined' ) ? field_id : $field.find( 'textarea.wp-editor-area' ).attr( 'name' );
		        if ( 'undefined' !== typeof field_id ) {
			        field_id = field_id.replace( 'field_', '' );
			        field_id = field_id.replace( '_day', '' );
			        field_id = field_id.replace( '[]', '' );
			        deleted_field_ids.push( field_id );
		        }
	        } );

            // Remove field set
            $delete_button.closest( '.repeater_group_outer' ).remove();

            // Update sorting order
            var repeater_set_sequence = [],
                repeaterGroupOuter = $( '#profile-edit-form .repeater_group_outer' );
            repeaterGroupOuter.each( function(){
                repeater_set_sequence.push( $(this).data('set_no') );
            });
            $( '#profile-edit-form [name="repeater_set_sequence"]' ).val( repeater_set_sequence.join(',') );

            // Remove the deleted field ids, so that it doesn't generate validation errors.
            var $fieldIds = $( '#profile-edit-form [name="field_ids"]' );
            var all_field_ids = $fieldIds.val().split( ',' );
            var remaining_field_ids = [];
            for ( var i =0; i < all_field_ids.length; i++ ) {
                var is_deleted = false;
                for ( var j = 0; j < deleted_field_ids.length; j++ ) {
                    if ( all_field_ids[ i ] === deleted_field_ids[ j ] ) {
                        is_deleted = true;
                        break;
                    }
                }

                if ( !is_deleted ) {
                    remaining_field_ids.push( all_field_ids[ i ] );
                }
            }

            remaining_field_ids = remaining_field_ids.join( ',' );

            $fieldIds.val( remaining_field_ids );

            //keep a record of deleted fields.
            var $deletedFieldIds = $( '#profile-edit-form [name="deleted_field_ids"]' );
            if ( ! $deletedFieldIds.length ) {
                $form.append( '<input type="hidden" name="deleted_field_ids" >' );
            }
            $deletedFieldIds.val( deleted_field_ids.join( ',' ) );

            // Disable the delete button if it's the only set remaining
            if ( repeaterGroupOuter.length === 1 ) {
                //$( '#profile-edit-form .repeater_group_outer .repeater_set_delete' ).addClass( 'disabled' );
            }

            // Save the form
            $form.submit();
            window.deleteFieldEvent = true;
        }
    });

    // Disable the delete button if it's the only set
    if ( repeater_set_count === 1 ) {
        //$( '#profile-edit-form .repeater_group_outer .repeater_set_delete' ).addClass( 'disabled' );
    }
    // Remove attr from button after page successfully load.
    if ( window.location.href.indexOf('#bpxpro') > 0 ) {
        document.addEventListener('DOMContentLoaded', function () {
            var $addRepeaterSetButton = $( '#profile-edit-form #btn_add_repeater_set' );
            $addRepeaterSetButton.removeAttr('disabled');
            $addRepeaterSetButton.css('pointer-events', 'auto');
        });
    }

    // Add repeater set button, on edit profile screens
    $( '#profile-edit-form #btn_add_repeater_set' ).click( function(e) {
        e.preventDefault();
        var $button = $(this);

        if ( $button.hasClass( 'disabled' ) ) {
            return;
        }

        $button.addClass('disabled');
        $button.attr('disabled', 'disabled');
        $button.css('pointer-events', 'none');

        $.ajax({
            'url' : ajaxurl,
            'method' : 'POST',
            'data' : {
                'action' : 'bp_xprofile_add_repeater_set',
                '_wpnonce' : $button.data('nonce'),
                'group' : $button.data('group')
            },
            'success' : function() {
                //$button.closest('form').submit();
                history.pushState('', document.title, window.location.pathname);
                window.location.href += '#bpxpro';
                window.location.reload();
            }
        });
    });

	/**
	 * This an ugly copy from Legacy's buddypress.js for now
	 *
	 * This really needs to be improved !
	 */

	/** Profile Visibility Settings *********************************/
    // Change visibility status when radio button changes
    $( '.field-visibility-toggle-action' ).on( 'click', function( event ) {
		event.preventDefault();

		var $visibilityBlock = $( this ).closest( '.bb-rl-field-visibility-block' );
        var $visibilitySettings = $visibilityBlock.find( '.field-visibility-settings' );

        $visibilitySettings.show();
	} );

    $( document ).on( 'click', function( event ) {
        var $visibilityCheck = $( '.field-visibility-settings:visible' );
        if (
            ! $(event.target).closest('.bb-rl-field-visibility-block').length &&
            $visibilityCheck.length
        ) {
            $visibilityCheck.hide();
        }
    } );

    $( '.field-visibility-settings input[type="radio"]' ).on( 'change', function() {
        var $radio = $( this );
        var settings_div = $radio.closest( '.field-visibility-settings' );
        var vis_setting_text = $( '.iradio_minimal' ).length > 0 ? 
            $radio.parent().next( '.field-visibility-text' ).text() : 
            $radio.parent().text();

        // Update the visibility text
        settings_div.siblings( '.field-visibility-settings-toggle' )
            .find( '.current-visibility-level' )
            .text( vis_setting_text );
            
        settings_div.hide();
    } );

	$( '#profile-edit-form input:not(:submit), #profile-edit-form textarea, #profile-edit-form select, #signup_form input:not(:submit), #signup_form textarea, #signup_form select' ).change( function() {
		var shouldconfirm = true;
        $( '#profile-edit-form #btn_add_repeater_set' ).addClass('disabled');

		$( '#profile-edit-form input:submit, #signup_form input:submit' ).on( 'click', function() {
			shouldconfirm = false;
            $( '#profile-edit-form #btn_add_repeater_set' ).removeClass('disabled');
		} );

		window.onbeforeunload = function() {
			if ( shouldconfirm && !window.deleteFieldEvent ) {
				return BP_Nouveau.unsaved_changes;
			}
		};
	} );

	window.clear = function( container ) {
		if ( ! container ) {
			return;
		}

		container = container.replace( '[', '\\[' ).replace( ']', '\\]' );

		var $containerOptions = $( '#' + container + ' option' ),
		    $containerRadios  = $( '#' + container + ' [type=radio]' );
		if ( $containerOptions.length ) {
			$.each( $containerOptions, function ( c, option ) {
				$( option ).prop( 'selected', false );
			} );
		} else if ( $containerRadios.length ) {
			$.each( $containerRadios, function ( c, checkbox ) {
				$( checkbox ).prop( 'checked', false );
			} );
		}
	};
} )( bp, jQuery );
