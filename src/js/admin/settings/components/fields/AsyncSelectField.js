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

import { useState, useEffect, useRef, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ajaxFetch } from '../../utils/ajax';
import { Spinner } from '@wordpress/components';

/**
 * Debounce delay in ms for the search input.
 *
 * @since BuddyBoss [BBVERSION]
 * @type {number}
 */
var SEARCH_DEBOUNCE_MS = 300;

/**
 * Module-scoped counter used to generate unique DOM ids for each
 * AsyncSelectField instance — needed so aria-controls / aria-activedescendant
 * references don't collide when multiple dropdowns mount on the same page
 * (the Pages panel renders 11 of them).
 *
 * @since BuddyBoss [BBVERSION]
 * @type {number}
 */
var bbAsyncSelectIdCounter = 0;

/**
 * Async Select Field component.
 *
 * Renders a text input that shows a dropdown of server-fetched options.
 * Supports browsing (no search term), search, and load-more pagination.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props
 * @param {string}   props.id           Optional ID attribute for the input element.
 * @param {string}   props.value        Current selected value (string ID).
 * @param {Function} props.onChange     Called with new value when selection changes.
 * @param {string}   props.asyncAction       WP AJAX action name for fetching options.
 * @param {Object}   props.asyncExtraParams  Extra params to include in every AJAX request.
 * @param {string}   props.placeholder       Input placeholder text.
 * @param {boolean}  props.disabled          Whether the field is disabled.
 * @param {string}   props.initialLabel      Pre-resolved display label for the current
 *                                           value. When supplied the mount-time resolve
 *                                           AJAX is skipped — useful when the server
 *                                           already knows the label (e.g. page
 *                                           directory dropdowns that ship their title
 *                                           in the initial feature payload).
 * @param {Array}    props.staticOptions     Optional pinned `{ value, label }` options
 *                                           that always render at the top of the dropdown
 *                                           regardless of search term, take precedence
 *                                           over the server resolve, and are used to
 *                                           dedupe identically-valued items returned by
 *                                           the server. Pass a referentially stable
 *                                           array (e.g. module-scoped const) so the
 *                                           memoised dependencies below don't churn.
 * @return {WPElement} Rendered component.
 */
