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
	TabPanel,
} from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getForums, getForum, saveForum, forumBulkAction, uploadForumImage } from '../utils/ajax';
import { sanitizeHtml, safeUrl, sanitizeCustomColumns } from '../utils/sanitize';
import { ListPagination } from '../components/common/ListPagination';
import { AdminNotice } from '../components/common/AdminNotice';
import { ListToolbar } from '../components/common/ListToolbar';
import { DeleteConfirmModal } from '../components/common/DeleteConfirmModal';
import { BulkEditModal } from '../components/common/BulkEditModal';
import { useListScreenHandlers } from '../hooks/useListScreenHandlers';
import { useListScreenState } from '../hooks/useListScreenState';
import { toSlug, groupFieldsWithLayout as groupForumFieldsWithLayout, buildRegisteredFieldPayload, getVisibleFields, isFieldConditionalMet, needsSeparator } from '../utils/format';
import { ForumCreateModal } from '../components/forums/ForumCreateModal';
import { RegisteredMetaField } from '../components/common/RegisteredMetaField';
import { forceRemoveEditor } from '../components/common/RichTextEditor';

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

	var bulkActionsState = useState( {} );
	var bulkActions = bulkActionsState[ 0 ];
	var setBulkActions = bulkActionsState[ 1 ];

	var viewsState = useState( {} );
	var views = viewsState[ 0 ];
	var setViews = viewsState[ 1 ];

	var columnsState = useState( {} );
	var columns = columnsState[ 0 ];
	var setColumns = columnsState[ 1 ];

	var forumBaseSlugState = useState( 'forum' );
	var forumBaseSlug = forumBaseSlugState[ 0 ];
	var setForumBaseSlug = forumBaseSlugState[ 1 ];

	var createFieldsState = useState( [] );
	var createFields = createFieldsState[ 0 ];
	var setCreateFields = createFieldsState[ 1 ];

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

	var bulkEditStatusState = useState( 'no_change' );
	var bulkEditStatus = bulkEditStatusState[ 0 ];
	var setBulkEditStatus = bulkEditStatusState[ 1 ];

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

	var refetchCounterState = useState( 0 );
	var refetchCounter = refetchCounterState[ 0 ];
	var setRefetchCounter = refetchCounterState[ 1 ];

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
				setForums( sanitizeCustomColumns( response.data.forums || [] ) );
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
				if ( response.data.forum_base_slug ) {
					setForumBaseSlug( response.data.forum_base_slug );
				}
				if ( response.data.create_fields ) {
					setCreateFields( response.data.create_fields );
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
		setFilter: setFilter,
		getItemIds: function () {
			return forums.map( function ( f ) { return f.id; } );
		},
	} );
	var handleSearchChange = handlers.handleSearchChange;
	var handleFilterChange = handlers.handleFilterChange;
	var handleSortChange = handlers.handleSortChange;
	var handleSelectAll = handlers.handleSelectAll;
	var handleSelectRow = handlers.handleSelectRow;

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
			setBulkEditStatus( 'no_change' );
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
			edit_status: bulkEditStatus,
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

	return (
		<div className="bb-forums-list">
			{ /* Notice */ }
			<AdminNotice notice={ notice } onDismiss={ function () { setNotice( null ); } } />

			{ /* Header */ }
			<div className="bb-forums-list__header">
				<h2 className="bb-forums-list__title">{ __( 'Forums', 'buddyboss' ) }</h2>
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
			<ListToolbar
				className="bb-forums-list"
				bulkAction={ bulkAction }
				bulkActions={ bulkActions }
				onBulkActionChange={ setBulkAction }
				onBulkApply={ handleBulkApply }
				selectedCount={ selectedIds.length }
				isBulkProcessing={ isBulkProcessing }
				searchInput={ searchInput }
				onSearchChange={ handleSearchChange }
				searchPlaceholder={ __( 'Search forums', 'buddyboss' ) }
			>
				<SelectControl
					value={ filter }
					options={ filterOptions }
					onChange={ handleFilterChange }
					className="bb-forums-list__filter-select"
					__nextHasNoMarginBottom
				/>
				<SelectControl
					value={ sortBy }
					options={ sortOptions }
					onChange={ handleSortChange }
					className="bb-forums-list__sort-select"
					__nextHasNoMarginBottom
				/>
			</ListToolbar>

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
													<span dangerouslySetInnerHTML={ { __html: sanitizeHtml( forum.custom_columns[ key ] ) } } />
												</td>
											);
										} ) }
										<td className="bb-forums-list__td--actions bb-admin-actions-toggle">
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
			{ ! isLoading && (
				<ListPagination
					currentPage={ currentPage }
					totalPages={ totalPages }
					total={ total }
					onPageChange={ function ( page ) { setCurrentPage( page ); } }
					className="bb-forums-list"
				/>
			) }

			{ /* Bulk Edit Forum Modal */ }
			<BulkEditModal
				isOpen={ bulkEditOpen }
				items={ selectedForumNames }
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
				confirmDisabled={ 'no_change' === bulkEditStatus && 'no_change' === bulkEditVisibility }
				className="bb-forum-bulk-edit-modal"
			>
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

			{ /* Delete Forum Modal */ }
			<DeleteConfirmModal
				isOpen={ deleteModalOpen }
				singleTitle={ __( 'Delete forum?', 'buddyboss' ) }
				items={ deleteTargetForumNames }
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
				warningText={ __( 'This permanently deletes forum from the community and cannot be undone.', 'buddyboss' ) }
				description={ __( 'Deletes the forum and all associated discussions, replies, media, and related content from the community. This action cannot be undone.', 'buddyboss' ) }
				confirmLabel={ __( 'I understand that this deletes the forum and its discussions.', 'buddyboss' ) }
				confirmChecked={ deleteConfirmChecked }
				onConfirmChange={ setDeleteConfirmChecked }
				onConfirm={ handleConfirmDelete }
				onClose={ function () { setDeleteModalOpen( false ); } }
				className="bb-forum-delete-modal"
			/>

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
				forumBaseSlug={ forumBaseSlug }
				createFields={ createFields }
			/>

			{ /* Forum Edit Modal */ }
			{ null !== editForum && (
				<ForumEditModal
					forum={ editForum }
					onClose={ function () {
						// Clean up TinyMCE editors for richtext registry fields.
						if ( editForum && editForum.registered_fields ) {
							editForum.registered_fields.forEach( function ( field ) {
								if ( 'richtext' === field.type ) {
									forceRemoveEditor( 'bb-admin-edit-' + field.id + '-' + editForum.id );
								}
							} );
						}
						setEditForum( null );
					} }
					onSave={ handleSaveForum }
					isSaving={ isEditSaving }
					forumBaseSlug={ forumBaseSlug }
				/>
			) }
		</div>
	);
}

