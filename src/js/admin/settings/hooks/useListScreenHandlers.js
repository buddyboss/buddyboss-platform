/**
 * BuddyBoss Admin Settings 2.0 - List Screen Handlers Hook
 *
 * Provides common event handlers for list screens: debounced search,
 * page change, sort change, filter change, select all, select row.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useRef, useCallback } from '@wordpress/element';

/**
 * Custom hook for common list screen handlers.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   config                   Configuration object.
 * @param {Function} config.setSearchInput     Setter for search input display value.
 * @param {Function} config.setSearchQuery     Setter for debounced search query (triggers fetch).
 * @param {Function} config.setPage            Setter for current page number.
 * @param {Function} config.setSelectedIds     Setter for selected item IDs array.
 * @param {Function} config.setSort            Setter for sort value (optional).
 * @param {Function} config.setFilter          Setter for filter value (optional).
 * @param {Function} config.getItemIds         Function that returns array of all current item IDs (for select all).
 * @param {number}   config.debounceMs         Debounce delay in ms. Default 500.
 *
 * @returns {Object} Handler functions.
 */
export function useListScreenHandlers( config ) {
	var setSearchInput = config.setSearchInput;
	var setSearchQuery = config.setSearchQuery;
	var setPage = config.setPage;
	var setSelectedIds = config.setSelectedIds;
	var setSort = config.setSort;
	var setFilter = config.setFilter;
	var getItemIds = config.getItemIds;
	var debounceMs = config.debounceMs || 500;

	var searchTimerRef = useRef( null );

	/**
	 * Handle search input change with debounce.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} value Search input value.
	 */
	var handleSearchChange = useCallback( function ( value ) {
		setSearchInput( value );
		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}
		searchTimerRef.current = setTimeout( function () {
			setSearchQuery( value );
			setPage( 1 );
		}, debounceMs );
	}, [ setSearchInput, setSearchQuery, setPage, debounceMs ] );

	/**
	 * Clear search input and query.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSearchClear = useCallback( function () {
		setSearchInput( '' );
		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}
		setSearchQuery( '' );
		setPage( 1 );
	}, [ setSearchInput, setSearchQuery, setPage ] );

	/**
	 * Handle page change — reset selection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} newPage Page number.
	 */
	var handlePageChange = useCallback( function ( newPage ) {
		setPage( newPage );
		setSelectedIds( [] );
	}, [ setPage, setSelectedIds ] );

	/**
	 * Handle sort change — reset to page 1 and clear selection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} value Sort value.
	 */
	var handleSortChange = useCallback( function ( value ) {
		if ( setSort ) {
			setSort( value );
		}
		setPage( 1 );
		setSelectedIds( [] );
	}, [ setSort, setPage, setSelectedIds ] );

	/**
	 * Handle filter change — reset to page 1 and clear selection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} value Filter value.
	 */
	var handleFilterChange = useCallback( function ( value ) {
		if ( setFilter ) {
			setFilter( value );
		}
		setPage( 1 );
		setSelectedIds( [] );
	}, [ setFilter, setPage, setSelectedIds ] );

	/**
	 * Handle select all checkbox.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {boolean} checked Whether select-all is checked.
	 */
	var handleSelectAll = useCallback( function ( checked ) {
		if ( checked && getItemIds ) {
			setSelectedIds( getItemIds() );
		} else {
			setSelectedIds( [] );
		}
	}, [ setSelectedIds, getItemIds ] );

	/**
	 * Handle individual row selection toggle.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number}  id      Item ID.
	 * @param {boolean} checked Whether the row is checked.
	 */
	var handleSelectRow = useCallback( function ( id, checked ) {
		if ( checked ) {
			setSelectedIds( function ( prev ) {
				return prev.concat( [ id ] );
			} );
		} else {
			setSelectedIds( function ( prev ) {
				return prev.filter( function ( i ) {
					return i !== id;
				} );
			} );
		}
	}, [ setSelectedIds ] );

	/**
	 * Get the search timer ref for cleanup on unmount.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @returns {Object} React ref holding the debounce timer.
	 */
	var getSearchTimerRef = function () {
		return searchTimerRef;
	};

	return {
		handleSearchChange: handleSearchChange,
		handleSearchClear: handleSearchClear,
		handlePageChange: handlePageChange,
		handleSortChange: handleSortChange,
		handleFilterChange: handleFilterChange,
		handleSelectAll: handleSelectAll,
		handleSelectRow: handleSelectRow,
		searchTimerRef: searchTimerRef,
		getSearchTimerRef: getSearchTimerRef,
	};
}
