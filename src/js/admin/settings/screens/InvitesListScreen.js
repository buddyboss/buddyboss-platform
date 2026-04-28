/**
 * BuddyBoss Admin Settings 2.0 - Email Invites List Screen
 *
 * Displays a paginated, searchable list of sent email invitations.
 * Supports filtering (All/Mine/Published), sorting (Newest/Oldest),
 * bulk revoke, and per-row actions (View, Edit, Revoke).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef, useMemo } from '@wordpress/element';
import {
	Button,
	CheckboxControl,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Modal,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getInvites, invitesBulkAction } from '../utils/ajax';
import { safeUrl } from '../utils/sanitize';
import { Toast } from '../components/Toast';
import { ListPagination } from '../components/common/ListPagination';
import { DeleteConfirmModal } from '../components/common/DeleteConfirmModal';
import { useListScreenState } from '../hooks/useListScreenState';
import { useListScreenHandlers } from '../hooks/useListScreenHandlers';

/**
 * Sort options (static).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var sortOptions = [
	{ label: __( 'Newest', 'buddyboss' ), value: 'newest' },
	{ label: __( 'Oldest', 'buddyboss' ), value: 'oldest' },
];

/**
 * Number of invites per page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {number}
 */
var PER_PAGE = 20;

/**
 * Email Invites List Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation callback.
 * @returns {JSX.Element} The invites list screen.
 */
