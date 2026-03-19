/**
 * BuddyBoss Admin Settings 2.0 - View Content Report Modal
 *
 * Modal for viewing report details (reporters) for a reported content item.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, Modal, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { getContentReport } from '../../utils/ajax';

/**
 * View Content Report Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                Component props.
 * @param {boolean}  props.isOpen         Whether modal is open.
 * @param {Function} props.onClose        Close handler.
 * @param {Object}   props.item           Content item data with id, item_id, item_type, etc.
 * @returns {JSX.Element|null} Modal element or null.
 */
export function ViewContentReportModal( { isOpen, onClose, item } ) {
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
		if ( ! isOpen || ! item ) {
			return;
		}

		setIsLoading( true );
		setError( '' );
		setReport( null );

		getContentReport( item.id )
			.then( function ( response ) {
				setIsLoading( false );
				if ( response.success && response.data ) {
					setReport( response.data );
				} else {
					setError( ( response.data && response.data.message ) || __( 'Failed to load report.', 'buddyboss' ) );
				}
			} )
			.catch( function () {
				setIsLoading( false );
				setError( __( 'Failed to load report.', 'buddyboss' ) );
			} );
	}, [ isOpen, item ] );

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ __( 'View Report', 'buddyboss' ) }
			onRequestClose={ onClose }
			className="bb-admin-view-content-report-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-view-content-report-modal__body">
				{ isLoading && (
					<div className="bb-admin-view-content-report-modal__loading">
						<Spinner />
					</div>
				) }

				{ error && (
					<div className="bb-admin-view-content-report-modal__error">
						{ error }
					</div>
				) }

				{ report && ! isLoading && (
					<>
						{/* Summary Bar */}
						<div className="bb-admin-view-content-report-modal__summary">
							<div className="bb-admin-view-content-report-modal__owner-info">
								{ report.owner && report.owner.avatar && (
									<img
										src={ report.owner.avatar }
										alt={ report.owner.display_name }
										className="bb-admin-view-content-report-modal__avatar"
									/>
								) }
								{ report.owner && report.owner.display_name && (
									<span>
										<a
											href={ report.owner.profile_url }
											target="_blank"
											rel="noopener noreferrer"
											className="bb-admin-view-content-report-modal__owner-name"
										>
											{ report.owner.display_name }
										</a>
										<span className="bb-admin-view-content-report-modal__owner-label">
											{ ' (' + __( 'Owner', 'buddyboss' ) + ')' }
										</span>
									</span>
								) }
							</div>
							<span className="bb-admin-view-content-report-modal__stat">
								<i className="bb-icons-rl bb-icons-rl-flag"></i>
								{ report.reports + ' ' + ( report.reports === 1 ? __( 'report', 'buddyboss' ) : __( 'reports', 'buddyboss' ) ) }
							</span>
							{ report.content_url && (
								<a
									href={ report.content_url }
									target="_blank"
									rel="noopener noreferrer"
									className="bb-admin-view-content-report-modal__content-link"
								>
									<i className={ report.content_icon }></i>
									{ report.content_label + ' #' + report.item_id }
									<span className="bb-admin-view-content-report-modal__content-link-external">
										<i className="bb-icons-rl bb-icons-rl-arrow-up-right"></i>
									</span>
								</a>
							) }
							{ report.is_hidden && (
								<span className="bb-admin-view-content-report-modal__hidden-badge">
									<i className="bb-icons-rl bb-icons-rl-eye-slash"></i>
									{ __( 'Hidden', 'buddyboss' ) }
								</span>
							) }
						</div>

						{/* Reporter Section */}
						{ report.reporters && report.reporters.length > 0 && (
							<div className="bb-admin-view-content-report-modal__section">
								<h4 className="bb-admin-view-content-report-modal__section-title">
									{ __( 'Reporter', 'buddyboss' ) }
								</h4>
								<div className="bb-admin-view-content-report-modal__list">
									{ report.reporters.map( function ( reporter, index ) {
										return (
											<div key={ index } className="bb-admin-view-content-report-modal__list-item">
												<div className="bb-admin-view-content-report-modal__list-item-user">
													<img
														src={ reporter.avatar }
														alt={ reporter.display_name }
														className="bb-admin-view-content-report-modal__list-avatar"
													/>
													<a
														href={ reporter.profile_url }
														target="_blank"
														rel="noopener noreferrer"
														className="bb-admin-view-content-report-modal__list-name"
													>
														{ reporter.display_name }
													</a>
												</div>
												<div className="bb-admin-view-content-report-modal__list-item-detail">
													<strong className="bb-admin-view-content-report-modal__category-name">
														{ reporter.category_name }
													</strong>
													<span className="bb-admin-view-content-report-modal__category-desc">
														{ reporter.category_desc }
													</span>
												</div>
												<div className="bb-admin-view-content-report-modal__list-item-date">
													{ reporter.date }
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

			<div className="bb-admin-settings-modal__footer bb-admin-view-content-report-modal__footer">
				<Button
					variant="primary"
					className="bb-admin-view-content-report-modal__close-btn"
					onClick={ onClose }
				>
					{ __( 'Close', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}

export default ViewContentReportModal;