/**
 * Tab label mapping for forum edit modal.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Object}
 */
var forumTabLabels = {
	details: __( 'Details', 'buddyboss' ),
};

/**
 * Tab order for known tabs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Object}
 */
var forumTabOrder = {
	details: 1,
};

/**
 * Determine if a field should be disabled for group forums.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string}  fieldId            Field ID.
 * @param {boolean} isGroupForum       Whether forum belongs to a group.
 * @param {boolean} isGroupForumChild  Whether forum is child of a group forum.
 * @returns {boolean} True if the field should be disabled.
 */
function isForumFieldDisabled( fieldId, isGroupForum, isGroupForumChild ) {
	// Visibility: disabled for group forum children.
	if ( 'visibility' === fieldId ) {
		return isGroupForumChild;
	}
	// Type and Parent: disabled for direct group forums.
	if ( 'forum_type' === fieldId || 'parent_id' === fieldId ) {
		return isGroupForum;
	}
	return false;
}

/**
 * Forum Edit Modal Component — uses BB_Admin_Meta_Field_Registry.
 *
 * Renders registered fields via RegisteredMetaField with tabbed layout.
 * Featured image remains a custom section (not in registry).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.forum          Forum data from server (includes registered_fields).
 * @param {Function} props.onClose        Close handler.
 * @param {Function} props.onSave         Save handler.
 * @param {boolean}  props.isSaving       Whether save is in progress.
 * @param {string}   props.forumBaseSlug  Forum base slug.
 * @returns {JSX.Element} Edit modal.
 */
