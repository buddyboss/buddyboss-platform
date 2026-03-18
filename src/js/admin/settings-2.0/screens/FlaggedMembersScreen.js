/**
 * BuddyBoss Admin Settings 2.0 - Flagged Members Screen
 *
 * Custom panel screen for listing flagged members with reports and blocks.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { Spinner, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getFlaggedMembers, suspendMember, unsuspendMember, flaggedMembersBulkAction } from '../utils/ajax';
import { ViewReportModal } from '../components/modals/ViewReportModal';

/**
 * Flagged Members Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props Component props.
 * @returns {JSX.Element} Flagged members screen.
 */
export function FlaggedMembersScreen() {
	var membersState = useState( [] );
	var members = membersState[ 0 ];
	var setMembers = membersState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var pageState = useState( 1 );
	var page = pageState[ 0 ];
	var setPage = pageState[ 1 ];

	var totalPagesState = useState( 1 );
	var totalPages = totalPagesState[ 0 ];
	var setTotalPages = totalPagesState[ 1 ];

	var totalState = useState( 0 );
	var total = totalState[ 0 ];
	var setTotal = totalState[ 1 ];

	var searchState = useState( '' );
	var search = searchState[ 0 ];
	var setSearch = searchState[ 1 ];

	var statusFilterState = useState( '' );
	var statusFilter = statusFilterState[ 0 ];
	var setStatusFilter = statusFilterState[ 1 ];

	var statusCountsState = useState( { all: 0, suspended: 0, active: 0 } );
	var statusCounts = statusCountsState[ 0 ];
	var setStatusCounts = statusCountsState[ 1 ];

	// Bulk action state.
	var bulkActionState = useState( '' );
	var bulkAction = bulkActionState[ 0 ];
	var setBulkAction = bulkActionState[ 1 ];

	var selectedIdsState = useState( [] );
	var selectedIds = selectedIdsState[ 0 ];
	var setSelectedIds = selectedIdsState[ 1 ];

	// 3-dot menu state.
	var openMenuState = useState( null );
	var openMenuId = openMenuState[ 0 ];
	var setOpenMenuId = openMenuState[ 1 ];

	// View report modal state.
	var reportModalState = useState( null );
	var reportModalMember = reportModalState[ 0 ];
	var setReportModalMember = reportModalState[ 1 ];

	// Error toast state.
	var errorMessageState = useState( '' );
	var errorMessage = errorMessageState[ 0 ];
	var setErrorMessage = errorMessageState[ 1 ];

	// Action in progress.
	var actionInProgressState = useState( null );
	var actionInProgress = actionInProgressState[ 0 ];
	var setActionInProgress = actionInProgressState[ 1 ];

	var abortRef = useRef( null );

	var PER_PAGE = 20;

	// Fetch members.
	var fetchMembers = useCallback( function ( pageNum, searchTerm, filterStatus ) {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		var controller = new AbortController();
		abortRef.current = controller;

		var params = { page: pageNum, per_page: PER_PAGE, search: searchTerm || '' };
		if ( filterStatus ) {
			params.status = filterStatus;
		}

		setIsLoading( true );
		getFlaggedMembers( params, { signal: controller.signal } )
			.then( function ( response ) {
				setIsLoading( false );
				if ( response.success && response.data ) {
					setMembers( response.data.members || [] );
					setTotal( response.data.total || 0 );
					setTotalPages( response.data.total_pages || 1 );
					if ( response.data.status_counts ) {
						setStatusCounts( response.data.status_counts );
					}
				}
			} )
			.catch( function ( err ) {
				if ( err.name !== 'AbortError' ) {
					setIsLoading( false );
				}
			} );
	}, [] );

	// Initial fetch.
	useEffect( function () {
		fetchMembers( 1, '', '' );
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	// Close menu on outside click.
	useEffect( function () {
		if ( openMenuId === null ) {
			return;
		}
		function handleClick() {
			setOpenMenuId( null );
		}
		document.addEventListener( 'click', handleClick );
		return function () {
			document.removeEventListener( 'click', handleClick );
		};
	}, [ openMenuId ] );

	// Handle search.
	var handleSearch = useCallback( function ( e ) {
		if ( e ) {
			e.preventDefault();
		}
		setPage( 1 );
		setSelectedIds( [] );
		fetchMembers( 1, search, statusFilter );
	}, [ search, statusFilter, fetchMembers ] );

	// Handle status filter change.
	var handleStatusFilterChange = useCallback( function ( e ) {
		var newStatus = e.target.value;
		setStatusFilter( newStatus );
		setPage( 1 );
		setSelectedIds( [] );
		fetchMembers( 1, search, newStatus );
	}, [ search, fetchMembers ] );

	// Handle page change.
	var handlePageChange = useCallback( function ( newPage ) {
		setPage( newPage );
		setSelectedIds( [] );
		fetchMembers( newPage, search, statusFilter );
	}, [ search, statusFilter, fetchMembers ] );

	// Select all checkbox.
	var handleSelectAll = useCallback( function () {
		if ( selectedIds.length === members.length && members.length > 0 ) {
			setSelectedIds( [] );
		} else {
			setSelectedIds( members.map( function ( m ) { return m.user_id; } ) );
		}
	}, [ members, selectedIds ] );

	// Select single row.
	var handleSelectRow = useCallback( function ( userId ) {
		setSelectedIds( function ( prev ) {
			if ( prev.indexOf( userId ) > -1 ) {
				return prev.filter( function ( id ) { return id !== userId; } );
			}
			return prev.concat( [ userId ] );
		} );
	}, [] );

	// Handle bulk action apply.
	var handleBulkApply = useCallback( function () {
		if ( ! bulkAction || selectedIds.length === 0 ) {
			return;
		}

		var confirmMessage = 'suspend' === bulkAction
			? __( 'Are you sure you want to suspend the selected members? They will be logged out and their content will be hidden. Please allow a few minutes for this process to complete.', 'buddyboss' )
			: __( 'Are you sure you want to unsuspend the selected members? They will be allowed to login again and their content will be visible. Please allow a few minutes for this process to complete.', 'buddyboss' );

		if ( ! window.confirm( confirmMessage ) ) {
			return;
		}

		setActionInProgress( 'bulk' );
		flaggedMembersBulkAction( bulkAction, selectedIds )
			.then( function ( response ) {
				setActionInProgress( null );
				if ( response.success ) {
					setSelectedIds( [] );
					setBulkAction( '' );
					fetchMembers( page, search, statusFilter );
				}
			} )
			.catch( function () {
				setActionInProgress( null );
				setErrorMessage( __( 'An error occurred. Please try again.', 'buddyboss' ) );
			} );
	}, [ bulkAction, selectedIds, page, search, statusFilter, fetchMembers ] );

	// Handle suspend/unsuspend with confirmation dialog.
	var handleSuspendAction = useCallback( function ( member, action ) {
		var confirmMessage = ( 'suspend' === action )
			? __( 'Please confirm you want to suspend this member. Members who are suspended will be logged out and not allowed to login again. Their content will be hidden from all members in your network. Please allow a few minutes for this process to complete.', 'buddyboss' )
			: __( 'Please confirm you want to unsuspend this member. Members who are unsuspended will be allowed to login again, and their content will no longer be hidden from other members in your network. Please allow a few minutes for this process to complete.', 'buddyboss' );

		if ( ! window.confirm( confirmMessage ) ) {
			setOpenMenuId( null );
			return;
		}

		setActionInProgress( member.user_id );
		setOpenMenuId( null );

		// Optimistically mark suspend_in_progress so button stays disabled after refetch.
		setMembers( function ( prev ) {
			return prev.map( function ( m ) {
				return m.user_id === member.user_id ? Object.assign( {}, m, { suspend_in_progress: true } ) : m;
			} );
		} );

		var promise = ( 'suspend' === action ) ? suspendMember( member.user_id ) : unsuspendMember( member.user_id );

		promise
			.then( function ( response ) {
				setActionInProgress( null );
				if ( response.success ) {
					// Refresh the list.
					fetchMembers( page, search, statusFilter );
				}
			} )
			.catch( function () {
				setActionInProgress( null );
				setErrorMessage( __( 'An error occurred. Please try again.', 'buddyboss' ) );
			} );
	}, [ page, search, statusFilter, fetchMembers ] );

	// Handle view report.
	var handleViewReport = useCallback( function ( member ) {
		setOpenMenuId( null );
		setReportModalMember( member );
	}, [] );

	var allSelected = members.length > 0 && selectedIds.length === members.length;
	var hasBulkSelection = selectedIds.length > 0;

	return (
		<div className="bb-admin-flagged-members">
			{ errorMessage && (
				<div className="bb-admin-flagged-members__error-notice">
					<span>{ errorMessage }</span>
					<button type="button" onClick={ function () { setErrorMessage( '' ); } }>&times;</button>
				</div>
			) }
			<div className="bb-admin-flagged-members__card">
				{/* Title */}
				<div className="bb-admin-flagged-members__title-bar">
					<h2 className="bb-admin-flagged-members__title">
						{ __( 'Flagged Members', 'buddyboss' ) }
					</h2>
				</div>

				{/* Body */}
				<div className="bb-admin-flagged-members__body">
					{/* Action Bar */}
					<div className="bb-admin-flagged-members__action-bar">
						<div className="bb-admin-flagged-members__action-bar-left">
							<select
								className={ 'bb-admin-flagged-members__bulk-select' + ( ! hasBulkSelection ? ' bb-admin-flagged-members__bulk-select--disabled' : '' ) }
								value={ bulkAction }
								onChange={ function ( e ) { setBulkAction( e.target.value ); } }
								disabled={ ! hasBulkSelection }
							>
								<option value="">{ __( 'Bulk actions', 'buddyboss' ) }</option>
								<option value="suspend">{ __( 'Suspend', 'buddyboss' ) }</option>
								<option value="unsuspend">{ __( 'Unsuspend', 'buddyboss' ) }</option>
							</select>
							<button
								className={ 'bb-admin-flagged-members__bulk-apply' + ( ( ! bulkAction || ! hasBulkSelection ) ? ' bb-admin-flagged-members__bulk-apply--disabled' : '' ) }
								onClick={ handleBulkApply }
								disabled={ ! bulkAction || ! hasBulkSelection || actionInProgress === 'bulk' }
							>
								{ actionInProgress === 'bulk' ? <Spinner /> : __( 'Apply', 'buddyboss' ) }
							</button>
						</div>
						<div className="bb-admin-flagged-members__action-bar-right">
							<select
								className="bb-admin-flagged-members__status-select"
								value={ statusFilter }
								onChange={ handleStatusFilterChange }
							>
								<option value="">{ __( 'All', 'buddyboss' ) + ' (' + statusCounts.all + ')' }</option>
								<option value="suspended">{ __( 'Suspended', 'buddyboss' ) + ' (' + statusCounts.suspended + ')' }</option>
								<option value="active">{ __( 'Active', 'buddyboss' ) + ' (' + statusCounts.active + ')' }</option>
							</select>
							<form className="bb-admin-flagged-members__search-form" onSubmit={ handleSearch }>
								<input
									type="text"
									className="bb-admin-flagged-members__search-input"
									placeholder={ __( 'Search members', 'buddyboss' ) }
									value={ search }
									onChange={ function ( e ) { setSearch( e.target.value ); } }
								/>
								<button type="submit" className="bb-admin-flagged-members__search-btn">
									<i className="bb-icons-rl bb-icons-rl-search"></i>
								</button>
							</form>
						</div>
					</div>

					{/* Table Header */}
					<div className="bb-admin-flagged-members__table-header">
						<div className="bb-admin-flagged-members__table-header-left">
							<div className="bb-admin-flagged-members__checkbox-col">
								<input
									type="checkbox"
									className="bb-admin-flagged-members__checkbox"
									checked={ allSelected }
									onChange={ handleSelectAll }
								/>
							</div>
							<span className="bb-admin-flagged-members__col-label bb-admin-flagged-members__col-label--member">
								{ __( 'Member', 'buddyboss' ) }
							</span>
						</div>
						<span className="bb-admin-flagged-members__col-label bb-admin-flagged-members__col-label--blocks">
							{ __( 'Blocks', 'buddyboss' ) }
						</span>
						<span className="bb-admin-flagged-members__col-label bb-admin-flagged-members__col-label--reports">
							{ __( 'Reports', 'buddyboss' ) }
						</span>
					</div>

					{ isLoading ? (
						<div className="bb-admin-loading">
							<Spinner />
						</div>
					) : members.length === 0 ? (
						<div className="bb-admin-flagged-members__empty">
							<p>{ __( 'No flagged members found.', 'buddyboss' ) }</p>
						</div>
					) : (
						<>
							<div className="bb-admin-flagged-members__list">
								{ members.map( function ( member ) {
									var isBusy = actionInProgress === member.user_id;
									var isSelected = selectedIds.indexOf( member.user_id ) > -1;
									return (
										<div key={ member.id } className={ 'bb-admin-flagged-members__list-item' + ( isSelected ? ' bb-admin-flagged-members__list-item--selected' : '' ) }>
											{/* Items row */}
											<div className="bb-admin-flagged-members__items">
												{/* Member column (264px) — checkbox + avatar + name */}
												<div className="bb-admin-flagged-members__member-col">
													<div className="bb-admin-flagged-members__checkbox-col">
														<input
															type="checkbox"
															className="bb-admin-flagged-members__checkbox"
															checked={ isSelected }
															onChange={ function () { handleSelectRow( member.user_id ); } }
														/>
													</div>
													<img
														src={ member.avatar }
														alt={ member.display_name }
														className="bb-admin-flagged-members__avatar"
													/>
													<a
														href={ member.profile_url }
														target="_blank"
														rel="noopener noreferrer"
														className="bb-admin-flagged-members__name"
													>
														{ member.display_name }
													</a>
												</div>

												{/* Blocks */}
												<div className={ 'bb-admin-flagged-members__blocks-col' + ( member.blocks > 0 ? ' bb-admin-flagged-members__col--active' : '' ) }>
													<i className="bb-icons-rl bb-icons-rl-prohibit"></i>
													{ member.blocks }
												</div>

												{/* Reports */}
												<div className={ 'bb-admin-flagged-members__reports-col' + ( member.reports > 0 ? ' bb-admin-flagged-members__col--active' : '' ) }>
													<i className="bb-icons-rl bb-icons-rl-flag"></i>
													{ member.reports }
												</div>

												{/* Suspended badge */}
												{ member.is_suspended && (
													<div className="bb-admin-flagged-members__status-col">
														<span className="bb-admin-flagged-members__suspended-badge">
															{ __( 'Suspended', 'buddyboss' ) }
														</span>
													</div>
												) }
											</div>

											{/* Actions */}
											<div className="bb-admin-flagged-members__actions-col">
												{ isBusy ? (
													<Spinner />
												) : (
													<div className="bb-admin-flagged-members__menu-wrapper">
														<button
															className="bb-admin-flagged-members__menu-trigger"
															onClick={ function ( e ) {
																e.stopPropagation();
																setOpenMenuId( openMenuId === member.id ? null : member.id );
															} }
														>
															<i className="bb-icons-rl bb-icons-rl-dots-three"></i>
														</button>
														{ openMenuId === member.id && (
															<div className="bb-admin-flagged-members__menu-dropdown">
																<button
																	className="bb-admin-flagged-members__menu-item"
																	onClick={ function () { handleViewReport( member ); } }
																>
																	<i className="bb-icons-rl bb-icons-rl-eye"></i>
																	{ __( 'View Report', 'buddyboss' ) }
																</button>
																{ ! member.is_admin && (
																	member.suspend_in_progress ? (
																		<span
																			className="bb-admin-flagged-members__menu-item bb-admin-flagged-members__menu-item--disabled"
																			data-balloon={ __( 'The background process is currently in the queue. Please refresh the page after a short while.', 'buddyboss' ) }
																			data-balloon-pos="up"
																		>
																			<i className={ member.is_suspended ? 'bb-icons-rl bb-icons-rl-user-circle-check' : 'bb-icons-rl bb-icons-rl-user-circle-minus' }></i>
																			{ member.is_suspended ? __( 'Unsuspend Member', 'buddyboss' ) : __( 'Suspend Member', 'buddyboss' ) }
																		</span>
																	) : (
																		<button
																			className="bb-admin-flagged-members__menu-item"
																			onClick={ function () {
																				handleSuspendAction(
																					member,
																					member.is_suspended ? 'unsuspend' : 'suspend'
																				);
																			} }
																		>
																			<i className={ member.is_suspended ? 'bb-icons-rl bb-icons-rl-user-circle-check' : 'bb-icons-rl bb-icons-rl-user-circle-minus' }></i>
																			{ member.is_suspended ? __( 'Unsuspend Member', 'buddyboss' ) : __( 'Suspend Member', 'buddyboss' ) }
																		</button>
																	)
																) }
															</div>
														) }
													</div>
												) }
											</div>
										</div>
									);
								} ) }
							</div>

							{/* Pagination */}
							{ totalPages > 1 && (
								<div className="bb-admin-flagged-members__pagination">
									<Button
										variant="secondary"
										disabled={ page <= 1 }
										onClick={ function () { handlePageChange( page - 1 ); } }
									>
										{ __( '← Previous', 'buddyboss' ) }
									</Button>
									<span className="bb-admin-flagged-members__page-info">
										{ page + ' / ' + totalPages }
									</span>
									<Button
										variant="secondary"
										disabled={ page >= totalPages }
										onClick={ function () { handlePageChange( page + 1 ); } }
									>
										{ __( 'Next →', 'buddyboss' ) }
									</Button>
								</div>
							) }
						</>
					) }
				</div>
			</div>

			{/* View Report Modal */}
			<ViewReportModal
				isOpen={ !! reportModalMember }
				onClose={ function () { setReportModalMember( null ); } }
				member={ reportModalMember }
			/>
		</div>
	);
}

export default FlaggedMembersScreen;