export default function InvitesListScreen( props ) {
	// Common list screen state (loading, notice, selection, bulk, search).
	var common = useListScreenState();
	var isLoading = common.isLoading;
	var setIsLoading = common.setIsLoading;
	var selectedIds = common.selectedIds;
	var setSelectedIds = common.setSelectedIds;
	var bulkAction = common.bulkAction;
	var setBulkAction = common.setBulkAction;
	var bulkProcessing = common.isBulkProcessing;
	var setBulkProcessing = common.setIsBulkProcessing;
	var searchInput = common.searchInput;
	var setSearchInput = common.setSearchInput;
	var search = common.searchQuery;
	var setSearch = common.setSearchQuery;

	// Screen-specific state.
	var stateItems = useState( [] );
	var items = stateItems[0];
	var setItems = stateItems[1];

	var stateTotal = useState( 0 );
	var total = stateTotal[0];
	var setTotal = stateTotal[1];

	var statePage = useState( 1 );
	var page = statePage[0];
	var setPage = statePage[1];

	var stateSort = useState( 'newest' );
	var sort = stateSort[0];
	var setSort = stateSort[1];

	var stateFilter = useState( 'all' );
	var filter = stateFilter[0];
	var setFilter = stateFilter[1];

	var stateViews = useState( {} );
	var views = stateViews[0];
	var setViews = stateViews[1];

	var stateBulkActions = useState( {} );
	var bulkActions = stateBulkActions[0];
	var setBulkActions = stateBulkActions[1];

	var stateToast = useState( null );
	var toast = stateToast[0];
	var setToast = stateToast[1];

	var revokeConfirmState = useState( false );
	var revokeConfirmOpen = revokeConfirmState[0];
	var setRevokeConfirmOpen = revokeConfirmState[1];

	var revokeConfirmIdsState = useState( [] );
	var revokeConfirmIds = revokeConfirmIdsState[0];
	var setRevokeConfirmIds = revokeConfirmIdsState[1];

	var revokeConfirmCheckedState = useState( false );
	var revokeConfirmChecked = revokeConfirmCheckedState[0];
	var setRevokeConfirmChecked = revokeConfirmCheckedState[1];

	var abortRef = useRef( null );
	var isFirstLoad = useRef( true );

	// Common list screen handlers (select all, select row, search timer cleanup).
	var handlers = useListScreenHandlers( {
		setSearchInput: setSearchInput,
		setSearchQuery: setSearch,
		setPage: setPage,
		setSelectedIds: setSelectedIds,
		setSort: setSort,
		setFilter: setFilter,
		getItemIds: function () {
			return items.map( function ( item ) { return item.id; } );
		},
	} );
	var searchTimerRef = handlers.searchTimerRef;

	var totalPages = Math.ceil( total / PER_PAGE );

	/**
	 * Fetch invites from the server.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} params Query parameters.
	 */
	var fetchInvites = useCallback( function( params ) {
		var fetchPage   = params.fetchPage || 1;
		var fetchSort   = params.fetchSort || 'newest';
		var fetchSearch = params.fetchSearch !== undefined ? params.fetchSearch : '';
		var fetchFilter = params.fetchFilter || 'all';

		// Cancel stale request.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}

		var controller = new AbortController();
		abortRef.current = controller;

		setIsLoading( true );

		var data = {
			page: fetchPage,
			per_page: PER_PAGE,
			sort: fetchSort,
			search: fetchSearch,
			filter: fetchFilter,
		};

		// Include metadata on first load.
		if ( isFirstLoad.current ) {
			data.include_meta = 1;
			isFirstLoad.current = false;
		}

		getInvites( data, { signal: controller.signal } )
			.then( function( response ) {
				if ( response.success && response.data ) {
					setItems( response.data.items || [] );
					setTotal( response.data.total || 0 );

					if ( response.data.views ) {
						setViews( response.data.views );
					}
					if ( response.data.bulk_actions ) {
						setBulkActions( response.data.bulk_actions );
					}
				}
				setIsLoading( false );
			} )
			.catch( function( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setIsLoading( false );
			} );
	}, [] );

	// Initial fetch.
	useEffect( function() {
		fetchInvites( { fetchPage: 1, fetchSort: sort, fetchSearch: '', fetchFilter: filter } );

		return function() {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			if ( searchTimerRef.current ) {
				clearTimeout( searchTimerRef.current );
			}
		};
	}, [] );

	// Auto-dismiss success toast after 3 seconds.
	useEffect( function() {
		if ( ! toast ) {
			return;
		}
		if ( 'success' === toast.status ) {
			var timer = setTimeout( function() {
				setToast( null );
			}, 3000 );
			return function() {
				clearTimeout( timer );
			};
		}
	}, [ toast ] );

	/**
	 * Handle search input change with 500ms debounce.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} e Input change event.
	 */
	var handleSearchChange = useCallback( function( e ) {
		var val = e.target.value;
		setSearchInput( val );

		if ( searchTimerRef.current ) {
			clearTimeout( searchTimerRef.current );
		}

		searchTimerRef.current = setTimeout( function() {
			setSearch( val );
			setPage( 1 );
			setSelectedIds( [] );
			fetchInvites( { fetchPage: 1, fetchSort: sort, fetchSearch: val, fetchFilter: filter } );
		}, 500 );
	}, [ sort, filter, fetchInvites ] );

	/**
	 * Handle sort change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} newSort New sort value.
	 */
	var handleSortChange = useCallback( function( newSort ) {
		setSort( newSort );
		setPage( 1 );
		setSelectedIds( [] );
		fetchInvites( { fetchPage: 1, fetchSort: newSort, fetchSearch: search, fetchFilter: filter } );
	}, [ search, filter, fetchInvites ] );

	/**
	 * Handle filter change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} newFilter New filter value.
	 */
	var handleFilterChange = useCallback( function( newFilter ) {
		setFilter( newFilter );
		setPage( 1 );
		setSelectedIds( [] );
		fetchInvites( { fetchPage: 1, fetchSort: sort, fetchSearch: search, fetchFilter: newFilter } );
	}, [ sort, search, fetchInvites ] );

	/**
	 * Handle page change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} newPage New page number.
	 */
	var handlePageChange = useCallback( function( newPage ) {
		setPage( newPage );
		setSelectedIds( [] );
		fetchInvites( { fetchPage: newPage, fetchSort: sort, fetchSearch: search, fetchFilter: filter } );
	}, [ sort, search, filter, fetchInvites ] );

	// Use shared select handlers from useListScreenHandlers.
	var handleToggleSelect = function( id ) {
		handlers.handleSelectRow( id, -1 === selectedIds.indexOf( id ) );
	};
	var handleSelectAll = handlers.handleSelectAll;

	/**
	 * Perform revoke action on one or more invites.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Array} ids Invite IDs to revoke.
	 */
	var performRevoke = useCallback( function( ids ) {
		if ( ! ids || 0 === ids.length || bulkProcessing ) {
			return;
		}

		setBulkProcessing( true );

		invitesBulkAction( ids, 'revoke' )
			.then( function( response ) {
				setBulkProcessing( false );
				if ( response.success ) {
					setToast( { status: 'success', message: response.data.message } );
					setSelectedIds( [] );
					setBulkAction( '' );
					if ( response.data.views ) {
						setViews( response.data.views );
					}
					isFirstLoad.current = true;
					fetchInvites( { fetchPage: page, fetchSort: sort, fetchSearch: search, fetchFilter: filter } );
				} else {
					setToast( {
						status: 'error',
						message: ( response.data && response.data.message ) || __( 'Something went wrong.', 'buddyboss' ),
					} );
				}
			} )
			.catch( function() {
				setBulkProcessing( false );
				setToast( { status: 'error', message: __( 'Something went wrong.', 'buddyboss' ) } );
			} );
	}, [ bulkProcessing, page, sort, search, filter, fetchInvites ] );

	/**
	 * Handle bulk action apply.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleBulkApply = useCallback( function() {
		if ( ! bulkAction || 0 === selectedIds.length || bulkProcessing ) {
			return;
		}

		if ( 'revoke' === bulkAction ) {
			setRevokeConfirmIds( selectedIds.slice() );
			setRevokeConfirmChecked( false );
			setRevokeConfirmOpen( true );
		}
	}, [ bulkAction, selectedIds, bulkProcessing ] );

	var allSelected = items.length > 0 && items.every( function( item ) {
		return -1 !== selectedIds.indexOf( item.id );
	} );

	// Build bulk action options.
	var bulkActionOptions = [ { label: __( 'Bulk actions', 'buddyboss' ), value: '' } ];
	Object.keys( bulkActions ).forEach( function( key ) {
		bulkActionOptions.push( { label: decodeEntities( bulkActions[ key ] ), value: key } );
	} );

	// Build filter options from views.
	var filterOptions = useMemo( function() {
		var opts = [];
		Object.keys( views ).forEach( function( key ) {
			var view = views[ key ];
			opts.push( {
				label: sprintf(
					/* translators: 1: filter label, 2: count */
					'%1$s (%2$d)',
					view.label,
					view.count
				),
				value: key,
			} );
		} );
		return opts.length > 0 ? opts : [ { label: __( 'All', 'buddyboss' ), value: 'all' } ];
	}, [ views ] );

	return (
		<div className="bb-invites-list">
			{/* Header */}
			<div className="bb-invites-list__header">
				<h2 className="bb-invites-list__title">
					{ __( 'Email Invites', 'buddyboss' ) }
				</h2>
			</div>

			{/* Toolbar */}
			<div className="bb-invites-list__toolbar bb-admin-list-toolbar">
				<div className="bb-invites-list__toolbar-left bb-admin-list-toolbar__left">
					{/* Bulk Actions */}
					<div className="bb-invites-list__bulk-actions">
						<SelectControl
							value={ bulkAction }
							options={ bulkActionOptions }
							onChange={ setBulkAction }
							__nextHasNoMarginBottom
						/>
						<Button
							className="bb-invites-list__bulk-apply is-secondary"
							onClick={ handleBulkApply }
							disabled={ ! bulkAction || 0 === selectedIds.length || bulkProcessing }
						>
							{ __( 'Apply', 'buddyboss' ) }
						</Button>
					</div>
				</div>

				<div className="bb-invites-list__toolbar-right bb-admin-list-toolbar__right">
					{/* Filter */}
					<div className="bb-invites-list__filter-select">
						<SelectControl
							value={ filter }
							options={ filterOptions }
							onChange={ handleFilterChange }
							__nextHasNoMarginBottom
						/>
					</div>

					{/* Sort */}
					<div className="bb-invites-list__sort-select">
						<SelectControl
							value={ sort }
							options={ sortOptions }
							onChange={ handleSortChange }
							__nextHasNoMarginBottom
						/>
					</div>

					{/* Search */}
					<div className="bb-invites-list__search bb-admin-list-search">
						<input
							type="text"
							className="bb-invites-list__search-input bb-admin-list-search__input"
							placeholder={ __( 'Search sent invites', 'buddyboss' ) }
							value={ searchInput }
							onChange={ handleSearchChange }
						/>
					</div>
				</div>
			</div>

			{/* Table */}
			<div className="bb-invites-list__table-wrapper">
				{ isLoading ? (
					<div className="bb-invites-list__loading bb-admin-list-table__loading">
						<Spinner />
					</div>
				) : 0 === items.length ? (
					<div className="bb-invites-list__empty bb-admin-list-table__empty">
						{ search
							? __( 'No invites found matching your search.', 'buddyboss' )
							: __( 'No invites found.', 'buddyboss' )
						}
					</div>
				) : (
					<table className="bb-invites-list__table bb-admin-list-table">
						<thead>
							<tr>
								<th className="bb-invites-list__th--checkbox bb-admin-list-table__checkbox">
									<CheckboxControl
										checked={ allSelected }
										onChange={ handleSelectAll }
										__nextHasNoMarginBottom
									/>
								</th>
								<th className="bb-invites-list__th--sender">
									{ __( 'Sender', 'buddyboss' ) }
								</th>
								<th className="bb-invites-list__th--recipient">
									{ __( 'Recipient', 'buddyboss' ) }
								</th>
								<th className="bb-invites-list__th--email">
									{ __( 'Recipient Email', 'buddyboss' ) }
								</th>
								<th className="bb-invites-list__th--status">
									{ __( 'Status', 'buddyboss' ) }
								</th>
								<th className="bb-invites-list__th--date">
									{ __( 'Date Invited', 'buddyboss' ) }
								</th>
								<th className="bb-invites-list__th--actions"></th>
							</tr>
						</thead>
						<tbody>
							{ items.map( function( item ) {
								var isSelected = -1 !== selectedIds.indexOf( item.id );
								var statusClass = 'registered' === item.status
									? 'bb-invites-list__status-badge--approved'
									: 'bb-invites-list__status-badge--pending';

								return (
									<tr
										key={ item.id }
										className={
											'bb-invites-list__row bb-admin-list-table__row' +
											( isSelected ? ' bb-invites-list__row--selected bb-admin-list-table__row--selected' : '' )
										}
									>
										<td className="bb-invites-list__td--checkbox bb-admin-list-table__checkbox">
											<CheckboxControl
												checked={ isSelected }
												onChange={ function() {
													handleToggleSelect( item.id );
												} }
												__nextHasNoMarginBottom
											/>
										</td>
										<td className="bb-invites-list__td--sender">
											<div className="bb-invites-list__sender">
												{ item.sender_avatar && (
													<img
														src={ safeUrl( item.sender_avatar ) }
														alt=""
														className="bb-invites-list__sender-avatar"
														width="40"
														height="40"
													/>
												) }
												<a
													href={ safeUrl( item.sender_url ) }
													className="bb-invites-list__sender-name"
												>
													{ decodeEntities( item.sender_name || '' ) }
												</a>
											</div>
										</td>
										<td className="bb-invites-list__td--recipient">
											{ decodeEntities( item.recipient_name || '' ) }
										</td>
										<td className="bb-invites-list__td--email">
											{ item.recipient_email || '' }
										</td>
										<td className="bb-invites-list__td--status">
											<span className={ 'bb-invites-list__status-badge ' + statusClass }>
												{ decodeEntities( item.status_label || '' ) }
											</span>
										</td>
										<td className="bb-invites-list__td--date">
											<i className="bb-icons-rl bb-icons-rl-clock"></i>
											{ item.date_invited || '' }
										</td>
										<td className="bb-invites-list__td--actions bb-admin-actions-toggle">
											<DropdownMenu
												icon={ <i className="bb-icons-rl-dots-three"></i> }
												label={ __( 'More options', 'buddyboss' ) }
											>
												{ function( { onClose } ) {
													return (
														<MenuGroup className="bb_dropdown_menu_group">
															{ item.view_url && (
																<MenuItem
																	onClick={ function() {
																		window.open( safeUrl( item.view_url ), '_blank', 'noopener,noreferrer' );
																		onClose();
																	} }
																>
																	<i className="bb-icons-rl bb-icons-rl-arrow-square-out"></i>
																	{ __( 'View', 'buddyboss' ) }
																</MenuItem>
															) }
															{ item.edit_url && (
																<MenuItem
																	onClick={ function() {
																		window.location.href = safeUrl( item.edit_url );
																		onClose();
																	} }
																>
																	<i className="bb-icons-rl bb-icons-rl-pencil-simple"></i>
																	{ __( 'Edit', 'buddyboss' ) }
																</MenuItem>
															) }
															{ item.can_revoke && (
																<MenuItem
																	isDestructive
																	onClick={ function() {
																		onClose();
																		setRevokeConfirmIds( [ item.id ] );
																		setRevokeConfirmChecked( false );
																		setRevokeConfirmOpen( true );
																	} }
																>
																	<i className="bb-icons-rl bb-icons-rl-trash"></i>
																	{ __( 'Revoke Invite', 'buddyboss' ) }
																</MenuItem>
															) }
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

			{/* Footer with pagination */}
			{ ! isLoading && (
				<ListPagination
					currentPage={ page }
					totalPages={ totalPages }
					total={ total }
					onPageChange={ handlePageChange }
					className="bb-invites-list"
				/>
			) }

			{/* Toast notification */}
			{ toast && (
				<div className="bb-toast-container">
					<Toast
						status={ toast.status }
						message={ toast.message }
						onDismiss={ function() { setToast( null ); } }
					/>
				</div>
			) }

			{/* Single Revoke Confirmation */}
			<DeleteConfirmModal
				isOpen={ revokeConfirmOpen && 1 === revokeConfirmIds.length }
				singleTitle={ __( 'Revoke Invite', 'buddyboss' ) }
				items={ revokeConfirmIds.length === 1 ? [ { id: revokeConfirmIds[0], title: ( items.find( function( i ) { return i.id === revokeConfirmIds[0]; } ) || {} ).recipient_email || '' } ] : [] }
				warningText={ __( 'This will permanently revoke email invitations and cannot be undone.', 'buddyboss' ) }
				confirmLabel={ __( 'I understand that this removes email invitations.', 'buddyboss' ) }
				confirmChecked={ revokeConfirmChecked }
				onConfirmChange={ setRevokeConfirmChecked }
				onConfirm={ function () {
					setRevokeConfirmOpen( false );
					setRevokeConfirmChecked( false );
					performRevoke( revokeConfirmIds );
				} }
				confirmText={ __( 'Revoke', 'buddyboss' ) }
				onClose={ function () { setRevokeConfirmOpen( false ); setRevokeConfirmChecked( false ); } }
				isProcessing={ bulkProcessing }
				className="bb-invites-revoke-modal"
			/>

			{/* Bulk Revoke Confirmation */}
			<DeleteConfirmModal
				isOpen={ revokeConfirmOpen && revokeConfirmIds.length > 1 }
				bulkTitle={ __( 'Bulk Revoke Invitations', 'buddyboss' ) }
				items={ items.filter( function( item ) { return -1 !== revokeConfirmIds.indexOf( item.id ); } ).map( function( item ) { return { id: item.id, title: item.recipient_email || '' }; } ) }
				onRemoveItem={ function ( id ) {
					setRevokeConfirmIds( function ( prev ) {
						var next = prev.filter( function ( i ) { return i !== id; } );
						if ( 0 === next.length ) {
							setRevokeConfirmOpen( false );
						}
						return next;
					} );
					setSelectedIds( function ( prev ) {
						return prev.filter( function ( i ) { return i !== id; } );
					} );
				} }
				warningText={ __( 'This will permanently revoke email invitations and cannot be undone.', 'buddyboss' ) }
				confirmLabel={ __( 'I understand that this removes email invitations.', 'buddyboss' ) }
				confirmChecked={ revokeConfirmChecked }
				onConfirmChange={ setRevokeConfirmChecked }
				onConfirm={ function () {
					setRevokeConfirmOpen( false );
					setRevokeConfirmChecked( false );
					performRevoke( revokeConfirmIds );
				} }
				confirmText={ __( 'Revoke', 'buddyboss' ) }
				onClose={ function () { setRevokeConfirmOpen( false ); setRevokeConfirmChecked( false ); } }
				isProcessing={ bulkProcessing }
				className="bb-invites-revoke-modal"
			/>
		</div>
	);
}
