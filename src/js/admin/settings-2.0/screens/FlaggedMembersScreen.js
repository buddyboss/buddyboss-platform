/**
 * BuddyBoss Admin Settings 2.0 - Flagged Members Screen
 *
 * Custom panel screen for listing flagged members with reports and blocks.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import {
	Spinner,
	Button,
	CheckboxControl,
	SelectControl,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Modal,
} from '@wordpress/components';
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

	// Confirm modal state: { title, message, confirmLabel, onConfirm }.
	var confirmModalState = useState( null );
	var confirmModal = confirmModalState[ 0 ];
	var setConfirmModal = confirmModalState[ 1 ];

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
	var handleStatusFilterChange = useCallback( function ( newStatus ) {
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
	var handleSelectAll = useCallback( function ( checked ) {
		if ( checked ) {
			setSelectedIds( members.map( function ( m ) { return m.user_id; } ) );
		} else {
			setSelectedIds( [] );
		}
	}, [ members ] );

	// Select single row.
	var handleSelectRow = useCallback( function ( userId, checked ) {
		if ( checked ) {
			setSelectedIds( function ( prev ) {
				return prev.concat( [ userId ] );
			} );
		} else {
			setSelectedIds( function ( prev ) {
				return prev.filter( function ( id ) { return id !== userId; } );
			} );
		}
	}, [] );

	// Execute bulk action after confirmation.
	var executeBulkAction = useCallback( function () {
		setConfirmModal( null );
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

	// Handle bulk action apply.
	var handleBulkApply = useCallback( function () {
		if ( ! bulkAction || selectedIds.length === 0 ) {
			return;
		}

		var confirmMessage = 'suspend' === bulkAction
			? __( 'Are you sure you want to suspend the selected members? They will be logged out and their content will be hidden. Please allow a few minutes for this process to complete.', 'buddyboss' )
			: __( 'Are you sure you want to unsuspend the selected members? They will be allowed to login again and their content will be visible. Please allow a few minutes for this process to complete.', 'buddyboss' );

		setConfirmModal( {
			title: 'suspend' === bulkAction ? __( 'Suspend Members', 'buddyboss' ) : __( 'Unsuspend Members', 'buddyboss' ),
			message: confirmMessage,
			confirmLabel: 'suspend' === bulkAction ? __( 'Suspend', 'buddyboss' ) : __( 'Unsuspend', 'buddyboss' ),
			onConfirm: executeBulkAction,
		} );
	}, [ bulkAction, selectedIds, executeBulkAction ] );

	// Handle suspend/unsuspend with confirmation modal.
	var handleSuspendAction = useCallback( function ( member, action, onClose ) {
		if ( onClose ) {
			onClose();
		}

		var confirmMessage = ( 'suspend' === action )
			? __( 'Please confirm you want to suspend this member. Members who are suspended will be logged out and not allowed to login again. Their content will be hidden from all members in your network. Please allow a few minutes for this process to complete.', 'buddyboss' )
			: __( 'Please confirm you want to unsuspend this member. Members who are unsuspended will be allowed to login again, and their content will no longer be hidden from other members in your network. Please allow a few minutes for this process to complete.', 'buddyboss' );

		setConfirmModal( {
			title: 'suspend' === action ? __( 'Suspend Member', 'buddyboss' ) : __( 'Unsuspend Member', 'buddyboss' ),
			message: confirmMessage,
			confirmLabel: 'suspend' === action ? __( 'Suspend', 'buddyboss' ) : __( 'Unsuspend', 'buddyboss' ),
			onConfirm: function () {
				setConfirmModal( null );
				setActionInProgress( member.user_id );

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
							fetchMembers( page, search, statusFilter );
						}
					} )
					.catch( function () {
						setActionInProgress( null );
						setErrorMessage( __( 'An error occurred. Please try again.', 'buddyboss' ) );
					} );
			},
		} );
	}, [ page, search, statusFilter, fetchMembers ] );

	// Handle view report.
	var handleViewReport = useCallback( function ( member, onClose ) {
		if ( onClose ) {
			onClose();
		}
		setReportModalMember( member );
	}, [] );

	var allSelected = members.length > 0 && selectedIds.length === members.length;
	var hasBulkSelection = selectedIds.length > 0;

	return (
		<div className="bb-admin-flagged-members">
			{ errorMessage && (
				<div className="bb-admin-flagged-members__error-notice">
					<span>{ errorMessage }</span>
					<button type="button" onClick={ function () { setErrorMessage( '' ); } }>
						<i className="bb-icons-rl bb-icons-rl-x"></i>
					</button>
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
					<div className="bb-admin-flagged-members__action-bar bb-admin-list-toolbar">
						<div className="bb-admin-flagged-members__action-bar-left bb-admin-list-toolbar__left">
							<div className="bb-admin-flagged-members__bulk-actions">
								<SelectControl
									value={ bulkAction }
									options={ [
										{ label: __( 'Bulk actions', 'buddyboss' ), value: '' },
										{ label: __( 'Suspend', 'buddyboss' ), value: 'suspend' },
										{ label: __( 'Unsuspend', 'buddyboss' ), value: 'unsuspend' },
									] }
									onChange={ setBulkAction }
									disabled={ ! hasBulkSelection }
									__nextHasNoMarginBottom
								/>
								<Button
									variant="secondary"
									onClick={ handleBulkApply }
									disabled={ ! bulkAction || ! hasBulkSelection || actionInProgress === 'bulk' }
									className="bb-admin-flagged-members__bulk-apply"
								>
									{ actionInProgress === 'bulk' ? <Spinner /> : __( 'Apply', 'buddyboss' ) }
								</Button>
							</div>
						</div>
						<div className="bb-admin-flagged-members__action-bar-right bb-admin-list-toolbar__right">
							<SelectControl
								value={ statusFilter }
								options={ [
									{ label: __( 'All', 'buddyboss' ) + ' (' + statusCounts.all + ')', value: '' },
									{ label: __( 'Suspended', 'buddyboss' ) + ' (' + statusCounts.suspended + ')', value: 'suspended' },
									{ label: __( 'Active', 'buddyboss' ) + ' (' + statusCounts.active + ')', value: 'active' },
								] }
								onChange={ handleStatusFilterChange }
								className="bb-admin-flagged-members__status-select"
								__nextHasNoMarginBottom
							/>
							<div className="bb-admin-flagged-members__search">
								<form className="bb-admin-flagged-members__search-form" onSubmit={ handleSearch }>
									<input
										type="text"
										className="bb-admin-flagged-members__search-input"
										placeholder={ __( 'Search members', 'buddyboss' ) }
										value={ search }
										onChange={ function ( e ) { setSearch( e.target.value ); } }
									/>
									<span className="bb-admin-flagged-members__search-icon">
										<i className="bb-icons-rl bb-icons-rl-magnifying-glass"></i>
									</span>
								</form>
							</div>
						</div>
					</div>

					{/* Table */}
					<div className="bb-admin-flagged-members__table-wrapper">
						{ isLoading ? (
							<div className="bb-admin-loading">
								<Spinner />
							</div>
						) : 0 === members.length ? (
							<div className="bb-admin-flagged-members__empty bb-admin-list-table__empty">
								<p>{ __( 'No flagged members found.', 'buddyboss' ) }</p>
							</div>
						) : (
							<table className="bb-admin-flagged-members__table bb-admin-list-table">
								<thead>
									<tr>
										<th className="bb-admin-flagged-members__th--checkbox bb-admin-list-table__checkbox">
											<CheckboxControl
												checked={ allSelected }
												onChange={ handleSelectAll }
												__nextHasNoMarginBottom
											/>
										</th>
										<th className="bb-admin-flagged-members__th--member">
											{ __( 'Member', 'buddyboss' ) }
										</th>
										<th className="bb-admin-flagged-members__th--blocks">
											{ __( 'Blocks', 'buddyboss' ) }
										</th>
										<th className="bb-admin-flagged-members__th--reports">
											{ __( 'Reports', 'buddyboss' ) }
										</th>
										<th className="bb-admin-flagged-members__th--status">
											{ __( 'Status', 'buddyboss' ) }
										</th>
										<th className="bb-admin-flagged-members__th--actions">&nbsp;</th>
									</tr>
								</thead>
								<tbody>
									{ members.map( function ( member ) {
										var isBusy = actionInProgress === member.user_id;
										var isSelected = selectedIds.indexOf( member.user_id ) > -1;
										return (
											<tr
												key={ member.id }
												className={ 'bb-admin-flagged-members__row bb-admin-list-table__row' + ( isSelected ? ' bb-admin-flagged-members__row--selected bb-admin-list-table__row--selected' : '' ) }
											>
												<td className="bb-admin-flagged-members__td--checkbox bb-admin-list-table__checkbox">
													<CheckboxControl
														checked={ isSelected }
														onChange={ function ( checked ) {
															handleSelectRow( member.user_id, checked );
														} }
														__nextHasNoMarginBottom
													/>
												</td>
												<td className="bb-admin-flagged-members__td--member">
													<div className="bb-admin-flagged-members__member">
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
												</td>
												<td className={ 'bb-admin-flagged-members__td--blocks' + ( member.blocks > 0 ? ' bb-admin-flagged-members__td--active' : '' ) }>
													<i className="bb-icons-rl bb-icons-rl-prohibit"></i>
													{ member.blocks }
												</td>
												<td className={ 'bb-admin-flagged-members__td--reports' + ( member.reports > 0 ? ' bb-admin-flagged-members__td--active' : '' ) }>
													<i className="bb-icons-rl bb-icons-rl-flag"></i>
													{ member.reports }
												</td>
												<td className="bb-admin-flagged-members__td--status">
													{ member.is_suspended && (
														<span className="bb-admin-flagged-members__suspended-badge">
															{ __( 'Suspended', 'buddyboss' ) }
														</span>
													) }
												</td>
												<td className="bb-admin-flagged-members__td--actions bb-admin-actions-toggle">
													{ isBusy ? (
														<Spinner />
													) : (
														<DropdownMenu
															icon={ <i className="bb-icons-rl bb-icons-rl-dots-three"></i> }
															label={ __( 'More options', 'buddyboss' ) }
														>
															{ function ( { onClose } ) {
																return (
																	<MenuGroup className="bb_dropdown_menu_group">
																		<MenuItem
																			onClick={ function () {
																				handleViewReport( member, onClose );
																			} }
																		>
																			<i className="bb-icons-rl bb-icons-rl-eye"></i>
																			{ __( 'View Report', 'buddyboss' ) }
																		</MenuItem>
																		{ ! member.is_admin && (
																			member.suspend_in_progress ? (
																				<MenuItem
																					disabled
																					aria-label={ __( 'The background process is currently in the queue. Please refresh the page after a short while.', 'buddyboss' ) }
																				>
																					<i className={ member.is_suspended ? 'bb-icons-rl bb-icons-rl-user-circle-check' : 'bb-icons-rl bb-icons-rl-user-circle-minus' }></i>
																					{ member.is_suspended ? __( 'Unsuspend Member', 'buddyboss' ) : __( 'Suspend Member', 'buddyboss' ) }
																				</MenuItem>
																			) : (
																				<MenuItem
																					onClick={ function () {
																						handleSuspendAction(
																							member,
																							member.is_suspended ? 'unsuspend' : 'suspend',
																							onClose
																						);
																					} }
																				>
																					<i className={ member.is_suspended ? 'bb-icons-rl bb-icons-rl-user-circle-check' : 'bb-icons-rl bb-icons-rl-user-circle-minus' }></i>
																					{ member.is_suspended ? __( 'Unsuspend Member', 'buddyboss' ) : __( 'Suspend Member', 'buddyboss' ) }
																				</MenuItem>
																			)
																		) }
																	</MenuGroup>
																);
															} }
														</DropdownMenu>
													) }
												</td>
											</tr>
										);
									} ) }
								</tbody>
							</table>
						) }
					</div>

					{/* Footer */}
					{ ! isLoading && total > 0 && (
						<div className="bb-admin-flagged-members__footer">
							<span className="bb-admin-flagged-members__item-count">
								{ total } { total === 1 ? __( 'item', 'buddyboss' ) : __( 'items', 'buddyboss' ) }
							</span>

							{ totalPages > 1 && (
								<div className="bb-admin-flagged-members__pagination">
									<Button
										variant="secondary"
										disabled={ page <= 1 }
										onClick={ function () { handlePageChange( page - 1 ); } }
										className="bb-admin-flagged-members__pagination-btn bb-admin-flagged-members__pagination-btn--previous"
									>
										&lsaquo;
									</Button>
									<span className="bb-admin-flagged-members__page-info">
										{ page + ' / ' + totalPages }
									</span>
									<Button
										variant="secondary"
										disabled={ page >= totalPages }
										onClick={ function () { handlePageChange( page + 1 ); } }
										className="bb-admin-flagged-members__pagination-btn bb-admin-flagged-members__pagination-btn--next"
									>
										&rsaquo;
									</Button>
								</div>
							) }
						</div>
					) }
				</div>
			</div>

			{/* View Report Modal */}
			<ViewReportModal
				isOpen={ !! reportModalMember }
				onClose={ function () { setReportModalMember( null ); } }
				member={ reportModalMember }
			/>

			{/* Confirm Action Modal */}
			{ confirmModal && (
				<Modal
					title={ confirmModal.title }
					onRequestClose={ function () {
						setConfirmModal( null );
					} }
					className="bb-admin-flagged-members__confirm-modal bb-admin-settings-modal"
				>
					<div className="bb-admin-settings-modal__body">
						<p>{ confirmModal.message }</p>
					</div>
					<div className="bb-admin-settings-modal__footer">
						<Button
							variant="primary"
							onClick={ confirmModal.onConfirm }
						>
							{ confirmModal.confirmLabel }
						</Button>
						<Button
							variant="secondary"
							onClick={ function () {
								setConfirmModal( null );
							} }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}

export default FlaggedMembersScreen;
