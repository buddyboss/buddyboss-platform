/**
 * BuddyBoss Admin Settings 2.0 - Groups List Screen
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
} from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { dateI18n } from '@wordpress/date';
import { decodeEntities } from '@wordpress/html-entities';
import { getGroups, groupBulkAction, getGroup, saveGroup } from '../utils/ajax';
import { sanitizeHtml, safeUrl } from '../utils/sanitize';
import { ListPagination } from '../components/common/ListPagination';
import { AdminNotice } from '../components/common/AdminNotice';
import { ListToolbar } from '../components/common/ListToolbar';
import { useListScreenHandlers } from '../hooks/useListScreenHandlers';
import { useListScreenState } from '../hooks/useListScreenState';
import { GroupCreateModal } from '../components/groups/GroupCreateModal';
import { GroupEditModal } from '../components/groups/GroupEditModal';
import { ConfirmToggleModal } from '../components/modals/ConfirmToggleModal';

/**
 * Sort options for the groups list dropdown (static, never changes).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var sortOptions = [
	{ label: __( 'Newest', 'buddyboss' ), value: 'newest' },
	{ label: __( 'Oldest', 'buddyboss' ), value: 'oldest' },
	{ label: __( 'Highest Users', 'buddyboss' ), value: 'highest_users' },
	{ label: __( 'Lowest Users', 'buddyboss' ), value: 'lowest_users' },
	{ label: __( 'Group Types', 'buddyboss' ), value: 'group_types' },
	{ label: __( 'Last Active', 'buddyboss' ), value: 'last_active' },
];

/**
 * Number of groups to fetch per page in the groups list.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {number}
 */
var GROUPS_PER_PAGE = ( window.bbAdminData && window.bbAdminData.groupsPerPage ) ? parseInt( window.bbAdminData.groupsPerPage, 10 ) : 20;

/**
 * Privacy icon class lookup (static, never changes).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Object}
 */
var PRIVACY_ICONS = {
	'public': 'bb-icons-rl bb-icons-rl-globe-simple',
	'private': 'bb-icons-rl bb-icons-rl-lock-simple',
	'hidden': 'bb-icons-rl bb-icons-rl-eye-slash',
};

/**
 * Privacy labels (static, never changes).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Object}
 */
var PRIVACY_LABELS = {
	'public': __( 'Public', 'buddyboss' ),
	'private': __( 'Private', 'buddyboss' ),
	'hidden': __( 'Hidden', 'buddyboss' ),
};

/**
 * Core column keys that are rendered natively by the React UI.
 * Any columns returned by bp_groups_list_table_get_columns that are NOT
 * in this list will be rendered as custom columns.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var CORE_COLUMNS = [ 'cb', 'comment', 'description', 'status', 'members', 'last_active' ];

/**
 * Groups List Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation handler.
 * @returns {JSX.Element} Groups list screen.
 */
