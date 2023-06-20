/* global BP_SEARCH */
/* jshint unused:false */
jQuery(document).ready(function($) {
	BP_SEARCH.cache = [];

	function initAutoComplete(  ) {
		var autoCompleteObjects = [];
		if (BP_SEARCH.enable_ajax_search == '1') {
			var document_height = $(document).height(),
			    bb_is_rtl       = $('body').hasClass('rtl');
			$(BP_SEARCH.autocomplete_selector).each(function() {
				var $form = $(this),
					$search_field = $form.find('input[name="s"], input[type=search]');
				if ($search_field.length > 0) {

					/**
					 * If the search input is positioned towards bottom of html document,
					 * autocomplete appearing vertically below the input isn't very effective.
					 * Lets flip it in that case.
					 */
					var ac_position_prop = {},
						input_offset = $search_field.offset(),
						input_offset_plus = input_offset.top + $search_field.outerHeight(),
						distance_from_bottom = document_height - input_offset_plus;

					//assuming 400px is good enough to display autocomplete ui
					if( distance_from_bottom < 400 && input_offset.top > distance_from_bottom ){
						//but if space available on top is even less!
						ac_position_prop = { collision: 'flip flip' };
					} else {
						ac_position_prop = { my: 'left top', at: 'left bottom', collision: 'none' };
					}

					autoCompleteObjects.push( $search_field );

					$($search_field).autocomplete({
						source: function(request, response) {

							var term = request.term;
							if (term in BP_SEARCH.cache) {
								response(BP_SEARCH.cache[ term ]);
								return;
							}

							var data = {
								'action': BP_SEARCH.action,
								'nonce': BP_SEARCH.nonce,
								'search_term': request.term,
								'per_page': BP_SEARCH.per_page
							};

							response({value: '<div class="loading-msg"><span class="bb_global_search_spinner"></span>' + BP_SEARCH.loading_msg + '</div>'});

							 $.ajax({
								url:BP_SEARCH.ajaxurl,
								dataType: 'json',
								data: data,
								success: function(data) {
									BP_SEARCH.cache[ term ] = data;
									response(data);
								}
							});
						},
						minLength: 2,
						delay: 500,
						select: function(event, ui) {
							var newLocation = $( ui.item.value ).find( 'a' ).attr( 'href' );
							if ( newLocation ) {
								window.location = newLocation;
							}

							return false;
						},
						focus: function() {
							$('.ui-autocomplete li').removeClass('ui-state-hover');
							$('.ui-autocomplete').find('li:has(a.ui-state-focus)').addClass('ui-state-hover');
							return false;
						},
						open: function() {
							$('.bp-search-ac').outerWidth($(this).outerWidth());
						},
						position: ac_position_prop
					})
					                 .data('ui-autocomplete')._renderItem = function(ul, item) {
						ul.addClass( 'bp-search-ac' );

						// Add .bp-search-ac-header if search is made from header area of the site
						if ( $form.parents('header').length != 0 ) {
							ul.addClass( 'bp-search-ac-header' );
						}

						if (item.type_label != '') {
							$(ul).data('current_cat', item.type);
							return $('<li>').attr('class', 'bbls-' + item.type + '-type bbls-category').append('<div>' + item.value + '</div>').appendTo(ul);
						} else {
							return $('<li>').attr('class', 'bbls-' + item.type + '-type bbls-sub-item').append('<a class="x">' + item.value + '</a>').appendTo(ul);
						}


						/*
						 currentCategory = "";
						 var li;
						 if ( item.type != currentType ) {
						 ul.append( "<li class='ui-autocomplete-category'>" + item.type + "</li>" );
						 currentType = item.type;
						 }
						 //li = this._renderItemData( ul, item );
						 if ( item.type ) {
						 li.attr( "aria-label", item.type + " : " + item.label );
						 }
						 */

					};
				}
			});

			if ( BP_SEARCH.forums_autocomplete ) {
				$( '#bbp-search-form, #bbp-search-index-form' ).each( function () {
					var $form = $( this ),
						$search_field = $form.find( '#bbp_search' );
					if ( $search_field.length > 0 ) {

						/**
						 * If the search input is positioned towards bottom of html document,
						 * autocomplete appearing vertically below the input isn't very effective.
						 * Lets flip it in that case.
						 */
						var ac_position_prop = {},
							input_offset = $search_field.offset(),
							input_offset_plus = input_offset.top + $search_field.outerHeight(),
							distance_from_bottom = document_height - input_offset_plus;

						if ( bb_is_rtl ) {
							ac_position_prop = { my: 'right top', at: 'right bottom', collision: 'none' };
						} else {
							ac_position_prop = { my: 'left top', at: 'left bottom', collision: 'none' };
						}

						//assuming 400px is good enough to display autocomplete ui
						if ( distance_from_bottom < 400 && input_offset.top > distance_from_bottom ) {
							ac_position_prop.collision = 'none flipfit';
						}
						
						$( $search_field ).autocomplete( {
							source: function ( request, response ) {

								var term = request.term;
								if ( term in BP_SEARCH.cache ) {
									response( BP_SEARCH.cache[ term ] );
									return;
								}

								var data = {
									'action': BP_SEARCH.action,
									'nonce': BP_SEARCH.nonce,
									'search_term': request.term,
									'forum_search_term': true,
									'per_page': 15
								};

								response( { value: '<div class="loading-msg"><span class="bb_global_search_spinner"></span><span>' + BP_SEARCH.loading_msg + '</span></div>' } );

								$.ajax( {
									url: BP_SEARCH.ajaxurl,
									dataType: 'json',
									data: data,
									success: function ( data ) {
										BP_SEARCH.cache[ term ] = data;
										response( data );
									}
								} );
							},
							minLength: 2,
							select: function ( event, ui ) {
								window.location = $( ui.item.value ).find( 'a' ).attr( 'href' );
								return false;
							},
							focus: function () {
								$( '.ui-autocomplete li' ).removeClass( 'ui-state-hover' );
								$( '.ui-autocomplete' ).find( 'li:has(a.ui-state-focus)' ).addClass( 'ui-state-hover' );
								return false;
							},
							open: function () {
								$( '.bp-search-ac' ).outerWidth( $( this ).outerWidth() );
							},
							position: ac_position_prop
						} )
							.data( 'ui-autocomplete' )._renderItem = function ( ul, item ) {
							ul.addClass( 'bp-search-ac' );

							if ( $( 'body.forum-archive' ).length ) {
								ul.addClass( 'bp-forum-search-ac-header' );
							}

							if ( $( '#bbp_search' ).length ) {
								ul.addClass( 'bp-forum-search-ac-header' );
							}

							if ( $( 'body.bbp-search.forum-search' ).length ) {
								ul.addClass( 'bp-forum-search-ac-header' );
							}

							if ( item.type_label != '' ) {
								$( ul ).data( 'current_cat', item.type );
								return $( '<li>' ).attr( 'class', 'bbls-' + item.type + '-type bbls-category' ).append( '<span class="bb-cat-title">' + item.value + '</span>' ).appendTo( ul );
							} else {
								return $( '<li>' ).attr( 'class', 'bbls-' + item.type + '-type bbls-sub-item' ).append( '<a class="x">' + item.value + '</a>' ).appendTo( ul );
							}
						};

					}
				} );
			}
		}
	}
	initAutoComplete();


	/**
	 * Add hidden input as a flag in a search form. If this hidden input exist in a search form,
	 * it'll sprint network search feature of the platform in the search query.
	 */
	$( [ BP_SEARCH.autocomplete_selector, BP_SEARCH.form_selector ].filter(Boolean).join(',') ).each(function () {
		var $form = $(this);

		if ( ! $( 'input[name="bp_search"]', $form ).length ) {
			$( '<input>' ).attr( {
				type: 'hidden',
				name: 'bp_search',
				value: '1'
			} ).appendTo( $form );
			$( '<input>' ).attr( {
				type: 'hidden',
				name: 'view',
				value: 'content'
			} ).appendTo( $form );
		}
	});
	/* ajax load */

	$(document).on('click', '.bp-search-results-wrapper .item-list-tabs li a', function(e) {
		e.preventDefault();

		var _this = this;

		$(this).addClass('loading');

		var get_page = $.post(BP_SEARCH.ajaxurl, {
			'action': BP_SEARCH.action,
			'nonce': BP_SEARCH.nonce,
			'subset': $(this).parent().data('item'),
			's': BP_SEARCH.search_term,
			'view': 'content'
		});
		get_page.done(function(d) {
			$(_this).removeClass('loading');
			if (d != '') {
				var present = $('.bp-search-page');
				present.after(d);
				present.remove();
			}
			initAutoComplete();
		});

		get_page.fail(function() {
			$(_this).removeClass('loading');
		});

		return false;

	});

	$(document).on('click', '.bp-search-results-wrapper .pagination-links a', function(e) {
		e.preventDefault();

		var _this = this;

		$(this).addClass('loading');
		var qdata = {
			'action': BP_SEARCH.action,
			'nonce': BP_SEARCH.nonce,
			'subset': $(this).parent().data('item'),
			's': BP_SEARCH.search_term,
			'view': 'content',
			'list': $(this).data('pagenumber')
		};

		var current_subset = $('.bp-search-results-wrapper .item-list-tabs li.active').data('item');
		qdata.subset = current_subset;

		var get_page = $.post(BP_SEARCH.ajaxurl, qdata);
		get_page.done(function(d) {
			$(_this).removeClass('loading');
			if (d != '') {
				var present = $('.bp-search-page');
				present.after(d);
				present.remove();
			}
		});

		get_page.fail(function() {
			$(_this).removeClass('loading');
		});

		return false;

	});

	/* end ajax load */

	$('body.bp-nouveau').on('click', '.bp-search-page button.friendship-button, .bp-search-page button.group-button', function(){
		window.location = this.getAttribute('data-bp-nonce');
	});


});

/**
 * Reset the search form value
 * 
 * @param ele
 */
function bp_ps_clear_form_elements( ele ) {
	var $form = jQuery( ele ).closest( 'form' );
	var event = new Event( 'change' );

	$form.find( ':input' ).each( function () {
		switch ( this.type ) {
			case 'password':
			case 'select-multiple':
			case 'select-one':
			case 'text':
			case 'email':
			case 'date':
			case 'url':
			case 'search':
			case 'textarea':
				jQuery( this ).val( '' );
				break;
			case 'checkbox':
			case 'radio':
				this.checked = false;
				this.dispatchEvent( event );
				break;
		}
	} );

	jQuery.removeCookie( 'bp_ps_request', {path: '/'} );
	$form.find( '.submit' ).trigger( 'click' );
}
