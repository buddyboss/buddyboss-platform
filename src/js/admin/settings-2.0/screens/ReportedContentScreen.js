/**
 * BuddyBoss Admin Settings 2.0 - Reported Content Screen
 *
 * Custom panel screen for listing reported content items with actions.
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
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
	getReportedContent,
	hideContent,
	unhideContent,
	suspendContentOwner,
	unsuspendContentOwner,
	reportedContentBulkAction,
} from '../utils/ajax';
import { ViewContentReportModal } from '../components/modals/ViewContentReportModal';

/**
 * Build an array of page numbers with ellipsis for pagination.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} currentPage Current active page.
 * @param {number} totalPages  Total number of pages.
 * @returns {Array} Array of page numbers and '...' strings.
 */
function getPageNumbers( currentPage, totalPages ) {
	var pages = [];
	var delta = 1;
	var left  = currentPage - delta;
	var right = currentPage + delta;

	// Always include first page.
	pages.push( 1 );

	// Add range around current page.
	for ( var i = left; i <= right; i++ ) {
		if ( i > 1 && i < totalPages ) {
			pages.push( i );
		}
	}

	// Always include last page.
	if ( totalPages > 1 ) {
		pages.push( totalPages );
	}

	// De-duplicate and sort.
	var unique = [];
	var seen = {};
	for ( var j = 0; j < pages.length; j++ ) {
		if ( ! seen[ pages[ j ] ] ) {
			seen[ pages[ j ] ] = true;
			unique.push( pages[ j ] );
		}
	}
	unique.sort( function ( a, b ) { return a - b; } );

	// Insert ellipsis between gaps.
	var result = [];
	for ( var k = 0; k < unique.length; k++ ) {
		if ( k > 0 && unique[ k ] - unique[ k - 1 ] > 1 ) {
			result.push( '...' );
		}
		result.push( unique[ k ] );
	}

	return result;
}

/**
 * Reported Content Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props Component props.
 * @returns {JSX.Element} Reported content screen.
 */
