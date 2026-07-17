/**
 * BuddyBoss Admin Settings 2.0 - Email Missing Modal
 *
 * Modal for viewing missing email templates and installing them
 * or resetting all emails to defaults. Uses the existing
 * bp_admin_repair_tools_wrapper_function AJAX action with
 * type=bp-missing-emails and type=bp-reinstall-emails.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import {
	Modal,
	Button,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Call the existing repair tools AJAX action.
 *
 * Uses the bp-do-counts nonce (repairNonce from bbAdminData)
 * instead of the Settings 2.0 nonce.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} type Repair type — 'bp-missing-emails' or 'bp-reinstall-emails'.
 * @return {Promise} Promise resolving to parsed JSON response.
 */
function callRepairTool( type ) {
	var ajaxUrl = ( window.bbAdminData && window.bbAdminData.ajaxUrl ) || window.ajaxurl || '';
	var repairNonce = ( window.bbAdminData && window.bbAdminData.repairNonce ) || '';

	var formData = new FormData();
	formData.append( 'action', 'bp_admin_repair_tools_wrapper_function' );
	formData.append( 'type', type );
	formData.append( 'nonce', repairNonce );

	return fetch( ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	} ).then( function ( response ) {
		if ( ! response.ok ) {
			return { success: false, data: { message: response.statusText } };
		}
		return response.json();
	} );
}

/**
 * Email Missing Modal Component
 *
 * Shows a list of missing email templates with options to install
 * just the missing ones or reset all emails to defaults.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                Component props.
 * @param {boolean}  props.isOpen         Whether the modal is open.
 * @param {number}   props.missingCount   Count of missing emails.
 * @param {Array}    props.missingEmails  Array of missing email objects [{slug, description}].
 * @param {Function} props.onClose        Close handler.
 * @param {Function} props.onInstalled    Success handler after install/reset.
 * @param {Function} props.setToast       Toast notification setter.
 * @returns {JSX.Element|null} Modal or null.
 */
export function EmailMissingModal( { isOpen, isLoading, missingCount, missingEmails, onClose, onInstalled, setToast } ) {
	var processingState = useState( '' );
	var processing = processingState[0];
	var setProcessing = processingState[1];

	var isMountedRef = useRef( true );

	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
		};
	}, [] );

	if ( ! isOpen ) {
		return null;
	}

	var handleClose = function () {
		if ( processing ) {
			return;
		}
		onClose();
	};

	/**
	 * Handle install missing emails via existing repair tool.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleInstallMissing = function () {
		if ( processing ) {
			return;
		}

		setProcessing( 'install' );

		callRepairTool( 'bp-missing-emails' ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setProcessing( '' );
			if ( response.success ) {
				if ( setToast ) {
					setToast( { status: 'success', message: __( 'Missing email templates installed successfully.', 'buddyboss-platform' ) } );
				}
				if ( onInstalled ) {
					onInstalled();
				}
			} else {
				if ( setToast ) {
					setToast( {
						status: 'error',
						message: __( 'Failed to install missing emails.', 'buddyboss-platform' ),
					} );
				}
			}
		} ).catch( function () {
			if ( ! isMountedRef.current ) {
				return;
			}
			setProcessing( '' );
			if ( setToast ) {
				setToast( { status: 'error', message: __( 'An error occurred.', 'buddyboss-platform' ) } );
			}
		} );
	};

	/**
	 * Handle reset all emails via existing repair tool.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleResetAll = function () {
		if ( processing ) {
			return;
		}

		setProcessing( 'reset' );

		callRepairTool( 'bp-reinstall-emails' ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setProcessing( '' );
			if ( response.success ) {
				if ( setToast ) {
					setToast( { status: 'success', message: __( 'All email templates have been reset to defaults.', 'buddyboss-platform' ) } );
				}
				if ( onInstalled ) {
					onInstalled();
				}
			} else {
				if ( setToast ) {
					setToast( {
						status: 'error',
						message: __( 'Failed to reset email templates.', 'buddyboss-platform' ),
					} );
				}
			}
		} ).catch( function () {
			if ( ! isMountedRef.current ) {
				return;
			}
			setProcessing( '' );
			if ( setToast ) {
				setToast( { status: 'error', message: __( 'An error occurred.', 'buddyboss-platform' ) } );
			}
		} );
	};

	return (
		<Modal
			title={
				<span className="bb-email-missing-modal__title-wrap">
					{ __( 'Email Missing', 'buddyboss-platform' ) }
					{ missingCount > 0 && (
						<span className="bb-email-missing-modal__count-badge">
							{ missingCount }
						</span>
					) }
				</span>
			}
			onRequestClose={ handleClose }
			className="bb-admin-settings-modal bb-email-missing-modal"
			shouldCloseOnClickOutside={ false }
		>
			{ isLoading ? (
				<div className="bb-email-missing-modal__loading">
					<Spinner />
				</div>
			) : (
				<>
					<div className="bb-admin-settings-modal__body bb-email-missing-modal__body">
						{/* Warning notice */}
						<div className="bb-email-missing-modal__warning">
							<i className="bb-icons-rl bb-icons-rl-warning-circle" />
							<span>{ __( 'Missing Email Template', 'buddyboss-platform' ) }</span>
						</div>

						{/* Missing email list */}
						{ missingEmails && missingEmails.length > 0 && (
							<ul className="bb-email-missing-modal__list">
								{ missingEmails.map( function ( email ) {
									return (
										<li key={ email.slug } className="bb-email-missing-modal__list-item">
											{ decodeEntities( email.description ) }
										</li>
									);
								} ) }
							</ul>
						) }
					</div>

					<div className="bb-admin-settings-modal__footer bb-email-missing-modal__footer">
						<Button
							variant="secondary"
							onClick={ handleResetAll }
							isBusy={ 'reset' === processing }
							disabled={ !! processing }
						>
							{ 'reset' === processing ? __( 'Resetting...', 'buddyboss-platform' ) : __( 'Reset All Emails', 'buddyboss-platform' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleInstallMissing }
							isBusy={ 'install' === processing }
							disabled={ !! processing }
						>
							{ 'install' === processing ? __( 'Installing...', 'buddyboss-platform' ) : __( 'Install Missing Emails', 'buddyboss-platform' ) }
						</Button>
					</div>
				</>
			) }
		</Modal>
	);
}