export function AsyncSelectField( { id, value, onChange, asyncAction, asyncExtraParams, placeholder, disabled, initialLabel, staticOptions } ) {
	// Normalise once so we can read .length / map without guarding everywhere.
	// Memoised so `findStaticMatch` and `displayResults` below have stable
	// references when the parent passes a stable `staticOptions` prop.
	var pinnedOptions = useMemo(
		function () {
			return Array.isArray( staticOptions ) ? staticOptions : [];
		},
		[ staticOptions ]
	);

	// Resolve the current value against pinned static options (e.g. "Custom URL"
	// at value '0'). When matched, we use the static option's label and bypass
	// the server resolve below — including the '0' === "none" short-circuit.
	var findStaticMatch = useCallback(
		function ( v ) {
			var s = String( v || '' );
			for ( var i = 0; i < pinnedOptions.length; i++ ) {
				if ( String( pinnedOptions[ i ].value ) === s ) {
					return pinnedOptions[ i ];
				}
			}
			return null;
		},
		[ pinnedOptions ]
	);

	// Re-resolved every render so the JSX `currentStaticMatch` test below
	// reflects the latest value/staticOptions combination, not the mount-time
	// seed. Naming is deliberate — earlier code used `initialStaticMatch` for
	// both the useState seed and the per-render JSX check, which read as if the
	// JSX was using the seed (it isn't).
	var currentStaticMatch = findStaticMatch( value );

	// Display label for the currently selected value. Seeded from a matching
	// static option first, then `initialLabel`, then '' (in which case the
	// mount effect below does the resolve). Lazy initializer because useState
	// only keeps the first call's return value — passing the function form is
	// the canonical React pattern even though the saved cycles here are tiny
	// (the ternary itself is sub-microsecond, and currentStaticMatch is still
	// computed every render for the JSX clear-button check below).
	var selectedLabelState = useState( function () {
		return currentStaticMatch ? currentStaticMatch.label : ( initialLabel || '' );
	} );
	var selectedLabel = selectedLabelState[ 0 ];
	var setSelectedLabel = selectedLabelState[ 1 ];

	// Index of the currently ARIA-active option for keyboard navigation.
	// -1 = nothing active (browse mode, no arrow key pressed yet). Drives
	// aria-activedescendant on the input and the visual highlight class on
	// the list option.
	var activeIndexState = useState( -1 );
	var activeIndex = activeIndexState[ 0 ];
	var setActiveIndex = activeIndexState[ 1 ];

	// Stable IDs for the combobox + listbox + options. Needed to wire
	// aria-controls on the input to the listbox, and aria-activedescendant on
	// the input to the currently-highlighted option. Generated once at mount
	// from a module-scoped counter so multiple instances on one page don't
	// collide.
	var idsRef = useRef( null );
	if ( null === idsRef.current ) {
		var uniq = ++bbAsyncSelectIdCounter;
		idsRef.current = {
			listbox: 'bb-async-select__listbox-' + uniq,
			option:  function ( idx ) { return 'bb-async-select__option-' + uniq + '-' + idx; },
		};
	}

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

	// Refs for the trigger button (focus restore on close) and the in-dropdown
	// search input (auto-focus on open). Required by the "select with internal
	// search" pattern — the trigger no longer accepts typing, so opening the
	// dropdown must move focus into the search input, and Escape/Enter/Clear
	// must return focus to the trigger so keyboard users don't lose their
	// position in the tab order.
	var triggerRef     = useRef( null );
	var searchInputRef = useRef( null );

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

			var fetchParams = { term: term, page: fetchPage };
			if ( asyncExtraParams ) {
				Object.keys( asyncExtraParams ).forEach( function ( key ) {
					fetchParams[ key ] = asyncExtraParams[ key ];
				} );
			}

			ajaxFetch(
				asyncAction,
				fetchParams,
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
					// Aborts are expected (rapid retypes / unmount) — swallow.
					// Anything else gets a warn so real fetch failures don't
					// vanish. Mirrors the resolve-path catch above so both
					// code paths have the same debuggability story.
					if ( err && 'AbortError' === err.name ) {
						return;
					}
					if ( window && window.console && 'function' === typeof window.console.warn ) {
						window.console.warn( 'AsyncSelectField fetchResults failed:', err );
					}
				} )
				.finally( function () {
					setIsLoading( false );
					setIsLoadingMore( false );
				} );
		},
		[ asyncAction, asyncExtraParams ]
	);

	// Track the (value, label) pair we last applied so we can tell when
	// `initialLabel` changing represents a fresh caller-provided hint
	// (e.g. post-Create-Page seeding) vs. the stale prop left over from a
	// user-driven search+select. Seeded with the mount-time value/label so
	// the very first resolve effect short-circuits cleanly.
	var lastAppliedRef = useRef( { value: String( value || '' ), label: initialLabel || '' } );

	// Ensure `selectedLabel` matches the current value.
	//
	// Paths (in priority order):
	//   1. Empty value — clear everything and return.
	//   2. New initialLabel from caller (value/label pair differs from the last
	//      one we applied). Happens when the server seeds the field OR when
	//      PageCreateButton pushes the just-created title through the parent.
	//      Sync selectedLabel and skip resolve.
	//   3. selectedLabel was already set (e.g. handleSelect after a user pick).
	//      Trust it — skip resolve.
	//   4. Fall through — value changed with no known label. Fetch via AJAX
	//      and flip the spinner flag so the UI shows a loader instead of
	//      the clear button.
	useEffect(
		function () {
			var valueStr = String( value || '' );

			// Static options (e.g. "Custom URL" at value '0') win first — they
			// already carry their label so we skip the server resolve, and we
			// also bypass the '0' → "no selection" short-circuit below since
			// '0' is now a real, picked option.
			var staticMatch = findStaticMatch( value );
			if ( staticMatch ) {
				setSelectedLabel( staticMatch.label );
				lastAppliedRef.current = { value: valueStr, label: staticMatch.label };
				return;
			}

			// Treat both '' and '0' as "no selection" — every entity this
			// component resolves (forums, parent groups, LD groups, etc.)
			// uses 0 as the "none" sentinel per WordPress convention. Without
			// this, the resolve fetch fires with selected_id=0; the PHP
			// handlers fall through to a regular search and return the
			// alphabetical first page, which the line below would then pick
			// up as response.data.results[0] and paint as the "saved" label.
			if ( '' === valueStr || '0' === valueStr ) {
				setSelectedLabel( '' );
				lastAppliedRef.current = { value: '', label: '' };
				return;
			}

			var lastApplied = lastAppliedRef.current;

			// Fresh caller-provided label. Only trip this branch when BOTH
			// the label AND the value diverge from what we last applied —
			// that pattern matches "parent pushed a new hint for a new
			// value" (e.g. Create Page seeded initialLabel + onChange(id)).
			//
			// Earlier versions checked the label alone; that misfired on the
			// user-search+select path where handleSelect updates
			// lastAppliedRef.value to the picked id and lastAppliedRef.label
			// to the picked title, while the parent's stale initialLabel prop
			// stays behind. Label-only comparison would see initialLabel
			// (stale 'News Feed') !== lastApplied.label (fresh 'Contact') and
			// overwrite the user's pick. Requiring value-change too defuses
			// that race: after handleSelect, lastApplied.value === valueStr,
			// so the branch is skipped and the "already current" branch below
			// keeps the freshly-picked label intact.
			if ( initialLabel && initialLabel !== lastApplied.label && valueStr !== lastApplied.value ) {
				setSelectedLabel( initialLabel );
				lastAppliedRef.current = { value: valueStr, label: initialLabel };
				return;
			}

			// Label is already current for this value (handleSelect just ran,
			// or the mount-time seed still matches). Keep it.
			if ( selectedLabel && valueStr === lastApplied.value ) {
				return;
			}

			var controller = new AbortController();

			ajaxFetch(
				asyncAction,
				{ selected_id: value, page: 1, term: '' },
				{ signal: controller.signal }
			)
				.then( function ( response ) {
					// A newer resolve (or unmount) may have aborted this request
					// — ignore the response in that case so we don't overwrite
					// state that now belongs to the newer request.
					if ( controller.signal.aborted ) {
						return;
					}
					if ( response.success && response.data.results && response.data.results.length ) {
						var resolvedLabel = response.data.results[ 0 ].label;
						setSelectedLabel( resolvedLabel );
						lastAppliedRef.current = { value: valueStr, label: resolvedLabel };
					}
				} )
				.catch( function ( err ) {
					// Aborts are expected (happen on rapid value changes /
					// unmount) — swallow silently. Anything else gets a warn
					// so real failures don't vanish.
					if ( err && 'AbortError' === err.name ) {
						return;
					}
					if ( window && window.console && 'function' === typeof window.console.warn ) {
						window.console.warn( 'AsyncSelectField resolve failed:', err );
					}
				} );

			return function () {
				controller.abort();
			};
		},
		// Re-run when the value OR the seed label changes externally so the
		// display label stays in sync with whatever the parent now knows.
		// `findStaticMatch` is memoised on `pinnedOptions`, so this also
		// re-resolves correctly when a caller swaps in a different pin set.
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ value, initialLabel, findStaticMatch ]
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

	// The list shown in the dropdown is the static (pinned) options first,
	// then the server-fetched results. Pinned options stay visible regardless
	// of search term — matching the legacy <select> behaviour where Default /
	// Custom URL were always present alongside dynamic page entries.
	//
	// Server responses may already contain options matching pinned values
	// (e.g. `bb_admin_search_published_pages` ships its own Default / Custom
	// URL rows). Filter those out before concatenating so we don't render a
	// duplicate "Custom URL" row, trip React's duplicate-key warning, or
	// announce the same option twice to assistive tech.
	var displayResults = useMemo(
		function () {
			if ( ! pinnedOptions.length ) {
				return results;
			}

			// Filter pinned options against the active search term so they
			// don't pollute the dropdown when the user is searching for
			// something else. Without this, typing "Account" on Login
			// Redirects still surfaced "Default" and "Custom URL" at the
			// top of the list — confusing because those entries don't
			// match the term. An empty search term means "browsing", in
			// which case every pinned option is shown as before.
			//
			// Matching is case-insensitive on the option label, mirroring
			// the server-side search behavior (the AJAX handler does a
			// LIKE on post_title without case folding either way).
			var term = ( search || '' ).trim().toLowerCase();
			var visiblePinned = '' === term
				? pinnedOptions
				: pinnedOptions.filter( function ( opt ) {
					return String( opt.label || '' ).toLowerCase().indexOf( term ) !== -1;
				} );

			if ( ! visiblePinned.length ) {
				// Nothing pinned matches the term — skip the dedupe pass
				// and return the server results unchanged.
				return results;
			}

			// Object.create(null) avoids the Object.prototype chain so a
			// future caller pinning a value like 'constructor', 'toString',
			// '__proto__' or 'hasOwnProperty' can't trigger a false-positive
			// hit when we test for membership below.
			var pinnedValues = Object.create( null );
			for ( var i = 0; i < visiblePinned.length; i++ ) {
				pinnedValues[ String( visiblePinned[ i ].value ) ] = true;
			}
			var deduped = results.filter( function ( r ) {
				return ! pinnedValues[ String( r.value ) ];
			} );
			return visiblePinned.concat( deduped );
		},
		[ pinnedOptions, results, search ]
	);

	// Reset the keyboard-active index when the displayed list changes or the
	// dropdown closes. Without this an out-of-range activeIndex could point
	// into the prior result set after a new search or after opening/closing.
	useEffect(
		function () {
			if ( ! isOpen ) {
				setActiveIndex( -1 );
				return;
			}
			if ( activeIndex >= displayResults.length ) {
				setActiveIndex( displayResults.length > 0 ? displayResults.length - 1 : -1 );
			}
		},
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ displayResults, isOpen ]
	);

	/**
	 * Keyboard navigation handler. Implements the WAI-ARIA combobox pattern:
	 *   - ArrowDown : open the dropdown if closed; otherwise advance to next option
	 *   - ArrowUp   : step back one option (no wrap — users can Escape + re-tab out)
	 *   - Home/End  : jump to first / last option
	 *   - Enter     : commit the currently active option
	 *   - Escape    : close dropdown and clear any unsaved search term
	 *
	 * Actions that open the dropdown also trigger the first page fetch so
	 * keyboard users don't have to press a separate key to see options.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {KeyboardEvent} e Input keydown event.
	 */
	function handleKeyDown( e ) {
		if ( disabled ) {
			return;
		}

		if ( 'ArrowDown' === e.key ) {
			e.preventDefault();
			if ( ! isOpen ) {
				setIsOpen( true );
				setSearch( '' );
				setPage( 1 );
				fetchResults( '', 1, false );
				return;
			}
			if ( displayResults.length > 0 ) {
				setActiveIndex( function ( prev ) {
					return prev + 1 < displayResults.length ? prev + 1 : prev;
				} );
			}
			return;
		}

		if ( 'ArrowUp' === e.key ) {
			e.preventDefault();
			if ( isOpen && displayResults.length > 0 ) {
				setActiveIndex( function ( prev ) {
					return prev > 0 ? prev - 1 : 0;
				} );
			}
			return;
		}

		if ( 'Home' === e.key && isOpen && displayResults.length > 0 ) {
			e.preventDefault();
			setActiveIndex( 0 );
			return;
		}

		if ( 'End' === e.key && isOpen && displayResults.length > 0 ) {
			e.preventDefault();
			setActiveIndex( displayResults.length - 1 );
			return;
		}

		if ( 'Enter' === e.key ) {
			if ( isOpen && activeIndex >= 0 && activeIndex < displayResults.length ) {
				e.preventDefault();
				handleSelect( displayResults[ activeIndex ] );
			}
			return;
		}

		if ( 'Escape' === e.key ) {
			if ( isOpen ) {
				e.preventDefault();
				setIsOpen( false );
				setSearch( '' );
				setActiveIndex( -1 );
				// Return focus to the trigger so the keyboard user doesn't
				// lose their place in the tab order. Click-outside close
				// intentionally skips this so focus follows where the user
				// clicked.
				if ( triggerRef.current ) {
					triggerRef.current.focus();
				}
			}
		}
	}

	/**
	 * Trigger button click — toggle the dropdown open/closed.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleTriggerClick() {
		if ( disabled ) {
			return;
		}
		if ( isOpen ) {
			setIsOpen( false );
			return;
		}
		setIsOpen( true );
		setSearch( '' );
		setPage( 1 );
		fetchResults( '', 1, false );
	}

	/**
	 * Trigger button keyboard handler. The trigger doesn't accept typing
	 * (search lives inside the dropdown), so only the keys that *open* the
	 * dropdown matter here — actual list navigation runs on the in-dropdown
	 * search input via handleKeyDown above.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {KeyboardEvent} e Trigger button keydown event.
	 */
	function handleTriggerKeyDown( e ) {
		if ( disabled || isOpen ) {
			return;
		}
		if ( 'ArrowDown' === e.key || 'Enter' === e.key || ' ' === e.key ) {
			e.preventDefault();
			setIsOpen( true );
			setSearch( '' );
			setPage( 1 );
			fetchResults( '', 1, false );
		}
	}

	// Auto-focus the in-dropdown search input when the dropdown opens.
	// Runs on every `isOpen` transition; the early-return guards against
	// the closed→closed re-render path where the ref is intentionally null.
	useEffect( function () {
		if ( isOpen && searchInputRef.current ) {
			searchInputRef.current.focus();
		}
	}, [ isOpen ] );

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
		// Record the user's choice BEFORE onChange fires so the value-change
		// effect (which runs next render) sees a fresh lastAppliedRef that
		// already matches the new value — and takes the "label is current"
		// branch instead of firing a redundant resolve AJAX.
		lastAppliedRef.current = { value: String( option.value ), label: option.label };
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

	// Display label for the trigger button — selected value or placeholder.
	// The trigger no longer accepts typing (search lives inside the dropdown
	// per the design contract), so we render the current selection verbatim
	// and fall back to the placeholder when nothing is selected.
	var triggerLabel = selectedLabel || placeholder || __( 'Select…', 'buddyboss-platform' );

	return (
		<div
			className="bb-async-select"
			ref={ wrapperRef }
		>
			<div className="bb-async-select__trigger-wrapper">
				<button
					type="button"
					ref={ triggerRef }
					id={ id || undefined }
					className={ 'bb-async-select__trigger' + ( isOpen ? ' is-open' : '' ) + ( ! selectedLabel ? ' is-placeholder' : '' ) }
					disabled={ disabled }
					aria-haspopup="listbox"
					aria-expanded={ isOpen }
					aria-controls={ idsRef.current.listbox }
					onClick={ handleTriggerClick }
					onKeyDown={ handleTriggerKeyDown }
				>
					<span className="bb-async-select__trigger-label">
						{ triggerLabel }
					</span>
					<i className="bb-async-select__trigger-chevron bb-icons-rl bb-icons-rl-caret-down" aria-hidden="true" />
				</button>
			</div>

			{ isOpen && (
				<div className="bb-async-select__dropdown">
					{ /*
					 * In-dropdown search input. Auto-focused on open via the
					 * useEffect above. Owns keyboard navigation
					 * (ArrowDown/Up/Home/End/Enter/Escape) through the shared
					 * `handleKeyDown` — same handler the old combobox-style
					 * trigger input used, just relocated. Not a combobox in the
					 * ARIA sense (no `role="combobox"`, no `aria-autocomplete`)
					 * — the dropdown is already open, so the input is just a
					 * filter over an already-visible listbox, identical to the
					 * GitHub / Linear / Material UI "select with search"
					 * pattern that the design team's Figma reference shows.
					 */ }
					<div className="bb-async-select__search-wrapper">
						<i className="bb-async-select__search-icon bb-icons-rl bb-icons-rl-magnifying-glass" aria-hidden="true" />
						<input
							ref={ searchInputRef }
							type="search"
							className="bb-async-select__search-input"
							value={ search }
							onChange={ handleSearchChange }
							onKeyDown={ handleKeyDown }
							placeholder={ __( 'Search…', 'buddyboss-platform' ) }
							autoComplete="off"
							aria-controls={ idsRef.current.listbox }
							aria-activedescendant={ activeIndex >= 0 && activeIndex < displayResults.length
								? idsRef.current.option( activeIndex )
								: undefined
							}
						/>
					</div>

					{ /*
					 * `role="status"` + `aria-live="polite"` on the status region
					 * so assistive tech announces "Loading" / "No results found"
					 * state changes as users type, instead of needing to Tab
					 * around the listbox to discover why it's empty.
					 */ }
					{ isLoading && (
						<div
							className="bb-async-select__status"
							role="status"
							aria-live="polite"
						>
							<Spinner />
							<span className="screen-reader-text">{ __( 'Loading results', 'buddyboss-platform' ) }</span>
						</div>
					) }

					{ ! isLoading && displayResults.length === 0 && (
						<div
							className="bb-async-select__status"
							role="status"
							aria-live="polite"
						>
							{ search
								? __( 'No results found.', 'buddyboss-platform' )
								: __( 'No options available.', 'buddyboss-platform' )
							}
						</div>
					) }

					{ ! isLoading && displayResults.length > 0 && (
						<ul
							className="bb-async-select__list"
							role="listbox"
							id={ idsRef.current.listbox }
						>
							{ displayResults.map( function ( option, idx ) {
								var isSelected = option.value === String( value );
								var isActive   = idx === activeIndex;
								return (
									<li
										key={ option.value }
										id={ idsRef.current.option( idx ) }
										role="option"
										aria-selected={ isSelected }
										className={
											'bb-async-select__option' +
											( isSelected ? ' is-selected' : '' ) +
											( isActive ? ' is-active' : '' )
										}
									>
										<button
											type="button"
											tabIndex={ -1 }
											onMouseDown={ function ( e ) {
												// Use mousedown to fire before input blur.
												e.preventDefault();
												handleSelect( option );
											} }
											onMouseEnter={ function () {
												// Keep pointer and keyboard focus aligned.
												setActiveIndex( idx );
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
									? __( 'Loading…', 'buddyboss-platform' )
									: __( 'Load more', 'buddyboss-platform' )
								}
							</button>
						</div>
					) }
				</div>
			) }
		</div>
	);
}
