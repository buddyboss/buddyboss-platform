/**
 * BuddyBoss Admin Settings 2.0 - Forums List Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef, useMemo } from '@wordpress/element';
import {
	Button,
	CheckboxControl,
	SelectControl,
	Spinner,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Modal,
	TextControl,
} from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getForums, getForum, saveForum, forumBulkAction } from '../utils/ajax';
import { sanitizeHtml, safeUrl } from '../utils/sanitize';
import { ForumCreateModal } from '../components/forums/ForumCreateModal';
import { AsyncSelectField } from '../components/fields/AsyncSelectField';
import { RichTextEditor, forceRemoveEditor } from '../components/common/RichTextEditor';

/**
 * Sort options for the forums list dropdown (static, never changes).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var sortOptions = [
	{ label: __( 'Newest', 'buddyboss' ), value: 'newest' },
	{ label: __( 'Oldest', 'buddyboss' ), value: 'oldest' },
	{ label: __( 'Highest Discussions', 'buddyboss' ), value: 'highest_discussions' },
	{ label: __( 'Lowest Discussions', 'buddyboss' ), value: 'lowest_discussions' },
	{ label: __( 'Highest Replies', 'buddyboss' ), value: 'highest_replies' },
	{ label: __( 'Lowest Replies', 'buddyboss' ), value: 'lowest_replies' },
];

/**
 * Number of forums to fetch per page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {number}
 */
var FORUMS_PER_PAGE = 20;

/**
 * Visibility icon class lookup (static, never changes).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Object}
 */
var VISIBILITY_ICONS = {
	'publish': 'bb-icons-rl bb-icons-rl-globe-simple',
	'private': 'bb-icons-rl bb-icons-rl-lock-simple',
	'hidden': 'bb-icons-rl bb-icons-rl-eye-slash',
};

/**
 * Visibility labels (static, never changes).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Object}
 */
var VISIBILITY_LABELS = {
	'publish': __( 'Public', 'buddyboss' ),
	'private': __( 'Private', 'buddyboss' ),
	'hidden': __( 'Hidden', 'buddyboss' ),
};

/**
 * Core column keys that are rendered natively by the React UI.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var CORE_COLUMNS = [ 'cb', 'title', 'bbp_forum_topic_count', 'bbp_forum_reply_count', 'author', 'bbp_forum_created', 'bbp_forum_freshness' ];

/**
 * Forums List Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation handler.
 * @returns {JSX.Element} Forums list screen.
 */
