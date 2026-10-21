/**
 * BuddyBoss Admin Settings 2.0 - List Screen State Hook
 *
 * Provides common useState declarations shared across all list screens:
 * loading, notice, selection, bulk action, search, and bulk processing state.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';

/**
 * Custom hook for common list screen state.
 *
 * Returns an object with standardized state variables and their setters.
 * Screens can destructure what they need and alias if their naming differs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {Object} Common state variables and setters.
 */
export function useListScreenState() {
	var isLoadingState = useState( true );
	var noticeState = useState( null );
	var selectedIdsState = useState( [] );
	var bulkActionState = useState( '' );
	var isBulkProcessingState = useState( false );
	var searchInputState = useState( '' );
	var searchQueryState = useState( '' );

	return {
		isLoading: isLoadingState[ 0 ],
		setIsLoading: isLoadingState[ 1 ],
		notice: noticeState[ 0 ],
		setNotice: noticeState[ 1 ],
		selectedIds: selectedIdsState[ 0 ],
		setSelectedIds: selectedIdsState[ 1 ],
		bulkAction: bulkActionState[ 0 ],
		setBulkAction: bulkActionState[ 1 ],
		isBulkProcessing: isBulkProcessingState[ 0 ],
		setIsBulkProcessing: isBulkProcessingState[ 1 ],
		searchInput: searchInputState[ 0 ],
		setSearchInput: searchInputState[ 1 ],
		searchQuery: searchQueryState[ 0 ],
		setSearchQuery: searchQueryState[ 1 ],
	};
}
