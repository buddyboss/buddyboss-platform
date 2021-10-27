/*jshint unused:false*/
/*jshint scripturl:true*/
function add_field () {

	var holder = document.getElementById( 'field_box' );
	var theId  = document.getElementById( 'field_next' ).value;

	var newDiv = document.createElement( 'div' );
	newDiv.setAttribute( 'id', 'field_div' + theId );
	newDiv.setAttribute( 'class', 'sortable' );

	var span = document.createElement( 'span' );
	span.setAttribute( 'class', 'bp_ps_col1' );
	span.setAttribute( 'title', window.bp_ps_strings.drag );
	span.appendChild( document.createTextNode( '\u00A0\u21C5' ) );

	var $select = jQuery( '<select>', {name: 'bp_ps_options[field_name][' + theId + ']', id: 'field_name' + theId} );
	$select.addClass( 'bp_ps_col2' );
	$select.addClass( 'new_field' );
	var $option = jQuery( '<option>', {text: window.bp_ps_strings.field, value: 0} );
	$option.appendTo( $select );
	
	jQuery.each(
		window.bp_ps_groups,
		function (i, optgroups) {
			jQuery.each(
				optgroups,
				function (groupName, options) {
					var $optgroup = jQuery( '<optgroup>', {label: groupName} );
					$optgroup.appendTo( $select );

					jQuery.each(
						options,
						function (j, option) {
							var $option = jQuery( '<option>', {text: option.name, value: option.id } );
							$option.appendTo( $optgroup );
						}
					);
					
				}
			);
		}
	);

	var toDelete = document.createElement( 'a' );
	toDelete.setAttribute( 'href', 'javascript:remove("field_div' + theId + '");' );
	toDelete.setAttribute( 'class', 'delete' );
	toDelete.appendChild( document.createTextNode( window.bp_ps_strings.remove ) );

	holder.appendChild( newDiv );
	newDiv.appendChild( span );
	newDiv.appendChild( document.createTextNode( '\n' ) );
	$select.appendTo( '#field_div' + theId );
	newDiv.appendChild( document.createTextNode( '\n' ) );
	newDiv.appendChild( toDelete );

	enableSortableFieldOptions();
	/**
	 * For dropdown value already selected then disable existing value
	 */
	disableAlreadySelectedOption();
	document.getElementById( 'field_name' + theId ).focus();
	document.getElementById( 'field_next' ).value = ++theId;
}

function remove (id) {
	var element    = document.getElementById( id );
	var count      = document.querySelectorAll( 'body.post-type-bp_ps_form #postbox-container-2 #normal-sortables #bp_ps_fields_box .inside #field_box .sortable' ).length;
	var countAfter = count - 1;
	if ( 0 === countAfter ) {
		var message = document.getElementById( 'empty-box-alert' ).value;
		window.alert( message );
		return false;
	}
	element.parentNode.removeChild( element );
}

function enableSortableFieldOptions () {
	jQuery( '.field_box' ).sortable(
		{
			items: 'div.sortable',
			tolerance: 'pointer',
			axis: 'y',
			handle: 'span'
		}
	);
}

function disableAlreadySelectedOption() {

	jQuery('#field_box select.bp_ps_col2 option').prop('disabled', false); //enable everything

    //collect the values from selected;
    var arr = jQuery.map (
	        jQuery('#field_box select.bp_ps_col2 option:selected'), function (n) {
	        	if ( n.value !== 'heading' ) {
	            	return n.value;
	        	}
	        }
        );

    //disable elements
    jQuery('#field_box select.bp_ps_col2 option').filter(function () {
    	if (jQuery(this).prop('selected') == false ){
	        return jQuery.inArray(jQuery(this).val(), arr) > -1; //if value is in the array of selected values
	    }
    }).prop('disabled', true);

    //re-enable elements
    jQuery('#field_box select.bp_ps_col2 option').filter(function () {
        return jQuery.inArray(jQuery(this).val(), arr) == -1; //if value is not in the array of selected values
    }).prop('disabled',false);
}

jQuery( document ).ready(
	function () {
		enableSortableFieldOptions();
		/**
		 * For dropdown value already selected then disable existing value When page load
		 */
		disableAlreadySelectedOption();
	}
);

jQuery( document ).ready(
	function ($) {
		$( '#template' ).change(
			function () {
				var template_spinner = $( '#bp_ps_template .spinner' );
				var save_button      = $( 'input[type=submit]' );
				var data             = {
					'action': 'template_options',
					'form': $( '#form_id' ).val(),
					'template': $( '#template option:selected' ).val()
				};

				save_button.attr( 'disabled', 'disabled' );
				template_spinner.addClass( 'is-active' );

				$.post(
					ajaxurl,
					data,
					function (new_options) {
						$( '#template_options' ).html( new_options );
						template_spinner.removeClass( 'is-active' );
						save_button.removeAttr( 'disabled' );
					}
				);
			}
		);

		jQuery( document ).on(
			'change',
			'body.post-type-bp_ps_form #postbox-container-2 #normal-sortables #bp_ps_fields_box .inside #field_box .sortable .bp_ps_col2.new_field',
			function () {
				var field_id  = this.value;
				var count     = $( 'body.post-type-bp_ps_form #postbox-container-2 #normal-sortables #bp_ps_fields_box .inside #field_next' ).val();
				var fieldData = {
					'action': 'bp_search_ajax_option',
					'field_id': field_id,
					'count': count
				};
				$.post(
					ajaxurl,
					fieldData,
					function (response) {
						var index = count - 1;
						$( 'body.post-type-bp_ps_form #postbox-container-2 #normal-sortables #bp_ps_fields_box .inside #field_box #field_div' + index ).remove();
						$( 'body.post-type-bp_ps_form #postbox-container-2 #normal-sortables #bp_ps_fields_box .inside #field_box' ).append( response );
						/**
						 * For dropdown value already selected then disable existing value
						 */
						disableAlreadySelectedOption();
					}
				);
			}
		);

		jQuery( document ).on(
			'change',
			'body.post-type-bp_ps_form #postbox-container-2 #normal-sortables #bp_ps_fields_box .inside #field_box .sortable .bp_ps_col2.existing',
			function () {
				var field_name = jQuery( this ).find( 'option:selected' ).text();
				var parent_div = jQuery( this ).parent().closest( 'div' ).attr( 'id' );
				jQuery( '#' + parent_div + ' .bp_ps_col3' ).attr( 'placeholder', field_name );
			}
		);
	}
);
