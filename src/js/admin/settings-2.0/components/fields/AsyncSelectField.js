/**
 * BuddyBoss Admin Settings 2.0 - Async Select Field Component
 *
 * Searchable select with server-side search and load-more pagination.
 *
 * - On open: loads the first page of options (browse mode, no term).
 * - On type: debounced search resets to page 1 and fetches matching results.
 * - Load more: appends the next page of results for the current term.
 * - On mount: if a selected value exists, resolves its label via selected_id.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ajaxFetch } from '../../utils/ajax';

/**
 * Debounce delay in ms for the search input.
 *
 * @since BuddyBoss [BBVERSION]
 * @type {number}
 */
var SEARCH_DEBOUNCE_MS = 300;

/**
 * Async Select Field component.
 *
 * Renders a text input that shows a dropdown of server-fetched options.
 * Supports browsing (no search term), search, and load-more pagination.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props
 * @param {string}   props.value        Current selected value (string ID).
 * @param {Function} props.onChange     Called with new value when selection changes.
 * @param {string}   props.asyncAction  WP AJAX action name for fetching options.
 * @param {string}   props.placeholder  Input placeholder text.
 * @param {boolean}  props.disabled     Whether the field is disabled.
 * @return {WPElement} Rendered component.
 */
export function AsyncSelectField( { value, onChange, asyncAction, placeholder, disabled } ) {
	// Display label for the currently selected value.
	var selectedLabelState = useState( '' );
	var selectedLabel = selectedLabelState[ 0 ];
	var setSelectedLabel = selectedLabelState[ 1 ];

	// Search input text (separate from selected label).
	var searchState = useState( '' );
	var search = searchState[ 0 ];
	var setSearch = searchState[ 1 ];

	// Whether the dropdown is open.
	var openState = useState( false );
	var isOpen = openState[ 0 ];
	var setIsOpen = openState[ 1 ];

	// Fetched results array.
	var resultsState = useState( [] );
	var results = resultsState[ 0 ];
	var setResults = resultsState[ 1 ];

	// Whether more results are available.
	var hasMoreState = useState( false );
	var hasMore = hasMoreState[ 0 ];
	var setHasMore = hasMoreState[ 1 ];

	// Current pagination page.
	var pageState = useState( 1 );
	var page = pageState[ 0 ];
	var setPage = pageState[ 1 ];

	// Loading states.
	var loadingState = useState( false );
	var isLoading = loadingState[ 0 ];
	var setIsLoading = loadingState[ 1 ];

	var loadingMoreState = useState( false );
	var isLoadingMore = loadingMoreState[ 0 ];
	var setIsLoadingMore = loadingMoreState[ 1 ];

	// Abort controller ref for cancelling stale requests.
	var abortRef = useRef( null );

	// Debounce timer ref.
	var debounceRef = useRef( null );

	// Wrapper ref for click-outside detection.
	var wrapperRef = useRef( null );

	/**
	 * Fetch a page of results from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string}  term      Search term (empty = browse).
	 * @param {number}  fetchPage Page number to fetch.
	 * @param {boolean} append    Whether to append to existing results.
	 */
	var fetchResults = useCallback(
		function ( term, fetchPage, append ) {
			// Cancel any in-flight request.
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			abortRef.current = new AbortController();

			if ( append ) {
				setIsLoadingMore( true );
			} else {
				setIsLoading( true );
				if ( ! append ) {
					setResults( [] );
				}
			}

			ajaxFetch(
				asyncAction,
				{ term: term, page: fetchPage },
				{ signal: abortRef.current.signal }
			)
				.then( function ( response ) {
					if ( ! response.success ) {
						return;
					}
					var data = response.data;
					if ( append ) {
						setResults( function ( prev ) {
							return prev.concat( data.results || [] );
						} );
					} else {
						setResults( data.results || [] );
					}
					setHasMore( !! data.has_more );
				} )
				.catch( function ( err ) {
					// Ignore aborted requests.
					if ( err && 'AbortError' === err.name ) {
						return;
					}
				} )
				.finally( function () {
					setIsLoading( false );
					setIsLoadingMore( false );
				} );
		},
		[ asyncAction ]
	);

	// On mount: resolve the label for the current value if one exists.
	useEffect(
		function () {
			if ( ! value || '0' === String( value ) ) {
				setSelectedLabel( '' );
				return;
			}

			var controller = new AbortController();

			ajaxFetch(
				asyncAction,
				{ selected_id: value, page: 1, term: '' },
				{ signal: controller.signal }
			)
				.then( function ( response ) {
					if ( response.success && response.data.results && response.data.results.length ) {
						setSelectedLabel( response.data.results[ 0 ].label );
					}
				} )
				.catch( function () {} );

			return function () {
				controller.abort();
			};
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[] // Run once on mount only.
	);

	// Close dropdown on click outside.
	useEffect(
		function () {
			function handleClickOutside( e ) {
				if ( wrapperRef.current && ! wrapperRef.current.contains( e.target ) ) {
					setIsOpen( false );
					// Restore display label if user clicked away without selecting.
					setSearch( '' );
				}
			}
			document.addEventListener( 'mousedown', handleClickOutside );
			return function () {
				document.removeEventListener( 'mousedown', handleClickOutside );
			};
		},
		[]
	);

	// Cleanup abort controller on unmount.
	useEffect(
		function () {
			return function () {
				if ( abortRef.current ) {
					abortRef.current.abort();
				}
				if ( debounceRef.current ) {
					clearTimeout( debounceRef.current );
				}
			};
		},
		[]
	);

	/**
	 * Handle input focus — open dropdown and load first page.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleFocus() {
		if ( disabled ) {
			return;
		}
		setIsOpen( true );
		setSearch( '' );
		setPage( 1 );
		fetchResults( '', 1, false );
	}

	/**
	 * Handle search input change — debounced search.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e Input change event.
	 */
	function handleSearchChange( e ) {
		var term = e.target.value;
		setSearch( term );
		setPage( 1 );

		if ( debounceRef.current ) {
			clearTimeout( debounceRef.current );
		}

		debounceRef.current = setTimeout( function () {
			fetchResults( term, 1, false );
		}, SEARCH_DEBOUNCE_MS );
	}

	/**
	 * Handle selecting an option from the dropdown.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} option Selected option { value, label }.
	 */
	function handleSelect( option ) {
		onChange( option.value );
		setSelectedLabel( option.label );
		setSearch( '' );
		setIsOpen( false );
	}

	/**
	 * Handle "Load more" button click — fetch next page and append.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleLoadMore() {
		var nextPage = page + 1;
		setPage( nextPage );
		fetchResults( search, nextPage, true );
	}

	// Display value: show search term while typing, otherwise selected label.
	var displayValue = isOpen ? search : ( selectedLabel || '' );

	return (
		<div
			className="bb-async-select"
			ref={ wrapperRef }
		>
			<div className="bb-async-select__input-wrapper">
				<input
					type="text"
					className="bb-async-select__input"
					value={ displayValue }
					placeholder={ selectedLabel || placeholder || __( 'Search…', 'buddyboss' ) }
					onFocus={ handleFocus }
					onChange={ handleSearchChange }
					disabled={ disabled }
					autoComplete="off"
				/>
				{ value && '0' !== String( value ) && (
					<button
						type="button"
						className="bb-async-select__clear"
						onClick={ function () {
							onChange( '0' );
							setSelectedLabel( '' );
							setSearch( '' );
							setIsOpen( false );
						} }
						aria-label={ __( 'Clear selection', 'buddyboss' ) }
					>
						&#x2715;
					</button>
				) }
			</div>

			{ isOpen && (
				<div className="bb-async-select__dropdown">
					{ isLoading && (
						<div className="bb-async-select__status">
							{ __( 'Loading…', 'buddyboss' ) }
						</div>
					) }

					{ ! isLoading && results.length === 0 && (
						<div className="bb-async-select__status">
							{ search
								? __( 'No forums found.', 'buddyboss' )
								: __( 'No forums available.', 'buddyboss' )
							}
						</div>
					) }

					{ ! isLoading && results.length > 0 && (
						<ul className="bb-async-select__list" role="listbox">
							{ results.map( function ( option ) {
								return (
									<li
										key={ option.value }
										role="option"
										aria-selected={ option.value === String( value ) }
										className={
											'bb-async-select__option' +
											( option.value === String( value )
												? ' is-selected'
												: '' )
										}
									>
										<button
											type="button"
											onMouseDown={ function ( e ) {
												// Use mousedown to fire before input blur.
												e.preventDefault();
												handleSelect( option );
											} }
										>
											{ option.label }
										</button>
									</li>
								);
							} ) }
						</ul>
					) }

					{ ! isLoading && hasMore && (
						<div className="bb-async-select__load-more">
							<button
								type="button"
								className="bb-async-select__load-more-btn"
								onClick={ handleLoadMore }
								disabled={ isLoadingMore }
							>
								{ isLoadingMore
									? __( 'Loading…', 'buddyboss' )
									: __( 'Load more', 'buddyboss' )
								}
							</button>
						</div>
					) }
				</div>
			) }
		</div>
	);
}