export function GroupsListScreen( { onNavigate } ) {
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
	var groupsState = useState( [] );
	var groups = groupsState[ 0 ];
	var setGroups = groupsState[ 1 ];

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

	// Read `group_type` from URL params (e.g. linked from Group Types count).
	var groupTypeFilterState = useState( function () {
		var params = new URLSearchParams( window.location.search );
		return params.get( 'group_type' ) || '';
	} );
	var groupTypeFilter = groupTypeFilterState[ 0 ];
	var setGroupTypeFilter = groupTypeFilterState[ 1 ];

	var bulkActionsState = useState( {} );
	var bulkActions = bulkActionsState[ 0 ];
	var setBulkActions = bulkActionsState[ 1 ];

	var viewsState = useState( {} );
	var views = viewsState[ 0 ];
	var setViews = viewsState[ 1 ];

	var columnsState = useState( {} );
	var columns = columnsState[ 0 ];
	var setColumns = columnsState[ 1 ];

	var groupTypesState = useState( [] );
	var groupTypes = groupTypesState[ 0 ];
	var setGroupTypes = groupTypesState[ 1 ];

	var deleteModalState = useState( false );
	var deleteModalOpen = deleteModalState[ 0 ];
	var setDeleteModalOpen = deleteModalState[ 1 ];

	var deleteTargetIdsState = useState( [] );
	var deleteTargetIds = deleteTargetIdsState[ 0 ];
	var setDeleteTargetIds = deleteTargetIdsState[ 1 ];

	var deleteConfirmState = useState( false );
	var deleteConfirmChecked = deleteConfirmState[ 0 ];
	var setDeleteConfirmChecked = deleteConfirmState[ 1 ];

	var changeTypeModalState = useState( false );
	var changeTypeModalOpen = changeTypeModalState[ 0 ];
	var setChangeTypeModalOpen = changeTypeModalState[ 1 ];

	var selectedGroupTypeState = useState( '' );
	var selectedGroupType = selectedGroupTypeState[ 0 ];
	var setSelectedGroupType = selectedGroupTypeState[ 1 ];

	var removeTypeModalState = useState( false );
	var removeTypeModalOpen = removeTypeModalState[ 0 ];
	var setRemoveTypeModalOpen = removeTypeModalState[ 1 ];

	var createModalState = useState( false );
	var createModalOpen = createModalState[ 0 ];
	var setCreateModalOpen = createModalState[ 1 ];

	var editGroupState = useState( null );
	var editGroup = editGroupState[ 0 ];
	var setEditGroup = editGroupState[ 1 ];

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

	var totalPages = Math.ceil( total / GROUPS_PER_PAGE );

	/**
	 * Fetch groups from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object}      options        Optional fetch options.
	 * @param {AbortSignal} options.signal AbortController signal to cancel in-flight requests.
	 */
	var fetchGroups = useCallback( function ( options ) {
		setIsLoading( true );

		var fetchOptions = {};
		if ( options && options.signal ) {
			fetchOptions.signal = options.signal;
		}

		getGroups( {
			page: currentPage,
			per_page: GROUPS_PER_PAGE,
			search: searchQuery,
			status: filter,
			sort: sortBy,
			group_type: groupTypeFilter,
			include_meta: hasMetaRef.current ? 0 : 1,
		}, fetchOptions ).then( function ( response ) {
			if ( response.success && response.data ) {
				var rawGroups = response.data.groups || [];

				// Sanitize custom column HTML once at fetch time to avoid DOMParser overhead per render.
				var sanitizedGroups = rawGroups.map( function ( group ) {
					if ( ! group.custom_columns ) {
						return group;
					}
					var sanitizedCols = {};
					Object.keys( group.custom_columns ).forEach( function ( key ) {
						sanitizedCols[ key ] = sanitizeHtml( group.custom_columns[ key ] );
					} );
					return Object.assign( {}, group, { custom_columns: sanitizedCols } );
				} );

				setGroups( sanitizedGroups );
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
				if ( response.data.group_types ) {
					setGroupTypes( response.data.group_types );
				}
				hasMetaRef.current = true;
			}
			setIsLoading( false );
		} ).catch( function ( error ) {
			// Ignore aborted requests — they are expected during cleanup.
			if ( error && 'AbortError' === error.name ) {
				return;
			}
			setIsLoading( false );
			setNotice( {
				type: 'error',
				message: __( 'Failed to load groups. Please try again.', 'buddyboss' ),
			} );
		} );
	}, [ currentPage, searchQuery, filter, sortBy, groupTypeFilter, refetchCounter ] );

	// Fetch on mount and when filters change. Abort stale requests on cleanup.
	useEffect( function () {
		var controller = new AbortController();
		fetchGroups( { signal: controller.signal } );
		return function () {
			controller.abort();
		};
	}, [ fetchGroups ] );

	// Cleanup search debounce timer and edit abort controller on unmount.
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
			return groups.map( function ( g ) { return g.id; } );
		},
	} );
	var handleSearchChange = handlers.handleSearchChange;
	var handleFilterChange = handlers.handleFilterChange;
	var handleSortChange = handlers.handleSortChange;
	var handleSelectAll = handlers.handleSelectAll;
	var handleSelectRow = handlers.handleSelectRow;

	// Group type filter uses the same pattern as handleFilterChange but for a separate state.
	var handleGroupTypeFilterChange = function ( value ) {
		setGroupTypeFilter( value );
		setCurrentPage( 1 );
		setSelectedIds( [] );
	};

	/**
	 * Reset metadata and refetch the groups list from page 1.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var resetAndRefetch = function () {
		hasMetaRef.current = false;
		if ( 1 === currentPage ) {
			// Increment counter to trigger fetchGroups useEffect with proper AbortController.
			setRefetchCounter( function ( prev ) { return prev + 1; } );
		} else {
			setCurrentPage( 1 );
		}
	};

	/**
	 * Perform bulk action on groups.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} action    The action key (delete, change_group_type, remove_group_type).
	 * @param {Array}  ids       Group ID(s).
	 * @param {Object} extraData Optional extra data to send (e.g. { group_type: 'slug' }).
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
		groupBulkAction( idArray, action, extraData ).then( function ( response ) {
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

		if ( 'change_group_type' === action ) {
			setSelectedGroupType( '' );
			setChangeTypeModalOpen( true );
			return;
		}

		if ( 'remove_group_type' === action ) {
			setRemoveTypeModalOpen( true );
			return;
		}

		performAction( action, selectedIds );
	};

	/**
	 * Handle single group delete — opens the delete confirmation modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} group The group object.
	 */
	var handleDeleteGroup = function ( group ) {
		setDeleteTargetIds( [ group.id ] );
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
	 * Confirm change group type from the modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleConfirmChangeType = function () {
		if ( ! selectedGroupType ) {
			return;
		}
		setChangeTypeModalOpen( false );
		performAction( 'change_group_type', selectedIds, { group_type: selectedGroupType } );
	};

	/**
	 * Handle opening the edit modal for a group.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} group The group object.
	 */
	var handleEditGroup = function ( group ) {
		if ( editAbortRef.current ) {
			editAbortRef.current.abort();
		}
		editAbortRef.current = new AbortController();

		setIsEditLoading( true );
		getGroup( group.id, { signal: editAbortRef.current.signal } ).then( function ( response ) {
			setIsEditLoading( false );
			if ( response.success && response.data ) {
				setEditGroup( response.data );
			} else {
				setNotice( { type: 'error', message: ( response.data && response.data.message ) || __( 'Failed to load group data.', 'buddyboss' ) } );
			}
		} ).catch( function ( err ) {
			if ( err && 'AbortError' === err.name ) {
				return;
			}
			setIsEditLoading( false );
			setNotice( { type: 'error', message: __( 'An error occurred loading group data.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Handle saving the edit modal data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} payload Save payload.
	 */
	var handleSaveGroup = function ( payload ) {
		// Extract and remove the members save callback before sending payload.
		var membersSaveFn = payload._membersSave;
		delete payload._membersSave;

		setIsEditSaving( true );
		saveGroup( payload ).then( function ( response ) {
			if ( response.success ) {
				// Process pending member changes after main save succeeds.
				var membersPromise = 'function' === typeof membersSaveFn ? membersSaveFn() : Promise.resolve();
				return ( membersPromise || Promise.resolve() ).then( function () {
					setIsEditSaving( false );
					setEditGroup( null );
					setNotice( { type: 'success', message: response.data.message } );
					resetAndRefetch();
				} );
			}
			setIsEditSaving( false );
			setNotice( { type: 'error', message: ( response.data && response.data.message ) || __( 'Failed to save group.', 'buddyboss' ) } );
		} ).catch( function ( err ) {
			setIsEditSaving( false );
			setNotice( { type: 'error', message: err.message || __( 'An error occurred saving the group.', 'buddyboss' ) } );
		} );
	};

	/**
	 * Handle group created successfully.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleGroupCreated = function () {
		setCreateModalOpen( false );
		setNotice( { type: 'success', message: __( 'Group created successfully.', 'buddyboss' ) } );
		resetAndRefetch();
	};

	/**
	 * Format date for display.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} dateStr Date string.
	 * @returns {string} Formatted date.
	 */
	var formatDate = function ( dateStr ) {
		if ( ! dateStr ) {
			return '';
		}
		// Use dateI18n for locale-aware formatting instead of hardcoded English month names.
		return dateI18n( 'M j, Y', dateStr.replace( ' ', 'T' ) + 'Z' );
	};

	/**
	 * Get privacy badge icon class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} status Group status.
	 * @returns {string} Icon class.
	 */
	var getPrivacyIcon = function ( status ) {
		return PRIVACY_ICONS[ status ] || PRIVACY_ICONS[ 'public' ];
	};

	/**
	 * Get privacy label. Uses server-provided status_label (filtered via
	 * bp_groups_admin_get_group_status) when available, falls back to local.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} group Group object.
	 * @returns {string} Privacy label.
	 */
	var getPrivacyLabel = function ( group ) {
		if ( group.status_label ) {
			return decodeEntities( group.status_label );
		}
		return PRIVACY_LABELS[ group.status ] || group.status;
	};

	// Compute custom column keys (memoized since columns only change on initial fetch).
	// CORE_COLUMNS is defined at module level as a static constant.
	var customColumnKeys = useMemo( function () {
		return Object.keys( columns ).filter( function ( key ) {
			return CORE_COLUMNS.indexOf( key ) === -1;
		} );
	}, [ columns ] );

	// Build filter options from views (memoized to avoid rebuilding on unrelated state changes).
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

	// Group type filter options (memoized).
	var groupTypeOptions = useMemo( function () {
		var options = [ { label: __( 'All Types', 'buddyboss' ), value: '' } ];
		groupTypes.forEach( function ( type ) {
			options.push( { label: decodeEntities( type.label ), value: type.value } );
		} );
		return options;
	}, [ groupTypes ] );

	var allSelected = groups.length > 0 && selectedIds.length === groups.length;

	return (
		<div className="bb-groups-list">
			{ /* Notice */ }
			<AdminNotice notice={ notice } onDismiss={ function () { setNotice( null ); } } />

			{ /* Header */ }
			<div className="bb-groups-list__header">
				<h2 className="bb-groups-list__title">{ __( 'All Groups', 'buddyboss' ) }</h2>
				<Button
					variant="primary"
					className="bb-groups-list__create-btn"
					onClick={ function () {
						setCreateModalOpen( true );
					} }
				>
					<i className="bb-icons-rl bb-icons-rl-plus"></i>
					{ __( 'Create New Group', 'buddyboss' ) }
				</Button>
			</div>

			{ /* Toolbar */ }
			<ListToolbar
				className="bb-groups-list"
				bulkAction={ bulkAction }
				bulkActions={ bulkActions }
				onBulkActionChange={ setBulkAction }
				onBulkApply={ handleBulkApply }
				selectedCount={ selectedIds.length }
				isBulkProcessing={ isBulkProcessing }
				searchInput={ searchInput }
				onSearchChange={ handleSearchChange }
				searchPlaceholder={ __( 'Search groups', 'buddyboss' ) }
			>
				<SelectControl
					value={ filter }
					options={ filterOptions }
					onChange={ handleFilterChange }
					className="bb-groups-list__filter-select"
					__nextHasNoMarginBottom
				/>
				{ groupTypes.length > 0 && (
					<SelectControl
						value={ groupTypeFilter }
						options={ groupTypeOptions }
						onChange={ handleGroupTypeFilterChange }
						className="bb-groups-list__type-filter"
						__nextHasNoMarginBottom
					/>
				) }
				<SelectControl
					value={ sortBy }
					options={ sortOptions }
					onChange={ handleSortChange }
					className="bb-groups-list__sort-select"
					__nextHasNoMarginBottom
				/>
			</ListToolbar>

			{ /* Table */ }
			<div className="bb-groups-list__table-wrapper">
				{ isLoading ? (
					<div className="bb-groups-list__loading bb-admin-list-table__loading">
						<Spinner />
					</div>
				) : 0 === groups.length ? (
					<div className="bb-groups-list__empty bb-admin-list-table__empty">
						<p>{ __( 'No groups found.', 'buddyboss' ) }</p>
					</div>
				) : (
					<table className="bb-groups-list__table bb-admin-list-table">
						<thead>
							<tr>
								<th className="bb-groups-list__th--checkbox bb-admin-list-table__checkbox">
									<CheckboxControl
										checked={ allSelected }
										onChange={ handleSelectAll }
										__nextHasNoMarginBottom
									/>
								</th>
								<th className="bb-groups-list__th--name">
									{ __( 'Name', 'buddyboss' ) }
								</th>
								<th className="bb-groups-list__th--privacy">
									{ __( 'Privacy', 'buddyboss' ) }
								</th>
								<th className="bb-groups-list__th--members">
									{ __( 'Members', 'buddyboss' ) }
								</th>
								<th className="bb-groups-list__th--group-type">
									{ __( 'Group Type', 'buddyboss' ) }
								</th>
								<th className="bb-groups-list__th--last-active">
									{ __( 'Last Active', 'buddyboss' ) }
								</th>
								{ /* Custom columns from bp_groups_list_table_get_columns filter */ }
								{ customColumnKeys.map( function ( key ) {
									return (
										<th key={ key } className={ 'bb-groups-list__th--custom bb-groups-list__th--' + key }>
											{ columns[ key ] }
										</th>
									);
								} ) }
								<th className="bb-groups-list__th--actions">&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							{ groups.map( function ( group ) {
								var isSelected = selectedIds.indexOf( group.id ) !== -1;

								return (
									<tr
										key={ group.id }
										className={ 'bb-groups-list__row bb-admin-list-table__row' + ( isSelected ? ' bb-groups-list__row--selected bb-admin-list-table__row--selected' : '' ) }
									>
										<td className="bb-groups-list__td--checkbox bb-admin-list-table__checkbox">
											<CheckboxControl
												checked={ isSelected }
												onChange={ function ( checked ) {
													handleSelectRow( group.id, checked );
												} }
												__nextHasNoMarginBottom
											/>
										</td>
										<td className="bb-groups-list__td--name">
											<div className="bb-groups-list__name-cell">
												{ group.avatar_url && (
													<img
														src={ safeUrl( group.avatar_url ) }
														alt={ decodeEntities( group.name ) }
														className="bb-groups-list__avatar"
													/>
												) }
												<a
												href={ safeUrl( group.permalink ) }
												target="_blank"
												rel="noopener noreferrer"
												className="bb-groups-list__group-name"
												>
													{ decodeEntities( group.name ) }
												</a>
											</div>
										</td>
										<td className="bb-groups-list__td--privacy">
											<span className={ 'bb-groups-list__privacy-badge bb-groups-list__privacy-badge--' + group.status }>
												<i className={ getPrivacyIcon( group.status ) }></i>
												{ getPrivacyLabel( group ) }
											</span>
										</td>
										<td className="bb-groups-list__td--members">
											<span className="bb-groups-list__members-count">
												<i className="bb-icons-rl bb-icons-rl-user"></i>
												{ group.total_members }
											</span>
										</td>
										<td className="bb-groups-list__td--group-type">
											{ group.group_type && (
												<span className="bb-groups-list__type-badge">
													{ decodeEntities( group.group_type ) }
												</span>
											) }
										</td>
										<td className="bb-groups-list__td--last-active">
											<span className="bb-groups-list__date">
												<i className="bb-icons-rl bb-icons-rl-clock"></i>
												{ formatDate( group.last_activity ) }
											</span>
										</td>
										{ /* Custom columns from bp_groups_admin_get_group_custom_column filter */ }
										{ group.custom_columns && customColumnKeys.map( function ( key ) {
											return (
												<td key={ key } className={ 'bb-groups-list__td--custom bb-groups-list__td--' + key }>
													{/* Safe: custom_columns are already sanitized via sanitizeHtml at fetch time. */}
													<span dangerouslySetInnerHTML={ { __html: group.custom_columns[ key ] } } />
												</td>
											);
										} ) }
										<td className="bb-groups-list__td--actions bb-admin-actions-toggle">
											<DropdownMenu
												icon={ <i className="bb-icons-rl-dots-three"></i> }
												label={ __( 'More options', 'buddyboss' ) }
											>
												{ function ( dropdownProps ) {
													var onClose = dropdownProps.onClose;
													return (
														<MenuGroup className="bb_dropdown_menu_group">
															{ group.permalink && (
																<MenuItem
																	onClick={ function () {
																		var permalink = safeUrl( group.permalink );
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
																	handleEditGroup( group );
																	onClose();
																} }
															>
																<i className="bb-icons-rl bb-icons-rl-note-pencil"></i>
																{ __( 'Edit', 'buddyboss' ) }
															</MenuItem>
															<MenuItem
																isDestructive
																onClick={ function () {
																	handleDeleteGroup( group );
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
					className="bb-groups-list"
				/>
			) }

			{ /* Delete Group Modal */ }
			{ deleteModalOpen && (
				<Modal
					title={ __( 'Delete Group?', 'buddyboss' ) }
					onRequestClose={ function () {
						setDeleteModalOpen( false );
					} }
					className="bb-group-delete-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-group-delete-modal__body">
						<div className="bb-admin-delete__warning">
							<i className="bb-icons-rl bb-icons-rl-warning-circle"></i>
							<div className="bb-admin-delete__warning-text">
								<span className="bb-admin-delete__warning-title">
									{ __( 'Warning', 'buddyboss' ) }
								</span>
								<span className="bb-admin-delete__warning-desc">
									{ __( 'This permanently deletes selected groups from the community and cannot be undone.', 'buddyboss' ) }
								</span>
							</div>
						</div>
						<p className="bb-group-delete-modal__description">
							{ __( 'Deleting groups will remove them from the community and the WordPress backend listings. They will no longer appear in the group directory, and all associated data and posts will be permanently deleted.', 'buddyboss' ) }
						</p>
						<CheckboxControl
							label={ __( 'I understand this will permanently delete the group.', 'buddyboss' ) }
							checked={ deleteConfirmChecked }
							onChange={ setDeleteConfirmChecked }
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="bb-group-delete-modal__footer">
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
				<div className="bb-groups-list__edit-loading">
					<Spinner />
				</div>
			) }

			{ /* Group Create Modal */ }
			<GroupCreateModal
				isOpen={ createModalOpen }
				onClose={ function () {
					setCreateModalOpen( false );
				} }
				onCreated={ handleGroupCreated }
			/>

			{ /* Group Edit Modal */ }
			<GroupEditModal
				isOpen={ null !== editGroup }
				group={ editGroup }
				onClose={ function () {
					setEditGroup( null );
				} }
				onSave={ handleSaveGroup }
				isSaving={ isEditSaving }
			/>

			{ /* Change Group Type Modal */ }
			{ /* Remove Group Type Confirm Modal */ }
			<ConfirmToggleModal
				isOpen={ removeTypeModalOpen }
				title={ __( 'Remove Group Type', 'buddyboss' ) }
				message={ __( 'Are you sure you want to remove the group type from the selected groups?', 'buddyboss' ) }
				confirmLabel={ __( 'Remove', 'buddyboss' ) }
				cancelLabel={ __( 'Cancel', 'buddyboss' ) }
				isDestructive={ true }
				onConfirm={ function () {
					setRemoveTypeModalOpen( false );
					performAction( 'remove_group_type', selectedIds );
				} }
				onCancel={ function () {
					setRemoveTypeModalOpen( false );
				} }
			/>

			{ /* Change Group Type Modal */ }
			{ changeTypeModalOpen && (
				<Modal
					title={ __( 'Bulk Action', 'buddyboss' ) }
					onRequestClose={ function () {
						setChangeTypeModalOpen( false );
					} }
					className="bb-group-change-type-modal bb-admin-settings-modal"
					shouldCloseOnClickOutside={ false }
				>
					<div className="bb-group-change-type-modal__body">
						<label className="bb-group-change-type-modal__label">
							{ __( 'Change Group Type to', 'buddyboss' ) }
						</label>
						<SelectControl
							value={ selectedGroupType }
							options={ [ { label: __( 'Select group type', 'buddyboss' ), value: '' } ].concat(
								groupTypes.map( function ( type ) {
									return { label: decodeEntities( type.label ), value: type.value };
								} )
							) }
							onChange={ setSelectedGroupType }
							__nextHasNoMarginBottom
						/>
					</div>
					<div className="bb-group-change-type-modal__footer">
						<Button
							variant="secondary"
							onClick={ function () {
								setChangeTypeModalOpen( false );
							} }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleConfirmChangeType }
							disabled={ ! selectedGroupType }
						>
							{ __( 'Save', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}

export default GroupsListScreen;
