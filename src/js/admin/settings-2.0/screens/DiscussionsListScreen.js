/**
 * BuddyBoss Admin Settings 2.0 - Discussions List Screen
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
import { getDiscussions, getDiscussion, saveDiscussion, discussionBulkAction } from '../utils/ajax';
import { sanitizeHtml, safeUrl, sanitizeCustomColumns } from '../utils/sanitize';
import { ListPagination } from '../components/common/ListPagination';
import { AdminNotice } from '../components/common/AdminNotice';
import { ListToolbar } from '../components/common/ListToolbar';
import { DeleteConfirmModal } from '../components/common/DeleteConfirmModal';
import { BulkEditModal } from '../components/common/BulkEditModal';
import { useListScreenHandlers } from '../hooks/useListScreenHandlers';
import { useListScreenState } from '../hooks/useListScreenState';
import { DiscussionCreateModal } from '../components/forums/DiscussionCreateModal';
import { AsyncSelectField } from '../components/fields/AsyncSelectField';
import { RichTextEditor, forceRemoveEditor } from '../components/common/RichTextEditor';
import { TagsAutocomplete } from '../components/forums/TagsAutocomplete';

/**
 * Sort options for the discussions list dropdown (static, never changes).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var sortOptions = [
	{ label: __( 'Newest', 'buddyboss' ), value: 'newest' },
	{ label: __( 'Oldest', 'buddyboss' ), value: 'oldest' },
	{ label: __( 'Highest Replies', 'buddyboss' ), value: 'highest_replies' },
	{ label: __( 'Lowest Replies', 'buddyboss' ), value: 'lowest_replies' },
	{ label: __( 'Highest Members', 'buddyboss' ), value: 'highest_members' },
	{ label: __( 'Lowest Members', 'buddyboss' ), value: 'lowest_members' },
];

/**
 * Number of discussions to fetch per page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {number}
 */
var DISCUSSIONS_PER_PAGE = 20;

/**
 * Core column keys that are rendered natively by the React UI.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var CORE_COLUMNS = [ 'cb', 'title', 'bbp_topic_author', 'bbp_topic_forum', 'bbp_topic_reply_count', 'bbp_topic_voice_count', 'bbp_topic_freshness' ];

/**
 * Discussions List Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation handler.
 * @returns {JSX.Element} Discussions list screen.
 */
