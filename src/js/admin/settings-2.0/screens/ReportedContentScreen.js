/**
 * BuddyBoss Admin Settings 2.0 - Reported Content Screen
 *
 * Custom panel screen for listing reported content items with actions.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
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
 * @param {Object}   props              Component props.
 * @param {Function} props.onNavigate   Navigation callback.
 * @returns {JSX.Element} Reported content screen.
 */
export function ReportedContentScreen( { onNavigate } ) {
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

	// 3-dot menu state.
	var openMenuState = useState( null );
	var openMenuId = openMenuState[ 0 ];
	var setOpenMenuId = openMenuState[ 1 ];

	// View report modal state.
	var reportModalState = useState( null );
	var reportModalItem = reportModalState[ 0 ];
	var setReportModalItem = reportModalState[ 1 ];

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

	// Handle content type filter change.
	var handleContentTypeChange = useCallback( function ( e ) {
		var newType = e.target.value;
		setContentType( newType );
		setPage( 1 );
		setSelectedIds( [] );
		fetchItems( 1, newType, statusFilter );
	}, [ fetchItems, statusFilter ] );

	// Handle status filter change.
	var handleStatusFilterChange = useCallback( function ( e ) {
		var newStatus = e.target.value;
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
	var handleSelectAll = useCallback( function () {
		if ( selectedIds.length === items.length && items.length > 0 ) {
			setSelectedIds( [] );
		} else {
			setSelectedIds( items.map( function ( item ) { return item.id; } ) );
		}
	}, [ items, selectedIds ] );

	// Select single row.
	var handleSelectRow = useCallback( function ( id ) {
		setSelectedIds( function ( prev ) {
			if ( prev.indexOf( id ) > -1 ) {
				return prev.filter( function ( i ) { return i !== id; } );
			}
			return prev.concat( [ id ] );
		} );
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
			} );
	}, [ bulkAction, selectedIds, page, contentType, statusFilter, fetchItems ] );

	// Handle hide/unhide content with confirmation dialog.
	var handleHideAction = useCallback( function ( item, action ) {
		var confirmMessage = ( 'hide' === action )
			? __( 'Please confirm you want to hide this content. It will be hidden from all members in your network.', 'buddyboss' )
			: __( 'Please confirm you want to unhide this content. It will be open for all members in your network.', 'buddyboss' );

		if ( ! window.confirm( confirmMessage ) ) {
			setOpenMenuId( null );
			return;
		}

		setActionInProgress( item.id );
		setOpenMenuId( null );

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
			} );
	}, [ page, contentType, statusFilter, fetchItems ] );

	// Handle suspend/unsuspend owner with confirmation dialog.
	var handleSuspendAction = useCallback( function ( item, action ) {
		var confirmMessage = ( 'suspend' === action )
			? __( 'Please confirm you want to suspend this member. Members who are suspended will be logged out and not allowed to login again. Their content will be hidden from all members in your network. Please allow a few minutes for this process to complete.', 'buddyboss' )
			: __( 'Please confirm you want to unsuspend this member. Members who are unsuspended will be allowed to login again, and their content will no longer be hidden from other members in your network. Please allow a few minutes for this process to complete.', 'buddyboss' );

		if ( ! window.confirm( confirmMessage ) ) {
			setOpenMenuId( null );
			return;
		}

		setActionInProgress( item.id );
		setOpenMenuId( null );

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
			} );
	}, [ page, contentType, statusFilter, fetchItems ] );

	// Handle view report.
	var handleViewReport = useCallback( function ( item ) {
		setOpenMenuId( null );
		setReportModalItem( item );
	}, [] );

	var allSelected = items.length > 0 && selectedIds.length === items.length;
	var hasBulkSelection = selectedIds.length > 0;

	return (
		<div className="bb-admin-reported-content">
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
							<select
								className={ 'bb-admin-reported-content__bulk-select' + ( ! hasBulkSelection ? ' bb-admin-reported-content__bulk-select--disabled' : '' ) }
								value={ bulkAction }
								onChange={ function ( e ) { setBulkAction( e.target.value ); } }
								disabled={ ! hasBulkSelection }
							>
								<option value="">{ __( 'Bulk actions', 'buddyboss' ) }</option>
								<option value="hide">{ __( 'Hide Content', 'buddyboss' ) }</option>
								<option value="unhide">{ __( 'Unhide Content', 'buddyboss' ) }</option>
							</select>
							<button
								className={ 'bb-admin-reported-content__bulk-apply' + ( ( ! bulkAction || ! hasBulkSelection ) ? ' bb-admin-reported-content__bulk-apply--disabled' : '' ) }
								onClick={ handleBulkApply }
								disabled={ ! bulkAction || ! hasBulkSelection || actionInProgress === 'bulk' }
							>
								{ actionInProgress === 'bulk' ? <Spinner /> : __( 'Apply', 'buddyboss' ) }
							</button>
						</div>
						<div className="bb-admin-reported-content__action-bar-right">
							<select
								className="bb-admin-reported-content__status-select"
								value={ statusFilter }
								onChange={ handleStatusFilterChange }
							>
								<option value="">{ __( 'All', 'buddyboss' ) + ' (' + statusCounts.all + ')' }</option>
								<option value="hidden">{ __( 'Hidden', 'buddyboss' ) + ' (' + statusCounts.hidden + ')' }</option>
								<option value="visible">{ __( 'Visible', 'buddyboss' ) + ' (' + statusCounts.visible + ')' }</option>
							</select>
							{ Object.keys( reportedContentTypes ).length > 0 && (
								<select
									className="bb-admin-reported-content__filter-select"
									value={ contentType }
									onChange={ handleContentTypeChange }
								>
									<option value="">{ __( 'All Content Types', 'buddyboss' ) }</option>
									{ Object.keys( reportedContentTypes ).map( function ( key ) {
										return (
											<option key={ key } value={ key }>
												{ reportedContentTypes[ key ] }
											</option>
										);
									} ) }
								</select>
							) }
						</div>
					</div>

					{/* Table Header */}
					<div className="bb-admin-reported-content__table-header">
						<div className="bb-admin-reported-content__table-header-left">
							<div className="bb-admin-reported-content__checkbox-col">
								<input
									type="checkbox"
									className="bb-admin-reported-content__checkbox"
									checked={ allSelected }
									onChange={ handleSelectAll }
								/>
							</div>
							<span className="bb-admin-reported-content__col-label bb-admin-reported-content__col-label--content">
								{ __( 'Content', 'buddyboss' ) }
							</span>
						</div>
						<span className="bb-admin-reported-content__col-label bb-admin-reported-content__col-label--owner">
							{ __( 'Owner', 'buddyboss' ) }
						</span>
						<span className="bb-admin-reported-content__col-label bb-admin-reported-content__col-label--reports">
							{ __( 'Reports', 'buddyboss' ) }
						</span>
					</div>

					{ isLoading ? (
						<div className="bb-admin-loading">
							<Spinner />
						</div>
					) : items.length === 0 ? (
						<div className="bb-admin-reported-content__empty">
							<p>{ __( 'No reported content found.', 'buddyboss' ) }</p>
						</div>
					) : (
						<>
							<div className="bb-admin-reported-content__list">
								{ items.map( function ( item ) {
									var isBusy = actionInProgress === item.id;
									var isSelected = selectedIds.indexOf( item.id ) > -1;
									return (
										<div key={ item.id } className={ 'bb-admin-reported-content__list-item' + ( isSelected ? ' bb-admin-reported-content__list-item--selected' : '' ) }>
											{/* Items row */}
											<div className="bb-admin-reported-content__items">
												{/* Content column (264px) — checkbox + icon + name */}
												<div className="bb-admin-reported-content__content-col">
													<div className="bb-admin-reported-content__checkbox-col">
														<input
															type="checkbox"
															className="bb-admin-reported-content__checkbox"
															checked={ isSelected }
															onChange={ function () { handleSelectRow( item.id ); } }
														/>
													</div>
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

												{/* Owner */}
												<div className="bb-admin-reported-content__owner-col">
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

												{/* Reports */}
												<div className={ 'bb-admin-reported-content__reports-col' + ( item.reports > 0 ? ' bb-admin-reported-content__col--active' : '' ) }>
													<i className="bb-icons-rl bb-icons-rl-flag"></i>
													{ item.reports }
												</div>

												{/* Hidden badge */}
												{ item.is_hidden && (
													<div className="bb-admin-reported-content__status-col">
														<span className="bb-admin-reported-content__hidden-badge">
															{ __( 'Hidden', 'buddyboss' ) }
														</span>
													</div>
												) }
											</div>

											{/* Actions */}
											<div className="bb-admin-reported-content__actions-col">
												{ isBusy ? (
													<Spinner />
												) : (
													<div className="bb-admin-reported-content__menu-wrapper">
														<button
															className="bb-admin-reported-content__menu-trigger"
															onClick={ function ( e ) {
																e.stopPropagation();
																setOpenMenuId( openMenuId === item.id ? null : item.id );
															} }
														>
															<i className="bb-icons-rl bb-icons-rl-dots-three"></i>
														</button>
														{ openMenuId === item.id && (
															<div className="bb-admin-reported-content__menu-dropdown">
																<button
																	className="bb-admin-reported-content__menu-item"
																	onClick={ function () { handleViewReport( item ); } }
																>
																	<i className="bb-icons-rl bb-icons-rl-eye"></i>
																	{ __( 'View Report', 'buddyboss' ) }
																</button>
																{ item.content_url && (
																	<a
																		href={ item.content_url }
																		target="_blank"
																		rel="noopener noreferrer"
																		className="bb-admin-reported-content__menu-item"
																	>
																		<i className="bb-icons-rl bb-icons-rl-file-text"></i>
																		{ __( 'View Content', 'buddyboss' ) }
																		<span className="bb-admin-reported-content__menu-item-external">
																			<i className="bb-icons-rl bb-icons-rl-arrow-square-out"></i>
																		</span>
																	</a>
																) }
																{/* Hide/Unhide: not shown when owner is suspended */}
																{ ! item.is_owner_suspended && (
																	item.is_hidden ? (
																		<button
																			className="bb-admin-reported-content__menu-item"
																			onClick={ function () { handleHideAction( item, 'unhide' ); } }
																		>
																			<i className="bb-icons-rl bb-icons-rl-eye"></i>
																			{ __( 'Unhide Content', 'buddyboss' ) }
																		</button>
																	) : (
																		<button
																			className="bb-admin-reported-content__menu-item"
																			onClick={ function () { handleHideAction( item, 'hide' ); } }
																		>
																			<i className="bb-icons-rl bb-icons-rl-eye-slash"></i>
																			{ __( 'Hide Content', 'buddyboss' ) }
																		</button>
																	)
																) }
																{/* Suspend/Unsuspend: not shown for admins */}
																{ item.owner && item.owner.user_id > 0 && ! item.is_owner_admin && (
																	item.suspend_in_progress ? (
																		<span
																			className="bb-admin-reported-content__menu-item bb-admin-reported-content__menu-item--disabled"
																			data-balloon={ __( 'The background process is currently in the queue. Please refresh the page after a short while.', 'buddyboss' ) }
																			data-balloon-pos="up"
																		>
																			<i className={ item.is_owner_suspended ? 'bb-icons-rl bb-icons-rl-plus-circle' : 'bb-icons-rl bb-icons-rl-minus-circle' }></i>
																			{ item.is_owner_suspended ? __( 'Unsuspend Owner', 'buddyboss' ) : __( 'Suspend Owner', 'buddyboss' ) }
																		</span>
																	) : (
																		item.is_owner_suspended ? (
																			<button
																				className="bb-admin-reported-content__menu-item"
																				onClick={ function () { handleSuspendAction( item, 'unsuspend' ); } }
																			>
																				<i className="bb-icons-rl bb-icons-rl-plus-circle"></i>
																				{ __( 'Unsuspend Owner', 'buddyboss' ) }
																			</button>
																		) : (
																			<button
																				className="bb-admin-reported-content__menu-item"
																				onClick={ function () { handleSuspendAction( item, 'suspend' ); } }
																			>
																				<i className="bb-icons-rl bb-icons-rl-minus-circle"></i>
																				{ __( 'Suspend Owner', 'buddyboss' ) }
																			</button>
																		)
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
								<div className="bb-admin-reported-content__pagination">
									<span className="bb-admin-reported-content__page-total">
										{ total + ' ' + ( total === 1 ? __( 'item', 'buddyboss' ) : __( 'items', 'buddyboss' ) ) }
									</span>
									<button
										className="bb-admin-reported-content__page-btn bb-admin-reported-content__page-btn--nav"
										disabled={ page <= 1 }
										onClick={ function () { handlePageChange( page - 1 ); } }
									>
										<i className="bb-icons-rl bb-icons-rl-caret-left"></i>
									</button>
									{ getPageNumbers( page, totalPages ).map( function ( p, idx ) {
										if ( p === '...' ) {
											return (
												<span key={ 'ellipsis-' + idx } className="bb-admin-reported-content__page-ellipsis">
													{ '...' }
												</span>
											);
										}
										return (
											<button
												key={ p }
												className={ 'bb-admin-reported-content__page-btn' + ( p === page ? ' bb-admin-reported-content__page-btn--active' : '' ) }
												onClick={ function () { handlePageChange( p ); } }
											>
												{ p }
											</button>
										);
									} ) }
									<button
										className="bb-admin-reported-content__page-btn bb-admin-reported-content__page-btn--nav"
										disabled={ page >= totalPages }
										onClick={ function () { handlePageChange( page + 1 ); } }
									>
										<i className="bb-icons-rl bb-icons-rl-caret-right"></i>
									</button>
								</div>
							) }
						</>
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