export function ReportedContentScreen() {
	var itemsState = useState( [] );
	var items = itemsState[ 0 ];
	var setItems = itemsState[ 1 ];

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

	var contentTypeState = useState( '' );
	var contentType = contentTypeState[ 0 ];
	var setContentType = contentTypeState[ 1 ];

	var statusFilterState = useState( '' );
	var statusFilter = statusFilterState[ 0 ];
	var setStatusFilter = statusFilterState[ 1 ];

	var statusCountsState = useState( { all: 0, hidden: 0, visible: 0 } );
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
	var reportModalItem = reportModalState[ 0 ];
	var setReportModalItem = reportModalState[ 1 ];

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

	// Fetch content items.
	var fetchItems = useCallback( function ( pageNum, filterType, filterStatus ) {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		var controller = new AbortController();
		abortRef.current = controller;

		var params = { page: pageNum, per_page: PER_PAGE };
		if ( filterType ) {
			params.content_type = filterType;
		}
		if ( filterStatus ) {
			params.status = filterStatus;
		}

		setIsLoading( true );
		getReportedContent( params, { signal: controller.signal } )
			.then( function ( response ) {
				setIsLoading( false );
				if ( response.success && response.data ) {
					setItems( response.data.items || [] );
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
		fetchItems( 1, '', '' );
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	// Get content types from localized data.
	var reportedContentTypes = ( window.bbAdminData && window.bbAdminData.reportedContentTypes ) || {};

	// Handle content type filter change.
	var handleContentTypeChange = useCallback( function ( newType ) {
		setContentType( newType );
		setPage( 1 );
		setSelectedIds( [] );
		fetchItems( 1, newType, statusFilter );
	}, [ fetchItems, statusFilter ] );

	// Handle status filter change.
	var handleStatusFilterChange = useCallback( function ( newStatus ) {
		setStatusFilter( newStatus );
		setPage( 1 );
		setSelectedIds( [] );
		fetchItems( 1, contentType, newStatus );
	}, [ fetchItems, contentType ] );

	// Handle page change.
	var handlePageChange = useCallback( function ( newPage ) {
		setPage( newPage );
		setSelectedIds( [] );
		fetchItems( newPage, contentType, statusFilter );
	}, [ contentType, statusFilter, fetchItems ] );

	// Select all checkbox.
	var handleSelectAll = useCallback( function ( checked ) {
		if ( checked ) {
			setSelectedIds( items.map( function ( item ) { return item.id; } ) );
		} else {
			setSelectedIds( [] );
		}
	}, [ items ] );

	// Select single row.
	var handleSelectRow = useCallback( function ( id, checked ) {
		if ( checked ) {
			setSelectedIds( function ( prev ) {
				return prev.concat( [ id ] );
			} );
		} else {
			setSelectedIds( function ( prev ) {
				return prev.filter( function ( i ) { return i !== id; } );
			} );
		}
	}, [] );

	// Handle bulk action apply.
	var handleBulkApply = useCallback( function () {
		if ( ! bulkAction || selectedIds.length === 0 ) {
			return;
		}

		var confirmMessage = 'hide' === bulkAction
			? __( 'Are you sure you want to hide the selected content items?', 'buddyboss' )
			: __( 'Are you sure you want to unhide the selected content items?', 'buddyboss' );

		if ( ! window.confirm( confirmMessage ) ) {
			return;
		}

		setActionInProgress( 'bulk' );
		reportedContentBulkAction( bulkAction, selectedIds )
			.then( function ( response ) {
				setActionInProgress( null );
				if ( response.success ) {
					setSelectedIds( [] );
					setBulkAction( '' );
					fetchItems( page, contentType, statusFilter );
				}
			} )
			.catch( function () {
				setActionInProgress( null );
				setErrorMessage( __( 'An error occurred. Please try again.', 'buddyboss' ) );
			} );
	}, [ bulkAction, selectedIds, page, contentType, statusFilter, fetchItems ] );

	// Handle hide/unhide content with confirmation dialog.
	var handleHideAction = useCallback( function ( item, action, onClose ) {
		var confirmMessage = ( 'hide' === action )
			? __( 'Please confirm you want to hide this content. It will be hidden from all members in your network.', 'buddyboss' )
			: __( 'Please confirm you want to unhide this content. It will be open for all members in your network.', 'buddyboss' );

		if ( ! window.confirm( confirmMessage ) ) {
			return;
		}

		if ( onClose ) {
			onClose();
		}

		setActionInProgress( item.id );

		var promise = ( 'hide' === action )
			? hideContent( item.item_id, item.item_type )
			: unhideContent( item.item_id, item.item_type );

		promise
			.then( function ( response ) {
				setActionInProgress( null );
				if ( response.success ) {
					fetchItems( page, contentType, statusFilter );
				}
			} )
			.catch( function () {
				setActionInProgress( null );
				setErrorMessage( __( 'An error occurred. Please try again.', 'buddyboss' ) );
			} );
	}, [ page, contentType, statusFilter, fetchItems ] );

	// Handle suspend/unsuspend owner with confirmation dialog.
	var handleSuspendAction = useCallback( function ( item, action, onClose ) {
		var confirmMessage = ( 'suspend' === action )
			? __( 'Please confirm you want to suspend this member. Members who are suspended will be logged out and not allowed to login again. Their content will be hidden from all members in your network. Please allow a few minutes for this process to complete.', 'buddyboss' )
			: __( 'Please confirm you want to unsuspend this member. Members who are unsuspended will be allowed to login again, and their content will no longer be hidden from other members in your network. Please allow a few minutes for this process to complete.', 'buddyboss' );

		if ( ! window.confirm( confirmMessage ) ) {
			return;
		}

		if ( onClose ) {
			onClose();
		}

		setActionInProgress( item.id );

		// Optimistically mark suspend_in_progress so button stays disabled after refetch.
		setItems( function ( prev ) {
			return prev.map( function ( i ) {
				return i.id === item.id ? Object.assign( {}, i, { suspend_in_progress: true } ) : i;
			} );
		} );

		var promise = ( 'suspend' === action )
			? suspendContentOwner( item.owner.user_id )
			: unsuspendContentOwner( item.owner.user_id );

		promise
			.then( function ( response ) {
				setActionInProgress( null );
				if ( response.success ) {
					fetchItems( page, contentType, statusFilter );
				}
			} )
			.catch( function () {
				setActionInProgress( null );
				setErrorMessage( __( 'An error occurred. Please try again.', 'buddyboss' ) );
			} );
	}, [ page, contentType, statusFilter, fetchItems ] );

	// Handle view report.
	var handleViewReport = useCallback( function ( item, onClose ) {
		if ( onClose ) {
			onClose();
		}
		setReportModalItem( item );
	}, [] );

	var allSelected = items.length > 0 && selectedIds.length === items.length;
	var hasBulkSelection = selectedIds.length > 0;

	// Build content type options for SelectControl.
	var contentTypeOptions = [ { label: __( 'All Content Types', 'buddyboss' ), value: '' } ];
	Object.keys( reportedContentTypes ).forEach( function ( key ) {
		contentTypeOptions.push( { label: reportedContentTypes[ key ], value: key } );
	} );

	return (
		<div className="bb-admin-reported-content">
			{ errorMessage && (
				<div className="bb-admin-reported-content__error-notice">
					<span>{ errorMessage }</span>
					<button type="button" onClick={ function () { setErrorMessage( '' ); } }>
						<i className="bb-icons-rl bb-icons-rl-x"></i>
					</button>
				</div>
			) }
			<div className="bb-admin-reported-content__card">
				{/* Title */}
				<div className="bb-admin-reported-content__title-bar">
					<h2 className="bb-admin-reported-content__title">
						{ __( 'Reported Content', 'buddyboss' ) }
					</h2>
				</div>

				{/* Body */}
				<div className="bb-admin-reported-content__body">
					{/* Action Bar */}
					<div className="bb-admin-reported-content__action-bar">
						<div className="bb-admin-reported-content__action-bar-left">
							<div className="bb-admin-reported-content__bulk-actions">
								<SelectControl
									value={ bulkAction }
									options={ [
										{ label: __( 'Bulk actions', 'buddyboss' ), value: '' },
										{ label: __( 'Hide Content', 'buddyboss' ), value: 'hide' },
										{ label: __( 'Unhide Content', 'buddyboss' ), value: 'unhide' },
									] }
									onChange={ setBulkAction }
									disabled={ ! hasBulkSelection }
									__nextHasNoMarginBottom
								/>
								<Button
									variant="secondary"
									onClick={ handleBulkApply }
									disabled={ ! bulkAction || ! hasBulkSelection || actionInProgress === 'bulk' }
									className="bb-admin-reported-content__bulk-apply"
								>
									{ actionInProgress === 'bulk' ? <Spinner /> : __( 'Apply', 'buddyboss' ) }
								</Button>
							</div>
						</div>
						<div className="bb-admin-reported-content__action-bar-right">
							<SelectControl
								value={ statusFilter }
								options={ [
									{ label: __( 'All', 'buddyboss' ) + ' (' + statusCounts.all + ')', value: '' },
									{ label: __( 'Hidden', 'buddyboss' ) + ' (' + statusCounts.hidden + ')', value: 'hidden' },
									{ label: __( 'Visible', 'buddyboss' ) + ' (' + statusCounts.visible + ')', value: 'visible' },
								] }
								onChange={ handleStatusFilterChange }
								className="bb-admin-reported-content__status-select"
								__nextHasNoMarginBottom
							/>
							{ contentTypeOptions.length > 1 && (
								<SelectControl
									value={ contentType }
									options={ contentTypeOptions }
									onChange={ handleContentTypeChange }
									className="bb-admin-reported-content__filter-select"
									__nextHasNoMarginBottom
								/>
							) }
						</div>
					</div>

					{/* Table */}
					<div className="bb-admin-reported-content__table-wrapper">
						{ isLoading ? (
							<div className="bb-admin-loading">
								<Spinner />
							</div>
						) : 0 === items.length ? (
							<div className="bb-admin-reported-content__empty">
								<p>{ __( 'No reported content found.', 'buddyboss' ) }</p>
							</div>
						) : (
							<table className="bb-admin-reported-content__table">
								<thead>
									<tr>
										<th className="bb-admin-reported-content__th--checkbox">
											<CheckboxControl
												checked={ allSelected }
												onChange={ handleSelectAll }
												__nextHasNoMarginBottom
											/>
										</th>
										<th className="bb-admin-reported-content__th--content">
											{ __( 'Content', 'buddyboss' ) }
										</th>
										<th className="bb-admin-reported-content__th--owner">
											{ __( 'Owner', 'buddyboss' ) }
										</th>
										<th className="bb-admin-reported-content__th--reports">
											{ __( 'Reports', 'buddyboss' ) }
										</th>
										<th className="bb-admin-reported-content__th--status">
											{ __( 'Status', 'buddyboss' ) }
										</th>
										<th className="bb-admin-reported-content__th--actions">&nbsp;</th>
									</tr>
								</thead>
								<tbody>
									{ items.map( function ( item ) {
										var isBusy = actionInProgress === item.id;
										var isSelected = selectedIds.indexOf( item.id ) > -1;
										return (
											<tr
												key={ item.id }
												className={ 'bb-admin-reported-content__row' + ( isSelected ? ' bb-admin-reported-content__row--selected' : '' ) }
											>
												<td className="bb-admin-reported-content__td--checkbox">
													<CheckboxControl
														checked={ isSelected }
														onChange={ function ( checked ) {
															handleSelectRow( item.id, checked );
														} }
														__nextHasNoMarginBottom
													/>
												</td>
												<td className="bb-admin-reported-content__td--content">
													<div className="bb-admin-reported-content__content">
														<span className="bb-admin-reported-content__content-icon">
															<i className={ item.content_icon }></i>
														</span>
														{ item.content_url ? (
															<a
																href={ item.content_url }
																target="_blank"
																rel="noopener noreferrer"
																className="bb-admin-reported-content__content-name"
															>
																{ item.content_label + ' #' + item.item_id }
															</a>
														) : (
															<span className="bb-admin-reported-content__content-name">
																{ item.content_label + ' #' + item.item_id }
															</span>
														) }
													</div>
												</td>
												<td className="bb-admin-reported-content__td--owner">
													<div className="bb-admin-reported-content__owner">
														{ item.owner && item.owner.avatar && (
															<img
																src={ item.owner.avatar }
																alt={ item.owner.display_name }
																className="bb-admin-reported-content__avatar"
															/>
														) }
														{ item.owner && item.owner.display_name ? (
															<a
																href={ item.owner.profile_url }
																target="_blank"
																rel="noopener noreferrer"
																className="bb-admin-reported-content__owner-name"
															>
																{ item.owner.display_name }
															</a>
														) : (
															<span className="bb-admin-reported-content__owner-name">
																{ __( 'Unknown', 'buddyboss' ) }
															</span>
														) }
													</div>
												</td>
												<td className={ 'bb-admin-reported-content__td--reports' + ( item.reports > 0 ? ' bb-admin-reported-content__td--active' : '' ) }>
													<i className="bb-icons-rl bb-icons-rl-flag"></i>
													{ item.reports }
												</td>
												<td className="bb-admin-reported-content__td--status">
													{ item.is_hidden && (
														<span className="bb-admin-reported-content__hidden-badge">
															{ __( 'Hidden', 'buddyboss' ) }
														</span>
													) }
												</td>
												<td className="bb-admin-reported-content__td--actions">
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
																				handleViewReport( item, onClose );
																			} }
																		>
																			<i className="bb-icons-rl bb-icons-rl-eye"></i>
																			{ __( 'View Report', 'buddyboss' ) }
																		</MenuItem>
																		{ item.content_url && (
																			<MenuItem
																				onClick={ function () {
																					try {
																						var parsed = new URL( item.content_url, window.location.origin );
																						if ( 'http:' === parsed.protocol || 'https:' === parsed.protocol ) {
																							window.open( parsed.href, '_blank', 'noopener,noreferrer' );
																						}
																					} catch ( e ) {
																						// Invalid URL — do nothing.
																					}
																					onClose();
																				} }
																			>
																				<i className="bb-icons-rl bb-icons-rl-file-text"></i>
																				{ __( 'View Content', 'buddyboss' ) }
																				<i className="bb-icons-rl bb-icons-rl-arrow-up-right bb-icons-external"></i>
																			</MenuItem>
																		) }
																		{/* Hide/Unhide: not shown when owner is suspended */}
																		{ ! item.is_owner_suspended && (
																			item.is_hidden ? (
																				<MenuItem
																					onClick={ function () {
																						handleHideAction( item, 'unhide', onClose );
																					} }
																				>
																					<i className="bb-icons-rl bb-icons-rl-eye"></i>
																					{ __( 'Unhide Content', 'buddyboss' ) }
																				</MenuItem>
																			) : (
																				<MenuItem
																					onClick={ function () {
																						handleHideAction( item, 'hide', onClose );
																					} }
																				>
																					<i className="bb-icons-rl bb-icons-rl-eye-slash"></i>
																					{ __( 'Hide Content', 'buddyboss' ) }
																				</MenuItem>
																			)
																		) }
																		{/* Suspend/Unsuspend: not shown for admins */}
																		{ item.owner && item.owner.user_id > 0 && ! item.is_owner_admin && (
																			item.suspend_in_progress ? (
																				<MenuItem
																					disabled
																					aria-label={ __( 'The background process is currently in the queue. Please refresh the page after a short while.', 'buddyboss' ) }
																				>
																					<i className={ item.is_owner_suspended ? 'bb-icons-rl bb-icons-rl-plus-circle' : 'bb-icons-rl bb-icons-rl-minus-circle' }></i>
																					{ item.is_owner_suspended ? __( 'Unsuspend Owner', 'buddyboss' ) : __( 'Suspend Owner', 'buddyboss' ) }
																				</MenuItem>
																			) : (
																				item.is_owner_suspended ? (
																					<MenuItem
																						onClick={ function () {
																							handleSuspendAction( item, 'unsuspend', onClose );
																						} }
																					>
																						<i className="bb-icons-rl bb-icons-rl-plus-circle"></i>
																						{ __( 'Unsuspend Owner', 'buddyboss' ) }
																					</MenuItem>
																				) : (
																					<MenuItem
																						onClick={ function () {
																							handleSuspendAction( item, 'suspend', onClose );
																						} }
																					>
																						<i className="bb-icons-rl bb-icons-rl-minus-circle"></i>
																						{ __( 'Suspend Owner', 'buddyboss' ) }
																					</MenuItem>
																				)
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
						<div className="bb-admin-reported-content__footer">
							<span className="bb-admin-reported-content__item-count">
								{ total } { total === 1 ? __( 'item', 'buddyboss' ) : __( 'items', 'buddyboss' ) }
							</span>

							{ totalPages > 1 && (
								<div className="bb-admin-reported-content__pagination">
									<Button
										variant="secondary"
										disabled={ page <= 1 }
										onClick={ function () { handlePageChange( page - 1 ); } }
										className="bb-admin-reported-content__pagination-btn bb-admin-reported-content__pagination-btn--previous"
									>
										&lsaquo;
									</Button>
									{ getPageNumbers( page, totalPages ).map( function ( p, idx ) {
										if ( '...' === p ) {
											return (
												<span key={ 'ellipsis-' + idx } className="bb-admin-reported-content__pagination-ellipsis">
													&hellip;
												</span>
											);
										}
										return (
											<Button
												key={ p }
												variant={ p === page ? 'primary' : 'secondary' }
												onClick={ function () { handlePageChange( p ); } }
												className={ 'bb-admin-reported-content__pagination-btn' + ( p === page ? ' bb-admin-reported-content__pagination-btn--current' : '' ) }
											>
												{ p }
											</Button>
										);
									} ) }
									<Button
										variant="secondary"
										disabled={ page >= totalPages }
										onClick={ function () { handlePageChange( page + 1 ); } }
										className="bb-admin-reported-content__pagination-btn bb-admin-reported-content__pagination-btn--next"
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
			<ViewContentReportModal
				isOpen={ !! reportModalItem }
				onClose={ function () { setReportModalItem( null ); } }
				item={ reportModalItem }
			/>
		</div>
	);
}

export default ReportedContentScreen;
