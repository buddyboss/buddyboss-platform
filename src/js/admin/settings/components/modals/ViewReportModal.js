/**
 * BuddyBoss Admin Settings 2.0 - View Report Modal
 *
 * Modal for viewing report details (reporters and blockers) for a flagged member.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, Modal, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getMemberReport } from '../../utils/ajax';
import { safeUrl } from '../../utils/sanitize';

/**
 * View Report Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {boolean}  props.isOpen       Whether modal is open.
 * @param {Function} props.onClose      Close handler.
 * @param {Object}   props.member       Member data with user_id, display_name, etc.
 * @returns {JSX.Element|null} Modal element or null.
 */
export function ViewReportModal( { isOpen, onClose, member } ) {
	var reportState = useState( null );
	var report = reportState[ 0 ];
	var setReport = reportState[ 1 ];

	var isLoadingState = useState( false );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	// Fetch report data when modal opens.
	useEffect( function () {
		if ( ! isOpen || ! member ) {
			return;
		}

		var controller = new AbortController();

		setIsLoading( true );
		setError( '' );
		setReport( null );

		getMemberReport( member.user_id, member.id, { signal: controller.signal } )
			.then( function ( response ) {
				setIsLoading( false );
				if ( response.success && response.data ) {
					setReport( response.data );
				} else {
					setError( ( response.data && response.data.message ) || __( 'Failed to load report.', 'buddyboss' ) );
				}
			} )
			.catch( function ( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setIsLoading( false );
				setError( __( 'Failed to load report.', 'buddyboss' ) );
			} );

		return function () {
			controller.abort();
		};
	}, [ isOpen, member ] );

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ __( 'View Report', 'buddyboss' ) }
			onRequestClose={ onClose }
			className="bb-admin-view-report-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-view-report-modal__body">
				{ isLoading && (
					<div className="bb-admin-view-report-modal__loading">
						<Spinner />
					</div>
				) }

				{ error && (
					<div className="bb-admin-view-report-modal__error">
						{ error }
					</div>
				) }

				{ report && ! isLoading && (
					<>
						{/* Member Summary */}
						<div className="bb-admin-view-report-modal__summary">
							<div className="bb-admin-view-report-modal__member-info">
								<img
									src={ safeUrl( report.avatar ) }
									alt={ report.display_name }
									className="bb-admin-view-report-modal__avatar"
								/>
								<a
									href={ safeUrl( report.profile_url ) }
									target="_blank"
									rel="noopener noreferrer"
									className="bb-admin-view-report-modal__name"
								>
									{ report.display_name }
								</a>
							</div>
							<div className="bb-admin-view-report-modal__stats">
								<span className="bb-admin-view-report-modal__stat">
									<i className="bb-icons-rl bb-icons-rl-flag"></i>
									{ report.reports + ' ' + ( report.reports === 1 ? __( 'report', 'buddyboss' ) : __( 'reports', 'buddyboss' ) ) }
								</span>
								<span className="bb-admin-view-report-modal__stat">
									<i className="bb-icons-rl bb-icons-rl-prohibit"></i>
									{ report.blocks + ' ' + ( report.blocks === 1 ? __( 'block', 'buddyboss' ) : __( 'blocks', 'buddyboss' ) ) }
								</span>
								{ report.is_suspended && (
									<span className="bb-admin-view-report-modal__suspended-badge">
										{ __( 'Suspended', 'buddyboss' ) }
									</span>
								) }
							</div>
						</div>

						{/* Reporter Section */}
						{ report.reporters && report.reporters.length > 0 && (
							<div className="bb-admin-view-report-modal__section">
								<h4 className="bb-admin-view-report-modal__section-title">
									{ __( 'Reporter', 'buddyboss' ) }
								</h4>
								<div className="bb-admin-view-report-modal__list">
									{ report.reporters.map( function ( reporter, index ) {
										return (
											<div key={ index } className="bb-admin-view-report-modal__list-item">
												<div className="bb-admin-view-report-modal__list-item-user">
													<img
														src={ safeUrl( reporter.avatar ) }
														alt={ reporter.display_name }
														className="bb-admin-view-report-modal__list-avatar"
													/>
													<a
														href={ safeUrl( reporter.profile_url ) }
														target="_blank"
														rel="noopener noreferrer"
														className="bb-admin-view-report-modal__list-name"
													>
														{ reporter.display_name }
													</a>
												</div>
												<div className="bb-admin-view-report-modal__list-item-detail">
													<strong className="bb-admin-view-report-modal__category-name">
														{ reporter.category_name }
													</strong>
													<span className="bb-admin-view-report-modal__category-desc">
														{ reporter.category_desc }
													</span>
												</div>
												<div className="bb-admin-view-report-modal__list-item-date">
													{ reporter.date }
												</div>
											</div>
										);
									} ) }
								</div>
							</div>
						) }

						{/* Blocker Section */}
						{ report.blockers && report.blockers.length > 0 && (
							<div className="bb-admin-view-report-modal__section">
								<h4 className="bb-admin-view-report-modal__section-title">
									{ __( 'Blocker', 'buddyboss' ) }
								</h4>
								<div className="bb-admin-view-report-modal__list">
									{ report.blockers.map( function ( blocker, index ) {
										return (
											<div key={ index } className="bb-admin-view-report-modal__list-item bb-admin-view-report-modal__list-item--blocker">
												<div className="bb-admin-view-report-modal__list-item-user">
													<img
														src={ safeUrl( blocker.avatar ) }
														alt={ blocker.display_name }
														className="bb-admin-view-report-modal__list-avatar"
													/>
													<a
														href={ safeUrl( blocker.profile_url ) }
														target="_blank"
														rel="noopener noreferrer"
														className="bb-admin-view-report-modal__list-name"
													>
														{ blocker.display_name }
													</a>
												</div>
												<div className="bb-admin-view-report-modal__list-item-date">
													{ blocker.date }
												</div>
											</div>
										);
									} ) }
								</div>
							</div>
						) }
					</>
				) }
			</div>

			<div className="bb-admin-settings-modal__footer bb-admin-view-report-modal__footer">
				<Button
					variant="primary"
					className="bb-admin-view-report-modal__close-btn"
					onClick={ onClose }
				>
					{ __( 'Close', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}

export default ViewReportModal;