function ForumEditModal( props ) {
	var forum = props.forum;
	var onClose = props.onClose;
	var onSave = props.onSave;
	var isSaving = props.isSaving;

	// All registered field values keyed by field ID.
	var registeredValuesState = useState( {} );
	var registeredValues = registeredValuesState[ 0 ];
	var setRegisteredValues = registeredValuesState[ 1 ];

	// Featured image state (not in registry).
	var imageIdState = useState( forum.featured_image_id || 0 );
	var imageId = imageIdState[ 0 ];
	var setImageId = imageIdState[ 1 ];

	var imageUrlState = useState( forum.featured_image || '' );
	var imageUrl = imageUrlState[ 0 ];
	var setImageUrl = imageUrlState[ 1 ];

	var removeImageState = useState( false );
	var removeImage = removeImageState[ 0 ];
	var setRemoveImage = removeImageState[ 1 ];

	var isUploadingState = useState( false );
	var isUploading = isUploadingState[ 0 ];
	var setIsUploading = isUploadingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	var fileInputRef = useRef( null );
	var uploadAbortRef = useRef( null );

	// Track mounted state.
	var isMountedRef = useRef( true );
	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
			if ( uploadAbortRef.current ) {
				uploadAbortRef.current.abort();
			}
		};
	}, [] );

	// Initialize registered values from forum data.
	useEffect( function () {
		if ( forum && forum.registered_fields && Array.isArray( forum.registered_fields ) ) {
			var initialValues = {};
			forum.registered_fields.forEach( function ( field ) {
				initialValues[ field.id ] = field.value;
			} );
			setRegisteredValues( initialValues );
		}
	}, [ forum ] );

	var isGroupForum = ! ! forum.is_group_forum;
	var isGroupForumChild = ! ! forum.is_group_forum_child;

	/**
	 * Handle change for a registered field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} fieldId Field ID.
	 * @param {*}      val     New value.
	 */
	var handleRegisteredFieldChange = useCallback( function ( fieldId, val ) {
		setRegisteredValues( function ( prev ) {
			var next = {};
			Object.keys( prev ).forEach( function ( k ) {
				next[ k ] = prev[ k ];
			} );
			next[ fieldId ] = val;
			return next;
		} );
	}, [] );

	// Build tabs from registered fields.
	var tabs = useMemo( function () {
		if ( ! forum || ! forum.registered_fields ) {
			return [];
		}

		var tabKeys = {};
		forum.registered_fields.forEach( function ( field ) {
			if ( field.tab && field.visible ) {
				tabKeys[ field.tab ] = true;
			}
		} );

		var tabArray = Object.keys( tabKeys ).map( function ( key ) {
			return {
				name: key,
				title: forumTabLabels[ key ] || key.charAt( 0 ).toUpperCase() + key.slice( 1 ),
				order: forumTabOrder[ key ] || 100,
			};
		} );

		tabArray.sort( function ( a, b ) {
			return a.order - b.order;
		} );

		return tabArray;
	}, [ forum ] );

	/**
	 * Trigger hidden file input for image selection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var triggerFileInput = function () {
		if ( fileInputRef.current ) {
			fileInputRef.current.value = '';
			fileInputRef.current.click();
		}
	};

	/**
	 * Handle file selection and upload via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e File input change event.
	 */
	var handleFileSelect = function ( e ) {
		var file = e.target.files && e.target.files[ 0 ];
		if ( ! file ) {
			return;
		}

		var allowedTypes = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
		if ( -1 === allowedTypes.indexOf( file.type ) ) {
			setError( __( 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.', 'buddyboss' ) );
			return;
		}

		if ( file.size > 10 * 1024 * 1024 ) {
			setError( __( 'File size exceeds the maximum allowed size of 10MB.', 'buddyboss' ) );
			return;
		}

		if ( uploadAbortRef.current ) {
			uploadAbortRef.current.abort();
		}
		uploadAbortRef.current = new AbortController();

		setIsUploading( true );
		setError( '' );

		uploadForumImage( file, uploadAbortRef.current.signal ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsUploading( false );
			if ( response.success && response.data ) {
				setImageId( response.data.attachment_id );
				setImageUrl( response.data.url );
				setRemoveImage( false );
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to upload image.', 'buddyboss' ) );
			}
		} ).catch( function ( err ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsUploading( false );
			if ( 'AbortError' !== err.name ) {
				setError( __( 'An error occurred while uploading. Please try again.', 'buddyboss' ) );
			}
		} );
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
	 * Handle save — collects all registered field values + image data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSave = function () {
		var nameVal = registeredValues.name || '';
		if ( ! nameVal.trim() ) {
			setError( __( 'Forum name is required.', 'buddyboss' ) );
			return;
		}

		setError( '' );

		// buildRegisteredFieldPayload emits both plain keys and registered_field_* keys automatically.
		var registeredPayload = forum.registered_fields
			? buildRegisteredFieldPayload( forum.registered_fields, registeredValues, forum.id )
			: {};

		var payload = Object.assign( registeredPayload, {
			forum_id: forum.id,
			name: ( registeredValues.name || '' ).trim(), // Override with trimmed value.
			image_id: imageId,            // Custom section, not in registry.
			remove_image: removeImage ? 1 : 0,
		} );

		onSave( payload );
	};

	/**
	 * Render registered meta fields for a specific tab.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} tabName Tab name to render fields for.
	 * @returns {Array|null} Rendered field elements or null.
	 */
	var renderTabFields = function ( tabName ) {
		if ( ! forum.registered_fields ) {
			return null;
		}

		var tabFields = forum.registered_fields.filter( function ( field ) {
			return field.tab === tabName && field.visible && isFieldConditionalMet( field, registeredValues );
		} );

		if ( 0 === tabFields.length ) {
			return null;
		}

		var grouped = groupForumFieldsWithLayout( tabFields );

		return grouped.map( function ( item, idx ) {
			var hasSeparator = needsSeparator( item, grouped[ idx + 1 ] );

			if ( 'row' === item.type ) {
				return (
					<div key={ 'row-' + idx } className={ 'bb-admin-meta-field__row bb-admin-settings-modal__row' + ( hasSeparator ? ' bb-admin-settings-modal__row--separator' : '' ) }>
						{ item.fields.map( function ( field ) {
							return (
								<RegisteredMetaField
									key={ field.id + '-' + forum.id }
									field={ field }
									value={ registeredValues[ field.id ] }
									onChange={ function ( val ) {
										handleRegisteredFieldChange( field.id, val );
									} }
									itemId={ forum.id }
									disabled={ isForumFieldDisabled( field.id, isGroupForum, isGroupForumChild ) }
								/>
							);
						} ) }
					</div>
				);
			}
			return (
				<div key={ item.field.id + '-' + forum.id } className={ 'components-base-control ' + ( hasSeparator ? 'bb-admin-settings-modal__row--separator' : '' ) }>
					<RegisteredMetaField
						field={ item.field }
						value={ registeredValues[ item.field.id ] }
						onChange={ function ( val ) {
							handleRegisteredFieldChange( item.field.id, val );
						} }
						itemId={ forum.id }
						disabled={ isForumFieldDisabled( item.field.id, isGroupForum, isGroupForumChild ) }
					/>
				</div>
			);
		} );
	};

	/**
	 * Render featured image section (custom, not registry-based).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @returns {JSX.Element} Featured image section.
	 */
	var renderFeaturedImage = function () {
		return (
			<div className="bb-forum-modal__image-field bb-forum-create-modal__image-field">
				<label className="components-base-control__label" htmlFor="bb-forum-edit-image">
					{ __( 'Feature Image (Optional)', 'buddyboss' ) }
				</label>
				<input
					type="file"
					ref={ fileInputRef }
					accept="image/jpeg,image/png,image/gif,image/webp"
					onChange={ handleFileSelect }
					style={ { display: 'none' } }
				/>
				{ imageUrl ? (
					<div className="bb-forum-modal__image-preview">
						<img src={ safeUrl( imageUrl ) } alt="" />
						<div className="bb-forum-modal__image-actions">
							<Button
								variant="secondary"
								onClick={ triggerFileInput }
								className="bb-forum-modal__replace-image"
								disabled={ isUploading }
							>
								{ __( 'Replace', 'buddyboss' ) }
							</Button>
							<Button
								variant="secondary"
								isDestructive
								onClick={ handleRemoveImage }
								className="bb-forum-modal__remove-image"
								disabled={ isUploading }
							>
								{ __( 'Reset', 'buddyboss' ) }
							</Button>
						</div>
					</div>
				) : (
					<button
						type="button"
						onClick={ triggerFileInput }
						className={ 'bb-forum-create-modal__upload-zone' + ( isUploading ? ' bb-forum-create-modal__upload-zone--uploading' : '' ) }
						disabled={ isUploading }
					>
						{ isUploading ? (
							<span className="bb-forum-create-modal__upload-spinner"></span>
						) : (
							<span className="bb-forum-create-modal__upload-icon"><i className="bb-icons-rl-plus"></i></span>
						) }
					</button>
				) }
				<p className="bb-forum-create-modal__image-help">
					{ __( 'For best results, use an image at least 1500px by 300px or higher.', 'buddyboss' ) }
				</p>
			</div>
		);
	};

	/**
	 * Render tab content.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} tab Tab object with name property.
	 * @returns {JSX.Element} Tab content.
	 */
	var renderTabContent = function ( tab ) {
		if ( 'details' === tab.name ) {
			return (
				<div className="bb-forum-edit-modal__tab-content">
					{ renderTabFields( tab.name ) }
					{ renderFeaturedImage() }
				</div>
			);
		}

		return (
			<div className="bb-forum-edit-modal__tab-content">
				{ renderTabFields( tab.name ) }
			</div>
		);
	};

	return (
		<Modal
			title={ __( 'Edit Forum', 'buddyboss' ) }
			onRequestClose={ function () {
				if ( ! isSaving ) {
					onClose();
				}
			} }
			className="bb-forum-modal bb-forum-edit-modal bb-forum-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-forum-modal__body bb-forum-edit-modal__body bb-admin-settings-modal__body">
				{ error && (
					<p className="bb-forum-modal__error">{ error }</p>
				) }

				{ tabs.length > 1 ? (
					<TabPanel
						className="bb-forum-edit-modal__tabs"
						tabs={ tabs }
					>
						{ renderTabContent }
					</TabPanel>
				) : (
					<div className="bb-forum-edit-modal__tab-content">
						{ renderTabFields( 'details' ) }
						{ renderFeaturedImage() }
					</div>
				) }
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
					disabled={ isSaving || isUploading }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}

export default ForumsListScreen;
