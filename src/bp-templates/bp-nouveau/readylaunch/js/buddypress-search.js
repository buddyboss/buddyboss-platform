/* global BP_SEARCH, bbReadyLaunchFront */
/* jshint unused:false */
jQuery( document ).ready(
	function ( $ ) {
		BP_SEARCH.cache         = [];
		var autoCompleteObjects = [];
		var currentAjaxRequest  = null; // Track the current AJAX request.

		function initAutoComplete() {
			if ( BP_SEARCH.enable_ajax_search == '1' ) {
				var document_height = $( document ).height(),
				bb_is_rtl           = $( 'body' ).hasClass( 'rtl' );
				$( BP_SEARCH.rl_autocomplete_selector ).each(
					function () {
						var $form     = $( this ),
						$search_field = $form.find( 'input[name="s"], input[type=search]' );
						if ( $search_field.length > 0 ) {

								/**
								 * If the search input is positioned towards bottom of html document,
								 * autocomplete appearing vertically below the input isn't very effective.
								 * Lets flip it in that case.
								 */
								var ac_position_prop = {},
							input_offset             = $search_field.offset(),
							input_offset_plus        = input_offset.top + $search_field.outerHeight(),
							distance_from_bottom     = document_height - input_offset_plus;

								// assuming 400px is good enough to display autocomplete ui.
							if ( distance_from_bottom < 400 && input_offset.top > distance_from_bottom ) {
								// but if space available on top is even less!
								ac_position_prop = { collision: 'flip flip' };
							} else {
								ac_position_prop = { my: 'left top', at: 'left bottom', collision: 'none' };
							}

							autoCompleteObjects.push( $search_field );

							// Create a container for the autocomplete results.
							var $resultsContainer = $search_field.parents( 'form' ).find( '.bb-rl-search-results-container' );

							$( $search_field ).autocomplete(
								{
									source: function ( request, response ) {
										$form.find( '.bb-rl-network-search-clear' ).removeClass( 'bp-hide' );
										$form.find( '.ui-autocomplete' ).removeClass( 'bp-hide' );

										var term = request.term;
										if ( term in BP_SEARCH.cache ) {
											response( BP_SEARCH.cache[ term ] );
											return;
										}

										// Abort any previous AJAX request.
										if (currentAjaxRequest) {
											currentAjaxRequest.abort();
										}

										var data = {
											'action': BP_SEARCH.action,
											'nonce': BP_SEARCH.nonce,
											'search_term': request.term,
											'per_page': BP_SEARCH.per_page,
											'subset': $form.find( 'select[name="subset"]' ).val() || '',
										};

										response( { value: '<div class="loading-msg"><span class="bb_global_search_spinner"></span>' + BP_SEARCH.loading_msg + '</div>' } );

										// Store the current AJAX request.
										currentAjaxRequest = $.ajax(
											{
												url: BP_SEARCH.ajaxurl,
												dataType: 'json',
												data: data,
												success: function ( data ) {
													BP_SEARCH.cache[ term ] = data;
													response( data );
													currentAjaxRequest = null; // Clear the reference when done.
												},
												error: function (xhr, status, error) {
													// Only show error if it's not an abort.
													if ( status !== 'abort' ) {
														console.error( 'Search AJAX error:', error );
													}
													currentAjaxRequest = null; // Clear the reference on error.
												}
											}
										);
									},
									minLength: 2,
									delay: 500,
									select: function ( event, ui ) {
										var newLocation = $( ui.item.value ).find( 'a' ).attr( 'href' );
										if ( newLocation ) {
											window.location = newLocation;
										}

										return false;
									},
									focus: function ( event, ui ) {
										event.preventDefault();
										$( '.ui-autocomplete li' ).removeClass( 'ui-state-hover' );
										$( '.ui-autocomplete' ).find( 'li:has(a.ui-state-focus)' ).addClass( 'ui-state-hover' );
										return false;
									},
									open: function () {
										$( '.bp-search-ac' ).outerWidth( $( this ).outerWidth() );
									},
									appendTo: $resultsContainer, // Append results to our custom container.
									position: {
										my: 'left top',
										at: 'left bottom',
										of: $search_field,
										collision: 'flip'
									}
								}
							).data( 'ui-autocomplete' )._renderItem = function ( ul, item ) {
								ul.addClass( 'bp-search-ac' );

								// Add .bp-search-ac-header if search is made from header area of the site.
								if ( $form.parents( 'header' ).length != 0 ) {
									ul.addClass( 'bp-search-ac-header' );
								}

								if ( item.type_label != '' ) {
									$( ul ).data( 'current_cat', item.type );
									return $( '<li>' ).attr( 'class', 'bbls-' + item.type + '-type bbls-category' ).append( '<div>' + item.value + '</div>' ).appendTo( ul );
								} else {
									return $( '<li>' ).attr( 'class', 'bbls-' + item.type + '-type bbls-sub-item' ).append( item.value ).appendTo( ul );
								}
							};

							$( $search_field ).on(
								'focus click',
								function () {
									$( this ).autocomplete( 'search', this.value );
								}
							);

						}
					}
				);
			}
		}

		initAutoComplete();

		/**
		 * Add hidden input as a flag in a search form. If this hidden input exist in a search form,
		 * it'll sprint network search feature of the platform in the search query.
		 */
		$( [ BP_SEARCH.rl_autocomplete_selector ].filter( Boolean ).join( ',' ) ).each(
			function () {
				var $form = $( this );

				if ( ! $( 'input[name="bp_search"]', $form ).length ) {
					$( '<input>' ).attr(
						{
							type: 'hidden',
							name: 'bp_search',
							value: '1',
						}
					).appendTo( $form );
					$( '<input>' ).attr(
						{
							type: 'hidden',
							name: 'view',
							value: 'content',
						}
					).appendTo( $form );
				}

				// Add event listener for subset select box changes.
				$form.find( 'select[name="subset"]' ).on(
					'change',
					function () {
						var $searchField = $form.find( 'input[name="s"].ui-autocomplete-input, input[type=search].ui-autocomplete-input' );

						// Check if the search field exists and has autocomplete initialized.
						if ( $searchField.length > 0 && $searchField.data( 'ui-autocomplete' ) ) {
							var min_length = $searchField.autocomplete( 'option', 'minLength' );

							if ( $searchField.val().length >= min_length ) {
								// Clear the cache for the current search term to force a new search.
								var currentTerm = $searchField.val();
								delete BP_SEARCH.cache[ currentTerm ];

								// Trigger the autocomplete search.
								$searchField.autocomplete( 'search', currentTerm );
							}
						}
					}
				);
			}
		);
		/* ajax load */

		// Close autocomplete suggestions when clear button is clicked.
		$( document ).on(
			'click',
			'.bb-rl-network-search-clear',
			function ( e ) {
				e.preventDefault();
				var $this        = $( e.currentTarget );
				var $searchForm  = $this.closest( '#search-form' );
				var $searchInput = $searchForm.find( '#search' );

				// Clear the search input.
				$searchInput.val( '' );

				// Reset the filter to 'All'.
				var $filterLabel = $searchForm.find( '.search-filter-label' );
				if ( $filterLabel.length ) {
					$filterLabel.text( bbReadyLaunchFront.filter_all );
				}

				// Focus back on the search input.
				$searchInput.focus();

				if ( $searchForm.find( '.ui-autocomplete' ).length ) {
					$searchForm.find( '.ui-autocomplete' ).addClass( 'bp-hide' );
				}

				// Hide the clear button.
				$this.addClass( 'bp-hide' );
			}
		);

		$( document ).on(
			'click',
			'.bb-rl-network-search-subnav .search-nav li a',
			function ( e ) {
				e.preventDefault();

				var _this = this;

				$( this ).addClass( 'loading' );

				var get_page = $.post(
					BP_SEARCH.ajaxurl,
					{
						'action': BP_SEARCH.action,
						'nonce': BP_SEARCH.nonce,
						'subset': $( this ).parent().data( 'item' ),
						's': BP_SEARCH.search_term,
						'view': 'content',
					}
				);
				get_page.done(
					function ( d ) {
						$( _this ).removeClass( 'loading' );
						if ( d != '' ) {
								var present = $( '.bp-search-page' );
								present.after( d );
								present.remove();
						}
						$( '.bb-rl-network-search-subnav .search-nav li' ).removeClass( 'active current selected' );
						$( _this ).parent( 'li' ).addClass( 'active current' );
						initAutoComplete();
					}
				);

				get_page.fail(
					function () {
						$( _this ).removeClass( 'loading' );
					}
				);

				return false;

			}
		);

		$( document ).on(
			'click',
			'.bp-search-results-wrapper .pagination-links a',
			function ( e ) {
				e.preventDefault();

				var _this = this;

				$( this ).addClass( 'loading' );
				var qdata = {
					'action': BP_SEARCH.action,
					'nonce': BP_SEARCH.nonce,
					'subset': $( this ).parent().data( 'item' ),
					's': BP_SEARCH.search_term,
					'view': 'content',
					'list': $( this ).data( 'pagenumber' ),
				};

				var current_subset = $( '.bb-rl-network-search-subnav .search-nav li.active' ).data( 'item' );
				qdata.subset       = current_subset;

				var get_page = $.post( BP_SEARCH.ajaxurl, qdata );
				get_page.done(
					function ( d ) {
						$( _this ).removeClass( 'loading' );
						if ( d != '' ) {
								var present = $( '.bp-search-page' );
								present.after( d );
								present.remove();
						}
					}
				);

				get_page.fail(
					function () {
						$( _this ).removeClass( 'loading' );
					}
				);

				return false;

			}
		);

		$( document ).on(
			'keydown',
			function ( e ) {
				if ( 'Escape' === e.key || 27 === e.keyCode ) {
					$( '#bb-rl-network-search-modal' ).addClass( 'bp-hide' );
				}
			}
		);

		/* end ajax load */

		$( 'body.bp-nouveau' ).on(
			'click',
			'.bp-search-page button.friendship-button, .bp-search-page button.group-button',
			function () {
				window.location = this.getAttribute( 'data-bp-nonce' );
			}
		);

	}
);

/**
 * Reset the search form value
 *
 * @param ele
 */
function bp_ps_clear_form_elements( ele ) {
	var $form = jQuery( ele ).closest( 'form' );
	var event = new Event( 'change' );

	$form.find( ':input' ).each(
		function () {
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
		}
	);

	jQuery.removeCookie( 'bp_ps_request', { path: '/' } );
	$form.find( '.submit' ).trigger( 'click' );
}