export function DiscussionsListScreen( { onNavigate } ) {
	// Common list screen state (loading, notice, selection, bulk, search).
	var common = useListScreenState();
	var isLoading = common.isLoading;
	var setIsLoading = common.setIsLoading;
	var notice = common.notice;
	var setNotice = common.setNotice;
	var selectedIds = common.selectedIds;
	var setSelectedIds = common.setSelectedIds;
	var bulkAction = common.bulkAction;
	var setBulkAction = common.setBulkAction;
	var isBulkProcessing = common.isBulkProcessing;
	var setIsBulkProcessing = common.setIsBulkProcessing;
	var searchInput = common.searchInput;
	var setSearchInput = common.setSearchInput;
	var searchQuery = common.searchQuery;
	var setSearchQuery = common.setSearchQuery;

	// Screen-specific state.
	var discussionsState = useState( [] );
	var discussions = discussionsState[ 0 ];
	var setDiscussions = discussionsState[ 1 ];

	var totalState = useState( 0 );
	var total = totalState[ 0 ];
	var setTotal = totalState[ 1 ];

	var currentPageState = useState( 1 );
	var currentPage = currentPageState[ 0 ];
	var setCurrentPage = currentPageState[ 1 ];

	var forumFilterState = useState( '0' );
	var forumFilter = forumFilterState[ 0 ];
	var setForumFilter = forumFilterState[ 1 ];

	var sortByState = useState( 'newest' );
	var sortBy = sortByState[ 0 ];
	var setSortBy = sortByState[ 1 ];

	var bulkActionsState = useState( {} );
	var bulkActions = bulkActionsState[ 0 ];
	var setBulkActions = bulkActionsState[ 1 ];

	var viewsState = useState( {} );
	var views = viewsState[ 0 ];
	var setViews = viewsState[ 1 ];

	var columnsState = useState( {} );
	var columns = columnsState[ 0 ];
	var setColumns = columnsState[ 1 ];

	var deleteModalState = useState( false );
	var deleteModalOpen = deleteModalState[ 0 ];
	var setDeleteModalOpen = deleteModalState[ 1 ];

	var deleteTargetIdsState = useState( [] );
	var deleteTargetIds = deleteTargetIdsState[ 0 ];
	var setDeleteTargetIds = deleteTargetIdsState[ 1 ];

	var deleteConfirmState = useState( false );
	var deleteConfirmChecked = deleteConfirmState[ 0 ];
	var setDeleteConfirmChecked = deleteConfirmState[ 1 ];

	var createModalState = useState( false );
	var createModalOpen = createModalState[ 0 ];
	var setCreateModalOpen = createModalState[ 1 ];

	var editDiscussionState = useState( null );
	var editDiscussion = editDiscussionState[ 0 ];
	var setEditDiscussion = editDiscussionState[ 1 ];

	var isEditLoadingState = useState( false );
	var isEditLoading = isEditLoadingState[ 0 ];
	var setIsEditLoading = isEditLoadingState[ 1 ];

	var isEditSavingState = useState( false );
	var isEditSaving = isEditSavingState[ 0 ];
	var setIsEditSaving = isEditSavingState[ 1 ];

	var refetchCounterState = useState( 0 );
	var refetchCounter = refetchCounterState[ 0 ];
	var setRefetchCounter = refetchCounterState[ 1 ];

	// Bulk edit modal state.
	var bulkEditOpenState = useState( false );
	var bulkEditOpen = bulkEditOpenState[ 0 ];
	var setBulkEditOpen = bulkEditOpenState[ 1 ];

	var bulkEditTypeState = useState( 'no_change' );
	var bulkEditType = bulkEditTypeState[ 0 ];
	var setBulkEditType = bulkEditTypeState[ 1 ];

	var bulkEditVisibilityState = useState( 'no_change' );
	var bulkEditVisibility = bulkEditVisibilityState[ 0 ];
	var setBulkEditVisibility = bulkEditVisibilityState[ 1 ];

	var bulkEditStatusState = useState( 'no_change' );
	var bulkEditStatus = bulkEditStatusState[ 0 ];
	var setBulkEditStatus = bulkEditStatusState[ 1 ];

	var bulkEditTagsState = useState( '' );
	var bulkEditTags = bulkEditTagsState[ 0 ];
	var setBulkEditTags = bulkEditTagsState[ 1 ];

	// Read tag_id from URL params (e.g. linked from Discussion Tags count).
	var urlTagIdState = useState( function () {
		var params = new URLSearchParams( window.location.search );
		return params.get( 'tag_id' ) ? parseInt( params.get( 'tag_id' ), 10 ) : 0;
	} );
	var urlTagId = urlTagIdState[ 0 ];

	var hasMetaRef = useRef( false );
	var editAbortRef = useRef( null );

	var totalPages = Math.ceil( total / DISCUSSIONS_PER_PAGE );

	/**
	 * Fetch discussions from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} options Optional fetch options.
	 */
	var fetchDiscussions = useCallback( function ( options ) {
		setIsLoading( true );

		var fetchOptions = {};
		if ( options && options.signal ) {
			fetchOptions.signal = options.signal;
		}

		var fetchData = {
			page: currentPage,
			per_page: DISCUSSIONS_PER_PAGE,
			search: searchQuery,
			forum_id: forumFilter,
			sort: sortBy,
			include_meta: hasMetaRef.current ? 0 : 1,
		};
		if ( urlTagId ) {
			fetchData.tag_id = urlTagId;
		}

		getDiscussions( fetchData, fetchOptions ).then( function ( response ) {
			if ( response.success && response.data ) {
				setDiscussions( sanitizeCustomColumns( response.data.discussions || [] ) );
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
				message: __( 'Failed to load discussions. Please try again.', 'buddyboss' ),
			} );
		} );
	}, [ currentPage, searchQuery, forumFilter, sortBy, urlTagId, refetchCounter ] );

	// Fetch on mount and when filters change.
	useEffect( function () {
		var controller = new AbortController();
		fetchDiscussions( { signal: controller.signal } );
		return function () {
			controller.abort();
		};
	}, [ fetchDiscussions ] );

	// Cleanup on unmount.
	useEffect( function () {
		return function () {
			if ( handlers.searchTimerRef.current ) {
				clearTimeout( handlers.searchTimerRef.current );
			}
			if ( editAbortRef.current ) {
				editAbortRef.current.abort();
			}
		};
	}, [] );

	// Common list screen handlers (search, sort, filter, select).
	var handlers = useListScreenHandlers( {
		setSearchInput: setSearchInput,
		setSearchQuery: setSearchQuery,
		setPage: setCurrentPage,
		setSelectedIds: setSelectedIds,
		setSort: setSortBy,
		setFilter: setForumFilter,
		getItemIds: function () {
			return discussions.map( function ( d ) { return d.id; } );
		},
	} );
	var handleSearchChange = handlers.handleSearchChange;
	var handleFilterChange = handlers.handleFilterChange;
	var handleSortChange = handlers.handleSortChange;
	var handleSelectAll = handlers.handleSelectAll;
	var handleSelectRow = handlers.handleSelectRow;

	/**
	 * Reset metadata and refetch the discussions list from page 1.
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
	 * Perform bulk action on discussions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} action    The action key (delete/edit).
	 * @param {Array}  ids       Discussion ID(s).
	 * @param {Object} extraData Optional extra data for edit actions.
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
		discussionBulkAction( idArray, action, extraData ).then( function ( response ) {
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
			setBulkEditType( 'no_change' );
			setBulkEditVisibility( 'no_change' );
			setBulkEditStatus( 'no_change' );
			setBulkEditOpen( true );
			return;
		}

		performAction( action, selectedIds );
	};

	/**
	 * Handle single discussion delete.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} disc The discussion object.
	 */
	var handleDeleteDiscussion = function ( disc ) {
		setDeleteTargetIds( [ disc.id ] );
		setDeleteConfirmChecked( false );
		setDeleteModalOpen( true );
	};

	/**
	 * Handle spam toggle for a single discussion.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} disc Discussion object.
	 */
	var handleSpamToggle = function ( disc ) {
		discussionBulkAction( [ disc.id ], 'spam' ).then( function ( response ) {
			if ( response.success ) {
				setNotice( {
					message: 'spam' === disc.post_status
						? __( 'Discussion unmarked as spam.', 'buddyboss' )
						: __( 'Discussion marked as spam.', 'buddyboss' ),
					type: 'success',
				} );
				resetAndRefetch();
			} else {
				setNotice( {
					message: __( 'Failed to update discussion.', 'buddyboss' ),
					type: 'error',
				} );
			}
		} ).catch( function () {
			setNotice( {
				message: __( 'Failed to update discussion.', 'buddyboss' ),
				type: 'error',
			} );
		} );
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
	 * Confirm bulk edit from the bulk edit modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleConfirmBulkEdit = function () {
		setBulkEditOpen( false );
		var editData = {
			edit_type: bulkEditType,
			edit_visibility: bulkEditVisibility,
			edit_status: bulkEditStatus,
		};
		if ( bulkEditTags ) {
			editData.edit_tags = bulkEditTags;
		}
		performAction( 'edit', selectedIds, editData );
	};

	/**
	 * Handle opening the edit modal for a discussion.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} disc The discussion object.
	 */
	var handleEditDiscussion = function ( disc ) {
		if ( editAbortRef.current ) {
			editAbortRef.current.abort();
		}
		editAbortRef.current = new AbortController();

		setIsEditLoading( true );
		getDiscussion( disc.id, { signal: editAbortRef.current.signal } ).then( function ( response ) {
			setIsEditLoading( false );
			if ( response.success && response.data ) {
				setEditDiscussion( response.data );
			} else {
				setNotice( { type: 'error', message: ( response.data && response.data.message ) || __( 'Failed to load discussion data.', 'buddyboss' ) } );
			}
		} ).catch( function ( err ) {
			if ( err && 'AbortError' === err.name ) {
				return;
			}
			setIsEditLoading( false );
			setNotice( { type: 'error', message: __( 'An error occurred loading discussion data.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Handle saving the edit modal data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} payload Save payload.
	 */
	var handleSaveDiscussion = function ( payload ) {
		setIsEditSaving( true );
		saveDiscussion( payload ).then( function ( response ) {
			setIsEditSaving( false );
			if ( response.success ) {
				setEditDiscussion( null );
				setNotice( { type: 'success', message: response.data.message } );
				resetAndRefetch();
			} else {
				setNotice( { type: 'error', message: ( response.data && response.data.message ) || __( 'Failed to save discussion.', 'buddyboss' ) } );
			}
		} ).catch( function ( err ) {
			setIsEditSaving( false );
			setNotice( { type: 'error', message: err.message || __( 'An error occurred saving the discussion.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Handle discussion created successfully.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleDiscussionCreated = function () {
		setCreateModalOpen( false );
		setNotice( { type: 'success', message: __( 'Discussion created successfully.', 'buddyboss' ) } );
		resetAndRefetch();
	};

	// Compute custom column keys.
	var customColumnKeys = useMemo( function () {
		return Object.keys( columns ).filter( function ( key ) {
			return CORE_COLUMNS.indexOf( key ) === -1;
		} );
	}, [ columns ] );

	// Build forum filter options from views.
	var filterOptions = useMemo( function () {
		var options = [];
		if ( views && 'undefined' !== typeof views.all ) {
			options.push( { label: sprintf( __( 'All Forums (%d)', 'buddyboss' ), views.all ), value: '0' } );
			if ( views.forums && Array.isArray( views.forums ) ) {
				views.forums.forEach( function ( f ) {
					options.push( {
						label: decodeEntities( f.name ) + ' (' + f.count + ')',
						value: String( f.id ),
					} );
				} );
			}
		} else {
			options.push( { label: __( 'All Forums', 'buddyboss' ), value: '0' } );
		}
		return options;
	}, [ views ] );

	var allSelected = discussions.length > 0 && selectedIds.length === discussions.length;

	// Get selected discussion titles for bulk edit modal pills.
	var selectedDiscussionNames = useMemo( function () {
		var nameMap = {};
		discussions.forEach( function ( d ) {
			nameMap[ d.id ] = d.title;
		} );
		return selectedIds.map( function ( id ) {
			return { id: id, title: nameMap[ id ] || '#' + id };
		} );
	}, [ selectedIds, discussions ] );

	// Get delete target discussion titles (may differ from selectedIds for single-row delete).
	var deleteTargetDiscussionNames = useMemo( function () {
		var nameMap = {};
		discussions.forEach( function ( d ) {
			nameMap[ d.id ] = d.title;
		} );
		return deleteTargetIds.map( function ( id ) {
			return { id: id, title: nameMap[ id ] || '#' + id };
		} );
	}, [ deleteTargetIds, discussions ] );

	return (
		<div className="bb-discussions-list">
			{ /* Notice */ }
			<AdminNotice notice={ notice } onDismiss={ function () { setNotice( null ); } } />

			{ /* Header */ }
			<div className="bb-discussions-list__header">
				<h2 className="bb-discussions-list__title">{ __( 'Discussions', 'buddyboss' ) }</h2>
				<Button
					variant="primary"
					className="bb-discussions-list__create-btn"
					onClick={ function () {
						setCreateModalOpen( true );
					} }
				>
					<i className="bb-icons-rl bb-icons-rl-plus"></i>
					{ __( 'Start New Discussion', 'buddyboss' ) }
				</Button>
			</div>

			{ /* Toolbar */ }
			<ListToolbar
				className="bb-discussions-list"
				bulkAction={ bulkAction }
				bulkActions={ bulkActions }
				onBulkActionChange={ setBulkAction }
				onBulkApply={ handleBulkApply }
				selectedCount={ selectedIds.length }
				isBulkProcessing={ isBulkProcessing }
				searchInput={ searchInput }
				onSearchChange={ handleSearchChange }
				searchPlaceholder={ __( 'Search discussions', 'buddyboss' ) }
			>
				<SearchableForumFilter
					options={ filterOptions }
					value={ forumFilter }
					onChange={ handleFilterChange }
				/>
				<SelectControl
					value={ sortBy }
					options={ sortOptions }
					onChange={ handleSortChange }
					className="bb-discussions-list__sort-select"
					__nextHasNoMarginBottom
				/>
			</ListToolbar>

			{ /* Table */ }
			<div className="bb-discussions-list__table-wrapper">
				{ isLoading ? (
					<div className="bb-discussions-list__loading">
						<Spinner />
					</div>
				) : 0 === discussions.length ? (
					<div className="bb-discussions-list__empty">
						<p>{ __( 'No discussions found.', 'buddyboss' ) }</p>
					</div>
				) : (
					<table className="bb-discussions-list__table">
						<thead>
							<tr>
								<th className="bb-discussions-list__th--checkbox">
									<CheckboxControl
										checked={ allSelected }
										onChange={ handleSelectAll }
										__nextHasNoMarginBottom
									/>
								</th>
								<th className="bb-discussions-list__th--name">
									{ __( 'Discussion', 'buddyboss' ) }
								</th>
								<th className="bb-discussions-list__th--forum">
									{ __( 'Forum', 'buddyboss' ) }
								</th>
								<th className="bb-discussions-list__th--replies">
									{ __( 'Replies', 'buddyboss' ) }
								</th>
								<th className="bb-discussions-list__th--members">
									{ __( 'Members', 'buddyboss' ) }
								</th>
								<th className="bb-discussions-list__th--last-post">
									{ __( 'Last Post', 'buddyboss' ) }
								</th>
								{ /* Custom columns from bbp_admin_topics_column_headers filter */ }
								{ customColumnKeys.map( function ( key ) {
									return (
										<th key={ key } className={ 'bb-discussions-list__th--custom bb-discussions-list__th--' + key }>
											{ columns[ key ] }
										</th>
									);
								} ) }
								<th className="bb-discussions-list__th--actions">&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							{ discussions.map( function ( disc ) {
								var isSelected = selectedIds.indexOf( disc.id ) !== -1;

								return (
									<tr
										key={ disc.id }
										className={ 'bb-discussions-list__row' + ( isSelected ? ' bb-discussions-list__row--selected' : '' ) }
									>
										<td className="bb-discussions-list__td--checkbox">
											<CheckboxControl
												checked={ isSelected }
												onChange={ function ( checked ) {
													handleSelectRow( disc.id, checked );
												} }
												__nextHasNoMarginBottom
											/>
										</td>
										<td className="bb-discussions-list__td--name">
											<a
												href={ safeUrl( disc.permalink ) }
												target="_blank"
												rel="noopener noreferrer"
												className="bb-discussions-list__discussion-name"
											>
												{ decodeEntities( disc.title ) }
											</a>
										</td>
										<td className="bb-discussions-list__td--forum">
											{ decodeEntities( disc.forum_name ) }
										</td>
										<td className="bb-discussions-list__td--replies">
											<span className="bb-discussions-list__count-cell">
												<i className="bb-icons-rl bb-icons-rl-chats"></i>
												{ disc.reply_count }
											</span>
										</td>
										<td className="bb-discussions-list__td--members">
											<span className="bb-discussions-list__count-cell">
												<i className="bb-icons-rl bb-icons-rl-user"></i>
												{ disc.voice_count }
											</span>
										</td>
										<td className="bb-discussions-list__td--last-post">
											{ disc.last_active ? (
												<span className="bb-discussions-list__date">
													<i className="bb-icons-rl bb-icons-rl-clock"></i>
													{ decodeEntities( disc.last_active ) }
												</span>
											) : (
												<span className="bb-discussions-list__no-activity">
													{ __( 'No Replies', 'buddyboss' ) }
												</span>
											) }
										</td>
										{ /* Custom columns */ }
										{ disc.custom_columns && customColumnKeys.map( function ( key ) {
											return (
												<td key={ key } className={ 'bb-discussions-list__td--custom bb-discussions-list__td--' + key }>
													<span dangerouslySetInnerHTML={ { __html: sanitizeHtml( disc.custom_columns[ key ] ) } } />
												</td>
											);
										} ) }
										<td className="bb-discussions-list__td--actions">
											<DropdownMenu
												icon={ <i className="bb-icons-rl-dots-three"></i> }
												label={ __( 'More options', 'buddyboss' ) }
											>
												{ function ( dropdownProps ) {
													var onClose = dropdownProps.onClose;
													return (
														<MenuGroup className="bb_dropdown_menu_group">
															{ disc.permalink && (
																<MenuItem
																	onClick={ function () {
																		var permalink = safeUrl( disc.permalink );
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
																	handleEditDiscussion( disc );
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-note-pencil"></i>
																{ __( 'Edit', 'buddyboss' ) }
															</MenuItem>
															<MenuItem
																onClick={ function () {
																	handleSpamToggle( disc );
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-warning-circle"></i>
																{ 'spam' === disc.post_status
																	? __( 'Not Spam', 'buddyboss' )
																	: __( 'Spam', 'buddyboss' )
																}
															</MenuItem>
															<MenuItem
																isDestructive
																onClick={ function () {
																	handleDeleteDiscussion( disc );
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
			{ ! isLoading && (
				<ListPagination
					currentPage={ currentPage }
					totalPages={ totalPages }
					total={ total }
					onPageChange={ function ( page ) { setCurrentPage( page ); } }
					className="bb-discussions-list"
				/>
			) }

			{ /* Delete Discussion Modal */ }
			<DeleteConfirmModal
				isOpen={ deleteModalOpen }
				singleTitle={ __( 'Delete discussion?', 'buddyboss' ) }
				items={ deleteTargetDiscussionNames }
				onRemoveItem={ function ( id ) {
					setDeleteTargetIds( function ( prev ) {
						var next = prev.filter( function ( i ) { return i !== id; } );
						if ( 0 === next.length ) {
							setDeleteModalOpen( false );
						}
						return next;
					} );
					setSelectedIds( function ( prev ) {
						return prev.filter( function ( i ) { return i !== id; } );
					} );
				} }
				warningText={ __( 'This permanently deletes discussions from the community and cannot be undone.', 'buddyboss' ) }
				description={ __( 'Deletes the discussions and all associated replies, media, and related content from the community. This action cannot be undone.', 'buddyboss' ) }
				confirmLabel={ __( 'I understand that this deletes the discussions.', 'buddyboss' ) }
				confirmChecked={ deleteConfirmChecked }
				onConfirmChange={ setDeleteConfirmChecked }
				onConfirm={ handleConfirmDelete }
				onClose={ function () { setDeleteModalOpen( false ); } }
				className="bb-discussion-delete-modal"
			/>

			{ /* Bulk Edit Modal */ }
			<BulkEditModal
				isOpen={ bulkEditOpen }
				items={ selectedDiscussionNames }
				onRemoveItem={ function ( id ) {
					setSelectedIds( function ( prev ) {
						var next = prev.filter( function ( i ) { return i !== id; } );
						if ( 0 === next.length ) {
							setBulkEditOpen( false );
						}
						return next;
					} );
				} }
				onConfirm={ handleConfirmBulkEdit }
				onClose={ function () { setBulkEditOpen( false ); } }
				confirmDisabled={ 'no_change' === bulkEditStatus && 'no_change' === bulkEditVisibility && ! bulkEditTags }
				className="bb-discussion-bulk-edit-modal"
			>
				<TagsAutocomplete
					label={ __( 'Tags (Optional)', 'buddyboss' ) }
					value={ bulkEditTags }
					onChange={ setBulkEditTags }
					placeholder={ __( 'Enter tags, separated by commas', 'buddyboss' ) }
				/>
				<SelectControl
					label={ __( 'Status', 'buddyboss' ) }
					value={ bulkEditStatus }
					options={ [
						{ value: 'no_change', label: __( '\u2014 No Change \u2014', 'buddyboss' ) },
						{ value: 'open', label: __( 'Open', 'buddyboss' ) },
						{ value: 'closed', label: __( 'Closed', 'buddyboss' ) },
					] }
					onChange={ setBulkEditStatus }
					__nextHasNoMarginBottom
				/>
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
			</BulkEditModal>

			{ /* Edit Loading Overlay */ }
			{ isEditLoading && (
				<div className="bb-discussions-list__edit-loading">
					<Spinner />
				</div>
			) }

			{ /* Discussion Create Modal */ }
			<DiscussionCreateModal
				isOpen={ createModalOpen }
				onClose={ function () {
					setCreateModalOpen( false );
				} }
				onCreated={ handleDiscussionCreated }
			/>

			{ /* Discussion Edit Modal */ }
			{ null !== editDiscussion && (
				<DiscussionEditModal
					discussion={ editDiscussion }
					onClose={ function () {
						forceRemoveEditor( 'bb-discussion-edit-description' );
						setEditDiscussion( null );
					} }
					onSave={ handleSaveDiscussion }
					isSaving={ isEditSaving }
				/>
			) }
		</div>
	);
}

/**
 * Discussion Edit Modal Component (inline).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {Object}   props.discussion   Discussion data from server.
 * @param {Function} props.onClose      Close handler.
 * @param {Function} props.onSave       Save handler.
 * @param {boolean}  props.isSaving     Whether save is in progress.
 * @returns {JSX.Element} Edit modal.
 */
function DiscussionEditModal( { discussion, onClose, onSave, isSaving } ) {
	var titleState = useState( discussion.title || '' );
	var title = titleState[ 0 ];
	var setTitle = titleState[ 1 ];

	var descriptionState = useState( discussion.description || '' );
	var description = descriptionState[ 0 ];
	var setDescription = descriptionState[ 1 ];

	var forumIdState = useState( discussion.forum_id || 0 );
	var forumId = forumIdState[ 0 ];
	var setForumId = forumIdState[ 1 ];

	var typeState = useState( discussion.type || 'normal' );
	var type = typeState[ 0 ];
	var setType = typeState[ 1 ];

	var topicStatusState = useState( discussion.topic_status || 'open' );
	var topicStatus = topicStatusState[ 0 ];
	var setTopicStatus = topicStatusState[ 1 ];

	var visibilityState = useState( discussion.post_status || 'publish' );
	var visibility = visibilityState[ 0 ];
	var setVisibility = visibilityState[ 1 ];

	var tagNamesState = useState( discussion.tag_names || '' );
	var tagNames = tagNamesState[ 0 ];
	var setTagNames = tagNamesState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	/**
	 * Handle save.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSave = function () {
		if ( ! title.trim() ) {
			setError( __( 'Discussion title is required.', 'buddyboss' ) );
			return;
		}

		setError( '' );
		onSave( {
			topic_id: discussion.id,
			title: title.trim(),
			description: description,
			forum_id: forumId,
			type: type,
			topic_status: topicStatus,
			visibility: visibility,
			tags: tagNames,
		} );
	};

	var typeOptions = [
		{ value: 'normal', label: __( 'Normal', 'buddyboss' ) },
		{ value: 'sticky', label: __( 'Sticky', 'buddyboss' ) },
		{ value: 'super_sticky', label: __( 'Super Sticky', 'buddyboss' ) },
	];

	var statusOptions = [
		{ value: 'open', label: __( 'Open', 'buddyboss' ) },
		{ value: 'closed', label: __( 'Closed', 'buddyboss' ) },
	];

	var visibilityOptions = [
		{ value: 'publish', label: __( 'Public', 'buddyboss' ) },
		{ value: 'private', label: __( 'Private', 'buddyboss' ) },
		{ value: 'hidden', label: __( 'Hidden', 'buddyboss' ) },
	];

	return (
		<Modal
			title={ __( 'Edit Discussion', 'buddyboss' ) }
			onRequestClose={ onClose }
			className="bb-discussion-modal bb-discussion-edit-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-discussion-modal__body">
				{ error && (
					<p className="bb-discussion-modal__error">{ error }</p>
				) }

				<TextControl
					label={ __( 'Title', 'buddyboss' ) }
					value={ title }
					onChange={ setTitle }
					__nextHasNoMarginBottom
				/>

				<div className="bb-discussion-modal__row--separator">
					<RichTextEditor
						id="bb-discussion-edit-description"
						label={ __( 'Description', 'buddyboss' ) }
						value={ description }
						onChange={ setDescription }
					/>
				</div>

				<div className="components-base-control">
					<label className="components-base-control__label" htmlFor="bb-discussion-edit-forum">
						{ __( 'Forum', 'buddyboss' ) }
					</label>
					<AsyncSelectField
						id="bb-discussion-edit-forum"
						value={ String( forumId ) }
						onChange={ function ( val ) {
							setForumId( parseInt( val, 10 ) || 0 );
						} }
						asyncAction="bb_admin_forum_autocomplete"
						placeholder={ __( 'Select Forum', 'buddyboss' ) }
					/>
				</div>

				<div className="bb-discussion-modal__row--separator">
					<SelectControl
						label={ __( 'Type', 'buddyboss' ) }
						value={ type }
						options={ typeOptions }
						onChange={ setType }
						__nextHasNoMarginBottom
					/>
				</div>

				<div className="bb-discussion-modal__row bb-discussion-modal__row--separator">
					<SelectControl
						label={ __( 'Status', 'buddyboss' ) }
						value={ topicStatus }
						options={ statusOptions }
						onChange={ setTopicStatus }
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

				<TagsAutocomplete
					label={ __( 'Tags (Optional)', 'buddyboss' ) }
					value={ tagNames }
					onChange={ setTagNames }
					placeholder={ __( 'Enter tags, separated by commas', 'buddyboss' ) }
				/>
			</div>

			<div className="bb-discussion-modal__footer bb-admin-settings-modal__footer">
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
					disabled={ isSaving || ! title.trim() }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}

/**
 * Searchable Forum Filter Component (inline).
 *
 * A dropdown with local search for filtering discussions by forum.
 * Handles large lists of forums gracefully.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Array}    props.options  Array of { label, value } objects.
 * @param {string}   props.value    Currently selected value.
 * @param {Function} props.onChange Change handler.
 * @returns {JSX.Element} Searchable filter dropdown.
 */
function SearchableForumFilter( { options, value, onChange } ) {
	var isOpenState = useState( false );
	var isOpen = isOpenState[ 0 ];
	var setIsOpen = isOpenState[ 1 ];

	var searchState = useState( '' );
	var search = searchState[ 0 ];
	var setSearch = searchState[ 1 ];

	var wrapperRef = useRef( null );

	// Close on outside click.
	useEffect( function () {
		var handleClickOutside = function ( e ) {
			if ( wrapperRef.current && ! wrapperRef.current.contains( e.target ) ) {
				setIsOpen( false );
				setSearch( '' );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return function () {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [] );

	// Find the label for the current value.
	var currentLabel = '';
	for ( var i = 0; i < options.length; i++ ) {
		if ( options[ i ].value === value ) {
			currentLabel = options[ i ].label;
			break;
		}
	}

	// Filter options by search.
	var filteredOptions = options;
	if ( search.trim() ) {
		var lowerSearch = search.toLowerCase();
		filteredOptions = options.filter( function ( opt ) {
			return opt.label.toLowerCase().indexOf( lowerSearch ) !== -1;
		} );
	}

	return (
		<div className="bb-discussions-list__forum-filter" ref={ wrapperRef }>
			<button
				type="button"
				className="bb-discussions-list__forum-filter-toggle"
				onClick={ function () {
					setIsOpen( ! isOpen );
					setSearch( '' );
				} }
			>
				<span className="bb-discussions-list__forum-filter-label">
					{ currentLabel || __( 'All Forums', 'buddyboss' ) }
				</span>
				<i className={ 'bb-icons-rl bb-icons-rl-caret-' + ( isOpen ? 'up' : 'down' ) }></i>
			</button>
			{ isOpen && (
				<div className="bb-discussions-list__forum-filter-dropdown">
					<div className="bb-discussions-list__forum-filter-search">
						<input
							type="text"
							value={ search }
							onChange={ function ( e ) {
								setSearch( e.target.value );
							} }
							placeholder={ __( 'Search forums...', 'buddyboss' ) }
							className="bb-discussions-list__forum-filter-search-input"
							autoFocus
						/>
					</div>
					<div className="bb-discussions-list__forum-filter-options">
						{ filteredOptions.length > 0 ? (
							filteredOptions.map( function ( opt ) {
								return (
									<button
										key={ opt.value }
										type="button"
										className={ 'bb-discussions-list__forum-filter-option' + ( opt.value === value ? ' bb-discussions-list__forum-filter-option--active' : '' ) }
										onMouseDown={ function ( e ) {
											e.preventDefault();
											onChange( opt.value );
											setIsOpen( false );
											setSearch( '' );
										} }
									>
										{ opt.label }
									</button>
								);
							} )
						) : (
							<span className="bb-discussions-list__forum-filter-no-results">
								{ __( 'No forums found.', 'buddyboss' ) }
							</span>
						) }
					</div>
				</div>
			) }
		</div>
	);
}

export default DiscussionsListScreen;