export function ForumsListScreen( { onNavigate } ) {
	var forumsState = useState( [] );
	var forums = forumsState[ 0 ];
	var setForums = forumsState[ 1 ];

	var totalState = useState( 0 );
	var total = totalState[ 0 ];
	var setTotal = totalState[ 1 ];

	var currentPageState = useState( 1 );
	var currentPage = currentPageState[ 0 ];
	var setCurrentPage = currentPageState[ 1 ];

	var filterState = useState( 'all' );
	var filter = filterState[ 0 ];
	var setFilter = filterState[ 1 ];

	var sortByState = useState( 'newest' );
	var sortBy = sortByState[ 0 ];
	var setSortBy = sortByState[ 1 ];

	var searchQueryState = useState( '' );
	var searchQuery = searchQueryState[ 0 ];
	var setSearchQuery = searchQueryState[ 1 ];

	var searchInputState = useState( '' );
	var searchInput = searchInputState[ 0 ];
	var setSearchInput = searchInputState[ 1 ];

	var selectedIdsState = useState( [] );
	var selectedIds = selectedIdsState[ 0 ];
	var setSelectedIds = selectedIdsState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var bulkActionsState = useState( {} );
	var bulkActions = bulkActionsState[ 0 ];
	var setBulkActions = bulkActionsState[ 1 ];

	var viewsState = useState( {} );
	var views = viewsState[ 0 ];
	var setViews = viewsState[ 1 ];

	var columnsState = useState( {} );
	var columns = columnsState[ 0 ];
	var setColumns = columnsState[ 1 ];



	var bulkActionState = useState( '' );
	var bulkAction = bulkActionState[ 0 ];
	var setBulkAction = bulkActionState[ 1 ];

	var noticeState = useState( null );
	var notice = noticeState[ 0 ];
	var setNotice = noticeState[ 1 ];

	var deleteModalState = useState( false );
	var deleteModalOpen = deleteModalState[ 0 ];
	var setDeleteModalOpen = deleteModalState[ 1 ];

	var deleteTargetIdsState = useState( [] );
	var deleteTargetIds = deleteTargetIdsState[ 0 ];
	var setDeleteTargetIds = deleteTargetIdsState[ 1 ];

	var deleteConfirmState = useState( false );
	var deleteConfirmChecked = deleteConfirmState[ 0 ];
	var setDeleteConfirmChecked = deleteConfirmState[ 1 ];

	var bulkEditOpenState = useState( false );
	var bulkEditOpen = bulkEditOpenState[ 0 ];
	var setBulkEditOpen = bulkEditOpenState[ 1 ];

	var bulkEditVisibilityState = useState( 'no_change' );
	var bulkEditVisibility = bulkEditVisibilityState[ 0 ];
	var setBulkEditVisibility = bulkEditVisibilityState[ 1 ];

	var createModalState = useState( false );
	var createModalOpen = createModalState[ 0 ];
	var setCreateModalOpen = createModalState[ 1 ];

	var editForumState = useState( null );
	var editForum = editForumState[ 0 ];
	var setEditForum = editForumState[ 1 ];

	var isEditLoadingState = useState( false );
	var isEditLoading = isEditLoadingState[ 0 ];
	var setIsEditLoading = isEditLoadingState[ 1 ];

	var isEditSavingState = useState( false );
	var isEditSaving = isEditSavingState[ 0 ];
	var setIsEditSaving = isEditSavingState[ 1 ];

	var isBulkProcessingState = useState( false );
	var isBulkProcessing = isBulkProcessingState[ 0 ];
	var setIsBulkProcessing = isBulkProcessingState[ 1 ];

	var refetchCounterState = useState( 0 );
	var refetchCounter = refetchCounterState[ 0 ];
	var setRefetchCounter = refetchCounterState[ 1 ];

	var searchTimerRef = useRef( null );
	var hasMetaRef = useRef( false );
	var editAbortRef = useRef( null );

	var totalPages = Math.ceil( total / FORUMS_PER_PAGE );

	/**
	 * Fetch forums from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} options Optional fetch options.
	 */
	var fetchForums = useCallback( function ( options ) {
		setIsLoading( true );

		var fetchOptions = {};
		if ( options && options.signal ) {
			fetchOptions.signal = options.signal;
		}

		getForums( {
			page: currentPage,
			per_page: FORUMS_PER_PAGE,
			search: searchQuery,
			status: filter,
			sort: sortBy,
			include_meta: hasMetaRef.current ? 0 : 1,
		}, fetchOptions ).then( function ( response ) {
			if ( response.success && response.data ) {
				var rawForums = response.data.forums || [];

				// Sanitize custom column HTML once at fetch time.
				var sanitizedForums = rawForums.map( function ( forum ) {
					if ( ! forum.custom_columns ) {
						return forum;
					}
					var sanitizedCols = {};
					Object.keys( forum.custom_columns ).forEach( function ( key ) {
						sanitizedCols[ key ] = sanitizeHtml( forum.custom_columns[ key ] );
					} );
					return Object.assign( {}, forum, { custom_columns: sanitizedCols } );
				} );

				setForums( sanitizedForums );
				setTotal( response.data.total || 0 );

				if ( response.data.views ) {
					setViews( response.data.views );
				}
				if ( response.data.bulk_actions ) {
					setBulkActions( response.data.bulk_actions );
				}
				if ( response.data.columns ) {
					setColumns( response.data.columns );
				}
				hasMetaRef.current = true;
			}
			setIsLoading( false );
		} ).catch( function ( error ) {
			if ( error && 'AbortError' === error.name ) {
				return;
			}
			setIsLoading( false );
			setNotice( {
				type: 'error',
				message: __( 'Failed to load forums. Please try again.', 'buddyboss' ),
			} );
		} );
	}, [ currentPage, searchQuery, filter, sortBy, refetchCounter ] );

	// Fetch on mount and when filters change.
	useEffect( function () {
		var controller = new AbortController();
		fetchForums( { signal: controller.signal } );
		return function () {
			controller.abort();
		};
	}, [ fetchForums ] );

	// Clear notice after 5 seconds.
	useEffect( function () {
		if ( notice ) {
			var timer = setTimeout( function () {
				setNotice( null );
			}, 5000 );
			return function () {
				clearTimeout( timer );
			};
		}
	}, [ notice ] );

	// Cleanup on unmount.
	useEffect( function () {
		return function () {
			if ( searchTimerRef.current ) {
				clearTimeout( searchTimerRef.current );
			}
			if ( editAbortRef.current ) {
				editAbortRef.current.abort();
			}
		};
	}, [] );

	/**
	 * Handle search input with debounce.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} value Search value.
	 */
	var handleSearchChange = function ( value ) {
		setSearchInput( value );
		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}
		searchTimerRef.current = setTimeout( function () {
			setSearchQuery( value );
			setCurrentPage( 1 );
		}, 500 );
	};

	/**
	 * Handle filter change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} newFilter Filter value.
	 */
	var handleFilterChange = function ( newFilter ) {
		setFilter( newFilter );
		setCurrentPage( 1 );
		setSelectedIds( [] );
	};

	/**
	 * Handle sort change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} value Sort value.
	 */
	var handleSortChange = function ( value ) {
		setSortBy( value );
		setCurrentPage( 1 );
		setSelectedIds( [] );
	};

	/**
	 * Handle select all checkbox.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {boolean} checked Checked state.
	 */
	var handleSelectAll = function ( checked ) {
		if ( checked ) {
			setSelectedIds( forums.map( function ( f ) {
				return f.id;
			} ) );
		} else {
			setSelectedIds( [] );
		}
	};

	/**
	 * Handle individual row checkbox.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number}  id      Forum ID.
	 * @param {boolean} checked Checked state.
	 */
	var handleSelectRow = function ( id, checked ) {
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
	};

	/**
	 * Reset metadata and refetch the forums list from page 1.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var resetAndRefetch = function () {
		hasMetaRef.current = false;
		if ( 1 === currentPage ) {
			setRefetchCounter( function ( prev ) { return prev + 1; } );
		} else {
			setCurrentPage( 1 );
		}
	};

	/**
	 * Perform bulk action on forums.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} action    The action key (edit, delete).
	 * @param {Array}  ids       Forum ID(s).
	 * @param {Object} extraData Optional extra data to send.
	 */
	var performAction = function ( action, ids, extraData ) {
		if ( ! ids || ( Array.isArray( ids ) && 0 === ids.length ) ) {
			return;
		}

		if ( isBulkProcessing ) {
			return;
		}

		var idArray = Array.isArray( ids ) ? ids : [ ids ];

		setIsBulkProcessing( true );
		forumBulkAction( idArray, action, extraData ).then( function ( response ) {
			setIsBulkProcessing( false );
			if ( response.success ) {
				setNotice( { type: 'success', message: response.data.message } );
				setSelectedIds( [] );
				setBulkAction( '' );
				resetAndRefetch();
			} else {
				setNotice( { type: 'error', message: ( response.data && response.data.message ) || __( 'Action failed.', 'buddyboss' ) } );
			}
		} ).catch( function () {
			setIsBulkProcessing( false );
			setNotice( { type: 'error', message: __( 'An error occurred.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Handle bulk action apply.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleBulkApply = function () {
		if ( ! bulkAction || 0 === selectedIds.length ) {
			return;
		}

		var action = bulkAction.replace( /^bulk_/, '' );

		if ( 'delete' === action ) {
			setDeleteTargetIds( selectedIds.slice() );
			setDeleteConfirmChecked( false );
			setDeleteModalOpen( true );
			return;
		}

		if ( 'edit' === action ) {
			setBulkEditVisibility( 'no_change' );
			setBulkEditOpen( true );
			return;
		}

		performAction( action, selectedIds );
	};

	/**
	 * Handle single forum delete.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} forum The forum object.
	 */
	var handleDeleteForum = function ( forum ) {
		setDeleteTargetIds( [ forum.id ] );
		setDeleteConfirmChecked( false );
		setDeleteModalOpen( true );
	};

	/**
	 * Confirm delete from the delete modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleConfirmDelete = function () {
		setDeleteModalOpen( false );
		performAction( 'delete', deleteTargetIds );
	};

	/**
	 * Handle confirming bulk edit from the bulk edit modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleConfirmBulkEdit = function () {
		setBulkEditOpen( false );
		performAction( 'edit', selectedIds, {
			edit_visibility: bulkEditVisibility,
		} );
	};

	/**
	 * Handle opening the edit modal for a forum.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} forum The forum object.
	 */
	var handleEditForum = function ( forum ) {
		if ( editAbortRef.current ) {
			editAbortRef.current.abort();
		}
		editAbortRef.current = new AbortController();

		setIsEditLoading( true );
		getForum( forum.id, { signal: editAbortRef.current.signal } ).then( function ( response ) {
			setIsEditLoading( false );
			if ( response.success && response.data ) {
				setEditForum( response.data );
			} else {
				setNotice( { type: 'error', message: ( response.data && response.data.message ) || __( 'Failed to load forum data.', 'buddyboss' ) } );
			}
		} ).catch( function ( err ) {
			if ( err && 'AbortError' === err.name ) {
				return;
			}
			setIsEditLoading( false );
			setNotice( { type: 'error', message: __( 'An error occurred loading forum data.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Handle saving the edit modal data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} payload Save payload.
	 */
	var handleSaveForum = function ( payload ) {
		setIsEditSaving( true );
		saveForum( payload ).then( function ( response ) {
			setIsEditSaving( false );
			if ( response.success ) {
				setEditForum( null );
				setNotice( { type: 'success', message: response.data.message } );
				resetAndRefetch();
			} else {
				setNotice( { type: 'error', message: ( response.data && response.data.message ) || __( 'Failed to save forum.', 'buddyboss' ) } );
			}
		} ).catch( function ( err ) {
			setIsEditSaving( false );
			setNotice( { type: 'error', message: err.message || __( 'An error occurred saving the forum.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Handle forum created successfully.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleForumCreated = function () {
		setCreateModalOpen( false );
		setNotice( { type: 'success', message: __( 'Forum created successfully.', 'buddyboss' ) } );
		resetAndRefetch();
	};

	/**
	 * Get visibility badge icon class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} visibility Forum visibility.
	 * @returns {string} Icon class.
	 */
	var getVisibilityIcon = function ( visibility ) {
		return VISIBILITY_ICONS[ visibility ] || VISIBILITY_ICONS[ 'publish' ];
	};

	/**
	 * Get visibility label.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} forum Forum object.
	 * @returns {string} Visibility label.
	 */
	var getVisibilityLabel = function ( forum ) {
		if ( forum.visibility_label ) {
			return decodeEntities( forum.visibility_label );
		}
		return VISIBILITY_LABELS[ forum.visibility ] || forum.visibility;
	};

	// Compute custom column keys.
	var customColumnKeys = useMemo( function () {
		return Object.keys( columns ).filter( function ( key ) {
			return CORE_COLUMNS.indexOf( key ) === -1;
		} );
	}, [ columns ] );

	// Build filter options from views.
	var filterOptions = useMemo( function () {
		var options = [];
		if ( Object.keys( views ).length > 0 ) {
			Object.keys( views ).forEach( function ( key ) {
				var view = views[ key ];
				var label = view.label || key;
				if ( view.count > 0 || 'all' === key ) {
					label = label + ' (' + view.count + ')';
				}
				options.push( { label: label, value: key } );
			} );
		} else {
			options.push( { label: __( 'All', 'buddyboss' ), value: 'all' } );
		}
		return options;
	}, [ views ] );

	var allSelected = forums.length > 0 && selectedIds.length === forums.length;

	// Get names for currently selected forums (for bulk edit modal).
	var selectedForumNames = useMemo( function () {
		var nameMap = {};
		forums.forEach( function ( f ) {
			nameMap[ f.id ] = f.name;
		} );
		return selectedIds.map( function ( id ) {
			return { id: id, title: nameMap[ id ] || '#' + id };
		} );
	}, [ selectedIds, forums ] );

	// Get names for forums targeted by the delete modal.
	var deleteTargetForumNames = useMemo( function () {
		var nameMap = {};
		forums.forEach( function ( f ) {
			nameMap[ f.id ] = f.name;
		} );
		return deleteTargetIds.map( function ( id ) {
			return { id: id, title: nameMap[ id ] || '#' + id };
		} );
	}, [ deleteTargetIds, forums ] );

	/**
	 * Build pagination page numbers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @returns {Array} Array of page number items.
	 */
	var getPageNumbers = function () {
		var pages = [];
		var maxVisible = 5;

		if ( totalPages <= 7 ) {
			for ( var i = 1; i <= totalPages; i++ ) {
				pages.push( i );
			}
		} else {
			pages.push( 1 );

			if ( currentPage > maxVisible - 1 ) {
				pages.push( '...' );
			}

			var start = Math.max( 2, currentPage - 1 );
			var end = Math.min( totalPages - 1, currentPage + 1 );

			if ( currentPage <= 3 ) {
				end = Math.min( totalPages - 1, maxVisible );
			}
			if ( currentPage >= totalPages - 2 ) {
				start = Math.max( 2, totalPages - maxVisible + 1 );
			}

			for ( var j = start; j <= end; j++ ) {
				pages.push( j );
			}

			if ( currentPage < totalPages - ( maxVisible - 2 ) ) {
				pages.push( '...' );
			}

			pages.push( totalPages );
		}

		return pages;
	};

	return (
		<div className="bb-forums-list">
			{ /* Notice */ }
			{ notice && (
				<div className={ 'bb-admin-notice bb-admin-notice--' + notice.type }>
					<span>{ notice.message }</span>
					<button
						className="bb-admin-notice--dismiss"
						onClick={ function () {
							setNotice( null );
						} }
					>
						<i className='bb-icons-rl bb-icons-rl-x'></i>
					</button>
				</div>
			) }

			{ /* Header */ }
			<div className="bb-forums-list__header">
				<h2 className="bb-forums-list__title">{ __( 'All Forums', 'buddyboss' ) }</h2>
				<Button
					variant="primary"
					className="bb-forums-list__create-btn"
					onClick={ function () {
						setCreateModalOpen( true );
					} }
				>
					<i className="bb-icons-rl bb-icons-rl-plus"></i>
					{ __( 'Create New Forum', 'buddyboss' ) }
				</Button>
			</div>

			{ /* Toolbar */ }
			<div className="bb-forums-list__toolbar">
				<div className="bb-forums-list__toolbar-left">
					{ /* Bulk Actions */ }
					<div className="bb-forums-list__bulk-actions">
						<SelectControl
							value={ bulkAction }
							options={ [ { label: __( 'Bulk actions', 'buddyboss' ), value: '' } ].concat(
								Object.keys( bulkActions ).map( function ( key ) {
									return { label: bulkActions[ key ], value: key };
								} )
							) }
							onChange={ setBulkAction }
							__nextHasNoMarginBottom
						/>
						<Button
							variant="secondary"
							onClick={ handleBulkApply }
							disabled={ ! bulkAction || 0 === selectedIds.length || isBulkProcessing }
							className="bb-forums-list__bulk-apply"
						>
							{ __( 'Apply', 'buddyboss' ) }
						</Button>
					</div>
				</div>

				<div className="bb-forums-list__toolbar-right">
					{ /* Status Filter */ }
					<SelectControl
						value={ filter }
						options={ filterOptions }
						onChange={ handleFilterChange }
						className="bb-forums-list__filter-select"
						__nextHasNoMarginBottom
					/>

					{ /* Sort Dropdown */ }
					<SelectControl
						value={ sortBy }
						options={ sortOptions }
						onChange={ handleSortChange }
						className="bb-forums-list__sort-select"
						__nextHasNoMarginBottom
					/>

					{ /* Search */ }
					<div className="bb-forums-list__search">
						<input
							type="text"
							value={ searchInput }
							onChange={ function ( e ) {
								handleSearchChange( e.target.value );
							} }
							placeholder={ __( 'Search forums', 'buddyboss' ) }
							aria-label={ __( 'Search forums', 'buddyboss' ) }
							className="bb-forums-list__search-input"
						/>
						<span className="bb-forums-list__search-icon">
							<i className="bb-icons-rl bb-icons-rl-search"></i>
						</span>
					</div>
				</div>
			</div>

			{ /* Table */ }
			<div className="bb-forums-list__table-wrapper">
				{ isLoading ? (
					<div className="bb-forums-list__loading">
						<Spinner />
					</div>
				) : 0 === forums.length ? (
					<div className="bb-forums-list__empty">
						<p>{ __( 'No forums found.', 'buddyboss' ) }</p>
					</div>
				) : (
					<table className="bb-forums-list__table">
						<thead>
							<tr>
								<th className="bb-forums-list__th--checkbox">
									<CheckboxControl
										checked={ allSelected }
										onChange={ handleSelectAll }
										__nextHasNoMarginBottom
									/>
								</th>
								<th className="bb-forums-list__th--name">
									{ __( 'Forum', 'buddyboss' ) }
								</th>
								<th className="bb-forums-list__th--privacy">
									{ __( 'Privacy', 'buddyboss' ) }
								</th>
								<th className="bb-forums-list__th--discussions">
									{ __( 'Discussions', 'buddyboss' ) }
								</th>
								<th className="bb-forums-list__th--replies">
									{ __( 'Replies', 'buddyboss' ) }
								</th>
								<th className="bb-forums-list__th--author">
									{ __( 'Author', 'buddyboss' ) }
								</th>
								<th className="bb-forums-list__th--last-post">
									{ __( 'Last Post', 'buddyboss' ) }
								</th>
								{ /* Custom columns from bbp_admin_forums_column_headers filter */ }
								{ customColumnKeys.map( function ( key ) {
									return (
										<th key={ key } className={ 'bb-forums-list__th--custom bb-forums-list__th--' + key }>
											{ columns[ key ] }
										</th>
									);
								} ) }
								<th className="bb-forums-list__th--actions">&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							{ forums.map( function ( forum ) {
								var isSelected = selectedIds.indexOf( forum.id ) !== -1;

								return (
									<tr
										key={ forum.id }
										className={ 'bb-forums-list__row' + ( isSelected ? ' bb-forums-list__row--selected' : '' ) }
									>
										<td className="bb-forums-list__td--checkbox">
											<CheckboxControl
												checked={ isSelected }
												onChange={ function ( checked ) {
													handleSelectRow( forum.id, checked );
												} }
												__nextHasNoMarginBottom
											/>
										</td>
										<td className="bb-forums-list__td--name">
											<a
												href={ safeUrl( forum.permalink ) }
												target="_blank"
												rel="noopener noreferrer"
												className="bb-forums-list__forum-name"
											>
												{ decodeEntities( forum.name ) }
											</a>
										</td>
										<td className="bb-forums-list__td--privacy">
											<span className={ 'bb-forums-list__privacy-badge bb-forums-list__privacy-badge--' + forum.visibility }>
												<i className={ getVisibilityIcon( forum.visibility ) }></i>
												{ getVisibilityLabel( forum ) }
											</span>
										</td>
										<td className="bb-forums-list__td--discussions">
											<i className="bb-icons-rl-chats"></i>
											{ forum.discussions_count }
										</td>
										<td className="bb-forums-list__td--replies">
											<i className="bb-icons-rl-arrow-bend-up-left"></i>
											{ forum.replies_count }
										</td>
										<td className="bb-forums-list__td--author">
											<div className="bb-forums-list__author-cell">
												{ forum.author_avatar && (
													<img
														src={ safeUrl( forum.author_avatar ) }
														alt={ decodeEntities( forum.author_name ) }
														className="bb-forums-list__author-avatar"
													/>
												) }
												<span className="bb-forums-list__author-name">
													{ decodeEntities( forum.author_name ) }
												</span>
											</div>
										</td>
										<td className="bb-forums-list__td--last-post">
											<i className="bb-icons-rl-clock"></i>
											{ forum.last_active ? (
												<span className="bb-forums-list__date">
													{ decodeEntities( forum.last_active ) }
												</span>
											) : (
												<span className="bb-forums-list__no-activity">
													{ __( 'No Discussions', 'buddyboss' ) }
												</span>
											) }
										</td>
										{ /* Custom columns */ }
										{ forum.custom_columns && customColumnKeys.map( function ( key ) {
											return (
												<td key={ key } className={ 'bb-forums-list__td--custom bb-forums-list__td--' + key }>
													<span dangerouslySetInnerHTML={ { __html: forum.custom_columns[ key ] } } />
												</td>
											);
										} ) }
										<td className="bb-forums-list__td--actions">
											<DropdownMenu
												icon={ <i className="bb-icons-rl-dots-three"></i> }
												label={ __( 'More options', 'buddyboss' ) }
											>
												{ function ( dropdownProps ) {
													var onClose = dropdownProps.onClose;
													return (
														<MenuGroup className="bb_dropdown_menu_group">
															{ forum.permalink && (
																<MenuItem
																	onClick={ function () {
																		var permalink = safeUrl( forum.permalink );
																		if ( '#' !== permalink ) {
																			window.open( permalink, '_blank', 'noopener noreferrer' );
																		}
																		onClose();
																	} }
																>
																	<i className="bb-icons-rl bb-icons-rl-eye"></i>
																	{ __( 'View', 'buddyboss' ) }
																	<i className="bb-icons-rl bb-icons-rl-arrow-up-right bb-icons-external"></i>
																</MenuItem>
															) }
															<MenuItem
																onClick={ function () {
																	handleEditForum( forum );
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-note-pencil"></i>
																{ __( 'Edit', 'buddyboss' ) }
															</MenuItem>
															<MenuItem
																isDestructive
																onClick={ function () {
																	handleDeleteForum( forum );
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-trash"></i>
																{ __( 'Delete', 'buddyboss' ) }
															</MenuItem>
														</MenuGroup>
													);
												} }
											</DropdownMenu>
										</td>
									</tr>
								);
							} ) }
						</tbody>
					</table>
				) }
			</div>

			{ /* Footer */ }
			{ ! isLoading && total > 0 && (
				<div className="bb-forums-list__footer">
					<span className="bb-forums-list__item-count">
						{ sprintf(
						/* translators: %s: total number of items. */
						_n( '%s item', '%s items', total, 'buddyboss' ),
						total
					) }
					</span>

					{ totalPages > 1 && (
						<div className="bb-forums-list__pagination">
							<Button
								variant="secondary"
								disabled={ 1 === currentPage }
								onClick={ function () {
									setCurrentPage( function ( p ) {
										return Math.max( 1, p - 1 );
									} );
								} }
								className="bb-forums-list__pagination-btn bb-forums-list__pagination-btn--previous"
							>
								&lsaquo;
							</Button>

							{ getPageNumbers().map( function ( page, index ) {
								if ( '...' === page ) {
									return (
										<span key={ 'ellipsis-' + index } className="bb-forums-list__pagination-ellipsis">
											&hellip;
										</span>
									);
								}
								return (
									<Button
										key={ page }
										variant={ currentPage === page ? 'primary' : 'secondary' }
										onClick={ function () {
											setCurrentPage( page );
										} }
										className={ 'bb-forums-list__pagination-btn' + ( currentPage === page ? ' bb-forums-list__pagination-btn--current' : '' ) }
									>
										{ page }
									</Button>
								);
							} ) }

							<Button
								variant="secondary"
								disabled={ currentPage >= totalPages }
								onClick={ function () {
									setCurrentPage( function ( p ) {
										return Math.min( totalPages, p + 1 );
									} );
								} }
								className="bb-forums-list__pagination-btn bb-forums-list__pagination-btn--next"
							>
								&rsaquo;
							</Button>
						</div>
					) }
				</div>
			) }

			{ /* Bulk Edit Forum Modal */ }
			{ bulkEditOpen && (
				<Modal
					title={ __( 'Bulk Edit', 'buddyboss' ) }
					onRequestClose={ function () {
						setBulkEditOpen( false );
					} }
					className="bb-forum-bulk-edit-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-forum-bulk-edit-modal__body">
						<div className="bb-admin-bulk-modal__selected-items">
							{ selectedForumNames.map( function ( item ) {
								return (
									<div key={ item.id } className="bb-admin-bulk-modal__selected-item">
										<CheckboxControl
											checked={ true }
											onChange={ function () {
												setSelectedIds( function ( prev ) {
													var next = prev.filter( function ( i ) { return i !== item.id; } );
													if ( 0 === next.length ) {
														setBulkEditOpen( false );
													}
													return next;
												} );
											} }
											__nextHasNoMarginBottom
										/>
										<span className="bb-admin-bulk-modal__selected-item-name">
											{ decodeEntities( item.title ) }
										</span>
									</div>
								);
							} ) }
						</div>

						<SelectControl
							label={ __( 'Visibility', 'buddyboss' ) }
							value={ bulkEditVisibility }
							options={ [
								{ value: 'no_change', label: __( '\u2014 No Change \u2014', 'buddyboss' ) },
								{ value: 'publish', label: __( 'Public', 'buddyboss' ) },
								{ value: 'private', label: __( 'Private', 'buddyboss' ) },
								{ value: 'hidden', label: __( 'Hidden', 'buddyboss' ) },
							] }
							onChange={ setBulkEditVisibility }
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="bb-forum-bulk-edit-modal__footer">
						<Button
							variant="secondary"
							onClick={ function () {
								setBulkEditOpen( false );
							} }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleConfirmBulkEdit }
							disabled={ 'no_change' === bulkEditVisibility }
						>
							{ __( 'Save', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }

			{ /* Delete Forum Modal */ }
			{ deleteModalOpen && (
				<Modal
					title={ __( 'Delete Forum?', 'buddyboss' ) }
					onRequestClose={ function () {
						setDeleteModalOpen( false );
					} }
					className="bb-forum-delete-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-forum-delete-modal__body">
						<div className="bb-admin-bulk-modal__selected-items">
							{ deleteTargetForumNames.map( function ( item ) {
								return (
									<div key={ item.id } className="bb-admin-bulk-modal__selected-item">
										<CheckboxControl
											checked={ true }
											onChange={ function () {
												setDeleteTargetIds( function ( prev ) {
													var next = prev.filter( function ( i ) { return i !== item.id; } );
													if ( 0 === next.length ) {
														setDeleteModalOpen( false );
													}
													return next;
												} );
												setSelectedIds( function ( prev ) {
													return prev.filter( function ( i ) { return i !== item.id; } );
												} );
											} }
											__nextHasNoMarginBottom
										/>
										<span className="bb-admin-bulk-modal__selected-item-name">
											{ decodeEntities( item.title ) }
										</span>
									</div>
								);
							} ) }
						</div>
						<div className="bb-admin-delete__warning">
							<i className="bb-icons-rl bb-icons-rl-warning-circle"></i>
							<div className="bb-admin-delete__warning-text">
								<span className="bb-admin-delete__warning-title">
									{ __( 'Warning', 'buddyboss' ) }
								</span>
								<span className="bb-admin-delete__warning-desc">
									{ __( 'This permanently deletes selected forums and all associated discussions and replies. This cannot be undone.', 'buddyboss' ) }
								</span>
							</div>
						</div>
						<p className="bb-forum-delete-modal__description">
							{ __( 'Deleting forums will remove them from the community and all associated discussions and replies will be permanently deleted.', 'buddyboss' ) }
						</p>
						<CheckboxControl
							label={ __( 'I understand this will permanently delete the forum.', 'buddyboss' ) }
							checked={ deleteConfirmChecked }
							onChange={ setDeleteConfirmChecked }
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="bb-forum-delete-modal__footer">
						<Button
							variant="secondary"
							onClick={ function () {
								setDeleteModalOpen( false );
							} }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ handleConfirmDelete }
							disabled={ ! deleteConfirmChecked }
						>
							{ __( 'Delete', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }

			{ /* Edit Loading Overlay */ }
			{ isEditLoading && (
				<div className="bb-forums-list__edit-loading">
					<Spinner />
				</div>
			) }

			{ /* Forum Create Modal */ }
			<ForumCreateModal
				isOpen={ createModalOpen }
				onClose={ function () {
					setCreateModalOpen( false );
				} }
				onCreated={ handleForumCreated }

			/>

			{ /* Forum Edit Modal */ }
			{ null !== editForum && (
				<ForumEditModal
					forum={ editForum }
					onClose={ function () {
						forceRemoveEditor( 'bb-forum-edit-description' );
						setEditForum( null );
					} }
					onSave={ handleSaveForum }
					isSaving={ isEditSaving }
				/>
			) }
		</div>
	);
}

/**
 * Forum Edit Modal Component (inline).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.forum    Forum data from server.
 * @param {Function} props.onClose  Close handler.
 * @param {Function} props.onSave   Save handler.
 * @param {boolean}  props.isSaving Whether save is in progress.
 * @returns {JSX.Element} Edit modal.
 */
function ForumEditModal( { forum, onClose, onSave, isSaving } ) {
	var nameState = useState( forum.name || '' );
	var name = nameState[ 0 ];
	var setName = nameState[ 1 ];

	var slugState = useState( forum.slug || '' );
	var slug = slugState[ 0 ];
	var setSlug = slugState[ 1 ];

	var descriptionState = useState( forum.description || '' );
	var description = descriptionState[ 0 ];
	var setDescription = descriptionState[ 1 ];

	var forumStatusState = useState( forum.forum_status || 'open' );
	var forumStatus = forumStatusState[ 0 ];
	var setForumStatus = forumStatusState[ 1 ];

	var visibilityState = useState( forum.visibility || 'publish' );
	var visibility = visibilityState[ 0 ];
	var setVisibility = visibilityState[ 1 ];

	var parentIdState = useState( forum.parent_id || 0 );
	var parentId = parentIdState[ 0 ];
	var setParentId = parentIdState[ 1 ];

	var imageIdState = useState( forum.featured_image_id || 0 );
	var imageId = imageIdState[ 0 ];
	var setImageId = imageIdState[ 1 ];

	var imageUrlState = useState( forum.featured_image || '' );
	var imageUrl = imageUrlState[ 0 ];
	var setImageUrl = imageUrlState[ 1 ];

	var removeImageState = useState( false );
	var removeImage = removeImageState[ 0 ];
	var setRemoveImage = removeImageState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	var siteUrl = ( window.bbAdminData && window.bbAdminData.siteUrl ) || '';

	/**
	 * Open WordPress media picker for featured image.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSelectImage = function () {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		var frame = window.wp.media( {
			title: __( 'Select Feature Image', 'buddyboss' ),
			button: { text: __( 'Use Image', 'buddyboss' ) },
			multiple: false,
			library: { type: 'image' },
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			setImageId( attachment.id );
			setImageUrl( attachment.url );
			setRemoveImage( false );
		} );

		frame.open();
	};

	/**
	 * Remove featured image.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleRemoveImage = function () {
		setImageId( 0 );
		setImageUrl( '' );
		setRemoveImage( true );
	};

	/**
	 * Handle save.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSave = function () {
		if ( ! name.trim() ) {
			setError( __( 'Forum name is required.', 'buddyboss' ) );
			return;
		}

		setError( '' );
		onSave( {
			forum_id: forum.id,
			name: name.trim(),
			slug: slug,
			description: description,
			visibility: visibility,
			forum_status: forumStatus,
			parent_id: parentId,
			image_id: imageId,
			remove_image: removeImage ? 1 : 0,
		} );
	};

	var statusOptions = [
		{ value: 'open', label: __( 'Open', 'buddyboss' ) },
		{ value: 'closed', label: __( 'Closed', 'buddyboss' ) },
	];

	var visibilityOptions = [
		{ value: 'publish', label: __( 'Public', 'buddyboss' ) },
		{ value: 'private', label: __( 'Private', 'buddyboss' ) },
		{ value: 'hidden', label: __( 'Hidden', 'buddyboss' ) },
	];

	/**
	 * Sanitize a string into a URL-friendly slug.
	 *
	 * @param {string} str Input string.
	 * @returns {string} Slug.
	 */
	var toSlugEdit = function ( str ) {
		return str
			.toLowerCase()
			.replace( /[^a-z0-9\s-]/g, '' )
			.replace( /[\s]+/g, '-' )
			.replace( /-+/g, '-' )
			.replace( /^-|-$/g, '' );
	};

	return (
		<Modal
			title={ __( 'Edit Forum', 'buddyboss' ) }
			onRequestClose={ onClose }
			className="bb-forum-modal bb-forum-edit-modal bb-forum-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-forum-modal__body bb-forum-edit-modal__body">
				{ error && (
					<p className="bb-forum-modal__error">{ error }</p>
				) }

				<TextControl
					label={ __( 'Forum Name', 'buddyboss' ) }
					value={ name }
					onChange={ setName }
					__nextHasNoMarginBottom
				/>

				<div className="bb-forum-modal__permalink-field">
					<TextControl
						label={ __( 'Permalink', 'buddyboss' ) }
						value={ slug }
						onChange={ function ( val ) {
							setSlug( toSlugEdit( val ) );
						} }
						__nextHasNoMarginBottom
					/>
					{ slug && siteUrl && (
						<p className="bb-forum-create-modal__permalink-preview">
							{ siteUrl + '/forum/' + slug + '/' }
						</p>
					) }
				</div>

				<div className="bb-forum-modal__row--separator">
					<RichTextEditor
						id="bb-forum-edit-description"
						label={ __( 'Forum Description (Optional)', 'buddyboss' ) }
						value={ description }
						onChange={ setDescription }
					/>
				</div>

				<div className="bb-forum-create-modal__row bb-forum-modal__row--separator">
					<SelectControl
						label={ __( 'Status', 'buddyboss' ) }
						value={ forumStatus }
						options={ statusOptions }
						onChange={ setForumStatus }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Visibility', 'buddyboss' ) }
						value={ visibility }
						options={ visibilityOptions }
						onChange={ setVisibility }
						__nextHasNoMarginBottom
					/>
				</div>

				<div className="components-base-control bb-forum-modal__row--separator">
					<label className="components-base-control__label">
						{ __( 'Parent Forum', 'buddyboss' ) }
					</label>
					<AsyncSelectField
						value={ String( parentId ) }
						onChange={ function ( val ) {
							setParentId( parseInt( val, 10 ) || 0 );
						} }
						asyncAction="bb_admin_forum_autocomplete"
						placeholder={ __( 'None', 'buddyboss' ) }
					/>
				</div>

				<div className="bb-forum-modal__image-field bb-forum-create-modal__image-field">
					<label className="components-base-control__label">
						{ __( 'Feature Image (Optional)', 'buddyboss' ) }
					</label>
					{ imageUrl ? (
						<div className="bb-forum-modal__image-preview">
							<img src={ imageUrl } alt="" />
							<div className="bb-forum-modal__image-actions">
								<Button
									variant="secondary"
									onClick={ handleSelectImage }
									className="bb-forum-modal__replace-image"
								>
									{ __( 'Replace', 'buddyboss' ) }
								</Button>
								<Button
									variant="secondary"
									isDestructive
									onClick={ handleRemoveImage }
									className="bb-forum-modal__remove-image"
								>
									{ __( 'Reset', 'buddyboss' ) }
								</Button>
							</div>
						</div>
					) : (
						<button
							type="button"
							onClick={ handleSelectImage }
							className="bb-forum-create-modal__upload-zone"
						>
							<span className="bb-forum-create-modal__upload-icon">+</span>
						</button>
					) }
					<p className="bb-forum-create-modal__image-help">
						{ __( 'For best results, use an image at least 1200px by 300px or higher.', 'buddyboss' ) }
					</p>
				</div>
			</div>

			<div className="bb-forum-modal__footer bb-admin-settings-modal__footer">
				<Button
					variant="secondary"
					onClick={ onClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ isSaving }
					disabled={ isSaving || ! name.trim() }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}

export default ForumsListScreen;
