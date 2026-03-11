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
import { getFlaggedMembers, suspendMember, unsuspendMember } from '../utils/ajax';
import { ViewReportModal } from '../components/modals/ViewReportModal';

/**
 * Flagged Members Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {Function} props.onNavigate   Navigation callback.
 * @returns {JSX.Element} Flagged members screen.
 */
export function FlaggedMembersScreen( { onNavigate } ) {
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

	// 3-dot menu state.
	var openMenuState = useState( null );
	var openMenuId = openMenuState[ 0 ];
	var setOpenMenuId = openMenuState[ 1 ];

	// View report modal state.
	var reportModalState = useState( null );
	var reportModalMember = reportModalState[ 0 ];
	var setReportModalMember = reportModalState[ 1 ];

	// Action in progress.
	var actionInProgressState = useState( null );
	var actionInProgress = actionInProgressState[ 0 ];
	var setActionInProgress = actionInProgressState[ 1 ];

	var abortRef = useRef( null );

	var PER_PAGE = 20;

	// Fetch members.
	var fetchMembers = useCallback( function ( pageNum, searchTerm ) {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		var controller = new AbortController();
		abortRef.current = controller;

		setIsLoading( true );
		getFlaggedMembers(
			{ page: pageNum, per_page: PER_PAGE, search: searchTerm || '' },
			{ signal: controller.signal }
		)
			.then( function ( response ) {
				setIsLoading( false );
				if ( response.success && response.data ) {
					setMembers( response.data.members || [] );
					setTotal( response.data.total || 0 );
					setTotalPages( response.data.total_pages || 1 );
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
		fetchMembers( 1, '' );
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
		fetchMembers( 1, search );
	}, [ search, fetchMembers ] );

	// Handle page change.
	var handlePageChange = useCallback( function ( newPage ) {
		setPage( newPage );
		fetchMembers( newPage, search );
	}, [ search, fetchMembers ] );

	// Handle suspend/unsuspend.
	var handleSuspendAction = useCallback( function ( member, action ) {
		setActionInProgress( member.user_id );
		setOpenMenuId( null );

		var promise = ( 'suspend' === action ) ? suspendMember( member.user_id ) : unsuspendMember( member.user_id );

		promise
			.then( function ( response ) {
				setActionInProgress( null );
				if ( response.success ) {
					// Refresh the list.
					fetchMembers( page, search );
				}
			} )
			.catch( function () {
				setActionInProgress( null );
			} );
	}, [ page, search, fetchMembers ] );

	// Handle view report.
	var handleViewReport = useCallback( function ( member ) {
		setOpenMenuId( null );
		setReportModalMember( member );
	}, [] );

	return (
		<div className="bb-admin-flagged-members">
			<div className="bb-admin-flagged-members__card">
				{/* Title */}
				<div className="bb-admin-flagged-members__title-bar">
					<h2 className="bb-admin-flagged-members__title">
						{ __( 'Flagged Members', 'buddyboss' ) }
					</h2>
					<form className="bb-admin-flagged-members__search-form" onSubmit={ handleSearch }>
						<input
							type="text"
							className="bb-admin-flagged-members__search-input"
							placeholder={ __( 'Search members...', 'buddyboss' ) }
							value={ search }
							onChange={ function ( e ) { setSearch( e.target.value ); } }
						/>
						<button type="submit" className="bb-admin-flagged-members__search-btn">
							<i className="bb-icons-rl bb-icons-rl-search"></i>
						</button>
					</form>
				</div>

				{/* Body */}
				<div className="bb-admin-flagged-members__body">
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
									return (
										<div key={ member.id } className="bb-admin-flagged-members__list-item">
											{/* Dot indicator */}
											<span className="bb-admin-flagged-members__dot"></span>

											{/* Items row */}
											<div className="bb-admin-flagged-members__items">
												{/* Member info */}
												<div className="bb-admin-flagged-members__member-col">
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

												{/* Reports */}
												<div className={ 'bb-admin-flagged-members__reports-col' + ( member.reports > 0 ? ' bb-admin-flagged-members__col--active' : '' ) }>
													<i className="bb-icons-rl bb-icons-rl-flag"></i>
													{ member.reports + ' ' + ( member.reports > 1 ? __( 'reports', 'buddyboss' ) : __( 'report', 'buddyboss' ) ) }
												</div>

												{/* Blocks */}
												<div className={ 'bb-admin-flagged-members__blocks-col' + ( member.blocks > 0 ? ' bb-admin-flagged-members__col--active' : '' ) }>
													<i className="bb-icons-rl bb-icons-rl-prohibit"></i>
													{ member.blocks + ' ' + ( member.blocks > 1 ? __( 'blocks', 'buddyboss' ) : __( 'block', 'buddyboss' ) ) }
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
